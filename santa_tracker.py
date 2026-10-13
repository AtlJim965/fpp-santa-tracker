#!/usr/bin/env python3
"""
Santa Tracker for FPP - engine for the fpp-santa-tracker plugin.

Draws a world map with Santa's live position (from Google's Santa Tracker
route) plus an info panel, or scrolls a text message, on a Pixel Overlay
Model. All settings come from the plugin's settings page
(<media>/config/plugin.fpp-santa-tracker); nothing needs editing here.

Standard Python 3 only - no extra packages.

Command line (normally run for you by the plugin's FPP Commands):
  santa_tracker.py --loop                 keep the display running and updating
  santa_tracker.py --once [--seconds N]   show once for N seconds, then exit
  santa_tracker.py --stop                 stop any running display and clear it
  santa_tracker.py --status               print whether the display is running
  santa_tracker.py --at d7 --once         show a preview moment on the matrix
  santa_tracker.py --preview-json         render the map, print it as JSON
  santa_tracker.py --moments-json         list the preview moments as JSON

  --at accepts a moment key (now, d30, d14, d7, d3, d1, eve_takeoff, eve_noon,
  eve_6pm, eve_9pm, home_soon, home_here, landed) or an ISO time such as
  2026-12-24T22:00 (read in the plugin's time zone).
"""

import argparse
import base64
import json
import math
import os
import signal
import subprocess
import sys
import time
import unicodedata
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timedelta, timezone

try:
    from zoneinfo import ZoneInfo
except Exception:                       # Python < 3.9
    ZoneInfo = None

REPO = "fpp-santa-tracker"
MEDIA_DIR = os.environ.get("MEDIADIR") or "/home/fpp/media"
CONFIG_FILE = os.path.join(MEDIA_DIR, "config", "plugin." + REPO)
DATA_DIR = os.path.join(MEDIA_DIR, "plugindata", REPO)
LOG_DIR = os.environ.get("LOGDIR") or os.path.join(MEDIA_DIR, "logs")
LOG_FILE = os.path.join(LOG_DIR, f"plugin-{REPO}.log")
IMAGES_DIR = os.path.join(MEDIA_DIR, "images")
FPP_HOST = "127.0.0.1"
SCRIPT_PATH = os.path.abspath(__file__)
PYTHON = sys.executable or "/usr/bin/python3"

ROUTE_URL = ("https://firebasestorage.googleapis.com/v0/b/"
             "santa-tracker-firebase.appspot.com/o/route%2Fsanta_en.json?alt=media")
ROUTE_MAX_AGE = 6 * 3600
LAND_URLS = [
    "https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_land.geojson",
    "https://cdn.jsdelivr.net/gh/nvkelso/natural-earth-vector@master/geojson/ne_110m_land.geojson",
    "https://d2ad6b4ur7yvpq.cloudfront.net/naturalearth-3.3.0/ne_110m_land.geojson",
]
# Google's published route is stamped for Dec 24 2019 (takeoff 10:00 UTC).
ROUTE_BASE_TAKEOFF_MS = 1577181600000

# ============================ SETTINGS ===================================
# Defaults for every setting on the plugin page (all stored as text, the way
# FPP stores plugin settings).
DEFAULTS = {
    # display
    "ModelName": "",                    # blank = first Pixel Overlay Model
    "DisplayMode": "map",               # map | text | both
    "MapSeconds": "20",                 # Show Once / Both: seconds for the map
    "RefreshSeconds": "30",             # running display: how often to update
    "AutoStart": "0",                   # start the display when FPP starts
    # location
    "Latitude": "",                     # blank = FPP's own location setting
    "Longitude": "",
    "TimeZone": "",                     # blank = FPP's time zone
    "CityName": "",
    "State": "",
    "AddHomeStop": "1",                 # make your town one of Santa's stops
    "HomeApproachMin": "3",
    "HomeStayMin": "2",
    # map colors
    "LandColor": "#00DC46",
    "OceanColor": "#000000",
    "HomeColor": "#FFFFFF",
    "SantaColor": "#FF0000",
    "TrailColor": "#FFB400",
    "ShowTrail": "1",
    # right-hand panel colors
    "PanelColorMode": "lines",          # lines | single | letter | word | line
    "PanelPalette": "Christmas",
    "PanelCustomColors": "#FF0000,#00C83C,#FFFFFF",
    "PanelSingleColor": "#FFFFFF",
    "HeadingColor": "#FFAA00",          # NOW IN: / FLYING TO: / HOME!
    "CityColor": "#FFFFFF",
    "RegionColor": "#787878",           # state / country
    "EtaColor": "#FFFFFF",              # ETA 3 MIN
    "DistanceColor": "#FF0000",         # 4,123 MI / CLOSE BY!
    "GiftsColor": "#00C83C",            # 6.8B GIFTS DELIVERED
    "CountdownTextColor": "#FFFFFF",    # SANTA BEGINS HIS JOURNEY IN ... DAYS
    "CountdownNumberColor": "#FF0000",  # 73
    "Greeting1Color": "#FF0000",        # HE'S HERE! / MERRY
    "Greeting2Color": "#00C83C",        # HO HO HO! / CHRISTMAS!
    # wording
    "CountdownPhrase": "begins his journey",
    "ShowDistance": "1",
    "ShowGifts": "1",
    # scrolling text mode
    "TextFont": "FreeSansBold",
    "TextFontSize": "24",
    "TextScrollSpeed": "40",
    "TextColor": "#FF0000",
    "TextColorMode": "Single",
    "TextPalette": "Christmas",
    # advanced
    "MapWidth": "0",                    # 0 = automatic
    "PanelRightMargin": "2",
}

PALETTES = {
    "Christmas": ["#FF0000", "#00C83C", "#FFFFFF"],
    "Red & Green": ["#FF0000", "#00C83C"],
    "Candy Cane": ["#FF0000", "#FFFFFF"],
    "Rainbow": ["#FF0000", "#FF8000", "#FFFF00", "#00FF00", "#0080FF", "#A000FF"],
    "Winter": ["#0080FF", "#FFFFFF", "#80D0FF"],
    "Gold & Silver": ["#FFAA00", "#C8C8C8"],
}

CFG = dict(DEFAULTS)
# SANTA_QUIET=1: messages go only to the plugin log (stderr is already the log)
QUIET = os.environ.get("SANTA_QUIET") == "1"


def log(msg):
    try:
        stamp = datetime.now(tz()).strftime('%Y-%m-%d %H:%M:%S')
    except Exception:
        stamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    line = f"{stamp} [santa pid {os.getpid()}] {msg}"
    if not QUIET:
        print(line, file=sys.stderr, flush=True)
    try:
        if os.path.isdir(LOG_DIR):
            new = not os.path.exists(LOG_FILE)
            with open(LOG_FILE, "a") as f:
                f.write(line + "\n")
            if new:
                own(LOG_FILE)
    except Exception:
        pass


def own(path):
    """Files made while running as root (FPP Commands) are handed to the
    media folder's owner so the settings page can still update them."""
    try:
        if os.geteuid() == 0:
            st = os.stat(MEDIA_DIR)
            os.chown(path, st.st_uid, st.st_gid)
    except Exception:
        pass


def ensure_data_dir():
    try:
        if not os.path.isdir(DATA_DIR):
            os.makedirs(DATA_DIR, exist_ok=True)
            own(DATA_DIR)
    except Exception:
        pass
    return DATA_DIR if os.path.isdir(DATA_DIR) else os.path.dirname(SCRIPT_PATH)


# ------------------------------------------------------------- settings
def read_plugin_settings(path=CONFIG_FILE):
    """FPP plugin settings file: one `Name = "value"` per line."""
    out = {}
    try:
        with open(path) as f:
            for raw in f:
                if "=" not in raw:
                    continue
                k, v = raw.split("=", 1)
                v = v.strip()
                if len(v) >= 2 and v[0] == v[-1] == '"':
                    v = v[1:-1]
                out[k.strip()] = v
    except FileNotFoundError:
        pass
    except Exception as e:
        log(f"Couldn't read settings: {e}")
    return out


def fpp_setting(name):
    """Read one of FPP's own settings (location, time zone) through its API."""
    try:
        with urllib.request.urlopen(
                f"http://{FPP_HOST}/api/settings/{urllib.parse.quote(name)}", timeout=5) as r:
            body = r.read().decode(errors="replace").strip()
        try:
            j = json.loads(body)
        except ValueError:
            return body.strip('"')
        if isinstance(j, dict):
            for key in ("value", "Value", name):
                if key in j and not isinstance(j[key], (dict, list)):
                    return str(j[key])
            return ""
        return "" if isinstance(j, list) else str(j)
    except Exception:
        return ""


def load_settings(override_path=None):
    CFG.clear()
    CFG.update(DEFAULTS)
    CFG.update(read_plugin_settings())
    if override_path:
        try:
            with open(override_path) as f:
                extra = json.load(f)
            CFG.update({k: str(v) for k, v in extra.items() if k in DEFAULTS})
        except Exception as e:
            log(f"Couldn't read preview settings: {e}")
    for key, fpp_key in (("Latitude", "Latitude"), ("Longitude", "Longitude"),
                         ("TimeZone", "TimeZone")):
        if not CFG.get(key, "").strip():
            CFG[key] = fpp_setting(fpp_key)


def cfg_str(k):
    return str(CFG.get(k, DEFAULTS.get(k, ""))).strip()


def cfg_bool(k):
    return cfg_str(k).lower() in ("1", "true", "yes", "on")


def cfg_num(k, lo=None, hi=None):
    try:
        v = float(cfg_str(k))
    except ValueError:
        v = float(DEFAULTS.get(k) or 0)
    if lo is not None:
        v = max(lo, v)
    if hi is not None:
        v = min(hi, v)
    return v


def hex_rgb(s, fallback=(255, 255, 255)):
    s = (s or "").strip().lstrip("#")
    if len(s) == 3:
        s = "".join(c * 2 for c in s)
    try:
        return tuple(int(s[i:i + 2], 16) for i in (0, 2, 4))
    except Exception:
        return fallback


def cfg_color(k):
    return hex_rgb(cfg_str(k), hex_rgb(DEFAULTS.get(k, "#FFFFFF")))


def home_latlng():
    try:
        return float(cfg_str("Latitude")), float(cfg_str("Longitude"))
    except ValueError:
        return 0.0, 0.0


def home_name():
    return cfg_str("CityName") or "Your Town"


def tz():
    name = cfg_str("TimeZone")
    if name and ZoneInfo:
        try:
            return ZoneInfo(name)
        except Exception:
            pass
    return datetime.now().astimezone().tzinfo


# ------------------------------------------------------------- route data
def load_route():
    cache = os.path.join(ensure_data_dir(), "santa_route.json")
    fresh = os.path.exists(cache) and time.time() - os.path.getmtime(cache) < ROUTE_MAX_AGE
    if not fresh:
        try:
            req = urllib.request.Request(ROUTE_URL, headers={"User-Agent": "fpp-santa-tracker"})
            with urllib.request.urlopen(req, timeout=20) as r:
                data = json.loads(r.read().decode("utf-8"))
            for d in data.get("destinations", []):
                d.pop("details", None)           # drop the bulky photo data
            tmp = cache + ".tmp"
            with open(tmp, "w") as f:
                json.dump(data, f)
            os.replace(tmp, cache)
            own(cache)
            log(f"Route downloaded ({len(data['destinations'])} stops)")
        except Exception as e:
            if not os.path.exists(cache):
                raise RuntimeError(f"Can't download Santa's route: {e}")
            log(f"Route download failed ({e}); using the saved copy")
    with open(cache) as f:
        return json.load(f)["destinations"]


def season_year(now_ms):
    now = datetime.fromtimestamp(now_ms / 1000, tz=timezone.utc)
    return now.year + 1 if (now.month, now.day) > (12, 26) else now.year


def year_offset_ms(dests, now_ms):
    """Shift the route so takeoff is Dec 24 10:00 UTC of this season."""
    takeoff = dests[0]["departure"]
    base = takeoff if takeoff > 1700000000000 else ROUTE_BASE_TAKEOFF_MS
    target = datetime(season_year(now_ms), 12, 24, 10, 0, tzinfo=timezone.utc).timestamp() * 1000
    return int(target - base)


# ------------------------------------------------------------- helpers
def miles_between(lat1, lng1, lat2, lng2):
    r = 3958.8
    p1, p2 = math.radians(lat1), math.radians(lat2)
    dp, dl = p2 - p1, math.radians(lng2 - lng1)
    a = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * r * math.asin(math.sqrt(min(1.0, a)))


def place(d):
    city, region = d.get("city", ""), d.get("region", "")
    return f"{city}, {region}" if region and region != city else city


def interpolate(a, b, frac):
    lat = a["lat"] + (b["lat"] - a["lat"]) * frac
    dl = b["lng"] - a["lng"]
    if dl > 180:
        dl -= 360
    elif dl < -180:
        dl += 360
    return lat, (a["lng"] + dl * frac + 540) % 360 - 180


def mins_text(ms):
    m = max(1, round(ms / 60000))
    return "1 min" if m == 1 else f"{m} min"


# ------------------------------------------------------------- your town
def add_home_stop(dests):
    """Insert your town as a stop right after the nearest real stop. Later
    stops are pushed back by the extra minutes so the night still flows."""
    if not cfg_bool("AddHomeStop") or len(dests) < 4:
        return dests
    if any(d.get("id") == "home_stop" for d in dests):
        return dests
    hlat, hlng = home_latlng()
    i = min(range(1, len(dests) - 1), key=lambda k: miles_between(
        hlat, hlng, dests[k]["location"]["lat"], dests[k]["location"]["lng"]))
    prev, nxt = dests[i], dests[i + 1]
    gap = max(60000, nxt["arrival"] - prev["departure"])
    approach = int(cfg_num("HomeApproachMin", 1, 30) * 60000)
    stay = int(cfg_num("HomeStayMin", 1, 30) * 60000)
    arrival = prev["departure"] + approach
    home = {
        "id": "home_stop", "city": home_name(), "region": cfg_str("State"),
        "arrival": arrival, "departure": arrival + stay,
        "presentsDelivered": (prev.get("presentsDelivered", 0) +
                              nxt.get("presentsDelivered", 0)) // 2,
        "location": {"lat": hlat, "lng": hlng},
    }
    shift = approach + stay + gap - (nxt["arrival"] - prev["departure"])
    later = [dict(d, arrival=d["arrival"] + shift, departure=d["departure"] + shift)
             for d in dests[i + 1:]]
    return dests[:i + 1] + [home] + later


def nearest_stop(dests):
    """Closest real stop on Santa's route to your coordinates (a quick
    check that the location is right)."""
    hlat, hlng = home_latlng()
    best = None
    for d in dests[1:-1]:
        if d.get("id") == "home_stop":
            continue
        mi = miles_between(hlat, hlng, d["location"]["lat"], d["location"]["lng"])
        if best is None or mi < best[0]:
            best = (mi, place(d))
    if not best:
        return None
    return {"name": best[1], "miles": round(best[0]), "lat": hlat, "lng": hlng}


def season_stops(dests, now_ms):
    dests = add_home_stop(dests)
    off = year_offset_ms(dests, now_ms)
    return [dict(d, arrival=d["arrival"] + off, departure=d["departure"] + off)
            for d in dests]


# ------------------------------------------------------------- where is he?
def santa_status(dests, now_ms):
    stops = season_stops(dests, now_ms)
    takeoff, landing = stops[0], stops[-1]
    st = {"state": "unknown", "loc": takeoff["location"], "dest": None,
          "left_ms": 0, "gifts": 0, "visited": []}
    if now_ms < takeoff["departure"]:
        st.update(state="countdown", left_ms=int(takeoff["departure"] - now_ms), now_ms=now_ms)
    elif now_ms >= landing["arrival"]:
        st.update(state="home", loc=landing["location"],
                  gifts=landing.get("presentsDelivered", 0),
                  visited=[s["location"] for s in stops[1:-1]])
    else:
        for i in range(1, len(stops)):
            prev, cur = stops[i - 1], stops[i]
            if cur["arrival"] <= now_ms <= cur["departure"]:
                st.update(state="stop", loc=cur["location"], dest=cur,
                          gifts=cur.get("presentsDelivered", 0),
                          visited=[s["location"] for s in stops[1:i + 1]])
                break
            if prev["departure"] < now_ms < cur["arrival"]:
                frac = (now_ms - prev["departure"]) / (cur["arrival"] - prev["departure"])
                lat, lng = interpolate(prev["location"], cur["location"], frac)
                st.update(state="flying", loc={"lat": lat, "lng": lng}, dest=cur,
                          left_ms=int(cur["arrival"] - now_ms),
                          gifts=prev.get("presentsDelivered", 0),
                          visited=[s["location"] for s in stops[1:i]])
                break
    hlat, hlng = home_latlng()
    st["miles"] = miles_between(hlat, hlng, st["loc"]["lat"], st["loc"]["lng"])
    return st


def countdown_parts(left_ms, now_ms=None):
    """Days are counted on the calendar in your time zone (on Dec 17 it's
    7 days until Christmas Eve, whatever the hour); on Christmas Eve itself
    it switches to hours, then minutes, until takeoff."""
    hours = left_ms // 3600000
    if now_ms is None:
        days = left_ms // 86400000
    else:
        zone = tz()
        today = datetime.fromtimestamp(now_ms / 1000, tz=zone).date()
        takeoff_day = datetime.fromtimestamp((now_ms + left_ms) / 1000, tz=zone).date()
        days = (takeoff_day - today).days
    if days >= 1:
        return days, "DAY" if days == 1 else "DAYS"
    if hours >= 1:
        return hours, "HOUR" if hours == 1 else "HOURS"
    return max(1, round(left_ms / 60000)), "MIN"


def santa_message(st):
    """One-line message for the scrolling-text mode."""
    s = st["state"]
    if s == "countdown":
        num, unit = countdown_parts(st["left_ms"], st.get("now_ms"))
        unit = {"DAY": "day", "DAYS": "days", "HOUR": "hour", "HOURS": "hours",
                "MIN": "min"}[unit]
        return f"Santa {cfg_str('CountdownPhrase')} in {num} {unit}!"
    if s == "home":
        return "Santa is back at the North Pole. Merry Christmas!"
    if s == "stop" and st["dest"].get("id") == "home_stop":
        parts = [f"Santa is HERE in {place(st['dest'])}!"]
        if cfg_bool("ShowGifts") and st["gifts"]:
            parts.append(f"{st['gifts']:,} gifts delivered")
        return " - ".join(parts)
    if s == "stop":
        parts = [f"Santa is in {place(st['dest'])}"]
    elif s == "flying":
        parts = [f"Santa is flying to {place(st['dest'])}",
                 f"arriving in {mins_text(st['left_ms'])}"]
    else:
        return "Santa is on his way!"
    if cfg_bool("ShowDistance"):
        mi = st["miles"]
        parts.append("he's almost here!" if mi < 50 else f"{mi:,.0f} mi from {home_name()}")
    if cfg_bool("ShowGifts") and st["gifts"]:
        parts.append(f"{st['gifts']:,} gifts delivered")
    return " - ".join(parts)


# ------------------------------------------------------------- preview moments
def moments(dests, now_ms):
    """Times worth previewing, for the settings page's dropdown."""
    zone = tz()
    stops = season_stops(dests, now_ms)
    takeoff, landing = stops[0], stops[-1]
    year = season_year(now_ms)
    out = [{"key": "now", "label": "Right now", "ms": now_ms}]

    def at_local(day, hour):
        return datetime(year, 12, day, hour, 0, tzinfo=zone).timestamp() * 1000

    eve = datetime(year, 12, 24, 19, 0, tzinfo=zone)
    for n in (30, 14, 7, 3, 1):
        ms = (eve - timedelta(days=n)).timestamp() * 1000
        out.append({"key": f"d{n}", "ms": ms,
                    "label": f"{n} day{'s' if n > 1 else ''} before Christmas Eve"})
    out.append({"key": "eve_takeoff", "ms": takeoff["departure"] + 20 * 60000,
                "label": "Christmas Eve - Santa just took off"})
    for key, hour, name in (("eve_noon", 12, "noon"), ("eve_6pm", 18, "6 PM"),
                            ("eve_9pm", 21, "9 PM")):
        out.append({"key": key, "ms": at_local(24, hour), "label": f"Christmas Eve - {name}"})
    home = next((d for d in stops if d.get("id") == "home_stop"), None)
    if home:
        out.append({"key": "home_soon", "ms": home["arrival"] - 60000,
                    "label": f"1 minute before Santa reaches {home_name()}"})
        out.append({"key": "home_here", "ms": home["arrival"] + 30000,
                    "label": f"Santa is in {home_name()}"})
    out.append({"key": "landed", "ms": landing["arrival"] + 3600000,
                "label": "Christmas morning - Santa is home"})
    for m in out:
        when = datetime.fromtimestamp(m["ms"] / 1000, tz=zone)
        m["when"] = when.strftime("%a %b %d, %I:%M %p").replace(" 0", " ")
        m["ms"] = int(m["ms"])
    out.sort(key=lambda m: (m["key"] != "now", m["ms"]))
    return out


# names shown in the "Santa Tracker - Preview" FPP Command dropdown
COMMAND_MOMENTS = {
    "Right now": "now", "30 days before": "d30", "14 days before": "d14",
    "7 days before": "d7", "3 days before": "d3", "1 day before": "d1",
    "Christmas Eve - takeoff": "eve_takeoff", "Christmas Eve - noon": "eve_noon",
    "Christmas Eve - 6 PM": "eve_6pm", "Christmas Eve - 9 PM": "eve_9pm",
    "Approaching my town": "home_soon", "Santa in my town": "home_here",
    "Christmas morning": "landed",
}


def resolve_at(at, dests):
    """--at value -> epoch ms (moment key or name, or an ISO time)."""
    if not at:
        return None
    key = at.strip()
    key = COMMAND_MOMENTS.get(key, key).lower()
    key = {"home": "home_soon", "home-here": "home_here"}.get(key, key)
    for m in moments(dests, time.time() * 1000):
        if m["key"] == key:
            return m["ms"]
    try:
        dt = datetime.fromisoformat(at)
    except ValueError:
        raise ValueError(f"Unknown moment '{at}'. Use a moment name such as "
                         f"'7 days before' or a date-time like 2026-12-24T22:00")
    if dt.tzinfo is None:
        dt = dt.replace(tzinfo=tz())
    return dt.timestamp() * 1000


# ------------------------------------------------------------- the map
MAP_LAT_TOP = 85.0
MAP_LAT_BOTTOM = -58.0


def map_xy(lat, lng, w, h):
    x = int((lng + 180.0) / 360.0 * w)
    y = int((MAP_LAT_TOP - lat) / (MAP_LAT_TOP - MAP_LAT_BOTTOM) * h)
    return min(max(x, 0), w - 1), min(max(y, 0), h - 1)


def _land_rings():
    last = None
    for url in LAND_URLS:
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "fpp-santa-tracker"})
            with urllib.request.urlopen(req, timeout=30) as r:
                gj = json.loads(r.read().decode("utf-8"))
            rings = []
            for feat in gj.get("features", []):
                g = feat.get("geometry") or {}
                polys = ([g["coordinates"]] if g.get("type") == "Polygon"
                         else g.get("coordinates", []) if g.get("type") == "MultiPolygon"
                         else [])
                for poly in polys:
                    rings.extend(poly)
            if rings:
                log(f"Coastline downloaded ({len(rings)} shapes)")
                return rings
        except Exception as e:
            last = e
    raise RuntimeError(f"couldn't download the coastline: {last}")


def rasterize_land(rings, w, h, ss=2):
    cov = [[0] * w for _ in range(h)]
    span = MAP_LAT_TOP - MAP_LAT_BOTTOM
    edges = []
    for ring in rings:
        for (x1, y1), (x2, y2) in zip(ring, ring[1:] + ring[:1]):
            if y1 != y2:
                edges.append((x1, y1, x2, y2))
    for sy in range(h * ss):
        lat = MAP_LAT_TOP - (sy + 0.5) / (h * ss) * span
        xs = sorted(x1 + (lat - y1) * (x2 - x1) / (y2 - y1)
                    for x1, y1, x2, y2 in edges if (y1 > lat) != (y2 > lat))
        row = cov[sy // ss]
        for a, b in zip(xs[0::2], xs[1::2]):
            c0 = math.ceil((a + 180.0) / 360.0 * w * ss - 0.5)
            c1 = math.floor((b + 180.0) / 360.0 * w * ss - 0.5)
            for c in range(max(c0, 0), min(c1, w * ss - 1) + 1):
                row[c // ss] += 1
    return cov


def load_land(w, h):
    cache = os.path.join(ensure_data_dir(), f"land_{w}x{h}.json")
    if os.path.exists(cache):
        try:
            with open(cache) as f:
                return json.load(f)
        except Exception:
            pass
    try:
        cov = rasterize_land(_land_rings(), w, h)
        tmp = cache + ".tmp"
        with open(tmp, "w") as f:
            json.dump(cov, f)
        os.replace(tmp, cache)
        own(cache)
        return cov
    except Exception as e:
        log(f"Map will show without land for now: {e}")
        return [[0] * w for _ in range(h)]


# ------------------------------------------------------------- panel size
_model_cache = {}


def model_info():
    """(name, width, height) of the overlay model, asked from FPP."""
    if "info" in _model_cache:
        return _model_cache["info"]
    name = cfg_str("ModelName")
    w, h = 192, 64
    try:
        if not name:
            with urllib.request.urlopen(f"http://{FPP_HOST}/api/overlays/models", timeout=5) as r:
                models = json.loads(r.read().decode())
            if models:
                name = models[0].get("Name", "")
        if name:
            with urllib.request.urlopen(
                    f"http://{FPP_HOST}/api/overlays/model/{urllib.parse.quote(name)}",
                    timeout=5) as r:
                m = json.loads(r.read().decode())
            w = int(m.get("width") or w)
            h = int(m.get("height") or h)
    except Exception as e:
        log(f"Couldn't read the overlay model from FPP ({e}); assuming {w}x{h}")
    _model_cache["info"] = (name or "LED Panels", w, h)
    return _model_cache["info"]


def map_width(W, H):
    mw = int(cfg_num("MapWidth", 0, 4096))
    if mw <= 0:
        mw = min(2 * H, W - 48) if W - 48 >= 32 else W
    return max(8, min(mw, W))


# ------------------------------------------------------------- pixel font
FONT5x7 = {
    "A": [".###.", "#...#", "#...#", "#####", "#...#", "#...#", "#...#"],
    "B": ["####.", "#...#", "#...#", "####.", "#...#", "#...#", "####."],
    "C": [".###.", "#...#", "#....", "#....", "#....", "#...#", ".###."],
    "D": ["####.", "#...#", "#...#", "#...#", "#...#", "#...#", "####."],
    "E": ["#####", "#....", "#....", "####.", "#....", "#....", "#####"],
    "F": ["#####", "#....", "#....", "####.", "#....", "#....", "#...."],
    "G": [".###.", "#...#", "#....", "#.###", "#...#", "#...#", ".####"],
    "H": ["#...#", "#...#", "#...#", "#####", "#...#", "#...#", "#...#"],
    "I": [".###.", "..#..", "..#..", "..#..", "..#..", "..#..", ".###."],
    "J": ["..###", "...#.", "...#.", "...#.", "...#.", "#..#.", ".##.."],
    "K": ["#...#", "#..#.", "#.#..", "##...", "#.#..", "#..#.", "#...#"],
    "L": ["#....", "#....", "#....", "#....", "#....", "#....", "#####"],
    "M": ["#...#", "##.##", "#.#.#", "#.#.#", "#...#", "#...#", "#...#"],
    "N": ["#...#", "#...#", "##..#", "#.#.#", "#..##", "#...#", "#...#"],
    "O": [".###.", "#...#", "#...#", "#...#", "#...#", "#...#", ".###."],
    "P": ["####.", "#...#", "#...#", "####.", "#....", "#....", "#...."],
    "Q": [".###.", "#...#", "#...#", "#...#", "#.#.#", "#..#.", ".##.#"],
    "R": ["####.", "#...#", "#...#", "####.", "#.#..", "#..#.", "#...#"],
    "S": [".####", "#....", "#....", ".###.", "....#", "....#", "####."],
    "T": ["#####", "..#..", "..#..", "..#..", "..#..", "..#..", "..#.."],
    "U": ["#...#", "#...#", "#...#", "#...#", "#...#", "#...#", ".###."],
    "V": ["#...#", "#...#", "#...#", "#...#", "#...#", ".#.#.", "..#.."],
    "W": ["#...#", "#...#", "#...#", "#.#.#", "#.#.#", "#.#.#", ".#.#."],
    "X": ["#...#", "#...#", ".#.#.", "..#..", ".#.#.", "#...#", "#...#"],
    "Y": ["#...#", "#...#", ".#.#.", "..#..", "..#..", "..#..", "..#.."],
    "Z": ["#####", "....#", "...#.", "..#..", ".#...", "#....", "#####"],
    "0": [".###.", "#...#", "#..##", "#.#.#", "##..#", "#...#", ".###."],
    "1": ["..#..", ".##..", "..#..", "..#..", "..#..", "..#..", ".###."],
    "2": [".###.", "#...#", "....#", "...#.", "..#..", ".#...", "#####"],
    "3": ["#####", "...#.", "..#..", "...#.", "....#", "#...#", ".###."],
    "4": ["...#.", "..##.", ".#.#.", "#..#.", "#####", "...#.", "...#."],
    "5": ["#####", "#....", "####.", "....#", "....#", "#...#", ".###."],
    "6": ["..##.", ".#...", "#....", "####.", "#...#", "#...#", ".###."],
    "7": ["#####", "....#", "...#.", "..#..", ".#...", ".#...", ".#..."],
    "8": [".###.", "#...#", "#...#", ".###.", "#...#", "#...#", ".###."],
    "9": [".###.", "#...#", "#...#", ".####", "....#", "...#.", ".##.."],
    " ": ["....."] * 7,
    ".": [".....", ".....", ".....", ".....", ".....", ".##..", ".##.."],
    ",": [".....", ".....", ".....", ".....", ".##..", "..#..", ".#..."],
    ":": [".....", ".##..", ".##..", ".....", ".##..", ".##..", "....."],
    "!": ["..#..", "..#..", "..#..", "..#..", "..#..", ".....", "..#.."],
    "?": [".###.", "#...#", "....#", "...#.", "..#..", ".....", "..#.."],
    "'": ["..#..", "..#..", ".#...", ".....", ".....", ".....", "....."],
    "-": [".....", ".....", ".....", "#####", ".....", ".....", "....."],
    "/": [".....", "....#", "...#.", "..#..", ".#...", "#....", "....."],
    "(": ["...#.", "..#..", ".#...", ".#...", ".#...", "..#..", "...#."],
    ")": [".#...", "..#..", "...#.", "...#.", "...#.", "..#..", ".#..."],
    "&": [".##..", "#..#.", "#.#..", ".#...", "#.#.#", "#..#.", ".##.#"],
    "#": [".#.#.", ".#.#.", "#####", ".#.#.", "#####", ".#.#.", ".#.#."],
}


def font_text(s):
    """Upper-case, strip accents (Sao Paulo), drop characters we can't draw."""
    s = unicodedata.normalize("NFKD", s).encode("ascii", "ignore").decode().upper()
    return "".join(c if c in FONT5x7 else " " for c in s)


def wrap(s, width, hard=None):
    """Word-wrap to `width` characters; a single long word may use up to
    `hard` characters (drawn with tighter spacing) before it is split."""
    hard = max(hard or width, width)
    lines, cur = [], ""
    for word in font_text(s).split():
        while len(word) > hard:
            if cur:
                lines.append(cur)
                cur = ""
            lines.append(word[:hard])
            word = word[hard:]
        if len(word) > width:
            if cur:
                lines.append(cur)
                cur = ""
            lines.append(word)
            continue
        if not cur:
            cur = word
        elif len(cur) + 1 + len(word) <= width:
            cur += " " + word
        else:
            lines.append(cur)
            cur = word
    if cur:
        lines.append(cur)
    return lines


class Ink:
    """Colors for the right-hand panel. In 'lines' mode each kind of line
    has its own color; the other modes color every letter / word / line
    from a palette, flowing across the whole panel."""

    def __init__(self):
        self.mode = cfg_str("PanelColorMode").lower() or "lines"
        name = cfg_str("PanelPalette")
        pal = PALETTES.get(name)
        if not pal:
            pal = [c for c in cfg_str("PanelCustomColors").split(",") if c.strip()]
        self.palette = [hex_rgb(c) for c in pal] or [(255, 255, 255)]
        self.letter = self.word = self.line = 0

    def __call__(self, role):
        n = len(self.palette)
        if self.mode == "single":
            return cfg_color("PanelSingleColor")
        if self.mode == "line":
            c = self.palette[self.line % n]
            self.line += 1
            return c
        if self.mode == "letter":
            def per_letter(ch):
                if ch == " ":
                    return None
                c = self.palette[self.letter % n]
                self.letter += 1
                return c
            return per_letter
        if self.mode == "word":
            state = {"gap": True}

            def per_word(ch):
                if ch == " ":
                    state["gap"] = True
                    return None
                if state["gap"]:
                    self.word += 1
                    state["gap"] = False
                return self.palette[(self.word - 1) % n]
            return per_word
        return cfg_color({
            "heading": "HeadingColor", "city": "CityColor", "region": "RegionColor",
            "eta": "EtaColor", "distance": "DistanceColor", "gifts": "GiftsColor",
            "cd_text": "CountdownTextColor", "cd_number": "CountdownNumberColor",
            "greet1": "Greeting1Color", "greet2": "Greeting2Color",
        }.get(role, "CityColor"))


class Canvas:
    def __init__(self, w, h, bg=(0, 0, 0)):
        self.w, self.h = w, h
        self.px = [[bg] * w for _ in range(h)]

    def set(self, x, y, c):
        if 0 <= x < self.w and 0 <= y < self.h:
            self.px[y][x] = c

    def text(self, x, y, s, c, scale=1, step=6):
        for ch in font_text(s):
            col = c(ch) if callable(c) else c
            if col is not None:
                for gy, row in enumerate(FONT5x7[ch]):
                    for gx, bit in enumerate(row):
                        if bit == "#":
                            for dy in range(scale):
                                for dx in range(scale):
                                    self.set(x + gx * scale + dx, y + gy * scale + dy, col)
            x += step * scale

    def fit_text(self, x, y, s, c, width):
        n = len(font_text(s))
        self.text(x, y, s, c, step=6 if n * 6 - 1 <= width else 5)

    def text_center(self, x0, width, y, s, c, scale=1):
        n = len(font_text(s))
        step = 6 if n * 6 * scale - scale <= width or scale > 1 else 5
        tw = n * step * scale - scale
        self.text(x0 + max(0, (width - tw) // 2), y, s, c, scale, step)

    def png(self):
        import struct
        import zlib
        raw = b"".join(b"\x00" + bytes(v for p in row for v in p) for row in self.px)

        def chunk(t, d):
            return (struct.pack(">I", len(d)) + t + d +
                    struct.pack(">I", zlib.crc32(t + d) & 0xFFFFFFFF))
        return (b"\x89PNG\r\n\x1a\n" +
                chunk(b"IHDR", struct.pack(">IIBBBBB", self.w, self.h, 8, 2, 0, 0, 0)) +
                chunk(b"IDAT", zlib.compress(raw, 9)) + chunk(b"IEND", b""))


def short_number(n):
    for div, suf in ((1e9, "B"), (1e6, "M"), (1e3, "K")):
        if n >= div:
            v = n / div
            return f"{v:.1f}{suf}" if v < 100 else f"{v:.0f}{suf}"
    return str(int(n))


def draw_frame(st, land, W, H):
    """Map on the left, info panel on the right."""
    mw = map_width(W, H)
    cv = Canvas(W, H)
    ocean = cfg_color("OceanColor")
    landc = cfg_color("LandColor")
    for y in range(H):
        row = land[y] if y < len(land) else []
        for x in range(mw):
            c = row[x] if x < len(row) else 0
            if c:
                k = 0.6 + 0.4 * c / 4          # coastline pixels a bit dimmer
                cv.set(x, y, tuple(int(o + (l - o) * k) for o, l in zip(ocean, landc)))
            elif ocean != (0, 0, 0):
                cv.set(x, y, ocean)
    if cfg_bool("ShowTrail"):
        trail = cfg_color("TrailColor")
        for loc in st["visited"]:
            cv.set(*map_xy(loc["lat"], loc["lng"], mw, H), trail)
    hlat, hlng = home_latlng()
    hx, hy = map_xy(hlat, hlng, mw, H)
    for dx, dy in ((0, 0), (1, 0), (-1, 0), (0, 1), (0, -1)):
        cv.set(hx + dx, hy + dy, cfg_color("HomeColor"))
    santa = cfg_color("SantaColor")
    sx, sy = map_xy(st["loc"]["lat"], st["loc"]["lng"], mw, H)
    for dx, dy in ((-1, 0), (1, 0), (0, -1), (0, 1), (-2, 0), (2, 0), (0, -2), (0, 2)):
        cv.set(sx + dx, sy + dy, santa)
    cv.set(sx, sy, tuple(min(255, v + 120) for v in santa))   # bright center

    px0 = mw + 2
    pw = W - px0 - int(cfg_num("PanelRightMargin", 0, 40))
    if pw < 30:
        return cv
    ink = Ink()
    cols = max(1, (pw + 1) // 6)
    hard = max(cols, (pw + 1) // 5)

    state = st["state"]
    if state == "countdown":
        num, unit = countdown_parts(st["left_ms"], st.get("now_ms"))
        phrase = f"Santa {cfg_str('CountdownPhrase')} in"
        layouts = [(cols, 9, 3, False), (cols, 9, 2, False),
                   (hard, 8, 3, False), (hard, 8, 2, False),
                   (hard, 8, 2, True), (hard, 8, 1, True)]
        for width, step, scale, inline in layouts:
            head = wrap(phrase, width, hard)
            if inline:
                body_h = 7 * scale
                body_w = len(f"{num} {unit}") * 6 * scale - scale
            else:
                body_h = 7 * scale + 2 + 7
                body_w = len(str(num)) * 6 * scale - scale
            top = 1 if step == 9 else 0
            if top + len(head) * step + 1 + body_h <= H and body_w <= pw:
                break
        y = top
        for ln in head:
            cv.text_center(px0, pw, y, ln, ink("cd_text"))
            y += step
        y += 1
        if inline:
            cv.text_center(px0, pw, y, f"{num} {unit}", ink("cd_number"), scale)
            y += 7 * scale + 2
        else:
            cv.text_center(px0, pw, y, str(num), ink("cd_number"), scale)
            y += 7 * scale + 2
            cv.text_center(px0, pw, y, unit, ink("cd_text"))
            y += 9
        if y + 7 <= H:
            cv.text_center(px0, pw, y, "HO HO HO!", ink("greet2"))
    elif state == "home":
        cv.text_center(px0, pw, 2, "SANTA IS", ink("cd_text"))
        cv.text_center(px0, pw, 11, "HOME!", ink("heading"))
        cv.text_center(px0, pw, 24, "MERRY", ink("greet1"))
        cv.text_center(px0, pw, 33, "CHRISTMAS!", ink("greet2"))
        if st["gifts"] and cfg_bool("ShowGifts") and H >= 63:
            cv.text_center(px0, pw, 47, short_number(st["gifts"]) + " GIFTS", ink("gifts"))
            cv.text_center(px0, pw, 56, "DELIVERED", ink("gifts"))
    elif state in ("stop", "flying"):
        d = st["dest"]
        y = 1
        cv.fit_text(px0, y, "NOW IN:" if state == "stop" else "FLYING TO:", ink("heading"), pw)
        y += 10
        city = wrap(d.get("city", ""), cols, hard)
        region = (wrap(d.get("region", ""), cols, hard)
                  if d.get("region") and d.get("region") != d.get("city") else [])
        for ln in city[:2]:
            cv.fit_text(px0, y, ln, ink("city"), pw)
            y += 9
        room = max(0, 3 - min(len(city), 2))
        if len(region) > room and "," in d.get("region", ""):
            region = wrap(d["region"].rsplit(",", 1)[1], cols, hard)   # "..., Japan"
        for ln in region[:room]:
            cv.fit_text(px0, y, ln, ink("region"), pw)
            y += 9
        bottom = []
        if state == "flying":
            bottom.append((f"ETA {max(1, round(st['left_ms'] / 60000))} MIN", "eta"))
        if state == "stop" and d.get("id") == "home_stop":
            bottom.append(("HE'S HERE!", "greet1"))
        elif cfg_bool("ShowDistance"):
            bottom.append(("CLOSE BY!" if st["miles"] < 50 else f"{st['miles']:,.0f} MI",
                           "distance"))
        if state == "stop" and st["gifts"] and cfg_bool("ShowGifts"):
            bottom.append((short_number(st["gifts"]) + " GIFTS", "gifts"))
            bottom.append(("DELIVERED", "gifts"))
        yb = H - 9 * len(bottom)
        for txt, role in bottom:
            cv.fit_text(px0, yb, txt, ink(role), pw)
            yb += 9
    else:
        cv.text_center(px0, pw, H // 2 - 4, "SANTA", ink("city"))
    return cv


def render(st):
    _name, W, H = model_info()
    land = load_land(map_width(W, H), H)
    return draw_frame(st, land, W, H)


# ------------------------------------------------------------- FPP output
def fpp_command(command, args):
    body = json.dumps({"command": command, "multisyncCommand": False,
                       "multisyncHosts": "", "args": args}).encode()
    req = urllib.request.Request(f"http://{FPP_HOST}/api/command", data=body,
                                 headers={"Content-Type": "application/json"}, method="POST")
    try:
        with urllib.request.urlopen(req, timeout=10) as r:
            return r.read().decode(errors="replace")
    except urllib.error.HTTPError as e:
        detail = e.read().decode(errors="replace").strip()[:300]
        raise RuntimeError(f"{e} - FPP said: {detail or '(no details)'}")


def enable_model():
    name = model_info()[0]
    try:
        url = f"http://{FPP_HOST}/api/overlays/model/{urllib.parse.quote(name)}/state"
        req = urllib.request.Request(url, data=json.dumps({"State": 1}).encode(),
                                     headers={"Content-Type": "application/json"}, method="PUT")
        urllib.request.urlopen(req, timeout=10).read()
    except Exception as e:
        log(f"Couldn't switch on model '{name}': {e}")


def clear_matrix():
    try:
        fpp_command("Overlay Model Clear", [model_info()[0]])
    except Exception:
        pass


_frame_gen = 0


def show_image(cv, duration):
    """Put a frame on the model. duration 0 = leave it up until replaced."""
    global _frame_gen
    _frame_gen += 1
    name = f"santa_map_{os.getpid()}_{_frame_gen}.png"
    path = os.path.join(IMAGES_DIR, name)
    with open(path, "wb") as f:
        f.write(cv.png())
    own(path)
    enable_model()
    fpp_command("Overlay Model Effect", [
        model_info()[0], "Enabled", "Image", "None", "Center", "0",
        str(int(duration)), name])
    try:
        for fn in os.listdir(IMAGES_DIR):
            if fn.startswith("santa_map_") and fn.endswith(".png") and fn != name:
                full = os.path.join(IMAGES_DIR, fn)
                if time.time() - os.path.getmtime(full) > 5:
                    os.remove(full)
    except Exception:
        pass


_font_cache = {}


def pick_font():
    want = cfg_str("TextFont")
    if want in _font_cache:
        return _font_cache[want]
    fonts = []
    try:
        with urllib.request.urlopen(f"http://{FPP_HOST}/api/overlays/fonts", timeout=10) as r:
            fonts = json.loads(r.read().decode())
    except Exception as e:
        log(f"Couldn't list fonts ({e})")
    fonts = [str(f) for f in (fonts.keys() if isinstance(fonts, dict) else fonts)]
    if want and (not fonts or want in fonts):
        pick = want
    elif fonts:
        pick = ([f for f in fonts if "sans" in f.lower()] or fonts)[0]
        log(f"Font '{want}' not found on FPP; using '{pick}'")
    else:
        pick = want or "FreeSans"
    _font_cache[want] = pick
    return pick


def scroll_seconds(text):
    _n, W, _h = model_info()
    char_px = cfg_num("TextFontSize", 4, 100) * 0.65
    return math.ceil((len(text) * char_px + W) / max(cfg_num("TextScrollSpeed", 1, 200), 1)) + 1


def show_text(text, duration):
    args = [model_info()[0], "Enabled", "Text",
            cfg_str("TextColor") or "#FF0000", pick_font(),
            str(int(cfg_num("TextFontSize", 4, 100))), "true",
            "Right to Left", str(int(cfg_num("TextScrollSpeed", 1, 200))),
            str(int(duration)), text]
    mode = cfg_str("TextColorMode") or "Single"
    if mode != "Single":
        args += [mode, cfg_str("TextPalette") or "Christmas", "2", "#0000FF",
                 "#00FF00", "#FFFF00", "#FF00FF", "0"]
    enable_model()
    fpp_command("Overlay Model Effect", args)


def wait_for_fpp(limit=90):
    """Wait until fppd answers (it may still be starting). Checks every
    3 seconds for at most `limit` seconds."""
    end = time.time() + limit
    while True:
        try:
            with urllib.request.urlopen(f"http://{FPP_HOST}/api/overlays/models", timeout=5) as r:
                r.read()
            return True
        except Exception:
            if time.time() >= end:
                log("FPP isn't answering yet; carrying on anyway")
                return False
            time.sleep(3)


# ------------------------------------------------------------- single copy
def running_copies():
    """[(pid, argv)] of other copies of this script that drive the matrix."""
    me = os.getpid()
    found = []
    for pid in os.listdir("/proc"):
        if not pid.isdigit() or int(pid) == me:
            continue
        try:
            with open(f"/proc/{pid}/cmdline", "rb") as f:
                argv = [x.decode(errors="replace") for x in f.read().split(b"\0") if x]
        except Exception:
            continue
        if len(argv) < 2 or not os.path.basename(argv[0]).startswith("python"):
            continue
        if os.path.abspath(argv[1]) != SCRIPT_PATH and \
                os.path.basename(argv[1]) != os.path.basename(SCRIPT_PATH):
            continue
        if any(a in argv for a in ("--loop", "--once")):
            found.append((int(pid), argv))
    return found


def wait_gone(pids, timeout=5):
    end = time.time() + timeout
    while time.time() < end and any(os.path.exists(f"/proc/{p}") for p in pids):
        time.sleep(0.2)
    for p in pids:
        if os.path.exists(f"/proc/{p}"):
            try:
                os.kill(p, signal.SIGKILL)
            except Exception:
                pass


def stop_all(clear=True):
    copies = running_copies()
    for pid, _argv in copies:
        try:
            os.kill(pid, signal.SIGUSR1)       # quiet exit
        except Exception as e:
            log(f"Couldn't stop PID {pid}: {e}")
    wait_gone([p for p, _ in copies])
    if clear:
        clear_matrix()
    return len(copies)


def relaunch(argv):
    try:
        env = dict(os.environ, SANTA_QUIET="1")
        with open(LOG_FILE, "a") as logf:
            subprocess.Popen(argv, stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL,
                             stderr=logf, env=env, start_new_session=True, close_fds=True)
        log("Resumed the running display")
    except Exception as e:
        log(f"Couldn't resume the running display: {e}")


# ------------------------------------------------------------- main
def main():
    global QUIET
    ap = argparse.ArgumentParser(description="Santa Tracker for FPP")
    ap.add_argument("--loop", action="store_true", help="keep the display running")
    ap.add_argument("--once", action="store_true", help="show once, then exit")
    ap.add_argument("--stop", action="store_true", help="stop the display and clear it")
    ap.add_argument("--status", action="store_true", help="print JSON status")
    ap.add_argument("--mode", choices=["map", "text", "both"], help="override DisplayMode")
    ap.add_argument("--seconds", type=float, help="how long --once shows the map")
    ap.add_argument("--at", help="moment key or ISO time to simulate")
    ap.add_argument("--settings", help="JSON file of settings to use (preview)")
    ap.add_argument("--preview", metavar="FILE.png", help="save a picture and exit")
    ap.add_argument("--preview-json", action="store_true", help="print a picture as JSON")
    ap.add_argument("--moments-json", action="store_true", help="print preview moments")
    ap.add_argument("--dry-run", action="store_true", help="print the text message only")
    ap.add_argument("--settings-json", action="store_true",
                    help="print the saved settings (with defaults) as JSON")
    a = ap.parse_args()

    if a.preview_json or a.moments_json or a.status or a.settings_json:
        QUIET = True
    if a.loop or a.once:
        os.closerange(3, 256)      # don't hold on to fppd's sockets
    load_settings(a.settings)

    if a.settings_json:
        saved = read_plugin_settings()
        print(json.dumps({"ok": True, "defaults": DEFAULTS,
                          "settings": {k: saved.get(k, v) for k, v in DEFAULTS.items()},
                          "palettes": PALETTES,
                          "commandMoments": COMMAND_MOMENTS}))
        return 0
    if a.status:
        copies = running_copies()
        loop = [p for p, argv in copies if "--loop" in argv]
        print(json.dumps({"ok": True, "running": bool(loop), "pids": [p for p, _ in copies]}))
        return 0
    if a.stop:
        n = stop_all()
        log(f"Stopped ({n} running)" if n else "Stopped (nothing was running)")
        return 0

    try:
        dests = load_route()
    except Exception as e:
        if a.preview_json or a.moments_json:
            print(json.dumps({"ok": False, "error": str(e)}))
            return 1
        log(str(e))
        return 1

    if a.moments_json:
        print(json.dumps({"ok": True, "moments": moments(dests, time.time() * 1000)}))
        return 0

    try:
        fake_start = resolve_at(a.at, dests)
    except ValueError as e:
        if a.preview_json:
            print(json.dumps({"ok": False, "error": str(e)}))
        else:
            log(str(e))
        return 2
    real_start = time.time() * 1000

    def now_ms():
        return time.time() * 1000 if fake_start is None else \
            fake_start + (time.time() * 1000 - real_start)

    if a.preview_json or a.preview or a.dry_run:
        st = santa_status(dests, now_ms())
        text = santa_message(st)
        if a.dry_run:
            print(text)
            return 0
        cv = render(st)
        if a.preview:
            with open(a.preview, "wb") as f:
                f.write(cv.png())
            log(f"Picture saved to {a.preview}")
            return 0
        name, W, H = model_info()
        print(json.dumps({
            "ok": True, "png": base64.b64encode(cv.png()).decode(), "width": W,
            "height": H, "model": name, "text": text, "state": st["state"],
            "when": datetime.fromtimestamp(now_ms() / 1000, tz=tz())
                    .strftime("%a %b %d %Y, %I:%M %p").replace(" 0", " "),
            "moments": moments(dests, time.time() * 1000),
            "nearest": nearest_stop(dests)}))
        return 0

    if not (a.loop or a.once):
        ap.print_help()
        return 0

    # ---- drive the matrix ----
    resume = None
    copies = running_copies()
    if a.once and any("--once" in argv for _p, argv in copies) and \
            not any("--loop" in argv for _p, argv in copies):
        log("Another Show Once is still on the matrix; letting it finish")
        return 0
    for pid, argv in copies:
        if a.once and "--loop" in argv:
            resume = argv                         # put the running display back after
        try:
            os.kill(pid, signal.SIGUSR1)
        except Exception as e:
            log(f"Couldn't replace PID {pid}: {e}")
    wait_gone([p for p, _ in copies])

    def on_term(*_):
        nonlocal resume
        resume = None                             # a Stop means stop everything
        log("Stopping - clearing the matrix")
        clear_matrix()
        sys.exit(0)

    def on_replaced(*_):
        nonlocal resume
        resume = None
        sys.exit(0)
    signal.signal(signal.SIGTERM, on_term)
    signal.signal(signal.SIGINT, on_term)
    signal.signal(signal.SIGUSR1, on_replaced)

    wait_for_fpp()
    mode = a.mode or cfg_str("DisplayMode").lower() or "map"
    if mode not in ("map", "text", "both"):
        mode = "map"
    map_secs = a.seconds if a.seconds else cfg_num("MapSeconds", 3, 3600)
    refresh = cfg_num("RefreshSeconds", 5, 3600)
    name, W, H = model_info()
    log(f"Started ({'running display' if a.loop else 'show once'}, {mode}, "
        f"model '{name}' {W}x{H}"
        + (f", simulating {a.at}" if a.at else "") + ")")

    last_png, last_sent = None, 0.0
    try:
        while True:
            try:
                dests = load_route()
                st = santa_status(dests, now_ms())
            except Exception as e:
                log(f"Error: {e}")
                time.sleep(30)
                continue

            if mode in ("map", "both"):
                try:
                    cv = render(st)
                    png = cv.png()
                    if a.once or mode == "both":
                        show_image(cv, map_secs + 3)
                        time.sleep(map_secs)
                    else:
                        # only reload the picture when it changes (reloading
                        # makes FPP blank the panel for a moment)
                        if png != last_png or time.time() - last_sent > 600:
                            show_image(cv, 0)
                            last_png, last_sent = png, time.time()
                        time.sleep(refresh)
                except Exception as e:
                    log(f"Map error: {e}")
                    time.sleep(10)
                if mode == "map":
                    if a.once:
                        break
                    continue

            text = santa_message(st)
            wait = scroll_seconds(text)
            try:
                show_text(text, wait)
            except Exception as e:
                log(f"FPP error: {e}")
                wait = 30
            time.sleep(wait)
            if a.once:
                break
    finally:
        if resume:
            relaunch(resume)
    return 0


if __name__ == "__main__":
    sys.exit(main() or 0)

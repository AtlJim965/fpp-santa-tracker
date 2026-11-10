#!/usr/bin/env python3
"""
ambient_weather.py - part of fpp-plugin-AmbientWeather

Shows current temperature and wind speed from an Ambient Weather station
on an FPP Pixel Overlay Model (LED matrix), optionally inside a
Christmas-colored border.

All settings come from the plugin's settings page in FPP
(Content Setup -> Ambient Weather Display). Command-line options
override them for a single run:

    AmbientWeather.sh                          # update once
    AmbientWeather.sh --loop 60                # keep updating every 60s
    AmbientWeather.sh --off                    # clear + disable overlay
    AmbientWeather.sh --text "The weather right now is:"
    AmbientWeather.sh --text "It's {temp} with {wind}!" --loop 60
    AmbientWeather.sh --text "Weather now:|{temp}  {wind}"   # | = new line
"""

import json
import os
import struct
import sys
import time
import unicodedata
import urllib.error
import urllib.parse
import urllib.request
import zlib

PLUGIN_NAME = "fpp-plugin-AmbientWeather"
MEDIA_DIR = "/home/fpp/media"
CONFIG_FILE = f"{MEDIA_DIR}/config/plugin.{PLUGIN_NAME}.json"
IMAGES_DIR = f"{MEDIA_DIR}/images"
IMAGE_PREFIX = "ambient_weather_"

DEFAULTS = {
    "api_key": "",
    "application_key": "",
    "station_mac": "",
    "fpp_host": "http://127.0.0.1",
    "model_name": "LED Panels",
    "font": "FreeSans",
    "font_size": 22,
    "color": "#FFFFFF",
    "position": "Center",
    "scroll_speed": 35,
    "show_gust": False,
    "message": "{temp}  {wind}",
    "loop_interval": 60,
    # border
    "border_enabled": False,
    "border_thickness": 3,
    "border_palette": "Christmas",
    "border_custom_colors": "#FF0000,#FFFFFF,#00C83C",
    "border_stripe": 6,
    "border_chase": False,
    "chase_speed": 4,
}

PALETTES = {
    "Christmas": ["#FF0000", "#FFFFFF", "#00C83C"],
    "Red & Green": ["#FF0000", "#00C83C"],
    "Candy Cane": ["#FF0000", "#FFFFFF"],
    "Winter": ["#0080FF", "#FFFFFF", "#80D0FF"],
    "Gold & Silver": ["#FFAA00", "#C8C8C8"],
    "Rainbow": ["#FF0000", "#FF8000", "#FFFF00", "#00FF00", "#0080FF", "#A000FF"],
}

AMBIENT_URL = "https://rt.ambientweather.net/v1/devices"

cfg = dict(DEFAULTS)


def log(msg):
    print(time.strftime("%Y-%m-%d %H:%M:%S"), msg, flush=True)


def as_bool(v):
    return v in (True, 1, "1", "true", "on", "True")


def load_config():
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE) as f:
                cfg.update(json.load(f))
        except Exception as e:
            log(f"Could not read {CONFIG_FILE}: {e} (using defaults)")
    else:
        log("No settings saved yet - open Content Setup -> "
            "Ambient Weather Display in FPP")
    for k in ("font_size", "scroll_speed", "loop_interval", "border_thickness",
              "border_stripe", "chase_speed"):
        try:
            cfg[k] = int(cfg.get(k))
        except (TypeError, ValueError):
            cfg[k] = DEFAULTS[k]
    for k in ("show_gust", "border_enabled", "border_chase"):
        cfg[k] = as_bool(cfg.get(k))


def hex_rgb(s, fallback=(255, 255, 255)):
    s = (s or "").strip().lstrip("#")
    try:
        return tuple(int(s[i:i + 2], 16) for i in (0, 2, 4))
    except (ValueError, IndexError):
        return fallback


def border_colors():
    pal = PALETTES.get(cfg["border_palette"])
    if not pal:
        pal = [c for c in cfg["border_custom_colors"].split(",") if c.strip()]
    return [hex_rgb(c) for c in pal] or [(255, 0, 0), (0, 200, 60)]


# ---------------------------------------------------------------- weather
def get_weather():
    if not cfg["api_key"] or not cfg["application_key"]:
        raise RuntimeError("API Key / Application Key not set on the "
                           "plugin settings page")
    query = urllib.parse.urlencode({"apiKey": cfg["api_key"],
                                    "applicationKey": cfg["application_key"]})
    req = urllib.request.Request(f"{AMBIENT_URL}?{query}",
                                 headers={"User-Agent": "FPP-AmbientWeather/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            devices = json.load(resp)
    except urllib.error.HTTPError as e:
        if e.code == 401:
            raise RuntimeError("HTTP 401 Unauthorized - check your API Key "
                               "and Application Key")
        raise

    if not devices:
        raise RuntimeError("No stations found on this Ambient Weather account")

    mac = cfg["station_mac"].strip().lower()
    if mac:
        for dev in devices:
            if dev.get("macAddress", "").lower() == mac:
                return dev.get("lastData", {})
        raise RuntimeError(f"Station {cfg['station_mac']} not found on account")
    return devices[0].get("lastData", {})


def build_message(data):
    temp = data.get("tempf")
    wind = data.get("windspeedmph")
    gust = data.get("windgustmph")

    temp_txt = f"{round(temp)}°F" if temp is not None else "--°F"
    if wind is None:
        wind_txt = "Wind --"
    elif cfg["show_gust"] and gust is not None:
        wind_txt = f"Wind {round(wind)} G{round(gust)} mph"
    else:
        wind_txt = f"Wind {round(wind)} mph"

    template = cfg["message"] or "{temp}  {wind}"
    if "{temp}" not in template and "{wind}" not in template:
        template += " {temp}  {wind}"   # plain text given -> append weather
    return template.replace("{temp}", temp_txt).replace("{wind}", wind_txt)


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
    "%": ["##..#", "##..#", "...#.", "..#..", ".#...", "#..##", "#..##"],
    "+": [".....", "..#..", "..#..", "#####", "..#..", "..#..", "....."],
    "°": [".##..", "#..#.", "#..#.", ".##..", ".....", ".....", "....."],
}


def font_text(s):
    """Upper-case, strip accents, drop characters the pixel font can't draw."""
    out = []
    for ch in s.upper():
        if ch in FONT5x7:
            out.append(ch)
            continue
        plain = unicodedata.normalize("NFKD", ch).encode("ascii", "ignore").decode()
        out.append(plain if plain in FONT5x7 and plain else " ")
    return "".join(out)


def advance(ch, scale):
    return (4 if ch == " " else 6) * scale     # narrow spaces


def text_width(s, scale):
    return sum(advance(c, scale) for c in s) - scale if s else 0


# ------------------------------------------------------------------ canvas
class Canvas:
    def __init__(self, w, h):
        self.w, self.h = w, h
        self.buf = bytearray(w * h * 3)

    def set(self, x, y, c):
        if 0 <= x < self.w and 0 <= y < self.h:
            i = (y * self.w + x) * 3
            self.buf[i:i + 3] = bytes(c)

    def text(self, x, y, s, c, scale=1):
        for ch in s:
            for gy, row in enumerate(FONT5x7.get(ch, FONT5x7[" "])):
                for gx, bit in enumerate(row):
                    if bit == "#":
                        for dy in range(scale):
                            for dx in range(scale):
                                self.set(x + gx * scale + dx, y + gy * scale + dy, c)
            x += advance(ch, scale)

    def png(self):
        row = self.w * 3
        raw = b"".join(b"\x00" + bytes(self.buf[y * row:(y + 1) * row])
                       for y in range(self.h))

        def chunk(t, d):
            return (struct.pack(">I", len(d)) + t + d +
                    struct.pack(">I", zlib.crc32(t + d) & 0xFFFFFFFF))
        return (b"\x89PNG\r\n\x1a\n" +
                chunk(b"IHDR", struct.pack(">IIBBBBB", self.w, self.h, 8, 2, 0, 0, 0)) +
                chunk(b"IDAT", zlib.compress(raw, 6)) + chunk(b"IEND", b""))


def draw_border(cv, offset=0):
    """Striped border on all four sides. Stripes are slanted like a candy
    cane and move around the edge as `offset` changes (chase)."""
    t = max(1, min(cfg["border_thickness"], min(cv.w, cv.h) // 4))
    seg = max(1, cfg["border_stripe"])
    colors = border_colors()
    n = len(colors)
    W, H = cv.w, cv.h
    for y in range(H):
        for x in range(W):
            d = min(x, y, W - 1 - x, H - 1 - y)      # depth into the border
            if d >= t:
                continue
            # position along the edge, clockwise from the top-left corner
            if y == d:
                p = x
            elif x == W - 1 - d:
                p = W + y
            elif y == H - 1 - d:
                p = W + H + (W - 1 - x)
            else:
                p = 2 * W + H + (H - 1 - y)
            cv.set(x, y, colors[((p + d - offset) // seg) % n])


def layout_text(message, max_w, max_h):
    """Choose lines and a size for each line so the message fills the area.
    '|' in the message forces a new line. Returns [(text, scale), ...]."""
    explicit = [font_text(l).strip() for l in message.split("|")]
    explicit = [l for l in explicit if l] or [""]

    candidates = [explicit]
    if len(explicit) == 1:                       # also try 2- and 3-line splits
        words = explicit[0].split()
        for parts in (2, 3):
            if len(words) >= parts:
                candidates.append(split_balanced(words, parts))

    best, best_score = None, None
    for lines in candidates:
        scales = []
        for l in lines:
            s = 4
            while s > 1 and text_width(l, s) > max_w:
                s -= 1
            scales.append(s)
        # shrink the biggest lines until everything fits vertically
        while block_height(scales) > max_h and max(scales) > 1:
            i = scales.index(max(scales))
            scales[i] -= 1
        fits = (block_height(scales) <= max_h and
                all(text_width(l, s) <= max_w for l, s in zip(lines, scales)))
        score = (fits, min(scales), sum(scales), -len(lines))
        if best_score is None or score > best_score:
            best, best_score = list(zip(lines, scales)), score
    return best


def split_balanced(words, parts):
    total = len(" ".join(words))
    target = total / parts
    lines, cur = [], ""
    for w in words:
        if cur and len(cur) + 1 + len(w) > target and len(lines) < parts - 1:
            lines.append(cur)
            cur = w
        else:
            cur = f"{cur} {w}" if cur else w
    lines.append(cur)
    return lines


def block_height(scales):
    gap = 2 if len(scales) > 1 else 0
    return sum(7 * s for s in scales) + gap * (len(scales) - 1) * max(1, min(scales))


def draw_frame(message, W, H, offset=0):
    cv = Canvas(W, H)
    draw_border(cv, offset)
    t = max(1, min(cfg["border_thickness"], min(W, H) // 4))
    pad = 2
    x0, y0 = t + pad, t + pad
    iw, ih = W - 2 * x0, H - 2 * y0
    lines = layout_text(message, iw, ih)
    scales = [s for _, s in lines]
    gap = (2 * max(1, min(scales))) if len(lines) > 1 else 0
    y = y0 + max(0, (ih - block_height(scales)) // 2)
    color = hex_rgb(cfg["color"])
    for text, s in lines:
        x = x0 + max(0, (iw - text_width(text, s)) // 2)
        cv.text(x, y, text, color, s)
        y += 7 * s + gap
    return cv


# -------------------------------------------------------------------- FPP
class FPPError(Exception):
    pass


def model_url(path=""):
    return (f"{cfg['fpp_host']}/api/overlays/model/"
            f"{urllib.parse.quote(cfg['model_name'])}{path}")


def list_models():
    try:
        with urllib.request.urlopen(f"{cfg['fpp_host']}/api/overlays/models",
                                    timeout=5) as resp:
            return [m.get("Name") for m in json.load(resp)]
    except Exception:
        return []


_size = {}


def model_size():
    if "wh" not in _size:
        w, h = 192, 64
        try:
            with urllib.request.urlopen(model_url(), timeout=5) as r:
                m = json.load(r)
            w = int(m.get("width") or w)
            h = int(m.get("height") or h)
        except Exception as e:
            log(f"Couldn't read model size from FPP ({e}); assuming {w}x{h}")
        _size["wh"] = (w, h)
    return _size["wh"]


def fpp_request(method, path, payload=None):
    url = model_url(path)
    body = json.dumps(payload).encode() if payload is not None else None
    req = urllib.request.Request(url, data=body, method=method,
                                 headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            return resp.read()
    except urllib.error.HTTPError as e:
        msg = f"{method} {url} -> HTTP {e.code}"
        if e.code == 404:
            models = list_models()
            if models and cfg["model_name"] not in models:
                msg += (f"\n  Model '{cfg['model_name']}' not found. "
                        f"FPP has these models: {models}")
        raise FPPError(msg)


def fpp_put(path, payload=None):
    return fpp_request("PUT", path, payload if payload is not None else {})


def fpp_clear():
    try:
        fpp_request("GET", "/clear")
    except FPPError:
        fpp_put("/clear")


def fpp_command(command, args):
    body = json.dumps({"command": command, "multisyncCommand": False,
                       "multisyncHosts": "", "args": args}).encode()
    req = urllib.request.Request(f"{cfg['fpp_host']}/api/command", data=body,
                                 headers={"Content-Type": "application/json"},
                                 method="POST")
    try:
        with urllib.request.urlopen(req, timeout=10) as r:
            return r.read()
    except urllib.error.HTTPError as e:
        detail = e.read().decode(errors="replace").strip()[:300]
        raise FPPError(f"FPP command '{command}' failed: {e} - {detail}")


def own(path):
    """Files made while running as root are handed to the media folder's
    owner so FPP's web pages can still manage them."""
    try:
        if os.geteuid() == 0:
            st = os.stat(MEDIA_DIR)
            os.chown(path, st.st_uid, st.st_gid)
    except Exception:
        pass


_frame_no = [0]


def show_image(cv):
    _frame_no[0] += 1
    name = f"{IMAGE_PREFIX}{os.getpid()}_{_frame_no[0]}.png"
    path = os.path.join(IMAGES_DIR, name)
    with open(path, "wb") as f:
        f.write(cv.png())
    own(path)
    fpp_put("/state", {"State": 1})
    fpp_command("Overlay Model Effect", [
        cfg["model_name"], "Enabled", "Image", "None", "Center", "0", "0", name])
    cleanup_images(keep=name, older_than=5)


def cleanup_images(keep=None, older_than=0):
    try:
        for fn in os.listdir(IMAGES_DIR):
            if fn.startswith(IMAGE_PREFIX) and fn.endswith(".png") and fn != keep:
                full = os.path.join(IMAGES_DIR, fn)
                if time.time() - os.path.getmtime(full) > older_than:
                    os.remove(full)
    except Exception:
        pass


def show_text(message):
    scrolling = cfg["position"] != "Center"
    fpp_put("/state", {"State": 1})
    fpp_clear()
    fpp_put("/text", {
        "Message": " ".join(p.strip() for p in message.split("|")),
        "Color": cfg["color"],
        "Font": cfg["font"],
        "FontSize": cfg["font_size"],
        "AntiAlias": True,
        "Position": cfg["position"],
        "PixelsPerSecond": cfg["scroll_speed"] if scrolling else 0,
        "AutoEnable": False,
    })


def display(message, offset=0):
    if cfg["border_enabled"]:
        W, H = model_size()
        show_image(draw_frame(message, W, H, offset))
    else:
        show_text(message)


def turn_off():
    fpp_clear()
    fpp_put("/state", {"State": 0})
    cleanup_images()


def fetch_message():
    try:
        return build_message(get_weather())
    except Exception as e:
        log(f"Weather fetch failed: {e}")
        return None


def update_once():
    message = fetch_message()
    if message is None:
        return False
    try:
        display(message)
        log(f"Displayed: {message.replace('|', ' / ')}")
        return True
    except Exception as e:
        log(f"FPP display failed: {e}")
        return False


def run_loop(interval):
    chasing = cfg["border_enabled"] and cfg["border_chase"]
    log(f"Updating every {interval}s" + (" with chasing border" if chasing else ""))
    message, offset = None, 0
    while True:
        new = fetch_message()
        if new is not None:
            message = new
            log(f"Weather: {message.replace('|', ' / ')}")
        next_fetch = time.time() + interval
        if message is None:
            time.sleep(interval)
            continue
        if not chasing:
            try:
                display(message)
            except Exception as e:
                log(f"FPP display failed: {e}")
            time.sleep(max(0, next_fetch - time.time()))
            continue
        step = 1.0 / max(1, min(cfg["chase_speed"], 10))
        while time.time() < next_fetch:
            start = time.time()
            try:
                display(message, offset)
            except Exception as e:
                log(f"FPP display failed: {e}")
                time.sleep(5)
            offset += 1
            time.sleep(max(0, step - (time.time() - start)))


# ------------------------------------------------------------------- main
def main():
    load_config()
    args = sys.argv[1:]

    if "--text" in args:
        i = args.index("--text")
        if i + 1 < len(args):
            cfg["message"] = args[i + 1]
            del args[i:i + 2]

    if "--off" in args:
        try:
            turn_off()
            log("Overlay cleared and disabled")
        except Exception as e:
            log(f"FPP display failed: {e}")
        return

    if "--loop" in args:
        i = args.index("--loop")
        try:
            interval = int(args[i + 1])
        except (IndexError, ValueError):
            interval = cfg["loop_interval"]
        run_loop(max(interval, 30))     # Ambient data only updates ~1/min
    else:
        sys.exit(0 if update_once() else 1)


if __name__ == "__main__":
    main()

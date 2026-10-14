#!/usr/bin/env python3
"""
Build Santa's route (route.json) from Natural Earth's public-domain list of
populated places. Run by hand when the route should change; the plugin only
reads the finished route.json and downloads nothing for it.

  python3 tools/make_route.py ne_10m_populated_places_simple.geojson > route.json

The geojson comes from the same Natural Earth collection as the map, e.g.
https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_10m_populated_places_simple.geojson

How the route is made:
- About STOPS cities, picked by population and kept at least MIN_KM apart,
  so they are spread around the world instead of bunched in big metro areas,
  plus the biggest place of every country (and every US state, Canadian
  province and Australian state) that would otherwise have no stop.
- Santa reaches each city around its local (sun) midnight on Christmas Eve:
  he sweeps west from the date line in BAND_MIN-minute slices of the night,
  and within a slice always flies to the nearest city he hasn't visited, so
  he doesn't zig-zag between continents.
- Gifts delivered grow with each city's population, to about TOTAL_GIFTS.
- Times are stamped for Christmas Eve 2019 (the plugin moves them to this
  year), in the same layout as the route file the plugin used to download.
"""

import json
import math
import sys
from datetime import datetime, timezone

STOPS = 450
MIN_KM = 220
EXTRA_MIN_KM = 60             # remote-spot pass: skip a place this close to a stop
# Every country gets at least one stop, and so does every state/province of
# these (Alaska, Yukon, Northern Territory ... even when few people live there)
EVERY_STATE = {"United States of America", "Canada", "Australia"}
BAND_MIN = 60                 # minutes of the night per slice
STAY_S = 60                   # seconds at each stop (less where stops are close in time)
TOTAL_GIFTS = 7_600_000_000
YEAR = 2019                   # stamp year (the plugin shifts to the current year)
TAKEOFF_LEAD_MIN = 0          # takeoff this long before the first slice starts
LANDING_LAG_MIN = 45          # landing this long after the last stop
NORTH_POLE = {"lat": 90.0, "lng": 0.0}

# State/province shown with the country for these (like "Illinois, USA")
WITH_STATE = {"United States of America", "Canada", "Australia", "Brazil", "Mexico", "India", "China",
              "Russia"}
SHORT_COUNTRY = {
    "United States of America": "USA", "United Kingdom": "UK",
    "Democratic Republic of the Congo": "DR Congo", "Congo (Kinshasa)": "DR Congo",
    "Congo (Brazzaville)": "Congo", "Republic of Congo": "Congo",
    "Central African Republic": "CAR", "United Arab Emirates": "UAE",
    "Bosnia and Herzegovina": "Bosnia", "Trinidad and Tobago": "Trinidad",
    "Saint Vincent and the Grenadines": "St. Vincent", "Antigua and Barbuda": "Antigua",
    "Sao Tome and Principe": "Sao Tome", "Papua New Guinea": "PNG",
    "Federated States of Micronesia": "Micronesia", "Dominican Republic": "Dominican Rep.",
    "Czech Republic": "Czechia", "South Korea": "S. Korea", "North Korea": "N. Korea",
    "South Georgia and the Islands": "South Georgia", "Svalbard and Jan Mayen Islands": "Svalbard",
    "United States Virgin Islands": "US Virgin Is.", "Turks and Caicos Islands": "Turks & Caicos",
    "Northern Mariana Islands": "N. Mariana Is.", "Saint Kitts and Nevis": "St. Kitts",
}


def km_between(a, b):
    p1, p2 = math.radians(a[0]), math.radians(b[0])
    dp, dl = p2 - p1, math.radians(b[1] - a[1])
    h = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * 6371.0 * math.asin(math.sqrt(min(1.0, h)))


def load_places(path):
    out = []
    for f in json.load(open(path, encoding="utf-8"))["features"]:
        p = f["properties"]
        try:
            lat, lng, pop = float(p["latitude"]), float(p["longitude"]), int(p.get("pop_max") or 0)
        except (TypeError, ValueError, KeyError):
            continue
        if pop <= 0 or abs(lat) > 85:
            continue
        country = p.get("adm0name") or p.get("sov0name") or ""
        state = p.get("adm1name") or ""
        if country in WITH_STATE and state:
            region = f"{state}, {SHORT_COUNTRY.get(country, country)}"
        else:
            region = SHORT_COUNTRY.get(country, country)
        out.append({"name": p.get("nameascii") or p.get("name"), "region": region,
                    "country": country, "state": state,
                    "lat": round(lat, 4), "lng": round(lng, 4), "pop": pop,
                    "id": f"ne_{p.get('ne_id')}"})
    return out


def pick(places):
    """Biggest cities first, skipping any closer than MIN_KM to one already
    chosen; then the biggest place of every country (and of every state or
    province in EVERY_STATE) that still has no stop, so remote places such as
    Alaska, Greenland, Iceland and the Pacific islands get a visit too."""
    chosen = []
    biggest = sorted(places, key=lambda c: -c["pop"])
    for c in biggest:
        if all(km_between((c["lat"], c["lng"]), (o["lat"], o["lng"])) >= MIN_KM for o in chosen):
            chosen.append(c)
            if len(chosen) >= STOPS:
                break

    def key(c):
        return (c["country"], c["state"]) if c["country"] in EVERY_STATE else (c["country"],)
    covered = {key(c) for c in chosen}
    for c in biggest:
        k = key(c)
        if k in covered or not c["country"]:
            continue
        covered.add(k)
        if all(km_between((c["lat"], c["lng"]), (o["lat"], o["lng"])) >= EXTRA_MIN_KM for o in chosen):
            chosen.append(c)
    return chosen


def build(places):
    midnight = datetime(YEAR, 12, 25, tzinfo=timezone.utc).timestamp()   # 00:00 UTC Dec 25

    def solar_midnight(c):
        return midnight - c["lng"] / 15 * 3600          # east of Greenwich is earlier

    band = BAND_MIN * 60
    t_first = min(solar_midnight(c) for c in places)
    bands = {}
    for c in places:
        bands.setdefault(int((solar_midnight(c) - t_first) // band), []).append(c)
    stops = []
    here = (NORTH_POLE["lat"], NORTH_POLE["lng"])
    for i in sorted(bands):
        # within a slice, always fly to the nearest city not yet visited
        left, group = list(bands[i]), []
        while left:
            c = min(left, key=lambda c: km_between(here, (c["lat"], c["lng"])))
            left.remove(c)
            group.append((c, km_between(here, (c["lat"], c["lng"]))))
            here = (c["lat"], c["lng"])
        # share the slice's flying time out by distance, so Santa flies at a
        # steady speed (a long hop takes longer) instead of jumping
        start, end = t_first + i * band, t_first + (i + 1) * band
        stay = min(STAY_S, band * 0.4 / len(group))
        fly = band - stay * len(group)
        total_km = sum(k for _, k in group) or 1.0
        t = start
        for c, k in group:
            t += max(20.0, fly * k / total_km)
            stops.append((t, stay, c))
            t += stay
    total_pop = sum(c["pop"] for c in places)
    gifts, dests = 0, []
    takeoff = t_first - TAKEOFF_LEAD_MIN * 60
    dests.append({"id": "takeoff", "city": "North Pole", "region": "Santa's Village",
                  "arrival": int(takeoff * 1000), "departure": int(takeoff * 1000),
                  "presentsDelivered": 0, "location": NORTH_POLE})
    last_dep = takeoff
    for arrive, stay, c in stops:
        arrive = max(arrive, last_dep + 20)
        depart = arrive + stay
        gifts += c["pop"] / total_pop * TOTAL_GIFTS
        dests.append({"id": c["id"], "city": c["name"], "region": c["region"],
                      "arrival": int(arrive * 1000), "departure": int(depart * 1000),
                      "presentsDelivered": int(gifts),
                      "location": {"lat": c["lat"], "lng": c["lng"]}})
        last_dep = depart
    landing = last_dep + LANDING_LAG_MIN * 60
    dests.append({"id": "landing", "city": "North Pole", "region": "Santa's Village",
                  "arrival": int(landing * 1000), "departure": int(landing * 1000),
                  "presentsDelivered": int(gifts), "location": NORTH_POLE})
    return dests


def main():
    if len(sys.argv) != 2:
        sys.exit(__doc__)
    dests = build(pick(load_places(sys.argv[1])))
    json.dump({"source": "Natural Earth populated places (public domain), built by tools/make_route.py",
               "destinations": dests}, sys.stdout, indent=0, separators=(",", ":"))
    sys.stdout.write("\n")


if __name__ == "__main__":
    main()

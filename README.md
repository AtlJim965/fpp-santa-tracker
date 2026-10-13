# Santa Tracker for FPP

Shows Santa's live position on a Pixel Overlay Model (LED matrix):

* a world map with Santa, his trail, and your town, which zooms in on your
  area (with lakes and state borders) when Santa gets close
* an info panel: a countdown before Christmas Eve, then "NOW IN" / "FLYING TO",
  distance from your town, and gifts delivered
* or a scrolling text message (or the map, then the text)

Your town can be added as one of Santa's stops ("FLYING TO: YOUR TOWN" ...
"HE'S HERE!").

Requires FPP 10 or newer. Uses FPP's own Python 3; no extra packages.

## Install

In FPP go to **Content Setup > Plugin Manager**, paste this into the box for
installing a plugin from a URL, and install:

```
https://raw.githubusercontent.com/AtlJim965/fpp-santa-tracker/main/pluginInfo.json
```

FPP picks up the plugin's commands when fppd starts, so click **Restart FPPD**
when FPP asks.

## Use

**Content Setup > Santa Tracker** has all the settings, with a live preview of
your panel beside them. Choose a moment in **Preview moment** to see any day
before Christmas Eve, Christmas Eve itself, or Santa's visit to your town, and
press **Show on matrix** to try it on the real panel. Under Location, the page
names the nearest stop on Santa's route, so you can check your coordinates.

FPP Commands (playlists, schedules, buttons):

| Command | What it does |
|---|---|
| Santa Tracker - Start | Starts the display; it keeps updating until Stop |
| Santa Tracker - Stop | Stops the display and clears the model |
| Santa Tracker - Show Once | Shows Santa's position for N seconds |
| Santa Tracker - Preview | Shows a chosen moment for N seconds |

A typical setup: schedule **Santa Tracker - Start** at dusk and
**Santa Tracker - Stop** when the show ends. Or tick **Start the display
automatically when FPP starts**.

## What it downloads (privacy)

Nothing about you or your show is sent anywhere. While the display or the
preview is in use, the plugin downloads:

* Google's public Santa Tracker route file, from `firebasestorage.googleapis.com`
  (checked at most every 6 hours)
* once, public-domain Natural Earth map data (coastlines, lakes, state and
  country borders), from `raw.githubusercontent.com` (or `cdn.jsdelivr.net` /
  `d2ad6b4ur7yvpq.cloudfront.net` if that fails)

## Files it writes

* `config/plugin.fpp-santa-tracker`: your settings
* `plugindata/fpp-santa-tracker/`: the saved route and map data
* `images/santa_map_*.png`: the current map picture, which FPP's overlay Image
  effect reads from its images folder. Old frames are deleted automatically.
* `logs/plugin-fpp-santa-tracker.log`: the plugin's log

Uninstalling stops the display and removes the map pictures and saved data.
Your settings file is kept, so a reinstall remembers them.

## License

GPL v2. See `LICENSE`.

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

* once, public-domain Natural Earth map data (coastlines, lakes, state and
  country borders), from `raw.githubusercontent.com` (or `cdn.jsdelivr.net` /
  `d2ad6b4ur7yvpq.cloudfront.net` if that fails)

## Files it writes and what it changes

* `config/plugin.fpp-santa-tracker`: your settings
* `plugindata/fpp-santa-tracker/`: the saved map data, small status
  notes while the display runs, and the overlay model's state from before the
  plugin first drew on it
* `images/santa_map_*.png`: the current map picture, which FPP's overlay Image
  effect reads from its images folder. Old frames are deleted automatically.
* `logs/plugin-fpp-santa-tracker.log`: the plugin's log
* Your overlay model is switched on while the plugin draws on it. When the
  display stops (Stop, or a Show Once / Preview finishing), the model is put
  back the way it was, so your sequences show through again.

Updating restarts a running display on the new code. If an update changes the
plugin's commands, FPP asks for an FPPD restart.

Uninstalling stops the display, puts the model back, and removes the map
pictures, saved data and log. FPP keeps listing the plugin's commands until
FPPD restarts, so it asks for a restart. Your settings file is kept, so a
reinstall remembers them; delete `config/plugin.fpp-santa-tracker` to remove
them too.

## More

* [DOCUMENTATION.md](DOCUMENTATION.md): every setting, the commands, how the map
  works, and troubleshooting
* [CHANGELOG.md](CHANGELOG.md): what changed and when

## License

GPL v2. See `LICENSE`.

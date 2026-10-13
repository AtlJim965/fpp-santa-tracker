# Santa Tracker for FPP

Shows Santa's live position on a Pixel Overlay Model (LED matrix):

* a world map with Santa, his trail, and your town
* an info panel: a countdown before Christmas Eve, then "NOW IN" / "FLYING TO",
  distance from your town, and gifts delivered
* or a scrolling text message (or the map, then the text)

Your town can be added as one of Santa's stops ("FLYING TO: CONYERS" ...
"HE'S HERE!").

Requires FPP 10 or newer. Standard Python 3 only, no extra packages.

## Install

**From GitHub (Plugin Manager):** Content Setup > Plugin Manager, paste this
repository's `pluginInfo.json` URL, and install. Then click **Restart FPPD**.

**From the zip file:**

```
cd /home/fpp/media/plugins
sudo unzip ~/fpp-santa-tracker.zip
sudo chown -R fpp:fpp fpp-santa-tracker
sudo ./fpp-santa-tracker/scripts/fpp_install.sh
```

Then click **Restart FPPD** in FPP's web page (FPP loads plugin commands when it starts).

## Use

**Content Setup > Santa Tracker** has all the settings, with a live preview of
your panel. Choose a moment in **Preview moment** to see any day before
Christmas Eve, Christmas Eve itself, or Santa's visit to your town, and
**Show on matrix** to try it on the real panel.

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

## Data

Santa's route comes from Google's Santa Tracker; the world outline is the
public-domain Natural Earth coastline. Both are downloaded once and saved in
`media/plugindata/fpp-santa-tracker`. Nothing about you or your show is sent
anywhere.

Log: `media/logs/plugin-fpp-santa-tracker.log`

# Santa Tracker for FPP - Documentation

The full reference for the plugin: every setting, the FPP Commands, how the
map works, the files it uses, and fixes for common problems. For a quick
introduction see [README.md](README.md). For what changed and when, see the
[change log](CHANGELOG.md).

## Contents

* [What it does](#what-it-does)
* [Requirements](#requirements)
* [Install, update and uninstall](#install-update-and-uninstall)
* [The settings page](#the-settings-page)
* [Preview moments](#preview-moments)
* [FPP Commands](#fpp-commands)
* [Santa's route](#santas-route)
* [How the map works](#how-the-map-works)
* [What the info panel shows](#what-the-info-panel-shows)
* [Files and downloads](#files-and-downloads)
* [Troubleshooting](#troubleshooting)
* [Command line (advanced)](#command-line-advanced)
* [Change log](#change-log)

## What it does

Santa Tracker shows Santa's position on a Pixel Overlay Model (an LED matrix)
in FPP. It can show:

* **a map with an info panel** - a map of the world, or a globe that turns
  to follow Santa (either one zooms in on your area when Santa is close),
  with Santa, his trail and your town on the left, and text
  on the right: a countdown before Christmas Eve, then "NOW IN" / "FLYING TO",
  the distance from your town and the gifts delivered
* **a scrolling text message**, such as "Santa is flying to Seville, Spain -
  he reaches Your Town in 6 hours 48 min - 4,187 mi from Your Town"
* **the map, then the scrolling text**, one after the other

Santa's route is built into the plugin: about 580 cities around the world, each reached around its local midnight (see [Santa's route](#santas-route)). Your town can be added as
one of his stops, so the matrix shows "FLYING TO: YOUR TOWN" and then
"HE'S HERE!".

## Requirements

* FPP 9 or newer
* A Pixel Overlay Model for the matrix (Input/Output Setup > Pixel Overlay
  Models). A panel about 128x64 or bigger shows the map and the panel
  together best.
* Internet access for the first download of the map data, and for Santa's
  route on Christmas Eve
* No extra packages: the plugin uses FPP's own Python 3

## Install, update and uninstall

**Install:** in FPP go to **Content Setup > Plugin Manager**, paste this into
the box for installing a plugin from a URL, and install:

```
https://raw.githubusercontent.com/AtlJim965/fpp-santa-tracker/main/pluginInfo.json
```

FPP only loads a plugin's commands when fppd starts, so click **Restart
FPPD** when FPP asks. Until then, **Start Display** and **Show on matrix**
report that the commands aren't loaded yet.

**Update:** use **Update** in the Plugin Manager. A running display restarts
on the new code. If the update changed the plugin's commands, FPP asks for an
FPPD restart.

**Uninstall:** use **Uninstall** in the Plugin Manager. It stops the display,
puts the overlay model back the way it was, and removes the map pictures,
saved data and log. FPP keeps listing the plugin's commands until fppd
restarts, so it asks for a restart. Your settings file is kept so a reinstall
remembers them; delete `config/plugin.fpp-santa-tracker` to remove them too.

Uninstalling can't edit your playlists or schedules. Remove any **Santa
Tracker** commands from them first.

## The settings page

Open **Content Setup > Santa Tracker**. Settings are on the left and a live
preview of your panel is on the right. Nothing is saved until you click
**Save settings**, and nothing goes to the matrix until you press **Start
Display** or **Show on matrix**.

The buttons at the top:

* **Start Display** - starts the running display. It shows "Starting..."
  until the display reports in (this can take several seconds on a Pi), then
  turns green and shows Running.
* **Stop** - stops the display, clears the model and puts it back the way it
  was before the plugin drew on it.
* **Help** - opens a help page in FPP's help window (or press **F1**) that
  describes each setting, with links at the top to each section and the
  preview last.

Point at the small **?** beside a section's title (or tap it on a phone) for
a short description of that section.

The setting names in `code` are the keys stored in
`config/plugin.fpp-santa-tracker`.

### Location

| Setting | Key | Default | Notes |
|---|---|---|---|
| City name | `CityName` | blank ("Your Town") | Shown on the panel and in messages |
| State | `State` | blank | Shown under the city name |
| Follow FPP's location & time zone | `FollowFppLocation` | on | See below |
| Latitude | `Latitude` | FPP's location | Decimal degrees, e.g. `42.088289` |
| Longitude | `Longitude` | FPP's location | Decimal degrees. West of Greenwich (the Americas) is negative, e.g. `-87.980451` |
| Time zone | `TimeZone` | FPP's time zone | Used for the countdown and preview times |
| Make my town one of Santa's stops | `AddHomeStop` | on | Adds your town to the route, just after the nearest real stop |
| Minutes "Flying to" shows | `HomeApproachMin` | 3 | How long he flies to your town (1-30) |
| Minutes "Now in" shows | `HomeStayMin` | 2 | How long he stays (1-30) |

**Follow FPP's location & time zone** (on by default) uses the location and
time zone set in FPP (Settings > System). The Latitude, Longitude and Time
zone boxes show FPP's values and can't be changed. If they are changed in
FPP later, the plugin picks that up and saves it in its own settings: when
the settings page is opened, within 5 minutes while the display runs, and
when the plugin is updated. Untick it to type a different town; the plugin
then keeps those values whatever FPP is set to. If FPP has no location set,
the values typed in the boxes are used until it has one.

Settings saved by an older version (before this box existed) follow FPP if
they still match FPP's location and time zone, and keep their own values if
they don't.

Under the fields, the page shows where your town is
marked and the nearest stop on Santa's route, so you can check the
coordinates. For example, Arlington Heights, IL shows "Chicago, Illinois, USA
(23 mi)".

### Map

| Setting | Key | Default | Notes |
|---|---|---|---|
| Show Santa as | `SantaMarker` | Sleigh and reindeer | Or **A dot**. Small maps (under 40x16) always use the dot. |
| Map view | `MapView` | Zoom in when Santa is near | Or **Globe (zoom in when Santa is near)**. Both switch to the zoomed-in map while Santa is near your town. |
| Zoomed-in area | `LocalZoom` | Medium (about 2,500 mi) | Wide (about 4,500 mi) or Close (about 1,200 mi) |
| World map shape | `WorldShape` | Fill the panel | Or **True proportions**. See [How the map works](#how-the-map-works). |
| Globe night-side brightness (%) | `GlobeNight` | 50 | Globe only: how bright the night side is (0-100; 100 = no night shading) |
| Globe ocean | `GlobeOceanColor` | `#002D78` | Globe only. The space around the globe is black. |
| Text beside the zoomed-in map | `ZoomedText` | Small (bigger map) | Or **Normal**. See [What the info panel shows](#what-the-info-panel-shows). |
| Land | `LandColor` | `#00DC46` | |
| Ocean | `OceanColor` | `#000000` | |
| My location | `HomeColor` | `#FFFFFF` | The small + marking your town |
| Santa's sleigh | `SantaColor` | `#FF0000` | Santa's suit and the dot |
| Santa's trail | `TrailColor` | `#FFB400` | One dot per stop he has visited |
| Borders | `BorderColor` | `#006428` | State and country lines |
| Show Santa's trail | `ShowTrail` | on | |
| Show state and country borders on the zoomed-in map | `ShowBorders` | on | |

### Right-hand text colors

**Color style** (`PanelColorMode`) chooses how the info panel is colored:

* **Each line its own color** (default) - each kind of line uses its color
  below
* **All one color** - every line uses **Text color** (`PanelSingleColor`)
* **Every letter / word / line a different color** - cycles through **Colors
  to cycle through** (`PanelPalette`): Christmas, Red & Green, Candy Cane,
  Rainbow, Winter, Gold & Silver, or your own colors (`PanelCustomColors`,
  with **How many colors**)

The per-line colors:

| Setting | Key | Default |
|---|---|---|
| "NOW IN:" / "FLYING TO:" | `HeadingColor` | `#FFAA00` |
| City name | `CityColor` | `#FFFFFF` |
| State / country | `RegionColor` | `#787878` |
| "ETA 3 MIN" | `EtaColor` | `#FFFFFF` |
| Distance / "CLOSE BY!" / "JUST LEFT" | `DistanceColor` | `#FF0000` |
| Gifts delivered | `GiftsColor` | `#00C83C` |
| Countdown words | `CountdownTextColor` | `#FFFFFF` |
| Countdown number | `CountdownNumberColor` | `#FF0000` |
| "HE'S HERE!" / "MERRY" | `Greeting1Color` | `#FF0000` |
| "HO HO HO!" / "CHRISTMAS!" | `Greeting2Color` | `#00C83C` |

### Wording

| Setting | Key | Default | Notes |
|---|---|---|---|
| Countdown message | `CountdownPhrase` | begins his journey | Shown as "Santa ... in 72 days" |
| ETA shows | `EtaMode` | Time until Santa reaches my town | Or **Time to his next stop**. See [What the info panel shows](#what-the-info-panel-shows). |
| Show distance from my town | `ShowDistance` | on | |
| Show gifts delivered | `ShowGifts` | on | |

### Display

| Setting | Key | Default | Notes |
|---|---|---|---|
| Overlay model | `ModelName` | first model | The Pixel Overlay Model to draw on |
| What to show on the matrix | `DisplayMode` | Map with info panel | Or **Scrolling text** / **Map, then scrolling text**. This is where scrolling text is turned on. |
| Map seconds | `MapSeconds` | 20 | How long the map shows before the text, in "Map, then scrolling text" mode |
| Update every (seconds) | `RefreshSeconds` | 30 | Under the preview, next to **Run live from this moment**. How often the matrix redraws (5-3600): the running display, **Show on matrix**, **Show Once** and the map part of "Map, then scrolling text". 15 is a good choice for Christmas Eve. The matrix only reloads when the picture has changed. |
| Start the display automatically when FPP starts | `AutoStart` | off | |

### Scrolling text

Only used when **Display > What to show on the matrix** is **Scrolling
text** or **Map, then scrolling text**.

| Setting | Key | Default |
|---|---|---|
| Font | `TextFont` | FreeSansBold |
| Font size | `TextFontSize` | 24 |
| Scroll speed | `TextScrollSpeed` | 40 |
| Text color | `TextColor` | `#FF0000` |
| Color effect | `TextColorMode` | Single |
| Color effect palette | `TextPalette` | Christmas |

### Advanced

| Setting | Key | Default | Notes |
|---|---|---|---|
| Map width in pixels | `MapWidth` | 0 (automatic) | Automatic is twice the panel height, leaving at least 48 pixels for the info panel |
| Right-edge margin | `PanelRightMargin` | 2 | Pixels kept clear at the right edge of the panel (0-40) |

## Preview moments

**Preview moment** (below the preview) shows the panel at another time. The
same moments are available to the **Santa Tracker - Preview** command.

| Moment | What it shows |
|---|---|
| Right now | The current time |
| 30 / 14 / 7 / 3 / 1 days before | The countdown on those days |
| Christmas Eve - takeoff | Just after Santa leaves the North Pole |
| Christmas Eve - noon / 6 PM / 9 PM | Those times in your time zone |
| Approaching my town | 1 minute before Santa reaches your town |
| Santa in my town | While Santa is in your town |
| Christmas morning | Santa back home |

**Show on matrix** saves your settings, then plays the chosen moment on the
matrix for **Show for (seconds)** (3-600) and then turns the matrix off. The
clock keeps running from that moment and the matrix redraws every **Update
every** seconds, so a preview of "Approaching my town" plays out: "LOCAL IN 1
MIN", then "HE'S HERE!".

**Run live from this moment** is for testing the running display: it starts
**Start Display** as if it were the chosen moment, and it keeps going (and
updating every **Update every** seconds) until you press **Stop**. While it
runs, the start button shows **Running (simulated: ...)** in yellow. Your
FPP's clock is not changed. Press **Stop** when you're done, then **Start
Display** for the real time.

**LED look** draws the preview as round LEDs; it only changes the picture in
the browser.

## FPP Commands

Use these in playlists, schedules, buttons and FPP's other command lists.

| Command | Arguments | What it does |
|---|---|---|
| Santa Tracker - Start | Display, Simulate time | Starts the display. It keeps updating until **Stop**. Leave **Simulate time** at **Real time** for the show; another choice runs it as if it were that [preview moment](#preview-moments), for testing. |
| Santa Tracker - Stop | none | Stops the display and any Show Once or Preview, clears the model and puts it back the way it was |
| Santa Tracker - Show Once | Display, Seconds | Shows Santa's current position for that many seconds, then stops |
| Santa Tracker - Preview | Moment, Seconds | Shows a [preview moment](#preview-moments) for that many seconds |

**Display** is **Saved setting**, **Map**, **Text** or **Map then text**.
**Seconds** is 3-600 (20 by default).

A Show Once or Preview started while the display is running takes over the
matrix, then hands it back to the running display when it finishes.

A typical setup: schedule **Santa Tracker - Start** at dusk and **Santa
Tracker - Stop** when the show ends, or tick **Start the display
automatically when FPP starts**.

## Santa's route

Santa's route is part of the plugin (`route.json`), so nothing has to be
downloaded for it, even on Christmas Eve. It was built from Natural Earth's
public-domain list of the world's cities by `tools/make_route.py`:

* about 580 stops: the biggest cities, kept at least 220 km apart so they are
  spread around the world, plus the biggest place of every country and every
  US state, Canadian province and Australian state, so remote places such as
  Alaska, the Yukon, Greenland, Iceland, Svalbard and the Pacific islands get
  a visit too
* Santa reaches each city within an hour of its local midnight. He sweeps
  west from the date line an hour of the night at a time, always flying on to
  the nearest city he hasn't visited, at a steady speed within each hour
* he takes off from the North Pole at about 12:00 UTC on December 24 (6 AM
  Chicago time) and is back about 12:50 UTC on Christmas morning
* gifts delivered grow with each city's population, to about 7.6 billion

To change the route (more or fewer stops, different spacing), edit the
numbers at the top of `tools/make_route.py` and run it with Natural Earth's
`ne_10m_populated_places_simple.geojson`; it writes a new `route.json`.

## How the map works

**Views.** The world view shows the whole world from 72°N southwards. With
**World map shape: Fill the panel** (the default) it stops at 60°S, south of
everywhere Santa visits, and fills the map's height, so Antarctica and the
empty Southern Ocean don't take up the bottom of the panel. On a tall panel
this stretches shapes vertically (about 1.5 times on a 384x256 panel); on a
wide, short panel it changes little. **True proportions** keeps the shapes
exact and shows as far south as the panel's height allows. The
zoomed-in view is centred on your town and shows lakes and state and country
borders. With **Zoom in when Santa is near**, the map switches to the zoomed
view while Santa is inside that area and back to the world when he leaves.

**Globe.** With **Map view: Globe (zoom in when Santa is near)**, the world
map is replaced by a round globe, as tall as the panel. On Christmas Eve the
globe holds still and Santa flies across its face; when he is about a third
of the way to the edge it turns, and tilts north or south, to bring him back
near the middle (so stops far south, such as South Georgia, aren't stuck on
the bottom edge). Before and after, while he is at the North Pole, it is
tilted to show the northern hemisphere and slowly spins (one turn every 18 minutes, a step each time the
display updates). The night side is shaded from the sun's real position, so
you can see Santa riding the edge of the night (he visits each place around
midnight). **Globe night-side brightness** sets how dark the night side is
and **Globe ocean** its ocean color. When Santa is near your town the map
switches to the zoomed-in view, as with **Zoom in when Santa is near**. A
new globe takes about half a second to draw on a fast computer (a few
seconds on a Raspberry Pi); it is reused while the globe hasn't turned.

**Projection.** The world and zoomed-in views use a Mercator projection, so shapes look like
the maps people know. The map data is the public-domain Natural Earth 1:50m
coastline, with lakes such as the Great Lakes cut out.

**Markers:**

* **Your town** - a small + in the **My location** color, centred on your
  coordinates
* **Santa** - the sleigh and reindeer, facing the way he is flying, with a
  thin dark outline so it stands out over land. It is kept fully on the map
  near the edges.
* **Trail** - one dot for each stop Santa has already visited

## What the info panel shows

| When | Panel |
|---|---|
| Before Christmas Eve | "SANTA BEGINS HIS JOURNEY IN", the number of days, "HO HO HO!". Days are counted on the calendar in your time zone. On Christmas Eve it counts hours, then minutes, until takeoff. |
| Flying | "FLYING TO:", the next city and its region, the time until he reaches your town ("LOCAL IN 6H 48M"), the distance from your town |
| At a stop | "NOW IN:", the city and region, "LOCAL IN ..." (time until he reaches your town), the distance, the gifts delivered so far |
| At your town | "NOW IN:" your town, "HE'S HERE!" |
| Christmas morning | "SANTA IS HOME!", "MERRY CHRISTMAS!", the total gifts delivered |

The distance shows "CLOSE BY!" when Santa is under 50 miles away, and
"JUST LEFT" once he has been to your town (the scrolling text says "he just
left Your Town"). Without **Make my town one of Santa's stops**, "been to
your town" means the stop on his route nearest to you.

**ETA.** With **ETA shows: Time until Santa reaches my town** (the default),
the panel shows "LOCAL IN" and the time until Santa reaches your town, such as
"6H 48M", then "4 MIN" as he gets close. It counts down all through Christmas
Eve, at stops and while flying, and the scrolling text says "he reaches Your
Town in 6 hours 48 min". Once he has visited your town, it goes back to the
time to his next stop ("ETA 3 MIN"). It needs **Make my town one of Santa's
stops**; without it, the panel always shows the time to his next stop. With
**Time to his next stop**, it is always "ETA n MIN", the minutes until his
next stop, which is usually only 1 to 5 minutes away.

**Small text beside the zoomed-in map.** With **Text beside the zoomed-in
map: Small** (the default), the "NOW IN" / "FLYING TO" panel uses a tiny
3x5-pixel font while the map is zoomed in, wrapped to 9 letters a line, so
the panel is narrower and the map wider (on a 384x256 panel the map grows
from 336 to 345 pixels wide, and long lines such as "FLYING TO:" and
"DELIVERED" no longer get cut off). The countdown and Christmas-morning
screens, and the world map, keep the normal text. This has no effect when
**Map width in pixels** is set by hand.

## Files and downloads

**Downloads.** Nothing about you or your show is sent anywhere. The plugin
downloads:

* once, the Natural Earth map data (coastlines, lakes, borders), from
  `raw.githubusercontent.com`, or `cdn.jsdelivr.net` /
  `d2ad6b4ur7yvpq.cloudfront.net` if that fails

**Files** (paths are inside FPP's media folder, normally `/home/fpp/media`):

| Path | What it is |
|---|---|
| `config/plugin.fpp-santa-tracker` | Your settings. With **Follow FPP's location & time zone** on, the plugin updates the location and time zone here when they change in FPP. Kept on uninstall. |
| `plugindata/fpp-santa-tracker/` | The saved map data, small status notes while the display runs, and the overlay model's state from before the plugin first drew on it |
| `images/santa_map_*.png` | The current map picture, which FPP's overlay Image effect reads. Old frames are deleted automatically. |
| `logs/plugin-fpp-santa-tracker.log` | The plugin's log (Status/Control > Logs) |

The overlay model is switched on while the plugin draws on it. When the
display stops (Stop, or a Show Once or Preview finishing), the model is put
back the way it was, so your sequences show through again.

## Troubleshooting

**Your town is in the wrong place.** Check the nearest stop shown under
**Location**. A town in Asia usually means the longitude is missing its
minus sign. A town off West Africa means the coordinates are blank or aren't
plain numbers (no `°`, `N` or `W`, and use a dot as the decimal mark).

**There are lots of orange or red dots on the map.** That is Santa's trail,
one dot per stop he has visited. Untick **Show Santa's trail**, or choose a
trail color that stands out more on your LEDs.

**Start Display or Show on matrix says the commands aren't loaded.** Click
**Restart FPPD**. FPP only reads the plugin's commands when fppd starts.

**Show on matrix does nothing the second time.** A Show on matrix that is
still running isn't replaced by a new one yet. Click **Stop**, or wait for
**Show for (seconds)** to run out, then press it again. Changing **Preview moment**
only updates the browser preview.

**The matrix stays black after stopping.** Stopping puts the overlay model
back the way it was before the plugin first drew on it. If the model was
switched on before that, it stays on: switch it off under Input/Output Setup
> Pixel Overlay Models.

**Santa hardly seems to move.** The matrix redraws every **Update every
(seconds)** (under the preview, next to **Run live from this moment**; 15 is
good for Christmas Eve), not every **Show for (seconds)**, which is only how
long **Show on matrix** plays. A running display reads its settings when it
starts, so click **Stop** and **Start Display** after changing it. The matrix
is only reloaded when the picture changed, and some hops are only a few LEDs
long on a big map (islands, or Santa parked at a stop for a minute), so at
those times only the miles or the countdown change. To watch it on any day,
pick a **Preview moment** and press **Run live from this moment**.

**The countdown or Santa's position is wrong.** The plugin uses the FPP's
clock. Check it with `date` on the FPP (or the time at the top of FPP's
pages); a Raspberry Pi has no clock battery and can come back from a power
cut with the wrong date. Turn on NTP under **Settings > Time** (or run
`sudo timedatectl set-ntp true`). A wrong date also makes downloads from
GitHub fail with "certificate has expired".

**The map shows no land.** The map data hasn't downloaded yet. Check that FPP
has internet access, and look in the plugin log.

**Anything else.** Look in `logs/plugin-fpp-santa-tracker.log` (Status/Control
> Logs).

## Command line (advanced)

The FPP Commands run `santa_tracker.py` for you. You can also run it by hand
on the FPP device, for example to test:

```
python3 santa_tracker.py --loop                 keep the display running and updating
python3 santa_tracker.py --once --seconds 20    show once for 20 seconds, then exit
python3 santa_tracker.py --stop                 stop any running display and clear it
python3 santa_tracker.py --status               print whether the display is running
python3 santa_tracker.py --at d7 --once         show a preview moment on the matrix
python3 santa_tracker.py --preview map.png      save a picture of the panel and exit
python3 santa_tracker.py --dry-run              print the scrolling-text message only
```

* `--mode map|text|both` overrides **What to show on the matrix**.
* `--at` takes a moment key (`now`, `d30`, `d14`, `d7`, `d3`, `d1`,
  `eve_takeoff`, `eve_noon`, `eve_6pm`, `eve_9pm`, `home_soon`, `home_here`,
  `landed`), a moment name from the table above, or a date and time such as
  `2026-12-24T22:00` (in the plugin's time zone).

## Change log

See [CHANGELOG.md](CHANGELOG.md).

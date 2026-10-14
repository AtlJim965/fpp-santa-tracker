# Change log

All notable changes to the Santa Tracker plugin. Newest first. Part of the
[documentation](DOCUMENTATION.md).

When you change the plugin, add a line under **Unreleased** in the same
change: what a user will notice, in plain words. Move the lines under a dated
heading when the change is released.

## Unreleased

### Added

* A small **?** next to each section title on the settings page (Location,
  Map, Right-hand text colors, Wording, Display, Scrolling text, Preview):
  point at it, or tap it on a phone, for a short description of that
  section.
* **Help** button on the settings page (or press **F1**): a help page that
  describes each setting, with links at the top to each section and the
  preview at the bottom.
* **FPP 9 support**: the Plugin Manager now offers the plugin on FPP 9 as
  well as FPP 10 (tested on FPP 9: settings page, commands, Show on matrix,
  Start/Stop, Run live with the globe, uninstall).
* **Release Notes** link in FPP's Plugin Manager for updates (the commits
  since the installed version).
* **Built-in route**: Santa's route is now part of the plugin (`route.json`,
  about 580 cities built from public-domain Natural Earth data by
  `tools/make_route.py`) instead of Google's Santa Tracker route, so nothing
  is downloaded for it and Christmas Eve works without internet. Every
  country, and every US state, Canadian province and Australian state, gets
  at least one stop (Anchorage, Whitehorse, Nuuk, Reykjavik, Fiji ...). The
  old saved Google route is removed on update.
* **Run live from this moment** (under the preview): starts the running
  display as if it were the chosen preview moment, for testing, until
  **Stop**, without changing the FPP's clock. The start button shows
  **Running (simulated: ...)** while it runs. The **Santa Tracker - Start**
  command has a matching optional **Simulate time** option (default **Real
  time**).
* **Globe** map view: a round globe that turns to follow Santa on Christmas
  Eve and slowly spins while he is at the North Pole, with the night side
  shaded from the sun's real position. It switches to the zoomed-in map when
  Santa is near your town. New settings **Globe night-side brightness** and
  **Globe ocean**.
* **World map shape** setting. **Fill the panel** (the new default) stops the
  world map at 60°S and fills the panel's height, so Antarctica and the
  empty Southern Ocean no longer take up the bottom third of tall panels.
  **True proportions** keeps the previous map.
* **Text beside the zoomed-in map** setting. **Small** (the new default)
  uses a tiny 3x5-pixel font for the "NOW IN" / "FLYING TO" panel while the
  map is zoomed in, so the map gets wider and long lines ("FLYING TO:",
  "HE'S HERE!", "DELIVERED") are wrapped instead of cut off. **Normal**
  keeps the previous panel.
* **ETA shows** setting. **Time until Santa reaches my town** (the new
  default) shows "DEST IN 6H 48M" on the panel, counting down all night to
  his visit to your town, and "he reaches Your Town in 6 hours 48 min" in
  the scrolling text. After his visit it goes back to the time to his next
  stop. **Time to his next stop** keeps the previous "ETA n MIN".
* After Santa's visit to your town, the distance line says "JUST LEFT"
  instead of "CLOSE BY!" while he is still within 50 miles, and the
  scrolling text says "he just left Your Town" instead of "he's almost
  here!".
* `DOCUMENTATION.md`: a full reference for the settings, commands, map,
  files and troubleshooting, and this change log

### Changed

* `pluginInfo.json` declares an empty `dependencies` block (no extra
  packages, Python modules, scripts or plugins needed), ready for FPP 11,
  which will require it.
* Passes fpp-data's plugin checks with no findings: the install script stops
  on an unexpected error (`set -e`, with the steps that may fail harmlessly
  guarded), the command scripts' shared helper has a `#!/bin/bash` line, and
  the privacy note about the map download is shorter.
* Documentation: troubleshooting for a display that hardly seems to move and
  for a wrong FPP clock; the README mentions the globe, the built-in route,
  **Run live from this moment** and **Update every**.
* Globe: Santa now flies across the face of the globe, and the globe only
  turns (and tilts north or south) when he is about a third of the way to
  the edge, instead of keeping him pinned in the middle, so you can see him
  move. Stops far south (South Georgia, the Falklands) are no longer on the
  bottom edge. The globe is redrawn much less often, so updates are quicker.
* **Update every (seconds)** moved from the Display section to under the
  preview, next to **Run live from this moment**.
* The preview's **Seconds** box is now labelled **Show for (seconds)**: how
  long **Show on matrix** plays before it turns the matrix off.
* **Show on matrix**, **Show Once** and the map part of **Map, then
  scrolling text** redraw every **Update every** seconds while they show,
  instead of holding one still picture, so Santa moves and the countdown
  ticks (a preview of "Approaching my town" plays through to "HE'S HERE!").
  The matrix only reloads when the picture has changed.
* New plugin icon: Santa waving from his sleigh, on a see-through
  background (Plugin Manager and the top of the settings page).
* `pluginInfo.json`'s `iconURL` points at this fork's `icon.png`.
* **Map view** has two choices: **Zoom in when Santa is near** (world map)
  and **Globe (zoom in when Santa is near)**. **Always the whole world** and
  **Always zoomed in** were removed; a saved setting using either now zooms
  in when Santa is near.
* The bottom lines of the "NOW IN" / "FLYING TO" panel are wrapped instead
  of cut off at the right edge, and on short panels whole lines are left
  out (gifts first) instead of running off the bottom.
* A word that only just fits a line no longer has its comma split onto the
  next line ("WISCONSIN" / "USA" instead of "WISCONSIN" / ", USA").

## 2026-10-13

### Added

* Settings page under **Content Setup > Santa Tracker**, using FPP's own
  look (light and dark themes, phone width), with a live preview of the
  panel and **Show on matrix**
* FPP Commands: **Santa Tracker - Start**, **Stop**, **Show Once** and
  **Preview**
* Location check: the page names the nearest stop on Santa's route, so you
  can confirm your coordinates
* Zoomed-in map centred on your town, with lakes and state and country
  borders. By default the map zooms in while Santa is in your area.
  New settings: **Map view**, **Zoomed-in area**, borders on/off and
  **Borders** color.
* Santa shown as a small sleigh and reindeer facing the way he is flying,
  with a dark outline so it stands out over land. **Show Santa as: A dot**
  keeps the old marker.
* Status badge shows previews and Show Once, not just Running and Stopped
* Plugin details for FPP's Plugin Manager: privacy information, supported
  platforms, icon, repository links and the GPL v2 license

### Changed

* World map uses a Mercator projection, so shapes look like familiar maps
  instead of stretched ones
* More detailed coastline (Natural Earth 1:50m), with the Great Lakes and
  other lakes cut out
* Settings are saved with FPP's own settings functions and checked before
  saving
* **Start Display** button: blue to start, green while running. It waits up
  to 30 seconds for the display to report in instead of giving up after 4,
  and **Stop** turns it blue at once.
* Updating restarts a running display on the new code, asks for an FPPD
  restart when the commands changed, fixes the data folder's ownership and
  removes files older versions left behind
* Uninstalling also removes the plugin log, and asks for an FPPD restart so
  the plugin's commands stop being listed

### Fixed

* The overlay model was left switched on after the display stopped or the
  plugin was uninstalled, blacking out the matrix for sequences. It is now
  put back the way it was before the plugin first drew on it.
* **Start**, **Show Once** and **Preview** did nothing: the command scripts
  ran with `sh`, but FPP's helper scripts need `bash`
* **Start Display** never showed Running: the page couldn't always see the
  display's process. The display now leaves a status note that it refreshes
  every 10 seconds, and the page reads that.
* Status checks crashed in FPP's API because every request was marked as
  JSON, even with no body, so the page always saw "not running"
* **Stop** no longer waits on processes that have already exited
* API replies are never taken from the browser's cache

## Before 2026-10-13

* First version, uploaded as a zip file

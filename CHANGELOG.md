# Change log

All notable changes to the Santa Tracker plugin. Newest first. Part of the
[documentation](DOCUMENTATION.md).

When you change the plugin, add a line under **Unreleased** in the same
change: what a user will notice, in plain words. Move the lines under a dated
heading when the change is released.

## Unreleased

### Added

* `DOCUMENTATION.md`: a full reference for the settings, commands, map,
  files and troubleshooting, and this change log

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

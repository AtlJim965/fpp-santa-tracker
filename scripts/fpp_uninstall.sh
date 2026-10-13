#!/bin/bash
# Santa Tracker - uninstall. Runs as root; safe to run twice.
# Undoes everything the plugin created outside its own folder.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" >/dev/null 2>&1
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
# stop the display (worst case 8 seconds), then make sure nothing is left running
MEDIADIR="$MEDIADIR" SANTA_QUIET=1 timeout 8 /usr/bin/python3 "$PLUGINDIR/santa_tracker.py" --stop >/dev/null 2>&1 || true
pkill -f "$PLUGINDIR/santa_tracker.py" 2>/dev/null || true
rm -f "$MEDIADIR"/images/santa_map_*.png
rm -rf "$MEDIADIR/plugindata/fpp-santa-tracker"
# Settings in config/plugin.fpp-santa-tracker are kept so a reinstall
# remembers them.
exit 0

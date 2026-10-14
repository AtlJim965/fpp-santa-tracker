#!/bin/bash
# Santa Tracker - uninstall. Runs as root; safe to run twice. FPP deletes the
# plugin folder itself afterwards; this undoes everything the plugin made or
# changed outside it (keep in step with fpp_install.sh and santa_tracker.py):
#   - the running display (and any Show Once / Preview)
#   - the overlay model's on/off state (put back as it was before the plugin
#     first drew on it), and what the plugin drew on it
#   - images/santa_map_*.png         map frames
#   - plugindata/fpp-santa-tracker/  map cache, status notes,
#                                    saved model state, commands checksum
#   - logs/plugin-fpp-santa-tracker.log (and rotated copies)
#   - the plugin's commands, which fppd keeps listing until it restarts
# Kept on purpose: config/plugin.fpp-santa-tracker (your settings), so a
# reinstall remembers them. Delete that file by hand to remove them too.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" >/dev/null 2>&1
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
LOGDIR="${LOGDIR:-$MEDIADIR/logs}"

# stop the display, clear the model and put its state back (worst case 8
# seconds), then make sure nothing is left running
MEDIADIR="$MEDIADIR" LOGDIR="$LOGDIR" SANTA_QUIET=1 \
    timeout 8 /usr/bin/python3 "$PLUGINDIR/santa_tracker.py" --stop >/dev/null 2>&1 || true
pkill -f "$PLUGINDIR/santa_tracker.py" 2>/dev/null || true

rm -f "$MEDIADIR"/images/santa_map_*.png
rm -rf "$MEDIADIR/plugindata/fpp-santa-tracker"
rm -f "$LOGDIR"/plugin-fpp-santa-tracker.log*

# fppd registered our commands from commands/descriptions.json when it
# started, and only a restart drops them (FPP's plugin unload skips plugins
# without callbacks). Flag the restart if they're still listed.
if curl -s -m 5 http://127.0.0.1/api/commands 2>/dev/null | grep -qF '"Santa Tracker - '; then
    if type setSetting >/dev/null 2>&1; then
        setSetting restartFlag 1
    fi
fi
exit 0

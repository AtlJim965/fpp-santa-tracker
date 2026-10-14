#!/bin/bash
set -e
# Santa Tracker - install, and every update (FPP runs this after pulling new
# code, since there is no fpp_upgrade.sh). Runs as root; safe to run again.
# Everything it creates outside the plugin folder is removed again by
# scripts/fpp_uninstall.sh - keep the two in step.
# set -e stops on the first unexpected failure; steps that may fail without
# harm (fppd not running, a chown on an odd file system) end in `|| true`.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" || true
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
LOGDIR="${LOGDIR:-$MEDIADIR/logs}"
OWNER="$(stat -c '%U:%G' "$MEDIADIR" 2>/dev/null || echo fpp:fpp)"
DATADIR="$MEDIADIR/plugindata/fpp-santa-tracker"

# our data folder (route and map cache, status notes), owned by the media
# folder's owner so the settings page (web user) can update it too
mkdir -p "$DATADIR"
chown -R "$OWNER" "$DATADIR" 2>/dev/null || true
# files from older versions of the plugin
rm -f "$DATADIR"/land_*x*.json "$DATADIR"/resume_*.json "$DATADIR"/*.tmp "$DATADIR"/santa_route.json
chmod 755 "$PLUGINDIR/santa_tracker.py" "$PLUGINDIR"/commands/*.sh "$PLUGINDIR"/scripts/*.sh 2>/dev/null || true

# This plugin has commands/descriptions.json and no callbacks script, so fppd
# only reads its commands when it starts (FPP's plugin load/unload skips
# plugins without callbacks). Ask for an FPPD restart when fppd doesn't list
# all of them, or when an update changed them.
NAMES="$(/usr/bin/python3 -c 'import json,sys; print("\n".join(c["name"] for c in json.load(open(sys.argv[1]))))' \
    "$PLUGINDIR/commands/descriptions.json" 2>/dev/null)" || true
LISTED="$(curl -s -m 5 http://127.0.0.1/api/commands 2>/dev/null)" || true
NEED_RESTART=0
if [ -n "$LISTED" ]; then
    while IFS= read -r n; do
        if [ -n "$n" ] && ! printf '%s' "$LISTED" | grep -qF "\"$n\""; then
            NEED_RESTART=1
        fi
    done <<< "$NAMES"
fi
SUMFILE="$DATADIR/.commands_sha256"
SUM="$(sha256sum "$PLUGINDIR/commands/descriptions.json" 2>/dev/null | cut -d' ' -f1)" || true
if [ -f "$SUMFILE" ] && [ -n "$SUM" ] && [ "$(cat "$SUMFILE")" != "$SUM" ]; then
    NEED_RESTART=1
fi
if [ -n "$SUM" ]; then
    echo "$SUM" > "$SUMFILE" || true
    chown "$OWNER" "$SUMFILE" 2>/dev/null || true
fi
if [ "$NEED_RESTART" = 1 ] && type setSetting >/dev/null 2>&1; then
    setSetting restartFlag 1 || true
fi

# After an update a running display is still the old code. Start the new
# code; it takes over from the old copy without blanking the matrix.
if pgrep -f "$PLUGINDIR/santa_tracker.py --loop" >/dev/null 2>&1; then
    echo "Restarting the running Santa Tracker display with the updated code"
    MEDIADIR="$MEDIADIR" LOGDIR="$LOGDIR" SANTA_QUIET=1 setsid /usr/bin/python3 \
        "$PLUGINDIR/santa_tracker.py" --loop </dev/null >/dev/null \
        2>>"$LOGDIR/plugin-fpp-santa-tracker.log" &
fi
exit 0

#!/bin/bash
# Santa Tracker - install / reinstall. Runs as root; safe to run again.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common"
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
OWNER="$(stat -c '%U:%G' "$MEDIADIR" 2>/dev/null || echo fpp:fpp)"

# our data folder (route and map outline cache)
mkdir -p "$MEDIADIR/plugindata/fpp-santa-tracker"
chown "$OWNER" "$MEDIADIR/plugindata/fpp-santa-tracker" 2>/dev/null || true
chmod 755 "$PLUGINDIR/santa_tracker.py" "$PLUGINDIR"/commands/*.sh "$PLUGINDIR"/scripts/*.sh 2>/dev/null || true

# This plugin has commands/descriptions.json and no callbacks script, so fppd
# only picks up its commands when it starts. Ask for a restart only if the
# running fppd doesn't already know them.
if ! curl -s -m 5 http://127.0.0.1/api/commands 2>/dev/null | grep -q '"Santa Tracker - Start"'; then
    if type setSetting >/dev/null 2>&1; then
        setSetting restartFlag 1
    fi
fi
exit 0

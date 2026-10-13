#!/bin/bash
# Before fppd stops: stop the display (it was started by a command or by
# postStart). Worst case 8 seconds; returns as soon as the display has exited.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" >/dev/null 2>&1
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
MEDIADIR="$MEDIADIR" LOGDIR="${LOGDIR:-$MEDIADIR/logs}" SANTA_QUIET=1 \
    timeout 8 /usr/bin/python3 "$PLUGINDIR/santa_tracker.py" --stop >/dev/null 2>&1 || true
exit 0

#!/bin/bash
# After fppd starts: start the display if "Start automatically" is on.
# Returns immediately; the engine runs in the background and waits for
# FPP's overlay models to be ready on its own.
PLUGINDIR="$(cd "$(dirname "$0")/.." && pwd)"
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" >/dev/null 2>&1
fi
MEDIADIR="${MEDIADIR:-/home/fpp/media}"
LOGDIR="${LOGDIR:-$MEDIADIR/logs}"
CFG="$MEDIADIR/config/plugin.fpp-santa-tracker"
if grep -Eq '^AutoStart *= *"?1"? *$' "$CFG" 2>/dev/null; then
    MEDIADIR="$MEDIADIR" LOGDIR="$LOGDIR" SANTA_QUIET=1 setsid /usr/bin/python3 \
        "$PLUGINDIR/santa_tracker.py" --loop </dev/null >/dev/null \
        2>>"$LOGDIR/plugin-fpp-santa-tracker.log" &
fi
exit 0

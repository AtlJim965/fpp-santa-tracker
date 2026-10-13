#!/bin/bash
# Santa Tracker - Preview [Moment] [Seconds]
. "${SCRIPTDIR:-$(dirname "$0")}/santa_env.sh"
MOMENT="${1:-Santa in my town}"
SECS=$(secs_opt "$2")
# the engine only accepts known moment names/keys or a date-time
run_bg --once --at "$MOMENT" --seconds "$SECS"
exit 0

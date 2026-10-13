#!/bin/bash
# Santa Tracker - Show Once [Display] [Seconds]
. "${SCRIPTDIR:-$(dirname "$0")}/santa_env.sh"
MODE=$(mode_opt "$1")
SECS=$(secs_opt "$2")
run_bg --once $MODE --seconds "$SECS"
exit 0

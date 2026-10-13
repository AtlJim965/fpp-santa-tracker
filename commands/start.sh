#!/bin/sh
# Santa Tracker - Start [Display]
. "${SCRIPTDIR:-$(dirname "$0")}/santa_env.sh"
MODE=$(mode_opt "$1")
run_bg --loop $MODE
exit 0

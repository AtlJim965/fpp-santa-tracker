#!/bin/bash
# Santa Tracker - Start [Display] [Simulate time]
. "${SCRIPTDIR:-$(dirname "$0")}/santa_env.sh"
MODE=$(mode_opt "$1")
case "$2" in
    ''|"Real time"|"Right now") run_bg --loop $MODE ;;
    *) run_bg --loop $MODE --at "$2" ;;      # testing: run as if it were that moment
esac
exit 0

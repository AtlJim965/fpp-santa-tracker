#!/bin/bash
# Santa Tracker - Stop
. "${SCRIPTDIR:-$(dirname "$0")}/santa_env.sh"
run_bg --stop
exit 0

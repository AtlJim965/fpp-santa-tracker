# Shared setup for the Santa Tracker command scripts (sourced, not run).
# fppd runs these with only MEDIADIR, FPPDIR and SCRIPTDIR set (no PATH) and
# waits for them, so every script starts the engine in the background and
# returns at once.
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
CMDDIR="${SCRIPTDIR:-$(dirname "$0")}"
PLUGINDIR="$(cd "$CMDDIR/.." && pwd)"
export MEDIADIR="${MEDIADIR:-/home/fpp/media}"
# FPP's own script helpers define LOGDIR (and more); fall back if missing.
if [ -f "${FPPDIR:-/opt/fpp}/scripts/common" ]; then
    . "${FPPDIR:-/opt/fpp}/scripts/common" >/dev/null 2>&1
fi
export LOGDIR="${LOGDIR:-$MEDIADIR/logs}"
LOGFILE="$LOGDIR/plugin-fpp-santa-tracker.log"
ENGINE="$PLUGINDIR/santa_tracker.py"
export SANTA_QUIET=1

# "Map" / "Text" / "Map then text" -> engine option (anything else: saved setting)
mode_opt() {
    case "$1" in
        Map) echo "--mode=map" ;;
        Text) echo "--mode=text" ;;
        "Map then text") echo "--mode=both" ;;
        *) echo "" ;;
    esac
}

# whole seconds 3-600, otherwise 20
secs_opt() {
    case "$1" in
        ''|*[!0-9]*) echo 20 ;;
        *) if [ "$1" -lt 3 ]; then echo 3; elif [ "$1" -gt 600 ]; then echo 600; else echo "$1"; fi ;;
    esac
}

# run the engine detached from fppd
run_bg() {
    setsid python3 "$ENGINE" "$@" </dev/null >/dev/null 2>>"$LOGFILE" &
}

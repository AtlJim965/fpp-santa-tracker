# Santa Tracker FPP plugin - notes for whoever changes it

## On every update: recheck install against uninstall

FPP runs `scripts/fpp_install.sh` on install AND after every update (there is
no `fpp_upgrade.sh`), and `scripts/fpp_uninstall.sh` before it deletes the
plugin folder. Whenever a change makes the plugin create, write or change
anything outside its own folder, update both scripts in the same change:

1. List what the code writes: `grep -n "open(\|os.replace\|IMAGES_DIR\|LOG_FILE\|DATA_DIR\|/state\|setSetting\|WriteSettingToFile" santa_tracker.py santa_common.php scripts/*.sh commands/*.sh`
2. Every item must be in the comment at the top of `fpp_uninstall.sh` and
   removed (or put back) by it. Today that is:
   - running display copies (`--stop`, then `pkill`)
   - overlay model state (saved in `plugindata/.../model_state.json`, put back
     by `restore_model_state()` on stop)
   - `images/santa_map_*.png`
   - `plugindata/fpp-santa-tracker/` (route, map cache, `run_*.json` status
     notes, `model_state.json`, `.commands_sha256`)
   - `logs/plugin-fpp-santa-tracker.log*`
   - fppd's registered commands (restartFlag; FPP's unload skips plugins
     without a callbacks script)
   - kept on purpose: `config/plugin.fpp-santa-tracker` (settings)
3. `fpp_install.sh` must stay safe to re-run, delete files that older
   versions left in plugindata, `chown -R` plugindata to the media owner, flag
   an FPPD restart when `commands/descriptions.json` changed, and restart a
   running `--loop` so it runs the new code.
4. Test the whole cycle on a stand-in FPP: snapshot the media folder, install,
   start, preview, stop, update (re-run fpp_install.sh, also with a changed
   descriptions.json), uninstall while running, delete the plugin folder, and
   diff the snapshot. Nothing may be left except the settings file; the model
   must be back in its original state and no copy may still be running.
5. Update the "Files it writes and what it changes" section of README.md and
   the `privacy` block in pluginInfo.json if any of this changed.

## On every change: update the docs

Add a line to `CHANGELOG.md` under **Unreleased** describing what a user will
notice. If a setting, command, file or behaviour changed, update
`DOCUMENTATION.md` too (and README.md where it covers the same thing).

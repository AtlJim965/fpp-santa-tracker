<?php
// Santa Tracker settings page (Content Setup > Santa Tracker).
// Uses FPP's Bootstrap 5.3 styling, so it follows FPP's light/dark theme.
require_once(__DIR__ . '/santa_common.php');

$fppsantatrackerBoot = array(
    'info' => fppsantatracker_get_settings(),
    'zones' => class_exists('DateTimeZone') ? DateTimeZone::listIdentifiers() : array(),
);
?>
<style>
/* layout-only tweaks, scoped to this page; colors come from FPP's theme */
#fppsantatracker .fst-preview { line-height: 0; }
#fppsantatracker .fst-preview canvas { width: 100%; height: auto; image-rendering: pixelated; border-radius: .375rem; }
#fppsantatracker .fst-swatch { width: 1.25rem; height: 1.25rem; border-radius: .25rem; display: inline-block; }
#fppsantatracker .fst-q { width: 1.1rem; height: 1.1rem; padding: 0; border-radius: 50%; font-size: .7rem; font-weight: 700;
  line-height: 1; vertical-align: .2em; border: 1px solid var(--bs-secondary-color); background: transparent;
  color: var(--bs-secondary-color); cursor: help; }
#fppsantatracker .fst-q:hover, #fppsantatracker .fst-q:focus { color: var(--bs-body-bg); background: var(--bs-secondary-color); }
#fppsantatracker .fst-savebar { position: sticky; bottom: .5rem; z-index: 5; }
@media (min-width: 1200px) { #fppsantatracker .fst-aside { position: sticky; top: .75rem; } }
</style>

<div id="fppsantatracker" class="container-fluid px-0">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h2 class="h3 mb-0 me-2">Santa Tracker</h2>
    <div class="ms-auto d-flex gap-2">
      <button type="button" class="btn btn-outline-secondary" id="sHelp"
              title="What each setting does (or press F1)">Help</button>
      <button type="button" class="btn btn-primary" id="sStart"
              title="Runs until you press Stop (same as the 'Santa Tracker - Start' command)">Start Display</button>
      <button type="button" class="btn btn-danger" id="sStop">Stop</button>
    </div>
  </div>
  <div id="sAlert"></div>

  <div class="row g-3">
    <!-- ===== Preview (beside the settings on wide screens, first on phones) ===== -->
    <div class="col-12 col-xl-6 order-xl-2">
      <div class="fst-aside">
        <div class="card">
          <div class="card-body">
            <h3 class="h5 card-title">Preview <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="A picture of your panel that updates as you change settings. Pick a moment to see another day or time, then try it on the real matrix." aria-label="About Preview">?</button></h3>
            <p class="small text-body-secondary mb-2">Updates as you change settings. Nothing is sent to the matrix until you press <b>Show on matrix</b> or <b>Run live from this moment</b>.</p>
            <div class="fst-preview bg-black rounded p-2"><canvas id="sCanvas" width="960" height="320"></canvas></div>
            <div class="row g-2 align-items-end mt-1">
              <div class="col-12 col-md">
                <label class="form-label mb-1" for="sMoment">Preview moment</label>
                <select class="form-select" id="sMoment"><option value="now">Right now</option></select>
              </div>
              <div class="col-6 col-md-3">
                <label class="form-label mb-1" for="sShowSecs" title="Show on matrix plays this long, then turns the matrix off">Show for (seconds)</label>
                <input type="number" class="form-control" id="sShowSecs" min="3" max="600" value="20">
              </div>
              <div class="col-6 col-md-auto">
                <button type="button" class="btn btn-primary w-100" id="sShow"
                        title="Saves your settings, then plays this moment on the matrix for the seconds set, then turns the matrix off">Show on matrix</button>
              </div>
            </div>
            <div class="row g-2 align-items-end mt-1">
              <div class="col-6 col-md-3">
                <label class="form-label mb-1" for="sRefresh" title="How often the matrix redraws: Start Display, Run live, Show on matrix and Show Once">Update every (seconds)</label>
                <input type="number" class="form-control" id="sRefresh" data-key="RefreshSeconds" min="5" max="3600">
              </div>
              <div class="col-6 col-md-auto">
                <button type="button" class="btn btn-outline-secondary w-100" id="sSimStart"
                        title="For testing: starts the running display as if it were this moment. It keeps going until you press Stop.">Run live from this moment</button>
              </div>
              <div class="col-12 col-md small text-body-secondary">The matrix redraws every <b>Update every</b> seconds (15 is good for Christmas Eve). <b>Run live</b> is for testing: the running display, as if it were this moment, until <b>Stop</b>.</div>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="sLed" checked>
              <label class="form-check-label" for="sLed">LED look</label>
            </div>
            <div class="small mt-2" id="sMsg"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== Settings ===== -->
    <div class="col-12 col-xl-6 order-xl-1">

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Location <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="Where your town is, so Santa's distance, ETA and the zoomed-in map are right. Optionally makes your town one of his stops." aria-label="About Location">?</button></h3>
        <div class="row g-2">
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sCity">City name</label>
            <input type="text" class="form-control" id="sCity" data-key="CityName" maxlength="40" placeholder="Your Town"></div>
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sState">State</label>
            <input type="text" class="form-control" id="sState" data-key="State" maxlength="40" placeholder="State"></div>
          <div class="col-6"><label class="form-label mb-1" for="sLat">Latitude</label>
            <input type="text" inputmode="decimal" class="form-control" id="sLat" data-key="Latitude" placeholder="Latitude"></div>
          <div class="col-6"><label class="form-label mb-1" for="sLng">Longitude</label>
            <input type="text" inputmode="decimal" class="form-control" id="sLng" data-key="Longitude" placeholder="Longitude"></div>
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sTz">Time zone</label>
            <select class="form-select" data-key="TimeZone" id="sTz"></select></div>
          <div class="col-12 col-sm-6 d-flex align-items-end">
            <button type="button" class="btn btn-outline-secondary w-100" id="sUseFpp">Use FPP's location &amp; time zone</button></div>
        </div>
        <div class="small mt-2" id="sLocNote"></div>
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" id="sHome" data-key="AddHomeStop">
          <label class="form-check-label" for="sHome">Make my town one of Santa's stops</label>
        </div>
        <div class="row g-2 mt-1" id="sHomeStop">
          <div class="col-6"><label class="form-label mb-1" for="sAppr">Minutes "Flying to" shows</label>
            <input type="number" class="form-control" id="sAppr" data-key="HomeApproachMin" min="1" max="30"></div>
          <div class="col-6"><label class="form-label mb-1" for="sStay">Minutes "Now in" shows</label>
            <input type="number" class="form-control" id="sStay" data-key="HomeStayMin" min="1" max="30"></div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Map <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="How the map looks: flat world map or globe, how Santa is drawn, how far it zooms in near your town, and the map colors." aria-label="About Map">?</button></h3>
        <div class="row g-2 mb-2">
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sMarker">Show Santa as</label>
            <select class="form-select" data-key="SantaMarker" id="sMarker">
              <option value="sleigh">Sleigh and reindeer</option>
              <option value="dot">A dot</option>
            </select></div>
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sView">Map view</label>
            <select class="form-select" data-key="MapView" id="sView">
              <option value="auto">Zoom in when Santa is near</option>
              <option value="globe">Globe (zoom in when Santa is near)</option>
            </select></div>
          <div class="col-12 col-sm-6" id="sZoomWrap"><label class="form-label mb-1" for="sZoom">Zoomed-in area</label>
            <select class="form-select" data-key="LocalZoom" id="sZoom">
              <option value="wide">Wide (about 4,500 mi)</option>
              <option value="medium">Medium (about 2,500 mi)</option>
              <option value="close">Close (about 1,200 mi)</option>
            </select></div>
          <div class="col-12 col-sm-6" id="sShapeWrap"><label class="form-label mb-1" for="sShape">World map shape</label>
            <select class="form-select" data-key="WorldShape" id="sShape">
              <option value="fill">Fill the panel (leave out Antarctica)</option>
              <option value="true">True proportions</option>
            </select></div>
          <div class="col-12 col-sm-6" id="sGlobeWrap"><label class="form-label mb-1" for="sNight">Globe night-side brightness (%)</label>
            <input type="number" class="form-control" id="sNight" data-key="GlobeNight" min="0" max="100"></div>
          <div class="col-12 col-sm-6" id="sZTextWrap"><label class="form-label mb-1" for="sZText">Text beside the zoomed-in map</label>
            <select class="form-select" data-key="ZoomedText" id="sZText">
              <option value="small">Small (bigger map)</option>
              <option value="normal">Normal</option>
            </select></div>
        </div>
        <div class="row g-2">
          <div class="col-6 col-sm-4" data-color="LandColor" data-label="Land"></div>
          <div class="col-6 col-sm-4" data-color="OceanColor" data-label="Ocean"></div>
          <div class="col-6 col-sm-4" id="sGlobeOceanWrap" data-color="GlobeOceanColor" data-label="Globe ocean"></div>
          <div class="col-6 col-sm-4" data-color="HomeColor" data-label="My location"></div>
          <div class="col-6 col-sm-4" data-color="SantaColor" data-label="Santa's sleigh"></div>
          <div class="col-6 col-sm-4" data-color="TrailColor" data-label="Santa's trail"></div>
          <div class="col-6 col-sm-4" id="sBorderWrap" data-color="BorderColor" data-label="Borders"></div>
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="sTrail" data-key="ShowTrail">
          <label class="form-check-label" for="sTrail">Show Santa's trail (stops he has visited)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="sBorders" data-key="ShowBorders">
          <label class="form-check-label" for="sBorders">Show state and country borders on the zoomed-in map</label>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Right-hand text colors <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="Colors of the info panel beside the map (NOW IN, city, ETA, distance, gifts, countdown and greetings)." aria-label="About Right-hand text colors">?</button></h3>
        <div class="row g-2">
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sPanelMode">Color style</label>
            <select class="form-select" data-key="PanelColorMode" id="sPanelMode">
              <option value="lines">Each line its own color</option>
              <option value="single">All one color</option>
              <option value="letter">Every letter a different color</option>
              <option value="word">Every word a different color</option>
              <option value="line">Every line a different color</option>
            </select></div>
          <div class="col-12 col-sm-6" id="sPaletteWrap"><label class="form-label mb-1" for="sPalette">Colors to cycle through</label>
            <select class="form-select" data-key="PanelPalette" id="sPalette"></select>
            <div class="d-flex gap-1 mt-1" id="sSwatches"></div></div>
          <div class="col-6 col-sm-4" id="sSingleWrap" data-color="PanelSingleColor" data-label="Text color"></div>
        </div>
        <div class="row g-2 mt-1" id="sCustomWrap">
          <div class="col-6 col-sm-3"><label class="form-label mb-1" for="sCustomCount">How many colors</label>
            <select class="form-select" id="sCustomCount"><option>2</option><option>3</option><option>4</option><option>5</option></select></div>
          <div class="col-12 col-sm-9 d-flex align-items-end gap-2 flex-wrap" id="sCustomPickers"></div>
        </div>
        <div class="row g-2 mt-1" id="sLineColors">
          <div class="col-6 col-sm-4" data-color="HeadingColor" data-label="&ldquo;NOW IN:&rdquo; / &ldquo;FLYING TO:&rdquo;"></div>
          <div class="col-6 col-sm-4" data-color="CityColor" data-label="City name"></div>
          <div class="col-6 col-sm-4" data-color="RegionColor" data-label="State / country"></div>
          <div class="col-6 col-sm-4" data-color="EtaColor" data-label="&ldquo;ETA 3 MIN&rdquo;"></div>
          <div class="col-6 col-sm-4" data-color="DistanceColor" data-label="Distance / &ldquo;CLOSE BY!&rdquo; / &ldquo;JUST LEFT&rdquo;"></div>
          <div class="col-6 col-sm-4" data-color="GiftsColor" data-label="Gifts delivered"></div>
          <div class="col-6 col-sm-4" data-color="CountdownTextColor" data-label="Countdown words"></div>
          <div class="col-6 col-sm-4" data-color="CountdownNumberColor" data-label="Countdown number"></div>
          <div class="col-6 col-sm-4" data-color="Greeting1Color" data-label="&ldquo;HE'S HERE!&rdquo; / &ldquo;MERRY&rdquo;"></div>
          <div class="col-6 col-sm-4" data-color="Greeting2Color" data-label="&ldquo;HO HO HO!&rdquo; / &ldquo;CHRISTMAS!&rdquo;"></div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Wording <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="What the panel says: the countdown message, what the ETA counts down to, and whether distance and gifts are shown." aria-label="About Wording">?</button></h3>
        <label class="form-label mb-1" for="sPhrase">Countdown message</label>
        <div class="input-group flex-wrap">
          <span class="input-group-text">Santa</span>
          <input type="text" class="form-control" style="min-width: 12rem" id="sPhrase" data-key="CountdownPhrase" maxlength="60" placeholder="begins his journey">
          <span class="input-group-text">in <span id="sDaysWord" class="mx-1">&hellip;</span> days</span>
        </div>
        <div class="mt-3"><label class="form-label mb-1" for="sEta">ETA shows</label>
          <select class="form-select" data-key="EtaMode" id="sEta">
            <option value="home">Time until Santa reaches my town</option>
            <option value="next">Time to his next stop</option>
          </select></div>
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" id="sDist" data-key="ShowDistance">
          <label class="form-check-label" for="sDist">Show distance from my town</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="sGifts" data-key="ShowGifts">
          <label class="form-check-label" for="sGifts">Show gifts delivered</label>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Display <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="Which overlay model (matrix) to draw on, whether to show the map, scrolling text or both, and starting automatically with FPP." aria-label="About Display">?</button></h3>
        <div class="row g-2">
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sModel">Overlay model</label>
            <select class="form-select" data-key="ModelName" id="sModel"><option value="">(first model)</option></select></div>
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sMode">Show</label>
            <select class="form-select" data-key="DisplayMode" id="sMode">
              <option value="map">Map with info panel</option>
              <option value="text">Scrolling text</option>
              <option value="both">Map, then scrolling text</option>
            </select></div>
          <div class="col-6"><label class="form-label mb-1" for="sMapSecs">Map seconds</label>
            <input type="number" class="form-control" id="sMapSecs" data-key="MapSeconds" min="3" max="3600">
            <div class="form-text">For Show Once, and Map-then-text.</div></div>
        </div>
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="sAuto" data-key="AutoStart">
          <label class="form-check-label" for="sAuto">Start the display automatically when FPP starts</label>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <h3 class="h5 card-title">Scrolling text <button type="button" class="fst-q" data-bs-toggle="tooltip" data-bs-placement="top" title="Font, size, speed and colors of the scrolling message, used when Show includes scrolling text." aria-label="About Scrolling text">?</button></h3>
        <p class="small text-body-secondary">Used when Show is set to scrolling text. FPP draws this text itself, so the preview shows the message it will scroll rather than a picture.</p>
        <div class="row g-2">
          <div class="col-12 col-sm-6"><label class="form-label mb-1" for="sFont">Font</label>
            <select class="form-select" data-key="TextFont" id="sFont"></select></div>
          <div class="col-6 col-sm-3"><label class="form-label mb-1" for="sFontSize">Font size</label>
            <input type="number" class="form-control" id="sFontSize" data-key="TextFontSize" min="4" max="100"></div>
          <div class="col-6 col-sm-3"><label class="form-label mb-1" for="sSpeed">Scroll speed</label>
            <input type="number" class="form-control" id="sSpeed" data-key="TextScrollSpeed" min="1" max="200"></div>
          <div class="col-6 col-sm-4" data-color="TextColor" data-label="Text color"></div>
          <div class="col-6 col-sm-4"><label class="form-label mb-1" for="sTextMode">Color effect</label>
            <select class="form-select" data-key="TextColorMode" id="sTextMode">
              <option>Single</option><option>Per Letter</option><option>Per Word</option><option>Per Line</option>
              <option>Random Letter</option><option>Random Word</option><option>Gradient Horizontal</option>
              <option>Gradient Vertical</option><option>Gradient Diagonal</option><option>Sparkle</option><option>Chase</option>
            </select></div>
          <div class="col-12 col-sm-4"><label class="form-label mb-1" for="sTextPal">Color effect palette</label>
            <select class="form-select" data-key="TextPalette" id="sTextPal">
              <option>Christmas</option><option>Rainbow</option><option>Winter</option><option>Fire</option>
              <option>Ocean</option><option>Forest</option><option>Patriotic</option><option>Pastel</option><option>Party</option>
            </select></div>
        </div>
      </div></div>

      <div class="card mb-3"><div class="card-body">
        <details>
          <summary class="fw-semibold">Advanced</summary>
          <div class="row g-2 mt-1">
            <div class="col-6"><label class="form-label mb-1" for="sMapW">Map width in pixels</label>
              <input type="number" class="form-control" id="sMapW" data-key="MapWidth" min="0" max="4096">
              <div class="form-text">0 = automatic.</div></div>
            <div class="col-6"><label class="form-label mb-1" for="sMargin">Right-edge margin</label>
              <input type="number" class="form-control" id="sMargin" data-key="PanelRightMargin" min="0" max="40">
              <div class="form-text">Pixels kept blank.</div></div>
          </div>
          <p class="small text-body-secondary mt-3 mb-0">FPP Commands for playlists and schedules:
            <b>Santa Tracker - Start</b>, <b>Santa Tracker - Stop</b>, <b>Santa Tracker - Show Once</b>,
            <b>Santa Tracker - Preview</b>. Log: <code>logs/plugin-fpp-santa-tracker.log</code>.</p>
        </details>
      </div></div>
    </div>
  </div>

  <div class="fst-savebar d-flex flex-wrap align-items-center gap-3 p-2 border rounded bg-body shadow-sm">
    <button type="button" class="btn btn-success" id="sSave">Save settings</button>
    <span class="small" id="sDirty"></span>
  </div>
</div>

<script>
(function () {
  var BOOT = <?php echo json_encode($fppsantatrackerBoot); ?>;
  var API = '/api/plugin/fpp-santa-tracker/';
  var info = BOOT.info || {};
  var root = document.getElementById('fppsantatracker');
  var $ = function (sel) { return root.querySelector(sel); };
  var $$ = function (sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); };
  var saved = {};          // last saved values
  var lastPreview = null;  // last preview response

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // FPP's own pop-up messages (jGrowl); an alert on the page if it's missing
  function toast(text, ok) {
    if (!text) return;
    if (window.jQuery && jQuery.jGrowl) {
      jQuery.jGrowl(text, { themeState: ok ? 'success' : 'danger', life: ok ? 4000 : 10000 });
      return;
    }
    var box = $('#sAlert');
    box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') +
      ' alert-dismissible py-2" role="alert">' + escapeHtml(text) +
      '<button type="button" class="btn-close" aria-label="Close"></button></div>';
    box.querySelector('.btn-close').addEventListener('click', function () { box.innerHTML = ''; });
    if (ok) setTimeout(function () { box.innerHTML = ''; }, 4000);
  }

  function api(method, path, body) {
    // cache: 'no-store' plus a unique query string, so the browser never
    // answers from a saved copy (status would otherwise stick on its first reply)
    // Only say "JSON" when there is a body: FPP's API (limonade) replaces the
    // request's parameters with the decoded body for any request marked
    // application/json, so a GET marked that way crashes FPP before it
    // reaches the plugin ("array_key_exists(): ... null given").
    var opts = { method: method, cache: 'no-store' };
    if (body !== undefined) {
      opts.headers = { 'Content-Type': 'application/json' };
      opts.body = JSON.stringify(body);
    }
    var url = API + path + (method === 'GET' ? (path.indexOf('?') < 0 ? '?' : '&') + '_=' + Date.now() : '');
    return fetch(url, opts).then(function (r) {
      return r.text().then(function (t) {
        var parsed;
        try { parsed = JSON.parse(t); } catch (e) { parsed = undefined; }
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && 'ok' in parsed) return parsed;
        var snip = String(t || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
        if (parsed !== undefined) {
          return { ok: false, error: 'FPP answered instead of the plugin (HTTP ' + r.status + '): ' + snip };
        }
        return { ok: false, error: 'Unexpected reply from FPP (HTTP ' + r.status + ')' + (snip ? ': ' + snip : '') };
      });
    }).catch(function (e) { return { ok: false, error: String(e) }; });
  }

  if (!info.ok) {
    toast(info.error || 'Could not load the Santa Tracker settings.', false);
    info = { settings: {}, defaults: {}, palettes: {}, commandMoments: {}, fpp: {} };
  }

  // ---------- color pickers ----------
  $$('[data-color]').forEach(function (w) {
    var key = w.getAttribute('data-color'), id = 'sc_' + key;
    w.innerHTML = '<label class="form-label mb-1 small" for="' + id + '">' + w.getAttribute('data-label') + '</label>' +
      '<div class="d-flex align-items-center gap-2"><input type="color" class="form-control form-control-color" id="' + id +
      '" data-key="' + key + '"><code class="small"></code></div>';
  });
  function syncCodes() {
    $$('input[type=color][data-key]').forEach(function (c) {
      c.parentNode.querySelector('code').textContent = c.value.toUpperCase();
    });
  }

  // ---------- time zones ----------
  (function () {
    var sel = $('#sTz');
    var common = ['America/New_York', 'America/Chicago', 'America/Denver', 'America/Phoenix',
                  'America/Los_Angeles', 'America/Anchorage', 'Pacific/Honolulu'];
    var g1 = document.createElement('optgroup'); g1.label = 'United States';
    common.forEach(function (z) { g1.appendChild(new Option(z.replace('_', ' '), z)); });
    sel.appendChild(g1);
    var g2 = document.createElement('optgroup'); g2.label = 'All time zones';
    (BOOT.zones || []).forEach(function (z) { g2.appendChild(new Option(z.replace(/_/g, ' '), z)); });
    sel.appendChild(g2);
  })();

  // ---------- palettes ----------
  var palettes = info.palettes || {};
  (function () {
    var sel = $('#sPalette');
    Object.keys(palettes).forEach(function (n) { sel.appendChild(new Option(n, n)); });
    sel.appendChild(new Option('Custom', 'Custom'));
  })();
  function customColors() {
    var n = parseInt($('#sCustomCount').value, 10);
    return $$('#sCustomPickers input').slice(0, n).map(function (i) { return i.value.toUpperCase(); });
  }
  function buildCustom(list) {
    var box = $('#sCustomPickers');
    box.innerHTML = '';
    var defaults = ['#FF0000', '#00C83C', '#FFFFFF', '#FFAA00', '#0080FF'];
    for (var i = 0; i < 5; i++) {
      var inp = document.createElement('input');
      inp.type = 'color';
      inp.className = 'form-control form-control-color';
      inp.value = list[i] || defaults[i];
      inp.title = 'Color ' + (i + 1);
      inp.setAttribute('aria-label', 'Color ' + (i + 1));
      inp.addEventListener('input', changed);
      box.appendChild(inp);
    }
    $('#sCustomCount').value = String(Math.min(5, Math.max(2, list.length || 3)));
    showCustomCount();
  }
  function showCustomCount() {
    var n = parseInt($('#sCustomCount').value, 10);
    $$('#sCustomPickers input').forEach(function (inp, i) { inp.classList.toggle('d-none', i >= n); });
  }
  function drawSwatches() {
    var name = $('#sPalette').value;
    var cols = name === 'Custom' ? customColors() : (palettes[name] || []);
    $('#sSwatches').innerHTML = cols.map(function (c) {
      return /^#[0-9A-Fa-f]{6}$/.test(c) ? '<span class="fst-swatch border" style="background-color:' + c + '"></span>' : '';
    }).join('');
  }

  // ---------- read / write the form ----------
  function setForm(s) {
    $$('[data-key]').forEach(function (el) {
      var k = el.getAttribute('data-key');
      var v = s[k] !== undefined ? String(s[k]) : '';
      if (el.type === 'checkbox') el.checked = (v === '1' || v === 'true');
      else if (el.type === 'color') el.value = /^#[0-9a-fA-F]{6}$/.test(v) ? v : '#FFFFFF';
      else if (el.tagName === 'SELECT') {
        if (v && !Array.prototype.some.call(el.options, function (o) { return o.value === v; })) {
          el.appendChild(new Option(v, v));
        }
        el.value = v;
      } else el.value = v;
    });
    buildCustom(String(s.PanelCustomColors || '').split(',').filter(function (c) { return /^#/.test(c.trim()); }));
    syncCodes();
    refreshVisibility();
  }
  function getForm() {
    var out = {};
    $$('[data-key]').forEach(function (el) {
      var k = el.getAttribute('data-key');
      if (el.type === 'checkbox') out[k] = el.checked ? '1' : '0';
      else if (el.type === 'color') out[k] = el.value.toUpperCase();
      else out[k] = el.value.trim();
    });
    out.PanelCustomColors = customColors().join(',');
    return out;
  }
  function isDirty() {
    var now = getForm();
    return Object.keys(now).some(function (k) { return String(saved[k] || '') !== String(now[k]); });
  }
  function refreshVisibility() {
    var mode = $('#sPanelMode').value;
    var cycling = (mode === 'letter' || mode === 'word' || mode === 'line');
    $('#sPaletteWrap').classList.toggle('d-none', !cycling);
    $('#sCustomWrap').classList.toggle('d-none', !(cycling && $('#sPalette').value === 'Custom'));
    $('#sSingleWrap').classList.toggle('d-none', mode !== 'single');
    $('#sLineColors').classList.toggle('d-none', mode !== 'lines');
    $('#sHomeStop').classList.toggle('d-none', !$('#sHome').checked);
    $('#sShapeWrap').classList.toggle('d-none', $('#sView').value === 'globe');
    $('#sGlobeWrap').classList.toggle('d-none', $('#sView').value !== 'globe');
    $('#sGlobeOceanWrap').classList.toggle('d-none', $('#sView').value !== 'globe');
    $('#sBorderWrap').classList.toggle('d-none', !$('#sBorders').checked);
    drawSwatches();
  }

  // ---------- preview ----------
  var previewBusy = false, previewAgain = false, previewTimer = null;
  function schedulePreview() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(runPreview, 500);
  }
  function runPreview() {
    if (previewBusy) { previewAgain = true; return; }
    previewBusy = true;
    $('#sMsg').textContent = 'Drawing preview…';
    api('POST', 'santa-preview', { settings: getForm(), at: $('#sMoment').value }).then(function (j) {
      previewBusy = false;
      if (!j.ok) {
        $('#sMsg').innerHTML = '<span class="text-danger">' + escapeHtml(j.error || 'Preview failed.') + '</span>';
      } else {
        lastPreview = j;
        fillMoments(j.moments || []);
        checkLocation(j.nearest);
        var dm = /in (\d+) days?!$/.exec(j.text || '');
        if (dm && $('#sMoment').value === 'now') $('#sDaysWord').textContent = dm[1];
        drawPng(j);
        var mode = $('#sMode').value;
        $('#sMsg').innerHTML = '<b>' + escapeHtml(j.when) + '</b> &mdash; ' +
          (mode === 'map' ? 'Scrolling-text version: ' : 'Scrolling text: ') +
          '<i>' + escapeHtml(j.text) + '</i>';
      }
      if (previewAgain) { previewAgain = false; runPreview(); }
    });
  }
  function fillMoments(list) {
    var sel = $('#sMoment'), cur = sel.value || 'now';
    sel.innerHTML = '';
    list.forEach(function (m) { sel.appendChild(new Option(m.label + ' — ' + m.when, m.key)); });
    sel.value = cur;
    if (!sel.value && list.length) sel.value = list[0].key;
  }
  // draw the panel picture as LEDs (the picture itself is the matrix content)
  function drawPng(j) {
    var img = new Image();
    img.onload = function () {
      var w = img.width, h = img.height;
      var tmp = document.createElement('canvas');
      tmp.width = w; tmp.height = h;
      var tctx = tmp.getContext('2d');
      tctx.drawImage(img, 0, 0);
      var px = tctx.getImageData(0, 0, w, h).data;
      var cv = $('#sCanvas');
      var scale = Math.max(2, Math.floor(1100 / w));
      cv.width = w * scale; cv.height = h * scale;
      var ctx = cv.getContext('2d');
      ctx.clearRect(0, 0, cv.width, cv.height);
      var led = $('#sLed').checked;
      for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
          var i = (y * w + x) * 4, r = px[i], g = px[i + 1], b = px[i + 2];
          var lit = (r || g || b);
          if (led) {
            ctx.fillStyle = lit ? 'rgb(' + r + ',' + g + ',' + b + ')' : 'rgba(255,255,255,0.07)';
            ctx.beginPath();
            ctx.arc(x * scale + scale / 2, y * scale + scale / 2, scale * 0.42, 0, Math.PI * 2);
            ctx.fill();
          } else if (lit) {
            ctx.fillStyle = 'rgb(' + r + ',' + g + ',' + b + ')';
            ctx.fillRect(x * scale, y * scale, scale, scale);
          }
        }
      }
    };
    img.src = 'data:image/png;base64,' + j.png;
  }

  // ---------- save / actions ----------
  function setNote(text, kind) {
    var note = $('#sDirty');
    note.className = 'small ' + ({ ok: 'text-success', err: 'text-danger', warn: 'text-warning-emphasis' }[kind] || '');
    note.textContent = text;
  }
  function markDirty() { setNote(isDirty() ? 'You have unsaved changes.' : '', 'warn'); }
  function save() {
    var vals = getForm();
    var btn = $('#sSave');
    btn.disabled = true;
    btn.textContent = 'Saving…';
    setNote('', '');
    return api('POST', 'santa-save', vals).then(function (j) {
      btn.disabled = false;
      btn.textContent = 'Save settings';
      if (j.ok) {
        saved = vals;
        setNote('✔ Settings saved.', 'ok');
        setTimeout(function () { if ($('#sDirty').classList.contains('text-success')) markDirty(); }, 4000);
      } else {
        setNote('Not saved: ' + (j.error || 'unknown problem'), 'err');
        toast('Settings were not saved: ' + (j.error || 'unknown problem'), false);
      }
      return j.ok;
    });
  }
  function action(name, args) {
    return api('POST', 'santa-run', { action: name, args: args || [] }).then(function (j) {
      if (!j.ok) toast(j.error || 'That didn\'t work.', false);
      setTimeout(pollStatus, 1500);
      setTimeout(pollStatus, 4000);
      return j;
    });
  }
  function momentCommandName(key) {
    var map = info.commandMoments || {};
    for (var label in map) { if (map[label] === key) return label; }
    return 'Right now';
  }
  var pollSoon = null;
  var startingUntil = 0, lastStatus = null;     // after Start: keep "Starting..." until it reports in
  function pollStatus() {
    api('GET', 'santa-status').then(function (j) {
      lastStatus = j;
      var on = !!(j && j.running), showing = !!(j && j.showing);
      var sb = $('#sStart');
      if (on) startingUntil = 0;
      if (!on && Date.now() < startingUntil) {
        // the display can take several seconds to start on a Pi
        sb.className = 'btn btn-success'; sb.textContent = 'Starting\u2026';
        if (!pollSoon) pollSoon = setTimeout(function () { pollSoon = null; pollStatus(); }, 1000);
        return;
      }
      if (!on && startingUntil) {
        startingUntil = 0;
        toast('The display didn\'t report that it started. Last status reply: ' +
          (lastStatus && !lastStatus.ok ? (lastStatus.error || 'error') : JSON.stringify(lastStatus)), false);
      }
      // Start button: blue "Start Display" when off, green "Running" while on
      var sim = on && j.simulated;
      sb.className = 'btn ' + (sim ? 'btn-warning' : on ? 'btn-success' : 'btn-primary');
      sb.textContent = sim ? 'Running (simulated: ' + j.simulated + ')' : on ? 'Running' : 'Start Display';
      sb.title = on ? 'The display is running. Click to restart it with your saved settings.'
        : "Runs until you press Stop (same as the 'Santa Tracker - Start' command)";
      if (showing && !pollSoon) pollSoon = setTimeout(function () { pollSoon = null; pollStatus(); }, 3000);
    });
  }

  // read one of FPP's own settings live (falls back to what the page loaded with)
  function fppSetting(name) {
    return fetch('/api/settings/' + encodeURIComponent(name)).then(function (r) { return r.text(); }).then(function (t) {
      var v = '';
      try {
        var j = JSON.parse(t);
        if (j && typeof j === 'object') v = j.value !== undefined ? j.value : (j[name] !== undefined ? j[name] : '');
        else v = j;
      } catch (e) { v = t; }
      v = String(v === null || v === undefined ? '' : v).trim().replace(/^"|"$/g, '');
      return v || (info.fpp || {})[name] || '';
    }).catch(function () { return (info.fpp || {})[name] || ''; });
  }

  // plain-English check of the coordinates, under the Location boxes
  function checkLocation(nearest) {
    var note = $('#sLocNote');
    var lat = parseFloat($('#sLat').value), lng = parseFloat($('#sLng').value);
    var tzv = $('#sTz').value || '';
    if (isNaN(lat) || isNaN(lng)) {
      note.innerHTML = '<span class="text-warning-emphasis fw-semibold">Enter a latitude and longitude so your town shows on the map.</span>';
      return;
    }
    var warn = [];
    if (lat < -90 || lat > 90) warn.push('Latitude must be between -90 and 90.');
    if (lng < -180 || lng > 180) warn.push('Longitude must be between -180 and 180.');
    if (/^America\//.test(tzv) && lng > 0) warn.push('In the Americas the longitude should be negative (for example -87.98, not 87.98).');
    if (/^America\/(New_York|Chicago|Denver|Phoenix|Los_Angeles|Anchorage|Detroit|Boise|Indiana|Kentucky|North_Dakota|Toronto|Vancouver|Winnipeg|Edmonton|Halifax|Mexico_City|Regina)/.test(tzv) && lat < 0)
      warn.push('That latitude is south of the equator. North America is positive (for example 42.09).');
    var html = 'Your town is marked at <b>' + Math.abs(lat).toFixed(4) + '&deg; ' + (lat >= 0 ? 'N' : 'S') + ', ' +
               Math.abs(lng).toFixed(4) + '&deg; ' + (lng >= 0 ? 'E' : 'W') + '</b>.';
    if (nearest && nearest.name) html += ' Nearest stop on Santa\'s route: <b>' + escapeHtml(nearest.name) + '</b> (' + Number(nearest.miles).toLocaleString() + ' mi).';
    if (warn.length) html += '<br><span class="text-danger fw-semibold">' + escapeHtml(warn.join(' ')) + '</span>';
    note.innerHTML = html;
  }

  function changed() {
    checkLocation(lastPreview && lastPreview.nearest);
    syncCodes();
    refreshVisibility();
    markDirty();
    schedulePreview();
  }

  // ---------- wire up ----------
  root.addEventListener('input', function (e) { if (e.target.closest('[data-key]')) changed(); });
  root.addEventListener('change', function (e) { if (e.target.closest('[data-key]')) changed(); });
  $('#sCustomCount').addEventListener('change', function () { showCustomCount(); changed(); });
  $('#sMoment').addEventListener('change', runPreview);
  $('#sLed').addEventListener('change', function () { if (lastPreview) drawPng(lastPreview); });
  // "?" next to each section title: a short description on hover, focus or tap
  $$('.fst-q').forEach(function (b) {
    if (window.bootstrap && bootstrap.Tooltip) { bootstrap.Tooltip.getOrCreateInstance(b); }
  });
  $('#sSave').addEventListener('click', save);
  // FPP's help window (F1) shows help/content.php; open it on its own if
  // this FPP has no help window
  $('#sHelp').addEventListener('click', function () {
    if (typeof DisplayHelp === 'function' && typeof helpPage !== 'undefined') {
      DisplayHelp();
    } else {
      window.open('plugin.php?plugin=fpp-santa-tracker&page=help/content.php', '_blank');
    }
  });
  $('#sUseFpp').addEventListener('click', function () {
    Promise.all([fppSetting('Latitude'), fppSetting('Longitude'), fppSetting('TimeZone')]).then(function (v) {
      if (!v[0] || !v[1] || isNaN(parseFloat(v[0])) || isNaN(parseFloat(v[1]))) {
        toast('FPP doesn\'t have a location set. Set it under Settings, or type your coordinates here.', false);
        return;
      }
      $('#sLat').value = v[0];
      $('#sLng').value = v[1];
      if (v[2]) {
        var tzSel = $('#sTz');
        if (!Array.prototype.some.call(tzSel.options, function (o) { return o.value === v[2]; })) tzSel.appendChild(new Option(v[2], v[2]));
        tzSel.value = v[2];
      }
      toast('Using FPP\'s location: ' + v[0] + ', ' + v[1] + (v[2] ? ' (' + v[2] + ')' : '') + '. Check the map, then Save.', true);
      changed();
    });
  });
  $('#sStart').addEventListener('click', function () {
    (isDirty() ? save() : Promise.resolve(true)).then(function (ok) {
      if (!ok) return;
      var sb = $('#sStart');
      sb.className = 'btn btn-success'; sb.textContent = 'Starting\u2026';
      startingUntil = Date.now() + 30000;
      action('start', ['Saved setting']).then(function (j) {
        if (j.ok) toast('Display started.', true);
        else { startingUntil = 0; pollStatus(); }
      });
    });
  });
  $('#sSimStart').addEventListener('click', function () {
    var at = momentCommandName($('#sMoment').value);
    (isDirty() ? save() : Promise.resolve(true)).then(function (ok) {
      if (!ok) return;
      var sb = $('#sStart');
      sb.className = 'btn btn-success'; sb.textContent = 'Starting\u2026';
      startingUntil = Date.now() + 30000;
      action('start', ['Saved setting', at]).then(function (j) {
        if (j.ok) toast('Display running as if it were: ' + at + '. Press Stop when you are done testing.', true);
        else { startingUntil = 0; pollStatus(); }
      });
    });
  });
  $('#sStop').addEventListener('click', function () {
    startingUntil = 0;
    action('stop', []).then(function (j) {
      if (!j.ok) return;
      toast('Display stopped.', true);
      var sb = $('#sStart');
      sb.className = 'btn btn-primary'; sb.textContent = 'Start Display';
    });
  });
  $('#sShow').addEventListener('click', function () {
    var secs = String(Math.max(3, Math.min(600, parseInt($('#sShowSecs').value, 10) || 20)));
    (isDirty() ? save() : Promise.resolve(true)).then(function (ok) {
      if (ok) action('preview', [momentCommandName($('#sMoment').value), secs]).then(function (j) {
        if (j.ok) toast('Showing on the matrix for ' + secs + ' seconds.', true);
        setTimeout(pollStatus, secs * 1000 + 3000);
      });
    });
  });
  window.addEventListener('beforeunload', function (e) {
    if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
  });

  // models and fonts come from FPP itself
  fetch('/api/overlays/models').then(function (r) { return r.json(); }).then(function (list) {
    var sel = $('#sModel'), cur = saved.ModelName || '';
    (list || []).forEach(function (m) {
      if (m && m.Name && !Array.prototype.some.call(sel.options, function (o) { return o.value === m.Name; }))
        sel.appendChild(new Option(m.Name + (m.width ? '  (' + m.width + '×' + m.height + ')' : ''), m.Name));
    });
    sel.value = cur;
  }).catch(function () {});
  fetch('/api/overlays/fonts').then(function (r) { return r.json(); }).then(function (list) {
    var sel = $('#sFont'), cur = saved.TextFont || '';
    if (!Array.isArray(list)) list = Object.keys(list || {});
    list.forEach(function (f) {
      if (!Array.prototype.some.call(sel.options, function (o) { return o.value === f; })) sel.appendChild(new Option(f, f));
    });
    sel.value = cur;
  }).catch(function () {});

  setForm(info.settings || {});
  saved = getForm();
  markDirty();
  runPreview();
  pollStatus();
  setInterval(pollStatus, 10000);
})();
</script>

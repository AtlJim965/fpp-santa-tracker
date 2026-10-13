<?php
// Santa Tracker settings page (Content Setup > Santa Tracker).
include_once(__DIR__ . '/santa_common.php');

$santaInfo = santaGetSettings();
$santaZones = array();
if (class_exists('DateTimeZone')) {
    $santaZones = DateTimeZone::listIdentifiers();
}
$santaBoot = array(
    'info' => $santaInfo,
    'zones' => $santaZones,
);
?>
<style>
#santa { --santa-line: rgba(127,127,127,.28); --santa-soft: rgba(127,127,127,.08);
         --santa-red: #c62828; --santa-green: #1b7f3b; max-width: 1500px; }
#santa .s-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 0 18px; }
#santa .s-aside { order: -1; }
@media (min-width: 1100px) {
  #santa .s-layout { grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); }
  #santa .s-aside { order: 2; }
  #santa .s-aside .s-sticky { position: sticky; top: 10px; }
}
#santa * { box-sizing: border-box; }
#santa h2 { margin: 0; font-size: 1.6rem; }
#santa .s-top { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin: 4px 0 14px; }
#santa .s-top .s-spacer { flex: 1; }
#santa .s-pill { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px;
                 border-radius: 999px; font-size: .85rem; border: 1px solid var(--santa-line); }
#santa .s-dot { width: 9px; height: 9px; border-radius: 50%; background: #999; }
#santa .s-pill.on .s-dot { background: #2fbf55; box-shadow: 0 0 6px #2fbf55; }
#santa .s-card { border: 1px solid var(--santa-line); border-radius: 8px; padding: 14px 16px;
                 margin-bottom: 14px; background: var(--santa-soft); }
#santa .s-card h3 { font-size: 1.1rem; margin: 0 0 10px; }
#santa .s-card .s-help { font-size: .85rem; opacity: .75; margin: -4px 0 10px; }
#santa .s-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
                 gap: 10px 18px; }
#santa label.s-field { display: flex; flex-direction: column; gap: 4px; font-size: .9rem; margin: 0; }
#santa label.s-field > span { font-weight: 600; }
#santa label.s-field small { opacity: .7; font-weight: normal; }
#santa input[type=text], #santa input[type=number], #santa select {
    width: 100%; padding: 5px 8px; border: 1px solid var(--santa-line); border-radius: 5px;
    background: rgba(255,255,255,.9); color: #111; font-size: .95rem; }
#santa .s-color { display: flex; align-items: center; gap: 8px; }
#santa .s-color input[type=color] { width: 44px; height: 30px; padding: 0 2px; border: 1px solid var(--santa-line);
                                    border-radius: 5px; background: none; cursor: pointer; }
#santa .s-color code { font-size: .8rem; opacity: .8; }
#santa .s-check { display: flex; align-items: center; gap: 8px; font-size: .9rem; margin: 0; }
#santa .s-check input { width: 18px; height: 18px; }
#santa .s-row { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
#santa .s-btn { padding: 6px 14px; border-radius: 6px; border: 1px solid var(--santa-line);
                background: #fff; color: #111; cursor: pointer; font-size: .92rem; }
#santa .s-btn:hover { filter: brightness(.95); }
#santa .s-btn:disabled { opacity: .5; cursor: default; }
#santa .s-btn.primary { background: var(--santa-green); color: #fff; border-color: var(--santa-green); }
#santa .s-btn.danger { background: var(--santa-red); color: #fff; border-color: var(--santa-red); }
#santa .s-preview { background: #0b0b0e; border-radius: 8px; padding: 10px; }
#santa .s-preview canvas { display: block; width: 100%; height: auto; image-rendering: pixelated; }
#santa .s-previewbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: end; margin-top: 10px; }
#santa .s-previewbar label.s-field { min-width: 260px; flex: 1; }
#santa .s-msg { font-size: .9rem; margin-top: 8px; opacity: .85; min-height: 1.3em; }
#santa .s-msg b { font-weight: 600; }
#santa .s-swatches { display: flex; gap: 4px; }
#santa .s-swatches i { width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--santa-line); }
#santa .s-phrase { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 1rem; }
#santa .s-phrase input { flex: 1; min-width: 220px; }
#santa .s-hidden { display: none !important; }
#santa .s-savebar { position: sticky; bottom: 0; z-index: 5; display: flex; gap: 12px; align-items: center;
                    padding: 10px 14px; border-radius: 8px; border: 1px solid var(--santa-line);
                    background: rgba(255,255,255,.96); color: #111; box-shadow: 0 -2px 10px rgba(0,0,0,.08); }
#santa .s-savebar .s-dirty { font-size: .9rem; color: #b45309; }
#santa .s-savebar .s-dirty.ok { color: #14532d; }
#santa .s-savebar .s-dirty.err { color: #8a1c1c; }
#santa #sToast { position: fixed; top: 80px; right: 20px; z-index: 1000; max-width: 440px; }
#santa .s-toast { font-size: .95rem; padding: 10px 14px; border-radius: 6px; margin-bottom: 8px;
                  box-shadow: 0 4px 14px rgba(0,0,0,.18); cursor: pointer; }
#santa .s-toast.err { background: #fde8e8; color: #8a1c1c; border: 1px solid #f3b4b4; }
#santa .s-toast.ok { background: #e6f6ea; color: #14532d; border: 1px solid #a7dcb6; }
#santa details summary { cursor: pointer; font-weight: 600; }
#santa .s-linecolors { margin-top: 12px; }
@media (max-width: 600px) { #santa .s-grid { grid-template-columns: 1fr; } }
</style>

<div id="santa">
  <div class="s-top">
    <h2>&#127877; Santa Tracker</h2>
    <span class="s-pill" id="sStatus"><span class="s-dot"></span><span id="sStatusText">Checking&hellip;</span></span>
    <span class="s-spacer"></span>
    <button class="s-btn primary" id="sStart" title="Runs until you press Stop (same as the 'Santa Tracker - Start' command)">&#9654; Start display</button>
    <button class="s-btn danger" id="sStop">&#9632; Stop</button>
  </div>
  <div id="sToast"></div>

  <div class="s-layout">
  <div class="s-aside"><div class="s-sticky">
  <!-- ===== Preview ===== -->
  <div class="s-card">
    <h3>Preview</h3>
    <div class="s-help">Updates as you change settings. Nothing is sent to the matrix until you press <b>Show on matrix</b>.</div>
    <div class="s-preview"><canvas id="sCanvas" width="960" height="320"></canvas></div>
    <div class="s-previewbar">
      <label class="s-field"><span>Preview moment</span>
        <select id="sMoment"><option value="now">Right now</option></select>
      </label>
      <label class="s-field" style="max-width:130px"><span>Seconds</span>
        <input type="number" id="sShowSecs" min="3" max="600" value="20">
      </label>
      <button class="s-btn" id="sShow" title="Saves your settings, then shows this moment on the matrix">Show on matrix</button>
      <label class="s-check"><input type="checkbox" id="sLed" checked> LED look</label>
    </div>
    <div class="s-msg" id="sMsg"></div>
  </div>
  </div></div>

  <div class="s-main">
  <!-- ===== Location ===== -->
  <div class="s-card">
    <h3>Location</h3>
    <div class="s-grid">
      <label class="s-field"><span>City name</span>
        <input type="text" data-key="CityName" maxlength="40" placeholder="Your Town"></label>
      <label class="s-field"><span>State</span>
        <input type="text" data-key="State" maxlength="40" placeholder="State"></label>
      <label class="s-field"><span>Latitude</span>
        <input type="text" data-key="Latitude" placeholder="e.g. 33.6676"></label>
      <label class="s-field"><span>Longitude</span>
        <input type="text" data-key="Longitude" placeholder="e.g. -84.0177"></label>
      <label class="s-field"><span>Time zone</span>
        <select data-key="TimeZone" id="sTz"></select></label>
    </div>
    <div class="s-row" style="margin-top:10px">
      <button class="s-btn" id="sUseFpp">Use FPP's location &amp; time zone</button>
      <a href="https://www.latlong.net/" target="_blank" rel="noopener">Look up coordinates &#8599;</a>
    </div>
    <div class="s-row" style="margin-top:12px">
      <label class="s-check"><input type="checkbox" data-key="AddHomeStop"> Make my town one of Santa's stops</label>
    </div>
    <div class="s-grid" style="margin-top:8px" id="sHomeStop">
      <label class="s-field"><span>Minutes "Flying to" shows</span>
        <input type="number" data-key="HomeApproachMin" min="1" max="30"></label>
      <label class="s-field"><span>Minutes "Now in" shows</span>
        <input type="number" data-key="HomeStayMin" min="1" max="30"></label>
    </div>
  </div>

  <!-- ===== Map colors ===== -->
  <div class="s-card">
    <h3>Map colors</h3>
    <div class="s-grid">
      <div class="s-field-wrap" data-color="LandColor" data-label="Land"></div>
      <div class="s-field-wrap" data-color="OceanColor" data-label="Ocean"></div>
      <div class="s-field-wrap" data-color="HomeColor" data-label="My location"></div>
      <div class="s-field-wrap" data-color="SantaColor" data-label="Santa's location"></div>
      <div class="s-field-wrap" data-color="TrailColor" data-label="Santa's trail (stops visited)"></div>
    </div>
    <div class="s-row" style="margin-top:10px">
      <label class="s-check"><input type="checkbox" data-key="ShowTrail"> Show Santa's trail</label>
    </div>
  </div>

  <!-- ===== Right-hand text ===== -->
  <div class="s-card">
    <h3>Right-hand text colors</h3>
    <div class="s-grid">
      <label class="s-field"><span>Color style</span>
        <select data-key="PanelColorMode" id="sPanelMode">
          <option value="lines">Each line its own color</option>
          <option value="single">All one color</option>
          <option value="letter">Every letter a different color</option>
          <option value="word">Every word a different color</option>
          <option value="line">Every line a different color</option>
        </select></label>
      <label class="s-field" id="sPaletteWrap"><span>Colors to cycle through</span>
        <select data-key="PanelPalette" id="sPalette"></select>
        <span class="s-swatches" id="sSwatches"></span></label>
      <div class="s-field-wrap" id="sSingleWrap" data-color="PanelSingleColor" data-label="Text color"></div>
    </div>
    <div id="sCustomWrap" class="s-linecolors">
      <div class="s-row">
        <label class="s-field" style="max-width:160px"><span>How many colors</span>
          <select id="sCustomCount"><option>2</option><option>3</option><option>4</option><option>5</option></select></label>
        <span id="sCustomPickers" class="s-row"></span>
      </div>
    </div>
    <div class="s-grid s-linecolors" id="sLineColors">
      <div class="s-field-wrap" data-color="HeadingColor" data-label="&ldquo;NOW IN:&rdquo; / &ldquo;FLYING TO:&rdquo;"></div>
      <div class="s-field-wrap" data-color="CityColor" data-label="City name"></div>
      <div class="s-field-wrap" data-color="RegionColor" data-label="State / country"></div>
      <div class="s-field-wrap" data-color="EtaColor" data-label="&ldquo;ETA 3 MIN&rdquo;"></div>
      <div class="s-field-wrap" data-color="DistanceColor" data-label="Distance / &ldquo;CLOSE BY!&rdquo;"></div>
      <div class="s-field-wrap" data-color="GiftsColor" data-label="Gifts delivered"></div>
      <div class="s-field-wrap" data-color="CountdownTextColor" data-label="Countdown words"></div>
      <div class="s-field-wrap" data-color="CountdownNumberColor" data-label="Countdown number"></div>
      <div class="s-field-wrap" data-color="Greeting1Color" data-label="&ldquo;HE'S HERE!&rdquo; / &ldquo;MERRY&rdquo;"></div>
      <div class="s-field-wrap" data-color="Greeting2Color" data-label="&ldquo;HO HO HO!&rdquo; / &ldquo;CHRISTMAS!&rdquo;"></div>
    </div>
  </div>

  <!-- ===== Wording ===== -->
  <div class="s-card">
    <h3>Wording</h3>
    <label class="s-field"><span>Countdown message</span></label>
    <div class="s-phrase">
      <b>Santa</b>
      <input type="text" data-key="CountdownPhrase" maxlength="60" placeholder="begins his journey">
      <b>in <span id="sDaysWord">&hellip;</span> days</b>
    </div>
    <div class="s-row" style="margin-top:12px; gap:20px">
      <label class="s-check"><input type="checkbox" data-key="ShowDistance"> Show distance from my town</label>
      <label class="s-check"><input type="checkbox" data-key="ShowGifts"> Show gifts delivered</label>
    </div>
  </div>

  <!-- ===== Display ===== -->
  <div class="s-card">
    <h3>Display</h3>
    <div class="s-grid">
      <label class="s-field"><span>Overlay model</span>
        <select data-key="ModelName" id="sModel"><option value="">(first model)</option></select></label>
      <label class="s-field"><span>Show</span>
        <select data-key="DisplayMode" id="sMode">
          <option value="map">Map with info panel</option>
          <option value="text">Scrolling text</option>
          <option value="both">Map, then scrolling text</option>
        </select></label>
      <label class="s-field"><span>Map seconds <small>(Show Once, and Map-then-text)</small></span>
        <input type="number" data-key="MapSeconds" min="3" max="3600"></label>
      <label class="s-field"><span>Update every (seconds) <small>(while running)</small></span>
        <input type="number" data-key="RefreshSeconds" min="5" max="3600"></label>
    </div>
    <div class="s-row" style="margin-top:12px">
      <label class="s-check"><input type="checkbox" data-key="AutoStart"> Start the display automatically when FPP starts</label>
    </div>
  </div>

  <!-- ===== Scrolling text ===== -->
  <div class="s-card" id="sTextCard">
    <h3>Scrolling text</h3>
    <div class="s-help">Used when Show is set to scrolling text. FPP draws this text itself, so it can't be previewed above; the message it will scroll is shown under the preview.</div>
    <div class="s-grid">
      <label class="s-field"><span>Font</span><select data-key="TextFont" id="sFont"></select></label>
      <label class="s-field"><span>Font size</span><input type="number" data-key="TextFontSize" min="4" max="100"></label>
      <label class="s-field"><span>Scroll speed</span><input type="number" data-key="TextScrollSpeed" min="1" max="200"></label>
      <div class="s-field-wrap" data-color="TextColor" data-label="Text color"></div>
      <label class="s-field"><span>Color effect</span>
        <select data-key="TextColorMode">
          <option>Single</option><option>Per Letter</option><option>Per Word</option><option>Per Line</option>
          <option>Random Letter</option><option>Random Word</option><option>Gradient Horizontal</option>
          <option>Gradient Vertical</option><option>Gradient Diagonal</option><option>Sparkle</option><option>Chase</option>
        </select></label>
      <label class="s-field"><span>Color effect palette</span>
        <select data-key="TextPalette">
          <option>Christmas</option><option>Rainbow</option><option>Winter</option><option>Fire</option>
          <option>Ocean</option><option>Forest</option><option>Patriotic</option><option>Pastel</option><option>Party</option>
        </select></label>
    </div>
  </div>

  <!-- ===== Advanced ===== -->
  <div class="s-card">
    <details>
      <summary>Advanced</summary>
      <div class="s-grid" style="margin-top:10px">
        <label class="s-field"><span>Map width in pixels <small>(0 = automatic)</small></span>
          <input type="number" data-key="MapWidth" min="0" max="4096"></label>
        <label class="s-field"><span>Right-edge margin in pixels</span>
          <input type="number" data-key="PanelRightMargin" min="0" max="40"></label>
      </div>
      <p class="s-help" style="margin-top:10px">FPP Commands for playlists and schedules:
        <b>Santa Tracker - Start</b>, <b>Santa Tracker - Stop</b>, <b>Santa Tracker - Show Once</b>,
        <b>Santa Tracker - Preview</b>. Log: <code>logs/plugin-fpp-santa-tracker.log</code>.</p>
    </details>
  </div>

  </div><!-- s-main -->
  </div><!-- s-layout -->

  <div class="s-savebar">
    <button class="s-btn primary" id="sSave">Save settings</button>
    <span class="s-dirty" id="sDirty"></span>
  </div>
</div>

<script>
(function () {
  var BOOT = <?php echo json_encode($santaBoot); ?>;
  var API = '/api/plugin/fpp-santa-tracker/';
  var info = BOOT.info || {};
  var root = document.getElementById('santa');
  var $ = function (sel) { return root.querySelector(sel); };
  var $$ = function (sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); };
  var saved = {};          // last saved values
  var lastPreview = null;  // last preview response

  function toast(text, ok) {
    var t = $('#sToast');
    t.innerHTML = '';
    if (!text) return;
    var d = document.createElement('div');
    d.className = 's-toast ' + (ok ? 'ok' : 'err');
    d.textContent = text;
    d.title = 'Click to close';
    d.addEventListener('click', function () { if (t.contains(d)) t.removeChild(d); });
    t.appendChild(d);
    if (ok) setTimeout(function () { if (t.contains(d)) t.removeChild(d); }, 4000);
  }

  function api(method, path, body) {
    var opts = { method: method, headers: { 'Content-Type': 'application/json' } };
    if (body !== undefined) opts.body = JSON.stringify(body);
    return fetch(API + path, opts).then(function (r) {
      return r.text().then(function (t) {
        var parsed;
        try { parsed = JSON.parse(t); } catch (e) { parsed = undefined; }
        if (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && 'ok' in parsed) return parsed;
        if (parsed !== undefined) {
          return { ok: false, error: 'FPP answered instead of the plugin (HTTP ' + r.status + '): ' +
                   String(t).replace(/\s+/g, ' ').slice(0, 200) };
        }
        try { return JSON.parse(t); } catch (e) {
          var snip = String(t || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 200);
          return { ok: false, error: 'Unexpected reply from FPP (HTTP ' + r.status + ')' + (snip ? ': ' + snip : '') };
        }
      });
    }).catch(function (e) { return { ok: false, error: String(e) }; });
  }

  if (!info.ok) {
    toast(info.error || 'Could not load the Santa Tracker settings.', false);
    info = { settings: {}, defaults: {}, palettes: {}, commandMoments: {}, fpp: {} };
  }

  // ---------- build color pickers ----------
  $$('.s-field-wrap[data-color]').forEach(function (w) {
    var key = w.getAttribute('data-color');
    w.innerHTML = '<label class="s-field"><span>' + w.getAttribute('data-label') + '</span>' +
      '<span class="s-color"><input type="color" data-key="' + key + '"><code></code></span></label>';
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
    common.forEach(function (z) { var o = new Option(z.replace('_', ' '), z); g1.appendChild(o); });
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
      inp.value = list[i] || defaults[i];
      inp.title = 'Color ' + (i + 1);
      inp.style.cssText = 'width:44px;height:30px;padding:0 2px;border-radius:5px;cursor:pointer';
      inp.addEventListener('input', changed);
      box.appendChild(inp);
    }
    $('#sCustomCount').value = String(Math.min(5, Math.max(2, list.length || 3)));
    showCustomCount();
  }
  function showCustomCount() {
    var n = parseInt($('#sCustomCount').value, 10);
    $$('#sCustomPickers input').forEach(function (inp, i) { inp.style.display = i < n ? '' : 'none'; });
  }
  function drawSwatches() {
    var name = $('#sPalette').value;
    var cols = name === 'Custom' ? customColors() : (palettes[name] || []);
    $('#sSwatches').innerHTML = cols.map(function (c) { return '<i style="background:' + c + '"></i>'; }).join('');
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
    $('#sPaletteWrap').classList.toggle('s-hidden', !cycling);
    $('#sCustomWrap').classList.toggle('s-hidden', !(cycling && $('#sPalette').value === 'Custom'));
    $('#sSingleWrap').classList.toggle('s-hidden', mode !== 'single');
    $('#sLineColors').classList.toggle('s-hidden', mode !== 'lines');
    $('#sHomeStop').classList.toggle('s-hidden', !root.querySelector('[data-key=AddHomeStop]').checked);
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
        $('#sMsg').textContent = j.error || 'Preview failed.';
      } else {
        lastPreview = j;
        fillMoments(j.moments || []);
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
    list.forEach(function (m) {
      sel.appendChild(new Option(m.label + ' — ' + m.when, m.key));
    });
    sel.value = cur;
    if (!sel.value && list.length) sel.value = list[0].key;
  }
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
      ctx.fillStyle = '#060608';
      ctx.fillRect(0, 0, cv.width, cv.height);
      var led = $('#sLed').checked;
      for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
          var i = (y * w + x) * 4, r = px[i], g = px[i + 1], b = px[i + 2];
          if (led) {
            ctx.fillStyle = (r || g || b) ? 'rgb(' + r + ',' + g + ',' + b + ')' : '#16161a';
            ctx.beginPath();
            ctx.arc(x * scale + scale / 2, y * scale + scale / 2, scale * 0.42, 0, Math.PI * 2);
            ctx.fill();
          } else if (r || g || b) {
            ctx.fillStyle = 'rgb(' + r + ',' + g + ',' + b + ')';
            ctx.fillRect(x * scale, y * scale, scale, scale);
          }
        }
      }
    };
    img.src = 'data:image/png;base64,' + j.png;
  }
  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"]/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
    });
  }

  // ---------- save / actions ----------
  function save() {
    var vals = getForm();
    var btn = $('#sSave'), note = $('#sDirty');
    btn.disabled = true;
    btn.textContent = 'Saving\u2026';
    note.className = 's-dirty';
    note.textContent = '';
    return api('POST', 'santa-save', vals).then(function (j) {
      btn.disabled = false;
      btn.textContent = 'Save settings';
      if (j.ok) {
        saved = vals;
        markDirty();
        note.className = 's-dirty ok';
        note.textContent = '\u2714 Settings saved.';
        setTimeout(function () { if (note.className === 's-dirty ok') markDirty(); }, 4000);
      } else {
        note.className = 's-dirty err';
        note.textContent = 'Not saved: ' + (j.error || 'unknown problem');
        toast('Settings were not saved: ' + (j.error || 'unknown problem'), false);
      }
      return j.ok;
    });
  }
  function markDirty() {
    var note = $('#sDirty');
    note.className = 's-dirty';
    note.textContent = isDirty() ? 'You have unsaved changes.' : '';
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
  function pollStatus() {
    api('GET', 'santa-status').then(function (j) {
      var on = !!(j && j.running);
      $('#sStatus').classList.toggle('on', on);
      $('#sStatusText').textContent = on ? 'Running' : 'Stopped';
    });
  }

  function changed() {
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
  $('#sSave').addEventListener('click', save);
  $('#sUseFpp').addEventListener('click', function () {
    var f = info.fpp || {};
    if (!f.Latitude || !f.Longitude) {
      toast('FPP doesn\'t have a location set. Set it under Settings > System, or type your coordinates here.', false);
      return;
    }
    root.querySelector('[data-key=Latitude]').value = f.Latitude;
    root.querySelector('[data-key=Longitude]').value = f.Longitude;
    if (f.TimeZone) root.querySelector('[data-key=TimeZone]').value = f.TimeZone;
    changed();
  });
  $('#sStart').addEventListener('click', function () {
    (isDirty() ? save() : Promise.resolve(true)).then(function (ok) {
      if (ok) action('start', ['Saved setting']).then(function (j) { if (j.ok) toast('Display started.', true); });
    });
  });
  $('#sStop').addEventListener('click', function () {
    action('stop', []).then(function (j) { if (j.ok) toast('Display stopped.', true); });
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

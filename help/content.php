<?php
// Help for the settings page (Content Setup > Santa Tracker). FPP opens it
// in its help window when you press F1 or the help button on that page:
// plugin.php?plugin=fpp-santa-tracker&nopage=1&page=help/content.php
?>
<style>
#fppsantatrackerhelp h3 { font-size: 1.15rem; margin-top: 1.5rem; padding-top: .25rem; }
#fppsantatrackerhelp dt { margin-top: .5rem; }
#fppsantatrackerhelp dd { margin-left: 1rem; margin-bottom: .25rem; }
#fppsantatrackerhelp .fsth-nav a { display: inline-block; margin: 0 .75rem .25rem 0; }
#fppsantatrackerhelp .fsth-top { font-size: .85rem; }
</style>

<div id="fppsantatrackerhelp">
  <h2 class="h4" id="fsth-start">Santa Tracker help</h2>
  <p>The settings on this page and what they do. Nothing is saved until you
    press <b>Save settings</b> at the bottom of the page, and nothing goes to
    the matrix until you press <b>Start Display</b>, <b>Show on matrix</b> or
    <b>Run live from this moment</b>.</p>

  <nav class="fsth-nav mb-2" aria-label="Help sections">
    <a href="#fsth-buttons">Start and Stop</a>
    <a href="#fsth-location">Location</a>
    <a href="#fsth-map">Map</a>
    <a href="#fsth-colors">Right-hand text colors</a>
    <a href="#fsth-wording">Wording</a>
    <a href="#fsth-display">Display</a>
    <a href="#fsth-text">Scrolling text</a>
    <a href="#fsth-advanced">Advanced</a>
    <a href="#fsth-save">Saving</a>
    <a href="#fsth-preview">Preview</a>
  </nav>

  <h3 id="fsth-buttons">Start and Stop</h3>
  <dl>
    <dt>Start Display</dt>
    <dd>Starts the running display (the same as the <b>Santa Tracker - Start</b>
      command). It shows "Starting..." until the display reports in, which can
      take several seconds on a Pi, then turns green and shows Running. It
      keeps going until you press Stop.</dd>
    <dt>Stop</dt>
    <dd>Stops the display, clears the overlay model and puts it back the way
      it was before the plugin drew on it.</dd>
    <dt>Running</dt>
    <dd>While the display runs, the start button is green and shows Running.
      Press it to restart the display with your saved settings.</dd>
  </dl>

  <h3 id="fsth-location">Location</h3>
  <dl>
    <dt>City name and State</dt>
    <dd>Shown on the panel and in the messages. A blank city shows as "Your
      Town".</dd>
    <dt>Latitude and Longitude</dt>
    <dd>Where your town is, in decimal degrees (for example 42.088289 and
      -87.980451). West of Greenwich, which includes the Americas, the
      longitude is negative. Under the fields the page names the nearest stop
      on Santa's route, so you can check the numbers are right.</dd>
    <dt>Time zone</dt>
    <dd>Used for the countdown and the preview times.</dd>
    <dt>Use FPP's location &amp; time zone</dt>
    <dd>Copies the location and time zone from FPP's own settings
      (Settings &gt; System).</dd>
    <dt>Make my town one of Santa's stops</dt>
    <dd>Adds your town to Santa's route, just after the nearest real stop
      (on by default).</dd>
    <dt>Minutes "Flying to" shows / Minutes "Now in" shows</dt>
    <dd>How long Santa flies to your town (default 3) and how long he stays
      (default 2), 1 to 30 minutes each.</dd>
  </dl>

  <h3 id="fsth-map">Map</h3>
  <dl>
    <dt>Show Santa as</dt>
    <dd><b>Sleigh and reindeer</b> (default) facing the way he flies, or
      <b>A dot</b>. Very small maps always use the dot.</dd>
    <dt>Map view</dt>
    <dd><b>Zoom in when Santa is near</b> shows a flat world map, or
      <b>Globe</b> shows a round globe that turns to follow Santa, with the
      night side shaded. Both switch to a zoomed-in map of your area while
      Santa is near your town.</dd>
    <dt>Zoomed-in area</dt>
    <dd>How much the zoomed-in map covers: Wide (about 4,500 miles), Medium
      (about 2,500, default) or Close (about 1,200).</dd>
    <dt>World map shape</dt>
    <dd>Flat world map only. <b>Fill the panel</b> (default) leaves out
      Antarctica so the land fills the panel's height. <b>True
      proportions</b> shows the whole map.</dd>
    <dt>Globe night-side brightness (%)</dt>
    <dd>Globe only: how bright the night side is, 0 to 100 (100 means no night
      shading). Default 50.</dd>
    <dt>Text beside the zoomed-in map</dt>
    <dd><b>Small</b> (default) uses a tiny font while the map is zoomed in, so
      the map is wider. <b>Normal</b> uses the usual font.</dd>
    <dt>Colors</dt>
    <dd><b>Land</b>, <b>Ocean</b>, <b>Globe ocean</b> (globe only; the space
      around the globe is black), <b>My location</b> (the small + on your
      town), <b>Santa's sleigh</b>, <b>Santa's trail</b> and <b>Borders</b>.</dd>
    <dt>Show Santa's trail</dt>
    <dd>A dot for each stop he has visited.</dd>
    <dt>Show state and country borders on the zoomed-in map</dt>
    <dd>Draws the border lines in the <b>Borders</b> color.</dd>
  </dl>

  <h3 id="fsth-colors">Right-hand text colors</h3>
  <dl>
    <dt>Color style</dt>
    <dd>How the info panel beside the map is colored:
      <ul class="mb-0">
        <li><b>Each line its own color</b> (default): each kind of line uses
          its own color, set below.</li>
        <li><b>All one color</b>: every line uses <b>Text color</b>.</li>
        <li><b>Every letter / word / line a different color</b>: cycles through
          <b>Colors to cycle through</b>.</li>
      </ul></dd>
    <dt>Colors to cycle through</dt>
    <dd>Christmas, Red &amp; Green, Candy Cane, Rainbow, Winter, Gold &amp;
      Silver, or your own colors (choose <b>How many colors</b>, 2 to 5, and
      pick each one).</dd>
    <dt>Per-line colors</dt>
    <dd>"NOW IN:" / "FLYING TO:", city name, state / country, the ETA line,
      distance / "CLOSE BY!" / "JUST LEFT", gifts delivered, the countdown
      words and number, and the two greeting lines ("HE'S HERE!" / "HO HO
      HO!" and "MERRY" / "CHRISTMAS!").</dd>
  </dl>

  <h3 id="fsth-wording">Wording</h3>
  <dl>
    <dt>Countdown message</dt>
    <dd>The words before Christmas Eve, shown as "Santa <i>begins his
      journey</i> in 72 days".</dd>
    <dt>ETA shows</dt>
    <dd><b>Time until Santa reaches my town</b> (default) counts down all night
      to his visit ("DEST IN 6H 48M"), then shows the time to his next stop.
      <b>Time to his next stop</b> always shows "ETA n MIN".</dd>
    <dt>Show distance from my town</dt>
    <dd>How far away Santa is. Within 50 miles it says "CLOSE BY!", or
      "JUST LEFT" after his visit.</dd>
    <dt>Show gifts delivered</dt>
    <dd>The running count of gifts.</dd>
  </dl>

  <h3 id="fsth-display">Display</h3>
  <dl>
    <dt>Overlay model</dt>
    <dd>The Pixel Overlay Model (your matrix) to draw on.</dd>
    <dt>Show</dt>
    <dd><b>Map with info panel</b> (default), <b>Scrolling text</b>, or
      <b>Map, then scrolling text</b>.</dd>
    <dt>Map seconds</dt>
    <dd>How long the map shows for <b>Show Once</b>, and before the text in
      <b>Map, then scrolling text</b>. Default 20.</dd>
    <dt>Start the display automatically when FPP starts</dt>
    <dd>Starts the running display each time FPP starts (off by default).</dd>
  </dl>

  <h3 id="fsth-text">Scrolling text</h3>
  <p>Used when <b>Show</b> includes scrolling text. FPP draws this text itself,
    so the preview shows the message rather than a picture.</p>
  <dl>
    <dt>Font, Font size and Scroll speed</dt>
    <dd>Defaults: FreeSansBold, 24 and 40.</dd>
    <dt>Text color, Color effect and Color effect palette</dt>
    <dd>A single color (default red), or one of FPP's color effects (per
      letter, gradient, sparkle, chase ...) using the chosen palette.</dd>
  </dl>

  <h3 id="fsth-advanced">Advanced</h3>
  <dl>
    <dt>Map width in pixels</dt>
    <dd>0 (default) is automatic: twice the panel's height, leaving at least
      48 pixels for the info panel.</dd>
    <dt>Right-edge margin</dt>
    <dd>Pixels kept blank at the right edge of the panel, 0 to 40. Default
      2.</dd>
  </dl>
  <p>FPP Commands for playlists and schedules: <b>Santa Tracker - Start</b>,
    <b>Santa Tracker - Stop</b>, <b>Santa Tracker - Show Once</b> and
    <b>Santa Tracker - Preview</b>. The log is
    <code>logs/plugin-fpp-santa-tracker.log</code>.</p>

  <h3 id="fsth-save">Saving</h3>
  <p><b>Save settings</b> at the bottom of the page saves everything and says
    when there are unsaved changes. <b>Show on matrix</b> saves your settings
    first. A display that is already running keeps the settings it started
    with: press the green <b>Running</b> button to restart it with your saved
    settings.</p>

  <h3 id="fsth-preview">Preview</h3>
  <p>The picture of your panel. It updates as you change settings, before you
    save. Nothing is sent to the matrix until you press <b>Show on matrix</b>
    or <b>Run live from this moment</b>.</p>
  <dl>
    <dt>Preview moment</dt>
    <dd>Shows the panel at another time: right now, 30 / 14 / 7 / 3 / 1 days
      before, Christmas Eve takeoff, noon, 6 PM or 9 PM, approaching my town
      (1 minute before Santa arrives), Santa in my town, or Christmas
      morning. The same moments are in the <b>Santa Tracker - Preview</b>
      command.</dd>
    <dt>Show for (seconds)</dt>
    <dd>How long <b>Show on matrix</b> plays, 3 to 600, before it turns the
      matrix off.</dd>
    <dt>Show on matrix</dt>
    <dd>Saves your settings, then plays the chosen moment on the matrix. The
      clock keeps running from that moment, so "Approaching my town" plays
      through to "HE'S HERE!".</dd>
    <dt>Update every (seconds)</dt>
    <dd>How often the matrix redraws, 5 to 3600 (default 30): the running
      display, <b>Run live</b>, <b>Show on matrix</b> and <b>Show Once</b>. 15
      is a good choice for Christmas Eve. The matrix only reloads when the
      picture has changed.</dd>
    <dt>Run live from this moment</dt>
    <dd>For testing: starts the running display as if it were the chosen
      moment, and keeps going until you press <b>Stop</b>. The start button
      shows <b>Running (simulated: ...)</b> in yellow. FPP's clock is not
      changed. Press <b>Stop</b> when you're done, then <b>Start Display</b>
      for the real time.</dd>
    <dt>LED look</dt>
    <dd>Draws the preview as round LEDs. It only changes the picture in the
      browser.</dd>
  </dl>
  <p class="fsth-top"><a href="#fsth-start">Back to top</a></p>
</div>

<script>
// The help is shown inside FPP's help window: scroll that window to the
// section instead of changing the settings page's address.
(function () {
  var root = document.getElementById('fppsantatrackerhelp');
  if (!root) { return; }
  root.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[href^="#fsth-"]') : null;
    if (!a) { return; }
    var target = document.getElementById(a.getAttribute('href').slice(1));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
})();
</script>

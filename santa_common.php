<?php
// Shared helpers for the Santa Tracker settings page (content.php) and the
// plugin's API callbacks (api.php). Santa's position and the drawing live in
// santa_tracker.py; these functions run it, save settings and send commands.
// Every global name is prefixed with the plugin name.

if (!defined('FPPSANTATRACKER_REPO')) {
    define('FPPSANTATRACKER_REPO', 'fpp-santa-tracker');
}

// A function, not a global variable: FPP includes plugin files from inside
// its own functions, so top-level variables here aren't truly global.
function fppsantatracker_commands() {
    return array(
        'start'   => 'Santa Tracker - Start',
        'stop'    => 'Santa Tracker - Stop',
        'show'    => 'Santa Tracker - Show Once',
        'preview' => 'Santa Tracker - Preview',
    );
}

function fppsantatracker_setting($name, $default = '') {
    global $settings;
    if (isset($settings[$name]) && $settings[$name] !== '') {
        return $settings[$name];
    }
    return $default;
}

function fppsantatracker_media_dir() {
    return fppsantatracker_setting('mediaDirectory', '/home/fpp/media');
}

function fppsantatracker_log_dir() {
    return fppsantatracker_setting('logDirectory', fppsantatracker_media_dir() . '/logs');
}

function fppsantatracker_config_file() {
    return fppsantatracker_setting('configDirectory', fppsantatracker_media_dir() . '/config')
        . '/plugin.' . FPPSANTATRACKER_REPO;
}

function fppsantatracker_data_dir() {
    return fppsantatracker_media_dir() . '/plugindata/' . FPPSANTATRACKER_REPO;
}

// Run the engine and return what it prints on stdout. Every argument is
// shell-escaped.
function fppsantatracker_run($args, &$code = null) {
    $cmd = 'MEDIADIR=' . escapeshellarg(fppsantatracker_media_dir())
        . ' LOGDIR=' . escapeshellarg(fppsantatracker_log_dir())
        . ' SANTA_QUIET=1'
        . ' /usr/bin/python3 ' . escapeshellarg(__DIR__ . '/santa_tracker.py');
    foreach ($args as $a) {
        $cmd .= ' ' . escapeshellarg($a);
    }
    $out = array();
    exec($cmd . ' 2>/dev/null', $out, $code);
    return implode("\n", $out);
}

function fppsantatracker_run_json($args) {
    $j = json_decode(fppsantatracker_run($args), true);
    if (!is_array($j)) {
        return array('ok' => false,
            'error' => 'The Santa Tracker engine did not answer. Check logs/plugin-' . FPPSANTATRACKER_REPO . '.log.');
    }
    return $j;
}

// Saved settings, with FPP's own location and time zone filled in where blank.
function fppsantatracker_get_settings() {
    $j = fppsantatracker_run_json(array('--settings-json'));
    if (empty($j['ok'])) {
        return $j;
    }
    foreach (array('Latitude', 'Longitude', 'TimeZone') as $key) {
        if (!isset($j['settings'][$key]) || trim($j['settings'][$key]) == '') {
            $j['settings'][$key] = fppsantatracker_setting($key, '');
        }
    }
    $j['fpp'] = array(
        'Latitude' => fppsantatracker_setting('Latitude', ''),
        'Longitude' => fppsantatracker_setting('Longitude', ''),
        'TimeZone' => fppsantatracker_setting('TimeZone', ''),
    );
    return $j;
}

// Strip anything that could break the settings file (quotes, $, backslashes,
// line breaks, control characters).
function fppsantatracker_clean($v) {
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    $v = str_replace(array('"', '$', '\\'), array("'", '', ''), (string)$v);
    $v = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $v);
    return trim($v);
}

// Check one value before it's saved. Returns an error message or ''.
function fppsantatracker_check($key, $value, $defaults, $info) {
    $default = (string)$defaults[$key];
    $choices = array(
        'DisplayMode' => array('map', 'text', 'both'),
        'MapView' => array('auto', 'globe'),
        'LocalZoom' => array('wide', 'medium', 'close'),
        'WorldShape' => array('fill', 'true'),
        'ZoomedText' => array('small', 'normal'),
        'EtaMode' => array('home', 'next'),
        'SantaMarker' => array('sleigh', 'dot'),
        'PanelColorMode' => array('lines', 'single', 'letter', 'word', 'line'),
        'TextColorMode' => array('Single', 'Per Letter', 'Per Word', 'Per Line', 'Random Letter',
            'Random Word', 'Gradient Horizontal', 'Gradient Vertical', 'Gradient Diagonal', 'Sparkle', 'Chase'),
        'TextPalette' => array('Christmas', 'Rainbow', 'Winter', 'Fire', 'Ocean', 'Forest', 'Patriotic',
            'Pastel', 'Party', 'Halloween', 'Custom'),
    );
    $panelPalettes = array_merge(array_keys(isset($info['palettes']) ? $info['palettes'] : array()), array('Custom'));
    $bools = array('AutoStart', 'AddHomeStop', 'ShowTrail', 'ShowDistance', 'ShowGifts', 'ShowBorders');
    $maxLen = array('CityName' => 40, 'State' => 40, 'CountdownPhrase' => 60, 'ModelName' => 64, 'TextFont' => 64);

    if (isset($choices[$key]) && !in_array($value, $choices[$key], true)) {
        return "$key: unexpected value";
    }
    if ($key == 'PanelPalette' && !in_array($value, $panelPalettes, true)) {
        return "$key: unexpected value";
    }
    if (in_array($key, $bools, true) && $value !== '0' && $value !== '1') {
        return "$key must be on or off";
    }
    if ($key == 'PanelCustomColors') {
        foreach (explode(',', $value) as $c) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', trim($c))) {
                return 'Custom colors must look like #FF0000';
            }
        }
        return '';
    }
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $default) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
        return "$key must be a color like #FF0000";
    }
    if ($key == 'Latitude' && $value !== '' && (!is_numeric($value) || abs((float)$value) > 90)) {
        return 'Latitude must be a number between -90 and 90';
    }
    if ($key == 'Longitude' && $value !== '' && (!is_numeric($value) || abs((float)$value) > 180)) {
        return 'Longitude must be a number between -180 and 180';
    }
    if ($key == 'TimeZone' && $value !== '' && class_exists('DateTimeZone')
        && !in_array($value, DateTimeZone::listIdentifiers(), true)) {
        return 'Unknown time zone';
    }
    if (is_numeric($default) && !in_array($key, $bools, true) && !is_numeric($value)) {
        return "$key must be a number";
    }
    if (isset($maxLen[$key]) && strlen($value) > $maxLen[$key]) {
        return "$key is too long (" . $maxLen[$key] . ' characters max)';
    }
    return '';
}

// Read our settings file (FPP writes Name = "value", or Name = value for
// numbers).
function fppsantatracker_read_config() {
    $out = array();
    $file = fppsantatracker_config_file();
    if (!file_exists($file)) {
        return $out;
    }
    foreach ((array)@file($file, FILE_IGNORE_NEW_LINES) as $line) {
        $parts = explode('=', $line, 2);
        if (count($parts) != 2 || trim($parts[0]) == '') {
            continue;
        }
        $v = trim($parts[1]);
        if (strlen($v) >= 2 && $v[0] == '"' && substr($v, -1) == '"') {
            $v = substr($v, 1, -1);
        }
        $out[trim($parts[0])] = $v;
    }
    return $out;
}

function fppsantatracker_write_setting($key, $value) {
    if (function_exists('WriteSettingToFile')) {
        return WriteSettingToFile($key, $value, FPPSANTATRACKER_REPO) !== false;
    }
    // Fallback (FPP always provides WriteSettingToFile): same format.
    $all = fppsantatracker_read_config();
    $all[$key] = $value;
    $text = '';
    foreach ($all as $k => $v) {
        $text .= $k . ' = "' . $v . '"' . "\n";
    }
    return @file_put_contents(fppsantatracker_config_file(), $text, LOCK_EX) !== false;
}

function fppsantatracker_save_settings($values) {
    $info = fppsantatracker_run_json(array('--settings-json'));
    if (empty($info['ok'])) {
        return $info;
    }
    if (!is_array($values) || count($values) == 0) {
        return array('ok' => false, 'error' => 'No settings were received from the page.');
    }
    $defaults = $info['defaults'];
    $clean = array();
    $problems = array();
    foreach ($values as $key => $value) {
        if (!is_string($key) || !array_key_exists($key, $defaults)) {
            continue;
        }
        $v = fppsantatracker_clean($value);
        $err = fppsantatracker_check($key, $v, $defaults, $info);
        if ($err != '') {
            $problems[] = $err;
        } else {
            $clean[$key] = $v;
        }
    }
    if (count($problems)) {
        return array('ok' => false, 'error' => implode('. ', $problems) . '.');
    }
    foreach ($clean as $key => $v) {
        if (!fppsantatracker_write_setting($key, $v)) {
            return array('ok' => false,
                'error' => "Couldn't write " . fppsantatracker_config_file() . '. Check its permissions.');
        }
    }
    $check = fppsantatracker_read_config();
    foreach ($clean as $key => $v) {
        if (!isset($check[$key]) || (string)$check[$key] !== $v) {
            return array('ok' => false, 'error' => "Saved, but $key didn't read back correctly.");
        }
    }
    return array('ok' => true, 'saved' => count($clean));
}

// Render a preview picture with the given (unsaved) settings.
function fppsantatracker_preview($values, $at) {
    $info = fppsantatracker_run_json(array('--settings-json'));
    $defaults = isset($info['defaults']) ? $info['defaults'] : array();
    $clean = array();
    foreach ((array)$values as $k => $v) {
        if (is_string($k) && array_key_exists($k, $defaults)) {
            $clean[$k] = fppsantatracker_clean($v);
        }
    }
    $dir = fppsantatracker_data_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $tmp = @tempnam(is_dir($dir) ? $dir : sys_get_temp_dir(), 'preview');
    file_put_contents($tmp, json_encode($clean));
    $args = array('--preview-json', '--settings', $tmp);
    $at = fppsantatracker_clean($at);
    if ($at != '' && preg_match('/^[A-Za-z0-9 _:+\-]{1,40}$/', $at)) {
        $args[] = '--at';
        $args[] = $at;
    }
    $j = fppsantatracker_run_json($args);
    @unlink($tmp);
    return $j;
}

function fppsantatracker_status() {
    return fppsantatracker_run_json(array('--status'));
}

function fppsantatracker_http($method, $path, $body = null) {
    $opts = array('http' => array(
        'method' => $method,
        'timeout' => 15,
        'ignore_errors' => true,
    ));
    // JSON header only with a body: FPP's API treats a request marked JSON as
    // carrying its parameters in the body, and a GET marked that way crashes it
    if ($body !== null) {
        $opts['http']['header'] = "Content-Type: application/json\r\n";
        $opts['http']['content'] = json_encode($body);
    }
    $resp = @file_get_contents('http://127.0.0.1' . $path, false, stream_context_create($opts));
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $code = intval($m[1]);
    }
    return array($code, $resp);
}

// Start / stop / show / preview on the matrix, through the plugin's FPP
// Commands (the same way a playlist or schedule runs them).
function fppsantatracker_action($action, $args) {
    $commands = fppsantatracker_commands();
    if (!isset($commands[$action])) {
        return array('ok' => false,
            'error' => 'Unknown action "' . $action . '" (expected start, stop, show or preview).');
    }
    $name = $commands[$action];
    list($code, $list) = fppsantatracker_http('GET', '/api/commands');
    $known = false;
    foreach ((array)json_decode($list, true) as $c) {
        if (is_array($c) && isset($c['name']) && $c['name'] == $name) {
            $known = true;
        }
    }
    if (!$known) {
        return array('ok' => false, 'needsRestart' => true,
            'error' => 'FPP hasn\'t loaded the Santa Tracker commands yet. Click "Restart FPPD" at the top of the page, then try again.');
    }
    $strArgs = array();
    foreach ((array)$args as $a) {
        $a = fppsantatracker_clean($a);
        if (strlen($a) > 60) {
            return array('ok' => false, 'error' => 'Command option is too long.');
        }
        $strArgs[] = $a;
    }
    list($code, $resp) = fppsantatracker_http('POST', '/api/command',
        array('command' => $name, 'multisyncCommand' => false, 'multisyncHosts' => '', 'args' => $strArgs));
    if ($code >= 400 || $resp === false) {
        return array('ok' => false, 'error' => 'FPP refused the command: ' . trim((string)$resp));
    }
    return array('ok' => true, 'result' => trim((string)$resp));
}
?>

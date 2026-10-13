<?php
// Shared helpers for the Santa Tracker settings page (content.php) and the
// plugin's API (api.php). The drawing and Santa logic live in
// santa_tracker.py; these functions just run it and save settings.

define('SANTA_REPO', 'fpp-santa-tracker');

// A function, not a global variable: FPP includes plugin files from inside
// its own functions, so top-level variables here aren't truly global.
function santaCommands() {
    return array(
        'start'   => 'Santa Tracker - Start',
        'stop'    => 'Santa Tracker - Stop',
        'show'    => 'Santa Tracker - Show Once',
        'preview' => 'Santa Tracker - Preview',
    );
}

function santaMediaDir() {
    global $settings;
    if (isset($settings['mediaDirectory']) && $settings['mediaDirectory'] != '') {
        return $settings['mediaDirectory'];
    }
    return '/home/fpp/media';
}

function santaConfigFile() {
    global $settings;
    $dir = (isset($settings['configDirectory']) && $settings['configDirectory'] != '')
        ? $settings['configDirectory'] : santaMediaDir() . '/config';
    return $dir . '/plugin.' . SANTA_REPO;
}

function santaFppSetting($name, $default = '') {
    global $settings;
    if (isset($settings[$name]) && $settings[$name] !== '') {
        return $settings[$name];
    }
    if (function_exists('getSetting')) {
        $v = getSetting($name);
        if ($v !== '' && $v !== null && $v !== false) {
            return $v;
        }
    }
    return $default;
}

// Run the engine and return what it prints on stdout.
function santaRun($args, &$code = null) {
    $cmd = 'MEDIADIR=' . escapeshellarg(santaMediaDir())
        . ' /usr/bin/python3 ' . escapeshellarg(__DIR__ . '/santa_tracker.py');
    foreach ($args as $a) {
        $cmd .= ' ' . escapeshellarg($a);
    }
    $out = array();
    exec($cmd . ' 2>/dev/null', $out, $code);
    return implode("\n", $out);
}

function santaRunJson($args) {
    $j = json_decode(santaRun($args), true);
    if (!is_array($j)) {
        return array('ok' => false,
            'error' => 'The Santa Tracker engine did not answer. Check the plugin log (Status/Control > Logs).');
    }
    return $j;
}

// Saved settings, with FPP's own location and time zone filled in where blank.
function santaGetSettings() {
    $j = santaRunJson(array('--settings-json'));
    if (empty($j['ok'])) {
        return $j;
    }
    $fill = array('Latitude' => 'Latitude', 'Longitude' => 'Longitude', 'TimeZone' => 'TimeZone');
    foreach ($fill as $mine => $fpp) {
        if (!isset($j['settings'][$mine]) || trim($j['settings'][$mine]) == '') {
            $j['settings'][$mine] = santaFppSetting($fpp, '');
        }
    }
    $j['fpp'] = array(
        'Latitude' => santaFppSetting('Latitude', ''),
        'Longitude' => santaFppSetting('Longitude', ''),
        'TimeZone' => santaFppSetting('TimeZone', ''),
    );
    return $j;
}

function santaCleanValue($v) {
    if (is_bool($v)) {
        return $v ? '1' : '0';
    }
    // keep the settings file simple: no quotes, $, backslashes or line breaks
    $v = str_replace(array('"', '$', '\\', "\r", "\n"), array("'", '', '', ' ', ' '), (string)$v);
    return trim($v);
}

function santaReadConfig($file) {
    $out = array();
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

// Save all settings in one go, in FPP's plugin settings format
// (Name = "value"), to config/plugin.fpp-santa-tracker.
function santaSaveSettings($values) {
    $info = santaRunJson(array('--settings-json'));
    if (empty($info['ok'])) {
        return $info;
    }
    if (!is_array($values) || count($values) == 0) {
        return array('ok' => false, 'error' => 'No settings were received from the page.');
    }
    $file = santaConfigFile();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        return array('ok' => false, 'error' => "The folder $dir doesn't exist.");
    }
    $all = santaReadConfig($file);
    $saved = 0;
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $info['defaults'])) {
            continue;
        }
        $all[$key] = santaCleanValue($value);
        $saved++;
    }
    $text = '';
    foreach ($all as $k => $v) {
        $text .= $k . ' = "' . $v . '"' . "\n";
    }
    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $text) === false) {
        $who = function_exists('posix_geteuid') ? @posix_getpwuid(posix_geteuid()) : null;
        return array('ok' => false, 'error' => "Can't write to $dir" .
            ($who ? ' as user ' . $who['name'] : '') . '. Check the folder permissions.');
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return array('ok' => false, 'error' => "Can't replace $file. Check its permissions.");
    }
    // read it back to be sure
    $check = santaReadConfig($file);
    foreach ($values as $key => $value) {
        if (array_key_exists($key, $info['defaults']) &&
            (!isset($check[$key]) || $check[$key] !== santaCleanValue($value))) {
            return array('ok' => false, 'error' => "Saved, but $key didn't read back correctly.");
        }
    }
    return array('ok' => true, 'saved' => $saved, 'file' => $file);
}

// Render a preview picture with the given (unsaved) settings.
function santaPreview($values, $at) {
    $tmp = tempnam(sys_get_temp_dir(), 'santa');
    $clean = array();
    foreach ((array)$values as $k => $v) {
        $clean[$k] = santaCleanValue($v);
    }
    file_put_contents($tmp, json_encode($clean));
    $args = array('--preview-json', '--settings', $tmp);
    if ($at != '') {
        $args[] = '--at';
        $args[] = $at;
    }
    $j = santaRunJson($args);
    @unlink($tmp);
    return $j;
}

function santaStatus() {
    return santaRunJson(array('--status'));
}

function santaHttp($method, $path, $body = null) {
    $opts = array('http' => array(
        'method' => $method,
        'timeout' => 15,
        'ignore_errors' => true,
        'header' => "Content-Type: application/json\r\n",
    ));
    if ($body !== null) {
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
// Commands (so it runs the same way a playlist or schedule would).
function santaAction($action, $args) {
    $commands = santaCommands();
    if (!isset($commands[$action])) {
        return array('ok' => false, 'error' => 'Unknown action "' . $action . '" (expected start, stop, show or preview).');
    }
    $name = $commands[$action];
    list($code, $list) = santaHttp('GET', '/api/commands');
    $known = false;
    foreach ((array)json_decode($list, true) as $c) {
        if (is_array($c) && isset($c['name']) && $c['name'] == $name) {
            $known = true;
        }
    }
    if (!$known) {
        return array('ok' => false, 'needsRestart' => true,
            'error' => 'FPP hasn\'t loaded the Santa Tracker commands yet. Click "Restart FPPD" at the top of the page (or reboot), then try again.');
    }
    $strArgs = array();
    foreach ((array)$args as $a) {
        $strArgs[] = (string)$a;
    }
    list($code, $resp) = santaHttp('POST', '/api/command',
        array('command' => $name, 'multisyncCommand' => false, 'multisyncHosts' => '', 'args' => $strArgs));
    if ($code >= 400 || $resp === false) {
        return array('ok' => false, 'error' => 'FPP refused the command: ' . trim((string)$resp));
    }
    return array('ok' => true, 'result' => trim((string)$resp));
}
?>

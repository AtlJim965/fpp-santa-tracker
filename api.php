<?php
// Santa Tracker API registrar. Only the endpoint list and its callbacks live
// here; the shared code is loaded on demand so other FPP API calls aren't
// affected. Endpoints: /api/plugin/fpp-santa-tracker/santa-...

function getEndpointsfppsantatracker() {
    return array(
        array('method' => 'GET',  'endpoint' => 'santa-config',  'callback' => 'fppsantatrackerGetSettings'),
        array('method' => 'POST', 'endpoint' => 'santa-save',    'callback' => 'fppsantatrackerSaveSettings'),
        array('method' => 'POST', 'endpoint' => 'santa-preview', 'callback' => 'fppsantatrackerPreview'),
        array('method' => 'GET',  'endpoint' => 'santa-status',  'callback' => 'fppsantatrackerStatus'),
        array('method' => 'POST', 'endpoint' => 'santa-run',     'callback' => 'fppsantatrackerAction'),
    );
}

// Load the shared code, run $fn, and always answer with JSON.
function fppsantatrackerReply($fn) {
    try {
        require_once(__DIR__ . '/santa_common.php');
        return json(call_user_func($fn));
    } catch (Throwable $e) {
        return json(array('ok' => false, 'error' => 'Santa Tracker error: ' . $e->getMessage()));
    }
}

function fppsantatrackerBody() {
    $j = json_decode(file_get_contents('php://input'), true);
    return is_array($j) ? $j : array();
}

// GET santa-config: saved settings plus defaults
function fppsantatrackerGetSettings() {
    return fppsantatrackerReply(function () { return fppsantatracker_get_settings(); });
}

// POST santa-save   body: {"CityName": "Your Town", ...}
function fppsantatrackerSaveSettings() {
    $body = fppsantatrackerBody();
    return fppsantatrackerReply(function () use ($body) { return fppsantatracker_save_settings($body); });
}

// POST santa-preview   body: {"settings": {...}, "at": "d7"}
function fppsantatrackerPreview() {
    $b = fppsantatrackerBody();
    $values = isset($b['settings']) && is_array($b['settings']) ? $b['settings'] : array();
    $at = isset($b['at']) ? (string)$b['at'] : '';
    return fppsantatrackerReply(function () use ($values, $at) { return fppsantatracker_preview($values, $at); });
}

// GET santa-status
function fppsantatrackerStatus() {
    return fppsantatrackerReply(function () { return fppsantatracker_status(); });
}

// POST santa-run   body: {"action": "start|stop|show|preview", "args": [...]}
function fppsantatrackerAction() {
    $b = fppsantatrackerBody();
    $args = isset($b['args']) && is_array($b['args']) ? $b['args'] : array();
    $action = isset($b['action']) ? strtolower(trim((string)$b['action'])) : '';
    return fppsantatrackerReply(function () use ($action, $args) { return fppsantatracker_action($action, $args); });
}
?>

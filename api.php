<?php
// REST endpoints for the Santa Tracker settings page.
// All live under /api/plugin/fpp-santa-tracker/santa-... (names FPP's own
// /api/plugin/<name>/settings routes can't collide with).
include_once(__DIR__ . '/santa_common.php');

function getEndpointsfppsantatracker() {
    return array(
        array('method' => 'GET',  'endpoint' => 'santa-config',      'callback' => 'fppsantatrackerGetSettings'),
        array('method' => 'POST', 'endpoint' => 'santa-save',        'callback' => 'fppsantatrackerSaveSettings'),
        array('method' => 'POST', 'endpoint' => 'santa-preview',     'callback' => 'fppsantatrackerPreview'),
        array('method' => 'GET',  'endpoint' => 'santa-status',      'callback' => 'fppsantatrackerStatus'),
        array('method' => 'POST', 'endpoint' => 'santa-run',         'callback' => 'fppsantatrackerAction'),
    );
}

// Any PHP problem comes back as a readable message instead of a blank reply.
function fppsantatrackerSafe($fn) {
    try {
        return json(call_user_func($fn));
    } catch (Throwable $e) {
        return json(array('ok' => false, 'error' => 'Santa Tracker error: ' . $e->getMessage()));
    }
}

function fppsantatrackerBody() {
    $j = json_decode(file_get_contents('php://input'), true);
    return is_array($j) ? $j : array();
}

// GET /api/plugin/fpp-santa-tracker/santa-config
function fppsantatrackerGetSettings() {
    return fppsantatrackerSafe(function () { return santaGetSettings(); });
}

// POST /api/plugin/fpp-santa-tracker/santa-save   body: {"CityName": "Conyers", ...}
function fppsantatrackerSaveSettings() {
    return fppsantatrackerSafe(function () { return santaSaveSettings(fppsantatrackerBody()); });
}

// POST /api/plugin/fpp-santa-tracker/santa-preview    body: {"settings": {...}, "at": "d7"}
function fppsantatrackerPreview() {
    $b = fppsantatrackerBody();
    $values = isset($b['settings']) && is_array($b['settings']) ? $b['settings'] : array();
    $at = isset($b['at']) ? (string)$b['at'] : '';
    return fppsantatrackerSafe(function () use ($values, $at) { return santaPreview($values, $at); });
}

// GET /api/plugin/fpp-santa-tracker/santa-status
function fppsantatrackerStatus() {
    return fppsantatrackerSafe(function () { return santaStatus(); });
}

// POST /api/plugin/fpp-santa-tracker/santa-run
//   body: {"action": "start|stop|show|preview", "args": ["Saved setting", "20"]}
function fppsantatrackerAction() {
    $b = fppsantatrackerBody();
    $args = isset($b['args']) && is_array($b['args']) ? $b['args'] : array();
    $action = isset($b['action']) ? strtolower(trim((string)$b['action'])) : '';
    return fppsantatrackerSafe(function () use ($action, $args) { return santaAction($action, $args); });
}
?>

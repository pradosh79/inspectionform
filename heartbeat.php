<?php
/**
 * Called every few seconds by index.php (and other protected pages)
 * while the tab is open, to keep the session marked as active.
 * If the tab is closed, these calls stop, and the session goes stale
 * after SESSION_IDLE_TIMEOUT seconds -- see auth.php.
 */
require_once 'auth.php';

header('Content-Type: application/json');

if (is_logged_in()) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(401);
    echo json_encode(['ok' => false]);
}

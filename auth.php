<?php
/**
 * Shared session/auth helpers. Plain procedural PHP.
 *
 * Browsers don't reliably destroy "session cookies" the instant a tab
 * or window closes -- many keep running in the background or restore
 * the previous session on reopen, which silently keeps the login
 * alive far longer than intended. To make "closing the tab logs you
 * out" actually true, this uses an activity timeout instead: the page
 * pings the server every few seconds while open (see heartbeat.php).
 * If those pings stop for longer than SESSION_IDLE_TIMEOUT, the
 * session is treated as dead on the next check, regardless of
 * whether the cookie itself is still present.
 */

define('SESSION_IDLE_TIMEOUT', 20); // seconds of silence before a session is considered dead

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,        // still a session cookie as a first line of defense
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * True if a user is currently logged in AND has checked in recently
 * enough (via heartbeat.php or a normal page load) to still count as
 * an active session. Destroys stale sessions as a side effect.
 */
function is_logged_in() {
    if (empty($_SESSION['user_id'])) {
        return false;
    }
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Call at the top of any page that must not be viewable without login.
 * Redirects to login.php if there is no active (non-expired) session.
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}


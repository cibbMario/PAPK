<?php
// includes/auth.php
// Simple authentication helper used by admin/user pages.
// Assumes session_start() already called in header.

/**
 * Ensure the user is logged in. If not, redirect to login page.
 */
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Ensure the logged‑in user has the specified role (admin or user).
 * @param string $role
 */
function requireRole(string $role) {
    requireLogin();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // unauthorized – show simple message and stop execution
        http_response_code(403);
        echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>";
        exit();
    }
}

/**
 * Set a flash message that will be shown on the next page load via Bootstrap toast.
 * @param string $msg  Message text
 * @param string $type One of: 'success', 'danger', 'info', 'warning'
 */
function setFlash(string $msg, string $type = 'info') {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
?>

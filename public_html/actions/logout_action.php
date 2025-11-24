<?php
/**
 * Public wrapper for logout_action.php
 * This file lives in public_html/actions so the logout requests from the frontend
 * can reach the backend on hosts where the document root is `public_html`.
 * It simply requires the real action script located at the repository root.
 */

// Forward the request to the real action implementation
require_once __DIR__ . '/../../actions/logout_action.php';

// End of wrapper


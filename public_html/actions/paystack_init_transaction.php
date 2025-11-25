<?php
/**
 * Public wrapper for paystack_init_transaction.php
 * This file lives in public_html/actions so the AJAX requests from the frontend
 * can reach the backend on hosts where the document root is `public_html`.
 */

// Forward the request to the real action implementation
require_once __DIR__ . '/../../actions/paystack_init_transaction.php';


<?php
/**
 * Upload Directory Diagnostic Tool
 * 
 * This script checks where images are being uploaded and verifies permissions
 * Access it via: /actions/check_upload_directory.php
 */

require_once __DIR__ . '/../settings/core.php';

header('Content-Type: application/json');

// Only allow logged-in users
if (!is_logged_in()) {
    echo json_encode(['error' => 'Please log in to access diagnostics']);
    exit;
}

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'base_path' => __DIR__ . '/../uploads',
    'relative_path' => 'uploads',
    'checks' => []
];

$uploads_base = __DIR__ . '/../uploads';

// 1. Check if base uploads directory exists
$diagnostics['checks']['base_directory'] = [
    'path' => $uploads_base,
    'exists' => is_dir($uploads_base),
    'readable' => is_readable($uploads_base),
    'writable' => is_writable($uploads_base),
    'permissions' => is_dir($uploads_base) ? substr(sprintf('%o', fileperms($uploads_base)), -4) : 'N/A',
    'status' => (is_dir($uploads_base) && is_writable($uploads_base)) ? 'OK' : 'ERROR'
];

// 2. Check temp directory
$temp_dir = $uploads_base . '/temp';
$diagnostics['checks']['temp_directory'] = [
    'path' => $temp_dir,
    'relative_path' => 'uploads/temp',
    'exists' => is_dir($temp_dir),
    'readable' => is_readable($temp_dir),
    'writable' => is_writable($temp_dir),
    'permissions' => is_dir($temp_dir) ? substr(sprintf('%o', fileperms($temp_dir)), -4) : 'N/A',
    'status' => (is_dir($temp_dir) && is_writable($temp_dir)) ? 'OK' : 'ERROR'
];

// 3. Check user directories (if user is logged in)
$user_id = get_user_id();
if ($user_id) {
    $user_dir = $uploads_base . '/u' . $user_id;
    $diagnostics['checks']['user_directory'] = [
        'path' => $user_dir,
        'relative_path' => 'uploads/u' . $user_id,
        'exists' => is_dir($user_dir),
        'readable' => is_readable($user_dir),
        'writable' => is_writable($user_dir),
        'permissions' => is_dir($user_dir) ? substr(sprintf('%o', fileperms($user_dir)), -4) : 'N/A',
        'status' => (is_dir($user_dir) && is_writable($user_dir)) ? 'OK' : 'ERROR'
    ];
    
    // Check product directories for this user
    if (is_dir($user_dir)) {
        $product_dirs = glob($user_dir . '/p*');
        $diagnostics['checks']['product_directories'] = [
            'count' => count($product_dirs),
            'directories' => array_map(function($dir) {
                return [
                    'path' => $dir,
                    'name' => basename($dir),
                    'writable' => is_writable($dir),
                    'file_count' => is_dir($dir) ? count(glob($dir . '/*')) : 0
                ];
            }, $product_dirs)
        ];
    }
}

// 4. Try to create directories if they don't exist
$diagnostics['checks']['directory_creation'] = [];
if (!is_dir($uploads_base)) {
    $created = @mkdir($uploads_base, 0755, true);
    $diagnostics['checks']['directory_creation']['base'] = [
        'attempted' => true,
        'success' => $created,
        'message' => $created ? 'Base directory created successfully' : 'Failed to create base directory'
    ];
}

if (!is_dir($temp_dir)) {
    $created = @mkdir($temp_dir, 0755, true);
    $diagnostics['checks']['directory_creation']['temp'] = [
        'attempted' => true,
        'success' => $created,
        'message' => $created ? 'Temp directory created successfully' : 'Failed to create temp directory'
    ];
}

if ($user_id && !is_dir($user_dir)) {
    $created = @mkdir($user_dir, 0755, true);
    $diagnostics['checks']['directory_creation']['user'] = [
        'attempted' => true,
        'success' => $created,
        'message' => $created ? 'User directory created successfully' : 'Failed to create user directory'
    ];
}

// 5. Test file write capability
$test_file = $temp_dir . '/test_write_' . time() . '.txt';
$test_write = @file_put_contents($test_file, 'test');
if ($test_write !== false) {
    @unlink($test_file);
    $diagnostics['checks']['write_test'] = [
        'success' => true,
        'message' => 'File write test passed'
    ];
} else {
    $diagnostics['checks']['write_test'] = [
        'success' => false,
        'message' => 'File write test failed - directory may not be writable'
    ];
}

// 6. PHP upload settings
$diagnostics['checks']['php_settings'] = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'ENABLED' : 'DISABLED',
    'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'System default'
];

// 7. Overall status
$all_checks_passed = 
    $diagnostics['checks']['base_directory']['status'] === 'OK' &&
    $diagnostics['checks']['temp_directory']['status'] === 'OK' &&
    $diagnostics['checks']['write_test']['success'] === true;

$diagnostics['overall_status'] = $all_checks_passed ? 'READY' : 'NOT_READY';
$diagnostics['recommendations'] = [];

if ($diagnostics['checks']['base_directory']['status'] !== 'OK') {
    $diagnostics['recommendations'][] = 'Create the uploads directory: ' . $uploads_base;
    $diagnostics['recommendations'][] = 'Set permissions to 755 or 777: chmod 755 uploads';
}

if ($diagnostics['checks']['temp_directory']['status'] !== 'OK') {
    $diagnostics['recommendations'][] = 'Create the temp directory: ' . $temp_dir;
}

if (!$diagnostics['checks']['write_test']['success']) {
    $diagnostics['recommendations'][] = 'Check directory permissions - uploads directory must be writable by PHP';
    $diagnostics['recommendations'][] = 'On Windows: Right-click uploads folder → Properties → Security → Add write permissions';
    $diagnostics['recommendations'][] = 'On Linux: chmod 755 uploads or chmod 777 uploads';
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);


<?php
/**
 * Test Page for Server Verification
 * 
 * This page helps verify that the server setup is working correctly
 */

// Start output buffering to prevent any issues
ob_start();

// Set content type
header('Content-Type: text/html; charset=UTF-8');

// Get current time
$current_time = date('Y-m-d H:i:s');
$server_time = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'] ?? time());

// Get server information
$server_info = [
    'PHP Version' => PHP_VERSION,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
    'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
    'Server Port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
    'HTTPS' => isset($_SERVER['HTTPS']) ? 'Yes' : 'No',
    'Remote Address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];

// Check if core settings can be loaded
$core_loaded = false;
$core_error = '';
try {
    require_once __DIR__ . '/../settings/core.php';
    $core_loaded = true;
} catch (Exception $e) {
    $core_error = $e->getMessage();
}

// Check if database connection works
$db_connected = false;
$db_error = '';
if ($core_loaded) {
    try {
        $db = new Database();
        $db_connected = true;
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

// Check file permissions
$file_permissions = [
    'public_html' => is_writable(__DIR__) ? 'Writable' : 'Not Writable',
    'assets' => is_writable(__DIR__ . '/assets') ? 'Writable' : 'Not Writable',
    'settings' => is_readable(__DIR__ . '/../settings') ? 'Readable' : 'Not Readable'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Test Page</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .content {
            padding: 30px;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .status-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #28a745;
        }
        .status-card.error {
            border-left-color: #dc3545;
        }
        .status-card.warning {
            border-left-color: #ffc107;
        }
        .status-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .status-value {
            color: #666;
            font-family: monospace;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .info-table th,
        .info-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .info-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .info-table td {
            font-family: monospace;
            color: #666;
        }
        .success {
            color: #28a745;
            font-weight: 600;
        }
        .error {
            color: #dc3545;
            font-weight: 600;
        }
        .warning {
            color: #ffc107;
            font-weight: 600;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #ddd;
        }
        .test-links {
            margin: 20px 0;
            padding: 20px;
            background: #e9ecef;
            border-radius: 8px;
        }
        .test-links h3 {
            margin-top: 0;
            color: #333;
        }
        .test-links a {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .test-links a:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Server Test Page</h1>
            <p>Testing your e-commerce platform setup</p>
        </div>
        
        <div class="content">
            <div class="status-grid">
                <div class="status-card <?php echo $core_loaded ? 'success' : 'error'; ?>">
                    <div class="status-title">Core Settings</div>
                    <div class="status-value">
                        <?php if ($core_loaded): ?>
                            <span class="success">✅ Loaded Successfully</span>
                        <?php else: ?>
                            <span class="error">❌ Failed to Load</span>
                            <br><small><?php echo htmlspecialchars($core_error); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="status-card <?php echo $db_connected ? 'success' : 'error'; ?>">
                    <div class="status-title">Database Connection</div>
                    <div class="status-value">
                        <?php if ($db_connected): ?>
                            <span class="success">✅ Connected</span>
                        <?php else: ?>
                            <span class="error">❌ Connection Failed</span>
                            <br><small><?php echo htmlspecialchars($db_error); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-title">Current Time</div>
                    <div class="status-value"><?php echo $current_time; ?></div>
                </div>
                
                <div class="status-card">
                    <div class="status-title">Server Time</div>
                    <div class="status-value"><?php echo $server_time; ?></div>
                </div>
            </div>
            
            <div class="test-links">
                <h3>🧪 Test Your Application</h3>
                <p>Try these links to test different parts of your application:</p>
                <a href="index.php">🏠 Homepage</a>
                <a href="view/user/login.php">🔐 Login Page</a>
                <a href="view/user/register.php">📝 Register Page</a>
                <a href="view/admin/dashboard.php">⚙️ Admin Dashboard</a>
                <a href="assets/css/sleep.css">🎨 CSS Test</a>
                <a href="assets/js/script.js">📜 JavaScript Test</a>
            </div>
            
            <h3>📊 Server Information</h3>
            <table class="info-table">
                <?php foreach ($server_info as $key => $value): ?>
                <tr>
                    <th><?php echo htmlspecialchars($key); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <h3>📁 File Permissions</h3>
            <table class="info-table">
                <?php foreach ($file_permissions as $path => $status): ?>
                <tr>
                    <th><?php echo htmlspecialchars($path); ?></th>
                    <td class="<?php echo $status === 'Writable' || $status === 'Readable' ? 'success' : 'error'; ?>">
                        <?php echo $status; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <?php if ($core_loaded): ?>
            <h3>🔧 Application Configuration</h3>
            <table class="info-table">
                <tr>
                    <th>App Name</th>
                    <td><?php echo defined('APP_NAME') ? APP_NAME : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <th>Base URL</th>
                    <td><?php echo defined('BASE_URL') ? BASE_URL : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <th>Assets URL</th>
                    <td><?php echo defined('ASSETS_URL') ? ASSETS_URL : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <th>Root Path</th>
                    <td><?php echo defined('ROOT_PATH') ? ROOT_PATH : 'Not defined'; ?></td>
                </tr>
                <tr>
                    <th>Public Path</th>
                    <td><?php echo defined('PUBLIC_PATH') ? PUBLIC_PATH : 'Not defined'; ?></td>
                </tr>
            </table>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>Test page generated at <?php echo $current_time; ?></p>
            <p>If you see this page, your server setup is working! 🎉</p>
        </div>
    </div>
</body>
</html>

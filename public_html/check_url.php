<?php
/**
 * URL Configuration Diagnostic Tool
 * This file helps identify URL and path configuration issues
 */

// Include core settings
require_once __DIR__ . '/../settings/core.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL Configuration Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .diagnostic-box {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .diagnostic-box h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            width: 250px;
            color: #666;
        }
        .info-value {
            flex: 1;
            color: #333;
            word-break: break-all;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .recommendation {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .recommendation h3 {
            margin-top: 0;
            color: #007bff;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <h1>🔍 URL Configuration Diagnostic</h1>
    
    <div class="diagnostic-box">
        <h2>Current URL Information</h2>
        <div class="info-row">
            <div class="info-label">Current URL:</div>
            <div class="info-value"><?php echo current_url(); ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">BASE_URL:</div>
            <div class="info-value"><code><?php echo BASE_URL; ?></code></div>
        </div>
        <div class="info-row">
            <div class="info-label">ASSETS_URL:</div>
            <div class="info-value"><code><?php echo ASSETS_URL; ?></code></div>
        </div>
        <div class="info-row">
            <div class="info-label">PUBLIC_URL:</div>
            <div class="info-value"><code><?php echo PUBLIC_URL; ?></code></div>
        </div>
    </div>

    <div class="diagnostic-box">
        <h2>Server Information</h2>
        <div class="info-row">
            <div class="info-label">HTTP_HOST:</div>
            <div class="info-value"><?php echo $_SERVER['HTTP_HOST'] ?? 'Not set'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">REQUEST_URI:</div>
            <div class="info-value"><?php echo $_SERVER['REQUEST_URI'] ?? 'Not set'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">SCRIPT_NAME:</div>
            <div class="info-value"><?php echo $_SERVER['SCRIPT_NAME'] ?? 'Not set'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">DOCUMENT_ROOT:</div>
            <div class="info-value"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Not set'; ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">HTTPS:</div>
            <div class="info-value"><?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Yes' : 'No'; ?></div>
        </div>
    </div>

    <div class="diagnostic-box">
        <h2>File System Paths</h2>
        <div class="info-row">
            <div class="info-label">ROOT_PATH:</div>
            <div class="info-value"><code><?php echo ROOT_PATH; ?></code></div>
        </div>
        <div class="info-row">
            <div class="info-label">PUBLIC_PATH:</div>
            <div class="info-value"><code><?php echo PUBLIC_PATH; ?></code></div>
        </div>
        <div class="info-row">
            <div class="info-label">ASSETS_PATH:</div>
            <div class="info-value"><code><?php echo ASSETS_PATH; ?></code></div>
        </div>
        <div class="info-row">
            <div class="info-label">ASSETS_PATH exists:</div>
            <div class="info-value <?php echo is_dir(ASSETS_PATH) ? 'success' : 'error'; ?>">
                <?php echo is_dir(ASSETS_PATH) ? '✓ Yes' : '✗ No'; ?>
            </div>
        </div>
    </div>

    <div class="diagnostic-box">
        <h2>Asset File Checks</h2>
        <?php
        $test_files = [
            'css/homepage.css' => 'Homepage CSS',
            'css/header_footer.css' => 'Header/Footer CSS',
            'js/script.js' => 'Main JavaScript',
        ];
        
        foreach ($test_files as $file => $label):
            $full_path = ASSETS_PATH . '/' . $file;
            $exists = file_exists($full_path);
            $url = ASSETS_URL . '/' . $file;
        ?>
        <div class="info-row">
            <div class="info-label"><?php echo $label; ?>:</div>
            <div class="info-value">
                <span class="<?php echo $exists ? 'success' : 'error'; ?>">
                    <?php echo $exists ? '✓' : '✗'; ?>
                </span>
                File: <code><?php echo $file; ?></code>
                <?php if ($exists): ?>
                    <br>URL: <code><?php echo $url; ?></code>
                    <br>Last Modified: <?php echo date('Y-m-d H:i:s', filemtime($full_path)); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="diagnostic-box">
        <h2>URL Test Links</h2>
        <p>Click these links to test if they work:</p>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/index.php" target="_blank">Homepage (index.php)</a></li>
            <li><a href="<?php echo ASSETS_URL; ?>/css/homepage.css" target="_blank">Homepage CSS</a></li>
            <li><a href="<?php echo ASSETS_URL; ?>/js/script.js" target="_blank">Main JavaScript</a></li>
            <li><a href="<?php echo url('view/product/all_product.php'); ?>" target="_blank">All Products Page</a></li>
        </ul>
    </div>

    <div class="recommendation">
        <h3>💡 Recommendations</h3>
        <?php
        $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $public_path = PUBLIC_PATH;
        
        // Check if document root matches expected structure
        if ($doc_root && strpos($public_path, $doc_root) === 0) {
            echo '<p class="success">✓ Document root appears to be configured correctly.</p>';
        } else {
            echo '<p class="warning">⚠ Document root may not be pointing to the correct location.</p>';
        }
        
        // Check if we're accessing via the correct URL
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/public_html') !== false) {
            echo '<p class="warning">⚠ You are accessing via <code>/public_html</code> in the URL. This might be correct depending on your server configuration.</p>';
        }
        
        echo '<p><strong>Expected URL formats:</strong></p>';
        echo '<ul>';
        echo '<li>If document root is <code>C:/xampp/htdocs</code>: <code>http://localhost/ecommerce-authent/public_html/</code></li>';
        echo '<li>If document root is <code>C:/Users/nayel/Downloads/htdocs/ecommerce-authent/public_html</code>: <code>http://localhost/</code></li>';
        echo '<li>If using virtual host: <code>http://ecommerce-authent.local/</code></li>';
        echo '</ul>';
        ?>
    </div>

    <div class="recommendation">
        <h3>🔧 Troubleshooting Steps</h3>
        <ol>
            <li><strong>Clear browser cache:</strong> Press Ctrl+Shift+R (or Cmd+Shift+R on Mac) to hard refresh</li>
            <li><strong>Check the URL you're using:</strong> Make sure it matches one of the expected formats above</li>
            <li><strong>Verify file paths:</strong> Check that the ASSETS_PATH exists and contains your CSS/JS files</li>
            <li><strong>Check .htaccess:</strong> Make sure mod_rewrite is enabled in Apache</li>
            <li><strong>Check Apache error logs:</strong> Look for any errors in XAMPP's Apache error log</li>
        </ol>
    </div>

    <div class="diagnostic-box">
        <h2>Quick Fix: Test Asset URLs</h2>
        <p>If your CSS/JS files aren't loading, try accessing them directly:</p>
        <ul>
            <?php
            foreach ($test_files as $file => $label):
                $url = ASSETS_URL . '/' . $file;
            ?>
            <li><a href="<?php echo $url; ?>" target="_blank"><?php echo $label; ?> - <?php echo $url; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>



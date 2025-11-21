<?php
/**
 * Quick URL Debug - Shows what URLs are being generated
 */
require_once __DIR__ . '/../settings/core.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>URL Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .url { color: #007bff; font-weight: bold; }
        .test { margin: 10px 0; padding: 10px; background: #e7f3ff; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🔍 URL Configuration Debug</h1>
    
    <div class="box">
        <h3>Generated URLs:</h3>
        <p><strong>BASE_URL:</strong> <span class="url"><?php echo BASE_URL; ?></span></p>
        <p><strong>ASSETS_URL:</strong> <span class="url"><?php echo ASSETS_URL; ?></span></p>
        <p><strong>PUBLIC_URL:</strong> <span class="url"><?php echo PUBLIC_URL; ?></span></p>
    </div>

    <div class="box">
        <h3>Server Variables:</h3>
        <p><strong>HTTP_HOST:</strong> <?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?></p>
        <p><strong>SCRIPT_NAME:</strong> <?php echo $_SERVER['SCRIPT_NAME'] ?? 'N/A'; ?></p>
        <p><strong>REQUEST_URI:</strong> <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></p>
        <p><strong>DOCUMENT_ROOT:</strong> <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></p>
    </div>

    <div class="box">
        <h3>File System Paths:</h3>
        <p><strong>PUBLIC_PATH:</strong> <?php echo PUBLIC_PATH; ?></p>
        <p><strong>ASSETS_PATH:</strong> <?php echo ASSETS_PATH; ?></p>
        <p><strong>ASSETS_PATH exists:</strong> <?php echo is_dir(ASSETS_PATH) ? '✅ YES' : '❌ NO'; ?></p>
    </div>

    <div class="box">
        <h3>Test Asset URLs:</h3>
        <div class="test">
            <p><strong>Homepage CSS:</strong></p>
            <p>URL: <span class="url"><?php echo ASSETS_URL; ?>/css/homepage.css</span></p>
            <p>File exists: <?php echo file_exists(ASSETS_PATH . '/css/homepage.css') ? '✅ YES' : '❌ NO'; ?></p>
            <p><a href="<?php echo ASSETS_URL; ?>/css/homepage.css" target="_blank">→ Try to open this URL</a></p>
        </div>
        <div class="test">
            <p><strong>Main JavaScript:</strong></p>
            <p>URL: <span class="url"><?php echo ASSETS_URL; ?>/js/script.js</span></p>
            <p>File exists: <?php echo file_exists(ASSETS_PATH . '/js/script.js') ? '✅ YES' : '❌ NO'; ?></p>
            <p><a href="<?php echo ASSETS_URL; ?>/js/script.js" target="_blank">→ Try to open this URL</a></p>
        </div>
    </div>

    <div class="box">
        <h3>Expected vs Actual:</h3>
        <p>If you're accessing: <code>http://localhost/ecommerce-authent/public_html/index.php</code></p>
        <p>Then BASE_URL should be: <code>http://localhost/ecommerce-authent/public_html</code></p>
        <p>And ASSETS_URL should be: <code>http://localhost/ecommerce-authent/public_html/assets</code></p>
        <p><strong>Is BASE_URL correct?</strong> <?php 
            $expected = 'http://localhost/ecommerce-authent/public_html';
            $actual = BASE_URL;
            echo ($actual === $expected) ? '✅ YES' : '❌ NO - Expected: ' . $expected . ', Got: ' . $actual;
        ?></p>
    </div>

    <div class="box">
        <h3>Quick Fixes:</h3>
        <ol>
            <li><strong>Clear Browser Cache:</strong> Press <kbd>Ctrl+Shift+R</kbd> (Windows) or <kbd>Cmd+Shift+R</kbd> (Mac)</li>
            <li><strong>Check Browser Console:</strong> Press <kbd>F12</kbd> and look for 404 errors on CSS/JS files</li>
            <li><strong>Test Asset URLs:</strong> Click the links above to see if they load</li>
            <li><strong>View Page Source:</strong> Right-click → View Source and check the CSS/JS link URLs</li>
        </ol>
    </div>

    <div class="box">
        <h3>Direct Links:</h3>
        <p><a href="<?php echo BASE_URL; ?>/index.php">→ Go to Homepage</a></p>
        <p><a href="<?php echo BASE_URL; ?>/check_url.php">→ Full Diagnostic Tool</a></p>
    </div>
</body>
</html>



<?php
/**
 * Debug Login Page with AJAX
 */

require_once __DIR__ . '/../settings/core.php';

include __DIR__ . '/view/templates/header.php';
?>

<div class="auth-container login-container">
    <h2>🔍 Debug Login Page (AJAX)</h2>
    <p>This page will show exactly what happens with the AJAX login</p>
    
    <div id="debug-output" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto;"></div>
    
    <form id="debugLoginForm" method="post" action="<?php echo url('../actions/process_login.php'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" value="admin@test.com" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-input" value="admin123" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            🔍 Debug AJAX Login
        </button>
    </form>
    
    <hr>
    <p><a href="view/user/login.php">Go to Regular Login Page</a></p>
    <p><a href="test_direct_login.php">Go to Direct Login Test</a></p>
</div>

<script>
document.getElementById('debugLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const debugOutput = document.getElementById('debug-output');
    debugOutput.textContent = 'Starting login process...\n';
    
    const formData = new FormData(this);
    formData.append('ajax', '1');
    
    debugOutput.textContent += 'Form data prepared\n';
    debugOutput.textContent += 'Submitting to: ' + this.action + '\n';
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        debugOutput.textContent += 'Response received\n';
        debugOutput.textContent += 'Status: ' + response.status + '\n';
        debugOutput.textContent += 'Status Text: ' + response.statusText + '\n';
        debugOutput.textContent += 'Headers: ' + JSON.stringify([...response.headers.entries()]) + '\n';
        
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        
        return response.text();
    })
    .then(data => {
        debugOutput.textContent += 'Response body received:\n';
        debugOutput.textContent += data + '\n';
        
        try {
            const jsonData = JSON.parse(data);
            debugOutput.textContent += 'JSON parsed successfully:\n';
            debugOutput.textContent += JSON.stringify(jsonData, null, 2) + '\n';
            
            if (jsonData.success) {
                debugOutput.textContent += 'SUCCESS! Redirecting to: ' + jsonData.redirect + '\n';
                setTimeout(() => {
                    window.location.href = jsonData.redirect;
                }, 2000);
            } else {
                debugOutput.textContent += 'FAILED: ' + jsonData.message + '\n';
            }
        } catch (e) {
            debugOutput.textContent += 'Error parsing JSON: ' + e.message + '\n';
            debugOutput.textContent += 'Raw response: ' + data + '\n';
        }
    })
    .catch(error => {
        debugOutput.textContent += 'Fetch error: ' + error.message + '\n';
        debugOutput.textContent += 'Stack: ' + error.stack + '\n';
    });
});
</script>

<?php include __DIR__ . '/view/templates/footer.php'; ?>

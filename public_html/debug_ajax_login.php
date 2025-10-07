<?php
/**
 * Debug AJAX Login Page
 */

require_once __DIR__ . '/../settings/core.php';

include __DIR__ . '/view/templates/header.php';
?>

<div class="auth-container login-container">
    <h2>🔍 Debug AJAX Login</h2>
    <p>This page will show exactly what happens with AJAX login</p>
    
    <div id="debug-output" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; font-family: monospace; white-space: pre-wrap;"></div>
    
    <form id="debugLoginForm" method="post" action="<?php echo url('../actions/process_login_debug.php'); ?>">
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
    <p><a href="debug_login_page.php">Go to Debug Login Page</a></p>
</div>

<script>
document.getElementById('debugLoginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const debugOutput = document.getElementById('debug-output');
    debugOutput.textContent = 'Submitting form...\n';
    
    const formData = new FormData(this);
    formData.append('ajax', '1');
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        debugOutput.textContent += 'Response status: ' + response.status + '\n';
        debugOutput.textContent += 'Response headers: ' + JSON.stringify([...response.headers.entries()]) + '\n';
        return response.text();
    })
    .then(data => {
        debugOutput.textContent += 'Response body: ' + data + '\n';
        
        try {
            const jsonData = JSON.parse(data);
            debugOutput.textContent += 'Parsed JSON: ' + JSON.stringify(jsonData, null, 2) + '\n';
            
            if (jsonData.success) {
                debugOutput.textContent += 'SUCCESS! Redirecting...\n';
                setTimeout(() => {
                    window.location.href = jsonData.redirect;
                }, 2000);
            } else {
                debugOutput.textContent += 'FAILED: ' + jsonData.message + '\n';
            }
        } catch (e) {
            debugOutput.textContent += 'Error parsing JSON: ' + e.message + '\n';
        }
    })
    .catch(error => {
        debugOutput.textContent += 'Fetch error: ' + error.message + '\n';
    });
});
</script>

<?php include __DIR__ . '/view/templates/footer.php'; ?>

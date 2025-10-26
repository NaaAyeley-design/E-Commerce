<?php
/**
 * User Management Page
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/user_controller.php';

// Set page variables
$page_title = 'User Management';
$page_description = 'Manage user accounts and permissions for your e-commerce platform.';
$body_class = 'users-page';
$additional_css = ['users.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $target_user_id = (int)($_POST['user_id'] ?? 0);
                if ($target_user_id > 0) {
                    $result = toggle_user_status_ctr($target_user_id, $user_id);
                    if ($result === 'success') {
                        $message = "User status updated successfully!";
                    } else {
                        $error = $result;
                    }
                } else {
                    $error = "Invalid user ID.";
                }
                break;
        }
    }
}

// Get all users
$users = get_all_users_ctr($user_id);
if (is_string($users)) {
    $error = $users;
    $users = [];
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>User Management</h1>
        <p>Manage user accounts and permissions for your e-commerce platform.</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo escape_html($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo escape_html($error); ?>
        </div>
    <?php endif; ?>

    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <h3>All Users</h3>
            <p class="text-muted">Total: <?php echo count($users); ?> users</p>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h4>No Users Found</h4>
                    <p>No users are registered in the system yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['customer_id']; ?></td>
                                    <td><?php echo escape_html($user['customer_name']); ?></td>
                                    <td><?php echo escape_html($user['customer_email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['is_admin'] ? 'badge-admin' : 'badge-user'; ?>">
                                            <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['customer_id'] != $user_id): // Don't allow self-modification ?>
                                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to toggle this user\'s status?');">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['customer_id']; ?>">
                                                <button type="submit" class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                    <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-admin {
    background-color: #dc3545;
    color: white;
}

.badge-user {
    background-color: #6c757d;
    color: white;
}

.badge-active {
    background-color: #28a745;
    color: white;
}

.badge-inactive {
    background-color: #ffc107;
    color: #212529;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h4 {
    margin-bottom: 0.5rem;
    color: #495057;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.btn-warning {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #212529;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
    color: white;
}
</style>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

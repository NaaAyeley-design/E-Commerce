<?php
/**
 * Category Management Page
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/category_controller.php';
require_once __DIR__ . '/../../../class/category_class.php';

// Set page variables
$page_title = 'Category Management';
$page_description = 'Manage product categories for your e-commerce platform.';
$body_class = 'categories-page';
$additional_css = ['categories.css'];

// Check authentication
if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/view/user/login.php');
    exit;
}

if (!is_admin()) {
    header('Location: ' . BASE_URL . '/view/user/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $cat_name = trim($_POST['cat_name'] ?? '');
                if (!empty($cat_name)) {
                    $result = add_category_ctr($cat_name, $user_id);
                    if ($result === 'success') {
                        $message = "Category added successfully!";
                    } else {
                        $error = $result;
                    }
                } else {
                    $error = "Category name is required.";
                }
                break;
                
            case 'delete':
                $cat_id = (int)($_POST['cat_id'] ?? 0);
                if ($cat_id > 0) {
                    $result = delete_category_ctr($cat_id, $user_id);
                    if ($result === 'success') {
                        $message = "Category deleted successfully!";
                    } else {
                        $error = $result;
                    }
                } else {
                    $error = "Invalid category ID.";
                }
                break;
                
            case 'update':
                $cat_id = (int)($_POST['cat_id'] ?? 0);
                $cat_name = trim($_POST['cat_name'] ?? '');
                if ($cat_id > 0 && !empty($cat_name)) {
                    $result = update_category_ctr($cat_id, $cat_name, $user_id);
                    if ($result === 'success') {
                        $message = "Category updated successfully!";
                    } else {
                        $error = $result;
                    }
                } else {
                    $error = "Category ID and name are required.";
                }
                break;
        }
    }
}

// Get categories - for admin users, show ALL categories
try {
    $category = new category_class();
    $categories = $category->get_all_categories(1000, 0); // Get up to 1000 categories
    
    if ($categories === false) {
        $categories = [];
    }
} catch (Exception $e) {
    error_log("Get categories error: " . $e->getMessage());
    $categories = [];
}

// Include header
include __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Category Management</h1>
        <p>Manage product categories for your e-commerce platform.</p>
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

    <!-- Add Category Form -->
    <div class="card">
        <div class="card-header">
            <h3>Add New Category</h3>
        </div>
        <div class="card-body">
            <form method="post" class="form-inline">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <input type="text" name="cat_name" placeholder="Category name" required class="form-input">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="card">
        <div class="card-header">
            <h3>Your Categories</h3>
            <p class="text-muted">Total: <?php echo count($categories); ?> categories</p>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h4>No Categories Found</h4>
                    <p>Add your first category above to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['cat_id']; ?></td>
                                    <td>
                                        <span id="name-<?php echo $category['cat_id']; ?>">
                                            <?php echo escape_html($category['cat_name']); ?>
                                        </span>
                                        <form id="edit-form-<?php echo $category['cat_id']; ?>" method="post" style="display: none;" class="form-inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cat_id" value="<?php echo $category['cat_id']; ?>">
                                            <input type="text" name="cat_name" value="<?php echo escape_html($category['cat_name']); ?>" required class="form-input">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Save
                                            </button>
                                            <button type="button" onclick="toggleEdit(<?php echo $category['cat_id']; ?>)" class="btn btn-sm btn-outline">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <button onclick="toggleEdit(<?php echo $category['cat_id']; ?>)" class="btn btn-sm btn-outline">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cat_id" value="<?php echo $category['cat_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
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

<script>
function toggleEdit(categoryId) {
    const nameSpan = document.getElementById('name-' + categoryId);
    const editForm = document.getElementById('edit-form-' + categoryId);
    
    if (editForm.style.display === 'none') {
        // Show edit form
        nameSpan.style.display = 'none';
        editForm.style.display = 'block';
        editForm.querySelector('input[name="cat_name"]').focus();
    } else {
        // Hide edit form
        nameSpan.style.display = 'block';
        editForm.style.display = 'none';
    }
}

// Auto-submit edit forms when Enter is pressed
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.name === 'cat_name') {
        e.target.closest('form').submit();
    }
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/footer.php';
?>

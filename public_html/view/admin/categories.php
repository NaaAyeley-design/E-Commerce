<?php
/**
 * Simple Category Management - No JavaScript, Just Works
 */

require_once __DIR__ . '/../../../settings/core.php';
require_once __DIR__ . '/../../../controller/category_controller.php';

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

$user_id = $_SESSION['user_id'];
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

// Get categories (with search if provided, or show all if requested)
$search_term = $_GET['search'] ?? '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';

if ($show_all) {
    // Show ALL categories from database (no limit)
    $category = new category_class();
    $categories = $category->get_categories_by_user($user_id, 999999, 0); // Very large limit to get all
} elseif (!empty($search_term)) {
    $categories = search_categories_ctr($search_term, $user_id);
} else {
    $categories = get_categories_ctr($user_id);
}

if (is_string($categories)) {
    $error = $categories;
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Categories - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/sleep.css">
</head>
<body>
    <!-- Simple Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>/public/index.php" class="navbar-brand">
                E-Commerce Platform
            </a>
            
            <ul class="nav-menu">
                <li><a href="<?php echo BASE_URL; ?>/view/admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>/view/admin/categories.php" class="nav-link">Categories</a></li>
                <li><a href="<?php echo BASE_URL; ?>/actions/logout_action.php" class="nav-link">Logout (<?php echo escape_html($_SESSION['user_name'] ?? $_SESSION['customer_name']); ?>)</a></li>
            </ul>
        </div>
    </nav>

    <div class="container center-content">
        <h1>Category Management</h1>
        
        <?php if ($message): ?>
            <div class="message message-success"><?php echo escape_html($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?php echo escape_html($error); ?></div>
        <?php endif; ?>

        <!-- Add Category Form -->
        <div class="card">
            <h3>Add New Category</h3>
            <form method="post" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="cat_name" placeholder="Category name" required class="form-input" style="flex: 1;">
                <button type="submit" class="btn btn-primary">Add Category</button>
            </form>
        </div>

        <!-- Search Categories -->
        <div class="card">
            <h3>Search Categories</h3>
            <form method="get" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" placeholder="Search categories..." value="<?php echo escape_html($_GET['search'] ?? ''); ?>" class="form-input" style="flex: 1;">
                <button type="submit" class="btn btn-outline">Search</button>
                <?php if (isset($_GET['search']) || $show_all): ?>
                    <a href="<?php echo BASE_URL; ?>/view/admin/categories.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Categories List -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3>Your Categories</h3>
                    <?php if (!empty($categories)): ?>
                        <p style="margin: 5px 0; color: #666; font-size: 14px;">
                            <?php if ($show_all): ?>
                                Showing ALL <?php echo count($categories); ?> categories from database
                            <?php elseif (isset($_GET['search']) && !empty($_GET['search'])): ?>
                                Showing <?php echo count($categories); ?> result(s) for "<?php echo escape_html($_GET['search']); ?>"
                            <?php else: ?>
                                Total: <?php echo count($categories); ?> categories
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($categories)): ?>
                <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <p>No categories found matching "<?php echo escape_html($_GET['search']); ?>". Try a different search term.</p>
                <?php else: ?>
                    <p>No categories found. Add your first category above.</p>
                <?php endif; ?>
            <?php else: ?>
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
                                    <form id="edit-form-<?php echo $category['cat_id']; ?>" method="post" style="display: none;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cat_id" value="<?php echo $category['cat_id']; ?>">
                                        <input type="text" name="cat_name" value="<?php echo escape_html($category['cat_name']); ?>" required class="form-input">
                                    </form>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <button onclick="toggleEdit(<?php echo $category['cat_id']; ?>)" class="btn btn-sm btn-outline">Edit</button>
                                    
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cat_id" value="<?php echo $category['cat_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
</body>
</html>

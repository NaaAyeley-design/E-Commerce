<?php
session_start();
require_once("db.php");

// Check if user is logged in (adjust based on your auth system)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    // Uncomment if authentication is required
    // header('Location: login.php');
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands - Ecommerce Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="brands.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Ecommerce Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">👥</span>
                    <span>Customers</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📦</span>
                    <span>Orders</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">🛍️</span>
                    <span>Products</span>
                </a>
                <a href="brands.php" class="nav-item active">
                    <span class="nav-icon">🏷️</span>
                    <span>Brands</span>
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">💰</span>
                    <span>Revenue</span>
                </a>
                <a href="login.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <h1>Brands Management</h1>
                <div class="header-actions">
                    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                    <button class="add-brand-btn" onclick="showAddBrandModal()">+ Add Brand</button>
                </div>
            </header>

            <!-- Brands Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>All Brands</h2>
                    <div class="search-box">
                        <input type="text" id="brandSearch" placeholder="Search brands..." onkeyup="filterBrands()">
                    </div>
                </div>
                
                <div class="brands-table-container">
                    <table class="brands-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Brand Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="brandsTableBody">
                            <!-- Sample data - replace with dynamic data from database -->
                            <tr>
                                <td class="brand-id">1</td>
                                <td class="brand-name">Nike</td>
                                <td class="brand-description">Just Do It - Athletic wear and footwear</td>
                                <td class="brand-products">45</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td class="brand-actions">
                                    <button class="action-btn edit-btn" onclick="editBrand(1)">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteBrand(1)">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td class="brand-id">2</td>
                                <td class="brand-name">Adidas</td>
                                <td class="brand-description">Impossible is Nothing - Sports apparel</td>
                                <td class="brand-products">38</td>
                                <td><span class="status-badge active">Active</span></td>
                                <td class="brand-actions">
                                    <button class="action-btn edit-btn" onclick="editBrand(2)">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteBrand(2)">Delete</button>
                                </td>
                            </tr>
                            <tr>
                                <td class="brand-id">3</td>
                                <td class="brand-name">Puma</td>
                                <td class="brand-description">Forever Faster - Performance sportswear</td>
                                <td class="brand-products">22</td>
                                <td><span class="status-badge inactive">Inactive</span></td>
                                <td class="brand-actions">
                                    <button class="action-btn edit-btn" onclick="editBrand(3)">Edit</button>
                                    <button class="action-btn delete-btn" onclick="deleteBrand(3)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Brand Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon brands">🏷️</div>
                    <div class="stat-content">
                        <h3>Total Brands</h3>
                        <p class="stat-value">12</p>
                        <span class="stat-change">Active brands</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon products">🛍️</div>
                    <div class="stat-content">
                        <h3>Brand Products</h3>
                        <p class="stat-value">245</p>
                        <span class="stat-change">Across all brands</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Brand Modal -->
    <div id="brandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBrandModal()">&times;</span>
            <h2 id="modalTitle">Add New Brand</h2>
            <form id="brandForm">
                <div class="form-group">
                    <label for="brandName">Brand Name *</label>
                    <input type="text" id="brandName" name="brandName" required>
                </div>
                <div class="form-group">
                    <label for="brandDescription">Description</label>
                    <textarea id="brandDescription" name="brandDescription" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="brandStatus">Status</label>
                    <select id="brandStatus" name="brandStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeBrandModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Brand</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddBrandModal() {
            document.getElementById('brandModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Brand';
            document.getElementById('brandForm').reset();
        }

        function closeBrandModal() {
            document.getElementById('brandModal').style.display = 'none';
        }

        function editBrand(id) {
            document.getElementById('brandModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Brand';
            // Load brand data here
        }

        function deleteBrand(id) {
            if (confirm('Are you sure you want to delete this brand?')) {
                // Delete brand logic here
                console.log('Deleting brand:', id);
            }
        }

        function filterBrands() {
            const input = document.getElementById('brandSearch');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('brandsTableBody');
            const tr = table.getElementsByTagName('tr');

            for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1]; // Brand name column
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('brandModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>


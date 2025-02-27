<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Check if user has permission to manage products
requirePermission('manage_products');

// Fetch all categories with product counts
$sql = "
    SELECT c.*, 
           COUNT(p.product_id) as product_count,
           COALESCE(u1.full_name, 'System') as created_by_name,
           COALESCE(u2.full_name, '') as updated_by_name
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    LEFT JOIN users u1 ON c.created_by = u1.user_id
    LEFT JOIN users u2 ON c.updated_by = u2.user_id
    GROUP BY c.category_id, c.name, c.description, c.created_at, c.created_by, c.updated_at, c.updated_by,
             u1.full_name, u2.full_name
    ORDER BY c.name
";
$categories = fetchAll($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexInvent - Category Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        /* Override any conflicting styles */
        :root {
            --primary-color: #4f46e5 !important;
            --primary-dark: #4338ca !important;
        }

        body {
            font-family: 'Inter', sans-serif !important;
            background-color: #f9fafb !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600 !important;
            
        }   
           
        
        .main-content {
            padding: 2rem !important;
        }

        .card {
            border-radius: 1rem !important;
            border: none !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            background-color: white !important;
        }

        .table {
            font-size: 0.875rem !important;
        }

        .table thead th {
            font-family: 'Inter', sans-serif !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.05em !important;
            background-color: #f8fafc !important;
            padding: 1rem !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .table tbody td {
            padding: 1rem !important;
            vertical-align: middle !important;
        }

        .btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark) !important;
            border-color: var(--primary-dark) !important;
            transform: translateY(-1px) !important;
        }

        .modal-content {
            border-radius: 1rem !important;
            border: none !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
        }

        .modal-header {
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem !important;
        }

        .modal-footer {
            background-color: #f8fafc !important;
            border-top: 1px solid #e2e8f0 !important;
            border-radius: 0 0 1rem 1rem !important;
            padding: 1.25rem !important;
        }

        .form-control {
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            padding: 0.625rem 1rem !important;
        }

        .form-control:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1) !important;
        }
      
    </style>
</head>
<body>

<?php include '../../includes/sidebar.php'; ?>
<?php include '../../includes/header.php'; ?>

<div class="main-content">
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Category Management</h2>
           
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg"></i> Add Category
            </button>
            
        </div>

       

        <!-- Categories Table -->
        <div class="card">
            <div class="card-body">
                <table id="categoriesTable" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td><?php echo htmlspecialchars($category['created_by_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($category['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['category_id']; ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
          
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCategoryForm">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Save Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateCategory()">Update Category</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#categoriesTable').DataTable({
        "order": [[1, "asc"]],
        "pageLength": 25
    });
});

function saveCategory() {
    const formData = {
        name: $('#name').val(),
        description: $('#description').val()
    };

    if (!formData.name) {
        showError('Category name is required');
        return;
    }

    showLoading('Saving category...');

    $.ajax({
        url: 'save_category.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            hideLoading();
            if (response.success) {
                showSuccess('Category saved successfully', function() {
                    location.reload();
                });
            } else {
                showError(response.error);
            }
        },
        error: function(xhr) {
            hideLoading();
            showError('Failed to save category');
        }
    });
}

function editCategory(categoryId) {
    showLoading('Loading category details...');

    $.get('get_category.php', { id: categoryId }, function(response) {
        hideLoading();
        if (response.success) {
            const category = response.data;
            $('#edit_category_id').val(category.category_id);
            $('#edit_name').val(category.name);
            $('#edit_description').val(category.description);
            $('#editCategoryModal').modal('show');
        } else {
            showError(response.error);
        }
    });
}

function updateCategory() {
    const formData = {
        category_id: $('#edit_category_id').val(),
        name: $('#edit_name').val(),
        description: $('#edit_description').val()
    };

    if (!formData.name) {
        showError('Category name is required');
        return;
    }

    showLoading('Updating category...');

    $.ajax({
        url: 'update_category.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            hideLoading();
            if (response.success) {
                showSuccess('Category updated successfully', function() {
                    location.reload();
                });
            } else {
                showError(response.error);
            }
        },
        error: function(xhr) {
            hideLoading();
            showError('Failed to update category');
        }
    });
}

function deleteCategory(categoryId) {
    showConfirm('Are you sure you want to delete this category?', function() {
        showLoading('Deleting category...');

        $.ajax({
            url: 'delete_category.php',
            type: 'POST',
            data: { category_id: categoryId },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    showSuccess('Category deleted successfully', function() {
                        location.reload();
                    });
                } else {
                    showError(response.error);
                }
            },
            error: function(xhr) {
                hideLoading();
                showError('Failed to delete category');
            }
        });
    });
}
</script>

</body>
</html>
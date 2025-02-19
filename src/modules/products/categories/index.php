<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login/index.php");
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
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<?php include '../../../modules/includes/sidebar.php'; ?>
<?php include '../../../modules/includes/header.php'; ?>

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

<?php include '../../../modules/includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#categoriesTable').DataTable({
        "order": [[0, "asc"]],
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
        url: 'ajax/save_category.php',
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

    $.get('ajax/get_category.php', { id: categoryId }, function(response) {
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
        url: 'ajax/update_category.php',
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
            url: 'ajax/delete_category.php',
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
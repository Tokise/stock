<?php
require_once 'config/db.php';

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/fix_users.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read the SQL file');
    }
    
    // Get database connection
    $pdo = getDBConnection();
    
    // Execute the SQL commands
    $result = $pdo->exec($sql);
    
    $success = true;
    $message = "Database structure has been updated successfully!";
    
} catch (Exception $e) {
    $success = false;
    $message = "Error updating database structure: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix - NexInvent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($success): ?>
                            <h3 class="text-success mb-4">
                                <i class="bi bi-check-circle-fill"></i> Success!
                            </h3>
                            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
                            <a href="users/create_staff.php" class="btn btn-primary">
                                Return to Create Staff Account
                            </a>
                        <?php else: ?>
                            <h3 class="text-danger mb-4">
                                <i class="bi bi-exclamation-circle-fill"></i> Error
                            </h3>
                            <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
                            <a href="users/create_staff.php" class="btn btn-primary">
                                Try Again
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
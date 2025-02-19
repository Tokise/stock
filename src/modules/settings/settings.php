<?php
session_start();
require_once '../config/db.php';
require_once '../includes/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login/index.php");
    exit();
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT username, email, full_name FROM users WHERE user_id = ?";
$user = fetchOne($sql, [$user_id]);

// Handle form submission for updating user info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        // Ensure the uploads directory exists
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Validate and move uploaded file
        $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update profile picture in the database
            $sql = "UPDATE settings SET profile_pic = ? WHERE user_id = ?";
            execute($sql, [$target_file, $user_id]);
        } else {
            // Handle error if the file could not be moved
            $_SESSION['error'] = "Failed to upload profile picture.";
        }
    }

    // Handle personal information update
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?";
    execute($sql, [$full_name, $email, $user_id]);

    // Handle password change
    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        execute($sql, [$new_password, $user_id]);
    }

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
        }
        .dark-mode {
            --bg-color: #2c3e50;
            --text-color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>User Settings</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="profile_pic" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="profile_pic" name="profile_pic">
            </div>
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
        <div class="mt-4">
            <h5>Toggle Dark/Light Mode</h5>
            <button id="toggleMode" class="btn btn-secondary">Switch to Dark Mode</button>
        </div>
    </div>

    <script>
        const toggleButton = document.getElementById('toggleMode');
        toggleButton.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            toggleButton.textContent = document.body.classList.contains('dark-mode') ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        });
    </script>
</body>
</html>

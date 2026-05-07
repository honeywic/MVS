<?php
session_start();
require_once '../config/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Server configuration error.';
    exit;
}

// Ensure user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$messageType = '';

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $del = $pdo->prepare('DELETE FROM positions WHERE position_id = ?');
        if ($del->execute([$delete_id])) {
            $message = '✅ Post deleted successfully!';
            $messageType = 'success';
        } else {
            $message = '❌ Failed to delete post.';
            $messageType = 'error';
        }
    } catch (PDOException $e) {
        error_log('Delete post error: ' . $e->getMessage());
        $message = '❌ Failed to delete post due to a server error.';
        $messageType = 'error';
    }
}

// Handle add post request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    if ($name === '') {
        $message = '⚠️ Please enter a valid post name.';
        $messageType = 'warning';
    } elseif (!preg_match('/^[A-Z\s]+$/', $name)) {
        $message = '⚠️ Post name must contain only uppercase letters and spaces.';
        $messageType = 'warning';
    } elseif (strlen($name) > 150) {
        $message = '⚠️ Post name is too long (max 150 characters).';
        $messageType = 'warning';
    } else {
        try {
            // Check if position exists
            $check = $pdo->prepare('SELECT position_id FROM positions WHERE position_name = ? LIMIT 1');
            $check->execute([$name]);
            if ($check->fetch()) {
                $message = '⚠️ Post already exists!';
                $messageType = 'warning';
            } else {
                // Insert new position
                $ins = $pdo->prepare('INSERT INTO positions (position_name, created_at) VALUES (?, NOW())');
                if ($ins->execute([$name])) {
                    $message = '✅ Post added successfully!';
                    $messageType = 'success';
                } else {
                    $message = '❌ Failed to add post. Please try again.';
                    $messageType = 'error';
                }
            }
        } catch (PDOException $e) {
            error_log('Add post error: ' . $e->getMessage());
            $message = '❌ Failed to add post due to a server error.';
            $messageType = 'error';
        }
    }
}

// Fetch all positions
$posts = [];
try {
    $fetch = $pdo->prepare('SELECT position_id, position_name, created_at FROM positions ORDER BY created_at DESC');
    $fetch->execute();
    $posts = $fetch->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fetch posts error: ' . $e->getMessage());
    $message = '❌ Failed to load posts due to a server error.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Post</title>
    <!-- <link rel="stylesheet" href="../assets/css/login-styles.css"> -->
    <link rel="stylesheet" href="../assets/css/add-post-styles.css">
</head>
<body>
    <canvas id="particle-canvas"></canvas>
    <nav class="navbar">
        <a href="../index.php">
            <img src="../assets/images/mzumbe.png" alt="Mzumbe School Logo" class="navbar-logo" />
        </a>
        <h1 class="navbar-title">Mzumbe Secondary School</h1>
    </nav>
    <main>
        <h2 class="form-header">Add New Post</h2>
        <div id="message-box" class="message-box" role="alert"></div>
        <div class="input-group">
            <form method="post" novalidate>
                <div class="input-group">
                    <input type="text" name="name" class="input-field" placeholder="Enter Post Name (e.g., HEAD PREFECT)" required autofocus pattern="[A-Z\s]+" title="Only uppercase letters and spaces are allowed" maxlength="150">
                </div>
                <div class="input-group">
                    <button type="submit" class="submit-btn">Add Post</button>
                </div>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Position Name</th>
                        <th>Added At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr><td colspan="3">No posts available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['position_name']); ?></td>
                                <td><?php echo htmlspecialchars($p['created_at']); ?></td>
                                <td>
                                    <form method="get" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($p['position_id']); ?>">
                                        <button type="submit" class="delete-btn" title="Delete Post">
                                            <span class="delete-emoji">🗑️</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="info-text">
            Back to <a href="../manage/dashboard.php">Dashboard</a>
        </p>
    </main>
    <script>
        // Message box handling
        function showMessage(message, type = 'error') {
            const messageBox = document.getElementById('message-box');
            messageBox.textContent = message;
            messageBox.className = `message-box ${type} show`;
            setTimeout(() => {
                messageBox.classList.remove('show');
                messageBox.style.opacity = '0';
                setTimeout(() => {
                    messageBox.style.display = 'none';
                }, 300);
            }, 3000);
        }

        // Display message if set
        <?php if (!empty($message)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($message)); ?>, '<?php echo htmlspecialchars($messageType); ?>');
        <?php endif; ?>
    </script>
    <script src="../assets/js/add-post-script.js"></script>
</body>
</html>

<?php
session_start();
require_once '../config/config.php';

// Check PDO connection
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Server configuration error: PDO not initialized.';
    exit;
}

// Ensure PDO is in exception mode
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ensure user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$messageType = '';

// Fetch positions for dropdown
$positions = [];
try {
    $stmt = $pdo->prepare('SELECT position_id, position_name FROM positions ORDER BY position_name');
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fetch positions error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
    $message = '❌ Failed to load positions: ' . htmlspecialchars($e->getMessage());
    $messageType = 'error';
}

// Fetch classes for dropdown
$classes = [];
try {
    $stmt = $pdo->prepare('SELECT class_id, class_name FROM classes ORDER BY class_name');
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fetch classes error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
    $message = '❌ Failed to load classes: ' . htmlspecialchars($e->getMessage());
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_id = isset($_POST['position_id']) ? (int)$_POST['position_id'] : 0;
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $candidate_name = isset($_POST['candidate_name']) ? trim($_POST['candidate_name']) : '';
    $slogan = isset($_POST['slogan']) ? trim($_POST['slogan']) : '';

    // Input validation
    if (empty($positions)) {
        $message = '⚠️ No positions available. Please add positions first.';
        $messageType = 'warning';
    } elseif (empty($classes)) {
        $message = '⚠️ No classes available. Please add classes first.';
        $messageType = 'warning';
    } elseif ($position_id <= 0) {
        $message = '⚠️ Please select a valid position.';
        $messageType = 'warning';
    } elseif ($class_id <= 0) {
        $message = '⚠️ Please select a valid class.';
        $messageType = 'warning';
    } elseif ($candidate_name === '') {
        $message = '⚠️ Please enter a valid candidate name.';
        $messageType = 'warning';
    } elseif (!preg_match('/^[A-Za-z\s]+$/', $candidate_name)) {
        $message = '⚠️ Candidate name must contain only letters and spaces.';
        $messageType = 'warning';
    } elseif (strlen($candidate_name) > 100) {
        $message = '⚠️ Candidate name is too long (max 100 characters).';
        $messageType = 'warning';
    } elseif (strlen($slogan) > 255) {
        $message = '⚠️ Slogan is too long (max 255 characters).';
        $messageType = 'warning';
    } else {
        try {
            // Verify position_id exists
            $check_pos = $pdo->prepare('SELECT position_id FROM positions WHERE position_id = ? LIMIT 1');
            $check_pos->execute([$position_id]);
            // Verify class_id exists
            $check_class = $pdo->prepare('SELECT class_id FROM classes WHERE class_id = ? LIMIT 1');
            $check_class->execute([$class_id]);
            if (!$check_pos->fetch()) {
                $message = '⚠️ Invalid position selected.';
                $messageType = 'warning';
            } elseif (!$check_class->fetch()) {
                $message = '⚠️ Invalid class selected.';
                $messageType = 'warning';
            } else {
                // Check if candidate exists for this position and class
                $check = $pdo->prepare('SELECT candidate_id FROM candidates WHERE position_id = ? AND class_id = ? AND candidate_name = ? LIMIT 1');
                $check->execute([$position_id, $class_id, $candidate_name]);
                if ($check->fetch()) {
                    $message = '⚠️ Candidate already exists for this position and class!';
                    $messageType = 'warning';
                } else {
                    // Insert new candidate
                    $ins = $pdo->prepare('INSERT INTO candidates (position_id, class_id, candidate_name, slogan, votes_count, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
                    if ($ins->execute([$position_id, $class_id, $candidate_name, $slogan])) {
                        $message = '✅ Candidate added successfully!';
                        $messageType = 'success';
                    } else {
                        $message = '❌ Failed to add candidate: Unknown database error.';
                        $messageType = 'error';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Add candidate error: ' . $e->getMessage() . ' | Code: ' . $e->getCode());
            if ($e->getCode() == '23000') {
                $message = '⚠️ Database constraint violation (e.g., invalid position/class or duplicate entry).';
                $messageType = 'warning';
            } else {
                $message = '❌ Failed to add candidate: ' . htmlspecialchars($e->getMessage());
                $messageType = 'error';
            }
        }
    }
}

// Handle delete candidate request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $del = $pdo->prepare('DELETE FROM candidates WHERE candidate_id = ?');
        if ($del->execute([$delete_id])) {
            $message = '✅ Candidate deleted successfully!';
            $messageType = 'success';
        } else {
            $message = '❌ Failed to delete candidate.';
            $messageType = 'error';
        }
    } catch (PDOException $e) {
        error_log('Delete candidate error: ' . $e->getMessage());
        $message = '❌ Failed to delete candidate due to a server error.';
        $messageType = 'error';
    }
}

// Fetch all candidates with position and class names and candidate_id
$candidates = [];
try {
    $fetch = $pdo->prepare('
        SELECT c.candidate_id, c.candidate_name, p.position_name, cl.class_name
        FROM candidates c
        JOIN positions p ON c.position_id = p.position_id
        JOIN classes cl ON c.class_id = cl.class_id
        ORDER BY p.position_name, cl.class_name, c.candidate_name
    ');
    $fetch->execute();
    $candidates = $fetch->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Fetch candidates error: ' . $e->getMessage());
    $message = '❌ Failed to load candidates due to a server error.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate</title>
    <link rel="stylesheet" href="../assets/css/add-candidate-styles.css">
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
        <h2 class="form-header">Add New Candidate</h2>
        <div id="message-box" class="message-box" role="alert"></div>
        <div class="input-group">
            <form method="post" autocomplete="off" novalidate>
                <div class="input-group">
                    <select name="position_id" class="input-field" required>
                        <option value="" disabled selected>Select Position</option>
                        <?php foreach ($positions as $position): ?>
                            <option value="<?php echo htmlspecialchars($position['position_id']); ?>">
                                <?php echo htmlspecialchars($position['position_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <select name="class_id" class="input-field" required>
                        <option value="" disabled selected>Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['class_id']); ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group">
                    <input type="text" name="candidate_name" class="input-field" placeholder="Enter Candidate Name (e.g, David Richard)" 
                           required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" maxlength="100">
                </div>
                <div class="input-group">
                    <input type="text" name="slogan" class="input-field" placeholder="Enter Slogan (optional)" maxlength="255">
                </div>
                <div class="input-group">
                    <button type="submit" class="submit-btn">Add Candidate</button>
                </div>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th>Class</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidates)): ?>
                        <tr><td colspan="4">No candidates available.</td></tr>
                    <?php else: ?>
                        <?php foreach ($candidates as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['candidate_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['position_name']); ?></td>
                                <td><?php echo htmlspecialchars($c['class_name']); ?></td>
                                <td>
                                    <form method="get" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($c['candidate_id']); ?>">
                                        <button type="submit" class="delete-btn" title="Delete Candidate">
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
    <script src="../assets/js/add-candidate-script.js"></script>
</body>
</html>
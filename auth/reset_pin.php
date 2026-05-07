<?php
session_start();
require_once '../config/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Server configuration error.';
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = isset($_POST['studentId']) ? trim($_POST['studentId']) : '';
    $currentPin = isset($_POST['currentPin']) ? trim($_POST['currentPin']) : '';
    $newPin = isset($_POST['newPin']) ? trim($_POST['newPin']) : '';

    // Input validation
    if ($studentId === '' || !ctype_digit($studentId) || strlen($studentId) !== 4) {
        $error = '❌ Please enter a valid 4-digit Student ID.';
    } elseif ($studentId !== $_SESSION['student_id']) {
        $error = '❌ Student ID does not match your account.';
    } elseif ($currentPin === '' || !ctype_digit($currentPin) || strlen($currentPin) !== 4) {
        $error = '❌ Please enter a valid 4-digit current PIN.';
    } elseif ($newPin === '' || !ctype_digit($newPin) || strlen($newPin) !== 4) {
        $error = '❌ Please enter a valid 4-digit new PIN.';
    } elseif ($currentPin === $newPin) {
        $error = '❌ New PIN must be different from the current PIN.';
    } elseif ($newPin === $studentId) {
        $error = '❌ New PIN cannot be the same as your Student ID.';
    } else {
        try {
            // Verify current PIN
            $stmt = $pdo->prepare('SELECT pin_code FROM users WHERE student_id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$studentId, $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = '❌ Invalid Student ID or user not found.';
            } else {
                $storedPin = $user['pin_code'];
                $isValid = false;

                if (is_string($storedPin) && $storedPin !== '') {
                    if (password_needs_rehash($storedPin, PASSWORD_DEFAULT)) {
                        if ($currentPin === $storedPin || password_verify($currentPin, $storedPin)) {
                            $isValid = true;
                        }
                    } else {
                        if (password_verify($currentPin, $storedPin) || $currentPin === $storedPin) {
                            $isValid = true;
                        }
                    }
                }

                if (!$isValid) {
                    $error = '❌ Incorrect current PIN. Please try again.';
                } else {
                    // Update PIN
                    $newPinHash = password_hash($newPin, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare('UPDATE users SET pin_code = ? WHERE student_id = ? AND user_id = ?');
                    $updateStmt->execute([$newPinHash, $studentId, $_SESSION['user_id']]);

                    if ($updateStmt->rowCount() > 0) {
                        $success = '✅ PIN updated successfully!';
                    } else {
                        $error = '❌ Failed to update PIN. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('Reset PIN error: ' . $e->getMessage());
            $error = '❌ Failed to reset PIN due to a server error. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset PIN</title>
    <link rel="stylesheet" href="../assets/css/reset-pin-styles.css">
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
        <h2 class="form-header">Reset PIN</h2>
        <div id="message-box" class="message-box" role="alert"></div>
        <form id="reset-pin-form" method="POST" autocomplete="off">
            <div class="input-group">
                <input type="text" name="studentId" id="studentId" class="input-field" placeholder="Student ID (4 digits)" required pattern="\d{4}" maxlength="4" value="<?php echo isset($_SESSION['student_id']) ? htmlspecialchars($_SESSION['student_id'], ENT_QUOTES, 'UTF-8') : ''; ?>" readonly>
            </div>
            <div class="password-wrapper">
                <input type="password" name="currentPin" id="currentPin" class="input-field" placeholder="Current PIN (4 digits)" required pattern="\d{4}" maxlength="4">
                <span class="eye-icon" id="toggleCurrentPin" title="Show/Hide Current PIN">&#128065;</span>
            </div>
            <div class="password-wrapper">
                <input type="password" name="newPin" id="newPin" class="input-field" placeholder="New PIN (4 digits)" required pattern="\d{4}" maxlength="4">
                <span class="eye-icon" id="toggleNewPin" title="Show/Hide New PIN">&#128065;</span>
            </div>
            <button type="submit" class="submit-btn">Reset PIN</button>
        </form>
        <p class="info-text">
            Back to <a href="../manage/dashboard.php">Dashboard</a>
        </p>
    </main>
    <script>
        // Function to show messages (error or success)
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

        // Display error or success message if set
        <?php if (!empty($error)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')); ?>, 'error');
        <?php elseif (!empty($success)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($success, ENT_QUOTES, 'UTF-8')); ?>, 'success');
        <?php endif; ?>

        // Toggle password visibility for current PIN
        document.getElementById('toggleCurrentPin').addEventListener('click', function() {
            const pin = document.getElementById('currentPin');
            if (pin.type === 'password') {
                pin.type = 'text';
                this.textContent = '🙈';
                this.setAttribute('title', 'Hide Current PIN');
            } else {
                pin.type = 'password';
                this.textContent = '👁️';
                this.setAttribute('title', 'Show Current PIN');
            }
        });

        // Toggle password visibility for new PIN
        document.getElementById('toggleNewPin').addEventListener('click', function() {
            const pin = document.getElementById('newPin');
            if (pin.type === 'password') {
                pin.type = 'text';
                this.textContent = '🙈';
                this.setAttribute('title', 'Hide New PIN');
            } else {
                pin.type = 'password';
                this.textContent = '👁️';
                this.setAttribute('title', 'Show New PIN');
            }
        });
    </script>
    <script src="../assets/js/reset-pin-script.js"></script>
</body>
</html>

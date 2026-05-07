<?php
session_start();
require_once '../config/config.php';

$classes = [];
$error = '';
$success = '';

try {
    $stmt = $pdo->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "❌ Failed to load classes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = trim($_POST['studentId'] ?? '');
    $studentClass = trim($_POST['class'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate inputs
    if (!preg_match('/^\d{4}$/', $studentId)) {
        $error = "❌ Student ID must be exactly 4 digits.";
    } elseif (empty($studentClass)) {
        $error = "❌ Please select a class.";
    } elseif (!preg_match('/^\d{4}$/', $password)) {
        $error = "❌ Password (PIN) must be exactly 4 digits.";
    } elseif ($studentId === $password) {
        $error = "❌ Student ID and PIN must not be the same.";
    } else {
        try {
            // Get class_id from classes table
            $classStmt = $pdo->prepare("SELECT class_id FROM classes WHERE class_name = ?");
            $classStmt->execute([$studentClass]);
            $classRow = $classStmt->fetch();
            if (!$classRow) {
                $error = "❌ Selected class does not exist.";
            } else {
                $class_id = $classRow['class_id'];

                // Check if student ID already exists in users table
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_id = ?");
                $check->execute([$studentId]);
                if ($check->fetchColumn() > 0) {
                    $error = "❌ Student ID already registered.";
                } else {
                    // Hash the PIN code
                    $hashedPin = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new user with both 'student' and 'voter' roles
                    $insert = $pdo->prepare("INSERT INTO users (class_id, student_id, pin_code, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $insert->execute([$class_id, $studentId, $hashedPin, 'voter']);
                    $success = "✅ Registration successful! You can now login.";
                }
            }
        } catch (PDOException $e) {
            $error = "❌ Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration</title>
<link rel="stylesheet" href="../assets/css/register-styles.css">
<style>
.eye-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 2rem;
    opacity: 1;
    z-index: 2;
    transition: opacity 0.2s, color 0.2s;
    color: #555;
}
.eye-icon:hover {
    opacity: 0.8;
    color: #ffffffff;
}
.password-wrapper {
    position: relative;
    width: 100%;
}
.floating-message {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    min-width: 320px;
    max-width: 90vw;
    padding: 24px 36px;
    border-radius: 16px;
    font-size: 1.15em;
    font-weight: 600;
    text-align: center;
    z-index: 9999;
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.18), 0 1.5px 8px 0 rgba(0,0,0,0.08);
    background: #fff;
    border: none;
    display: none;
    transition: box-shadow 0.2s, background 0.2s, color 0.2s;
    letter-spacing: 0.02em;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    animation: fadeInScale 0.35s cubic-bezier(.4,2,.3,1) forwards;
}
@keyframes fadeInScale {
    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.85);}
    100% { opacity: 1; transform: translate(-50%, -50%) scale(1);}
}
.floating-message.error {
    color: #ffeaea;
    background: #d32f2f;
    display: block;
}
.floating-message.success {
   color: #e8f5e9;
   background: #2e7d32;
   display: block;
}
</style>
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
    <h1 class="form-header">Register</h1>
    <div id="floating-message" class="floating-message"></div>
    <form id="register-form" method="POST" autocomplete="off">
        <input type="text" name="studentId" id="studentId" class="input-field" placeholder="Student ID (4 digits)" required pattern="\d{4}" maxlength="4">

        <select name="class" id="class" class="input-field" required>
            <option value="">-- Select Class --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo htmlspecialchars($c['class_name']); ?>">
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="password-wrapper">
            <input type="password" name="password" id="password" class="input-field" placeholder="Password (4-digit PIN)" required pattern="\d{4}" maxlength="4">
            <span class="eye-icon" id="togglePassword" title="Show/Hide Password">&#128065;</span>
        </div>

        <button type="submit" class="submit-btn" id="register-btn">Register</button>
    </form>
    <p class="info-text">Already have an account? <a href="login.php">Login here</a></p>
</main>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        this.style.opacity = 0.7;
        this.textContent = '🙈';
    } else {
        pwd.type = 'password';
        this.style.opacity = 1;
        this.textContent = '👁️'; 
    }
});

// Floating message display
function showFloatingMessage(msg, type) {
    const box = document.getElementById('floating-message');
    box.textContent = msg;
    box.className = 'floating-message ' + type;
    box.style.display = 'block';
    setTimeout(() => {
        box.style.display = 'none';
    }, type === 'success' ? 1800 : 2500);
}

<?php if (!empty($error)): ?>
    showFloatingMessage("<?php echo addslashes($error); ?>", "error");
<?php elseif (!empty($success)): ?>
    showFloatingMessage("<?php echo addslashes($success); ?>", "success");
    setTimeout(() => { window.location.href = "login.php"; }, 2000);
<?php endif; ?>
</script>
<script src="../assets/js/register-script.js"></script>
</body>
</html>
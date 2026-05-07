<?php
session_start();
require_once '../config/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Server configuration error.';
    exit;
}

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    header('Location: ../manage/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = isset($_POST['studentId']) ? trim($_POST['studentId']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($studentId === '' || !ctype_digit($studentId) || strlen($studentId) !== 4) {
        $error = 'Please enter a valid 4-digit Student ID.';
    } elseif ($password === '' || !ctype_digit($password) || strlen($password) !== 4) {
        $error = 'Please enter a valid 4-digit PIN.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT user_id, pin_code, role FROM users WHERE student_id = ? LIMIT 1');
            $stmt->execute([$studentId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = '❌ Incorrect Student ID or PIN. Please try again.';
            } else {
                $storedPin = $user['pin_code'];
                $isValid = false;

                if (is_string($storedPin) && $storedPin !== '') {
                    if (password_needs_rehash($storedPin, PASSWORD_DEFAULT)) {
                        if ($password === $storedPin || password_verify($password, $storedPin)) {
                            $isValid = true;
                        }
                    } else {
                        if (password_verify($password, $storedPin) || $password === $storedPin) {
                            $isValid = true;
                        }
                    }
                }

                if (!$isValid) {
                    $error = '❌ Incorrect PIN or Student ID. Please try again.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['student_id'] = $studentId;
                    $_SESSION['role'] = $user['role'];
                    $success = '🎉Login successful! Redirecting...';
                    // Redirect is handled in JavaScript to show success message first
                }
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Login failed due to a server error. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
    <link rel="stylesheet" href="../assets/css/login-styles.css">
    <style>
        .message-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 320px;
            max-width: 90vw;
            padding: 20px 32px;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 500;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            display: none;
        }
        .message-box.error {
            color: #ffeaea;
            background: #d32f2f;
        }
        .message-box.success {
            color: #e8f5e9;
            background: #2e7d32;
        }
        .message-box.show {
            display: block;
            opacity: 1;
        }
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
        <h2 class="form-header">Login</h2>
        <div id="message-box" class="message-box" role="alert"></div>
        <form id="login-form" method="POST" autocomplete="off">
            <div class="input-group">
                <input type="text" name="studentId" id="studentId" class="input-field" placeholder="Student ID (4 digits)" required pattern="\d{4}" maxlength="4" value="<?php echo isset($studentId) ? htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="input-field" placeholder="Password (4-digit PIN)" required pattern="\d{4}" maxlength="4">
                <span class="eye-icon" id="togglePassword" title="Show/Hide Password">&#128065;</span>
            </div>
            <button type="submit" class="submit-btn">Login</button>
        </form>
        <p class="info-text">
            Don't have an account? <a href="register.php">Register here</a>
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
                    // Redirect after success message
                    if (type === 'success') {
                        window.location.href = '../manage/dashboard.php';
                    }
                }, 300); // Match transition duration
            }, 1000); // Display for 1 seconds
        }

        // Display error or success message if set
        <?php if (!empty($error)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($error, ENT_QUOTES, 'UTF-8')); ?>, 'error');
        <?php elseif (!empty($success)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($success, ENT_QUOTES, 'UTF-8')); ?>, 'success');
        <?php endif; ?>

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.textContent = '🙈';
                this.setAttribute('title', 'Hide Password');
            } else {
                pwd.type = 'password';
                this.textContent = '👁️';
                this.setAttribute('title', 'Show Password');
            }
        });

        // Particle animation (unchanged)
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        const particleCount = 60;
        const maxDistance = 100;

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            initParticles();
        }

        function Particle() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.vx = (Math.random() - 0.5) * 1;
            this.vy = (Math.random() - 0.5) * 1;
            this.radius = Math.random() * 1.5 + 1;
            this.color = '#fff';
        }

        Particle.prototype.draw = function() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2, false);
            ctx.fillStyle = this.color;
            ctx.fill();
        };

        Particle.prototype.update = function() {
            if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
            if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
            this.x += this.vx;
            this.y += this.vy;
        };

        function initParticles() {
            particles = [];
            for (let i = 0; i < particleCount; i++) particles.push(new Particle());
        }

        function drawLines() {
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const a = particles[i];
                    const b = particles[j];
                    const dx = a.x - b.x;
                    const dy = a.y - b.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < maxDistance) {
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.strokeStyle = `rgba(255, 255, 255, ${1 - distance / maxDistance})`;
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drawLines();
            for (let k = 0; k < particles.length; k++) {
                particles[k].update();
                particles[k].draw();
            }
        }

        window.onload = function() {
            resizeCanvas();
            animate();
        };
        window.addEventListener('resize', resizeCanvas);
    </script>
</body>
</html>
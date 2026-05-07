<?php
session_start();
require_once '../config/config.php';

// --- Auto logout after 3 minutes of inactivity ---
$timeout = 180; // seconds (3 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();
// --- End auto logout ---

// Redirect to login if not logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Show login success message only once after login
$showSuccess = false;
if (!isset($_SESSION['dashboard_welcome'])) {
    $_SESSION['dashboard_welcome'] = true;
    $success = "🎉 Welcome to the voting system.";
    $showSuccess = true;
} else {
    $success = "";
}

$role = $_SESSION['role'];
$username = $_SESSION['student_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- <link rel="stylesheet" href="../assets/css/login-styles.css"> -->
    <link rel="stylesheet" href="../assets/css/dashboard-styles.css">
</head>
<body>
    <canvas id="particle-canvas"></canvas>
    <nav class="navbar">
        <a href="../index.php">
            <img src="../assets/images/mzumbe.png" alt="Mzumbe School Logo" class="navbar-logo" />
        </a>
        <h1 class="navbar-title">Mzumbe Secondary School</h1>
    </nav>
    <div class="dashboard-container">
        <div class="dashboard-header"><?php echo strtoupper($role); ?>
 Dashboard</div>
        <div class="welcome">Hello, <strong><?php echo htmlspecialchars($username); ?></strong></div>
        <div class="dashboard-links">
            <?php if (strpos($role, 'admin') !== false): ?>
                <a href="add_candidate.php" class="dashboard-link">➕ Add Candidate</a>
                <a href="add_post.php" class="dashboard-link">📝 Add Post</a>
                <a href="../vote-handler/live_votes.php" class="dashboard-link">⏱️ Live Votes</a>
                <!-- <a href="students.php" class="dashboard-link">👥 Students</a> -->
                <a href="../vote-handler/voting.php" class="dashboard-link">🗳️ Vote Now</a>
                <a href="../auth/reset_pin.php" class="dashboard-link">🔑 Reset PIN</a>

            <?php elseif (strpos($role, 'voter') !== false): ?>
                <a href="../vote-handler/voting.php" class="dashboard-link">🗳️ Vote Now</a>
                <a href="../auth/reset_pin.php" class="dashboard-link">🔑 Reset PIN</a>
            <?php else: ?>
                <div style="color:#d32f2f;">No dashboard links available for your role.</div>
            <?php endif; ?>
            <a href="../auth/logout.php" class="dashboard-link logout">🚪 Logout</a>
                  <footer class="footer">
            &copy; <span id="current-year"></span> Mzumbe Secondary School
        </footer>
        </div>
    </div>
    <div id="message-box" class="message-box"></div>
    
<script>
    document.getElementById("current-year").textContent = new Date().getFullYear();

    // Particle background 
    var canvas = document.getElementById('particle-canvas');
    var ctx = canvas.getContext('2d');
    var particles = [];
    var particleCount = 60;
    var maxDistance = 100;

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
        for (var i = 0; i < particleCount; i++) particles.push(new Particle());
    }
    function drawLines() {
        for (var i = 0; i < particles.length; i++) {
            for (var j = i + 1; j < particles.length; j++) {
                var a = particles[i];
                var b = particles[j];
                var dx = a.x - b.x;
                var dy = a.y - b.y;
                var distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < maxDistance) {
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.strokeStyle = 'rgba(255, 255, 255, ' + (1 - distance / maxDistance) + ')';
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
        for (var k = 0; k < particles.length; k++) {
            particles[k].update();
            particles[k].draw();
        }
    }
    window.onload = function() { resizeCanvas(); animate(); };
    window.addEventListener('resize', resizeCanvas);

    // Show login success message immediately
    <?php if ($showSuccess && !empty($success)): ?>
        document.getElementById('message-box').textContent = "<?php echo addslashes($success); ?>";
        document.getElementById('message-box').style.display = "block";
        setTimeout(() => {
            document.getElementById('message-box').style.display = "none";
        }, 2500);
    <?php endif; ?>

    // Auto logout warning before session expires (2:55 min)
    setTimeout(function() {
        var box = document.getElementById('message-box');
        box.textContent = "⏰ You will be logged out in 5 seconds due to inactivity.";
        box.style.display = "block";
        box.style.opacity = "1";
        setTimeout(function() {
            box.style.opacity = "0";
            box.style.display = "none";
        }, 5000);
    }, 175000); // 2 min 55 sec

    // Auto logout after 3 minutes (3 min)
    setTimeout(function() {
        window.location.href = "../auth/logout.php?timeout=1";
    }, 180000); // 3 min
</script>
<?php if (isset($_GET['timeout'])): ?>
<script>
    // Show timeout message if redirected due to inactivity
    var box = document.getElementById('message-box');
    box.textContent = "You have been logged out due to inactivity.";
    box.style.display = "block";
    box.style.opacity = "1";
    setTimeout(function() {
        box.style.opacity = "0";
        box.style.display = "none";
    }, 2500);
</script>
<?php endif; ?>
</body>
</html>


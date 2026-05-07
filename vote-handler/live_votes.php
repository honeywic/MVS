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

// CSRF token for AJAX
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Votes</title>
    <link rel="stylesheet" href="../assets/css/live-votes-styles.css">
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
        <div class="dashboard-container">
            <div class="dashboard-header">LIVE VOTES</div>
            <div class="section-title">REGISTERED VOTERS</div>
            <div class="count-number" id="registered-count">0</div>
            <div class="divider"></div>
            <div class="section-title">VOTED</div>
            <div class="count-number" id="voted-count">0</div>
            <p class="info-text">
            Back to <a href="../manage/dashboard.php">Dashboard</a>
           </p>
        </div>
    </main>
    <script>
        function updateLiveVotes() {
            fetch('get_vote_stats.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) return;
                document.getElementById('registered-count').textContent = data.total_voters;
                document.getElementById('voted-count').textContent = data.voted_count;
            })
            .catch(() => {
                // Optionally handle error
            });
        }
        updateLiveVotes();
        setInterval(updateLiveVotes, 1000); // Update every second
    </script>
    <script src="../assets/js/live-votes-script.js"></script>
</body>
</html>
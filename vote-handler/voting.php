<?php
session_start();
require_once '../config/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Server configuration error: PDO not initialized.';
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../auth/login.php');
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$message = '';
$messageType = '';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch candidates grouped by position
$position_candidates = [];
try {
    $fetch = $pdo->prepare('
        SELECT p.position_id, p.position_name, c.candidate_id, c.candidate_name, c.slogan, c.votes_count
        FROM positions p
        LEFT JOIN candidates c ON p.position_id = c.position_id
        ORDER BY p.position_name, c.candidate_name
    ');
    $fetch->execute();
    $results = $fetch->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $position_candidates[$row['position_name']][] = $row;
    }
} catch (PDOException $e) {
    $message = '❌ Failed to load candidates: ' . htmlspecialchars($e->getMessage());
    $messageType = 'error';
}

if ($role === 'voter') {

    $voted_positions = [];
    try {
        $voted_stmt = $pdo->prepare('SELECT position_id FROM votes WHERE voter_id = ?');
        $voted_stmt->execute([$user_id]);
        $voted = $voted_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $voted_positions = array_flip($voted);
    } catch (PDOException $e) {
        $message = '❌ Failed to load voting status: ' . htmlspecialchars($e->getMessage());
        $messageType = 'error';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $votes = isset($_POST['candidate']) ? $_POST['candidate'] : [];
        $voted_count = 0;

        $expected_count = 0;
        foreach ($position_candidates as $pos_name => $cands) {
            if (!empty($cands) && isset($cands[0]['position_id'])) {
                $pos_id = $cands[0]['position_id'];
                if (!isset($voted_positions[$pos_id])) {
                    $expected_count++;
                }
            }
        }

        if (count($votes) != $expected_count) {
            $message = '⚠️ Please vote for all positions before submitting.';
            $messageType = 'warning';
        } else {
            try {
                $pdo->beginTransaction();

                foreach ($votes as $position_id => $candidate_id) {
                    $position_id = (int)$position_id;
                    $candidate_id = (int)$candidate_id;

                    
                    $check_vote = $pdo->prepare('SELECT vote_id FROM votes WHERE voter_id = ? AND position_id = ? LIMIT 1');
                    $check_vote->execute([$user_id, $position_id]);
                    if ($check_vote->fetch()) {
                        continue; 
                    }

                    $check_candidate = $pdo->prepare('SELECT candidate_id FROM candidates WHERE candidate_id = ? AND position_id = ? LIMIT 1');
                    $check_candidate->execute([$candidate_id, $position_id]);
                    if (!$check_candidate->fetch()) {
                        continue;
                    }

                    $ins_vote = $pdo->prepare('INSERT INTO votes (voter_id, candidate_id, position_id, vote_time) VALUES (?, ?, ?, NOW())');
                    $ins_vote->execute([$user_id, $candidate_id, $position_id]);

                    $update_count = $pdo->prepare('UPDATE candidates SET votes_count = votes_count + 1 WHERE candidate_id = ?');
                    $update_count->execute([$candidate_id]);

                    $voted_count++;
                }

                $pdo->commit();

                if ($voted_count > 0) {
                    $message = "✅ Successfully submitted $voted_count vote(s)";
                    $messageType = 'success';
                } else {
                    $message = '⚠️ already voted for all positions.';
                    $messageType = 'warning';
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $message = '❌ Failed to submit votes: ' . htmlspecialchars($e->getMessage());
                $messageType = 'error';
            }
        }
    }
}

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_pdf']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    try {
        require '../fpdf/fpdf.php';
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(18, 18, 18);
        $pdf->AddPage();

        $logoWidth = 28;
        $logoHeight = 28;
        $pageWidth = $pdf->GetPageWidth();
        $logoX = ($pageWidth - $logoWidth) / 2;
        $pdf->Image('../assets/images/mzumbe.png', $logoX, 12, $logoWidth, $logoHeight);

        $pdf->SetY(42);

        // Header Section
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(30, 60, 130);
        $pdf->Cell(0, 12, 'MZUMBE SECONDARY SCHOOL', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'STUDENTS GOVERNMENT', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 13);
        $pdf->Cell(0, 10, 'ELECTION REPORT, YEAR 2025', 0, 1, 'C');
        $pdf->Ln(2);

        // Add more info
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->MultiCell(0, 8, "This document presents the official results and statistics of the Mzumbe Secondary School Students Government Election for the year 2025. It includes the number of registered voters, voting turnout by class, and the results for each candidate and position.\n\nPrepared by: Mzumbe Voting System\nDate: " . date('d M Y'), 0, 'C');
        $pdf->Ln(2);

        // Draw a line
        $pdf->SetDrawColor(30, 60, 130);
        $pdf->SetLineWidth(0.7);
        $pdf->Line(18, $pdf->GetY(), 192, $pdf->GetY());
        $pdf->Ln(8);

        // VOTERS SECTION
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, 'VOTERS STATISTICS BY CLASS', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(30, 60, 130);
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(50, 9, 'CLASS', 1, 0, 'C', true);
        $pdf->Cell(50, 9, 'REGISTERED', 1, 0, 'C', true);
        $pdf->Cell(50, 9, 'VOTED', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(44, 62, 80);

        $classes = [
            'FORM ONE', 'FORM TWO', 'FORM THREE', 'FORM FOUR',
            'FORM FIVE HGL', 'FORM FIVE PMC', 'FORM FIVE PCM', 'FORM FIVE PCB',
            'FORM SIX HGL', 'FORM SIX PCM', 'FORM SIX PCB', 'FORM SIX PMC'
        ];
        $total_registered = 0;
        $total_voted = 0;

        foreach ($classes as $class) {
            // Registered
            $reg_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM users u
                INNER JOIN classes c ON u.class_id = c.class_id
                WHERE c.class_name = ? AND u.role = 'voter'
            ");
            $reg_stmt->execute([$class]);
            $registered = (int)$reg_stmt->fetchColumn();

            // Voted
            $vote_stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT v.voter_id)
                FROM votes v
                INNER JOIN users u ON v.voter_id = u.user_id
                INNER JOIN classes c ON u.class_id = c.class_id
                WHERE c.class_name = ?
            ");
            $vote_stmt->execute([$class]);
            $voted = (int)$vote_stmt->fetchColumn();

            $total_registered += $registered;
            $total_voted += $voted;

            $pdf->Cell(50, 8, $class, 1, 0, 'C');
            $pdf->Cell(50, 8, $registered, 1, 0, 'C');
            $pdf->Cell(50, 8, $voted, 1, 1, 'C');
        }
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(50, 9, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell(50, 9, $total_registered, 1, 0, 'C', true);
        $pdf->Cell(50, 9, $total_voted, 1, 1, 'C', true);

        $pdf->Ln(12);

        // SUMMARY SECTION
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(30, 60, 130);
        $pdf->Cell(0, 10, 'SUMMARY', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(44, 62, 80);
        $turnout = $total_registered > 0 ? round(($total_voted / $total_registered) * 100, 2) : 0;
        $pdf->MultiCell(0, 8, "Total Registered Voters: $total_registered\nTotal Voted: $total_voted\nOverall Turnout: $turnout%", 0, 'L');
        $pdf->Ln(8);

        // RESULTS SECTION
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, 'CANDIDATE RESULTS', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(30, 60, 130);
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(60, 9, 'CANDIDATE', 1, 0, 'C', true);
        $pdf->Cell(45, 9, 'POST', 1, 0, 'C', true);
        $pdf->Cell(45, 9, 'CLASS', 1, 0, 'C', true);
        $pdf->Cell(20, 9, 'VOTES', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(44, 62, 80);

        $cand_stmt = $pdo->prepare('
            SELECT c.candidate_name, p.position_name, cl.class_name, c.votes_count, c.slogan
            FROM candidates c
            LEFT JOIN positions p ON c.position_id = p.position_id
            LEFT JOIN classes cl ON c.class_id = cl.class_id
            ORDER BY p.position_name, c.candidate_name
        ');
        $cand_stmt->execute();
        $candidates = $cand_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($candidates as $cand) {
            $pdf->Cell(60, 8, $cand['candidate_name'], 1, 0, 'C');
            $pdf->Cell(45, 8, $cand['position_name'], 1, 0, 'C');
            $pdf->Cell(45, 8, $cand['class_name'], 1, 0, 'C');
            $pdf->Cell(20, 8, $cand['votes_count'], 1, 1, 'C');
            // Add slogan below candidate row
            if (!empty($cand['slogan'])) {
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(170, 7, 'Slogan: ' . $cand['slogan'], 1, 1, 'L');
                $pdf->SetFont('Arial', '', 12);
                $pdf->SetTextColor(44, 62, 80);
            }
        }

        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, 'Report generated on: ' . date('d M Y, H:i'), 0, 1, 'R');

        // Footer: Copyright and contact
        $pdf->SetY(-30);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->MultiCell(0, 8, "© " . date('Y') . " Mzumbe Voting System. This document is copyright and may not be reproduced without permission.", 0, 'C');

        $pdf->Output('D', 'election_report_2025.pdf');
        exit;
    } catch (Exception $e) {
        $message = '❌ Failed to generate PDF: ' . htmlspecialchars($e->getMessage());
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Page</title>
<link rel="stylesheet" href="../assets/css/voting-styles.css">
</head>
<body>
    <canvas id="particle-canvas"></canvas>
    <nav class="navbar">
        <a href="../index.php">
            <img src="../assets/images/mzumbe.png" alt="Mzumbe School Logo" class="navbar-logo" />
        </a>
        <h1 class="navbar-title">Voting Page</h1>
    </nav>
    <main>
        <div id="message-box" class="message-box" role="alert"></div>
        <div class="container">
            <?php if (!empty($position_candidates)): ?>
                <?php if ($role === 'voter'): ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <?php foreach ($position_candidates as $position_name => $candidates): ?>
                            <div class="position-section">
                                <div class="position-title"><?php echo htmlspecialchars($position_name); ?></div>
                                <?php $position_id = $candidates[0]['position_id']; ?>
                                <?php $voted = isset($voted_positions[$position_id]); ?>
                                <?php if ($voted): ?>
                                    <p class="vote-count">You have already voted for this position.</p>
                                <?php else: ?>
                                    <?php foreach ($candidates as $candidate): ?>
                                        <?php if ($candidate['candidate_id']): ?>
                                            <label class="candidate-card" for="candidate_<?php echo htmlspecialchars($candidate['candidate_id']); ?>">
                                                <input type="radio" name="candidate[<?php echo htmlspecialchars($position_id); ?>]" value="<?php echo htmlspecialchars($candidate['candidate_id']); ?>" id="candidate_<?php echo htmlspecialchars($candidate['candidate_id']); ?>">
                                                <div class="candidate-info">
                                                    <span class="candidate-name"><?php echo htmlspecialchars($candidate['candidate_name']); ?></span>
                                                    <span class="candidate-slogan"><?php echo htmlspecialchars($candidate['slogan'] ?: 'No slogan'); ?></span>
                                                </div>
                                            </label>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" name="vote" class="submit-btn" disabled>Submit Votes</button>
                    </form>
                <?php else: // Admin view ?>
                    <?php foreach ($position_candidates as $position_name => $candidates): ?>
                        <div class="position-section">
                            <div class="position-title"><?php echo htmlspecialchars($position_name); ?></div>
                            <?php foreach ($candidates as $candidate): ?>
                                <?php if ($candidate['candidate_id']): ?>
                                    <div class="candidate-card">
                                        <div class="candidate-info">
                                            <span class="candidate-name"><?php echo htmlspecialchars($candidate['candidate_name']); ?></span>
                                            <span class="candidate-slogan"><?php echo htmlspecialchars($candidate['slogan'] ?: 'No slogan'); ?></span>
                                        </div>
                                        <span class="vote-count">Votes: <?php echo htmlspecialchars($candidate['votes_count']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="candidate-card">
                                        <span>No candidates available</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <button type="submit" name="download_pdf" class="submit-btn">Download Results</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p>No positions or candidates available.</p>
            <?php endif; ?>
                    <p class="info-text">
            Back to <a href="../manage/dashboard.php">Dashboard</a>
        </p>
        </div>
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

        <?php if (!empty($message)): ?>
            showMessage(<?php echo json_encode(htmlspecialchars($message)); ?>, '<?php echo htmlspecialchars($messageType); ?>');
        <?php endif; ?>

        const form = document.querySelector('form');
        const submitBtn = form ? form.querySelector('button[name="vote"]') : null;
        if (submitBtn) {
            function checkAllVoted() {
                const positions = document.querySelectorAll('.position-section');
                let allVoted = true;
                positions.forEach(pos => {
                    if (!pos.querySelector('.vote-count')) { // Not already voted
                        const selected = pos.querySelector('input[type="radio"]:checked');
                        if (!selected) {
                            allVoted = false;
                        }
                    }
                });
                submitBtn.disabled = !allVoted;
            }
            checkAllVoted();
            const radios = form.querySelectorAll('input[type="radio"]');
            radios.forEach(radio => {
                radio.addEventListener('change', checkAllVoted);
            });
        }
    </script>
    <script src="../assets/js/voting-script.js"></script>
</body>
</html>
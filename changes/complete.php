<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();

$change_id = (int)($_GET['id'] ?? 0);

if ($change_id === 0) {
    $_SESSION['error'] = 'Change request tidak ditemukan.';
    header('Location: list.php');
    exit;
}

// Get change details
$sql = "SELECT * FROM change_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $change_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Change request tidak ditemukan.';
    header('Location: list.php');
    exit;
}

$change = $result->fetch_assoc();

// Check permission
if (!canPerformAction('execute_change', $change)) {
    $_SESSION['error'] = 'Anda tidak memiliki akses untuk menyelesaikan change request ini.';
    header("Location: detail.php?id={$change_id}");
    exit;
}

if ($change['status'] !== 'in_progress') {
    $_SESSION['error'] = 'Change request ini belum dalam status In Progress.';
    header("Location: detail.php?id={$change_id}");
    exit;
}

$page_title = 'Complete Execution';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $completion_status = $_POST['completion_status'] ?? '';
    $completion_notes = trim($_POST['completion_notes'] ?? '');
    
    if (!in_array($completion_status, ['completed', 'failed', 'rolled_back'])) {
        $_SESSION['error'] = 'Invalid completion status.';
    } else {
        $actual_end = date('Y-m-d H:i:s');
        $closure_date = $actual_end;
        
        $sql = "UPDATE change_requests 
                SET status = ?, actual_end = ?, closure_date = ?, completion_notes = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $completion_status, $actual_end, $closure_date, $completion_notes, $change_id);
        
        if ($stmt->execute()) {
            // Log audit
            logAudit($_SESSION['user_id'], 'complete_change', 'change_request', $change_id,
                    json_encode(['status' => 'in_progress']),
                    json_encode(['status' => $completion_status, 'actual_end' => $actual_end])
            );
            
            if ($completion_status === 'completed') {
                $_SESSION['success'] = 'Change request berhasil diselesaikan! 🎉';
            } elseif ($completion_status === 'failed') {
                $_SESSION['info'] = 'Change request ditandai sebagai Failed.';
            } else {
                $_SESSION['info'] = 'Change request di-rollback ke kondisi sebelumnya.';
            }
            
            header("Location: detail.php?id={$change_id}");
            exit;
        } else {
            $_SESSION['error'] = 'Gagal menyelesaikan change request.';
        }
    }
}

include '../includes/header.php';
?>

<h1>Complete Execution</h1>

<div class="card">
    <div class="card-header">
        <h2><?php echo htmlspecialchars($change['change_number']); ?></h2>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h3><?php echo htmlspecialchars($change['title']); ?></h3>
        <p><?php echo getStatusBadge($change['status']); ?></p>
    </div>
    
    <div class="detail-grid" style="margin-bottom: 20px;">
        <div class="detail-item">
            <label>Started At</label>
            <div><?php echo formatTanggal($change['actual_start']); ?></div>
        </div>
        
        <div class="detail-item">
            <label>Duration</label>
            <div>
                <?php 
                $start = new DateTime($change['actual_start']);
                $now = new DateTime();
                $interval = $start->diff($now);
                echo $interval->format('%h hours %i minutes');
                ?>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong>ℹ️ Completion Options:</strong><br>
        <ul style="margin: 10px 0 0 20px;">
            <li><strong>Completed:</strong> Implementasi berhasil dan sistem berjalan normal</li>
            <li><strong>Failed:</strong> Implementasi gagal, tetapi sistem masih aman</li>
            <li><strong>Rolled Back:</strong> Implementasi sudah di-rollback ke kondisi semula</li>
        </ul>
    </div>
    
    <h3>Test Plan</h3>
    <div style="white-space: pre-wrap; background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <?php echo htmlspecialchars($change['test_plan'] ?: 'No test plan provided.'); ?>
    </div>
</div>

<form method="POST" action="">
    <div class="card">
        <div class="card-header">
            <h2>Completion Details</h2>
        </div>
        
        <div class="form-group">
            <label for="completion_status">Completion Status *</label>
            <select id="completion_status" name="completion_status" required>
                <option value="">-- Select Status --</option>
                <option value="completed">✅ Completed Successfully</option>
                <option value="failed">❌ Failed</option>
                <option value="rolled_back">↩️ Rolled Back</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="completion_notes">Completion Notes *</label>
            <textarea id="completion_notes" name="completion_notes" rows="6" required
                      placeholder="Jelaskan hasil implementasi, testing yang dilakukan, dan kondisi sistem saat ini..."></textarea>
            <small>Berikan detail tentang apa yang sudah dilakukan dan hasil akhirnya</small>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-success"
                    onclick="return confirm('Apakah Anda yakin data completion sudah benar?')">
                ✔️ Mark as Complete
            </button>
            <a href="detail.php?id=<?php echo $change_id; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

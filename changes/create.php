<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();

$page_title = 'Create Change Request';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $impact = $_POST['impact'] ?? 'medium';
    $risk_level = $_POST['risk_level'] ?? 'low';
    $systems_affected = trim($_POST['systems_affected'] ?? '');
    $implementation_plan = trim($_POST['implementation_plan'] ?? '');
    $rollback_plan = trim($_POST['rollback_plan'] ?? '');
    $test_plan = trim($_POST['test_plan'] ?? '');
    $planned_start = $_POST['planned_start'] ?? null;
    $planned_end = $_POST['planned_end'] ?? null;
    $submit_action = $_POST['action'] ?? 'draft';
    
    $errors = [];
    
    if (empty($title)) $errors[] = 'Title wajib diisi.';
    if (empty($description)) $errors[] = 'Description wajib diisi.';
    if (empty($category)) $errors[] = 'Category wajib dipilih.';
    
    if (empty($errors)) {
        // Generate change number
        $change_number = generateChangeNumber();
        
        // Tentukan status
        $status = ($submit_action === 'submit') ? 'submitted' : 'draft';
        
        // Insert ke database
        $sql = "INSERT INTO change_requests (
                    change_number, title, description, category, priority, 
                    impact, risk_level, systems_affected, implementation_plan, 
                    rollback_plan, test_plan, planned_start, planned_end, 
                    status, requester_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $requester_id = $_SESSION['user_id'];
        
        $stmt->bind_param("ssssssssssssssi", 
            $change_number, $title, $description, $category, $priority,
            $impact, $risk_level, $systems_affected, $implementation_plan,
            $rollback_plan, $test_plan, $planned_start, $planned_end,
            $status, $requester_id
        );
        
        if ($stmt->execute()) {
            $change_id = $conn->insert_id;
            
            // Log audit
            $action = ($status === 'submitted') ? 'submit_change' : 'create_change';
            logAudit($requester_id, $action, 'change_request', $change_id, null, json_encode([
                'change_number' => $change_number,
                'title' => $title,
                'status' => $status
            ]));
            
            $_SESSION['success'] = "Change request berhasil dibuat dengan nomor {$change_number}.";
            header("Location: detail.php?id={$change_id}");
            exit;
        } else {
            $errors[] = 'Gagal menyimpan change request.';
        }
    }
}

include '../includes/header.php';
?>

<h1>Create Change Request</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul style="margin: 0; padding-left: 20px;">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="" id="changeForm">
    <div class="card">
        <div class="card-header">
            <h2>Basic Information</h2>
        </div>
        
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required 
                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                   placeholder="e.g., Upgrade Database Server to MySQL 8.0">
            <small>Judul yang jelas dan deskriptif</small>
        </div>
        
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required 
                      rows="5" placeholder="Jelaskan detail perubahan yang akan dilakukan..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">-- Pilih Category --</option>
                    <option value="software" <?php echo ($_POST['category'] ?? '') === 'software' ? 'selected' : ''; ?>>Software</option>
                    <option value="hardware" <?php echo ($_POST['category'] ?? '') === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                    <option value="network" <?php echo ($_POST['category'] ?? '') === 'network' ? 'selected' : ''; ?>>Network</option>
                    <option value="security" <?php echo ($_POST['category'] ?? '') === 'security' ? 'selected' : ''; ?>>Security</option>
                    <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="priority">Priority *</label>
                <select id="priority" name="priority">
                    <option value="low" <?php echo ($_POST['priority'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo ($_POST['priority'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo ($_POST['priority'] ?? 'medium') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="impact">Impact Level *</label>
                <select id="impact" name="impact">
                    <option value="low" <?php echo ($_POST['impact'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo ($_POST['impact'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo ($_POST['impact'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="risk_level">Risk Level *</label>
                <select id="risk_level" name="risk_level">
                    <option value="low" <?php echo ($_POST['risk_level'] ?? 'low') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo ($_POST['risk_level'] ?? 'low') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo ($_POST['risk_level'] ?? 'low') === 'high' ? 'selected' : ''; ?>>High</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Technical Details</h2>
        </div>
        
        <div class="form-group">
            <label for="systems_affected">Systems Affected</label>
            <textarea id="systems_affected" name="systems_affected" rows="3" 
                      placeholder="List sistem/aplikasi yang akan terpengaruh..."><?php echo htmlspecialchars($_POST['systems_affected'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="implementation_plan">Implementation Plan</label>
            <textarea id="implementation_plan" name="implementation_plan" rows="5" 
                      placeholder="Step-by-step cara implementasi..."><?php echo htmlspecialchars($_POST['implementation_plan'] ?? ''); ?></textarea>
            <small>Jelaskan langkah-langkah yang akan dilakukan</small>
        </div>
        
        <div class="form-group">
            <label for="rollback_plan">Rollback Plan</label>
            <textarea id="rollback_plan" name="rollback_plan" rows="5" 
                      placeholder="Cara rollback jika terjadi masalah..."><?php echo htmlspecialchars($_POST['rollback_plan'] ?? ''); ?></textarea>
            <small>Rencana untuk mengembalikan ke kondisi semula jika gagal</small>
        </div>
        
        <div class="form-group">
            <label for="test_plan">Test Plan</label>
            <textarea id="test_plan" name="test_plan" rows="4" 
                      placeholder="Cara testing setelah implementasi..."><?php echo htmlspecialchars($_POST['test_plan'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2>Scheduling (Optional)</h2>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="planned_start">Planned Start</label>
                <input type="datetime-local" id="planned_start" name="planned_start" 
                       value="<?php echo $_POST['planned_start'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="planned_end">Planned End</label>
                <input type="datetime-local" id="planned_end" name="planned_end" 
                       value="<?php echo $_POST['planned_end'] ?? ''; ?>">
            </div>
        </div>
    </div>
    
    <div class="btn-group">
        <button type="submit" name="action" value="draft" class="btn btn-secondary">
            💾 Save as Draft
        </button>
        <button type="submit" name="action" value="submit" class="btn btn-primary">
            📤 Submit for Approval
        </button>
        <a href="list.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

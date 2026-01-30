<?php
require_once 'config/database.php';
require_once 'auth/auth_check.php';

requireLogin();

$page_title = 'Dashboard';
$user = getCurrentUser();

// Get statistics berdasarkan role
$stats = [];

if (canPerformAction('view_all_changes')) {
    // Manager & Admin - lihat semua
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM change_requests";
    $result = $conn->query($sql);
    $stats = $result->fetch_assoc();
    
} else {
    // Staff & Client - hanya milik sendiri
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM change_requests
            WHERE requester_id = ? OR assigned_to = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
}

// Get recent changes
if (canPerformAction('view_all_changes')) {
    $sql = "SELECT cr.*, u.full_name as requester_name 
            FROM change_requests cr
            JOIN users u ON cr.requester_id = u.id
            ORDER BY cr.created_at DESC
            LIMIT 10";
    $recent = $conn->query($sql);
} else {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT cr.*, u.full_name as requester_name 
            FROM change_requests cr
            JOIN users u ON cr.requester_id = u.id
            WHERE cr.requester_id = ? OR cr.assigned_to = ?
            ORDER BY cr.created_at DESC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $recent = $stmt->get_result();
}

include 'includes/header.php';
?>

<h1>Dashboard</h1>
<p style="color: #888; margin-bottom: 30px;">
    Selamat datang, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> 
    (<?php echo getRoleName($user['role']); ?>)
</p>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['total']; ?></h3>
        <p>Total Change Requests</p>
    </div>
    
    <div class="stat-card warning">
        <h3><?php echo $stats['pending_approval']; ?></h3>
        <p>Pending Approval</p>
    </div>
    
    <div class="stat-card info">
        <h3><?php echo $stats['in_progress']; ?></h3>
        <p>In Progress</p>
    </div>
    
    <div class="stat-card success">
        <h3><?php echo $stats['completed']; ?></h3>
        <p>Completed</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2>Quick Actions</h2>
    </div>
    <div class="btn-group">
        <a href="changes/create.php" class="btn btn-primary">
            ➕ New Change Request
        </a>
        
        <a href="changes/list.php" class="btn btn-secondary">
            📋 View All Changes
        </a>
        
        <?php if (canPerformAction('view_audit_logs')): ?>
        <a href="logs/audit.php" class="btn btn-secondary">
            📊 Audit Logs
        </a>
        <?php endif; ?>
        
        <?php if (canPerformAction('manage_users')): ?>
        <a href="admin/users.php" class="btn btn-secondary">
            👥 Manage Users
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Changes -->
<div class="card">
    <div class="card-header">
        <h2>Recent Change Requests</h2>
    </div>
    
    <?php if ($recent->num_rows > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Change Number</th>
                <th>Title</th>
                <th>Requester</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['change_number']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['requester_name']); ?></td>
                <td><?php echo getPriorityBadge($row['priority']); ?></td>
                <td><?php echo getStatusBadge($row['status']); ?></td>
                <td><?php echo formatTanggal($row['created_at']); ?></td>
                <td>
                    <a href="changes/detail.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.85em;">
                        View
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Belum ada change request.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// Role-specific notifications
if ($user['role'] === 'manager') {
    $sql = "SELECT COUNT(*) as count FROM change_requests WHERE status = 'submitted'";
    $result = $conn->query($sql);
    $pending = $result->fetch_assoc()['count'];
    
    if ($pending > 0):
?>
<div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107;">
    <h3>⚠️ Action Required</h3>
    <p>Ada <strong><?php echo $pending; ?></strong> change request yang menunggu approval Anda.</p>
    <a href="changes/list.php?status=submitted" class="btn btn-warning">Review Now</a>
</div>
<?php 
    endif;
}

if ($user['role'] === 'staff') {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COUNT(*) as count FROM change_requests 
            WHERE assigned_to = ? AND status IN ('approved', 'scheduled')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assigned = $result->fetch_assoc()['count'];
    
    if ($assigned > 0):
?>
<div class="card" style="background: #d1ecf1; border-left: 4px solid #17a2b8;">
    <h3>📌 Your Tasks</h3>
    <p>Ada <strong><?php echo $assigned; ?></strong> change request yang ditugaskan kepada Anda.</p>
    <a href="changes/list.php?assigned_to=me" class="btn btn-info">View Tasks</a>
</div>
<?php 
    endif;
}

include 'includes/footer.php';
?>

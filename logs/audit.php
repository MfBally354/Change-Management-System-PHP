<?php
require_once '../config/database.php';
require_once '../auth/auth_check.php';

requireLogin();
requireRole(['admin', 'manager']);

$page_title = 'Audit Logs';

// Filters
$user_filter = $_GET['user_id'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = '';

$sql = "SELECT al.*, u.full_name as user_name, u.role as user_role
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id";

if (!empty($user_filter)) {
    $where_clauses[] = "al.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if (!empty($action_filter)) {
    $where_clauses[] = "al.action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(al.created_at) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(al.created_at) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY al.created_at DESC LIMIT 500";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $logs = $stmt->get_result();
} else {
    $logs = $conn->query($sql);
}

// Get users for filter
$users = $conn->query("SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name");

// Get unique actions for filter
$actions = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");

include '../includes/header.php';
?>

<h1>Audit Logs</h1>
<p style="color: #888; margin-bottom: 20px;">
    Tracking semua aktivitas pengguna dalam sistem
</p>

<!-- Filters -->
<div class="card">
    <div class="card-header">
        <h2>Filters</h2>
    </div>
    
    <form method="GET" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="user_id">User</label>
                <select id="user_id" name="user_id">
                    <option value="">-- All Users --</option>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="action">Action</label>
                <select id="action" name="action">
                    <option value="">-- All Actions --</option>
                    <?php while ($act = $actions->fetch_assoc()): ?>
                    <option value="<?php echo $act['action']; ?>" <?php echo $action_filter === $act['action'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($act['action']))); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
        </div>
        
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="audit.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Results -->
<div class="card">
    <div class="card-header">
        <h2>Audit Trail (<?php echo $logs->num_rows; ?> records)</h2>
    </div>
    
    <?php if ($logs->num_rows > 0): ?>
    <table class="table">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>User</th>
                <th>Action</th>
                <th>Entity</th>
                <th>IP Address</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($log = $logs->fetch_assoc()): ?>
            <tr>
                <td style="white-space: nowrap;">
                    <?php echo formatTanggal($log['created_at']); ?>
                </td>
                <td>
                    <?php if ($log['user_name']): ?>
                        <?php echo htmlspecialchars($log['user_name']); ?><br>
                        <small class="badge badge-secondary"><?php echo getRoleName($log['user_role']); ?></small>
                    <?php else: ?>
                        <em style="color: #999;">System</em>
                    <?php endif; ?>
                </td>
                <td>
                    <span style="font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">
                        <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($log['entity_type']): ?>
                        <?php echo htmlspecialchars($log['entity_type']); ?>
                        <?php if ($log['entity_id']): ?>
                            #<?php echo $log['entity_id']; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <small style="font-family: monospace;"><?php echo htmlspecialchars($log['ip_address']); ?></small>
                </td>
                <td>
                    <?php if ($log['new_value']): ?>
                        <button class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8em;"
                                onclick="alert('<?php echo htmlspecialchars(str_replace("'", "\\'", $log['new_value'])); ?>')">
                            View
                        </button>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Tidak ada audit logs yang sesuai dengan filter.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Summary Statistics -->
<div class="stats-grid">
    <?php
    // Get today's activity count
    $today = $conn->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc();
    
    // Get this week's activity count
    $week = $conn->query("SELECT COUNT(*) as count FROM audit_logs WHERE YEARWEEK(created_at) = YEARWEEK(NOW())")->fetch_assoc();
    
    // Get most active user today
    $active_user = $conn->query("
        SELECT u.full_name, COUNT(*) as count 
        FROM audit_logs al
        JOIN users u ON al.user_id = u.id
        WHERE DATE(al.created_at) = CURDATE()
        GROUP BY al.user_id
        ORDER BY count DESC
        LIMIT 1
    ")->fetch_assoc();
    ?>
    
    <div class="stat-card info">
        <h3><?php echo $today['count']; ?></h3>
        <p>Activities Today</p>
    </div>
    
    <div class="stat-card success">
        <h3><?php echo $week['count']; ?></h3>
        <p>Activities This Week</p>
    </div>
    
    <?php if ($active_user): ?>
    <div class="stat-card">
        <h3><?php echo $active_user['count']; ?></h3>
        <p>Most Active: <?php echo htmlspecialchars($active_user['full_name']); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

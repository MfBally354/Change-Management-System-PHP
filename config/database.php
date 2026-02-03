<?php
// Config Database Connection
// Sesuaikan dengan environment kamu

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // atau user MySQL kamu
define('DB_PASS', ''); // password MySQL
define('DB_NAME', 'change_management');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Koneksi database
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper function untuk prepared statement
function executeQuery($sql, $params = [], $types = "") {
    global $conn;
    
    if (empty($params)) {
        return $conn->query($sql);
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// Helper function untuk audit log
function logAudit($user_id, $action, $entity_type = null, $entity_id = null, $old_value = null, $new_value = null) {
    global $conn;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ississss", 
        $user_id, $action, $entity_type, $entity_id, 
        $old_value, $new_value, $ip, $user_agent
    );
    $stmt->execute();
}

// Helper function untuk generate change number
function generateChangeNumber() {
    global $conn;
    
    $year = date('Y');
    $prefix = "CR-{$year}-";
    
    // Cari nomor terakhir tahun ini
    $sql = "SELECT change_number FROM change_requests 
            WHERE change_number LIKE '{$prefix}%' 
            ORDER BY id DESC LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = (int)substr($row['change_number'], -4);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

// Helper untuk format tanggal Indonesia
function formatTanggal($datetime) {
    if (empty($datetime)) return '-';
    
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    
    $timestamp = strtotime($datetime);
    $tgl = date('d', $timestamp);
    $bln = $bulan[(int)date('m', $timestamp)];
    $thn = date('Y', $timestamp);
    $jam = date('H:i', $timestamp);
    
    return "{$tgl} {$bln} {$thn} {$jam}";
}

// Helper untuk badge status
function getStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge badge-secondary">Draft</span>',
        'submitted' => '<span class="badge badge-info">Submitted</span>',
        'reviewing' => '<span class="badge badge-warning">Reviewing</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'scheduled' => '<span class="badge badge-primary">Scheduled</span>',
        'in_progress' => '<span class="badge badge-warning">In Progress</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
        'failed' => '<span class="badge badge-danger">Failed</span>',
        'rolled_back' => '<span class="badge badge-danger">Rolled Back</span>',
        'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
    ];
    
    return $badges[$status] ?? $status;
}

// Helper untuk badge priority
function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge badge-success">Low</span>',
        'medium' => '<span class="badge badge-warning">Medium</span>',
        'high' => '<span class="badge badge-danger">High</span>',
        'critical' => '<span class="badge badge-danger-dark">Critical</span>'
    ];
    
    return $badges[$priority] ?? $priority;
}

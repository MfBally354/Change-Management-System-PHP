<?php
// Auth Check - Cek login dan role user
session_start();

// Fungsi untuk cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fungsi untuk cek role user
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

// Fungsi untuk redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

// Fungsi untuk redirect jika role tidak sesuai
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini.";
        header('Location: /index.php');
        exit;
    }
}

// Fungsi untuk mendapatkan info user yang sedang login
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    require_once __DIR__ . '/../config/database.php';
    
    $sql = "SELECT id, username, full_name, email, role, department FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Fungsi untuk cek permission berdasarkan action
function canPerformAction($action, $change_request = null) {
    if (!isLoggedIn()) return false;
    
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    switch ($action) {
        case 'create_change':
            // Semua role bisa buat change request
            return true;
            
        case 'edit_change':
            // Hanya requester atau admin
            if ($role === 'admin') return true;
            if ($change_request && $change_request['requester_id'] == $user_id) {
                return in_array($change_request['status'], ['draft', 'rejected']);
            }
            return false;
            
        case 'submit_change':
            // Hanya requester
            if ($change_request && $change_request['requester_id'] == $user_id) {
                return $change_request['status'] === 'draft';
            }
            return false;
            
        case 'approve_change':
            // Manager atau admin
            return in_array($role, ['manager', 'admin']);
            
        case 'assign_change':
            // Manager atau admin
            return in_array($role, ['manager', 'admin']);
            
        case 'execute_change':
            // IT staff yang ditugaskan, atau admin
            if ($role === 'admin') return true;
            if ($role === 'staff' && $change_request) {
                return $change_request['assigned_to'] == $user_id;
            }
            return false;
            
        case 'view_all_changes':
            // Manager dan admin bisa lihat semua
            return in_array($role, ['manager', 'admin']);
            
        case 'manage_users':
            // Hanya admin
            return $role === 'admin';
            
        case 'view_audit_logs':
            // Admin dan manager
            return in_array($role, ['admin', 'manager']);
            
        default:
            return false;
    }
}

// Fungsi untuk display role dalam bahasa Indonesia
function getRoleName($role) {
    $roles = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'staff' => 'IT Staff',
        'client' => 'Client/User'
    ];
    
    return $roles[$role] ?? $role;
}

// Helper untuk menampilkan pesan flash
function displayFlashMessage() {
    $output = '';
    
    if (isset($_SESSION['success'])) {
        $output .= '<div class="alert alert-success alert-dismissible">';
        $output .= '<button type="button" class="close" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        $output .= $_SESSION['success'];
        $output .= '</div>';
        unset($_SESSION['success']);
    }
    
    if (isset($_SESSION['error'])) {
        $output .= '<div class="alert alert-danger alert-dismissible">';
        $output .= '<button type="button" class="close" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        $output .= $_SESSION['error'];
        $output .= '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['info'])) {
        $output .= '<div class="alert alert-info alert-dismissible">';
        $output .= '<button type="button" class="close" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        $output .= $_SESSION['info'];
        $output .= '</div>';
        unset($_SESSION['info']);
    }
    
    return $output;
}

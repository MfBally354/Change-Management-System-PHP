-- Change Management System Database
-- Buat database dulu ya...
CREATE DATABASE IF NOT EXISTS change_management;
USE change_management;

-- 1. Tabel Users (Admin, Manager, IT Staff, Client)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'manager', 'staff', 'client') NOT NULL DEFAULT 'client',
    department VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabel Change Requests (inti sistem)
CREATE TABLE IF NOT EXISTS change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_number VARCHAR(20) UNIQUE NOT NULL, -- CR-2025-0001
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('software', 'hardware', 'network', 'security', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    impact ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    risk_level ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'low',
    
    -- Informasi teknis
    systems_affected TEXT, -- sistem yang kena dampak
    implementation_plan TEXT, -- cara implementasi
    rollback_plan TEXT, -- cara rollback kalau gagal
    test_plan TEXT, -- cara testing
    
    -- Scheduling
    planned_start DATETIME,
    planned_end DATETIME,
    actual_start DATETIME,
    actual_end DATETIME,
    
    -- Status workflow
    status ENUM(
        'draft',        -- baru dibuat
        'submitted',    -- sudah disubmit
        'reviewing',    -- sedang direview
        'approved',     -- disetujui
        'rejected',     -- ditolak
        'scheduled',    -- dijadwalkan
        'in_progress',  -- sedang dikerjakan
        'completed',    -- selesai sukses
        'failed',       -- gagal
        'rolled_back',  -- di-rollback
        'cancelled'     -- dibatalkan
    ) NOT NULL DEFAULT 'draft',
    
    -- Who's who
    requester_id INT NOT NULL,
    assigned_to INT, -- IT staff yang mengerjakan
    approved_by INT,
    
    -- Closure
    completion_notes TEXT,
    closure_date DATETIME,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at)
);

-- 3. Tabel Approvals (tracking approval process)
CREATE TABLE IF NOT EXISTS change_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id INT NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    comments TEXT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (change_id) REFERENCES change_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id),
    INDEX idx_change_status (change_id, status)
);

-- 4. Tabel Comments/Discussion
CREATE TABLE IF NOT EXISTS change_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0, -- internal notes vs public
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (change_id) REFERENCES change_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_change_id (change_id)
);

-- 5. Tabel Audit Logs (setiap aktivitas tercatat)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL, -- login, create_change, approve, etc
    entity_type VARCHAR(50), -- change_request, user, etc
    entity_id INT,
    old_value TEXT, -- nilai sebelum diubah (JSON)
    new_value TEXT, -- nilai sesudah diubah (JSON)
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created_at (created_at)
);

-- 6. Tabel Attachments (opsional, untuk lampiran)
CREATE TABLE IF NOT EXISTS change_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    filesize INT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (change_id) REFERENCES change_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_change_id (change_id)
);

-- Data awal: Buat user default
INSERT INTO users (username, password, full_name, email, role, department) VALUES
-- Password: admin123
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@company.com', 'admin', 'IT'),
-- Password: manager123
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Manager', 'manager@company.com', 'manager', 'IT'),
-- Password: staff123
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Staff', 'staff@company.com', 'staff', 'IT'),
-- Password: client123
('client1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Client', 'client@company.com', 'client', 'Finance');

-- Sample change request untuk demo
INSERT INTO change_requests (
    change_number, title, description, category, priority, impact, risk_level,
    systems_affected, implementation_plan, rollback_plan,
    requester_id, status, created_at
) VALUES (
    'CR-2025-0001',
    'Upgrade Server Database MySQL 8.0',
    'Upgrade database server dari MySQL 5.7 ke 8.0 untuk performa lebih baik dan fitur terbaru',
    'software',
    'high',
    'high',
    'medium',
    'Database Server, All Applications',
    '1. Backup full database\n2. Install MySQL 8.0\n3. Migrate data\n4. Test connections',
    '1. Stop MySQL 8.0\n2. Restore dari backup\n3. Start MySQL 5.7',
    3,
    'submitted',
    NOW()
);

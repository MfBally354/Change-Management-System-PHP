# Change Management System 🔧

Sistem IT Change Management lengkap dengan multi-role authentication, approval workflow, dan audit trail.

## 📋 Fitur Utama

### 1. **Multi-Role System**
- **Admin**: Kelola semua aspek sistem, user management, lihat semua change requests
- **Manager**: Review & approve change requests, assign ke IT staff
- **IT Staff**: Eksekusi change requests yang ditugaskan
- **Client/User**: Buat dan track change requests sendiri

### 2. **Change Request Lifecycle**
```
Draft → Submitted → Reviewing → Approved → Scheduled → In Progress → Completed
                      ↓
                   Rejected
```

**Status yang tersedia:**
- `draft` - Masih dalam proses pembuatan
- `submitted` - Sudah disubmit untuk approval
- `reviewing` - Sedang direview
- `approved` - Disetujui manager
- `rejected` - Ditolak manager
- `scheduled` - Sudah dijadwalkan
- `in_progress` - Sedang dikerjakan IT staff
- `completed` - Selesai berhasil
- `failed` - Gagal
- `rolled_back` - Di-rollback
- `cancelled` - Dibatalkan

### 3. **Audit Trail Lengkap**
Setiap aktivitas tercatat:
- Login/Logout
- Create/Edit/Delete
- Approve/Reject
- Execute/Complete
- Comments

### 4. **Fitur Tambahan**
- Priority Management (Low, Medium, High, Critical)
- Impact & Risk Assessment
- Implementation & Rollback Plans
- Comments & Discussion
- Scheduled Maintenance Window
- Completion Notes

---

## 🚀 Instalasi

### Requirements
- Apache Web Server
- PHP 7.4 atau lebih baru
- MySQL/MariaDB 5.7+
- Extension: mysqli, pdo_mysql

### Langkah-langkah Install

#### 1. **Clone/Copy Files**
```bash
# Via SSH
ssh user@your-server
cd /var/www/
git clone https://github.com/MfBally354/Change-Management-System-PHP.git 
# atau copy manual files ke /var/www/change-management
```

#### 2. **Setup Database**
```bash
# Login ke MySQL
mysql -u root -p

# Import database
mysql -u root -p < /var/www/change-management/database.sql

# Atau manual:
# - Buka phpMyAdmin
# - Create database 'change_management'
# - Import file database.sql
```

#### 3. **Konfigurasi Database Connection**
Edit file `/var/www/change-management/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // sesuaikan
define('DB_PASS', '[]');  // sesuaikan
define('DB_NAME', 'change_management');
```

#### 4. **Setup Apache Virtual Host**
```bash
sudo nano /etc/apache2/sites-available/change-management.conf
```

Isi dengan:
```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/change-management
    
    <Directory /var/www/change-management>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/change-management-error.log
    CustomLog ${APACHE_LOG_DIR}/change-management-access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite change-management
sudo systemctl reload apache2
```

#### 5. **Set Permissions**
```bash
sudo chown -R www-data:www-data /var/www/change-management
sudo chmod -R 755 /var/www/change-management
```

#### 6. **Akses Aplikasi**
Buka browser: `http://localhost/change-management/` atau `http://your-server-ip/change-management/`

---

## 👥 Default Users

| Username  | Password     | Role      | Description               |
|-----------|--------------|-----------|---------------------------|
| admin     | admin123     | Admin     | Full system access        |
| manager1  | manager123   | Manager   | Approve & assign changes  |
| staff1    | staff123     | IT Staff  | Execute changes           |
| client1   | client123    | Client    | Create & track changes    |

⚠️ **PENTING:** Segera ubah password default setelah first login!

---

## 📖 Cara Penggunaan

### Sebagai **Client/User**

1. **Login** dengan credentials
2. **Dashboard**: Lihat change requests Anda
3. **Create New Change Request**:
   - Klik "New Change Request"
   - Isi form (Title, Description, Category, Priority, dll)
   - **Save as Draft** (simpan sementara) atau **Submit for Approval** (langsung submit)
4. **Track Status**: Lihat progress change request di dashboard atau detail page

### Sebagai **Manager**

1. **Login** sebagai manager
2. **Dashboard**: Lihat pending approvals
3. **Review Change Request**:
   - Klik change request yang status "Submitted"
   - Klik "Review & Approve"
   - Pilih IT Staff yang akan ditugaskan
   - Tambahkan comments (opsional)
   - **Approve** atau **Reject**
4. **Monitor**: Track semua change requests di List page

### Sebagai **IT Staff**

1. **Login** sebagai staff
2. **Dashboard**: Lihat tasks yang assigned ke Anda
3. **Execute Change**:
   - Klik change request yang assigned dan status "Approved"
   - Klik "Start Execution" → status jadi "In Progress"
   - Lakukan implementasi sesuai Implementation Plan
   - Klik "Mark Complete"
   - Pilih status: **Completed**, **Failed**, atau **Rolled Back**
   - Isi Completion Notes
4. **Comments**: Tambahkan update/progress di comment section

### Sebagai **Admin**

1. **Login** sebagai admin
2. **Full Access** ke semua fitur:
   - View semua change requests
   - Edit/Delete (jika diperlukan)
   - User Management → Create, Activate/Deactivate users
   - Audit Logs → Track semua aktivitas
   - Reset password user

---

## 🗂️ Struktur Folder

```
change-management/
├── index.php              # Dashboard
├── login.php              # Login page
├── logout.php             # Logout handler
├── database.sql           # Database schema & sample data
│
├── config/
│   └── database.php       # Database connection & helpers
│
├── auth/
│   └── auth_check.php     # Authentication & authorization
│
├── changes/               # Change Request module
│   ├── create.php         # Create new change
│   ├── list.php           # List all changes (with filters)
│   ├── detail.php         # View change details
│   ├── approve.php        # Approve/reject change
│   ├── execute.php        # Start execution
│   ├── complete.php       # Mark as complete
│   └── add_comment.php    # Add comment handler
│
├── logs/
│   └── audit.php          # Audit logs viewer
│
├── admin/
│   └── users.php          # User management
│
├── assets/
│   └── style.css          # Styling
│
└── includes/
    ├── header.php         # Header template
    └── footer.php         # Footer template
```

---

## 🔐 Security Features

1. **Password Hashing**: Menggunakan PHP `password_hash()` dengan bcrypt
2. **SQL Injection Prevention**: Prepared statements di semua query
3. **Session Management**: Secure session handling
4. **Role-Based Access Control**: Permission check di setiap halaman
5. **Audit Trail**: Semua aktivitas tercatat dengan IP & user agent
6. **XSS Prevention**: `htmlspecialchars()` di semua output

---

## 🎨 Customization

### Mengubah Warna Theme
Edit `/assets/style.css`, cari gradient colors:
```css
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Menambah Category Baru
Edit `database.sql` dan file `/changes/create.php`:
```sql
category ENUM('software', 'hardware', 'network', 'security', 'database', 'other')
```

### Menambah Status Baru
Edit enum di database dan tambahkan badge di `config/database.php`:
```php
function getStatusBadge($status) {
    // tambah status baru di sini
}
```

---

## 📊 Database Schema

### Tabel Utama

1. **users** - User accounts & roles
2. **change_requests** - Change request data (inti sistem)
3. **change_approvals** - Approval history
4. **change_comments** - Comments & discussion
5. **audit_logs** - Activity tracking
6. **change_attachments** - File uploads (opsional)

---

## 🐛 Troubleshooting

### "Connection failed" saat akses
- Cek konfigurasi database di `config/database.php`
- Pastikan MySQL service running: `sudo systemctl status mysql`

### "Permission denied"
```bash
sudo chown -R www-data:www-data /var/www/change-management
```

### Lupa password admin
Update manual di database:
```sql
-- Password: newpassword123
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
```

### Session tidak persistent
Edit `php.ini`:
```ini
session.gc_maxlifetime = 3600
session.cookie_lifetime = 3600
```

---

## 🔄 Backup & Restore

### Backup Database
```bash
mysqldump -u root -p change_management > backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -u root -p change_management < backup_20250130.sql
```

---

## 📈 Future Enhancements

Ide pengembangan selanjutnya:
- [ ] Email notifications
- [ ] File attachments
- [ ] Calendar view untuk scheduled changes
- [ ] API REST untuk integrasi
- [ ] Dashboard analytics/charts
- [ ] Export to PDF/Excel
- [ ] Mobile responsive improvements
- [ ] Multi-language support

---

## 📝 License

This project is open source. Feel free to use and modify.

---

## 👨‍💻 Developer Notes

**Tech Stack:**
- Backend: PHP 7.4+, MySQL
- Frontend: Vanilla JavaScript, CSS3
- No frameworks - pure PHP for simplicity

**Best Practices:**
- Prepared statements untuk security
- Audit trail untuk accountability
- Role-based permissions
- Clean separation of concerns

**Contact:**
Untuk pertanyaan atau support, hubungi IT Admin Anda.

---

🎉 **Selamat menggunakan Change Management System!**

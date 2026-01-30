# QUICK REFERENCE GUIDE
# Change Management System

## 🚀 QUICK START

### Installation (Linux/Ubuntu)
```bash
sudo ./install.sh
```

### Manual Setup
1. Copy files to `/var/www/change-management`
2. Import `database.sql` to MySQL
3. Edit `config/database.php` (set DB credentials)
4. Set permissions: `sudo chown -R www-data:www-data /var/www/change-management`
5. Access: `http://localhost/change-management/`

---

## 👥 DEFAULT USERS

| Username | Password   | Role     |
|----------|------------|----------|
| admin    | admin123   | Admin    |
| manager1 | manager123 | Manager  |
| staff1   | staff123   | Staff    |
| client1  | client123  | Client   |

---

## 🎯 WORKFLOW OVERVIEW

```
CLIENT/USER
   ↓ Create Change Request
   ↓ Submit
MANAGER
   ↓ Review & Approve
   ↓ Assign to IT Staff
IT STAFF
   ↓ Execute
   ↓ Complete
DONE ✓
```

---

## 📋 STATUS FLOW

```
draft → submitted → approved → in_progress → completed
                      ↓
                   rejected
```

**All Status:**
- `draft` - Baru dibuat
- `submitted` - Menunggu approval
- `approved` - Disetujui
- `rejected` - Ditolak
- `scheduled` - Dijadwalkan
- `in_progress` - Sedang dikerjakan
- `completed` - Selesai
- `failed` - Gagal
- `rolled_back` - Di-rollback
- `cancelled` - Dibatalkan

---

## 🔑 ROLE PERMISSIONS

### Admin (Full Access)
✅ View all changes
✅ Edit/Delete changes
✅ Approve changes
✅ User management
✅ Audit logs
✅ All reports

### Manager
✅ View all changes
✅ Approve/Reject changes
✅ Assign to IT staff
✅ Audit logs
✅ Comments
❌ User management

### IT Staff
✅ View assigned changes
✅ Execute changes
✅ Complete changes
✅ Comments
❌ Approve changes
❌ User management

### Client/User
✅ Create changes
✅ View own changes
✅ Edit draft changes
✅ Comments
❌ Approve changes
❌ Execute changes

---

## 📝 COMMON TASKS

### Create Change Request
1. Login → Dashboard
2. Click "New Change Request"
3. Fill form (minimum: Title, Description, Category)
4. Choose: "Save as Draft" or "Submit for Approval"

### Approve Change (Manager)
1. Dashboard → "Pending Approval" or Change List
2. Click change → "Review & Approve"
3. Select IT Staff to assign
4. Click "Approve" or "Reject"

### Execute Change (IT Staff)
1. Dashboard → "Your Tasks"
2. Click change → "Start Execution"
3. Follow Implementation Plan
4. Click "Mark Complete"
5. Fill Completion Notes

### Add Comment
1. Open change detail
2. Scroll to Comments section
3. Type comment → "Post Comment"

### Reset User Password (Admin)
1. Admin → "Users"
2. Click "Reset PW" on user
3. Enter new password

### View Audit Logs (Admin/Manager)
1. Navigation → "Audit Logs"
2. Use filters (User, Action, Date)
3. Click "Filter"

---

## 🔧 CONFIGURATION

### Database Connection
File: `config/database.php`
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'change_management');
```

### Change Categories
Edit: `database.sql` and `changes/create.php`
```sql
category ENUM('software', 'hardware', 'network', 'security', 'other')
```

### Theme Colors
File: `assets/style.css`
```css
/* Main gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

---

## 🐛 TROUBLESHOOTING

### Can't login?
- Check database connection in `config/database.php`
- Verify users exist: `SELECT * FROM users;`
- Reset password via SQL

### "Permission denied"?
```bash
sudo chown -R www-data:www-data /var/www/change-management
sudo chmod -R 755 /var/www/change-management
```

### Blank page?
- Check PHP error log: `/var/log/apache2/error.log`
- Enable error display in `config/database.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Database connection failed?
- Verify MySQL is running: `sudo systemctl status mysql`
- Test connection: `mysql -u root -p`
- Check credentials in `config/database.php`

---

## 📊 REPORTS & EXPORTS

### Export Audit Logs
Use phpMyAdmin or:
```sql
SELECT * FROM audit_logs 
WHERE DATE(created_at) = '2025-01-30'
INTO OUTFILE '/tmp/audit_20250130.csv';
```

### Get Change Statistics
```sql
SELECT status, COUNT(*) as count 
FROM change_requests 
GROUP BY status;
```

### Active Users Report
```sql
SELECT u.full_name, u.role, COUNT(cr.id) as total_changes
FROM users u
LEFT JOIN change_requests cr ON u.id = cr.requester_id
WHERE u.is_active = 1
GROUP BY u.id;
```

---

## 🔐 SECURITY CHECKLIST

✅ Change all default passwords
✅ Use strong passwords (min 12 chars)
✅ Backup database regularly
✅ Keep PHP & MySQL updated
✅ Enable HTTPS (SSL certificate)
✅ Restrict database access
✅ Review audit logs weekly
✅ Remove inactive users
✅ Set session timeout in php.ini

---

## 📞 SUPPORT

### File Locations
- **Logs**: `/var/log/apache2/`
- **Database backup**: Use `mysqldump`
- **Application**: `/var/www/change-management/`

### Useful Commands
```bash
# Restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# View error log
tail -f /var/log/apache2/error.log

# Backup database
mysqldump -u root -p change_management > backup.sql

# Restore database
mysql -u root -p change_management < backup.sql
```

---

## 📚 RESOURCES

- Full documentation: `README.md`
- Database schema: `database.sql`
- Installation guide: `install.sh`

---

**Version:** 1.0
**Last Updated:** January 2025

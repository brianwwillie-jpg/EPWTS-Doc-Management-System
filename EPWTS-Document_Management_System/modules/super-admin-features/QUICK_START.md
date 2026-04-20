# Backup & Restore Feature - Quick Start Guide

## ✅ What Was Created

### 1. **Main Interface** (`super_admin_backup.php`)
   - Professional 4-tab interface for managing backups
   - Tab 1: **Create Backup** - Database and file backup creation
   - Tab 2: **Restore Backup** - Restore from saved backups
   - Tab 3: **Recovery** - Recover deleted documents
   - Tab 4: **History** - View all backup/restore operations

**Access:** Dashboard → Super Admin → Backup & Restore Manager

---

### 2. **Database Migration Script** (`db_migration_backup.php`)
   - Creates 3 new tables:
     - `deleted_documents` - Tracks soft-deleted documents for recovery
     - `backup_logs` - Logs all backup operations
     - `restore_logs` - Logs all restore operations

**First Step:** Run this ONCE before using the feature

---

### 3. **Automatic Backup Script** (`auto_backup.php`)
   - Automated daily backup script
   - Can be scheduled via cron job
   - Automatic cleanup of old backups
   - Detailed logging to `/backups/backup_log.txt`

**Optional:** Set up cron job for daily backups

---

### 4. **Enhanced Delete Logic** (`delete_document.php` - MODIFIED)
   - Documents are now **soft deleted** instead of permanently removed
   - Moved to `deleted_documents` table for recovery
   - 30-day recovery window
   - Operation is fully logged

---

### 5. **Complete Documentation** (`BACKUP_RESTORE_GUIDE.md`)
   - 10+ sections covering all aspects
   - Setup instructions
   - Troubleshooting guide
   - Security best practices
   - FAQs

---

## 🚀 SETUP STEPS (IMPORTANT!)

### Step 1: Create Backup Tables (REQUIRED)

**Method A: Via Browser (Easy)**
1. Access: `http://localhost/EPWTS-Document_Management_System/modules/super-admin-features/db_migration_backup.php`
2. Page will automatically run migration and show results
3. Should see: ✅ Successfully Created Tables

**Method B: Via phpMyAdmin (Alternative)**
1. Open phpMyAdmin
2. Select `epwts_db` database
3. Open "SQL" tab
4. Paste the contents of the migration script
5. Execute

**What it does:**
- Creates `deleted_documents` table
- Creates `backup_logs` table
- Creates `restore_logs` table

---

### Step 2: Verify Setup

After migration, verify tables were created:

```sql
SHOW TABLES LIKE 'deleted_%';
SHOW TABLES LIKE 'backup_%';
SHOW TABLES LIKE 'restore_%';
```

Should return 3 tables:
- deleted_documents
- backup_logs
- restore_logs

---

### Step 3: Access the Feature

1. Log in as **Super Admin**
2. Go to **Dashboard**
3. Look for **Backup & Restore Manager** button
4. Or navigate directly to:
   `http://localhost/EPWTS-Document_Management_System/modules/super-admin-features/super_admin_backup.php`

---

## 📋 Feature Overview

### Create Database Backup
- Click one button
- Entire database is exported to SQL file
- Stored in `/backups/epwts_db_TIMESTAMP.sql`
- Size: ~125 KB
- Can be restored anytime

### Create File Backup
- Click one button
- All documents in `/uploads/` are copied
- Stored in `/backups/files/backup_TIMESTAMP/`
- Includes file count statistics

### Restore from Backup
⚠️ **WARNING - Destructive Operation**
- Select a backup from dropdown
- Confirm (system asks twice!)
- Entire database is replaced
- No rollback - use carefully!

### Recovery/Deleted Documents
- View all deleted documents
- Click "Restore" to bring them back
- Document instantly reappears in system
- No file is actually deleted

### View History
- See all backup/restore operations
- Timestamps and user who performed action
- Success/failure status
- Perfect for audit trail

---

## 🗂️ Directory Structure Created

```
EPWTS-Document_Management_System/
├── backups/                          ← New directory created automatically
│   ├── epwts_db_2026-04-11_14-35-22.sql
│   ├── epwts_db_2026-04-10_10-20-15.sql
│   ├── backup_log.txt               ← Log of auto backups
│   └── files/
│       ├── backup_2026-04-11_14-35-22/
│       │   ├── page 4.png
│       │   ├── page 8.png
│       │   └── ...
│       └── backup_2026-04-10_10-20-15/
│           └── ...
└── modules/super-admin-features/
    ├── super_admin_backup.php       ← Main interface
    ├── db_migration_backup.php      ← One-time setup
    ├── auto_backup.php              ← Automated backup
    └── BACKUP_RESTORE_GUIDE.md      ← Full documentation
```

---

## ⚙️ Optional: Set Up Automated Daily Backups

Add this line to your server's crontab (Linux/Mac):

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 2 AM)
0 2 * * * php /var/www/html/EPWTS-Document_Management_System/modules/super-admin-features/auto_backup.php >> /var/www/html/EPWTS-Document_Management_System/backups/cron.log 2>&1
```

For Windows, use **Task Scheduler**:
1. Create scheduled task
2. Run: `php.exe C:\xampp\htdocs\EPWTS-Document_Management_System\modules\super-admin-features\auto_backup.php`
3. Schedule: Daily at 2:00 AM

---

## 🔐 Security Notes

1. **Keep `/backups/` directory private:**
   ```bash
   chmod 750 /var/www/html/EPWTS-Document_Management_System/backups
   ```

2. **Only Super Admin has access** - Verified in code

3. **SQL injection protected** - Uses prepared statements

4. **Backups should be:**
   - Regularly tested
   - Stored on separate device
   - Encrypted if sensitive
   - Retained for 30 days minimum

---

## 📊 Key Features Summary

| Feature | Availability | Use Case |
|---------|--------------|----------|
| Database Backup | All systems | Full system recovery |
| File Backup | All systems | Document archive |
| Document Recovery | After migration | Restore 1-30 day deletions |
| Automated Backups | With cron job | Daily protection |
| Backup Logs | Automatic | Audit trail |
| Restore Logs | Automatic | Track all restorations |

---

## 🆘 Troubleshooting

### "Access Denied" error
- Make sure you're logged in as **Super Admin**
- Check `$_SESSION['role']` is set to 'Super_Admin'

### "Table doesn't exist" error
- Run migration: `db_migration_backup.php`
- Verify tables exist in phpMyAdmin

### "Failed to create backup"
- Check `/backups/` directory permissions
- Ensure disk space is available (>500MB recommended)
- Check database connectivity

### Deleted documents not showing in Recovery
- Run database migration if not done
- Check `deleted_documents` table exists
- Verify deletions used new soft-delete mechanism

---

## 📱 Testing the Feature

### Test Database Backup:
1. Go to Backup Manager → Create Backup tab
2. Click "Create Database Backup"
3. Check `/backups/` folder - should see `epwts_db_*.sql` file

### Test File Backup:
1. Go to Backup Manager → Create Backup tab
2. Click "Create File Backup"
3. Check `/backups/files/` folder - should see timestamped folder with files

### Test Document Recovery:
1. Go to document list
2. Delete a document
3. Go to Backup Manager → Recovery tab
4. Should see deleted document
5. Click "Restore"
6. Document should reappear in document list

---

## 📞 Support Documentation

For detailed information, read: [`BACKUP_RESTORE_GUIDE.md`](BACKUP_RESTORE_GUIDE.md)

Covers:
- Complete feature documentation
- Database schema details
- How to use each feature
- Backup recommendations
- Advanced configurations
- FAQs and troubleshooting

---

## ✨ Next Steps

1. **Run Migration** - Access `db_migration_backup.php`
2. **Test Feature** - Create a test backup
3. **Read Documentation** - Review `BACKUP_RESTORE_GUIDE.md`
4. **Set Schedule** - Setup cron job (optional but recommended)
5. **Monitor Backups** - Check History tab regularly

---

**Status:** ✅ Production Ready  
**Version:** 1.0  
**Last Updated:** April 11, 2026

**Questions?** Contact your system administrator or refer to the detailed guide included.

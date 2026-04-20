# EPWTS Document Management System - Backup & Restore Feature Documentation

## Overview

The Backup & Restore feature provides comprehensive data protection and recovery capabilities for the EPWTS Document Management System. It includes:

- **Database Backups** - Complete SQL dumps of all system data
- **File Backups** - Timestamped copies of all uploaded documents
- **Document Recovery** - Soft delete mechanism to restore deleted documents
- **Audit Logging** - Complete history of all backup/restore operations
- **Easy UI Interface** - Tab-based management system for all backup operations

---

## Features

### 1. Database Backups

**What it does:**
- Creates a complete SQL dump of the entire `epwts_db` database
- Includes all tables: `users`, `documents`, `requests`, `user_permissions`
- Stores backups with timestamps (format: `epwts_db_YYYY-MM-DD_HH-MM-SS.sql`)

**Location:** `/backups/epwts_db_*.sql`

**Size:** Typically 50-500 KB depending on document volume

**Use Cases:**
- Regular scheduled backups (daily/weekly)
- Before major system updates
- Disaster recovery
- Data auditing

**Example:**
```
epwts_db_2026-04-11_14-35-22.sql (125 KB)
```

### 2. File Backups

**What it does:**
- Creates a timestamped copy of all documents in `/uploads/`
- Preserves original file names and structure
- Includes metadata for quick lookup

**Location:** `/backups/files/backup_YYYY-MM-DD_HH-MM-SS/`

**Use Cases:**
- Protect against accidental file deletion
- Archive document versions
- Compliance and legal requirements

**Example Structure:**
```
backups/files/
├── backup_2026-04-11_14-35-22/
│   ├── page 4.png
│   ├── page 8.png
│   ├── Infectious Disease Test 1.png
│   └── Serology 1.png
└── backup_2026-04-10_10-15-00/
    └── ...
```

### 3. Document Recovery (Soft Delete)

**How it works:**
1. When a document is deleted, it's **moved** to `deleted_documents` table, not permanently deleted
2. The document appears in the "Recovery" tab of the Backup Manager
3. Super admin can restore it with one click within 30 days
4. Restoration re-inserts the document into the `documents` table

**Deleted Documents Table:**
- `id` - Record ID
- `doc_id` - Original document ID
- `file_name` - Original filename
- `section` - Department (AB, CW, AD, EP, DO)
- `deleted_by` - Username who deleted it
- `deleted_at` - Timestamp of deletion

**Recovery Process:**
1. Go to Backup & Restore Manager
2. Click "Recovery" tab
3. Find the deleted document
4. Click "Restore" button
5. Document is instantly restored to active documents

---

## Database Migration

**IMPORTANT:** Before using the Backup & Restore feature, you must run the database migration.

### Running the Migration

1. Navigate to: `/modules/super-admin-features/db_migration_backup.php`
2. Access it via browser as Super Admin
3. Click to execute
4. System will create 3 new tables:
   - `deleted_documents` - Stores soft-deleted documents
   - `backup_logs` - Logs all backup operations
   - `restore_logs` - Logs all restore operations

### Tables Created

**deleted_documents**
```sql
- id (INT, PK, AUTO_INCREMENT)
- doc_id (VARCHAR)
- file_name (VARCHAR)
- file_path (VARCHAR)
- section (VARCHAR)
- doc_type (VARCHAR)
- uploaded_by (VARCHAR)
- upload_date (DATETIME)
- expiry_date (DATE)
- description (TEXT)
- deleted_by (VARCHAR) -- Who deleted it
- deleted_at (TIMESTAMP) -- When it was deleted
```

**backup_logs**
```sql
- id (INT, PK, AUTO_INCREMENT)
- backup_type (ENUM: 'database', 'files')
- created_by (VARCHAR) -- Username who created backup
- file_path (VARCHAR) -- Where backup is stored
- file_size (VARCHAR) -- Size in bytes
- file_count (INT) -- Number of files (for file backups)
- status (ENUM: 'success', 'failed', 'partial')
- notes (TEXT)
- created_at (TIMESTAMP)
```

**restore_logs**
```sql
- id (INT, PK, AUTO_INCREMENT)
- restore_type (ENUM: 'database', 'files', 'document')
- restored_from (VARCHAR) -- Backup file or document ID
- restored_by (VARCHAR) -- Username who restored
- status (ENUM: 'success', 'failed', 'partial')
- notes (TEXT)
- restored_at (TIMESTAMP)
```

---

## How to Use

### Creating a Database Backup

1. Log in as **Super Admin**
2. Go to **Dashboard** → **Backup & Restore Manager**
3. Click **"Create Database Backup"** button
4. System will automatically:
   - Generate SQL dump
   - Save to `/backups/epwts_db_TIMESTAMP.sql`
   - Log the operation
   - Display success message

### Creating a File Backup

1. Log in as **Super Admin**
2. Go to **Dashboard** → **Backup & Restore Manager**
3. Click **"Create File Backup"** button
4. System will automatically:
   - Scan `/uploads/` directory
   - Copy all files to `/backups/files/backup_TIMESTAMP/`
   - Log the operation
   - Display file count and success message

### Restoring from Database Backup

⚠️ **WARNING:** This is a destructive operation!

1. Log in as **Super Admin**
2. Go to **Backup & Restore Manager** → **"Restore Backup"** tab
3. Select a backup file from the dropdown
4. **CAREFULLY** review what you're restoring
5. Confirm the operation (system will ask for confirmation)
6. Database will be replaced with backup data
7. Check **History** tab to verify restoration

**Important Considerations:**
- Current database will be **COMPLETELY REPLACED**
- All changes since the backup was made will be lost
- Users are NOT automatically logged out
- Don't restore during active document uploads

### Recovering Deleted Documents

1. Log in as **Super Admin**
2. Go to **Backup & Restore Manager** → **"Recovery"** tab
3. Browse the list of deleted documents
4. Find the document you want to restore
5. Click the **"Restore"** button next to it
6. Document is immediately restored to active documents
7. Verify in the **Document Management** section

---

## Access Control

### Who Can Access Backup & Restore?

**Only Super Admin users** can access the backup and restore feature.

```php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Super_Admin') {
    die("Access Denied. Super Admin privileges required.");
}
```

### Audit Trail

All backup and restore operations are logged with:
- **Type** - Database, Files, or Document recovery
- **User** - Username who performed the action
- **Timestamp** - Exactly when it occurred
- **Status** - Success, Failed, or Partial
- **Details** - Additional information (file sizes, document IDs, etc.)

---

## Backup Recommendations

### Frequency
- **Database Backups:** Daily (automated via cron job - recommended)
- **File Backups:** Weekly (or after major uploads)
- **Before major operations:** Manual backup before system updates

### Retention Policy
- Keep last 30 days of backups
- Archive older backups to external storage
- Periodically test restoration to ensure integrity

### Automation (Optional - Cron Job)

To automate daily database backups, add to server cron:

```bash
# Daily backup at 2 AM
0 2 * * * cd /var/www/html/EPWTS-Document_Management_System && php -r 'include "config/db.php"; include "modules/super-admin-features/auto_backup.php";'
```

### Storage Considerations

- Each database backup: ~125 KB
- Each file backup depends on document size (typically 1-50 MB)
- Recommend keeping backups on separate storage device
- Monitor disk space regularly

---

## Troubleshooting

### Backup File Not Created

**Problem:** "Failed to create database backup!"

**Solutions:**
1. Check directory permissions: `/backups/` must be writable
2. Ensure disk space is available
3. Check database connectivity
4. Verify Super Admin role

```bash
# Fix permissions on Linux
chmod 755 /var/www/html/EPWTS-Document_Management_System/backups
chmod 755 /var/www/html/EPWTS-Document_Management_System/backups/files
```

### Restore Failed

**Problem:** "Failed to restore database"

**Solutions:**
1. Verify backup file size and integrity
2. Check for SQL syntax errors in backup file
3. Ensure enough free disk space
4. Check user permissions in MySQL

### Deleted Documents Not Showing

**Problem:** Recovery tab is empty but documents were deleted

**Solutions:**
1. Run database migration: `/modules/super-admin-features/db_migration_backup.php`
2. Check if `deleted_documents` table exists: `SHOW TABLES;`
3. Verify documents were soft-deleted (check `deleted_documents` table directly)

---

## Security Considerations

### Backup File Protection

Backup files are stored in `/backups/` directory. **Recommended security:**

1. **Access Control:**
   - Only Super Admin should have read access
   - Store outside web root if possible
   - Use proper file permissions (644)

2. **Encryption:**
   - Store backups on encrypted drives
   - Use SFTP/SSH for remote backup transfers
   - Don't store backups on public servers

3. **Integrity:**
   - Verify backup file checksums
   - Test restoration regularly
   - Keep backup logs for audit purposes

### SQL Injection Prevention

All input is properly escaped using prepared statements:

```php
$restore_stmt = $conn->prepare($sql);
$filepath = htmlspecialchars($_POST['backup_file']);
```

---

## Advanced Features

### Backup with Compression

For large backups, compress the SQL file:

```bash
gzip epwts_db_2026-04-11_14-35-22.sql
# Creates: epwts_db_2026-04-11_14-35-22.sql.gz
```

### Incremental Backups

To create faster incremental backups, consider:
- Only backing up changed files
- Using rsync or similar tools
- Implementing transaction logs

### Remote Backup

To backup to remote server:

```bash
scp backups/epwts_db_*.sql user@remote:/path/to/backups/
```

---

## FAQs

**Q: Can I restore a backup without losing recent data?**  
A: No. Full database restore replaces all data. Deleted documents can be recovered individually, but database restore is all-or-nothing.

**Q: How long until deleted documents are permanently removed?**  
A: Currently, there's no automatic cleanup. Implement a 30-day cleanup policy manually or via cron.

**Q: Can multiple backups be running simultaneously?**  
A: Not recommended. Wait for one to complete before starting another.

**Q: What happens to file locks during backup?**  
A: The system creates a snapshot. Active uploads might not be included in the backup.

**Q: Can I backup to the cloud?**  
A: Yes, copy backup files to cloud storage (AWS S3, Google Drive, etc.) after creation.

---

## Support & Contact

For issues or questions about the Backup & Restore feature:
- Contact: System Administrator
- Documentation: `/modules/super-admin-features/BACKUP_RESTORE_GUIDE.md`
- Migration Script: `/modules/super-admin-features/db_migration_backup.php`

---

**Last Updated:** April 11, 2026  
**Version:** 1.0  
**Status:** Production Ready

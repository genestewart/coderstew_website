# CoderStew Website Backup System

Comprehensive backup and restore system for the CoderStew website with automated scheduling, monitoring, and multi-tier storage.

## 🏗️ System Architecture

### Components

1. **Docker Backup Service**: Alpine Linux container with automated backup scripts
2. **Laravel Backup Integration**: Spatie Laravel Backup package for application-level backups
3. **S3 Cloud Storage**: Primary backup destination with lifecycle management
4. **Local Storage**: Secondary backup storage and staging area
5. **Monitoring & Alerting**: Health checks and notification system

### Backup Types

- **Full Backup**: Database + Application files + Storage + Logs
- **Database Only**: MySQL dump with all databases and routines
- **Application Files**: Laravel storage, uploads, and configuration
- **Incremental**: Changed files only (planned feature)

## 📅 Backup Schedule

### Automated Schedule
- **Daily Full Backup**: 2:00 AM UTC
- **Weekly Health Check**: Sunday 1:00 AM UTC
- **Monthly Cleanup**: 1st of month, 3:00 AM UTC

### Retention Policy
- **Local**: 7 days (rolling deletion)
- **S3 Standard-IA**: 30 days (configurable)
- **S3 Lifecycle**: Automatic transition to cheaper storage classes

## 🚀 Quick Start

### 1. Enable Backup Service
```bash
# Start the backup service
docker compose -f docker-compose.prod.yml up -d backup

# Check service status
docker compose -f docker-compose.prod.yml ps backup
```

### 2. Run Manual Backup
```bash
# Using backup manager
./backup-manager.sh backup

# Using Docker directly
docker compose -f docker-compose.prod.yml exec backup /usr/local/bin/backup.sh backup
```

### 3. Monitor Backup Status
```bash
# Check backup health
./backup-manager.sh health

# View logs
./backup-manager.sh logs

# Show statistics
./backup-manager.sh stats
```

## ⚙️ Configuration

### Environment Variables

Add to `backend/.env.production`:

```env
# Backup Configuration
BACKUP_S3_BUCKET=coderstew-backups
BACKUP_RETENTION_DAYS=30
BACKUP_NOTIFICATION_EMAIL=admin@coderstew.com

# AWS Configuration (for S3 storage)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1

# Backup Disk (local or s3)
BACKUP_DISK=s3
```

### Laravel Backup Configuration

The system uses both Docker-based and Laravel-based backups:

- **Docker Backup**: System-level database dumps and file archives
- **Laravel Backup**: Application-aware backups with Laravel integration

Configuration file: `backend/config/backup.php`

## 📦 Backup Contents

### Database Backup
- All MySQL databases
- User accounts and permissions
- Stored procedures and functions
- Triggers and views
- Complete schema and data

### Application Files
- Laravel storage directory
- User uploads and media files
- Application logs
- Configuration files (excluding secrets)
- Generated cache files (optional)

### Exclusions
- Temporary files
- Node modules
- Git repository
- Environment files with secrets
- Framework cache
- Session files

## 🛠️ Management Commands

### Backup Manager Script

The `backup-manager.sh` script provides comprehensive backup management:

```bash
# Core Operations
./backup-manager.sh backup          # Run backup now
./backup-manager.sh restore FILE    # Restore from backup
./backup-manager.sh list            # List available backups

# Monitoring
./backup-manager.sh monitor         # Real-time monitoring
./backup-manager.sh health          # Health check
./backup-manager.sh logs            # View logs
./backup-manager.sh stats           # Show statistics

# Maintenance
./backup-manager.sh cleanup         # Clean old backups
./backup-manager.sh test            # Test backup system
```

### Laravel Artisan Commands

Application-level backup commands:

```bash
# Laravel backup commands
php artisan coderstew:backup --type=full
php artisan backup:run --only-db
php artisan backup:run --only-files
php artisan backup:monitor
php artisan backup:clean
```

## 🔄 Restore Procedures

### Automatic Restore

Use the backup manager for guided restore:

```bash
# Restore from local backup
./backup-manager.sh restore coderstew_backup_20250721_020000.tar.gz

# Restore latest from S3
./backup-manager.sh restore latest.tar.gz s3
```

### Manual Restore

1. **Stop Application Services** (recommended):
   ```bash
   docker compose -f docker-compose.prod.yml stop app queue scheduler
   ```

2. **Run Restore Script**:
   ```bash
   docker compose -f docker-compose.prod.yml exec backup /usr/local/bin/restore.sh backup_file.tar.gz
   ```

3. **Restart Services**:
   ```bash
   docker compose -f docker-compose.prod.yml start app queue scheduler
   ```

### Database-Only Restore

```bash
# Extract database from backup
tar -xzf coderstew_backup_20250721_020000.tar.gz

# Restore database
mysql -h coderstew_db_prod -u root -p < database_20250721_020000.sql
```

## 📊 Monitoring & Alerting

### Health Checks

The backup service includes comprehensive health monitoring:

- **Database Connectivity**: Verifies MySQL connection
- **S3 Availability**: Checks cloud storage access
- **Disk Space**: Monitors available storage
- **Backup Freshness**: Ensures recent backup exists
- **Service Health**: Monitors backup container status

### Monitoring Endpoints

- **Service Health**: `docker compose exec backup /usr/local/bin/backup.sh health`
- **Backup Logs**: `/var/log/backup/backup.log`
- **Cron Logs**: `/var/log/backup/cron.log`
- **Container Stats**: `docker stats coderstew_backup_prod`

### Notifications

Email notifications are sent for:
- Backup completion (success/failure)
- Health check failures
- Storage space warnings
- Service errors

Configure in `backend/.env.production`:
```env
BACKUP_NOTIFICATION_EMAIL=admin@coderstew.com
MAIL_FROM_ADDRESS=backup@coderstew.com
```

## 🗂️ Storage Organization

### Local Storage Structure
```
/backup/
├── archives/           # Final backup archives
├── database/          # Database volume (read-only)
├── storage/           # Application storage (read-only)
├── logs/             # Application logs (read-only)
└── temp/             # Temporary backup files
```

### S3 Storage Structure
```
s3://coderstew-backups/
├── backups/
│   ├── coderstew_backup_YYYYMMDD_HHMMSS.tar.gz
│   └── latest.tar.gz
├── laravel-backups/   # Laravel Backup package files
└── restore/           # Restore staging area
```

## 🔒 Security Considerations

### Access Control
- Database passwords stored in Docker secrets
- S3 credentials managed through environment variables
- Backup archives encrypted in transit (HTTPS/TLS)
- Local backup files have restricted permissions (600)

### Data Protection
- Database backups include all user data
- File backups exclude sensitive configuration
- Backup integrity verified with checksums
- Cloud storage uses server-side encryption

### Network Security
- Backup service runs on internal Docker network
- S3 access uses IAM roles with minimum required permissions
- No external ports exposed for backup service

## 🚨 Troubleshooting

### Common Issues

1. **Backup Service Won't Start**
   ```bash
   # Check service logs
   docker compose -f docker-compose.prod.yml logs backup
   
   # Verify secrets exist
   ls -la secrets/mysql_*
   ```

2. **S3 Upload Failures**
   ```bash
   # Test AWS credentials
   docker compose exec backup aws s3 ls
   
   # Check network connectivity
   docker compose exec backup ping s3.amazonaws.com
   ```

3. **Database Connection Issues**
   ```bash
   # Test database connectivity
   docker compose exec backup nc -z coderstew_db_prod 3306
   
   # Verify MySQL password
   docker compose exec backup mysql -h coderstew_db_prod -u root -p
   ```

4. **Insufficient Disk Space**
   ```bash
   # Check disk usage
   docker compose exec backup df -h /backup/
   
   # Clean old backups
   ./backup-manager.sh cleanup
   ```

### Error Recovery

- **Failed Backup**: Check logs and retry with `--force` option
- **Corrupted Archive**: Restore from previous backup
- **S3 Access Lost**: Use local backups as fallback
- **Database Corruption**: Use point-in-time recovery

### Performance Tuning

- **Large Databases**: Use `--single-transaction` for consistency
- **Network Issues**: Implement retry logic and timeout handling
- **Storage Optimization**: Use compression and deduplication
- **Parallel Processing**: Run database and file backups concurrently

## 📈 Backup Metrics

### Key Performance Indicators

- **Backup Success Rate**: >99% successful backups
- **Recovery Time Objective (RTO)**: <30 minutes
- **Recovery Point Objective (RPO)**: <24 hours
- **Backup Size Growth**: Monitor monthly growth rate
- **Storage Costs**: Track S3 usage and optimize

### Monitoring Commands

```bash
# Backup statistics
./backup-manager.sh stats

# Storage usage
docker compose exec backup du -sh /backup/archives/*

# S3 storage analysis
aws s3 ls s3://coderstew-backups/backups/ --recursive --human-readable --summarize
```

## 🔮 Advanced Features

### Planned Enhancements

- **Incremental Backups**: Rsync-based differential backups
- **Cross-Region Replication**: Multi-region S3 backup storage
- **Backup Encryption**: Client-side encryption before upload
- **Backup Verification**: Automatic restore testing
- **Real-time Monitoring**: Prometheus metrics integration
- **Disaster Recovery**: Automated failover procedures

### Custom Backup Scripts

You can extend the backup system by creating custom scripts in `/docker/backup/custom/`:

```bash
# Custom backup hook
/docker/backup/custom/pre-backup.sh   # Run before backup
/docker/backup/custom/post-backup.sh  # Run after backup
/docker/backup/custom/validate.sh     # Custom validation
```

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks

- **Weekly**: Review backup logs and success rates
- **Monthly**: Test restore procedures
- **Quarterly**: Update backup scripts and dependencies
- **Annually**: Conduct disaster recovery drills

### Documentation Updates

This documentation should be updated when:
- Backup procedures change
- New storage destinations are added
- Monitoring requirements evolve
- Security policies are updated

### Contact Information

- **System Administrator**: admin@coderstew.com
- **Emergency Contact**: backup-emergency@coderstew.com
- **Documentation Updates**: docs@coderstew.com
#!/bin/sh

# CoderStew Website Backup Script
# Automated backup of database, application files, and logs to S3

set -e  # Exit on any error

# Configuration from environment variables
BACKUP_DIR="/backup"
ARCHIVE_DIR="/backup/archives"
TEMP_DIR="/backup/temp"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="coderstew_backup_${TIMESTAMP}"
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}

# Create temp directory
mkdir -p "$TEMP_DIR"

# Logging
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [BACKUP] $1"
}

error() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $1" >&2
}

# Check required environment variables
check_config() {
    if [ "$AWS_DISABLED" = "true" ]; then
        log "AWS S3 backup is disabled - using local storage only"
    elif [ -z "$AWS_ACCESS_KEY_ID" ] || [ -z "$AWS_SECRET_ACCESS_KEY" ] || [ -z "$S3_BUCKET" ]; then
        log "AWS credentials or S3 bucket not configured - using local storage only"
        export AWS_DISABLED="true"
    fi
    
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        if [ -n "$MYSQL_ROOT_PASSWORD_FILE" ] && [ -f "$MYSQL_ROOT_PASSWORD_FILE" ]; then
            export MYSQL_ROOT_PASSWORD=$(cat "$MYSQL_ROOT_PASSWORD_FILE")
        else
            error "MySQL root password not available"
            return 1
        fi
    fi
    
    log "Configuration validated successfully"
}

# Create backup directories
setup_directories() {
    log "Setting up backup directories..."
    mkdir -p "$ARCHIVE_DIR"
    cd "$BACKUP_DIR"
}

# Backup database
backup_database() {
    log "Starting database backup..."
    
    # Wait for database to be ready
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if nc -z coderstew_db_prod 3306; then
            break
        fi
        log "Waiting for database connection... (attempt $attempt/$max_attempts)"
        sleep 10
        attempt=$((attempt + 1))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        error "Database connection timeout"
        return 1
    fi
    
    # Create database dump
    log "Creating database dump..."
    mysqldump -h coderstew_db_prod -u root -p"$MYSQL_ROOT_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --all-databases \
        --compress \
        --lock-tables=false \
        --quick \
        --add-drop-database \
        > "${TEMP_DIR}/database_${TIMESTAMP}.sql"
    
    if [ $? -eq 0 ]; then
        log "Database backup completed successfully"
    else
        error "Database backup failed"
        return 1
    fi
}

# Backup application files
backup_files() {
    log "Starting file backup..."
    
    # Create tar archive of application files and logs
    tar -czf "${TEMP_DIR}/storage_${TIMESTAMP}.tar.gz" \
        -C /backup \
        storage/ \
        logs/ \
        2>/dev/null || true
    
    # Get file sizes for logging
    if [ -f "${TEMP_DIR}/storage_${TIMESTAMP}.tar.gz" ]; then
        local size=$(du -h "${TEMP_DIR}/storage_${TIMESTAMP}.tar.gz" | cut -f1)
        log "File backup completed successfully (size: $size)"
    else
        log "File backup completed with warnings"
    fi
}

# Create consolidated backup archive
create_archive() {
    log "Creating consolidated backup archive..."
    
    cd "$TEMP_DIR"
    
    # Create final backup archive
    tar -czf "${ARCHIVE_DIR}/${BACKUP_NAME}.tar.gz" \
        "database_${TIMESTAMP}.sql" \
        "storage_${TIMESTAMP}.tar.gz"
    
    # Clean up temp files
    rm -f "database_${TIMESTAMP}.sql" "storage_${TIMESTAMP}.tar.gz"
    
    # Get final archive size
    local size=$(du -h "${ARCHIVE_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)
    log "Backup archive created: ${BACKUP_NAME}.tar.gz (size: $size)"
}

# Upload to S3
upload_to_s3() {
    if [ "$AWS_DISABLED" = "true" ]; then
        log "S3 upload skipped (AWS disabled)"
        return 0
    fi
    
    log "Uploading backup to S3..."
    
    # Upload backup with metadata
    aws s3 cp "${ARCHIVE_DIR}/${BACKUP_NAME}.tar.gz" \
        "s3://${S3_BUCKET}/backups/${BACKUP_NAME}.tar.gz" \
        --storage-class STANDARD_IA \
        --metadata "source=coderstew,timestamp=${TIMESTAMP},type=full-backup"
    
    if [ $? -eq 0 ]; then
        log "Backup uploaded to S3 successfully"
        # Create a latest backup symlink
        aws s3 cp "${ARCHIVE_DIR}/${BACKUP_NAME}.tar.gz" \
            "s3://${S3_BUCKET}/backups/latest.tar.gz" \
            --storage-class STANDARD_IA \
            --metadata "source=coderstew,timestamp=${TIMESTAMP},type=latest"
    else
        error "S3 upload failed"
        return 1
    fi
}

# Clean up old backups locally
cleanup_local() {
    log "Cleaning up old local backups..."
    
    find "$ARCHIVE_DIR" -name "coderstew_backup_*.tar.gz" -type f -mtime +7 -delete
    
    log "Local cleanup completed"
}

# Clean up old backups in S3
cleanup_s3() {
    if [ "$AWS_DISABLED" = "true" ]; then
        log "S3 cleanup skipped (AWS disabled)"
        return 0
    fi
    
    log "Cleaning up old S3 backups (retention: ${RETENTION_DAYS} days)..."
    
    # Use AWS CLI lifecycle policy for more reliable cleanup
    # Create lifecycle configuration if it doesn't exist
    local lifecycle_config='{"Rules":[{"ID":"coderstew-backup-lifecycle","Status":"Enabled","Filter":{"Prefix":"backups/coderstew_backup_"},"Expiration":{"Days":'${RETENTION_DAYS}'}}]}'
    
    # Apply lifecycle policy
    echo "$lifecycle_config" > /tmp/lifecycle.json
    if aws s3api put-bucket-lifecycle-configuration --bucket "${S3_BUCKET}" --lifecycle-configuration file:///tmp/lifecycle.json 2>/dev/null; then
        log "S3 lifecycle policy applied successfully"
    else
        log "Failed to apply S3 lifecycle policy - using manual cleanup"
        
        # Fallback to manual cleanup
        aws s3 ls "s3://${S3_BUCKET}/backups/" | grep "coderstew_backup_" | while read -r line; do
            backup_date=$(echo "$line" | awk '{print $1" "$2}')
            backup_file=$(echo "$line" | awk '{print $4}')
            
            if [ -n "$backup_date" ] && [ -n "$backup_file" ] && [ "$backup_file" != "latest.tar.gz" ]; then
                # Calculate age of backup (using date command)
                backup_epoch=$(date -d "$backup_date" +%s 2>/dev/null || echo "0")
                current_epoch=$(date +%s)
                
                if [ "$backup_epoch" != "0" ]; then
                    age_days=$(( (current_epoch - backup_epoch) / 86400 ))
                    
                    if [ $age_days -gt $RETENTION_DAYS ]; then
                        log "Deleting old backup: $backup_file (${age_days} days old)"
                        aws s3 rm "s3://${S3_BUCKET}/backups/$backup_file"
                    fi
                fi
            fi
        done
    fi
    
    rm -f /tmp/lifecycle.json
    log "S3 cleanup completed"
}

# Send notification (optional)
send_notification() {
    local status=$1
    local message=$2
    
    log "Backup $status: $message"
    
    # Add notification logic here (email, Slack, etc.)
    # For now, just log the result
}

# Main backup function
run_backup() {
    local start_time=$(date +%s)
    
    log "Starting backup process..."
    
    if check_config && \
       setup_directories && \
       backup_database && \
       backup_files && \
       create_archive && \
       upload_to_s3 && \
       cleanup_local && \
       cleanup_s3; then
        
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        send_notification "completed" "Duration: ${duration}s"
        log "Backup process completed successfully in ${duration} seconds"
        return 0
    else
        send_notification "failed" "Check logs for details"
        error "Backup process failed"
        return 1
    fi
}

# Health check function
health_check() {
    log "Performing backup system health check..."
    
    # Check disk space
    local available_space=$(df /backup | awk 'NR==2 {print $4}')
    if [ "$available_space" -lt 1048576 ]; then  # Less than 1GB
        error "Low disk space: ${available_space}KB available"
        return 1
    fi
    
    # Check S3 connectivity
    if ! aws s3 ls "s3://${S3_BUCKET}/" >/dev/null 2>&1; then
        error "S3 connectivity check failed"
        return 1
    fi
    
    # Check database connectivity
    if ! nc -z coderstew_db_prod 3306; then
        error "Database connectivity check failed"
        return 1
    fi
    
    log "Health check passed"
    return 0
}

# Handle different commands
case "${1:-backup}" in
    "backup")
        run_backup
        ;;
    "health")
        health_check
        ;;
    "cleanup")
        cleanup_local
        cleanup_s3
        ;;
    *)
        echo "Usage: $0 {backup|health|cleanup}"
        exit 1
        ;;
esac
#!/bin/sh

# CoderStew Website Restore Script
# Restore database and application files from backup

set -e

# Configuration
RESTORE_DIR="/backup/restore"
BACKUP_FILE=""
SOURCE="local"  # local or s3
MYSQL_HOST="coderstew_db_prod"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [RESTORE] $1"
}

error() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $1" >&2
}

success() {
    echo -e "${GREEN}$(date '+%Y-%m-%d %H:%M:%S') [SUCCESS] $1${NC}"
}

warning() {
    echo -e "${YELLOW}$(date '+%Y-%m-%d %H:%M:%S') [WARNING] $1${NC}"
}

# Show usage
usage() {
    echo "Usage: $0 [OPTIONS] BACKUP_FILE"
    echo ""
    echo "Options:"
    echo "  -s, --source SOURCE    Backup source: 'local' (default) or 's3'"
    echo "  -h, --help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 coderstew_backup_20250721_020000.tar.gz"
    echo "  $0 -s s3 latest.tar.gz"
    echo "  $0 --source local coderstew_backup_20250721_020000.tar.gz"
    exit 1
}

# Parse command line arguments
while [ $# -gt 0 ]; do
    case $1 in
        -s|--source)
            SOURCE="$2"
            shift 2
            ;;
        -h|--help)
            usage
            ;;
        -*)
            error "Unknown option: $1"
            usage
            ;;
        *)
            if [ -z "$BACKUP_FILE" ]; then
                BACKUP_FILE="$1"
            else
                error "Multiple backup files specified"
                usage
            fi
            shift
            ;;
    esac
done

# Validate arguments
if [ -z "$BACKUP_FILE" ]; then
    error "Backup file not specified"
    usage
fi

if [ "$SOURCE" != "local" ] && [ "$SOURCE" != "s3" ]; then
    error "Invalid source: $SOURCE (must be 'local' or 's3')"
    usage
fi

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites..."
    
    # Check MySQL password
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        if [ -n "$MYSQL_ROOT_PASSWORD_FILE" ] && [ -f "$MYSQL_ROOT_PASSWORD_FILE" ]; then
            export MYSQL_ROOT_PASSWORD=$(cat "$MYSQL_ROOT_PASSWORD_FILE")
        else
            error "MySQL root password not available"
            return 1
        fi
    fi
    
    # Check database connectivity
    if ! nc -z "$MYSQL_HOST" 3306; then
        error "Cannot connect to database at $MYSQL_HOST:3306"
        return 1
    fi
    
    # Check AWS CLI for S3 source
    if [ "$SOURCE" = "s3" ] && ! command -v aws >/dev/null 2>&1; then
        error "AWS CLI not available for S3 restore"
        return 1
    fi
    
    success "Prerequisites check passed"
}

# Download backup from S3
download_from_s3() {
    log "Downloading backup from S3..."
    
    local s3_path="s3://${S3_BUCKET}/backups/${BACKUP_FILE}"
    local local_path="${RESTORE_DIR}/${BACKUP_FILE}"
    
    if ! aws s3 cp "$s3_path" "$local_path"; then
        error "Failed to download backup from S3"
        return 1
    fi
    
    BACKUP_FILE="$local_path"
    success "Backup downloaded from S3"
}

# Extract backup archive
extract_backup() {
    log "Extracting backup archive..."
    
    local extract_dir="${RESTORE_DIR}/extracted"
    mkdir -p "$extract_dir"
    
    if ! tar -xzf "$BACKUP_FILE" -C "$extract_dir"; then
        error "Failed to extract backup archive"
        return 1
    fi
    
    # Find extracted files
    DATABASE_FILE=$(find "$extract_dir" -name "database_*.sql" -type f | head -1)
    STORAGE_FILE=$(find "$extract_dir" -name "storage_*.tar.gz" -type f | head -1)
    
    if [ -z "$DATABASE_FILE" ]; then
        error "Database dump not found in backup"
        return 1
    fi
    
    if [ -z "$STORAGE_FILE" ]; then
        warning "Storage archive not found in backup"
    fi
    
    success "Backup archive extracted successfully"
}

# Restore database
restore_database() {
    log "Starting database restore..."
    
    # Create backup of current database
    local current_backup="${RESTORE_DIR}/current_db_backup_$(date +%Y%m%d_%H%M%S).sql"
    log "Creating backup of current database..."
    
    if ! mysqldump -h "$MYSQL_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" \
        --single-transaction --routines --triggers --all-databases \
        > "$current_backup"; then
        error "Failed to backup current database"
        return 1
    fi
    
    log "Current database backed up to: $current_backup"
    
    # Restore from backup
    log "Restoring database from backup..."
    
    if ! mysql -h "$MYSQL_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" < "$DATABASE_FILE"; then
        error "Database restore failed"
        log "Attempting to restore original database..."
        
        if mysql -h "$MYSQL_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" < "$current_backup"; then
            warning "Original database restored after failed restore"
        else
            error "Failed to restore original database - manual intervention required"
        fi
        
        return 1
    fi
    
    success "Database restored successfully"
}

# Restore application files
restore_files() {
    if [ -z "$STORAGE_FILE" ]; then
        log "No storage files to restore"
        return 0
    fi
    
    log "Starting application files restore..."
    
    # Create backup of current storage
    local current_storage_backup="${RESTORE_DIR}/current_storage_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    log "Creating backup of current storage..."
    
    if tar -czf "$current_storage_backup" -C /backup storage/ logs/ 2>/dev/null; then
        log "Current storage backed up to: $current_storage_backup"
    else
        warning "Failed to backup current storage"
    fi
    
    # Extract storage files
    local storage_extract="${RESTORE_DIR}/storage_extracted"
    mkdir -p "$storage_extract"
    
    if ! tar -xzf "$STORAGE_FILE" -C "$storage_extract"; then
        error "Failed to extract storage archive"
        return 1
    fi
    
    # Copy restored files (this would need to be coordinated with the running application)
    log "Storage files extracted to: $storage_extract"
    warning "Manual intervention required to apply storage files to running containers"
    
    success "Storage files restore completed"
}

# Verify restoration
verify_restore() {
    log "Verifying restoration..."
    
    # Check database connectivity
    if ! mysql -h "$MYSQL_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES;" >/dev/null 2>&1; then
        error "Database verification failed"
        return 1
    fi
    
    # Check for expected tables
    if ! mysql -h "$MYSQL_HOST" -u root -p"$MYSQL_ROOT_PASSWORD" -e "USE coderstew_prod; SHOW TABLES;" >/dev/null 2>&1; then
        error "Application database verification failed"
        return 1
    fi
    
    success "Restoration verification passed"
}

# Cleanup restore files
cleanup_restore() {
    log "Cleaning up restore files..."
    
    if [ -d "${RESTORE_DIR}/extracted" ]; then
        rm -rf "${RESTORE_DIR}/extracted"
    fi
    
    if [ -d "${RESTORE_DIR}/storage_extracted" ]; then
        rm -rf "${RESTORE_DIR}/storage_extracted"
    fi
    
    success "Cleanup completed"
}

# Main restore function
main() {
    local start_time=$(date +%s)
    
    log "Starting restore process..."
    log "Backup file: $BACKUP_FILE"
    log "Source: $SOURCE"
    
    # Create restore directory
    mkdir -p "$RESTORE_DIR"
    
    # Confirm restore operation
    echo ""
    warning "WARNING: This will overwrite the current database!"
    warning "Current database will be backed up, but this is a destructive operation."
    echo ""
    printf "Are you sure you want to continue? (yes/no): "
    read -r confirm
    
    if [ "$confirm" != "yes" ]; then
        log "Restore operation cancelled by user"
        exit 0
    fi
    
    # Execute restore steps
    if check_prerequisites && \
       { [ "$SOURCE" != "s3" ] || download_from_s3; } && \
       { [ "$SOURCE" != "local" ] || BACKUP_FILE="/backup/archives/$BACKUP_FILE"; } && \
       extract_backup && \
       restore_database && \
       restore_files && \
       verify_restore && \
       cleanup_restore; then
        
        local end_time=$(date +%s)
        local duration=$((end_time - start_time))
        
        success "Restore completed successfully in ${duration} seconds"
        log "Database restored from: $DATABASE_FILE"
        [ -n "$STORAGE_FILE" ] && log "Storage files restored from: $STORAGE_FILE"
        
        return 0
    else
        error "Restore process failed"
        return 1
    fi
}

# Run main function
main "$@"
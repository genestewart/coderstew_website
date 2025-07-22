#!/bin/bash

# CoderStew Website Backup Manager
# Management script for backup operations, monitoring, and restore

set -e

# Configuration
BACKUP_CONTAINER="coderstew_backup_prod"
COMPOSE_FILE="docker-compose.prod.yml"
BACKUP_LOG_FILE="/tmp/backup-manager.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if backup service is running
check_backup_service() {
    if ! docker compose -f "$COMPOSE_FILE" ps backup | grep -q "Up"; then
        print_error "Backup service is not running"
        return 1
    fi
    return 0
}

# Run backup immediately
run_backup() {
    print_status "Starting manual backup..."
    
    if ! check_backup_service; then
        return 1
    fi
    
    if docker compose -f "$COMPOSE_FILE" exec -T backup /usr/local/bin/backup.sh backup; then
        print_success "Manual backup completed successfully"
    else
        print_error "Manual backup failed"
        return 1
    fi
}

# Check backup health
check_health() {
    print_status "Checking backup system health..."
    
    if ! check_backup_service; then
        return 1
    fi
    
    if docker compose -f "$COMPOSE_FILE" exec -T backup /usr/local/bin/backup.sh health; then
        print_success "Backup system health check passed"
    else
        print_error "Backup system health check failed"
        return 1
    fi
}

# List available backups
list_backups() {
    print_status "Listing available backups..."
    
    echo ""
    echo "📁 Local backups:"
    docker compose -f "$COMPOSE_FILE" exec -T backup ls -la /backup/archives/
    
    echo ""
    echo "☁️  S3 backups (if configured):"
    if docker compose -f "$COMPOSE_FILE" exec -T backup /usr/local/bin/backup.sh health | grep -q "S3 connectivity check failed"; then
        print_warning "S3 not configured or accessible"
    else
        docker compose -f "$COMPOSE_FILE" exec -T backup aws s3 ls s3://\${S3_BUCKET}/backups/ 2>/dev/null || print_warning "Failed to list S3 backups"
    fi
}

# Show backup logs
show_logs() {
    local lines=${1:-50}
    
    print_status "Showing last $lines lines of backup logs..."
    
    echo ""
    echo "🔍 Backup service logs:"
    docker compose -f "$COMPOSE_FILE" logs --tail="$lines" backup
    
    echo ""
    echo "📋 Backup operation logs:"
    docker compose -f "$COMPOSE_FILE" exec -T backup tail -n "$lines" /var/log/backup/backup.log 2>/dev/null || print_warning "Backup log file not available"
}

# Monitor backup service
monitor() {
    print_status "Monitoring backup service (press Ctrl+C to stop)..."
    
    echo ""
    echo "📊 Service status:"
    docker compose -f "$COMPOSE_FILE" ps backup
    
    echo ""
    echo "📈 Resource usage:"
    docker stats "$BACKUP_CONTAINER" --no-stream
    
    echo ""
    echo "🔄 Following backup logs..."
    docker compose -f "$COMPOSE_FILE" logs -f backup
}

# Restore from backup
restore() {
    local backup_file="$1"
    local source="${2:-local}"
    
    if [ -z "$backup_file" ]; then
        print_error "Backup file not specified"
        echo "Usage: $0 restore BACKUP_FILE [local|s3]"
        return 1
    fi
    
    print_warning "This will restore the database and application files from backup"
    print_warning "Current data will be backed up but this is a destructive operation"
    
    echo ""
    read -p "Are you sure you want to continue? (yes/no): " confirm
    
    if [ "$confirm" != "yes" ]; then
        print_status "Restore operation cancelled"
        return 0
    fi
    
    print_status "Starting restore from backup: $backup_file"
    
    if docker compose -f "$COMPOSE_FILE" exec -T backup /usr/local/bin/restore.sh -s "$source" "$backup_file"; then
        print_success "Restore completed successfully"
    else
        print_error "Restore failed"
        return 1
    fi
}

# Test backup system
test_backup() {
    print_status "Testing backup system..."
    
    echo ""
    echo "1. Checking service status..."
    if ! check_backup_service; then
        return 1
    fi
    
    echo ""
    echo "2. Running health check..."
    if ! check_health; then
        return 1
    fi
    
    echo ""
    echo "3. Testing backup creation..."
    if ! run_backup; then
        return 1
    fi
    
    echo ""
    echo "4. Verifying backup files..."
    if docker compose -f "$COMPOSE_FILE" exec -T backup ls -la /backup/archives/ | grep -q "coderstew_backup_"; then
        print_success "Backup files found"
    else
        print_error "No backup files found"
        return 1
    fi
    
    print_success "All backup system tests passed!"
}

# Show backup statistics
stats() {
    print_status "Backup system statistics..."
    
    echo ""
    echo "📊 Service Information:"
    echo "----------------------"
    docker compose -f "$COMPOSE_FILE" ps backup
    
    echo ""
    echo "💾 Storage Usage:"
    echo "----------------"
    docker compose -f "$COMPOSE_FILE" exec -T backup df -h /backup/
    
    echo ""
    echo "📁 Local Backups:"
    echo "-----------------"
    docker compose -f "$COMPOSE_FILE" exec -T backup du -sh /backup/archives/* 2>/dev/null || echo "No backups found"
    
    echo ""
    echo "🕐 Last Backup Activity:"
    echo "------------------------"
    docker compose -f "$COMPOSE_FILE" exec -T backup tail -n 5 /var/log/backup/backup.log 2>/dev/null || echo "No recent activity"
    
    echo ""
    echo "⚙️  Configuration:"
    echo "-----------------"
    echo "Retention: ${BACKUP_RETENTION_DAYS:-30} days"
    echo "Schedule: Daily at 2 AM UTC"
    echo "S3 Bucket: ${BACKUP_S3_BUCKET:-Not configured}"
}

# Cleanup old backups
cleanup() {
    print_status "Running backup cleanup..."
    
    if ! check_backup_service; then
        return 1
    fi
    
    if docker compose -f "$COMPOSE_FILE" exec -T backup /usr/local/bin/backup.sh cleanup; then
        print_success "Backup cleanup completed"
    else
        print_error "Backup cleanup failed"
        return 1
    fi
}

# Show usage
usage() {
    echo "CoderStew Website Backup Manager"
    echo "==============================="
    echo ""
    echo "Usage: $0 COMMAND [OPTIONS]"
    echo ""
    echo "Commands:"
    echo "  backup              Run backup immediately"
    echo "  restore FILE [SRC]  Restore from backup (SRC: local|s3)"
    echo "  list                List available backups"
    echo "  logs [LINES]        Show backup logs (default: 50 lines)"
    echo "  monitor             Monitor backup service in real-time"
    echo "  health              Check backup system health"
    echo "  test                Run comprehensive backup system test"
    echo "  stats               Show backup statistics"
    echo "  cleanup             Clean up old backups"
    echo "  help                Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 backup                                    # Run backup now"
    echo "  $0 restore coderstew_backup_20250721.tar.gz # Restore from local backup"
    echo "  $0 restore latest.tar.gz s3                 # Restore latest from S3"
    echo "  $0 logs 100                                 # Show last 100 log lines"
    echo "  $0 test                                     # Test backup system"
}

# Main command handler
main() {
    case "${1:-help}" in
        "backup")
            run_backup
            ;;
        "restore")
            restore "$2" "$3"
            ;;
        "list")
            list_backups
            ;;
        "logs")
            show_logs "$2"
            ;;
        "monitor")
            monitor
            ;;
        "health")
            check_health
            ;;
        "test")
            test_backup
            ;;
        "stats")
            stats
            ;;
        "cleanup")
            cleanup
            ;;
        "help"|"--help"|"-h")
            usage
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            usage
            exit 1
            ;;
    esac
}

# Run main function
main "$@"
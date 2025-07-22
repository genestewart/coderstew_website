#!/bin/sh

# Backup Service Entrypoint Script
# Initializes the backup service with proper cron setup and dependencies

set -e

echo "🚀 Starting CoderStew Backup Service"
echo "======================================"

# Install required packages
apk add --no-cache \
    mysql-client \
    aws-cli \
    tar \
    gzip \
    netcat-openbsd \
    curl

# Set up logging
mkdir -p /var/log/backup
touch /var/log/backup/backup.log
touch /var/log/backup/cron.log

# Read MySQL password from secrets
if [ -f "$MYSQL_ROOT_PASSWORD_FILE" ]; then
    export MYSQL_ROOT_PASSWORD=$(cat "$MYSQL_ROOT_PASSWORD_FILE")
else
    echo "❌ MySQL password secret file not found"
    exit 1
fi

# Validate environment variables
if [ -z "$AWS_ACCESS_KEY_ID" ] || [ -z "$AWS_SECRET_ACCESS_KEY" ] || [ -z "$S3_BUCKET" ]; then
    echo "⚠️  AWS credentials not fully configured. S3 backup will be disabled."
    export AWS_DISABLED="true"
fi

# Test database connectivity
echo "🔍 Testing database connectivity..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if nc -z coderstew_db_prod 3306 2>/dev/null; then
        echo "✅ Database connection established"
        break
    fi
    echo "⏳ Waiting for database... (attempt $attempt/$max_attempts)"
    sleep 10
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "❌ Failed to connect to database after $max_attempts attempts"
    exit 1
fi

# Test S3 connectivity (if configured)
if [ "$AWS_DISABLED" != "true" ]; then
    echo "🔍 Testing S3 connectivity..."
    if aws s3 ls "s3://$S3_BUCKET/" >/dev/null 2>&1; then
        echo "✅ S3 connection established"
    else
        echo "⚠️  S3 connection failed. Backups will be stored locally only."
        export AWS_DISABLED="true"
    fi
fi

# Set up crontab
echo "📅 Setting up backup schedule..."
echo "Backup schedule: $BACKUP_SCHEDULE"

# Create dynamic crontab
cat > /tmp/crontab << EOF
# CoderStew Website Backup Schedule
# Generated at $(date)

# Daily backup at 2 AM UTC
$BACKUP_SCHEDULE /usr/local/bin/backup.sh backup >> /var/log/backup/backup.log 2>&1

# Weekly health check on Sundays at 1 AM UTC
0 1 * * 0 /usr/local/bin/backup.sh health >> /var/log/backup/backup.log 2>&1

# Monthly cleanup on the 1st at 3 AM UTC
0 3 1 * * /usr/local/bin/backup.sh cleanup >> /var/log/backup/backup.log 2>&1

EOF

# Install crontab
crontab /tmp/crontab
rm /tmp/crontab

# Make backup script executable
chmod +x /usr/local/bin/backup.sh

# Create backup directories
mkdir -p /backup/archives
mkdir -p /backup/temp

# Log startup information
cat >> /var/log/backup/backup.log << EOF

============================================
Backup Service Started: $(date)
============================================
Schedule: $BACKUP_SCHEDULE
Retention: ${BACKUP_RETENTION_DAYS} days
S3 Bucket: ${S3_BUCKET}
AWS Disabled: ${AWS_DISABLED:-false}
Timezone: ${TZ:-UTC}
============================================

EOF

echo "✅ Backup service initialized successfully"
echo "📊 Service information:"
echo "   - Schedule: $BACKUP_SCHEDULE"
echo "   - Retention: ${BACKUP_RETENTION_DAYS} days"
echo "   - S3 Bucket: ${S3_BUCKET}"
echo "   - AWS Status: ${AWS_DISABLED:+Disabled}"
echo "   - Log file: /var/log/backup/backup.log"

# Run initial health check
echo "🏥 Running initial health check..."
if /usr/local/bin/backup.sh health; then
    echo "✅ Health check passed"
else
    echo "⚠️  Health check failed - check logs for details"
fi

# Start cron daemon
echo "🔄 Starting cron daemon..."
exec crond -f -d 8 -L /var/log/backup/cron.log
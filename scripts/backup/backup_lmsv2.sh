#!/bin/bash
# backup_lmsv2.sh — Full backup for LMS V2 (PostgreSQL + pgvector)
set -e

BACKUP_DIR="$(cd "$(dirname "$0")/../../backups" && pwd)"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_CONTAINER="lmsv2-db"
WEB_CONTAINER="lmsv2-web"
DB_USER="moodlev2user"
DB_NAME="moodlev2"

echo "=== LMS V2 Backup — $TIMESTAMP ==="

# 1. Backup PostgreSQL database
echo "--- Backing up PostgreSQL database ---"
docker exec "$DB_CONTAINER" pg_dump -U "$DB_USER" -d "$DB_NAME" --clean --if-exists \
    > "$BACKUP_DIR/lmsv2_db_${TIMESTAMP}.sql"
echo "  DB dump: lmsv2_db_${TIMESTAMP}.sql ($(du -h "$BACKUP_DIR/lmsv2_db_${TIMESTAMP}.sql" | cut -f1))"

# 2. Backup moodledata (excluding cache/sessions)
echo "--- Backing up moodledata ---"
docker exec "$WEB_CONTAINER" tar czf /tmp/moodledata_backup.tar.gz \
    --exclude='cache' --exclude='sessions' --exclude='localcache' \
    --exclude='muc' --exclude='temp' --exclude='trashdir' \
    -C / moodledata
docker cp "$WEB_CONTAINER":/tmp/moodledata_backup.tar.gz \
    "$BACKUP_DIR/lmsv2_moodledata_${TIMESTAMP}.tar.gz"
docker exec "$WEB_CONTAINER" rm /tmp/moodledata_backup.tar.gz
echo "  Moodledata: lmsv2_moodledata_${TIMESTAMP}.tar.gz ($(du -h "$BACKUP_DIR/lmsv2_moodledata_${TIMESTAMP}.tar.gz" | cut -f1))"

# 3. Backup Moodle config
echo "--- Backing up config ---"
docker cp "$WEB_CONTAINER":/var/www/html/moodle/config.php \
    "$BACKUP_DIR/lmsv2_config_${TIMESTAMP}.php"

# 4. Create symlinks to latest
ln -sf "lmsv2_db_${TIMESTAMP}.sql" "$BACKUP_DIR/latest.sql"
ln -sf "lmsv2_moodledata_${TIMESTAMP}.tar.gz" "$BACKUP_DIR/latest_moodledata.tar.gz"

echo ""
echo "=== Backup complete ==="
echo "  DB:        $BACKUP_DIR/lmsv2_db_${TIMESTAMP}.sql"
echo "  Data:      $BACKUP_DIR/lmsv2_moodledata_${TIMESTAMP}.tar.gz"
echo "  Config:    $BACKUP_DIR/lmsv2_config_${TIMESTAMP}.php"

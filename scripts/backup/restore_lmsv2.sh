#!/bin/bash
# restore_lmsv2.sh — Restore LMS V2 from backup
# Usage: ./restore_lmsv2.sh [backup_timestamp]
# If no timestamp given, uses latest symlinks
set -e

BACKUP_DIR="$(cd "$(dirname "$0")/../../backups" && pwd)"
DB_CONTAINER="lmsv2-db"
WEB_CONTAINER="lmsv2-web"
DB_USER="moodlev2user"
DB_NAME="moodlev2"

TIMESTAMP="${1:-latest}"

if [ "$TIMESTAMP" = "latest" ]; then
    SQL_FILE="$BACKUP_DIR/latest.sql"
    DATA_FILE="$BACKUP_DIR/latest_moodledata.tar.gz"
else
    SQL_FILE="$BACKUP_DIR/lmsv2_db_${TIMESTAMP}.sql"
    DATA_FILE="$BACKUP_DIR/lmsv2_moodledata_${TIMESTAMP}.tar.gz"
fi

echo "=== LMS V2 Restore ==="

# Verify files exist
if [ ! -f "$SQL_FILE" ]; then
    echo "ERROR: SQL file not found: $SQL_FILE"
    exit 1
fi
if [ ! -f "$DATA_FILE" ]; then
    echo "ERROR: Moodledata file not found: $DATA_FILE"
    exit 1
fi

echo "  SQL: $SQL_FILE ($(du -h "$SQL_FILE" | cut -f1))"
echo "  Data: $DATA_FILE ($(du -h "$DATA_FILE" | cut -f1))"
echo ""

read -p "This will OVERWRITE the current database and moodledata. Continue? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Aborted."
    exit 0
fi

# 1. Restore PostgreSQL database
echo "--- Restoring database ---"
cat "$SQL_FILE" | docker exec -i "$DB_CONTAINER" psql -U "$DB_USER" -d "$DB_NAME" 2>&1 | tail -5
echo "  Database restored."

# 2. Restore moodledata
echo "--- Restoring moodledata ---"
docker cp "$DATA_FILE" "$WEB_CONTAINER":/tmp/moodledata_restore.tar.gz
docker exec "$WEB_CONTAINER" bash -c "cd / && tar xzf /tmp/moodledata_restore.tar.gz && rm /tmp/moodledata_restore.tar.gz"
docker exec "$WEB_CONTAINER" chown -R www-data:www-data /moodledata
echo "  Moodledata restored."

# 3. Clear caches
echo "--- Clearing Moodle caches ---"
docker exec "$WEB_CONTAINER" php /var/www/html/moodle/admin/cli/purge_caches.php 2>/dev/null || true
echo "  Caches purged."

echo ""
echo "=== Restore complete ==="
echo "  URL: http://159.65.149.161:10183/lmsv2/"

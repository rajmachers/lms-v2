#!/bin/bash
# restore_moodle.sh — Restore Moodle 5.0.1 instance from backup
# Date: 2026-03-02
set -e

# Variables
BACKUP_ARCHIVE="$1" # Path to backup tar.gz
RESTORE_DIR="/root/moodle_restore_$(date +%Y%m%d_%H%M%S)"
CONTAINER_NAME="moodle"
DB_USER="moodleuser"
DB_PASS="moodleAdmin1," # Note: comma is part of password
DB_NAME="moodle"
DB_HOST="localhost"
MOODLEDATA="/var/www/moodle/moodledata"
APACHE_CONF="/etc/apache2/sites-available/000-default.conf"
CONFIG_PHP="/var/www/moodle/public/config.php"

if [ -z "$BACKUP_ARCHIVE" ]; then
  echo "Usage: $0 <backup_archive.tar.gz>"
  exit 1
fi

mkdir -p "$RESTORE_DIR"
tar xzf "$BACKUP_ARCHIVE" -C "$RESTORE_DIR"
BACKUP_DIR=$(find "$RESTORE_DIR" -maxdepth 1 -type d -name 'moodle_backup_*' | head -n1)

# 1. Stop and remove current container
if docker ps -qf "name=$CONTAINER_NAME"; then
  docker stop "$CONTAINER_NAME"
  docker rm "$CONTAINER_NAME"
fi

# 2. Load Docker image
IMAGE_TAR="$BACKUP_DIR/moodle_docker_image.tar"
docker load -i "$IMAGE_TAR"
IMAGE_NAME=$(docker images --format '{{.Repository}}:{{.Tag}}' | grep 'moodle_backup_')

# 3. Restore moodledata (rename current for safety)
if [ -d "$MOODLEDATA" ]; then
  mv "$MOODLEDATA" "${MOODLEDATA}_old_$(date +%Y%m%d_%H%M%S)"
fi
cp -a "$BACKUP_DIR/moodledata" "$MOODLEDATA"

# 4. Restore config.php
cp "$BACKUP_DIR/config.php" "$CONFIG_PHP"

# 5. Restore Apache config
cp "$BACKUP_DIR/apache.conf" "$APACHE_CONF"
service apache2 reload

# 6. Start new container with saved run command and port mappings
RUN_CMD=$(cat "$BACKUP_DIR/container_run_cmd.txt")
PORTS=$(cat "$BACKUP_DIR/port_mappings.txt")
# You may need to manually reconstruct the docker run command using RUN_CMD and PORTS
# Example:
# docker run -d -p 80:80 -p 10181:9001 --name "$CONTAINER_NAME" "$IMAGE_NAME"

echo "Run this command to start the container (edit as needed):"
echo "docker run -d -p 80:80 -p 10181:9001 --name $CONTAINER_NAME $IMAGE_NAME"

# 7. Drop and restore database
mysql -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;"
mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_DIR/moodle_db.sql"

# 8. Clear caches
rm -rf "$MOODLEDATA/cache" "$MOODLEDATA/sessions"

# 9. Print completion
echo "Restore complete. Verify Moodle at /lms."

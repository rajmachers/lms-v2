#!/bin/bash
# backup_moodle.sh — Full backup for Moodle 5.0.1 instance
# Date: 2026-03-02
set -e

# Variables

CONTAINER_NAME="naughty_ganguly"
IMAGE_NAME="moodle_backup_$(date +%Y%m%d_%H%M%S)"
DB_USER="moodleuser"
DB_PASS="moodleAdmin1," # Note: comma is part of password
DB_NAME="moodle"
DB_HOST="localhost"
MOODLEDATA="/moodledata"
APACHE_CONF="/etc/apache2/sites-available/000-default.conf"
CONFIG_PHP="/var/www/moodle/public/config.php"

# Write all backup files directly to /home/lms/backups
BACKUP_BASENAME="moodle_backup_$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="/home/lms/backups"


# 1. Backup Docker image
IMAGE_ID=$(docker ps -qf "name=$CONTAINER_NAME")
docker commit "$IMAGE_ID" "$IMAGE_NAME"
docker save -o "$BACKUP_DIR/${BACKUP_BASENAME}_docker_image.tar" "$IMAGE_NAME"

# 2. Backup moodledata (excluding cache/sessions)
rsync -a --exclude='cache/' --exclude='sessions/' "$MOODLEDATA" "$BACKUP_DIR/${BACKUP_BASENAME}_moodledata"

# 3. Backup database
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/${BACKUP_BASENAME}_db.sql"

# 4. Backup config.php
cp "$CONFIG_PHP" "$BACKUP_DIR/${BACKUP_BASENAME}_config.php"

# 5. Backup Apache config
cp "$APACHE_CONF" "$BACKUP_DIR/${BACKUP_BASENAME}_apache.conf"

# 6. Save container run command
CONTAINER_RUN_CMD=$(docker inspect "$CONTAINER_NAME" --format '{{.Config.Cmd}}')
echo "$CONTAINER_RUN_CMD" > "$BACKUP_DIR/${BACKUP_BASENAME}_container_run_cmd.txt"

# 7. Save port mappings
PORTS=$(docker port "$CONTAINER_NAME")
echo "$PORTS" > "$BACKUP_DIR/${BACKUP_BASENAME}_port_mappings.txt"

# 8. Tar everything for easy transfer
cd "$BACKUP_DIR"
tar czf "${BACKUP_BASENAME}.tar.gz" ${BACKUP_BASENAME}_*

# 9. Print completion
echo "Backup complete: $BACKUP_DIR/${BACKUP_BASENAME}.tar.gz"

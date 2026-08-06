#!/usr/bin/env bash
# ==============================================================================
# Enterprise RADIUS Database Restore Script
# Usage: ./restore_db.sh /path/to/backup.sql.gz
# ==============================================================================

set -e

if [ -z "$1" ]; then
    echo "[ERROR] Usage: $0 <path_to_backup_file.sql.gz>"
    exit 1
fi

BACKUP_FILE="$1"
DB_NAME="radius"
DB_USER="radius_user"
DB_PASS="radius_password"
DB_HOST="localhost"

if [ ! -f "${BACKUP_FILE}" ]; then
    echo "[ERROR] File not found: ${BACKUP_FILE}"
    exit 1
fi

echo "[WARNING] Restoring database '${DB_NAME}' from '${BACKUP_FILE}'..."
read -p "Are you sure you want to overwrite database '${DB_NAME}'? (y/N) " confirm

if [[ "$confirm" =~ ^[Yy]$ ]]; then
    zcat "${BACKUP_FILE}" | mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
    echo "[SUCCESS] Database successfully restored."
else
    echo "[CANCELLED] Restore aborted by user."
fi

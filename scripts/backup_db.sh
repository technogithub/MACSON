#!/usr/bin/env bash
# ==============================================================================
# Enterprise RADIUS Database Automated Backup Script
# ==============================================================================

set -e

BACKUP_DIR="/var/backups/omni-radius"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DB_NAME="radius"
DB_USER="radius_user"
DB_PASS="radius_password"
DB_HOST="localhost"
RETENTION_DAYS=14

mkdir -p "${BACKUP_DIR}"

BACKUP_FILE="${BACKUP_DIR}/radius_backup_${TIMESTAMP}.sql.gz"

echo "[INFO] Starting database backup for '${DB_NAME}' at $(date)..."

mysqldump -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_NAME}" | gzip -9 > "${BACKUP_FILE}"

echo "[SUCCESS] Database backup saved to: ${BACKUP_FILE}"

# Cleanup old backups older than 14 days
echo "[INFO] Cleaning backups older than ${RETENTION_DAYS} days..."
find "${BACKUP_DIR}" -type f -name "radius_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete

echo "[COMPLETE] Backup cycle finished successfully."

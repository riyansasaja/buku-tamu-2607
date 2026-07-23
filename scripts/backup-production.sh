#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

: "${BACKUP_DIR:?BACKUP_DIR wajib diisi}"
: "${DB_HOST:?DB_HOST wajib diisi}"
: "${DB_PORT:=3306}"
: "${DB_DATABASE:?DB_DATABASE wajib diisi}"
: "${DB_USERNAME:?DB_USERNAME wajib diisi}"
: "${DB_PASSWORD:?DB_PASSWORD wajib diisi}"
: "${APP_STORAGE_PATH:?APP_STORAGE_PATH wajib diisi}"

[[ "$BACKUP_DIR" != "/" && ${#BACKUP_DIR} -gt 5 ]] || { echo "BACKUP_DIR terlalu luas/tidak aman"; exit 2; }

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="${BACKUP_DIR%/}/${timestamp}"
mkdir -p "$target"

export MYSQL_PWD="$DB_PASSWORD"
mysqldump --single-transaction --quick --routines --triggers --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE" | gzip -9 > "$target/database.sql.gz"
unset MYSQL_PWD DB_PASSWORD

tar -C "$APP_STORAGE_PATH" -czf "$target/private-storage.tar.gz" app/private
sha256sum "$target/database.sql.gz" "$target/private-storage.tar.gz" > "$target/SHA256SUMS"

find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +30 -exec rm -rf -- {} +
echo "Backup selesai: $target"

#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

: "${RESTORE_SOURCE:?RESTORE_SOURCE wajib diisi}"
: "${DB_HOST:?DB_HOST wajib diisi}"
: "${DB_PORT:=3306}"
: "${DB_DATABASE:?DB_DATABASE wajib diisi}"
: "${DB_USERNAME:?DB_USERNAME wajib diisi}"
: "${DB_PASSWORD:?DB_PASSWORD wajib diisi}"
: "${APP_STORAGE_PATH:?APP_STORAGE_PATH wajib diisi}"
: "${CONFIRM_RESTORE:?Isi CONFIRM_RESTORE dengan nama database}"

[[ "$CONFIRM_RESTORE" == "$DB_DATABASE" ]] || { echo "Konfirmasi database tidak cocok"; exit 2; }
cd "$RESTORE_SOURCE"
sha256sum --check SHA256SUMS

export MYSQL_PWD="$DB_PASSWORD"
gunzip -c database.sql.gz | mysql --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE"
unset MYSQL_PWD DB_PASSWORD
tar -C "$APP_STORAGE_PATH" -xzf private-storage.tar.gz
echo "Restore selesai. Jalankan pemeriksaan aplikasi dan catat durasinya."

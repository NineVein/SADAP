#!/usr/bin/env bash
# Uji pulihkan backup DISKATmsa ke database sementara. TIDAK menyentuh database produksi.
# Pemakaian: bash scripts/uji-pulihkan.sh /path/ke/backup.zip
# Env: DB_HOST (default 127.0.0.1), DB_PORT (3306), DB_USERNAME, DB_PASSWORD, BACKUP_ARCHIVE_PASSWORD (bila zip bersandi)
set -euo pipefail

ZIP="${1:?Pemakaian: bash scripts/uji-pulihkan.sh /path/ke/backup.zip}"
HOST="${DB_HOST:-127.0.0.1}"; PORT="${DB_PORT:-3306}"
USER_DB="${DB_USERNAME:?Isi DB_USERNAME}"; export MYSQL_PWD="${DB_PASSWORD:-}"
UJI_DB="diskat_uji_pulih"
TMP="$(mktemp -d)"
mysql_cmd=(mysql -h "$HOST" -P "$PORT" -u "$USER_DB")

bersihkan() { "${mysql_cmd[@]}" -e "DROP DATABASE IF EXISTS \`$UJI_DB\`" 2>/dev/null || true; rm -rf "$TMP"; }
trap bersihkan EXIT

echo "==> 1. Membuka berkas backup"
if [[ -n "${BACKUP_ARCHIVE_PASSWORD:-}" ]]; then
  command -v 7z >/dev/null || { echo "Zip bersandi AES butuh 7z (apt install p7zip-full)"; exit 1; }
  7z x -y -p"$BACKUP_ARCHIVE_PASSWORD" -o"$TMP" "$ZIP" >/dev/null
else
  unzip -q "$ZIP" -d "$TMP"
fi

DUMP="$(find "$TMP" -path '*db-dumps*' \( -name '*.sql' -o -name '*.sql.gz' \) | head -n 1)"
[[ -n "$DUMP" ]] || { echo "GAGAL: dump SQL tidak ditemukan di backup"; exit 1; }
echo "    dump: ${DUMP#"$TMP"/}"

echo "==> 2. Mengimpor ke database sementara $UJI_DB"
"${mysql_cmd[@]}" -e "DROP DATABASE IF EXISTS \`$UJI_DB\`; CREATE DATABASE \`$UJI_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if [[ "$DUMP" == *.gz ]]; then gunzip -c "$DUMP" | "${mysql_cmd[@]}" "$UJI_DB"; else "${mysql_cmd[@]}" "$UJI_DB" < "$DUMP"; fi

echo "==> 3. Jumlah baris tabel utama (bandingkan dengan produksi)"
for t in users pantis anaks pengurus dokumens activity_logs; do
  n="$("${mysql_cmd[@]}" -N -e "SELECT COUNT(*) FROM \`$UJI_DB\`.\`$t\`" 2>/dev/null || echo 'TABEL TIDAK ADA')"
  printf '    %-14s %s\n' "$t" "$n"
done

echo "==> 4. Berkas dokumen di backup"
JUMLAH="$(find "$TMP" -path '*storage/app/private/dokumen*' -type f | wc -l)"
echo "    $JUMLAH berkas"

echo
echo "SELESAI: backup dapat dibuka dan database dapat dipulihkan."
echo "Bandingkan angka di atas dengan produksi, lalu catat tanggal dan hasil uji ini."

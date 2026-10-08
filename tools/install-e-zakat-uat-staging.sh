#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="/var/www/e-zakat-uat"
POOL_SOURCE="$ROOT/docs/php84/e-zakat-uat84.pool.conf"
POOL_TARGET="/etc/php/8.4/fpm/pool.d/e-zakat-uat84.conf"
NGINX_SOURCE="$ROOT/docs/php84/e-zakat-uat.nginx-php84.conf"
NGINX_TARGET="/etc/nginx/sites-available/e-zakat-uat"
NGINX_LINK="/etc/nginx/sites-enabled/e-zakat-uat"
SOCKET="/run/php/e-zakat-uat84.sock"
HOST="zakat-uat.upnm.edu.my"
BACKUP_DIR="/var/www/_migration_backups/e-zakat-uat-install-$(date +%Y%m%d-%H%M%S)"

[[ ${EUID} -eq 0 ]] || { echo "ERROR: Jalankan menggunakan sudo." >&2; exit 1; }
for path in "$POOL_SOURCE" "$NGINX_SOURCE" "$ROOT/.env"; do
  [[ -f "$path" ]] || { echo "ERROR: Fail tiada: $path" >&2; exit 1; }
done

install -d -o root -g root -m 0750 "$BACKUP_DIR"
[[ ! -f "$POOL_TARGET" ]] || cp -p "$POOL_TARGET" "$BACKUP_DIR/pool.before"
[[ ! -f "$NGINX_TARGET" ]] || cp -p "$NGINX_TARGET" "$BACKUP_DIR/nginx.before"

rollback() {
  trap - ERR
  echo "ROLLBACK: Memulihkan konfigurasi e-Zakat UAT..." >&2
  if [[ -f "$BACKUP_DIR/pool.before" ]]; then cp -p "$BACKUP_DIR/pool.before" "$POOL_TARGET"; else rm -f "$POOL_TARGET"; fi
  if [[ -f "$BACKUP_DIR/nginx.before" ]]; then cp -p "$BACKUP_DIR/nginx.before" "$NGINX_TARGET"; else rm -f "$NGINX_TARGET" "$NGINX_LINK"; fi
  php-fpm8.4 -t && systemctl reload php8.4-fpm || true
  nginx -t && systemctl reload nginx || true
}
trap 'echo "ERROR: Pemasangan e-Zakat UAT gagal." >&2; rollback' ERR

chown iqs:www-data "$ROOT" "$ROOT/public" "$ROOT/public/assets" "$ROOT/.env"
chmod 0755 "$ROOT" "$ROOT/public" "$ROOT/public/assets"
chmod 0640 "$ROOT/.env"
for path in "$ROOT/public/pages" "$ROOT/public/controllers" "$ROOT/public/assets/css/pages" "$ROOT/public/lang/custom"; do
  install -d -o iqs -g www-data -m 2775 "$path"
done
for path in "$ROOT/public/log" "$ROOT/public/cache" "$ROOT/public/uploads" "$ROOT/storage/manuals"; do
  install -d -o www-data -g www-data -m 2770 "$path"
done

install -o root -g root -m 0644 "$POOL_SOURCE" "$POOL_TARGET"
php-fpm8.4 -t
systemctl reload php8.4-fpm
systemctl is-active --quiet php8.4-fpm
for attempt in $(seq 1 10); do [[ -S "$SOCKET" ]] && break; sleep 1; done
[[ -S "$SOCKET" ]] || { echo "ERROR: Socket tidak tersedia: $SOCKET" >&2; false; }

install -o root -g root -m 0644 "$NGINX_SOURCE" "$NGINX_TARGET"
ln -sfn "$NGINX_TARGET" "$NGINX_LINK"
nginx -t
systemctl reload nginx

http_code="$(curl --silent --show-error --insecure --output /dev/null --write-out '%{http_code}' --resolve "$HOST:443:127.0.0.1" "https://$HOST/")"
case "$http_code" in 200|302|303) ;; *) echo "ERROR: HTTP tidak sihat: $http_code" >&2; false ;; esac

trap - ERR
echo "SUCCESS: Sistem e-Zakat UAT tersedia melalui PHP 8.4.26."
echo "URL: https://$HOST"
echo "HTTP: $http_code"
echo "BACKUP DIR: $BACKUP_DIR"

#!/usr/bin/env bash
# =============================================================================
#  GS Sportif Faaliyetler (e-spor koçluk) - VPS kurulum betiği (Ubuntu 22.04 / 24.04, Debian 12)
#
#  Kullanım (sunucuya root olarak bağlanıp):
#    curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/dreamy-newton-rci15t/espor/deploy/vps-kurulum.sh -o kurulum.sh
#    bash kurulum.sh              # ilk kurulum
#    bash kurulum.sh ssl          # alan adı sunucuya yönlendikten sonra SSL kur ve https'e geç
#    bash kurulum.sh guncelle     # GitHub'daki son sürümü yükle (ayarlar ve veritabanı korunur)
#
#  Kurar: Apache, PHP, MariaDB, Let's Encrypt SSL, UFW güvenlik duvarı, fail2ban,
#         otomatik güvenlik güncellemeleri, gece veritabanı yedeği.
# =============================================================================
set -euo pipefail

DOMAIN="${DOMAIN:-gssportiffaaliyetler.com.tr}"
WWW_DOMAIN="www.${DOMAIN}"
REPO_TARBALL="${REPO_TARBALL:-https://codeload.github.com/tunahan33/Tuna/tar.gz/refs/heads/claude/dreamy-newton-rci15t}"
APP_DIR="${APP_DIR:-/var/www/gssportif}"
DB_NAME="gssportif"
DB_USER="gssportif"
CRED_FILE="/root/gssportif-bilgiler.txt"
BACKUP_DIR="/var/backups/gssportif"
TEST_MODE="${TEST_MODE:-0}"   # 1: konteyner testleri için güvenlik duvarı/SSL/servis adımlarını atlar

yesil() { printf '\033[1;32m%s\033[0m\n' "$*"; }
sari()  { printf '\033[1;33m%s\033[0m\n' "$*"; }
kirmizi() { printf '\033[1;31m%s\033[0m\n' "$*"; }
adim()  { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

svc() { # svc <restart|reload|enable> <servis>
    if [ "$TEST_MODE" = 1 ]; then
        case "$2" in apache2) apache2ctl -k "${1/enable/start}" >/dev/null 2>&1 || apache2ctl start >/dev/null 2>&1 || true ;; esac
        return 0
    fi
    systemctl "$1" "$2"
}

[ "$(id -u)" -eq 0 ] || { kirmizi "Bu betik root olarak çalıştırılmalı (sudo -i)."; exit 1; }
[ -f /etc/os-release ] && . /etc/os-release
case "${ID:-}" in ubuntu|debian) ;; *) kirmizi "Desteklenen sistemler: Ubuntu 22.04/24.04, Debian 12"; exit 1 ;; esac

public_ip() { curl -fs4 --max-time 8 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}'; }
dns_ip() { getent ahostsv4 "$1" 2>/dev/null | awk 'NR==1{print $1}'; }

indir_kod() { # $1 hedef klasör
    local tmp; tmp="$(mktemp -d)"
    if [ -n "${SOURCE_TARBALL:-}" ]; then cp "$SOURCE_TARBALL" "$tmp/src.tgz"; else curl -fsSL "$REPO_TARBALL" -o "$tmp/src.tgz"; fi
    mkdir -p "$tmp/src" && tar -xzf "$tmp/src.tgz" -C "$tmp/src" --strip-components=1
    rsync -a --delete \
        --exclude config.php --exclude 'storage/*.sqlite' --exclude '.git*' --exclude '.github' --exclude '.vscode' --exclude '/deploy' --exclude '/README.md' \
        --exclude 'install/install.lock' "$tmp/src/espor/" "$1/"
    rm -rf "$tmp"
}

izinler() {
    chown -R root:www-data "$APP_DIR"
    find "$APP_DIR" -type d -exec chmod 755 {} \;
    find "$APP_DIR" -type f -exec chmod 644 {} \;
    if [ -f "$APP_DIR/config.php" ]; then chown www-data:www-data "$APP_DIR/config.php"; chmod 640 "$APP_DIR/config.php"; fi
    chown -R www-data:www-data "$APP_DIR/storage"
}

php_ayar() { # $1 anahtar, $2 değer - veritabanındaki settings tablosuna yazar
    mysql "$DB_NAME" -e "REPLACE INTO settings (skey, svalue) VALUES ('$1', '$2');"
}

base_url_ayarla() {
    php -r '$f=$argv[1]; $c=require $f; $c["base_url"]=$argv[2]; file_put_contents($f, "<?php\n// GS Sportif Faaliyetler yapılandırması\nreturn ".var_export($c,true).";\n");' "$APP_DIR/config.php" "$1"
    chown www-data:www-data "$APP_DIR/config.php"; chmod 640 "$APP_DIR/config.php"
}

ssl_kur() {
    adim "SSL sertifikası (Let's Encrypt)"
    local ip; ip="$(public_ip)"
    local d1 d2; d1="$(dns_ip "$DOMAIN")"; d2="$(dns_ip "$WWW_DOMAIN")"
    if [ "$d1" != "$ip" ] || [ "$d2" != "$ip" ]; then
        sari "Alan adı henüz bu sunucuyu göstermiyor:"
        sari "  $DOMAIN -> ${d1:-yok}   $WWW_DOMAIN -> ${d2:-yok}   (sunucu: $ip)"
        sari "Natro panelinden A kayıtlarını $ip yapın, DNS yayıldıktan sonra: bash $0 ssl"
        return 1
    fi
    local mail; mail="$(mysql -N "$DB_NAME" -e "SELECT svalue FROM settings WHERE skey='notify_email'" 2>/dev/null || true)"
    certbot --apache --non-interactive --agree-tos --redirect -m "${mail:-admin@$DOMAIN}" -d "$WWW_DOMAIN" -d "$DOMAIN"
    base_url_ayarla "https://$WWW_DOMAIN"
    php_ayar force_https 1
    yesil "SSL kuruldu. Site adresi: https://$WWW_DOMAIN"
}

# ----------------------------------------------------------------------------- guncelle
if [ "${1:-}" = "guncelle" ]; then
    [ -f "$APP_DIR/config.php" ] || { kirmizi "Kurulu site bulunamadı ($APP_DIR)."; exit 1; }
    adim "Güncelleme öncesi yedek"
    mkdir -p "$BACKUP_DIR" && mysqldump --single-transaction "$DB_NAME" | gzip > "$BACKUP_DIR/guncelleme-oncesi-$(date +%F-%H%M).sql.gz"
    adim "Son sürüm indiriliyor"
    indir_kod "$APP_DIR"
    rm -rf "$APP_DIR/install"
    izinler
    yesil "Güncelleme tamamlandı."
    exit 0
fi

# ----------------------------------------------------------------------------- ssl
if [ "${1:-}" = "ssl" ]; then
    ssl_kur
    exit $?
fi

# ----------------------------------------------------------------------------- ilk kurulum
if [ -f "$APP_DIR/config.php" ]; then
    kirmizi "Site zaten kurulu. Güncellemek için: bash $0 guncelle"
    exit 1
fi

adim "Süper admin hesabı"
if [ -z "${ADMIN_EMAIL:-}" ]; then
    read -rp "Ad Soyad: " ADMIN_NAME
    read -rp "E-posta: " ADMIN_EMAIL
    while true; do
        read -rsp "Şifre (en az 8 karakter): " ADMIN_PASS; echo
        read -rsp "Şifre tekrar: " ADMIN_PASS2; echo
        [ "$ADMIN_PASS" = "$ADMIN_PASS2" ] && [ ${#ADMIN_PASS} -ge 8 ] && break
        kirmizi "Şifreler eşleşmiyor veya 8 karakterden kısa."
    done
fi

adim "Sistem güncelleniyor ve paketler kuruluyor (birkaç dakika sürebilir)"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
[ "$TEST_MODE" = 1 ] || apt-get -y -q upgrade
apt-get install -y -q apache2 mariadb-server rsync curl unzip ca-certificates \
    php libapache2-mod-php php-mysql php-mbstring php-xml php-curl php-gd php-intl php-zip php-cli \
    certbot python3-certbot-apache ufw fail2ban unattended-upgrades
[ "$TEST_MODE" = 1 ] || timedatectl set-timezone Europe/Istanbul || true

if [ "$TEST_MODE" != 1 ] && ! swapon --show | grep -q . && [ "$(awk '/MemTotal/{print $2}' /proc/meminfo)" -lt 2500000 ]; then
    adim "Takas alanı (2 GB swap) oluşturuluyor"
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
    grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

adim "Veritabanı"
svc enable mariadb || true; svc restart mariadb || true
DB_PASS="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 28)"
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
          CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
          GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

adim "Site dosyaları indiriliyor"
mkdir -p "$APP_DIR"
indir_kod "$APP_DIR"

adim "Site kuruluyor"
IP="$(public_ip)"
if [ "$(dns_ip "$WWW_DOMAIN")" = "$IP" ]; then START_URL="http://$WWW_DOMAIN"; else START_URL="http://$IP"; fi
cd "$APP_DIR"
DB_PASS="$DB_PASS" START_URL="$START_URL" ADMIN_NAME="$ADMIN_NAME" ADMIN_EMAIL="$ADMIN_EMAIL" ADMIN_PASS="$ADMIN_PASS" DB_NAME="$DB_NAME" DB_USER="$DB_USER" \
php -r '
require "install/installer.php";
$config = ["db" => ["driver" => "mysql", "host" => "localhost", "port" => 3306, "name" => getenv("DB_NAME"), "user" => getenv("DB_USER"), "pass" => getenv("DB_PASS"), "sqlite" => ""],
    "base_url" => getenv("START_URL"), "app_key" => bin2hex(random_bytes(32)), "timezone" => "Europe/Istanbul", "debug" => false];
install_run($config, ["name" => getenv("ADMIN_NAME"), "email" => getenv("ADMIN_EMAIL"), "password" => getenv("ADMIN_PASS")]);
install_write_config($config);
'
rm -rf "$APP_DIR/install"
izinler

adim "Apache yapılandırması"
cat > /etc/apache2/sites-available/gssportif.conf <<VHOST
<VirtualHost *:80>
    ServerName $WWW_DOMAIN
    ServerAlias $DOMAIN $IP
    DocumentRoot $APP_DIR
    <Directory $APP_DIR>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/gssportif-error.log
    CustomLog \${APACHE_LOG_DIR}/gssportif-access.log combined
</VirtualHost>
VHOST
cat > /etc/apache2/conf-available/zz-gssportif-guvenlik.conf <<'CONF'
ServerTokens Prod
ServerSignature Off
TraceEnable Off
CONF
PHP_INI_DIR="$(php -r 'echo "/etc/php/".PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
cat > "$PHP_INI_DIR/apache2/conf.d/99-gssportif.ini" <<'INI'
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 16M
post_max_size = 20M
memory_limit = 256M
max_execution_time = 120
date.timezone = Europe/Istanbul
session.cookie_httponly = 1
session.use_strict_mode = 1
INI
a2enmod -q rewrite headers expires >/dev/null
a2enconf -q zz-gssportif-guvenlik >/dev/null
a2dissite -q 000-default >/dev/null 2>&1 || true
a2ensite -q gssportif >/dev/null
apache2ctl configtest
svc enable apache2 || true; svc restart apache2

adim "Otomatik yedek (her gece 03:30, 14 gün saklanır)"
mkdir -p "$BACKUP_DIR" && chmod 700 "$BACKUP_DIR"
cat > /etc/cron.d/gssportif-yedek <<CRON
30 3 * * * root mysqldump --single-transaction $DB_NAME | gzip > $BACKUP_DIR/gssportif-\$(date +\%F).sql.gz && find $BACKUP_DIR -name '*.sql.gz' -mtime +14 -delete
CRON

if [ "$TEST_MODE" != 1 ]; then
    adim "Güvenlik duvarı ve saldırı koruması"
    ufw allow OpenSSH >/dev/null && ufw allow 'Apache Full' >/dev/null && ufw --force enable >/dev/null
    systemctl enable --now fail2ban >/dev/null 2>&1 || true
    dpkg-reconfigure -f noninteractive unattended-upgrades >/dev/null 2>&1 || true
fi

cat > "$CRED_FILE" <<CRED
GS Sportif Faaliyetler kurulum bilgileri ($(date '+%d.%m.%Y %H:%M'))
Site klasörü   : $APP_DIR
Veritabanı     : $DB_NAME
DB kullanıcı   : $DB_USER
DB şifre       : $DB_PASS
Yedek klasörü  : $BACKUP_DIR
Süper admin    : $ADMIN_EMAIL
CRED
chmod 600 "$CRED_FILE"

SSL_OK=0
if [ "$TEST_MODE" != 1 ] && [ "$START_URL" = "http://$WWW_DOMAIN" ]; then ssl_kur && SSL_OK=1 || true; fi

echo
yesil "================= KURULUM TAMAMLANDI ================="
if [ "$SSL_OK" = 1 ]; then
    yesil "Site  : https://$WWW_DOMAIN"
    yesil "Panel : https://$WWW_DOMAIN/admin"
else
    yesil "Site  : $START_URL"
    yesil "Panel : $START_URL/admin"
    sari  "Alan adını bağlamak için Natro > Alan Adı > DNS Yönetimi'nde:"
    sari  "   A   @     -> $IP"
    sari  "   A   www   -> $IP"
    sari  "DNS yayıldıktan sonra (genelde 15 dk - birkaç saat): bash $0 ssl"
fi
yesil "Veritabanı bilgileri: $CRED_FILE"

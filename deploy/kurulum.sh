#!/usr/bin/env bash
# =============================================================================
#  GS Sportif Ürünler — sunucu kurulum betiği (Ubuntu 22.04 / 24.04)
#
#  Sunucuya root olarak bağlanıp:
#    curl -fsSL https://raw.githubusercontent.com/tunahan33/Tuna/claude/confident-ride-wpz20g/deploy/kurulum.sh -o kurulum.sh
#    bash kurulum.sh                       # ilk kurulum (süper admin bilgileri sorulur, DNS hazırsa SSL de kurulur)
#    bash kurulum.sh guncelle              # GitHub'daki son sürümü yükler (veriler ve fotoğraflar korunur)
#    bash kurulum.sh ssl                   # alan adı sunucuya yönlendikten sonra ücretsiz SSL (https)
#    bash kurulum.sh yedek                 # elle yedek al
#
#  Kurar: Apache + PHP 8, güvenlik duvarı (UFW), fail2ban, otomatik güvenlik güncellemeleri,
#         her gece veritabanı ve fotoğraf yedeği (/var/backups/gssportif, son 14 gün).
# =============================================================================
set -euo pipefail

DOMAIN="${DOMAIN:-gssportifurunler.net}"
BRANCH="${BRANCH:-claude/confident-ride-wpz20g}"
TARBALL="${TARBALL:-https://codeload.github.com/tunahan33/Tuna/tar.gz/refs/heads/${BRANCH}}"
APP_DIR="${APP_DIR:-/var/www/gssportif}"
BACKUP_DIR="/var/backups/gssportif"
TEST_MODE="${TEST_MODE:-0}"   # 1: konteyner testi — servis, güvenlik duvarı ve SSL adımlarını atlar

yesil()   { printf '\033[1;32m%s\033[0m\n' "$*"; }
sari()    { printf '\033[1;33m%s\033[0m\n' "$*"; }
kirmizi() { printf '\033[1;31m%s\033[0m\n' "$*"; }
adim()    { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }

[ "$(id -u)" -eq 0 ] || { kirmizi "Bu betik root olarak çalıştırılmalı."; exit 1; }
export DEBIAN_FRONTEND=noninteractive

servis() { # servis <restart|reload|enable> <ad>
    if [ "$TEST_MODE" = 1 ]; then
        [ "$2" = apache2 ] && { apache2ctl -k restart >/dev/null 2>&1 || apache2ctl start >/dev/null 2>&1 || true; }
        return 0
    fi
    systemctl "$1" "$2"
}

sunucu_ip() { curl -fs4 --max-time 8 https://api.ipify.org 2>/dev/null || hostname -I | awk '{print $1}'; }
dns_ip() { getent ahostsv4 "$1" 2>/dev/null | awk 'NR==1{print $1}'; }
dns_hazir() { local ip; ip="$(sunucu_ip)"; [ "$(dns_ip "$DOMAIN")" = "$ip" ] && [ "$(dns_ip "www.$DOMAIN")" = "$ip" ]; }

kodu_indir() {
    adim "Site dosyaları GitHub'dan indiriliyor"
    local tmp; tmp="$(mktemp -d)"
    if [ -n "${SOURCE_TARBALL:-}" ]; then cp "$SOURCE_TARBALL" "$tmp/src.tgz"; else curl -fsSL "$TARBALL" -o "$tmp/src.tgz"; fi
    mkdir -p "$tmp/src" && tar -xzf "$tmp/src.tgz" -C "$tmp/src" --strip-components=1
    mkdir -p "$APP_DIR"
    # Veritabanı (data/*.sqlite) ve panelden yüklenen fotoğraflar (uploads/) hiçbir zaman ezilmez
    rsync -a --delete \
        --exclude '.git*' --exclude '/deploy' --exclude '/.vscode' --exclude 'baslat.*' \
        --exclude '/data/*.sqlite*' \
        --include '/uploads/.htaccess' --exclude '/uploads/*' \
        "$tmp/src/" "$APP_DIR/"
    rm -rf "$tmp"
}

izinler() {
    chown -R root:www-data "$APP_DIR"
    find "$APP_DIR" -type d -exec chmod 750 {} +
    find "$APP_DIR" -type f -exec chmod 640 {} +
    mkdir -p "$APP_DIR/data" "$APP_DIR/uploads"
    chown -R www-data:www-data "$APP_DIR/data" "$APP_DIR/uploads"
    chmod 770 "$APP_DIR/data" "$APP_DIR/uploads"
}

paketler() {
    adim "Sistem güncelleniyor ve paketler kuruluyor (birkaç dakika sürebilir)"
    apt-get update -y
    [ "$TEST_MODE" = 1 ] || apt-get upgrade -y
    apt-get install -y apache2 libapache2-mod-php php-cli php-sqlite3 php-gd php-mbstring \
        sqlite3 rsync curl ca-certificates ufw fail2ban unattended-upgrades
    a2enmod rewrite headers >/dev/null
}

apache_ayar() {
    adim "Apache ayarlanıyor ($DOMAIN)"
    cat > /etc/apache2/sites-available/gssportif.conf <<CONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${APP_DIR}
    <Directory ${APP_DIR}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    <DirectoryMatch "^${APP_DIR}/(app|data)">
        Require all denied
    </DirectoryMatch>
    <Directory ${APP_DIR}/uploads>
        php_admin_flag engine off
    </Directory>
    php_value upload_max_filesize 64M
    php_value post_max_size 512M
    php_value max_file_uploads 200
    php_value memory_limit 512M
    ServerSignature Off
    ErrorLog \${APACHE_LOG_DIR}/gssportif-error.log
    CustomLog \${APACHE_LOG_DIR}/gssportif-access.log combined
</VirtualHost>
CONF
    grep -q '^ServerTokens Prod' /etc/apache2/conf-available/security.conf 2>/dev/null || \
        sed -i 's/^ServerTokens .*/ServerTokens Prod/' /etc/apache2/conf-available/security.conf 2>/dev/null || true
    a2dissite 000-default >/dev/null 2>&1 || true
    a2ensite gssportif >/dev/null
    apache2ctl configtest
    servis reload apache2 || servis restart apache2
}

guvenlik() {
    [ "$TEST_MODE" = 1 ] && return 0
    adim "Güvenlik duvarı, fail2ban ve otomatik güncellemeler"
    ufw allow OpenSSH >/dev/null
    ufw allow 'Apache Full' >/dev/null
    ufw --force enable >/dev/null
    systemctl enable --now fail2ban >/dev/null
    dpkg-reconfigure -f noninteractive unattended-upgrades >/dev/null 2>&1 || true
}

yedek_kur() {
    adim "Gece yedeği ayarlanıyor"
    mkdir -p "$BACKUP_DIR"
    chmod 700 "$BACKUP_DIR"
    cat > /usr/local/bin/gssportif-yedek <<YEDEK
#!/bin/sh
# GS Sportif Ürünler gece yedeği: veritabanı + yüklenen fotoğraflar, son 14 gün saklanır
set -e
T=\$(date +%Y%m%d-%H%M)
mkdir -p ${BACKUP_DIR}
sqlite3 ${APP_DIR}/data/gs.sqlite ".backup '${BACKUP_DIR}/gs-\$T.sqlite'"
gzip -f ${BACKUP_DIR}/gs-\$T.sqlite
tar -czf ${BACKUP_DIR}/uploads-\$T.tar.gz -C ${APP_DIR} uploads
find ${BACKUP_DIR} -type f -mtime +14 -delete
YEDEK
    chmod 750 /usr/local/bin/gssportif-yedek
    echo "30 3 * * * root /usr/local/bin/gssportif-yedek" > /etc/cron.d/gssportif-yedek
}

admin_olustur() {
    adim "Süper admin hesabı"
    local eposta ad sifre sifre2
    read -rp "Süper admin e-posta adresi: " eposta
    read -rp "Ad Soyad: " ad
    while true; do
        read -rsp "Şifre (en az 10 karakter, harf ve rakam): " sifre; echo
        read -rsp "Şifre (tekrar): " sifre2; echo
        [ "$sifre" = "$sifre2" ] && [ "${#sifre}" -ge 10 ] && break
        kirmizi "Şifreler eşleşmiyor veya 10 karakterden kısa, tekrar deneyin."
    done
    runuser -u www-data -- php "$APP_DIR/app/canli.php" --eposta="$eposta" --sifre="$sifre" --ad="$ad" --adres="https://www.$DOMAIN"
}

kurulum() {
    paketler
    kodu_indir
    izinler
    apache_ayar
    # Veritabanını www-data kullanıcısıyla oluştur (site ilk açılışta da oluşturabilir)
    runuser -u www-data -- php -r 'require "'"$APP_DIR"'/app/bootstrap.php"; db();'
    if [ "${SUPERADMIN_EMAIL:-}" != "" ]; then
        runuser -u www-data -- php "$APP_DIR/app/canli.php" --eposta="$SUPERADMIN_EMAIL" --sifre="$SUPERADMIN_PASS" --ad="${SUPERADMIN_NAME:-Süper Admin}" --adres="https://www.$DOMAIN"
    else
        admin_olustur
    fi
    guvenlik
    yedek_kur
    local ip; ip="$(sunucu_ip)"
    local adres="http://${ip}"
    if [ "$TEST_MODE" != 1 ] && dns_hazir; then
        ssl_kur && adres="https://www.${DOMAIN}"
    else
        sari "Alan adı ($DOMAIN) henüz bu sunucuyu göstermiyor; SSL daha sonra kurulacak."
    fi
    yesil "
============================================================
 Kurulum tamamlandı!

 Mağaza:         ${adres}
 Yönetim paneli: ${adres}/admin

 DNS hazır değilse: Natro > DNS Yönetimi'nde @ ve www için A kaydı = ${ip}
 yayıldıktan sonra: bash kurulum.sh ssl
 Güncelleme:        bash kurulum.sh guncelle
============================================================"
    sari "ÖNEMLİ: root şifrenizi değiştirin:  passwd"
}

guncelle() {
    [ -d "$APP_DIR" ] || { kirmizi "Önce kurulum yapın: bash kurulum.sh"; exit 1; }
    [ -f "$APP_DIR/data/gs.sqlite" ] && /usr/local/bin/gssportif-yedek 2>/dev/null && yesil "Güncelleme öncesi yedek alındı."
    kodu_indir
    izinler
    # Veritabanı güncellemeleri (app/migrate.php) siteyi ilk açışta kendiliğinden çalışır; hemen tetikle
    runuser -u www-data -- php -r 'require "'"$APP_DIR"'/app/bootstrap.php"; db();'
    servis reload apache2
    yesil "Güncelleme tamamlandı. Ayarlar, ürünler, siparişler ve fotoğraflar korundu."
}

ssl_kur() {
    [ -n "${1:-}" ] && DOMAIN="$1"
    adim "SSL sertifikası kuruluyor: $DOMAIN ve www.$DOMAIN"
    local ip; ip="$(sunucu_ip)"
    if ! dns_hazir; then
        kirmizi "Alan adı henüz bu sunucuyu göstermiyor."
        echo "  $DOMAIN      -> $(dns_ip "$DOMAIN" || true)   (olması gereken: $ip)"
        echo "  www.$DOMAIN  -> $(dns_ip "www.$DOMAIN" || true)   (olması gereken: $ip)"
        echo "Natro > Alan Adı Yönetimi > DNS Yönetimi'nden A kayıtlarını düzeltin, 15-60 dk bekleyip tekrar deneyin."
        return 1
    fi
    grep -q "ServerName ${DOMAIN}" /etc/apache2/sites-available/gssportif.conf || apache_ayar
    apt-get install -y certbot python3-certbot-apache
    certbot --apache --non-interactive --agree-tos --redirect --register-unsafely-without-email -d "$DOMAIN" -d "www.$DOMAIN"
    runuser -u www-data -- php -r 'require "'"$APP_DIR"'/app/bootstrap.php"; save_setting("site_url", "https://www.'"$DOMAIN"'");'
    yesil "SSL kuruldu: https://www.$DOMAIN (sertifika otomatik yenilenir)"
}

case "${1:-kur}" in
    kur|kurulum) kurulum ;;
    guncelle)    guncelle ;;
    ssl)         ssl_kur "${2:-}" ;;
    yedek)       /usr/local/bin/gssportif-yedek && yesil "Yedek alındı: $BACKUP_DIR" ;;
    *) echo "Kullanım: bash kurulum.sh [guncelle | ssl | yedek]" ;;
esac

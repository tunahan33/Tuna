#!/usr/bin/env bash
# GS Projeler — Ubuntu 22.04 / 24.04 sunucu kurulumu
#
# Kullanım (root olarak):
#   bash kurulum.sh                 -> siteyi sunucu IP adresi üzerinden yayınlar
#   bash kurulum.sh alanadi.com     -> alan adıyla yayınlar ve ücretsiz SSL (Let's Encrypt) kurar
#
# gsprojeler-site.zip dosyası bu betikle aynı klasörde olmalıdır.
set -euo pipefail

DOMAIN="${1:-}"
EMAIL="${2:-}"
SITE_DIR=/var/www/gsprojeler
HERE="$(cd "$(dirname "$0")" && pwd)"
ZIP="$HERE/gsprojeler-site.zip"

if [ "$(id -u)" -ne 0 ]; then echo "Lütfen root olarak çalıştırın: sudo bash kurulum.sh"; exit 1; fi
if [ ! -f "$ZIP" ]; then echo "HATA: $ZIP bulunamadı. Zip dosyasını bu betikle aynı klasöre yükleyin."; exit 1; fi

echo "==> [1/6] Sistem güncelleniyor ve paketler kuruluyor (birkaç dakika sürebilir)"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y apache2 libapache2-mod-php php-sqlite3 php-mbstring php-xml php-curl php-intl unzip ufw

echo "==> [2/6] Site dosyaları yükleniyor"
if [ -f "$SITE_DIR/data/gsprojeler.sqlite" ]; then
  cp -a "$SITE_DIR/data/gsprojeler.sqlite" "/root/gsprojeler-yedek-$(date +%Y%m%d-%H%M%S).sqlite"
  echo "    Mevcut veritabanı /root altına yedeklendi."
fi
mkdir -p "$SITE_DIR"
unzip -oq "$ZIP" -d "$SITE_DIR"
chown -R www-data:www-data "$SITE_DIR"
find "$SITE_DIR" -type d -exec chmod 755 {} \;
find "$SITE_DIR" -type f -exec chmod 644 {} \;
chmod 775 "$SITE_DIR/data"

echo "==> [3/6] Apache yapılandırılıyor"
SERVER_NAME_LINE=""
if [ -n "$DOMAIN" ]; then SERVER_NAME_LINE="ServerName $DOMAIN
    ServerAlias www.$DOMAIN"; fi
cat > /etc/apache2/sites-available/gsprojeler.conf <<CONF
<VirtualHost *:80>
    $SERVER_NAME_LINE
    DocumentRoot $SITE_DIR
    <Directory $SITE_DIR>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/gsprojeler-error.log
    CustomLog \${APACHE_LOG_DIR}/gsprojeler-access.log combined
</VirtualHost>
CONF
a2enmod -q rewrite headers
a2dissite -q 000-default >/dev/null 2>&1 || true
a2ensite -q gsprojeler

echo "==> [4/6] PHP ayarları"
PHPINI=$(php -r 'echo php_ini_loaded_file();' | sed 's#/cli/#/apache2/#')
if [ -f "$PHPINI" ]; then
  sed -i 's/^;\?date.timezone.*/date.timezone = Europe\/Istanbul/' "$PHPINI"
  sed -i 's/^expose_php.*/expose_php = Off/' "$PHPINI"
fi
systemctl restart apache2

echo "==> [5/6] Güvenlik duvarı (SSH, HTTP, HTTPS açık)"
ufw allow OpenSSH >/dev/null
ufw allow 'Apache Full' >/dev/null
ufw --force enable >/dev/null

echo "==> [6/6] SSL"
if [ -n "$DOMAIN" ]; then
  apt-get install -y certbot python3-certbot-apache
  if [ -n "$EMAIL" ]; then EMAIL_ARG="-m $EMAIL"; else EMAIL_ARG="--register-unsafely-without-email"; fi
  if certbot --apache -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos $EMAIL_ARG --redirect; then
    echo "    SSL kuruldu."
  else
    echo "    UYARI: SSL kurulamadı. Alan adının DNS kaydı (A kaydı) bu sunucunun IP adresini göstermiyor olabilir."
    echo "    DNS ayarı yapıldıktan sonra tekrar çalıştırın: certbot --apache -d $DOMAIN -d www.$DOMAIN"
  fi
else
  echo "    Alan adı verilmedi, SSL atlandı. Alan adınız hazır olunca: bash kurulum.sh alanadi.com"
fi

IP=$(hostname -I | awk '{print $1}')
echo
echo "================================================================"
echo " KURULUM TAMAMLANDI"
if [ -n "$DOMAIN" ]; then echo " Site adresi : https://$DOMAIN"; else echo " Site adresi : http://$IP"; fi
echo " İlk açılışta 'Kurulum' ekranında Süper Admin hesabınızı oluşturun."
echo "================================================================"

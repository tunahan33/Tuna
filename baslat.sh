#!/bin/sh
# Mac / Linux: ./baslat.sh
cd "$(dirname "$0")"
echo "GS Sportif Ürünler: http://localhost:8000  ·  Panel: http://localhost:8000/admin"
php -d upload_max_filesize=64M -d post_max_size=512M -d max_file_uploads=200 -d memory_limit=512M -S localhost:8000 router.php

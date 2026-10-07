#!/bin/sh
# Mac / Linux: ./baslat.sh
cd "$(dirname "$0")"
echo "GS Sportif Ürünler: http://localhost:8000  ·  Panel: http://localhost:8000/admin"
php -S localhost:8000 router.php

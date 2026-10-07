<?php
require dirname(__DIR__) . '/app/bootstrap.php';
logout_user();
redirect('admin/giris.php');

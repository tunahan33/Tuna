<?php
require __DIR__ . '/app/bootstrap.php';
logout_user();
flash('success', 'Çıkış yaptınız.');
redirect('');

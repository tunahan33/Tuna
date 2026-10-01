<?php
require __DIR__ . '/includes/bootstrap.php';
logout_user();
flash('success', 'Güvenli şekilde çıkış yaptınız.');
redirect('');

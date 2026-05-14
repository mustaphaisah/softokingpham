<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('/pages/dashboard.php');
}
redirect('/pages/login.php');

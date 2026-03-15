<?php
session_start();
unset($_SESSION['site_user_id'], $_SESSION['site_user_name'], $_SESSION['site_username']);
session_regenerate_id(true);
header('Location: /BarangaySystem/public/user/auth/login.php?msg=' . urlencode('Logged out successfully.'));
exit;

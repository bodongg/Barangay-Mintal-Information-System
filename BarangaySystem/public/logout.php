<?php
session_start();
session_unset();
session_destroy();
session_regenerate_id(true);
header('Location: /BarangaySystem/public/adminlogin.php?msg=' . urlencode('Logged out successfully.'));
exit;

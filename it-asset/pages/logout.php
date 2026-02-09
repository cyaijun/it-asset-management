<?php
// pages/logout.php - 登出
logout();
flash_set('已安全退出');
header('Location: ?p=login');
exit;

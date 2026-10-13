<?php

// نوع دیتابیس را از sqlite به mysql تغییر می‌دهیم
define('DB_DRIVER', 'mysql');

// آدرس سرور پایگاه داده در ومپ
define('DB_HOSTNAME', 'localhost');

// نام کاربری دیتابیس در Wamp (پیش‌فرض root است)
define('DB_USERNAME', 'root');

// رمز عبور دیتابیس در Wamp (پیش‌فرض خالی است، مگر اینکه خودتان پسورد گذاشته باشید)
define('DB_PASSWORD', '');

// نام دیتابیسی که می‌خواهید بسازید
define('DB_NAME', 'kanboard');

// پورت پیش‌فرض MySQL
define('DB_PORT', '3306');

// خاموش کردن حالت دیباگ
define('DEBUG', false);

// مخفی کردن هشدارهای منسوخ شدن PHP در خروجی صفحه
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

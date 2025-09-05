<?php
// api/config.php

// Включить детальный вывод в dev-режиме
define('APP_DEBUG', true);

// Параметры БД
define('DB_HOST', 'localhost');
define('DB_NAME', 'rovin231_korabely');    // как в SQL выше
define('DB_USER', 'rovin231_korabely');           // подставь свои
define('DB_PASS', 'DCEVVWtLP8TYJ7Q95UuJ');               // подставь свои
define('DB_CHARSET', 'utf8mb4');

// Настройки куки сессии (можно ужесточить на проде)
define('SESSION_NAME', 'korabely_sid');
define('SESSION_LIFETIME', 60*60*24*7); // 7 дней

<?php

declare(strict_types=1);

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . 
            ';port=' . (getenv('DB_PORT') ?: '3306') . 
            ';dbname=' . (getenv('DB_NAME') ?: 'short_link_db'),
    'username' => getenv('DB_USER') ?: 'user',
    'password' => getenv('DB_PASSWORD') ?: 'password',
    'charset' => 'utf8mb4',
];

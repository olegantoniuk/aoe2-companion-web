<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . (getenv('MYSQL_HOST') ?: 'mysql') . ';dbname=' . (getenv('DB_NAME') ?: 'aoe2-companion'),
    'username' => getenv('MYSQL_USER') ?: 'user',
    'password' => getenv('MYSQL_PASSWORD') ?: '123456',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => !YII_DEBUG,
    'schemaCacheDuration' => 3600,
];

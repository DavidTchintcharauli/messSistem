<?php
declare(strict_types=1);

return [
    'driver'    => 'mysql',   
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'signalRDB',
    'username'  => 'root',    
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];

<?php
return [
  'db' => [
    'host' => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port' => getenv('MYSQL_PORT') ?: '3306',
    'name' => getenv('MYSQL_DB') ?: 'medisys',
    'user' => getenv('MYSQL_USER') ?: 'root',
    'pass' => getenv('MYSQL_PASSWORD') ?: '',
  ],
];

<?php
return [
  'db' => [
    'host' => 'localhost',
    'dbname' => 'database_name',
    'username' => 'username',
    'password' => 'password'
  ],
  'storage' => [
    'calendars'  => __DIR__ . '/../data/calendars.json',
    'app_secret' => __DIR__ . '/../data/client_secret_web.json',
    'app_credentials' => __DIR__ . '/../data/credentials.json'
  ],
  'hours' => [
    'min' => 7,
    'max' => 23
  ],
  'google' => [
    'app_name' => 'Google Calendar API PHP Quickstart'
  ]
];
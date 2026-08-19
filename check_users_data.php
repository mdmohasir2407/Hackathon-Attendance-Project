<?php
require 'config/database.php';
$users = $pdo->query('SELECT * FROM users')->fetchAll();
print_r($users);

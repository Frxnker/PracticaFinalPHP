<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = new Database();
var_dump($db->fetchAll('SELECT 1 AS prueba'));
<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\User;

$db = new Database();
var_dump($db->fetchAll('SELECT 1 AS prueba'));

$users = new User($db);

$id = $users->insert([
    'email'    => 'capitantrueno@iescelia.org',
    'username' => 'capitan',
    'password' => password_hash('la-contraseña-que-sea', PASSWORD_DEFAULT),
]);

$users->update($id, ['username' => 'trueno']);
var_dump($users->getById($id));
$users->delete($id);
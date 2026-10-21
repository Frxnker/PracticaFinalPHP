<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Controllers\AuthController;

session_start();

$db     = new Database();
$auth   = new AuthController($db);
$action = $_GET['action'] ?? 'home';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

switch ($action) {
    case 'register':
        $isPost ? $auth->register() : $auth->showRegisterForm();
        break;
    case 'login':
        $isPost ? $auth->login() : $auth->showLoginForm();
        break;
    case 'logout':
        $auth->logout();
        break;
    default:
        require __DIR__ . '/../src/Views/partials/header.php';
        require __DIR__ . '/../src/Views/home.php';
        require __DIR__ . '/../src/Views/partials/footer.php';
}
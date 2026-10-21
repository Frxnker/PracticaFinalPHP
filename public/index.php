<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\CommentController;

session_start();

$db     = new Database();
$auth   = new AuthController($db);
$posts  = new PostController($db);
$comments = new CommentController($db);

$action = $_GET['action'] ?? 'index';
$id     = (int) ($_GET['id'] ?? 0);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

switch ($action) {
    // Autenticación
    case 'register':
        $isPost ? $auth->register() : $auth->showRegisterForm();
        break;
    case 'login':
        $isPost ? $auth->login() : $auth->showLoginForm();
        break;
    case 'logout':
        $auth->logout();
        break;

    // Publicaciones
    case 'show':
        $posts->show($id);
        break;
    case 'create':
        $isPost ? $posts->store() : $posts->create();
        break;
    case 'edit':
        $isPost ? $posts->update($id) : $posts->edit($id);
        break;
    case 'destroy':
        $isPost ? $posts->destroy($id) : $posts->index();
        break;

        // Comentarios
    case 'comment':
        $isPost ? $comments->store($id) : $posts->show($id);
        break;
    default:
        $posts->index();
}
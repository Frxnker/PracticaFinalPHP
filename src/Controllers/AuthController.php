<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\User;
use App\Core\Controller;
use PDOException;

class AuthController extends Controller
{
    private User $users;

    public function __construct(Database $db)
    {
        $this->users = new User($db);
    }

    public function showRegisterForm(array $errors = []): void
    {
        $this->render('auth/register', ['errors' => $errors]);
    }

    public function register(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email no válido.';
        if ($username === '')                           $errors[] = 'El nombre de usuario es obligatorio.';
        if (strlen($password) < 6)                      $errors[] = 'La contraseña debe tener al menos 6 caracteres.';

        if ($errors) {
            $this->showRegisterForm($errors);
            return;
        }

        try {
            $this->users->insert([
                'email'    => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // clave duplicada (UNIQUE)
                $this->showRegisterForm(['Ese email o nombre de usuario ya está registrado.']);
                return;
            }
            throw $e;
        }

        $this->redirect('?action=login');
    }

    public function showLoginForm(array $errors = []): void
    {
        $this->render('auth/login', ['errors' => $errors]);
    }

    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $id = $this->users->findByEmail($email, $password);

        if ($id === null) {
            $this->showLoginForm(['Email o contraseña incorrectos.']);
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true); // evita robo de sesión

        $_SESSION['user_id'] = $id;
        $_SESSION['email']   = $email;

        $this->redirect('?action=index');
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();

        $this->redirect('?action=index');
    }
}
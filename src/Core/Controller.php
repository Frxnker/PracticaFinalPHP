<?php

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../Views/partials/header.php';
        require __DIR__ . "/../Views/$view.php";
        require __DIR__ . '/../Views/partials/footer.php';
    }

    protected function redirect(string $url): never
    {
        header("Location: $url");
        exit;
    }

    protected function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }

    protected function currentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function requireLogin(): void
    {
        if ($this->currentUserId() === null) {
            $this->flash('Tienes que iniciar sesión para publicar.');
            $this->redirect('?action=login');
        }
    }

    protected function forbidden(): never
    {
        http_response_code(403);
        $this->render('errors/403');
        exit;
    }

    protected function notFound(): never
    {
        http_response_code(404);
        $this->render('errors/404');
        exit;
    }
}
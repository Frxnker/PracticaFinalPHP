<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Comment;
use App\Models\Post;

class CommentController extends Controller
{
    private Comment $comments;
    private Post $posts;

    public function __construct(Database $db)
    {
        $this->comments = new Comment($db);
        $this->posts    = new Post($db);
    }

    public function store(int $postId): void
    {
        $this->requireLogin('Tienes que iniciar sesión para comentar.');

        // El post tiene que existir
        $this->posts->getById($postId) ?? $this->notFound();

        $body = trim($_POST['body'] ?? '');

        if ($body === '') {
            $this->flash('El comentario no puede estar vacío.');
            $this->redirect("?action=show&id=$postId#comentarios");
        }

        $this->comments->insert([
            'post_id' => $postId,                 // de la URL, comprobado arriba
            'user_id' => $this->currentUserId(),  // de la sesión, NUNCA del formulario
            'body'    => $body,
        ]);

        $this->flash('Comentario publicado.');
        $this->redirect("?action=show&id=$postId#comentarios");
    }
}
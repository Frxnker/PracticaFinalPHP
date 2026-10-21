<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Post;
use App\Models\Comment;

class PostController extends Controller
{
    private Post $posts;
    private Comment $comments;

    public function __construct(Database $db)
    {
        $this->posts = new Post($db);
        $this->comments = new Comment($db);
    }

    public function index(): void
    {
        $q = trim($_GET['q'] ?? '');
        $posts = $this->posts->search($q) ?? [];

        $this->render('posts/index', ['posts' => $posts, 'q' => $q]);
    }

   public function show(int $id): void
   {
       $post = $this->posts->findWithAuthor($id) ?? $this->notFound();
       $comments = $this->comments->findByPost($id) ?? [];

       $this->render('posts/show', ['post' => $post, 'comments' => $comments]);
   }

    public function create(): void
    {
        $this->requireLogin();

        $this->render('posts/create', [
            'post'   => ['title' => '', 'category' => '', 'body' => ''],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        [$data, $errors] = $this->validate();

        if ($errors) {
            $this->render('posts/create', ['post' => $data, 'errors' => $errors]);
            return;
        }

        $data['user_id'] = $this->currentUserId();
        $id = $this->posts->insert($data);

        $this->flash('Publicación creada.');
        $this->redirect("?action=show&id=$id");
    }

    public function edit(int $id): void
    {
        $post = $this->findOwnedOrFail($id);

        $this->render('posts/edit', ['post' => $post, 'errors' => []]);
    }

    public function update(int $id): void
    {
        $post = $this->findOwnedOrFail($id);

        [$data, $errors] = $this->validate();

        if ($errors) {
            $this->render('posts/edit', ['post' => $data + $post, 'errors' => $errors]);
            return;
        }

        $this->posts->update($id, $data);

        $this->flash('Publicación actualizada.');
        $this->redirect("?action=show&id=$id");
    }

    public function destroy(int $id): void
    {
        $this->findOwnedOrFail($id);

        $this->posts->delete($id);

        $this->flash('Publicación eliminada.');
        $this->redirect('?action=index');
    }

    /** Autorización: el post existe y es del usuario logueado. */
    private function findOwnedOrFail(int $id): array
    {
        $this->requireLogin();

        $post = $this->posts->getById($id) ?? $this->notFound();

        if ((int) $post['user_id'] !== $this->currentUserId()) {
            $this->forbidden();
        }

        return $post;
    }

    private function validate(): array
    {
        $data = [
            'title'    => trim($_POST['title'] ?? ''),
            'category' => trim($_POST['category'] ?? ''),
            'body'     => trim($_POST['body'] ?? ''),
        ];

        $errors = [];
        if ($data['title'] === '')         $errors[] = 'El título es obligatorio.';
        if (mb_strlen($data['title']) > 150) $errors[] = 'El título no puede superar 150 caracteres.';
        if ($data['category'] === '')      $errors[] = 'La categoría es obligatoria.';
        if ($data['body'] === '')          $errors[] = 'El contenido es obligatorio.';

        return [$data, $errors];
    }
}
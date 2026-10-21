<article>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <p>
        <?= htmlspecialchars($post['category']) ?> ·
        por <?= htmlspecialchars($post['username']) ?> ·
        <?= htmlspecialchars($post['created_at']) ?>
    </p>
    <p><?= nl2br(htmlspecialchars($post['body'])) ?></p>
</article>

<?php if (($_SESSION['user_id'] ?? null) === (int) $post['user_id']): ?>
    <a href="?action=edit&id=<?= (int) $post['id'] ?>">Editar</a>

    <form method="post" action="?action=destroy&id=<?= (int) $post['id'] ?>"
          onsubmit="return confirm('¿Seguro que quieres borrar esta publicación?');"
          style="display:inline">
        <button type="submit">Borrar</button>
    </form>
<?php endif; ?>

<p><a href="?action=index">← Volver</a></p>
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

<section id="comentarios">
    <h2>Comentarios (<?= count($comments) ?>)</h2>

    <?php if (!$comments): ?>
        <p>Todavía no hay comentarios.</p>
    <?php endif; ?>

    <?php foreach ($comments as $comment): ?>
        <div>
            <p>
                <strong><?= htmlspecialchars($comment['username']) ?></strong> ·
                <?= htmlspecialchars($comment['created_at']) ?>
            </p>
            <p><?= nl2br(htmlspecialchars($comment['body'])) ?></p>
        </div>
    <?php endforeach; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="post" action="?action=comment&id=<?= (int) $post['id'] ?>">
            <label>Añadir comentario<br>
                <textarea name="body" rows="4" cols="60" required></textarea>
            </label><br>
            <button type="submit">Comentar</button>
        </form>
    <?php else: ?>
        <p><a href="?action=login">Inicia sesión</a> para comentar.</p>
    <?php endif; ?>
</section>

<p><a href="?action=index">← Volver</a></p>
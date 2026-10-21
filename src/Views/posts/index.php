<h1>Publicaciones</h1>

<form method="get">
    <input type="hidden" name="action" value="index">
    <input type="search" name="q" placeholder="Buscar por título o categoría"
           value="<?= htmlspecialchars($q) ?>">
    <button type="submit">Buscar</button>
    <?php if ($q !== ''): ?>
        <a href="?action=index">Quitar filtro</a>
    <?php endif; ?>
</form>

<?php if (!$posts): ?>
    <p>No hay publicaciones<?= $q !== '' ? ' que coincidan con la búsqueda' : '' ?>.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <article>
        <h2>
            <a href="?action=show&id=<?= (int) $post['id'] ?>">
                <?= htmlspecialchars($post['title']) ?>
            </a>
        </h2>
        <p>
            <?= htmlspecialchars($post['category']) ?> ·
            por <?= htmlspecialchars($post['username']) ?> ·
            <?= htmlspecialchars($post['created_at']) ?>
        </p>
    </article>
<?php endforeach; ?>
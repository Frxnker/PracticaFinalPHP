<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="post" action="<?= htmlspecialchars($formAction) ?>">
    <label>Título<br>
        <input type="text" name="title" maxlength="150" required
               value="<?= htmlspecialchars($post['title']) ?>">
    </label><br>
    <label>Categoría<br>
        <input type="text" name="category" maxlength="50" required
               value="<?= htmlspecialchars($post['category']) ?>">
    </label><br>
    <label>Contenido<br>
        <textarea name="body" rows="8" cols="60" required><?= htmlspecialchars($post['body']) ?></textarea>
    </label><br>
    <button type="submit"><?= htmlspecialchars($submitLabel) ?></button>
</form>
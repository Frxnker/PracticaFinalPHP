<h1>Iniciar sesión</h1>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="post" action="?action=login">
    <?= App\Core\Csrf::field() ?>
    <label>Email <input type="email" name="email" required></label><br>
    <label>Contraseña <input type="password" name="password" required></label><br>
    <button type="submit">Entrar</button>
</form>
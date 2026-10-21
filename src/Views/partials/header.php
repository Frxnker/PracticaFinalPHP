<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tablón de anuncios</title>
</head>
<body>
<header>
    <a href="?action=index">Tablón de anuncios</a>
    <nav>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="?action=create">Publicar</a>
            <span><?= htmlspecialchars($_SESSION['email']) ?></span>
            <a href="?action=logout">Cerrar sesión</a>
        <?php else: ?>
            <a href="?action=login">Iniciar sesión</a>
            <a href="?action=register">Registrarse</a>
        <?php endif; ?>
    </nav>
</header>

<?php if (!empty($_SESSION['flash'])): ?>
    <p><strong><?= htmlspecialchars($_SESSION['flash']) ?></strong></p>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<main>
<h1>Editar publicación</h1>
<?php
$formAction  = '?action=edit&id=' . (int) $post['id'];
$submitLabel = 'Guardar cambios';
require __DIR__ . '/form.php';
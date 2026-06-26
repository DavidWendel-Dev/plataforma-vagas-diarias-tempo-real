<?php
// Redirecionar para o arquivo correto
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    header('Location: diaria-nova.php?id=' . $id);
} else {
    header('Location: diaria-nova.php');
}
exit;

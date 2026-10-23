<?php

require_once __DIR__ . '/../config/app.php';
exigirLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit;
}

try {
    $consulta = banco()->prepare(
        'SELECT i.arquivo, s.cidadao_id, s.administrador_id
         FROM imagem i INNER JOIN solicitacao s ON s.id = i.solicitacao_id
         WHERE i.id = ?'
    );
    $consulta->execute([$id]);
    $imagem = $consulta->fetch();
} catch (PDOException $excecao) {
    http_response_code(503);
    exit;
}

if (!$imagem || ($_SESSION['papel'] !== 'administrador'
    && ($_SESSION['papel'] === 'trabalhador'
        ? (int) $imagem['administrador_id'] !== (int) $_SESSION['usuario_id']
        : (int) $imagem['cidadao_id'] !== (int) $_SESSION['usuario_id']))) {
    http_response_code(404);
    exit;
}

$nome = basename($imagem['arquivo']);
$extensoes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
$caminho = __DIR__ . '/../uploads/' . $nome;
if (!isset($extensoes[$extensao]) || !is_file($caminho)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $extensoes[$extensao]);
header('Content-Length: ' . filesize($caminho));
header('X-Content-Type-Options: nosniff');
readfile($caminho);

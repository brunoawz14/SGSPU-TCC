<?php

session_start();
require_once __DIR__ . '/conexao.php';

function banco(): PDO
{
    static $conexao = null;
    if ($conexao === null) {
        $conexao = conectarBanco();
    }
    return $conexao;
}

function h($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function tamanho(string $valor): int
{
    return function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
}

function categorias(): array
{
    return ['Iluminação pública', 'Limpeza urbana', 'Pavimentação', 'Áreas verdes', 'Outro'];
}

function statusDisponiveis(): array
{
    return ['ABERTA', 'EM_ANDAMENTO', 'CONCLUIDA'];
}

function prioridadesDisponiveis(): array
{
    return ['BAIXA', 'NORMAL', 'ALTA'];
}

function caminho(string $rota): string
{
    $diretorio = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    $partes = $diretorio === '' || $diretorio === '.' ? [] : explode('/', $diretorio);
    if ($partes && in_array(end($partes), ['auth', 'admin', 'solicitacoes'], true)) {
        array_pop($partes);
    }
    $base = $partes ? '/' . implode('/', $partes) : '';
    return $base . '/' . ltrim($rota, '/');
}

function exigirLogin(?string $papel = null): void
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . caminho('auth/login.php'));
        exit;
    }
    if ($papel !== null && ($_SESSION['papel'] ?? '') !== $papel) {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }
}

function tokenCsrf(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfValido(): bool
{
    return hash_equals(tokenCsrf(), (string) ($_POST['csrf_token'] ?? ''));
}

function cabecalho(string $titulo): void
{
    $tituloSeguro = h($titulo);
    $nome = h($_SESSION['usuario_nome'] ?? '');
    $autenticado = isset($_SESSION['usuario_id']);
    $papel = $_SESSION['papel'] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $tituloSeguro ?> | SGSPU</title>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    </head>
    <body class="w3-light-grey">
        <nav class="w3-bar w3-blue-grey">
            <a href="<?= h(caminho('index.php')) ?>" class="w3-bar-item w3-button"><strong>SGSPU</strong></a>
            <?php if ($autenticado): ?>
                <?php if ($papel === 'cidadao'): ?>
                    <a href="<?= h(caminho('solicitacoes/nova.php')) ?>" class="w3-bar-item w3-button">Nova solicitação</a>
                <?php elseif ($papel === 'administrador'): ?>
                    <a href="<?= h(caminho('admin/trabalhadores.php')) ?>" class="w3-bar-item w3-button">Trabalhadores</a>
                    <a href="<?= h(caminho('admin/cidadaos.php')) ?>" class="w3-bar-item w3-button">Cidadãos</a>
                <?php endif; ?>
                <a href="<?= h(caminho('auth/sair.php')) ?>" class="w3-bar-item w3-button w3-right">Sair</a>
                <span class="w3-bar-item w3-right w3-hide-small"><?= $nome ?></span>
            <?php endif; ?>
        </nav>
        <main class="w3-content w3-padding-24" style="max-width:1100px">
            <h1 class="w3-xlarge"><?= $tituloSeguro ?></h1>
    <?php
}

function rodape(): void
{
    echo '</main></body></html>';
}

function aviso(string $mensagem, bool $erro = false): void
{
    $classe = $erro ? 'w3-pale-red w3-border-red' : 'w3-pale-green w3-border-green';
    echo '<div class="w3-panel w3-leftbar ' . $classe . '"><p>' . h($mensagem) . '</p></div>';
}

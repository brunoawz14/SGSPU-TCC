<?php

require_once __DIR__ . '/../config/app.php';

try {
    $existeAdministrador = (int) banco()->query(
        "SELECT COUNT(*) FROM administrador WHERE nivel_acesso = 'GERAL'"
    )->fetchColumn() > 0;
} catch (PDOException $excecao) {
    http_response_code(503);
    exit('Não foi possível conectar ao banco de dados.');
}

if ($existeAdministrador) {
    header('Location: ' . caminho('auth/login.php'));
    exit;
}

$erro = '';
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if (!csrfValido()) {
        $erro = 'Formulário inválido. Atualize a página.';
    } elseif ($nome === '' || tamanho($nome) > 150) {
        $erro = 'Informe um nome com até 150 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $erro = 'Informe um e-mail válido.';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres.';
    } else {
        try {
            $banco = banco();
            $banco->beginTransaction();
            $inserir = $banco->prepare('INSERT INTO usuario (nome, email, senha) VALUES (?, ?, ?)');
            $inserir->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $id = (int) $banco->lastInsertId();
            $inserir = $banco->prepare(
                'INSERT INTO administrador (usuario_id, nivel_acesso) VALUES (?, ?)'
            );
            $inserir->execute([$id, 'GERAL']);
            $banco->commit();

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['papel'] = 'administrador';
            header('Location: ' . caminho('index.php') . '?cadastro=admin');
            exit;
        } catch (PDOException $excecao) {
            if (isset($banco) && $banco->inTransaction()) $banco->rollBack();
            $erro = $excecao->getCode() === '23000'
                ? 'E-mail já cadastrado.'
                : 'Não foi possível criar a conta de administrador.';
        }
    }
}

cabecalho('Configuração inicial');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding" style="max-width:600px">
    <p>Crie a primeira conta de administrador. Esta página deixa de permitir novos cadastros após a configuração.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
        <p><label>Nome<input class="w3-input w3-border" name="nome" maxlength="150" required value="<?= h($nome) ?>"></label></p>
        <p><label>E-mail<input class="w3-input w3-border" type="email" name="email" maxlength="150" required value="<?= h($email) ?>"></label></p>
        <p><label>Senha<input class="w3-input w3-border" type="password" name="senha" minlength="8" required></label></p>
        <button class="w3-button w3-blue-grey" type="submit">Configurar administrador</button>
    </form>
    <p><a href="<?= h(caminho('auth/login.php')) ?>">Voltar ao login</a></p>
</div>
<?php rodape(); ?>

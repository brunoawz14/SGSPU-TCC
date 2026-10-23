<?php

require_once __DIR__ . '/../config/app.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . caminho('index.php'));
    exit;
}

$erro = '';
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');

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
    } elseif (strlen($cpf) !== 11) {
        $erro = 'Informe um CPF com 11 dígitos.';
    } elseif (strlen($telefone) > 20) {
        $erro = 'O telefone deve ter até 20 caracteres.';
    } else {
        try {
            $banco = banco();
            $banco->beginTransaction();
            $inserir = $banco->prepare('INSERT INTO usuario (nome, email, senha) VALUES (?, ?, ?)');
            $inserir->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);
            $id = (int) $banco->lastInsertId();

            $inserir = $banco->prepare(
                'INSERT INTO cidadao (usuario_id, cpf, telefone) VALUES (?, ?, ?)'
            );
            $inserir->execute([$id, $cpf, $telefone ?: null]);

            $banco->commit();
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['papel'] = 'cidadao';
            header('Location: ' . caminho('index.php') . '?cadastro=cidadao');
            exit;
        } catch (PDOException $excecao) {
            if (isset($banco) && $banco->inTransaction()) $banco->rollBack();
            $erro = $excecao->getCode() === '23000'
                ? 'E-mail ou CPF já cadastrado.'
                : 'Não foi possível concluir o cadastro.';
        }
    }
}

cabecalho('Criar conta de cidadão');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding" style="max-width:600px">
    <p>Com sua conta, você poderá abrir e acompanhar solicitações de serviços urbanos.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
        <p><label>Nome<input class="w3-input w3-border" name="nome" maxlength="150" required value="<?= h($nome) ?>"></label></p>
        <p><label>E-mail<input class="w3-input w3-border" type="email" name="email" maxlength="150" required value="<?= h($email) ?>"></label></p>
        <p><label>Senha<input class="w3-input w3-border" type="password" name="senha" minlength="8" required></label></p>
        <p><label>CPF<input class="w3-input w3-border" name="cpf" inputmode="numeric" maxlength="14" required value="<?= h($cpf) ?>"></label></p>
        <p><label>Telefone<input class="w3-input w3-border" name="telefone" maxlength="20" value="<?= h($telefone) ?>"></label></p>
        <button class="w3-button w3-blue-grey" type="submit">Criar conta</button>
    </form>
    <p><a href="<?= h(caminho('auth/login.php')) ?>">Já tenho uma conta</a></p>
</div>
<?php rodape(); ?>

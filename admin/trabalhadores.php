<?php

require_once __DIR__ . '/../config/app.php';
exigirLogin('administrador');

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
            $inserir->execute([$id, 'OPERADOR']);
            $banco->commit();
            header('Location: ' . caminho('admin/trabalhadores.php') . '?criado=1');
            exit;
        } catch (PDOException $excecao) {
            if (isset($banco) && $banco->inTransaction()) $banco->rollBack();
            $erro = $excecao->getCode() === '23000'
                ? 'E-mail já cadastrado.'
                : 'Não foi possível cadastrar o trabalhador.';
        }
    }
}

$trabalhadores = [];
try {
    $consulta = banco()->query(
        "SELECT u.id, u.nome, u.email, u.ativo,
                (SELECT COUNT(*) FROM solicitacao s WHERE s.administrador_id = u.id) AS atribuidas
         FROM administrador a INNER JOIN usuario u ON u.id = a.usuario_id
         WHERE a.nivel_acesso = 'OPERADOR' ORDER BY u.nome"
    );
    $trabalhadores = $consulta->fetchAll();
} catch (PDOException $excecao) {
    $erro = 'Não foi possível consultar os trabalhadores.';
}

cabecalho('Trabalhadores');
if (isset($_GET['criado'])) aviso('Trabalhador cadastrado com sucesso.');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding w3-margin-bottom" style="max-width:650px">
    <h2 class="w3-large">Cadastrar trabalhador</h2>
    <p>Ele poderá entrar no sistema para acompanhar e atualizar as solicitações atribuídas a ele.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
        <p><label>Nome<input class="w3-input w3-border" name="nome" maxlength="150" required value="<?= h($nome) ?>"></label></p>
        <p><label>E-mail<input class="w3-input w3-border" type="email" name="email" maxlength="150" required value="<?= h($email) ?>"></label></p>
        <p><label>Senha<input class="w3-input w3-border" type="password" name="senha" minlength="8" required></label></p>
        <button class="w3-button w3-blue-grey" type="submit">Cadastrar trabalhador</button>
    </form>
</div>

<div class="w3-responsive w3-card w3-white">
    <table class="w3-table-all w3-hoverable">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Situação</th><th>Solicitações atribuídas</th></tr></thead>
        <tbody>
            <?php foreach ($trabalhadores as $trabalhador): ?>
                <tr>
                    <td><?= h($trabalhador['nome']) ?></td>
                    <td><?= h($trabalhador['email']) ?></td>
                    <td><?= $trabalhador['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                    <td><?= (int) $trabalhador['atribuidas'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php rodape(); ?>

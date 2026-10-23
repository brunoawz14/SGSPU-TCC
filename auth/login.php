<?php

require_once __DIR__ . '/../config/app.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . caminho('index.php'));
    exit;
}

$erro = '';
$email = trim($_POST['email'] ?? '');
$primeiroAcesso = false;

try {
    $primeiroAcesso = (int) banco()->query("SELECT COUNT(*) FROM administrador WHERE nivel_acesso = 'GERAL'")->fetchColumn() === 0;
} catch (PDOException $excecao) {
    $erro = 'Não foi possível conectar ao banco de dados.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    if (!csrfValido()) {
        $erro = 'Formulário inválido. Atualize a página.';
    } else {
        try {
            $consulta = banco()->prepare(
                'SELECT u.id, u.nome, u.senha, a.nivel_acesso, c.usuario_id AS cidadao_id
                 FROM usuario u
                 LEFT JOIN administrador a ON a.usuario_id = u.id
                 LEFT JOIN cidadao c ON c.usuario_id = u.id
                 WHERE u.email = ? AND u.ativo = 1'
            );
            $consulta->execute([$email]);
            $usuario = $consulta->fetch();

            $papel = null;
            if ($usuario) {
                if ($usuario['nivel_acesso'] === 'GERAL') {
                    $papel = 'administrador';
                } elseif ($usuario['nivel_acesso'] === 'OPERADOR') {
                    $papel = 'trabalhador';
                } elseif ($usuario['cidadao_id'] !== null) {
                    $papel = 'cidadao';
                }
            }

            if ($usuario && $papel !== null
                && password_verify($_POST['senha'] ?? '', $usuario['senha'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['papel'] = $papel;
                header('Location: ' . caminho('index.php'));
                exit;
            }

            $erro = 'E-mail ou senha inválidos.';
        } catch (PDOException $excecao) {
            $erro = 'Não foi possível entrar. Confira a conexão com o banco.';
        }
    }
}

cabecalho('Entrar');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding" style="max-width:500px">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
        <p><label>E-mail<input class="w3-input w3-border" type="email" name="email" required value="<?= h($email) ?>"></label></p>
        <p><label>Senha<input class="w3-input w3-border" type="password" name="senha" required></label></p>
        <button class="w3-button w3-blue-grey" type="submit">Entrar</button>
    </form>
    <p><a class="w3-button w3-border w3-block" href="<?= h(caminho('auth/cadastro.php')) ?>">Criar conta de cidadão</a></p>
    <p class="w3-small w3-text-grey">Trabalhadores e administradores entram com suas credenciais neste mesmo formulário.</p>
    <?php if ($primeiroAcesso): ?>
        <p class="w3-small w3-text-grey"><a href="<?= h(caminho('auth/configurar_admin.php')) ?>">Configuração inicial da administração</a></p>
    <?php endif; ?>
</div>
<?php rodape(); ?>

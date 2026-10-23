<?php

require_once __DIR__ . '/../config/app.php';
exigirLogin('administrador');

$busca = trim($_GET['busca'] ?? '');
$erro = '';
$cidadaos = [];
try {
    $sql = 'SELECT u.nome, u.email, u.ativo, c.cpf, c.telefone,
                   (SELECT COUNT(*) FROM solicitacao s WHERE s.cidadao_id = u.id) AS total_solicitacoes
            FROM cidadao c INNER JOIN usuario u ON u.id = c.usuario_id';
    $parametros = [];
    if ($busca !== '') {
        $sql .= ' WHERE u.nome LIKE ? OR u.email LIKE ? OR c.cpf LIKE ?';
        $termo = '%' . $busca . '%';
        $parametros = [$termo, $termo, $termo];
    }
    $sql .= ' ORDER BY u.nome';
    $consulta = banco()->prepare($sql);
    $consulta->execute($parametros);
    $cidadaos = $consulta->fetchAll();
} catch (PDOException $excecao) {
    $erro = 'Não foi possível consultar os cidadãos.';
}

cabecalho('Cidadãos cadastrados');
if ($erro !== '') aviso($erro, true);
?>
<form method="get" class="w3-margin-bottom">
    <label for="busca">Buscar por nome, e-mail ou CPF</label>
    <input class="w3-input w3-border" id="busca" name="busca" style="max-width:360px" value="<?= h($busca) ?>">
    <button class="w3-button w3-blue-grey w3-margin-top" type="submit">Consultar</button>
</form>

<?php if (!$cidadaos): ?>
    <div class="w3-panel w3-white w3-padding">Nenhum cidadão encontrado.</div>
<?php else: ?>
    <div class="w3-responsive w3-card w3-white">
        <table class="w3-table-all w3-hoverable">
            <thead><tr><th>Nome</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Situação</th><th>Solicitações</th></tr></thead>
            <tbody>
                <?php foreach ($cidadaos as $cidadao): ?>
                    <tr>
                        <td><?= h($cidadao['nome']) ?></td>
                        <td><?= h($cidadao['email']) ?></td>
                        <td><?= h($cidadao['cpf']) ?></td>
                        <td><?= h($cidadao['telefone']) ?></td>
                        <td><?= $cidadao['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                        <td><?= (int) $cidadao['total_solicitacoes'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php rodape(); ?>

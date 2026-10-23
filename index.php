<?php

require_once __DIR__ . '/config/app.php';
exigirLogin();

$papel = $_SESSION['papel'];
$administrador = $papel === 'administrador';
$trabalhador = $papel === 'trabalhador';
$filtro = $_GET['status'] ?? '';
if ($filtro !== '' && !in_array($filtro, statusDisponiveis(), true)) {
    $filtro = '';
}

$erro = '';
$solicitacoes = [];
$totais = [];
try {
    $sql = 'SELECT s.id, s.categoria, s.status, s.prioridade, s.data_abertura,
                   u.nome AS cidadao, t.nome AS trabalhador, l.endereco
            FROM solicitacao s
            INNER JOIN usuario u ON u.id = s.cidadao_id
            INNER JOIN localizacao l ON l.solicitacao_id = s.id
            LEFT JOIN usuario t ON t.id = s.administrador_id';
    $condicoes = [];
    $parametros = [];
    if ($trabalhador) {
        $condicoes[] = 's.administrador_id = ?';
        $parametros[] = $_SESSION['usuario_id'];
    } elseif (!$administrador) {
        $condicoes[] = 's.cidadao_id = ?';
        $parametros[] = $_SESSION['usuario_id'];
    }
    if ($filtro !== '') {
        $condicoes[] = 's.status = ?';
        $parametros[] = $filtro;
    }
    if ($condicoes) {
        $sql .= ' WHERE ' . implode(' AND ', $condicoes);
    }
    $sql .= ' ORDER BY s.data_abertura DESC, s.id DESC';
    $consulta = banco()->prepare($sql);
    $consulta->execute($parametros);
    $solicitacoes = $consulta->fetchAll();

    $sqlTotais = 'SELECT status, COUNT(*) AS total FROM solicitacao';
    if ($trabalhador) {
        $sqlTotais .= ' WHERE administrador_id = ?';
    } elseif (!$administrador) {
        $sqlTotais .= ' WHERE cidadao_id = ?';
    }
    $sqlTotais .= ' GROUP BY status';
    $consulta = banco()->prepare($sqlTotais);
    $consulta->execute($administrador ? [] : [$_SESSION['usuario_id']]);
    foreach ($consulta->fetchAll() as $linha) {
        $totais[$linha['status']] = (int) $linha['total'];
    }
} catch (PDOException $excecao) {
    $erro = 'Não foi possível consultar as solicitações. Confira se o banco foi atualizado.';
}

cabecalho($administrador ? 'Painel administrativo' : ($trabalhador ? 'Solicitações atribuídas' : 'Minhas solicitações'));
if ($erro !== '') aviso($erro, true);
if (($_GET['cadastro'] ?? '') === 'admin') aviso('Administrador cadastrado. Agora você pode cadastrar trabalhadores.');
if (($_GET['cadastro'] ?? '') === 'cidadao') aviso('Conta de cidadão criada com sucesso.');
if ($papel === 'cidadao'): ?>
    <p><a class="w3-button w3-blue-grey" href="<?= h(caminho('solicitacoes/nova.php')) ?>">Abrir solicitação</a></p>
<?php endif; ?>

<div class="w3-row-padding w3-margin-bottom">
    <?php foreach (statusDisponiveis() as $status): ?>
        <div class="w3-third w3-margin-bottom">
            <div class="w3-card w3-white w3-padding">
                <strong><?= h(str_replace('_', ' ', $status)) ?></strong>
                <div class="w3-xxlarge"><?= $totais[$status] ?? 0 ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<form class="w3-margin-bottom" method="get">
    <label for="status">Filtrar por status</label>
    <select class="w3-select w3-border" id="status" name="status" style="max-width:260px">
        <option value="">Todos</option>
        <?php foreach (statusDisponiveis() as $status): ?>
            <option value="<?= h($status) ?>" <?= $filtro === $status ? 'selected' : '' ?>><?= h(str_replace('_', ' ', $status)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="w3-button w3-blue-grey" type="submit">Consultar</button>
</form>

<?php if (!$solicitacoes): ?>
    <div class="w3-panel w3-white w3-padding">Nenhuma solicitação encontrada.</div>
<?php else: ?>
    <div class="w3-responsive w3-card w3-white">
        <table class="w3-table-all w3-hoverable">
            <thead><tr>
                <th>Nº</th><th>Categoria</th>
                <?php if ($administrador || $trabalhador): ?><th>Cidadão</th><?php endif; ?>
                <th>Endereço</th><th>Prioridade</th><th>Status</th>
                <?php if ($administrador): ?><th>Trabalhador</th><?php endif; ?>
                <th>Abertura</th><th></th>
            </tr></thead>
            <tbody>
                <?php foreach ($solicitacoes as $item): ?>
                    <tr>
                        <td><?= (int) $item['id'] ?></td>
                        <td><?= h($item['categoria']) ?></td>
                        <?php if ($administrador || $trabalhador): ?><td><?= h($item['cidadao']) ?></td><?php endif; ?>
                        <td><?= h($item['endereco']) ?></td>
                        <td><?= h($item['prioridade']) ?></td>
                        <td><?= h(str_replace('_', ' ', $item['status'])) ?></td>
                        <?php if ($administrador): ?><td><?= h($item['trabalhador'] ?: 'Não atribuído') ?></td><?php endif; ?>
                        <td><?= h(date('d/m/Y H:i', strtotime($item['data_abertura']))) ?></td>
                        <td><a href="<?= h(caminho('solicitacoes/detalhe.php')) ?>?id=<?= (int) $item['id'] ?>">Ver</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php rodape(); ?>

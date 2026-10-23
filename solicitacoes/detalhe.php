<?php

require_once __DIR__ . '/../config/app.php';
exigirLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    exit('Solicitação não encontrada.');
}

$administrador = $_SESSION['papel'] === 'administrador';
$trabalhador = $_SESSION['papel'] === 'trabalhador';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$administrador && !$trabalhador) {
        http_response_code(403);
        exit('Acesso não autorizado.');
    }

    $novoStatus = $_POST['status'] ?? '';
    $novaPrioridade = $_POST['prioridade'] ?? '';
    $trabalhadorId = $_POST['trabalhador_id'] ?? '';
    if (!csrfValido()) {
        $erro = 'Formulário inválido. Atualize a página.';
    } elseif ($administrador && (!in_array($novaPrioridade, prioridadesDisponiveis(), true)
        || ($trabalhadorId !== '' && (!ctype_digit((string) $trabalhadorId) || (int) $trabalhadorId < 1)))) {
        $erro = 'Escolha um trabalhador e uma prioridade válidos.';
    } elseif ($trabalhador && !in_array($novoStatus, statusDisponiveis(), true)) {
        $erro = 'Escolha um status válido.';
    } else {
        try {
            $banco = banco();
            $banco->beginTransaction();
            $consulta = $banco->prepare(
                'SELECT status, administrador_id FROM solicitacao WHERE id = ? FOR UPDATE'
            );
            $consulta->execute([$id]);
            $atual = $consulta->fetch();
            if (!$atual) {
                $banco->rollBack();
                http_response_code(404);
                exit('Solicitação não encontrada.');
            }

            if ($administrador) {
                $destino = $trabalhadorId === '' ? null : (int) $trabalhadorId;
                if ($destino !== null) {
                    $consulta = $banco->prepare(
                        "SELECT COUNT(*) FROM administrador a
                         INNER JOIN usuario u ON u.id = a.usuario_id
                         WHERE a.usuario_id = ? AND a.nivel_acesso = 'OPERADOR' AND u.ativo = 1"
                    );
                    $consulta->execute([$destino]);
                    if ((int) $consulta->fetchColumn() === 0) {
                        throw new InvalidArgumentException('Selecione um trabalhador ativo.');
                    }
                }
                $alterar = $banco->prepare(
                    'UPDATE solicitacao SET administrador_id = ?, prioridade = ? WHERE id = ?'
                );
                $alterar->execute([$destino, $novaPrioridade, $id]);
                if ($destino !== ($atual['administrador_id'] === null ? null : (int) $atual['administrador_id'])) {
                    $inserir = $banco->prepare(
                        'INSERT INTO notificacao (solicitacao_id, mensagem) VALUES (?, ?)'
                    );
                    $inserir->execute([
                        $id,
                        $destino === null ? 'Solicitação aguardando atribuição.' : 'Solicitação encaminhada para atendimento.',
                    ]);
                }
            } else {
                if ((int) $atual['administrador_id'] !== (int) $_SESSION['usuario_id']) {
                    $banco->rollBack();
                    http_response_code(403);
                    exit('Esta solicitação não está atribuída a você.');
                }
                if ($atual['status'] !== $novoStatus) {
                    $alterar = $banco->prepare('UPDATE solicitacao SET status = ? WHERE id = ?');
                    $alterar->execute([$novoStatus, $id]);
                    $inserir = $banco->prepare(
                        'INSERT INTO historico_solicitacao
                         (solicitacao_id, status_anterior, status_novo, administrador_id)
                         VALUES (?, ?, ?, ?)'
                    );
                    $inserir->execute([$id, $atual['status'], $novoStatus, $_SESSION['usuario_id']]);
                    $inserir = $banco->prepare(
                        'INSERT INTO notificacao (solicitacao_id, mensagem) VALUES (?, ?)'
                    );
                    $inserir->execute([$id, 'Status atualizado para ' . str_replace('_', ' ', $novoStatus) . '.']);
                }
            }

            $banco->commit();
            header('Location: ' . caminho('solicitacoes/detalhe.php') . '?id=' . $id . '&atualizada=1');
            exit;
        } catch (PDOException | InvalidArgumentException $excecao) {
            if (isset($banco) && $banco->inTransaction()) $banco->rollBack();
            $erro = $excecao instanceof InvalidArgumentException
                ? $excecao->getMessage()
                : 'Não foi possível atualizar a solicitação.';
        }
    }
}

try {
    $consulta = banco()->prepare(
        'SELECT s.*, u.nome AS cidadao, t.nome AS trabalhador_nome, l.endereco
         FROM solicitacao s
         INNER JOIN usuario u ON u.id = s.cidadao_id
         INNER JOIN localizacao l ON l.solicitacao_id = s.id
         LEFT JOIN usuario t ON t.id = s.administrador_id
         WHERE s.id = ?'
    );
    $consulta->execute([$id]);
    $solicitacao = $consulta->fetch();
    if (!$solicitacao || (!$administrador
        && ($trabalhador
            ? (int) $solicitacao['administrador_id'] !== (int) $_SESSION['usuario_id']
            : (int) $solicitacao['cidadao_id'] !== (int) $_SESSION['usuario_id']))) {
        http_response_code(404);
        exit('Solicitação não encontrada.');
    }

    $trabalhadores = [];
    if ($administrador) {
        $trabalhadores = banco()->query(
            "SELECT u.id, u.nome FROM administrador a
             INNER JOIN usuario u ON u.id = a.usuario_id
             WHERE a.nivel_acesso = 'OPERADOR' AND u.ativo = 1 ORDER BY u.nome"
        )->fetchAll();
    }

    $consulta = banco()->prepare('SELECT id FROM imagem WHERE solicitacao_id = ? ORDER BY id');
    $consulta->execute([$id]);
    $imagens = $consulta->fetchAll();

    $consulta = banco()->prepare(
        'SELECT h.status_anterior, h.status_novo, h.data_alteracao, u.nome AS responsavel
         FROM historico_solicitacao h
         LEFT JOIN usuario u ON u.id = h.administrador_id
         WHERE h.solicitacao_id = ? ORDER BY h.id DESC'
    );
    $consulta->execute([$id]);
    $historico = $consulta->fetchAll();

    $consulta = banco()->prepare(
        'SELECT mensagem, data_envio FROM notificacao WHERE solicitacao_id = ? ORDER BY id DESC'
    );
    $consulta->execute([$id]);
    $notificacoes = $consulta->fetchAll();
} catch (PDOException $excecao) {
    http_response_code(503);
    exit('Não foi possível consultar a solicitação.');
}

cabecalho('Solicitação #' . $id);
if (isset($_GET['criada'])) aviso('Solicitação cadastrada com sucesso.');
if (isset($_GET['atualizada'])) aviso('Solicitação atualizada com sucesso.');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding w3-margin-bottom">
    <p><strong>Cidadão:</strong> <?= h($solicitacao['cidadao']) ?></p>
    <p><strong>Categoria:</strong> <?= h($solicitacao['categoria']) ?></p>
    <p><strong>Endereço:</strong> <?= h($solicitacao['endereco']) ?></p>
    <p><strong>Abertura:</strong> <?= h(date('d/m/Y H:i', strtotime($solicitacao['data_abertura']))) ?></p>
    <p><strong>Status:</strong> <?= h(str_replace('_', ' ', $solicitacao['status'])) ?></p>
    <p><strong>Prioridade:</strong> <?= h($solicitacao['prioridade']) ?></p>
    <p><strong>Trabalhador responsável:</strong> <?= h($solicitacao['trabalhador_nome'] ?: 'Não atribuído') ?></p>
    <p><strong>Descrição:</strong><br><?= nl2br(h($solicitacao['descricao'])) ?></p>
    <?php foreach ($imagens as $imagem): ?>
        <a href="<?= h(caminho('solicitacoes/imagem.php')) ?>?id=<?= (int) $imagem['id'] ?>" target="_blank" rel="noopener">
            <img class="w3-image w3-margin-bottom" src="<?= h(caminho('solicitacoes/imagem.php')) ?>?id=<?= (int) $imagem['id'] ?>" alt="Imagem enviada com a solicitação" style="max-height:320px">
        </a>
    <?php endforeach; ?>
</div>

<?php if ($administrador): ?>
    <div class="w3-card w3-white w3-padding w3-margin-bottom" style="max-width:600px">
        <h2 class="w3-large">Atribuir atendimento</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
            <p><label>Trabalhador
                <select class="w3-select w3-border" name="trabalhador_id">
                    <option value="">Não atribuído</option>
                    <?php foreach ($trabalhadores as $pessoa): ?>
                        <option value="<?= (int) $pessoa['id'] ?>" <?= (int) $solicitacao['administrador_id'] === (int) $pessoa['id'] ? 'selected' : '' ?>><?= h($pessoa['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label></p>
            <p><label>Prioridade
                <select class="w3-select w3-border" name="prioridade">
                    <?php foreach (prioridadesDisponiveis() as $prioridade): ?>
                        <option value="<?= h($prioridade) ?>" <?= $prioridade === $solicitacao['prioridade'] ? 'selected' : '' ?>><?= h($prioridade) ?></option>
                    <?php endforeach; ?>
                </select>
            </label></p>
            <button class="w3-button w3-blue-grey" type="submit">Salvar atribuição</button>
        </form>
    </div>
<?php endif; ?>

<?php if ($trabalhador): ?>
    <div class="w3-card w3-white w3-padding w3-margin-bottom" style="max-width:600px">
        <h2 class="w3-large">Atualizar atendimento</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
            <p><label>Status
                <select class="w3-select w3-border" name="status">
                    <?php foreach (statusDisponiveis() as $status): ?>
                        <option value="<?= h($status) ?>" <?= $status === $solicitacao['status'] ? 'selected' : '' ?>><?= h(str_replace('_', ' ', $status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label></p>
            <button class="w3-button w3-blue-grey" type="submit">Salvar status</button>
        </form>
    </div>
<?php endif; ?>

<div class="w3-row-padding">
    <section class="w3-half">
        <div class="w3-card w3-white w3-padding w3-margin-bottom">
            <h2 class="w3-large">Histórico</h2>
            <?php if (!$historico): ?><p>Sem alterações registradas.</p><?php endif; ?>
            <?php foreach ($historico as $evento): ?>
                <p><?= h(date('d/m/Y H:i', strtotime($evento['data_alteracao']))) ?> —
                    <?= h(str_replace('_', ' ', $evento['status_novo'])) ?>
                    <?php if ($evento['responsavel']): ?>por <?= h($evento['responsavel']) ?><?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="w3-half">
        <div class="w3-card w3-white w3-padding w3-margin-bottom">
            <h2 class="w3-large">Avisos</h2>
            <?php if (!$notificacoes): ?><p>Sem avisos.</p><?php endif; ?>
            <?php foreach ($notificacoes as $aviso): ?>
                <p><?= h(date('d/m/Y H:i', strtotime($aviso['data_envio']))) ?> — <?= h($aviso['mensagem']) ?></p>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php rodape(); ?>

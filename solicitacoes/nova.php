<?php

require_once __DIR__ . '/../config/app.php';
exigirLogin('cidadao');

$erro = '';
$categoria = $_POST['categoria'] ?? '';
$descricao = trim($_POST['descricao'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imagem = $_FILES['imagem'] ?? null;
    $temImagem = $imagem && $imagem['error'] !== UPLOAD_ERR_NO_FILE;
    $arquivoSalvo = null;

    if (!csrfValido()) {
        $erro = 'Formulário inválido. Atualize a página.';
    } elseif (!in_array($categoria, categorias(), true)) {
        $erro = 'Selecione uma categoria válida.';
    } elseif ($descricao === '' || tamanho($descricao) > 5000) {
        $erro = 'Descreva o problema em até 5000 caracteres.';
    } elseif ($endereco === '' || tamanho($endereco) > 255) {
        $erro = 'Informe o endereço em até 255 caracteres.';
    } elseif ($temImagem && $imagem['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Não foi possível receber a imagem.';
    } elseif ($temImagem && $imagem['size'] > 5 * 1024 * 1024) {
        $erro = 'A imagem deve ter no máximo 5 MB.';
    } else {
        try {
            $extensoes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $extensao = null;
            if ($temImagem) {
                $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($imagem['tmp_name']);
                $extensao = $extensoes[$tipo] ?? null;
                if ($extensao === null) throw new RuntimeException('Envie uma imagem JPG, PNG ou WebP.');
            }

            $banco = banco();
            $banco->beginTransaction();
            $inserir = $banco->prepare(
                'INSERT INTO solicitacao (categoria, descricao, cidadao_id) VALUES (?, ?, ?)'
            );
            $inserir->execute([$categoria, $descricao, $_SESSION['usuario_id']]);
            $id = (int) $banco->lastInsertId();

            $inserir = $banco->prepare(
                'INSERT INTO localizacao (endereco, solicitacao_id) VALUES (?, ?)'
            );
            $inserir->execute([$endereco, $id]);

            if ($temImagem) {
                $pasta = __DIR__ . '/../uploads';
                if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
                    throw new RuntimeException('Não foi possível criar a pasta de imagens.');
                }
                $nome = bin2hex(random_bytes(16)) . '.' . $extensao;
                $arquivoSalvo = $pasta . '/' . $nome;
                if (!move_uploaded_file($imagem['tmp_name'], $arquivoSalvo)) {
                    throw new RuntimeException('Não foi possível salvar a imagem.');
                }
                $inserir = $banco->prepare('INSERT INTO imagem (arquivo, solicitacao_id) VALUES (?, ?)');
                $inserir->execute(['uploads/' . $nome, $id]);
            }

            $inserir = $banco->prepare(
                'INSERT INTO historico_solicitacao (solicitacao_id, status_novo) VALUES (?, ?)'
            );
            $inserir->execute([$id, 'ABERTA']);
            $banco->commit();
            header('Location: ' . caminho('solicitacoes/detalhe.php') . '?id=' . $id . '&criada=1');
            exit;
        } catch (Throwable $excecao) {
            if (isset($banco) && $banco->inTransaction()) $banco->rollBack();
            if ($arquivoSalvo !== null && is_file($arquivoSalvo)) unlink($arquivoSalvo);
            $erro = $excecao instanceof RuntimeException
                ? $excecao->getMessage()
                : 'Não foi possível salvar a solicitação.';
        }
    }
}

cabecalho('Nova solicitação');
if ($erro !== '') aviso($erro, true);
?>
<div class="w3-card w3-white w3-padding" style="max-width:750px">
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(tokenCsrf()) ?>">
        <p><label>Categoria
            <select class="w3-select w3-border" name="categoria" required>
                <option value="">Selecione</option>
                <?php foreach (categorias() as $opcao): ?>
                    <option value="<?= h($opcao) ?>" <?= $categoria === $opcao ? 'selected' : '' ?>><?= h($opcao) ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
        <p><label>Descrição do problema<textarea class="w3-input w3-border" name="descricao" rows="5" maxlength="5000" required><?= h($descricao) ?></textarea></label></p>
        <p><label>Endereço<input class="w3-input w3-border" name="endereco" maxlength="255" required value="<?= h($endereco) ?>"></label></p>
        <p><label>Imagem opcional (JPG, PNG ou WebP, até 5 MB)
            <input class="w3-input" type="file" name="imagem" accept="image/jpeg,image/png,image/webp">
        </label></p>
        <button class="w3-button w3-blue-grey" type="submit">Enviar solicitação</button>
    </form>
</div>
<?php rodape(); ?>

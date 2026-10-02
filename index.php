<?php

require_once __DIR__ . "/classes/Cidadao.php";

$cidadao = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $cpf = $_POST["cpf"];
    $telefone = $_POST["telefone"];

    $cidadao = new Cidadao(
        null,
        $nome,
        $email,
        $senha,
        $cpf,
        $telefone
    );
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Cidadão</title>
</head>

<body>

    <h1>Cadastro de Cidadão</h1>

    <form method="POST">

        <label for="nome">Nome:</label>
        <br>
        <input
            type="text"
            id="nome"
            name="nome"
            required
        >

        <br><br>

        <label for="email">E-mail:</label>
        <br>
        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>

        <label for="senha">Senha:</label>
        <br>
        <input
            type="password"
            id="senha"
            name="senha"
            required
        >

        <br><br>

        <label for="cpf">CPF:</label>
        <br>
        <input
            type="text"
            id="cpf"
            name="cpf"
            required
        >

        <br><br>

        <label for="telefone">Telefone:</label>
        <br>
        <input
            type="text"
            id="telefone"
            name="telefone"
        >

        <br><br>

        <button type="submit">
            Cadastrar
        </button>

    </form>

    <?php if ($cidadao !== null): ?>

        <hr>

        <h2>Cidadão cadastrado</h2>

        <p>
            <?php echo $cidadao->exibirDados(); ?>
        </p>

    <?php endif; ?>

</body>

</html>
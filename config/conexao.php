<?php

function conectarBanco(): PDO
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $nomeBanco = getenv('DB_NAME') ?: 'gestao_servicos_publicos';
    $usuario = getenv('DB_USER') ?: 'root';
    $senha = getenv('DB_PASSWORD') ?: '';

    return new PDO(
        "mysql:host={$host};dbname={$nomeBanco};charset=utf8mb4",
        $usuario,
        $senha,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

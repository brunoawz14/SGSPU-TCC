<?php

require_once "Usuario.php";

class Cidadao extends Usuario
{
    private $cpf;
    private $telefone;

    public function __construct(
        $id,
        $nome,
        $email,
        $senha,
        $cpf,
        $telefone,
        $ativo = true
    ) {
        parent::__construct($id, $nome, $email, $senha, $ativo);

        $this->cpf = $cpf;
        $this->telefone = $telefone;
    }

    public function getCpf()
    {
        return $this->cpf;
    }

    public function setCpf($cpf)
    {
        $this->cpf = $cpf;
    }

    public function getTelefone()
    {
        return $this->telefone;
    }

    public function setTelefone($telefone)
    {
        $this->telefone = $telefone;
    }

    public function exibirDados()
    {
        return "Nome: " . $this->getNome() .
               "<br>Email: " . $this->getEmail() .
               "<br>CPF: " . $this->cpf .
               "<br>Telefone: " . $this->telefone;
    }
}
?>
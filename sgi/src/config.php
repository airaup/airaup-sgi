<?php
$config = array(
    'DB_HOST' => getenv('DB_HOST'),
    'DB_USERNAME' => getenv('DB_USERNAME'),
    'DB_PASSWORD' => getenv('DB_PASSWORD'),
    'DB_DATABASE' => getenv('DB_DATABASE')
);

assert($config['DB_HOST'] != '');
assert($config['DB_USERNAME'] != '');
assert($config['DB_PASSWORD'] != '');
assert($config['DB_DATABASE'] != '');

// TODO: Remover clase conectar.
class conectar
{
    private $servidor = null;
    private $base = null;
    private $usuario = null;
    private $pass = null;

    public function __construct()
    {
        $this->servidor = getenv('DB_HOST');
        $this->base = getenv('DB_DATABASE');
        $this->usuario = getenv('DB_USERNAME');
        $this->pass = getenv('DB_PASSWORD');
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function getUsr()
    {
        return $this->usuario;
    }

    public function getServ()
    {
        return $this->servidor;
    }
}

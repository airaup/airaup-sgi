<?php
class conectar
{
    public $servidor="mysql";
    public $base="c0310458_sgi";
    public $usuario="c0310458_sgi";
    public $pass="Rotaract2016";

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

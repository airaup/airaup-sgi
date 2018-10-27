<?php
class ConexionDB
{
    public $link = false;
    public $estado = false;
    public $sql = '';
    public $result = null;
    public $linea = null;

    public function ConexionDB($server="mysql", $basedatos="c0310458_sgi", $user="c0310458_sgi", $pass="Rotaract2016")
    {
        if ($this->link=mysql_connect("$server", "$user", "$pass")) {
            if (mysql_select_db("$basedatos", $this->link)) {
                //Marco que me conecté
                mysql_query("SET character_set_results=utf8", $this->link);
                mb_language('uni');
                mb_internal_encoding('UTF-8');
                mysql_query("set names 'utf8'", $this->link);
                $this->estado=true;
            } else {
                //Marco que no me conecté
                $this->estado=false;
            }
        }
    }

    //Tamano : retorna la cantidad de lineas de la consulta
    public function Tamano()
    {
        return mysql_num_rows($this->result);
    }

    //Siguiente : Obtengo el próximo registro
    public function Siguiente()
    {
        //Si se ejecutó una consulta
        if ($this->result <> null) {
            //Obtengo y retorno la próxima linea
            $this->linea = mysql_fetch_array($this->result);
            $retorno=$this->linea;
        } else {
            $retorno=null;
        }
        return $retorno;
    }

    //Libero : libero los recursos de la consulta y cierro la conexión
    public function Libero()
    {
        if ($this->result <> null) {
            mysql_free_result($this->result);
        }
        mysql_close($this->link);
    }

    //Ejecuto : la sql recibida contra la conexión establecida
    public function Ejecuto($sql = '')
    {
        //Guardo la sql recibida
        if ($sql <> '') {
            $this->sql = $sql;
        }
        //Ejecuto la consulta sobre la conexión establecida
        $this->result = mysql_query($this->sql, $this->link);
        //Si no hay error almaceno el puntero a los registros
        if ($this->result <> null) {
            $retorno=$this->result;
        } else {
            $retorno=null;
        }
        return $retorno;
    }

    //Dato : Obtiene la info de un campo en particular
    public function Dato($campo = '')
    {
        //Si hay una consulta que fue ejecutada
        if ($this->result <> null) {
            //Si no tengo un alinea obtenida mediante Siguiente, la obtengo
            if ($this->linea == null) {
                $this->linea = mysql_fetch_array($this->result);
            }
            $retorno=$this->linea["$campo"];
        } else {
            $retorno = "";
        }
        return $retorno;
    }
}

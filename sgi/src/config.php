<?php
	class conectar{
		var $servidor="mysql";
		var $base="c0310458_sgi";
		var $usuario="c0310458_sgi";
		var $pass="Rotaract2016";

		function getPass() {
		    return $this->pass;
		}

		function getBase(){
			return $this->base;
		}

		function getUsr() {
		    return $this->usuario;
		}

		function getServ() {
		    return $this->servidor;
		}
	}
?>

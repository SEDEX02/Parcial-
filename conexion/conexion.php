<?php
class conexion{
	private $link;

	function __construct(){
	}	
	
	public function conectar(){
		$this->link = new mysqli('%','admin-petronaburger','hClLpHrY/WVJyD)9','database-petronaburger');
		if ($this->link->connect_errno) {
			echo "Falló la conexión a MySQL: (" . $this->link->connect_errno . ") " . $this->link->connect_error;
		}
		return $this->link;
//		echo"conexion establecida<br>";
	}

	public function desconectar(){
		mysqli_close($this->link);
//		echo "conexion cerrada<br>";
	}
	
}
	


?>
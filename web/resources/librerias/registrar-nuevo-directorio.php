<?php 
include_once('../conexion/conexion.php'); 

$sqlemail = "SELECT *
FROM email WHERE email = '".$_POST["email"]."';";

$consultaemail = mysqli_query($conexion, $sqlemail);
if(mysqli_num_rows($consultaemail)>1)
{
 	echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El email ya esta registrado en el sistema </div></div>'));	
}else{
		$sql = "INSERT INTO email (email, nombre, observacion, fecha_create) VALUES 
		('".$_POST["email"]."', '".$_POST["nombres"]."', '".$_POST["mensaje"]."', '".date("Y-m-d h:i:s")."');";

		if (mysqli_query($conexion, $sql)) {
			
		echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Registrado Correctamente</div></div>'));
		}else{
			echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error al Registrar Intentelo de Nuevo</div></div>'));
		}
	}	
?>
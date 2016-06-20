<?php 
include_once('../conexion/conexion.php'); 

$sqlemail = "SELECT *
FROM email WHERE email ='$_POST[email]' AND id !='$_POST[id]'";
$consultaemail = mysqli_query($conexion, $sqlemail);

//print_r($consultaemail);
if(mysqli_num_rows($consultaemail) == 1)
{
 	echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-warning alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El nuevo Correo que trata de tomar está en uso </div></div>'));	
}else{

		$sql = "UPDATE email set observacion='$_POST[mensaje]',email='$_POST[email]',nombre='$_POST[nombres]' WHERE id='$_POST[id]'";		

		if (mysqli_query($conexion, $sql)) {
			
		echo json_encode(array('success' => true, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-success alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Actualizado Correctamente</div></div>'));
		}else{
			echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Error no se pudo actualizar Intentelo de Nuevo</div></div>'));
		}
	
	}
	
?>
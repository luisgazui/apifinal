<?php  
include_once('../conexion/conexion.php'); 
$sql = "DELETE FROM email WHERE id = ".$_POST['id'].";";
if(mysqli_query($conexion, $sql)){
  echo json_encode(array('success' => true, 'mensages' => '<div class="alert alert-success">Email Borrado Correctamente</div>'));
}
else{
  echo json_encode(array('success' => false, 'mensages' => '<div class="col-md-12 text-center"><div class="alert alert-danger alert-dismissible" role="alert">
		  			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>El Registro no se puede Borrar</div></div>'));
}
?>
 
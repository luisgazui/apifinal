<?php
// - Este archivo es su licencia para acceder al servicio remoto de envío de SMS, queda bajo su responsabilidad el uso que le de al mismo y queda estrictamente prohibida su distribución y/o comercialización.
//Estos son los parámetros de configuración, y deberán ser establecidos conforme las instrucciones del personal técnico de Auronix.

define('HOST','www.calixtaondemand.com');
define('PORT',80);
define('TIMEOUT',40);
define('CLIENTE',44323);
define('PASSWORD','6cbb788d4619f0cc0f73ed6dd0f33557de90bd59f8d1b28f9bf232a33402b2ca');
define('USER','secultcamp@secultsystempdc.com');

function checkValidSession(){
	//Esta función debe devolver TRUE cuando la sesión actual es válida para envío de SMS, y FALSE en cuanquier otro caso.
	return true;
}
?>

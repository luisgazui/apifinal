<?php
// - Este archivo es su licencia para acceder al servicio remoto de env�o de SMS, queda bajo su responsabilidad el uso que le de al mismo y queda estrictamente prohibida su distribuci�n y/o comercializaci�n.
//Estos son los par�metros de configuraci�n, y deber�n ser establecidos conforme las instrucciones del personal t�cnico de Auronix.

define('HOST','www.calixtaondemand.com');
define('PORT',80);
define('TIMEOUT',40);
define('CLIENTE',44323);
define('PASSWORD','6cbb788d4619f0cc0f73ed6dd0f33557de90bd59f8d1b28f9bf232a33402b2ca');
define('USER','secultcamp@secultsystempdc.com');

function checkValidSession(){
	//Esta funci�n debe devolver TRUE cuando la sesi�n actual es v�lida para env�o de SMS, y FALSE en cuanquier otro caso.
	return true;
}
?>

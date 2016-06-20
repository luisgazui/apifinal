<?php
/**************************************************************************
// libreria donde se define la logica completa de la api de envio de sms
/permite establecer como y donde se maneja toda la estructura logica de envio de sms
//si genera el error ssl copiar la intruccion
/curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false); en el direcctorio
/vendor\infobip\infobip-api-php-client\infobip\api
/linea  110
/***************************************************************************/


require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';
require_once __DIR__.'/../../vozapi/CalixtaAPI.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/voice', function () use ($app) {
    $session = new Session();
    $session->start();
    $_SESSION['app'] = '6';  
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    $initial_data = array(
		'mensaje' => '', 
		'cod_area'  => '',
		'telefono' => '', 

    );

    $calixta = new CalixtaAPI();
//var_dump(date("d/m/Y/H/i"));
    $form = $app['form.factory']->createBuilder('form', $initial_data);
	$form = $form->add('mensaje', 'textarea', array('required' => true));
	$form = $form->add('telefono', 'text', array('required' => true));

    $find_sql = "SELECT cod_area, pais FROM `paises`";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['cod_area']] = $value['pais'];
    }

	$form = $form->add('cod_area', 'choice', array('required' => true,
        "choices" => $datos 
        ));
    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
            $telfono= '+'.$data['cod_area'].$data['telefono'];
            $mensaje= $data['mensaje'];
            $fecha= date("d/m/Y/H/i");

             $idEnvio = $calixta->enviaMensajeVoz('5553711104', 'Mensaje de voz enviado para probar el componente php', '16/06/2016/10/45');
			//$idEnvio = $calixta->enviaMensajeVoz('+'.$data['cod_area'].$data['telefono'],   			$data['mensaje']);


			if ($idEnvio > 0) {
			    echo 'Mensaje enviado con éxito. (', $idEnvio, ')';
			  } else {
			    echo 'Ocurrió un error al enviar el mensaje (', $idEnvio, ')';
			  }

            $update_query = "INSERT INTO `bandeja_ent` (`remitente`, 
            									   `destinatario`,
            									   `estado`,
            									   `fecha_envio`,
            									   `mensaje`,
            									   `recurso`,
            									   `app_id`,
            									   `usuario_id`) 
            									   VALUES  (?, 
            									   			?,
            									   			?,
            									   			?,
            									   			?,
            									   			?,
            									   			?,
            									   			?)";
            $app['db']->executeUpdate($update_query, array($_SESSION['email'],
            												$data['cod_area'].$data['telefono'], 
            												'enviado',
            												date("Y-m-d"),
            												$data['mensaje'],
            												'',
            												$_SESSION['app'],
            												$_SESSION['id']));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Mensaje Enviado!',
                )
            );
            return $app->redirect($app['url_generator']->generate('voice'));

        }
    }



    return $app['twig']->render('voice/voice.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('voice');
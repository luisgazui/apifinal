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
require_once __DIR__.'/../../vozapi/SMS_CONFIG.php';

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
    $find_sql = "SELECT ncreditos FROM `creditosapp` WHERE app_id= '".$_SESSION['app']."'";
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    foreach ($rows_sql as  $value) {
       $costo = $value['ncreditos'];
    }
    $calixta = new CalixtaAPI();
//var_dump(date("d/m/Y/H/i"));
    $form = $app['form.factory']->createBuilder('form', $initial_data);
	$form = $form->add('mensaje', 'textarea', array('required' => true));
	$form = $form->add('telefono', 'text', array('required' => true));

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            try {
                        $data = $form->getData();
                        $telfono= $data['telefono'];
                        $mensaje= $data['mensaje'];
                        $fecha= date("d/m/Y/H/i");
            
                         $idEnvio = $calixta->enviaMensajeVoz($telfono, $mensaje, $fecha);
                        //$idEnvio = $calixta->enviaMensajeVoz('+'.$data['cod_area'].$data['telefono'],               $data['mensaje']);
            
            
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
                                                                        $data['telefono'], 
                                                                        'enviado',
                                                                        date("Y-m-d"),
                                                                        $data['mensaje'],
                                                                        '',
                                                                        $_SESSION['app'],
                                                                        $_SESSION['id']));   
                            $update_query = "INSERT INTO `cuenta` (`egreso`, 
                                                                   `usuario_id`) 
                                                                  VALUES 
                                                                  (?, ?)";
            
                            $app['db']->executeUpdate($update_query, array($costo, 
                                                                           $_SESSION['id'])); 
                                $find_sql = "SELECT
                                            Sum(a.ingreso) - Sum(a.egreso) AS total
                                            FROM
                                            cuenta AS a
                                            WHERE
                                            a.usuario_id = '".$_SESSION['id']."'";
                                $rows_sql = $app['db']->fetchAll($find_sql, array());
                                
                                foreach ($rows_sql as  $value) {
                                   $_SESSION['total'] = $value['total'];
                                   $session->set('total', $value['total']);
                                }                                                                        
                                $session->save();
                        if ($idEnvio > 0) {
                             $app['session']->getFlashBag()->add(
                                'success',
                            array(
                                'message' => 'Mensaje Enviado! '.$idEnvio,
                            )
                        );
                          } else {
                             $app['session']->getFlashBag()->add(
                                    'danger',
                                array(
                                    'message' => 'Ocurrio Un error! '.$idEnvio,
                            )
                        );
                          }}
            catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
            }                          
/*            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'Mensaje Enviado!',
                )
            );*/
            return $app->redirect($app['url_generator']->generate('voice'));

        }
    }



    return $app['twig']->render('voice/voice.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('voice');


$app->match('/voice/masivos', function () use ($app) {
    $session = new Session();
    $session->start();
    $_SESSION['app'] = '6';  
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    $initial_data = array(
        'mensaje' => '', 
        'cod_area'  => '', 

    );
    $find_sql = "SELECT ncreditos FROM `creditosapp` WHERE app_id= '".$_SESSION['app']."'";
    $rows_sql = $app['db']->fetchAll($find_sql, array());
    
    foreach ($rows_sql as  $value) {
       $costo = $value['ncreditos'];
    }

    $carpeta    = __DIR__. "/tmp";
    $form = $app['form.factory']->createBuilder('form', $initial_data);
    $form = $form->add('mensaje', 'textarea', array('required' => true));
    $form = $form->add('telefono', 'file', array('required' => true,
                                                "attr" => array(
                                                "accept" => ".csv",
                                                )));

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
          try{
                      $request = $app['request'];
                      $data = $form->getData();
                      $file=$data['telefono']->openFile('r');
                      while (!$file->eof()) { 
                          $file->next();
                          $line = $file->current(); 
                          if ((trim($line) != "") && (trim($line) != 'telefono')) {
                              $telfono= $line;
                              $mensaje= $data['mensaje'];
                              $fecha= date("d/m/Y/H/i");
          
                              $idEnvio = $calixta->enviaMensajeVoz($telfono, $mensaje, $fecha);
          
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
                                                                                  $line, 
                                                                                  'enviado',
                                                                                  date("Y-m-d"),
                                                                                  $mensaje,
                                                                                  '',
                                                                                  $_SESSION['app'],
                                                                                  $_SESSION['id'])); 
                              $update_query = "INSERT INTO `cuenta` (`egreso`, 
                                                                     `usuario_id`) 
                                                                    VALUES 
                                                                    (?, ?)";
          
                              $app['db']->executeUpdate($update_query, array($costo, 
                                                                             $_SESSION['id'])); 
                                  $find_sql = "SELECT
                                              Sum(a.ingreso) - Sum(a.egreso) AS total
                                              FROM
                                              cuenta AS a
                                              WHERE
                                              a.usuario_id = '".$_SESSION['id']."'";
                                  $rows_sql = $app['db']->fetchAll($find_sql, array());
                                  
                                  foreach ($rows_sql as  $value) {
                                     $_SESSION['total'] = $value['total'];
                                     $session->set('total', $value['total']);
                                  }                                                                                
                                  $session->save();
                          }
                      }
                      $app['session']->getFlashBag()->add(
                          'success',
                          array(
                              'message' => 'Mensaje Enviado!',
                          )
                      );}
            catch (Exception $e) {
                $app['session']->getFlashBag()->add(
                    'danger',
                    array(
                        'message' => 'Revise sus datos!',
                    )
                );
            }                      
            return $app->redirect($app['url_generator']->generate('vmasivos'));

        }
    }



    return $app['twig']->render('voice/masivos.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('vmasivos');
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

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\File\UploadedFile;


$app->match('/sms', function () use ($app) {
    
    $session = new Session();
    $session->start();

    $_SESSION['app'] = '3';  
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }

    $client = new infobip\api\client\SendSingleTextualSms(new infobip\api\configuration\BasicAuthConfiguration
	('GonzalezL', 'Infobip1'));
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

    $form = $app['form.factory']->createBuilder('form', $initial_data);
	$form = $form->add('mensaje', 'textarea', array('required' => true));
	$form = $form->add('telefono', 'text', array('required' => true));

    $find_sql = "SELECT codigo, pais FROM `paises` order by pais";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['codigo']] = $value['pais'];
    }

	$form = $form->add('cod_area', 'choice', array('required' => true,
        "choices" => $datos 
        ));
    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
          try{
                      $data = $form->getData();
          
                      $requestBody = new infobip\api\model\sms\mt\send\textual\SMSTextualRequest();
                      $requestBody->setFrom('LauroGonzalez');
                      $requestBody->setTo($data['cod_area'].$data['telefono']);
                      $requestBody->setText($data['mensaje']);
                      
                      $response = $client->execute($requestBody);
                      var_dump($response);
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
                      $app['db']->executeUpdate($update_query, array('luis',
                                                                      $data['cod_area'].$data['telefono'], 
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
            return $app->redirect($app['url_generator']->generate('enviar'));

        }
    }



    return $app['twig']->render('sms/enviar.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('enviar');


$app->match('/sms/masivos', function () use ($app) {
    $session = new Session();
    $session->start();
    
    $_SESSION['app'] = '3';  
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    $client = new infobip\api\client\SendSingleTextualSms(new infobip\api\configuration\BasicAuthConfiguration
    ('GonzalezL', 'Infobip1'));
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

    $find_sql = "SELECT codigo, pais FROM `paises` order by pais";
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    $datos = array();
    foreach ($rows_sql as  $value) {
       $datos[$value['codigo']] = $value['pais'];
    }


    $form = $form->add('cod_area', 'choice', array('required' => true,
        "choices" => $datos 
        ));
    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
          try {
                      $request = $app['request'];
                      $data = $form->getData();
                      $file=$data['telefono']->openFile('r');
                      while (!$file->eof()) { 
                      $file->next();
                      $line = $file->current(); 
                      if ((trim($line) != "") && (trim($line) != 'telefono')) {
          
          
                      //$ext=$file->guessExtension();
          
                      //$file_name=time().".".$ext;
                      //$file->move($carpeta, $file_name);
                      //$file =  $request->files->get('telefono');
                      //$filename = $file->getClientOriginalName();
                      //$NewFilename= $filename.$fecha;
                      //$file->move($carpeta, $filename);
                      //$NewFilename = "tmp_excel".$fecha;
          
          
                      //$excel      = $fecha."-".$_FILES['telefono']['name'];
                      $requestBody = new infobip\api\model\sms\mt\send\textual\SMSTextualRequest();
                      $requestBody->setFrom('LauroGonzalez');
                      $requestBody->setTo($data['cod_area'].$line);
                      $requestBody->setText($data['mensaje']);
                      $response = $client->execute($requestBody);      
          
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
                              $app['db']->executeUpdate($update_query, array('luis',
                                                                              $data['cod_area'].$line, 
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
            return $app->redirect($app['url_generator']->generate('masivos'));

        }
    }



    return $app['twig']->render('sms/masivos.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('masivos');


$app->match('/salir', function () use ($app) {

    $session = new Session();
    $session->start();  
    if (isset($_SESSION['id'])){
        session_destroy();
        return $app->redirect($app['url_generator']->generate('section'));
    }
    return $app->redirect($app['url_generator']->generate('section'));
        
})
->bind('salir');
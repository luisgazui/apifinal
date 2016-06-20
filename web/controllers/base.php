<?php

/*
 * inicio de sistema 
 *
 * se describen todos los controladores que componen
 * el sistema Bigsender.com
 *
 * Tambien contiene el inicio de secion del mismo
 */

use Symfony\Component\HttpFoundation\Session\Session;

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';

require_once __DIR__.'/bancos/index.php';
require_once __DIR__.'/bandeja_ent/index.php';
require_once __DIR__.'/creditos/index.php';
require_once __DIR__.'/creditosapp/index.php';
require_once __DIR__.'/cuenta/index.php';
require_once __DIR__.'/direccion_app/index.php';
require_once __DIR__.'/directorio/index.php';
require_once __DIR__.'/monedas/index.php';
require_once __DIR__.'/pagos/index.php';
require_once __DIR__.'/paises/index.php';
require_once __DIR__.'/usuarios/index.php';
require_once __DIR__.'/sms/index.php';
require_once __DIR__.'/voice/index.php';
require_once __DIR__.'/login/index.php';
require_once __DIR__.'/cuentas_bancos/index.php';



$app->match('/', function () use ($app) {
	$initial_data = array(
		'email' => '', 
		'password' => '', 
    );
	session_destroy();
    $form = $app['form.factory']->createBuilder('form', $initial_data);

	$form = $form->add('email', 'text', array('required' => true));
	$form = $form->add('password', 'password', array('required' => true));

    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];
            $findexternal_sql = "SELECT `id`, 
            							`email`,
            							`nombres`,
            							`password`,
            							`is_superadmin` 
            					FROM `usuarios`
            					WHERE `email` = '$email'";

			$findexternal_rows = $app['db']->fetchAll($findexternal_sql, array());
			foreach($findexternal_rows as $findexternal_row){

            	$pass= hash_equals($findexternal_row['password'],crypt($password, 'BigSender##%%&&//'));
	    		$options[$findexternal_row['id']] = $findexternal_row['email'];
	    		if ($pass){
	    			$session = new Session();
					$session->start();
					$uid=$findexternal_row['id'];
					$session->set('id', $findexternal_row['id']);
					$_SESSION['id'] = $findexternal_row['id'];
					$_SESSION['email'] = $findexternal_row['email'];
					$_SESSION['name'] = $findexternal_row['nombres'];
					$_SESSION['admin'] = $findexternal_row['is_superadmin'];
				    $find_sql = "SELECT
								Sum(a.ingreso) - Sum(a.egreso) AS total
								FROM
								cuenta AS a
								WHERE
								a.usuario_id = '". $_SESSION['id'] ."'";
				    $rows_sql = $app['db']->fetchAll($find_sql, array());
				    
				    foreach ($rows_sql as  $value) {
				       $_SESSION['total'] = $value['total'];
				    }	
				    $session->save();				
					//$twig->addGlobal("session", $_SESSION);
					//$app->view->setData('sessionid', $_SESSION['id']);
	    		}
			}
			if (isset($_SESSION['id'])){
				if ($_SESSION['admin'] == 0){
				    return $app->redirect($app['url_generator']->generate('enviar'));
				}
				else{
					return $app->redirect($app['url_generator']->generate('usuarios_list'));
				}

			}
			else{
				$app['session']->getFlashBag()->add(
		                'danger',
		                array(
		                    'message' => 'Usuario o password incorrectos',
		                ));

					//var_dump('nombres');
		            return $app->redirect($app['url_generator']->generate('section'));
			}

		}
	}


    return $app['twig']->render('login/section.html.twig', array(
    	"form" => $form->createView()
    ));
       
})
->bind('section');


$app->run();
<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/usuarios/list', function (Symfony\Component\HttpFoundation\Request $request) use ($app) {  
    $start = 0;
    $vars = $request->query->all();
    $qsStart = (int)$vars["start"];
    $search = $vars["search"];
    $order = $vars["order"];
    $columns = $vars["columns"];
    $qsLength = (int)$vars["length"];    
    
    if($qsStart) {
        $start = $qsStart;
    }    
	
    $index = $start;   
    $rowsPerPage = $qsLength;
       
    $rows = array();
    
    $searchValue = $search['value'];
    $orderValue = $order[0];
    
    $orderClause = "";
    if($orderValue) {
        $orderClause = " ORDER BY ". $columns[(int)$orderValue['column']]['data'] . " " . $orderValue['dir'];
    }
    
    $table_columns = array(
		'id', 
		'nombres', 
		'apellidos', 
		'email', 
		'password', 

    );
    
    $table_columns_type = array(
		'bigint(20)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(255)', 
		'varchar(60)', 

    );    
    
    $whereClause = "";
    
    $i = 0;
    foreach($table_columns as $col){
        
        if ($i == 0) {
           $whereClause = " WHERE";
        }
        
        if ($i > 0) {
            $whereClause =  $whereClause . " OR"; 
        }
        
        $whereClause =  $whereClause . " " . $col . " LIKE '%". $searchValue ."%'";
        
        $i = $i + 1;
    }
    
    $recordsTotal = $app['db']->executeQuery("SELECT * FROM `usuarios`" . $whereClause . $orderClause)->rowCount();
    
    $find_sql = "SELECT * FROM `usuarios`". $whereClause . $orderClause . " LIMIT ". $index . "," . $rowsPerPage;
    $rows_sql = $app['db']->fetchAll($find_sql, array());

    foreach($rows_sql as $row_key => $row_sql){
        for($i = 0; $i < count($table_columns); $i++){

		if( $table_columns_type[$i] != "blob") {
				$rows[$row_key][$table_columns[$i]] = $row_sql[$table_columns[$i]];
		} else {				if( !$row_sql[$table_columns[$i]] ) {
						$rows[$row_key][$table_columns[$i]] = "0 Kb.";
				} else {
						$rows[$row_key][$table_columns[$i]] = " <a target='__blank' href='menu/download?id=" . $row_sql[$table_columns[0]];
						$rows[$row_key][$table_columns[$i]] .= "&fldname=" . $table_columns[$i];
						$rows[$row_key][$table_columns[$i]] .= "&idfld=" . $table_columns[0];
						$rows[$row_key][$table_columns[$i]] .= "'>";
						$rows[$row_key][$table_columns[$i]] .= number_format(strlen($row_sql[$table_columns[$i]]) / 1024, 2) . " Kb.";
						$rows[$row_key][$table_columns[$i]] .= "</a>";
				}
		}

        }
    }    
    
    $queryData = new queryData();
    $queryData->start = $start;
    $queryData->recordsTotal = $recordsTotal;
    $queryData->recordsFiltered = $recordsTotal;
    $queryData->data = $rows;
    
    return new Symfony\Component\HttpFoundation\Response(json_encode($queryData), 200);
});




/* Download blob img */
$app->match('/usuarios/download', function (Symfony\Component\HttpFoundation\Request $request) use ($app) { 
    
    // menu
    $rowid = $request->get('id');
    $idfldname = $request->get('idfld');
    $fieldname = $request->get('fldname');
    
    if( !$rowid || !$fieldname ) die("Invalid data");
    
    $find_sql = "SELECT " . $fieldname . " FROM " . usuarios . " WHERE ".$idfldname." = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($rowid));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('menu_list'));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: image/jpeg');
    header("Content-length: ".strlen( $row_sql[$fieldname] ));
    header('Expires: 0');
    header('Cache-Control: public');
    header('Pragma: public');
    ob_clean();    
    echo $row_sql[$fieldname];
    exit();
   
    
});



$app->match('/usuarios', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }    
	$table_columns = array(
		'id', 
		'nombres', 
		'apellidos', 
		'email', 
		'password', 

    );

    $primary_key = "id";	

    return $app['twig']->render('usuarios/list.html.twig', array(
    	"table_columns" => $table_columns,
        "primary_key" => $primary_key
    ));
        
})
->bind('usuarios_list');



$app->match('/usuarios/create', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }    
    $initial_data = array(
		'nombres' => '', 
		'apellidos' => '', 
		'email' => '', 
		'password' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('nombres', 'text', array('required' => true));
	$form = $form->add('apellidos', 'text', array('required' => true));
	$form = $form->add('email', 'text', array('required' => true));
	$form = $form->add('password', 'password', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
            $hashed_password = crypt($data['password'],'BigSender##%%&&//');
            $update_query = "INSERT INTO `usuarios` (`id`, `nombres`, `apellidos`, `email`, `password`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['nombres'], $data['apellidos'], $data['email'], $hashed_password));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_list'));

        }
    }

    return $app['twig']->render('usuarios/create.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('usuarios_create');


$app->match('/usuarios/registro', function () use ($app) {
    
    $initial_data = array(
        'nombres' => '', 
        'apellidos' => '', 
        'email' => '', 
        'password' => '', 

    );

    $form = $app['form.factory']->createBuilder('form', $initial_data);


    $form = $form->add('nombres', 'text', array('required' => true));
    $form = $form->add('apellidos', 'text', array('required' => true));
    $form = $form->add('email', 'text', array('required' => true));
    $form = $form->add('password', 'password', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();
            $hashed_password = crypt($data['password'],'BigSender##%%&&//');
            $update_query = "INSERT INTO `usuarios` (`id`, `nombres`, `apellidos`, `email`, `password`) VALUES (?, ?, ?, ?, ?)";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['nombres'], $data['apellidos'], $data['email'], $hashed_password));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios created!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_list'));

        }
    }

    return $app['twig']->render('usuarios/register.html.twig', array(
        "form" => $form->createView()
    ));
        
})
->bind('usuarios_register');



$app->match('/usuarios/edit/{id}', function ($id) use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }
    $find_sql = "SELECT * FROM `usuarios` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('usuarios_list'));
    }

    
    $initial_data = array(
		'id' => $row_sql['id'], 
		'nombres' => $row_sql['nombres'], 
		'apellidos' => $row_sql['apellidos'], 
		'email' => $row_sql['email'], 
		'password' => $row_sql['password'], 
    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);


	$form = $form->add('id', 'text', array('required' => true));
	$form = $form->add('nombres', 'text', array('required' => true));
	$form = $form->add('apellidos', 'text', array('required' => true));
	$form = $form->add('email', 'text', array('required' => true));
	$form = $form->add('password', 'text', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `usuarios` SET `id` = ?, `nombres` = ?, `apellidos` = ?, `email` = ?, `password` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['nombres'], $data['apellidos'], $data['email'], $data['password'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('usuarios/edit.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('usuarios_edit');

$app->match('/usuarios/profile', function () use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    $find_sql = "SELECT * FROM `usuarios` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($_SESSION['id']));

    if(!$row_sql){
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );        
        return $app->redirect($app['url_generator']->generate('usuarios_list'));
    }

    
    $initial_data = array(
        'nombres' => $row_sql['nombres'], 
        'apellidos' => $row_sql['apellidos'], 
        'email' => $row_sql['email'], 
        'password' => $row_sql['password'], 
    );


    $form = $app['form.factory']->createBuilder('form', $initial_data);

    $form = $form->add('nombres', 'text', array('required' => true));
    $form = $form->add('apellidos', 'text', array('required' => true));
    $form = $form->add('email', 'text', array('required' => true));
    $form = $form->add('password', 'password', array('required' => true));


    $form = $form->getForm();

    if("POST" == $app['request']->getMethod()){

        $form->handleRequest($app["request"]);

        if ($form->isValid()) {
            $data = $form->getData();

            $update_query = "UPDATE `usuarios` SET `id` = ?, `nombres` = ?, `apellidos` = ?, `email` = ?, `password` = ? WHERE `id` = ?";
            $app['db']->executeUpdate($update_query, array($data['id'], $data['nombres'], $data['apellidos'], $data['email'], $data['password'], $id));            


            $app['session']->getFlashBag()->add(
                'success',
                array(
                    'message' => 'usuarios edited!',
                )
            );
            return $app->redirect($app['url_generator']->generate('usuarios_edit', array("id" => $id)));

        }
    }

    return $app['twig']->render('usuarios/profile.html.twig', array(
        "form" => $form->createView(),
        "id" => $id
    ));
        
})
->bind('profile_edit');


$app->match('/usuarios/delete/{id}', function ($id) use ($app) {
    $session = new Session();
    $session->start();
    if (!isset($_SESSION['id'])){
        return $app->redirect($app['url_generator']->generate('section'));
    }
    if (isset($_SESSION['admin'])) {
        if ($_SESSION['admin']==0) {
            return $app->redirect($app['url_generator']->generate('enviar'));
        }
    }
    $find_sql = "SELECT * FROM `usuarios` WHERE `id` = ?";
    $row_sql = $app['db']->fetchAssoc($find_sql, array($id));

    if($row_sql){
        $delete_query = "DELETE FROM `usuarios` WHERE `id` = ?";
        $app['db']->executeUpdate($delete_query, array($id));

        $app['session']->getFlashBag()->add(
            'success',
            array(
                'message' => 'usuarios deleted!',
            )
        );
    }
    else{
        $app['session']->getFlashBag()->add(
            'danger',
            array(
                'message' => 'Registro No encontrado!',
            )
        );  
    }

    return $app->redirect($app['url_generator']->generate('usuarios_list'));

})
->bind('usuarios_delete');







<?php

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../../src/app.php';

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Session\Session;

$app->match('/login', function () use ($app) {



    return $app['twig']->render('login/section.html.twig', array(
    ));
        
})
->bind('section');


?>
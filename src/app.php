<?php

use Silex\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

$app = new Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'         => __DIR__.'/../views',
    'twig.options'      => array('cache' => __DIR__.'/../cache/twig'),
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Predis\Silex\PredisServiceProvider());
$app->register(new Igorw\Trashbin\Provider());

$app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
    $twig->addGlobal('base_path', $app['request']->getBasePath());
    $twig->addGlobal('index_url', $app['url_generator']->generate('homepage'));
    $twig->addGlobal('create_url', $app['url_generator']->generate('create'));
    $twig->addGlobal('languages', $app['app.languages']);

    return $twig;
}));

$app->get('/', function (Request $request) use ($app) {
    $parentPasteId = $request->get('parent');

    $parentPaste = null;
    if ($parentPasteId) {
        $parentPaste = $app['app.storage']->get($parentPasteId);
    }

    return $app['twig']->render('index.html.twig', array(
        'paste'     => $parentPaste,
    ));
})
->bind('homepage');

$app->post('/', function (Request $request) use ($app) {
    list($id, $paste) = $app['app.parser']->createPasteFromRequest($request);

    $errors = $app['app.validator']->validate($paste);
    if ($errors) {
        $page = $app['twig']->render('index.html.twig', array(
            'errors'    => $errors,
            'paste'     => $paste,
        ));

        return new Response($page, 400);
    }
    $app['app.storage']->set($id, $paste);

    return $app->redirect($app['url_generator']->generate('view', array('id' => $id)));
})
->bind('create');

$app->get('/about', function (Request $request) use ($app) {
    return $app['twig']->render('about.html.twig');
});

$app->get('/{id}', function (Request $request, $id) use ($app) {
    $paste = $app['app.storage']->get($id);

    if (!$paste) {
        $app->abort(404, 'paste not found');
    }

    return $app['twig']->render('view.html.twig', array(
        'copy_url'	=> $app['url_generator']->generate('homepage', array('parent' => $id)),
        'paste'		=> $paste,
    ));
})
->bind('view')
->assert('id', '[0-9a-f]{8}');

$app->get('/{id}/delete', function (Request $request, $id) use ($app) {
    $paste = $app['app.storage']->get($id);

    if (!$paste) {
        $app->abort(404, 'paste not found');
    }

    $app['app.storage']->delete($id);
    return $app->redirect($app['url_generator']->generate('homepage'));
})
->bind('delete')
->assert('id', '[0-9a-f]{8}');

$app->get('/list', function (Request $request) use ($app) {
    $keys = $app['app.storage']->all();
    $pastes = array();

    if (!$keys) {
        $app->abort(404, 'paste not found');
    }

    foreach($keys as $key){
	array_push($pastes,
           $app['app.storage']->get($key)
	);
    }

    $sortArray = array(); 

    foreach($pastes as $paste){ 
        foreach($paste as $key=>$value){ 
            if(!isset($sortArray[$key])){ 
                $sortArray[$key] = array(); 
            } 
            $sortArray[$key][] = $value; 
        } 
    } 

    $orderby = "created_at";

    array_multisort($sortArray[$orderby],SORT_DESC,$pastes);

    return $app['twig']->render('list.html.twig', array(
        'pastes'		=> $pastes
    ));
})
->bind('list');

$app->error(function (Exception $e) use ($app) {
    if ($app['debug']) {
        return;
    }

    $code = ($e instanceof HttpException) ? $e->getStatusCode() : 500;

    return new Response($app['twig']->render('error.html.twig', array(
        'message'	=> $e->getMessage(),
    )), $code);
});

return $app;

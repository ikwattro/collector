<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

$app->get('/collect', function(Request $request) use ($app) {
    $response = new JsonResponse();
    $ua = $request->query->get('ua');
    $ip = $request->query->get('uip');
    print_r($request->getClientIp());
    $app['monolog']->addInfo(sprintf('Received collect info from UA "%s" and IP "%s"', (string) $ua, $request->getClientIp()));
    $app['monolog']->addInfo(sprintf('Request content is %s', json_encode($request->query->all())));
    return $response;
})
->bind('collect');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});

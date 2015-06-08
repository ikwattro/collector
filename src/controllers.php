<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Yaml\Parser;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig', array());
})
->bind('homepage')
;

$app->get('/collect', function(Request $request) use ($app) {
    $awsConfig = file_get_contents(__DIR__.'/../config/aws.yml');
    $yaml = new Parser();
    $config = $yaml->parse($awsConfig);
    $response = new JsonResponse();
    $ua = $request->query->get('cid');
    $v = $request->get('v');
    $client = $app['sqs'];
    $dt = new \DateTime("NOW");
    $t = $dt->getTimestamp() * 1000;
    $client->sendMessage(array(
        'QueueUrl' => $config['queue']['url'],
        'MessageBody' => $ua . '|' . $v . '|' . $request->getClientIp() . '|' . $t,
    ));
    $app['monolog']->addInfo(sprintf('Received collect info from CID "%s" using VERSION "%s" and IP "%s"', $ua, $v, $request->getClientIp()));
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

<?php

use Silex\Provider\MonologServiceProvider;
use Aws\Sqs\SqsClient;
use Symfony\Component\Yaml\Parser;

// configure your app for the production environment

$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/logs/app_prod.log',
));

$awsConfig = file_get_contents(__DIR__.'/aws.yml');
$yaml = new Parser();
$config = $yaml->parse($awsConfig);

$sqs = SqsClient::factory(array(
		'region' => 'eu-west-1',
		'version' => 'latest',
		'credentials' => [
			'key' => $config['credentials']['key'],
			'secret' => $config['credentials']['secret']
		]
	));
$app['sqs'] = function() use ($sqs) {
	return $sqs;

};


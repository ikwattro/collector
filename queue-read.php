<?php

require_once(__DIR__.'/vendor/autoload.php');

use Aws\Sqs\SqsClient;
use Symfony\Component\Yaml\Parser;
use Geocoder\HttpAdapter\GuzzleHttpAdapter;
use Geocoder\Provider\FreeGeoIpProvider;

$aws = file_get_contents(__DIR__.'/config/aws.yml');
$yaml = new Parser();
$config = $yaml->parse($aws);

$sqs = SqsClient::factory(array(
    'region' => 'eu-west-1',
    'version' => 'latest',
    'credentials' => [
        'key' => $config['credentials']['key'],
        'secret' => $config['credentials']['secret']
    ]
));

$g = new GuzzleHttpAdapter();
$geocoder = new FreeGeoIpProvider($g);

$hasMessages = true;
while ($hasMessages) {
    $result = $sqs->receiveMessage(array(
        'QueueUrl' => $config['queue']['url'],
        //'MaxNumberOfMessages' => 10
    ));

    $messages = $result->get('Messages');
    if (count($messages) === 0) {
        $hasMessages = false;
        break;
    }

    foreach ($messages as $message) {
        $body = $message['Body'];
        $handle = $message['ReceiptHandle'];
        $sqs->deleteMessage(array(
            'QueueUrl' => $config['queue']['url'],
            'ReceiptHandle' => $handle
        ));
        $v = list($uid, $version, $ip, $time) = explode('|', $body);
        print_r($ip);
        print_r($geocoder->getGeocodedData($ip));
        echo "\n";
    }
}
<?php

// include this file or add your autoloader


use SharkyDog\BLE;
use SharkyDog\MessageBroker as MSGB;

function pn($d) {
  print "*** Ex.03: ".print_r($d,true)."\n";
}

// helper to print messages from broker
function pmsg($app,$topic,$msg,$from) {
  pn($app.': from '.$from.' on '.$topic.': '.$msg);
}

// If sharkydog/message-broker package is installed,
// the parser can be attached to a local message broker.
//
// The parser will subscribe to the given advertisment topic
// and publish the parsed message json encoded to a different topic.
//
// Many scanners can publish advertisments to the same broker
// and all could be converted (parsed) in one place.


$adv = BLE\Advertisment::import([
  'addr' => 'AA:BB:CC:DD:EE:FF',
  'atyp' => BLE\Advertisment::ADDR_PUB,
  'etyp' => BLE\Advertisment::ADV_NONCONN_IND,
  'rssi' => -50,
  'data' => [
    '09' => [bin2hex('Dummy')], // Complete Local Name
    'ff' => ['0201aabbcc']      // Manufacturer data: 0102 => aabbcc
  ]
]);

$parser = new BLE\AdvertismentParser;
$msgb = new MSGB\Server;


// parse scanners1/+/advertisment messages
// and publish json encoded array
// on advparser/scanners1/parsedadv
$parser->addToBroker(
  $msgb,
  'scanners1/+/advertisment',
  'advparser/scanners1/parsedadv'
);


// dummy scanner
$msgb_scanner1 = new MSGB\Local\Client('scanner1', $msgb);
// listener for scanner messages
$msgb_viewer1 = new MSGB\Local\Client('viewer1', $msgb);
// listener for parser messages
$msgb_viewer2 = new MSGB\Local\Client('viewer2', $msgb);

$msgb_viewer1->on('message', function($topic,$msg,$from) {
  pmsg('viewer1',$topic,'',$from);
  pn(BLE\Advertisment::import($msg));
});

$msgb_viewer2->on('message', function($topic,$msg,$from) {
  pmsg('viewer2',$topic,'',$from);
  pn(json_decode($msg,true));
});

$msgb_viewer1->send('broker/subscribe', '*/advertisment');
$msgb_viewer2->send('broker/subscribe', '*/parsedadv');

$msgb_scanner1->send(
  'scanners1/scanner1/advertisment',
  json_encode($adv->export())
);

<?php

// include this file or add your autoloader


use SharkyDog\BLE\Advertisment;
use SharkyDog\BLE\Format\iBeacon;

function pn($d) {
  print "*** Ex.07: ".print_r($d,true)."\n";
}


// dummy advertisment
$advarr = [
  'addr' => 'AA:BB:CC:DD:EE:FF',
  'atyp' => Advertisment::ADDR_PUB,
  'etyp' => Advertisment::ADV_NONCONN_IND,
  'rssi' => -50,
  'data' => []
];
//pn(Advertisment::import($advarr));


///--- iBeacon ---///
// https://developer.apple.com/ibeacon

//generate new UUIDv4
// https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
$uuidbin = random_bytes(16);
$uuidbin[6] = chr(ord($uuidbin[6]) & 0x0F | 0x40);
$uuidbin[8] = chr(ord($uuidbin[8]) & 0x3F | 0x80);

// or use your own
//$uuid = '8400d64b-d547-4ff1-80ba-f8915862c542';
//$uuidbin = pack('H*', str_replace('-','',$uuid));

$major = 1;
$minor = 2;
$txpower = -10;

// 0x004C - Apple, Inc.
$mandt = bin2hex(pack('vCCa*nnc',0x004C,0x02,0x15,$uuidbin,$major,$minor,$txpower));

// fill our dummy advertisment
$advarr['data'] = [
  'ff' => [$mandt] // Manufacturer data
];
$adv = Advertisment::import($advarr);
pn($adv);

// parse
// returns SharkyDog\BLE\Format\iBeacon object or null
if(!($ibcn = iBeacon::parseAdvData($adv->data))) {
  pn('Not an iBeacon');
  exit;
}
pn($ibcn);

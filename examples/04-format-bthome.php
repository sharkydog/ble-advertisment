<?php

// include this file or add your autoloader


use SharkyDog\BLE\Advertisment;
use SharkyDog\BLE\Format\BTHome;

function pn($d) {
  print "*** Ex.04: ".print_r($d,true)."\n";
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


///--- BTHome v2 ---///
// https://bthome.io/format

// only unencrypted devices
// sample bthome service data (hex), AD type 0x16
// 16bit uuid 0xFCD2, Allterco Robotics ltd (Shelly)
$svc16d = 'd2fc440045016405bc02002d003f0000f00202f100090001';

$advarr['data'] = [
  '16' => [$svc16d] // Service Data - 16-bit UUID
];
$adv = Advertisment::import($advarr);
pn($adv);

// remove the service uuid
// single service data AD at index 0
$bin = $adv->data->getBin('16');
$bin = substr($bin, 2);

// parse, returns stdClass or null
$arr = BTHome::parseBin($bin);
//pn($arr);

// finds BTHome service data in AdvData object and parses it
// returns stdClass or null
$arr = BTHome::parseAdvData($adv->data);
pn($arr);

// extend SharkyDog\BLE\Format\BTHome class
// to add more sensors
// you will also need to update BTHome::$len array

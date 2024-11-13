<?php

// include this file or add your autoloader


use SharkyDog\BLE\Advertisment;
use SharkyDog\BLE\Format\MikroTik;

function pn($d) {
  print "*** Ex.06: ".print_r($d,true)."\n";
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


///--- MikroTik ---///
// https://help.mikrotik.com/docs/spaces/UM/pages/105742533/MikroTik+Tag+advertisement+formats

// only unencrypted devices
// sample manufacturer data (hex) from TG-BT5-IN, AD type 0xff
// uuid 0x094F, Limited Liability Company ”Mikrotikls”
$mandt = '4f090100d459e1fe250006ff0080d3008e010056';
// same but with added temperature -12.94°C, reed trigger and x,z impact triggers
$mandt = '4f090100d459e1fe250006ff10f3d3008e012956';

// fill our dummy advertisment
$advarr['data'] = [
  'ff' => [$mandt] // Manufacturer data
];
$adv = Advertisment::import($advarr);
pn($adv);

// parse
// returns SharkyDog\BLE\Format\MikroTik object or null
if(!($mkt = MikroTik::parseAdvData($adv->data))) {
  pn('Not a MikroTik tag');
  exit;
}
pn($mkt);

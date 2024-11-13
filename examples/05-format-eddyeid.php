<?php

// include this file or add your autoloader


use SharkyDog\BLE\Advertisment;
use SharkyDog\BLE\Format\EddystoneEID;

function pn($d) {
  print "*** Ex.05: ".print_r($d,true)."\n";
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


///--- Eddystone-EID ---///
// https://github.com/google/eddystone/tree/master/eddystone-eid


// random 128bit key
$key = random_bytes(16);

// create new beacon
// binary key, exponent, number of past EIDs to match
// when this object is printed with var_dump() or print_r()
// the key will be shown encoded as hex and base64
$eddyeid = new EddystoneEID($key, 9, 3);
//pn($eddyeid);

// generate EID for the current timestamp
$now = time();
// EID in hex
$eid = $eddyeid->genEID($now);

// service data (hex), AD type 0x16
// 16bit uuid 0xFEAA, Google LLC
$svc16d  = 'aafe'; // 0xFEAA
$svc16d .= '30';   // 0x30, EID frame type
$svc16d .= 'f6';   // 0xF6, Tx power -10
$svc16d .= $eid;

// or if you prefer hollywood hacking
$svc16d = bin2hex(pack('vCc',0xFEAA,0x30,-10)).$eid;

// fill our dummy advertisment
$advarr['data'] = [
  '16' => [$svc16d] // Service Data - 16-bit UUID
];
$adv = Advertisment::import($advarr);
pn($adv);


// parse
// returns null or stdClass with two properties
//  - eid - found EID in hex
//  - txp - Tx power, usually static
//          and could be used as a beacon identifier
//          range -128 - 127
//
if(!($data = EddystoneEID::parseAdvData($adv->data))) {
  pn('Not an Eddystone-EID beacon');
  exit;
}

// match
// needs EID in hex and a timestamp, Tx power is optional
// returns
//   0 if no match,
//   1 if current EID matched
//   2 if first past EID matched
//   etc
// can also be retrieved later
//
$matched = $eddyeid->match($data->eid, $now, $data->txp);
pn($eddyeid);

// result from last match()
$matched = $eddyeid->getMatch();

// counter for a given timestamp
// seconds until EID rotation
$counter = $eddyeid->getCounter($now);

// last set Tx power, could be null
$txpower = $eddyeid->getTxp();

pn([
  'matched' => $matched,
  'counter' => $counter,
  'txpower' => $txpower
]);

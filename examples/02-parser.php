<?php

// include this file or add your autoloader


use SharkyDog\BLE;

function pn($d) {
  print "*** Ex.02: ".print_r($d,true)."\n";
}

// First some dummy advertisment
// all AdvData values must be in hex
$adv = BLE\Advertisment::import([
  'addr' => 'AA:BB:CC:DD:EE:FF',
  'atyp' => BLE\Advertisment::ADDR_PUB,
  'etyp' => BLE\Advertisment::ADV_NONCONN_IND,
  'rssi' => -50,
  'data' => [
    '09' => [bin2hex('Dummy')],            // Complete Local Name
    'ff' => ['0201aabbcc'],                // Manufacturer data: 0102 => aabbcc
    'ee' => [bin2hex(pack('N',time()))]    // unhandled dummy type, timestamp packed as uint32 big endian
  ]
]);
pn($adv);

// pack AdvData to binary string with padding to 31 bytes
//pn(bin2hex($adv->data->pack(true)));

// parser
$parser = new BLE\AdvertismentParser;

// when called, parser will do the same Advertisment::import() as above
// $adv can be
// a string - treated as json encoded
// an array as above
// SharkyDog\BLE\Advertisment object - returned as is
//
// in a json encoded string or array, the 'data' array must always have hex values and keys
// otherwise, a value will be discarded
// if $adv is not one of the above, can not be parsed as json or is missing a key
// parser will return null

// parse default
pn($parser($adv));

// add a parser for our unhandled type
// data is now an array of binary strings, not hex
$parser->addParser('ee', function(array $data, array &$parsed, BLE\Advertisment $adv) {
  $parsed['datetime'] = date('d.m.Y H:i:s', unpack('N',$data[0])[1]);
});

// and parse again
pn($parser($adv));

// remove some keys
// this callback will be called last
// if it returns false, parser will return null
// data will be an empty array
$parser->addParser('00', function(array $data, array &$parsed, BLE\Advertisment $adv) {
  unset($parsed['atyp'], $parsed['etyp']);
});

// and parse again
pn($parser($adv));

// parser doesn't need to be called multiple times
// add all of your callbacks and parse once

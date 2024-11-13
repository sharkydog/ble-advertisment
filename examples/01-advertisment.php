<?php

// include this file or add your autoloader


use SharkyDog\BLE\Advertisment;
use SharkyDog\BLE\AdvData;

function pn($d) {
  print "*** Ex.01: ".print_r($d,true)."\n";
}


//
// The SharkyDog\BLE\Advertisment class
// is meant to represent a BLE advertisments
// comming from different sources
// so they can be handled in a common way.
//
// The SharkyDog\BLE\AdvData class
// holds AdvData (0-32 bytes) within an advertisment
//
// All properties are private and can be modified only with setters.
//


// Advertisment
$adv = new Advertisment;

// get: $adv->addr
// converted to upper case
// invalid address will set 00:00:00:00:00:00
$adv->setAddress('aa:bb:cc:dd:ee:ff');

// get: $adv->atyp
// see Advertisment class for the constants
// and https://www.bluetooth.com/wp-content/uploads/Files/Specification/HTML/Core-54/out/en/host-controller-interface/host-controller-interface-functional-specification.html#UUID-bf6970a8-7187-7d2c-0408-b83aa09837e3
// Address_Type[i]: for values
$adv->setAddressType(Advertisment::ADDR_PUB);

// get: $adv->etyp
// see Advertisment class for the constants
// and https://www.bluetooth.com/wp-content/uploads/Files/Specification/HTML/Core-54/out/en/host-controller-interface/host-controller-interface-functional-specification.html#UUID-bf6970a8-7187-7d2c-0408-b83aa09837e3
// Event_Type[i]: for values
$adv->setEventType(Advertisment::ADV_NONCONN_IND);

// get: $adv->rssi
// min: -127, max: 20
// set to +127 if invalid
$adv->setRSSI(-50);

// get: $adv->data
// AdvData object
$adv->setData(new AdvData);


// print with empty AdvData
//pn($adv);

// export as array, AdvData would be converted to hex
// but is empty now
//pn($adv->export());


// AdvData
// printing an AdvData object with print_r() and var_dump()
// will produce debugging arrays showing AD values length, hex and ascii printable chars
$data = new AdvData;

// add an AD type and value
// AD type must be one byte hex string (2 hex chars)
// see section 2.3 from https://www.bluetooth.com/specifications/assigned-numbers
// AD value must be binary string, depends on type

// 09 - Complete Local Name
$data->add('09', 'Dummy');

// ff - Manufacturer Specific Data, first two bytes are company identifier
// little-endian, LSB first in string, would result in these integers - 0x0102, 0xBBAA
$data->add('ff', "\x02\x01\xAA\xBB");
//pn([[1=>0x0102, 2=>0xBBAA], unpack('v*',$data->getBin('ff'))]);

// pack ADs to advertising data binary payload up to 31 bytes
// ADs will not be truncated, packing will stop at first that exceeds the payload limit
// right padding to 31 bytes (true) or no padding (false, default)
$bin = $data->pack(false);
//pn(bin2hex($bin));

// export to an array with AD values in hex
$arr = $data->export();
//pn($arr);

// get an array of all ADs of a given type or an empty array
// AD value will be in binary
$FFs = $data->get('ff');
//pn(array_map('bin2hex', $FFs));

// or two-dimensional array of all ADs in binary
$ADs = $data->get();
//pn(array_map(fn($a) => array_map('bin2hex',$a), $ADs));

// get a single AD in binary or null
$FF0bin = $data->getBin('ff',0);
//pn(bin2hex($FF0bin));

// get a single AD in hex or null
$FF0hex = $data->getHex('ff',0);
//pn($FF0hex);

// set data in the advertisment
$adv->setData($data);
//pn($adv);


// export advertisment to array
$export = $adv->export();
//pn($export);

// json encode array
//$export = json_encode($export);

// import array to a new Advertisment object
// data to be imported can be
//  - json encoded string
//  - array as exported by Advertisment->export()
//  - an Advertisment object, returned as is
// the json encoded string and array must have their AD values in hex
// if imported data isn't one of the above or if some element is missing
// import() will return null
$adv = Advertisment::import($export);
//pn($adv);


// add flags to AdvData
// bit 1 - LE General Discoverable Mode
// bit 2 - BR/EDR Not Supported
$data->add('01', chr(0b0010 | 0b0100)); // 0x06
//pn($data);

// pack and export
// to demonstrate import into a new AdvData object
$bin = $data->pack(true);
$arr = $data->export();
//pn(bin2hex($bin));
//pn($arr);

// import from binary, unpack
$data = AdvData::parseBin($bin);
//pn($data);

// or import into existing AdvData object
// imported ADs will be appended to the object
// effectively, this will duplicate current ADs
// as we are using the same object
$data = AdvData::parseBin($bin, $data);
//pn($data);

// import from array
$data = AdvData::parseArray($arr);
$data = AdvData::parseArray($arr, new AdvData);
//pn($data);

// re-set data with flags
$adv->setData($data);
pn($adv);

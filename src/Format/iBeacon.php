<?php
namespace SharkyDog\BLE\Format;
use SharkyDog\BLE\AdvData;

class iBeacon {
  public $uuid;
  public $major;
  public $minor;
  public $power;

  public static function parseAdvData(AdvData $adv): ?self {
    foreach($adv->get('ff') as $mand) {
      if($mand[0] != "\x4C" || $mand[1] != "\x00") {
        continue;
      }
      return self::parseBin(substr($mand,2));
    }
    return null;
  }

  public static function parseBin(string $adv): ?self {
    if(strlen($adv) != 23) {
      return null;
    }
    if($adv[0] != "\x02" || $adv[1] != "\x15") {
      return null;
    }

    $that = new self;
    $that->uuid = implode('-',unpack('@2/H8a/H4b/H4c/H4d/H12e', $adv));

    $data = unpack('@18/nmajor/nminor/cpower', $adv);
    $that->major = $data['major'];
    $that->minor = $data['minor'];
    $that->power = $data['power'];

    return $that;
  }
}

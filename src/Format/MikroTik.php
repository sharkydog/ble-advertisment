<?php
namespace SharkyDog\BLE\Format;
use SharkyDog\BLE\AdvData;

class MikroTik {
  public $version;
  public $salt;
  public $encrypted;
  public $accel;
  public $accelX;
  public $accelY;
  public $accelZ;
  public $temperature;
  public $uptime;
  public $upDateTime;
  public $tgrAny;
  public $tgrReed;
  public $tgrTilt;
  public $tgrFall;
  public $tgrImpact;
  public $tgrImpactX;
  public $tgrImpactY;
  public $tgrImpactZ;
  public $battery;

  public static function parseAdvData(AdvData $adv): ?self {
    foreach($adv->get('ff') as $mand) {
      if($mand[0] != "\x4F" || $mand[1] != "\x09") {
        continue;
      }
      return self::parseBin(substr($mand,2));
    }
    return null;
  }

  public static function parseBin(string $adv): ?self {
    if(strlen($adv) != 18) {
      return null;
    }

    $that = new self;
    $that->version = ord($adv[0]);
    $that->salt = unpack('@2/vsalt',$adv)['salt'];
    $that->encrypted = (bool)(ord($adv[1]) & 0x01);

    if($that->encrypted) {
      return $that;
    }

    $accel = unpack('@4/vaccel_x/vaccel_y/vaccel_z',$adv);
    $accel = array_map(fn($a) => $a>=0x8000 ? $a-0x010000 : $a, $accel);
    $accel = array_map(fn($a) => $a/256, $accel);

    $that->accelX = round($accel['accel_x'],4).' m/s²';
    $that->accelY = round($accel['accel_y'],4).' m/s²';
    $that->accelZ = round($accel['accel_z'],4).' m/s²';

    $accel = array_map(fn($a) => pow($a,2), $accel);
    $accel = sqrt(array_sum($accel));
    $that->accel = round($accel,4).' m/s²';

    $data = unpack('@10/vtemp/Vuptime/Ctrigger/Cbattery',$adv);

    $temp = $data['temp']==0x8000 ? null : $data['temp'];
    $temp = $temp===null ? null : ($temp>0x8000 ? $temp-0x010000 : $temp);
    $that->temperature = $temp===null ? null : round($temp/256,2).'°C';

    $that->uptime = $data['uptime'].'s';
    $that->upDateTime = date('d.m.Y H:i:s',time()-(int)$that->uptime);

    $trigger = $data['trigger'];
    $that->tgrAny = (bool)($trigger & 0x3F);
    $that->tgrReed = (bool)($trigger & 0x01);
    $that->tgrTilt = (bool)($trigger & 0x02);
    $that->tgrFall = (bool)($trigger & 0x04);
    $that->tgrImpact = (bool)($trigger & 0x38);
    $that->tgrImpactX = (bool)($trigger & 0x08);
    $that->tgrImpactY = (bool)($trigger & 0x10);
    $that->tgrImpactZ = (bool)($trigger & 0x20);

    $that->battery = $data['battery'].'%';

    return $that;
  }
}

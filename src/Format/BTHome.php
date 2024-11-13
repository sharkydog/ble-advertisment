<?php
namespace SharkyDog\BLE\Format;
use SharkyDog\BLE\AdvData;

class BTHome {
  protected static $len = [
    '00'=>1,'01'=>1,'02'=>2,'03'=>2,'04'=>3,'05'=>3,'06'=>2,'07'=>2,
    '08'=>2,'09'=>1,'0a'=>3,'0b'=>3,'0c'=>2,'0d'=>2,'0e'=>2,'0f'=>1,
    '10'=>1,'11'=>1,'12'=>2,'13'=>2,'14'=>2,'15'=>1,'16'=>1,'17'=>1,
    '18'=>1,'19'=>1,'1a'=>1,'1b'=>1,'1c'=>1,'1d'=>1,'1e'=>1,'1f'=>1,
    '20'=>1,'21'=>1,'22'=>1,'23'=>1,'24'=>1,'25'=>1,'26'=>1,'27'=>1,
    '28'=>1,'29'=>1,'2a'=>1,'2b'=>1,'2c'=>1,'2d'=>1,'2e'=>1,'2f'=>1,
    '30'=>0,'31'=>0,'32'=>0,'33'=>0,'34'=>0,'35'=>0,'36'=>0,'37'=>0,
    '38'=>0,'39'=>0,'3a'=>1,'3b'=>0,'3c'=>2,'3d'=>2,'3e'=>4,'3f'=>2
  ];

  public static function parseAdvData(AdvData $adv): ?\stdClass {
    foreach($adv->get('16') as $svcd) {
      if($svcd[0] != "\xD2" || $svcd[1] != "\xFC") {
        continue;
      }
      return self::parseBin(substr($svcd,2));
    }
    return null;
  }

  public static function parseBin(string $adv): ?\stdClass {
    if(!(($cnk = ord($adv[0])) & 0x40)) {
      return null;
    }

    self::str_shift($adv,1);
    $ret = (object)[];
    $ret->type = ($cnk & 0x04) ? 'trigger' : 'beacon';
    $ret->encrypted = (bool)($cnk & 0x01);

    if($ret->encrypted) {
      return $ret;
    }

    $idx = [];

    while(strlen($adv)) {
      $cnk = self::str_shift($adv, 1);
      if(!strlen($cnk) || !strlen($adv)) {
        return $ret;
      }

      $cnkhex = bin2hex($cnk);
      if(!(static::$len[$cnkhex]??0)) {
        return $ret;
      }

      $fnc = 'fmt_'.$cnkhex;
      if(!method_exists(static::class, $fnc)) {
        return $ret;
      }
      $fnc = static::class.'::'.$fnc;

      $cnk = self::str_shift($adv, static::$len[$cnkhex]);
      if(strlen($cnk) != static::$len[$cnkhex]) {
        return $ret;
      }

      $fmt = $fnc($cnk);

      if(!isset($idx[$fmt[0]])) $idx[$fmt[0]] = 0;

      $key = $fmt[0];
      $key = $idx[$key] ? $key.'_'.$idx[$key] : $key;
      $val = $fmt[1];
      $idx[$fmt[0]]++;

      $ret->$key = $val;
    }

    return $ret;
  }

  protected static function str_shift(&$str, $len=1) {
    $cnk = substr($str, 0, $len);
    $str = substr($str, $len);
    return $cnk;
  }

  protected static function dec_sint16($v) {
    return unpack('s',$v)[1];
  }
  protected static function dec_uint24($v) {
    return unpack('L',$v.chr(0))[1];
  }

  protected static function fmt_00($v) {
    return ['packet',ord($v)];
  }
  protected static function fmt_01($v) {
    return ['battery',ord($v).'%'];
  }
  protected static function fmt_05($v) {
    return ['illuminance',(self::dec_uint24($v)*0.01).' lux'];
  }
  protected static function fmt_2d($v) {
    return ['window',ord($v)?'opened':'closed'];
  }
  protected static function fmt_3a($v) {
    return ['button', [
      'na','press','double_press','triple_press',
      'long_press','long_double_press','long_triple_press'
    ][ord($v)]??'na'];
  }
  protected static function fmt_3f($v) {
    return ['rotation',(self::dec_sint16($v)*0.1).'°'];
  }
}

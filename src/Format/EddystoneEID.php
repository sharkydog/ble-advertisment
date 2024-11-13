<?php
namespace SharkyDog\BLE\Format;
use SharkyDog\BLE\AdvData;

class EddystoneEID {
  private $_key;
  private $_exp;
  private $_int;
  private $_cnt;
  private $_txp;

  private $_eid = [];
  private $_match = 0;

  public static function parseAdvData(AdvData $adv): ?\stdClass {
    foreach($adv->get('16') as $svcd) {
      if($svcd[0] != "\xAA" || $svcd[1] != "\xFE") {
        continue;
      }
      return self::parseBin(substr($svcd,2));
    }
    return null;
  }

  public static function parseBin(string $adv): ?\stdClass {
    if(strlen($adv)<10) return null;
    if($adv[0] != "\x30") return null;
    return (object)unpack('ctxp/H16eid', substr($adv,1));
  }

  public function __construct(string $key, int $exp=4, int $cnt=1) {
    $this->_key = $key;
    $this->_exp = max(0, min($exp, 15));
    $this->_int = pow(2, $this->_exp);
    $this->_cnt = max(1, $cnt);
  }

  public function __debugInfo() {
    $props = get_object_vars($this);
    $props['_key'] = [
      '_hex' => bin2hex($props['_key']),
      '_b64' => base64_encode($props['_key'])
    ];
    return $props;
  }

  public function getTxp(): ?int {
    return $this->_txp;
  }

  public function getMatch(): int {
    return $this->_match;
  }

  public function getCounter(int $tss): int {
    return $this->_int - ($tss - $this->_tsi($tss));
  }

  public function genEID(int $tss): string {
    return $this->_eid($this->_tsi($tss));
  }

  public function match(string $eid, int $tss, ?int $txp=null): int {
    $this->_txp = ($txp === null || max(-128,min($txp,127)) == $txp) ? $txp : null;

    $tsi = $this->_tsi($tss);
    $this->_match = 0;

    for($i=0; $i<$this->_cnt; $i++)  {
      if($i) $tsi -= $this->_int;

      if(isset($this->_eid[$tsi])) {
        if($this->_eid[$tsi] == $eid) {
          $this->_match = $i+1;
          return $this->_match;
        }
        continue;
      }

      $out = $this->_eid($tsi);

      if($i) {
        $this->_eid = $this->_eid + [$tsi=>$out];
      } else {
        if(count($this->_eid)==$this->_cnt) {
          array_pop($this->_eid);
        }
        $this->_eid = [$tsi=>$out] + $this->_eid;
      }

      if($this->_eid[$tsi] == $eid) {
        $this->_match = $i+1;
        return $this->_match;
      }
    }

    return $this->_match;
  }

  private function _tsi($tss) {
    return ($tss >> $this->_exp) << $this->_exp;
  }

  private function _eid($tsi) {
    $key = $this->_key;
    $opt = OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING;
    $tmp = openssl_encrypt(pack('@11C@14n',0xFF,$tsi>>16), 'aes-128-ecb', $key, $opt);
    $out = openssl_encrypt(pack('@11CN',$this->_exp,$tsi), 'aes-128-ecb', $tmp, $opt);
    return bin2hex(substr($out,0,8));
  }
}

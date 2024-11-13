<?php
namespace SharkyDog\BLE;

class AdvData {
  private $_data = [];

  public static function parseBin(string $data, ?self $that=null): self {
    $that = $that ?: new self;
    $dlen = strlen($data) - 1;
    $dpos = 0;
    $alen = 0;

    while($dpos < $dlen) {
      if(!$alen && !($alen = ord($data[$dpos]))) {
        break;
      }

      $key = strtolower(bin2hex($data[$dpos+1]));
      $val = substr($data,$dpos+2,$alen-1);

      if(!isset($that->_data[$key])) {
        $that->_data[$key] = [];
      }

      $that->_data[$key][] = $val;
      $dpos += 1+$alen;
      $alen = 0;
    }

    return $that;
  }

  public static function parseArray(array $datas, ?self $that=null): self {
    $that = $that ?: new self;

    if(empty($datas)) {
      return $that;
    }

    $filter = function(&$val) {
      if(!is_string($val)) {
        $val = false;
      } else {
        $val = @hex2bin($val);
      }
    };

    foreach($datas as $key => $data) {
      if(strlen(($key=(string)$key)) != 2 || @hex2bin($key) === false) {
        continue;
      }
      if(!is_array($data) || empty($data)) {
        continue;
      }

      array_walk($data, $filter);
      $data = array_filter($data);

      if(empty($data)) {
        continue;
      }

      $that->_data[strtolower($key)] = array_values($data);
    }

    return $that;
  }

  private function _convertData($fn) {
    $data = [];

    foreach($this->_data as $key => $datas) {
      $data[$key] = array_map($fn, $datas);
    }

    return $data;
  }

  public function pack(bool $pad=false): string {
    $bin = '';

    foreach($this->_data as $key => $datas) {
      $key = hex2bin($key);

      foreach($datas as $data) {
        $len = strlen($data);

        if(($len+2+strlen($bin)) > 31) {
          break 2;
        }

        $bin .= chr($len+1).$key.$data;
      }
    }

    if($pad) {
      $bin = str_pad($bin, 31, "\x0");
    }

    return $bin;
  }

  public function export(): array {
    return $this->_convertData('bin2hex');
  }

  public function __debugInfo() {
    return $this->_convertData(function($val) {
      return [
        '_len' => strlen($val),
        '_bin' => preg_replace('/[^\x21-\x7E]/','.',$val),
        '_hex' => bin2hex($val)
      ];
    });
  }

  public function add(string $key, string $val) {
    if(strlen($key) != 2 || @hex2bin(($key = strtolower($key))) === false) {
      return;
    }
    if(!isset($this->_data[$key])) {
      $this->_data[$key] = [];
    }
    $this->_data[$key][] = $val;
  }

  public function get(string $key=''): array {
    return !$key ? $this->_data : ($this->_data[strtolower($key)] ?? []);
  }

  public function getBin(string $key, int $idx=0): ?string {
    return $this->_data[strtolower($key)][$idx] ?? null;
  }

  public function getHex(string $key, int $idx=0): ?string {
    if(($data = $this->getBin($key,$idx)) === null) {
      return null;
    } else {
      return bin2hex($data);
    }
  }
}

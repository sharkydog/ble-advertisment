<?php
namespace SharkyDog\BLE;
use SharkyDog\MessageBroker as MSGB;

class AdvertismentParser {
  private $_parsers = [];

  private function _fn($key) {
    if(array_key_exists($key,$this->_parsers)) {
      $fn = $this->_parsers[$key];
    } else {
      $fn = '_parse_adv_'.$key;
      $fn = method_exists($this, $fn) ? [$this,$fn] : null;
    }
    return $fn;
  }

  public function addParser(string $key, ?callable $parser) {
    $this->_parsers[strtolower($key)] = $parser;
  }

  public function addToBroker(MSGB\Server $msgb, string $stopic, string $ptopic) {
    new MSGB\Local\MessageParser(function($msg,$topic,$from) {
      return ($msg=$this($msg)) ? json_encode($msg) : null;
    }, $msgb, $stopic, $ptopic);
  }

  public function __invoke($msg): ?array {
    if(!($msg = Advertisment::import($msg))) {
      return null;
    }

    $parsed = [
      'addr' => $msg->addr,
      'atyp' => $msg->atyp,
      'etyp' => $msg->etyp,
      'rssi' => $msg->rssi
    ];

    foreach($msg->data->get() as $key => $data) {
      if(!($fn = $this->_fn($key))) {
        continue;
      }
      $fn($data, $parsed, $msg);
    }

    if(($fn = $this->_fn('00')) && $fn([], $parsed, $msg) === false) {
      return null;
    }

    return $parsed;
  }

  protected function _parse_adv_00(array $data, array &$store) {
  }

  protected function _parse_adv_01(array $data, array &$store) {
    $store['flags'] = ord($data[0]);
  }

  protected function _parse_adv_02(array $data, array &$store) {
    if(!isset($store['svc16l'])) $store['svc16l'] = [];
    $svcl = str_split(bin2hex(strrev($data[0])),4);
    $store['svc16l'] = array_unique(array_merge($store['svc16l'], $svcl));
  }
  protected function _parse_adv_03(array $data, array &$store) {
    $this->_parse_adv_02($data, $store);
  }

  protected function _parse_adv_08(array $data, array &$store) {
    $store['sname'] = $data[0];
  }
  protected function _parse_adv_09(array $data, array &$store) {
    $store['cname'] = $data[0];
  }

  protected function _parse_adv_16(array $data, array &$store) {
    if(!isset($store['svc16d'])) $store['svc16d'] = [];
    foreach($data as $svcd) {
      $key = bin2hex(strrev(substr($svcd,0,2)));
      $val = bin2hex(substr($svcd,2));
      $store['svc16d'][$key] = $val;
    }
  }

  protected function _parse_adv_ff(array $data, array &$store) {
    if(!isset($store['mandt'])) $store['mandt'] = [];
    foreach($data as $mand) {
      $key = bin2hex(strrev(substr($mand,0,2)));
      $val = bin2hex(substr($mand,2));
      $store['mandt'][$key] = $val;
    }
  }
}

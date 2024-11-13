<?php
namespace SharkyDog\BLE;

class Advertisment {
  const ADV_IND = 0;
  const ADV_DIRECT_IND = 1;
  const ADV_SCAN_IND = 2;
  const ADV_NONCONN_IND = 3;
  const SCAN_RSP = 4;

  const ADDR_PUB = 0;
  const ADDR_RAND = 1;
  const ADDR_PUB_IDENT = 2;
  const ADDR_RAND_STAT = 3;

  private $addr = '00:00:00:00:00:00';
  private $atyp = self::ADDR_PUB;
  private $etyp = self::ADV_NONCONN_IND;
  private $rssi = 127;
  private $data;

  public function __construct() {
    $this->data = new AdvData;
  }

  public static function import($msg): ?self {
    if($msg instanceOf self) {
      return $msg;
    }

    if(is_string($msg) && !($msg = json_decode($msg,true))) {
      return null;
    }
    if(!is_array($msg)) {
      return null;
    }
    if(!isset($msg['addr'],$msg['atyp'],$msg['etyp'],$msg['rssi'],$msg['data'])) {
      return null;
    }

    $adv = new self;
    $adv->setAddress((string)$msg['addr']);
    $adv->setAddressType((int)$msg['atyp']);
    $adv->setEventType((int)$msg['etyp']);
    $adv->setRSSI((int)$msg['rssi']);

    if($msg['data'] instanceOf AdvData) {
      $adv->data = $msg['data'];
    } else {
      AdvData::parseArray(
        is_array($msg['data']) ? $msg['data'] : [],
        $adv->data
      );
    }

    return $adv;
  }

  public function __get($prop) {
    if($prop[0] == '_') return null;
    else return $this->$prop;
  }

  public function export(): array {
    return [
      'addr' => $this->addr,
      'atyp' => $this->atyp,
      'etyp' => $this->etyp,
      'rssi' => $this->rssi,
      'data' => $this->data->export()
    ];
  }

  public function __debugInfo() {
    return [
      'addr' => $this->addr,
      'atyp' => $this->atyp,
      'etyp' => $this->etyp,
      'rssi' => $this->rssi,
      'data' => $this->data
    ];
  }

  public function setAddress(string $addr) {
    if(!preg_match('/^(?:[a-f0-9]{2})(?:\:[a-f0-9]{2}){5}$/i',$addr)) {
      $this->addr = '00:00:00:00:00:00';
    } else {
      $this->addr = strtoupper($addr);
    }
  }

  public function setAddressType(int $atyp) {
    $this->atyp = max(self::ADDR_PUB, min(self::ADDR_RAND_STAT, $atyp));
  }

  public function setEventType(int $etyp) {
    $this->etyp = max(self::ADV_IND, min(self::SCAN_RSP, $etyp));
  }

  public function setRSSI(int $rssi) {
    $this->rssi = max(-127, min(20, $rssi)) == $rssi ? $rssi : 127;
  }

  public function setData(AdvData $data) {
    $this->data = $data;
  }
}

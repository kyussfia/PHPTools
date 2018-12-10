<?php

namespace PlanBundle\Schedule\Common\Resource;

class MemObserver {

    private $PHPMemory;
    private $OSMemory;

    public function __construct() {
        $this->PHPMemory = memory_get_usage(false);
        $this->OSMemory = memory_get_usage(true);
    }

    public function start() {
        $this->PHPMemory = memory_get_usage(false);
        $this->OSMemory = memory_get_usage(true);
    }

    public function mem() {
        $this->PHPMemory = memory_get_usage(false) - $this->PHPMemory;
        $this->OSMemory = memory_get_usage(true) - $this->OSMemory;
        return $this->convert($this->PHPMemory) . " PHP Memory usage. " .  $this->convert($this->OSMemory) . " OS Memory usage.";

    }

    private function convert($size) {
        $unit=array('b','kb','mb','gb','tb','pb');
        return pow(1024,($i=floor(log($size,1024)))) > 0 ? @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i] : 0 .' '. $unit[$i];
    }
}
<?php

class Engine {

  private function move_curosor(int $x, int $y) {
    echo "\033[{$x};{$y}H";
  }
  
  public function draw_tuit(array $tuit, int $x, int $y) {
    $this->move_curosor($x, $y);
    foreach ($tuit as $line) {
      echo "\0337";
      echo $line;
      echo "\0338";
      echo "\033[1B";
    }
  }
}














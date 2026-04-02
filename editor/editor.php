<?php

$ffi = FFI::cdef("
  void* GetStdHandle(unsigned int nStdHandle);

  bool GetConsoleMode(void* hConsoleHandle, unsigned int* lpMode);
  bool SetConsoleMode(void* hConsoleHandle, unsigned int dwMode);
  bool ReadConsoleInputA(void* hConsoleInput, void* lpBuffer, unsigned int nLength, unsigned int* lpNumberOfEventsRead);
  bool FlushConsoleInputBuffer(void* hConsoleInput);

  struct KEY_EVENT_RECORD {
    int bKeyDown;
    unsigned short wRepeatCount;
    unsigned short wVirtualKeyCode;
    unsigned short wVirtualScanCode;
    union {
      unsigned short UnicodeChar;
      char AciiChar;
    } uChar;
    unsigned int dwControlKeyState;
  };

  struct INPUT_RECORD {
    unsigned short EventType;
    union {
      struct KEY_EVENT_RECORD KeyEvent;
    } Event;
  };

", "kernel32.dll"
);

class Editor {
  public bool $is_running;

  //RGB
  private array $rgb;
  //Size
  private int $rows;
  private int $columns;

  function __construct()
  { 
    global $ffi;

    $stdout = $ffi->GetStdHandle(-11);

    $mode = FFI::new("unsigned int");
    $ffi->GetConsoleMode($stdout, FFI::addr($mode));
    $mode->cdata |= 0x0004;
    $mode->cdata |= 0x0001;

    $ffi->SetConsoleMode($stdout, $mode->cdata);

    $this->is_running = true;


    $this->rgb = $this->set_rgb();
  }

  private function move_curosor(int $x, int $y) {
    echo "\033[{$x};{$y}H";
  }

  private function enable_raw_mode() {
    global $ffi;
    $stdin = $ffi->GetStdHandle(-10);
    
    $mode = FFI::new("unsigned int");
    $ffi->GetConsoleMode($stdin, FFI::addr($mode));

    $mode->cdata &= ~ 0x0004;
    $mode->cdata &= ~ 0x0002;

    $ffi->SetConsoleMode($stdin, $mode->cdata);
  }

  private function disable_raw_mode() {
    global $ffi;
    $stdin = $ffi->GetStdHandle(-10);
    
    $mode = FFI::new("unsigned int");
    $ffi->GetConsoleMode($stdin, FFI::addr($mode));

    $mode->cdata |= 0x0004;
    $mode->cdata |= 0x0002;

    $ffi->SetConsoleMode($stdin, $mode->cdata);
  }

  public function read_key() {
    global $ffi;

    $input_record = $ffi->new("struct INPUT_RECORD");

    $key_event = $input_record->Event->KeyEvent;
    
    $eventsRead = $ffi->new("unsigned int");

    $stdin = $ffi->GetStdHandle(-10);
    $ffi->FlushConsoleInputBuffer($stdin);
    
    do {
      $ffi->ReadConsoleInputA($stdin, FFI::addr($input_record), 1, FFI::addr($eventsRead));
    } while ($key_event->bKeyDown !== 1 || $input_record->EventType !== 0x0001);

    return $key_event->wVirtualKeyCode;
  }

  public function set_rgb() {
  $rgb = [];
  //RED
    $this->move_curosor(2,1);
    echo "r:";

    $this->move_curosor(2, 3);
    $red = readline();
    while (true) {
      if (intval($red) === 0) {
        echo "\x1b[2K";
        $this->move_curosor(2,1);

        echo "r:";
        $this->move_curosor(2, 3);

        $red = readline();
        echo "\x1b[2K"; 
        continue;

      } else {
        $red = intval($red);
        if ($red > 255 || $red < 0) {
          echo "\x1b[2K";
          $this->move_curosor(2,1);

          echo "r:";
          $this->move_curosor(2, 3);

          $red = readline();
          echo "\x1b[2K"; 
          continue;
        }
        break;
      }
    }
    
    //GREEN
    $this->move_curosor(3,1);
    echo "g:";

    $this->move_curosor(3, 3);
    $green = readline();
    while (true) {
      if (intval($green) === 0) {
        echo "\x1b[2K";
        $this->move_curosor(3,1);

        echo "g:";
        $this->move_curosor(3, 3);

        $red = readline();
        echo "\x1b[2K"; 
        continue;

      } else {
        $green = intval($green);
        if ($green > 255 || $green < 0) {
          echo "\x1b[2K";
          $this->move_curosor(3,1);

          echo "g:";
          $this->move_curosor(3, 3);

          $green = readline();
          echo "\x1b[2K"; 
          continue;
        }
        break;
      }
    }

    //BLUE
    $this->move_curosor(4,1);
    echo "b:";

    $this->move_curosor(4, 3);
    $blue = readline();
    while (true) {
      if (intval($blue) === 0) {
        echo "\x1b[2K";
        $this->move_curosor(4,1);

        echo "b:";
        $this->move_curosor(4, 3);

        $blue = readline();
        echo "\x1b[2K"; 
        continue;

      } else {
        $blue = intval($blue);
        if ($blue > 255 || $blue < 0) {
          echo "\x1b[2K";
          $this->move_curosor(4,1);

          echo "b:";
          $this->move_curosor(4, 3);

          $blue = readline();
          echo "\x1b[2K"; 
          continue;
        }
        break;
      }
    }
    array_push($rgb, $red, $green, $blue);
    return $rgb;
  }
  private function draw() {
    $r = $this->rgb[0];
    $g = $this->rgb[1];
    $b = $this->rgb[2];

    echo "\x1b[38;2;{$r};{$g};{$b}m";
    echo "█";
    echo "\x1b[0m";
  }

  public function move() {
    $key = $this->read_key();
    switch($key) {
      case 37:            //Left
        echo "\033[1D";
        break;
      case 38:            //Up
        echo "\033[1A";
        break;
      case 39:            //Right
        echo "\033[1C";
        break;
      case 40:            //Down
        echo "\033[1B";
        break;
      case 13:            //Enter 
        $this->draw();
        break;
      case 9:
        $this->rgb = $this->set_rgb();
    } 
  }     
}

echo "\x1b[10;10H\x1b[2J";
$editor =  new Editor;

while ($editor->is_running) {
  $editor->move();
}



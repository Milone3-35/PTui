<?php

$ffi = FFI::cdef("
  void* GetStdHandle(unsigned int nStdHandle);

  bool GetConsoleMode(void* hConsoleHandle, unsigned int* lpMode);
  bool SetConsoleMode(void* hConsoleHandle, unsigned int dwMode);
  bool ReadConsoleInputA(void* hConsoleInput, void* lpBuffer, unsigned int nLength, unsigned int* lpNumberOfEventsRead);
  bool FlushConsoleInputBuffer(void* hConsoleInput);

  typedef struct {
    short X;
    short Y;
  } COORD;

  typedef struct {
    short Left;
    short Top;
    short Right;
    short Bottom;
  } SMALL_RECT;

  typedef struct {
    COORD dwSize;
    COORD dwCursorPosition;
    unsigned short wAttributes;
    SMALL_RECT srWindow;
    COORD dwMaximumWindowSize;
  } CONSOLE_SCREEN_BUFFER_INFO;

  bool GetConsoleScreenBufferInfo(void* hConsoleOutput, CONSOLE_SCREEN_BUFFER_INFO* lpConsoleScreenBufferInfo);

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
  private array $highest_points;

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
    $this->highest_points = [0,0,0,0];    //N|E|S|W
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

  private function set_rgb() {
  $rgb = [];

  /* We do a range of 1-256 even tho rgb is from 0-255, 
  but intval returns 0 if $value is a string and we need 0, so we 
  just substract 1 at the end
  */

  //RED
  while (true) {
    $this->move_curosor(4, 1);
    echo "\033[2K";

    $red = readline("r:");
    if (!is_numeric($red)) { continue; };

    $red = intval($red);
    if ($red < 0 || $red > 255) { continue; };
    break;    
  }

  //GREEN
  while (true) {
    $this->move_curosor(5, 1);
    echo "\033[2K";

    $green = readline("g:");
    if (!is_numeric($green)) { continue; };

    $green = intval($green);
    if ($green < 0 || $green > 255) { continue; };
    break;
  }

  //BLUE
  while (true) {
    $this->move_curosor(6, 1);
    echo "\033[2K";

    $blue = readline("b:");
    if (!is_numeric($blue)) { continue; };

    $blue = intval($blue);
    if ($blue < 0 || $blue > 255) { continue; };
    break;
    }

  array_push($rgb, $red, $green, $blue);
  return $rgb;
  }
  private function draw() {
    $r = $this->rgb[0];
    $g = $this->rgb[1];
    $b = $this->rgb[2];

    echo "\033[38;2;{$r};{$g};{$b}m";
    echo "█";
    echo "\033[0m";
  }
  private function update_highest_points() {
    
  }
  private function leave() {
    $this->is_running = false;
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
        $this->update_highest_points();
        $this->draw();
        break;
      case 9:             //Tab
        $this->rgb = $this->set_rgb();
      case 8:
        echo " ";
        echo "\033[2D";
    } 
  }     
}

echo "\x1b[10;10H\x1b[2J";
$editor =  new Editor;

echo "\033[2 q";
while ($editor->is_running) {
  $editor->move();
}
echo "\033[5 q";



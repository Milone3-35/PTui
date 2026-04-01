<?php

function move_curosor(int $x, int $y) {
  echo "\033[{$x};{$y}H";
}

function draw_tuit(array $tuit, int $x, int $y) {
  move_curosor($x, $y);
  foreach ($tuit as $line) {
    echo "\0337";
    echo $line;
    echo "\0338";
    echo "\033[1B";
  }
}

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

function enable_raw_mode() {
  global $ffi;
  $stdin = $ffi->GetStdHandle(-10);
  
  $mode = FFI::new("unsigned int");
  $ffi->GetConsoleMode($stdin, FFI::addr($mode));

  $mode->cdata &= ~ 0x0004;
  $mode->cdata &= ~ 0x0002;

  $ffi->SetConsoleMode($stdin, $mode->cdata);
}

function disable_raw_mode() {
  global $ffi;
  $stdin = $ffi->GetStdHandle(-10);
  
  $mode = FFI::new("unsigned int");
  $ffi->GetConsoleMode($stdin, FFI::addr($mode));

  $mode->cdata |= 0x0004;
  $mode->cdata |= 0x0002;

  $ffi->SetConsoleMode($stdin, $mode->cdata);
}

function read_key() {
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





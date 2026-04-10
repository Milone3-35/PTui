<?php

$ffi = FFI::cdef("
  void* GetStdHandle(unsigned int nStdHandle);
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
", "kernel32.dll"
);

$stdout = $ffi->GetStdHandle(-11);
$csbi = $ffi->new("CONSOLE_SCREEN_BUFFER_INFO");
$ffi->GetConsoleScreenBufferInfo($stdout, FFI::addr($csbi));

$rows = $csbi->dwSize->X;
$columns = $csbi->dwSize->Y;

echo $rows . "\n" .  $columns;

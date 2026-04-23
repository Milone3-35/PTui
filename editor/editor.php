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

	//Cursor position
	private int $cursor_pos_x;
	private int $cursor_pos_y;
	
	//Canvas frame 
	private array $canvas_frame;

	//Pixels 
	private array $pixels;

	//Size 
	private int $terminal_width;
	private int $terminal_height;

	private int $canvas_width;
	private int $canvas_height;
	
 	//Save 
	private ?string $file_path = null;

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

		$stdout = $ffi->GetStdHandle(-11);
		$csbi = $ffi->new("CONSOLE_SCREEN_BUFFER_INFO");
		$ffi->GetConsoleScreenBufferInfo($stdout, FFI::addr($csbi));

		$this->terminal_width = $csbi->dwSize->X - 20;
		$this->terminal_height = $csbi->dwSize->Y;

		$this->canvas_frame = $this->create_canvas();
		$this->rgb = $this->set_rgb();
		
		$canvas_left_corner_y = floor($this->terminal_height / 2) - floor($this->canvas_height / 2);
		$canvas_left_corner_x = floor($this->terminal_width / 2) - floor($this->canvas_width / 2);
		$this->move_curosor($canvas_left_corner_y, $canvas_left_corner_x + 15);

		$this->cursor_pos_y = $canvas_left_corner_y;
		$this->cursor_pos_x = $canvas_left_corner_x +15;

		$this->pixels = [];
  }
	
	public function create_canvas() {
		//Width
		while (true) {
			$this->move_curosor(1, 0);
			$width = readline("width:");

			if (!is_numeric($width)) { continue; };
			if ($width > $this->terminal_width || $width < 20) { continue; };

			$this->canvas_width = $width;
			break;
		}	
		//Height 
		while (true) {
			$this->move_curosor(2, 0);
			$height = readline("height:");

			if (!is_numeric($height)) { continue; };
			if ($height > $this->terminal_height || $height <= 0) { continue; };

			$this->canvas_height = $height;
			break;
		}
		
		$canvas_left_corner_y = floor($this->terminal_height / 2) - floor($this->canvas_height / 2);
		$canvas_left_corner_x = floor($this->terminal_width / 2) - floor($this->canvas_width / 2);
		$this->move_curosor($canvas_left_corner_y, $canvas_left_corner_x + 15);

		echo "\033[38;2;255;255;255m";
		for ($i = 0; $i < $this->canvas_height; $i++) {
			echo str_repeat("█", $this->canvas_width);
			$this->move_curosor($canvas_left_corner_y + $i, $canvas_left_corner_x + 15);
		}
		$corner_coordinates = [
			"Left x" => $canvas_left_corner_x + 15, 
			"Right x" => $canvas_left_corner_x + 15 + $this->canvas_width - 1, 
			"Top y" => $canvas_left_corner_y, 
			"Bottom y" => $canvas_left_corner_y + $this->canvas_height - 2
		];
		return $corner_coordinates;
	}
  private function move_curosor(int $y, int $x) {
    echo "\033[{$y};{$x}H";
	}

  public function enable_raw_mode() {
    global $ffi;
    $stdin = $ffi->GetStdHandle(-10);
    
    $mode = FFI::new("unsigned int");
    $ffi->GetConsoleMode($stdin, FFI::addr($mode));

    $mode->cdata &= ~ 0x0004;
    $mode->cdata &= ~ 0x0002;

    $ffi->SetConsoleMode($stdin, $mode->cdata);
  }

  public function disable_raw_mode() {
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

		$key = $this->cursor_pos_x . "_" . $this->cursor_pos_y;
		$this->pixels[$key] = [$r, $g, $b];
  }
  private function leave() {
    $this->is_running = false;
	}
	public function save() {
    $this->move_curosor(3, 1);
    
    if ($this->file_path === null) {
        $this->file_path = readline("Save at: ");
    }
    
    $data = pack("A4Cnn", "TUIT", 1, $this->canvas_width, $this->canvas_height);

    foreach ($this->pixels as $key => $pixel) {
        [$x, $y] = explode("_", $key);
        
        $data .= pack("CCCCC", (int)$x, (int)$y, $pixel[0], $pixel[1], $pixel[2]);
    }

    file_put_contents($this->file_path, $data);

    $this->move_curosor($this->cursor_pos_y, $this->cursor_pos_x);
	}

	public function load() {
		$this->pixels = [];

		$this->move_curosor(3,1);
		$this->file_path = readline("Load from:");

		$tuit_to_load = file_get_contents($this->file_path);

		$pixel_data = substr($tuit_to_load, 9);
		$total_bytes = strlen($pixel_data);

		for ($i = 0; $i < $total_bytes; $i += 5) {
			$chunk = substr($pixel_data, $i, 5);

			$pixel = unpack("Cx/Cy/Cr/Cg/Cb", $chunk);
			$key = $pixel['x'] . "_" . $pixel['y'];
			$this->pixels[$key] = [$pixel['r'], $pixel['g'], $pixel['b']];
		}
		$this->redraw();
	}

	private function redraw() {
		foreach ($this->pixels as $key => $pixel) {
			[$x, $y] = explode("_", $key);
			$this->move_curosor($y, $x);
			echo "\033[38;2;{$pixel[0]};{$pixel[1]};{$pixel[2]}m█";
		}
		echo "\033[0m";

		$canvas_left_corner_y = floor($this->terminal_height / 2) - floor($this->canvas_height / 2);
		$canvas_left_corner_x = floor($this->terminal_width / 2) - floor($this->canvas_width / 2);
		$this->move_curosor($canvas_left_corner_y, $canvas_left_corner_x + 15);	
	}
  public function move() {
    $key = $this->read_key();
    switch($key) {
		
		case 37:            //Left
			if ($this->cursor_pos_x <= $this->canvas_frame["Left x"]) {
				break;
			}
			$this->cursor_pos_x -= 1;
      echo "\033[1D";
			break;

		case 38:            //Up
			if ($this->cursor_pos_y <= $this->canvas_frame["Top y"]) {
				break;
			}
			$this->cursor_pos_y -= 1;
      echo "\033[1A";
			break;

		case 39:            //Right
			if ($this->cursor_pos_x >= $this->canvas_frame["Right x"]) {
				break;
			}
			$this->cursor_pos_x += 1;
      echo "\033[1C";
			break;

		case 40:            //Down
			if ($this->cursor_pos_y >= $this->canvas_frame["Bottom y"]) {
				break;
			}
			$this->cursor_pos_y += 1;
      echo "\033[1B";
			break;

    case 13:            //Enter 
			$this->draw();
			echo "\033[1D";   //Cursor position is not influenced by enter
			break;

    case 9:             //Tab
			$this->rgb = $this->set_rgb();
			$this->move_curosor($this->cursor_pos_y, $this->cursor_pos_x);
			break;

		case 8:							//Backspace
			$key = $this->cursor_pos_x . "_" . $this->cursor_pos_y;
			if (isset($this->pixels[$key])) {
				unset($this->pixels[$key]);
			}
			echo "\033[38;2;255;255;255m█";

			echo "\033[1D";
			break;

		case 27: 					//ESC
			$this->leave();
			break;

		case 83:					//S
			$this->save();
			break;

		case 76: 					//L
			$this->load();
			break;
		}
  }     
}

echo "\x1b[10;10H\x1b[2J";
$editor =  new Editor;
$editor->enable_raw_mode();
echo "\033[2 q";
while ($editor->is_running) {
  $editor->move();
}
$editor->disable_raw_mode();
echo "\033[5 q";



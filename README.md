# PTui - Terminal User Interface Texture Editor

**PTui** is an experimental, terminal-based pixel art editor written entirely in PHP. It leverages the PHP FFI (Foreign Function Interface) to communicate directly with the Windows `kernel32.dll`, allowing for precise console manipulation and low-level input handling.

The project features a custom-built binary texture format called `.tuit` (Terminal User Interface Texture) for highly efficient data storage.

---

## Features & Controls

The editor is controlled entirely via keyboard. You can navigate the canvas, set RGB colors, and manage your texture files.

| Key | Action |
| :--- | :--- |
| **Arrow Keys** | Move the cursor across the canvas |
| **Enter** | Draw a pixel with the current color |
| **Tab** | Change color (Input R, G, B values) |
| **S** | Save project (as a `.tuit` file) |
| **L** | Load project (Import `.tuit` files) |
| **Backspace** | Delete pixel (resets to whitespace) |
| **ESC** | Exit the program |


--- 

## The .tuit Format

To keep file sizes minimal, PTui uses a bespoke binary format. A `.tuit` file consists of:

1. **Header (9 Bytes):** Contains the magic number `TUIT`, versioning, and canvas dimensions.
2. **Pixel Data (5 Bytes per pixel):** Efficiently stores coordinates and color data as a byte stream (`X, Y, R, G, B`).

---

## Important Notes

- **Windows Only:** This project relies on direct calls to the Windows System API (`kernel32.dll`) and is not compatible with Linux or macOS.
- **Experimental:** This is **not a finished product**, nor is it intended to be one. It is a learning project designed to explore the capabilities of PHP FFI and terminal graphics.
- **Work in Progress:** The codebase is experimental and will likely be refactored or overhauled in the future.

---

## Getting Started

1. Ensure you have **PHP 7.4+** installed.
2. The **FFI extension** must be enabled in your `php.ini` (`extension=ffi`).
3. Clone the repository:
   ```bash
   git clone [https://github.com/Milone3-35/PTui.git](https://github.com/Milone3-35/PTui.git)

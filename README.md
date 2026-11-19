# Flint Component Library
The Flint Component Library is a starting point for all web developers to build off of for new WordPress installs.

## Helpful Links
- [Library Test Site](https://8ecef22864.nxcli.io)
- [Visual Studio Code - Preferred Text Editor](https://code.visualstudio.com)
- [LocalWP - Preferred Local WordPress Environment Manager](https://localwp.com)

## Installation

### Manual Spin-Up
This method does not import pre-set database items. Best used when you want a fully fresh base install with no pre-created pages, menus, etc.
1. Download the base wp-content file and ACF json export.
2. Spin up fresh WordPress website within LocalWP using the "Create a New Site" option.
3. Move wp-content folder contents into fresh WordPress install.
4. Enable all plugins.
5. Import ACF json file via the WordPress Dashboard > ACF > Tools page.

### Blueprint Spin-Up
This method creates commonly needed starter pages and menus, configures plugin settings, etc.
1. Download the "Flint Component Library - Blueprint" zip file.
2. Navigate to the following file location on your Mac:
    - /Users/YOUR USER HERE/Library/Application Support/Local/
3. Navigate to the "blueprints" directory OR create a new one if one doesn't exist.
4. Paste the zip file into this folder. Do not extract.
5. Start LocalWP and check Blueprints tab to confirm blueprint is recognized.
6. Spin up fresh WordPress website within LocalWP using the "Create from Blueprint" option.

## Compiling Your SCSS
A SCSS compiler will be needed to use the base styles within the Component Library. Utilize Live SASS Compiler within Visual Studio Code.
It is recommended you set your base directory to "/wp-content/themes" to avoid editing plugin files.
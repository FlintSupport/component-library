# Flint Component Library
The Flint Component Library is a starting point for all web developers to build off of for new WordPress installs.

## Helpful Links
- [Library Test Site](https://lwsite.flint-group.com/library/)
- [Visual Studio Code - Preferred Text Editor](https://code.visualstudio.com)
- [LocalWP - Preferred Local WordPress Environment Manager](https://localwp.com)
- [Koala - Preferred SCSS Compiler](http://koala-app.com)

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
A SCSS compiler will be needed to use the base styles within the Component Library. Follow the steps below to set up Koala for a new site.
1. Open Koala and navigate to the settings area.
2. Turn off the setting "Automatically compile files when project is added or reloaded".
3. Press OK.
4. Press the plus button to add your new site.
5. Navigate to your new site's theme > "base-src" file and select it.
6. Select all files once loaded. Deselect the "styles.scss" and the "styles.css" files.
7. Right click the remaining highlighted files and select "Toggle Auto Compile".
8. Click on the "styles.scss" file and click "compile" in the sidebar. Wait for verification that compiling was successful.
9. Now, when you edit your SCSS files in VSCode, Koala will automatically compile for you.
## Plugin Name: PressForward
- Plugin URI: http://pressforward.org/
- Description: PressForward is a WordPress plugin built to process feeds as a feed reader, allow groups to share and discuss the items that come in and then blog about them as an integrated editorial process.
- Version: 5.8
- Author: Boone B Gorges, Aram Zucker-Scharff, Jeremy Boggs
- Author URI: http://pressforward.org/
- License: AGPLv3
- Authors' Sites: http://boone.gorg.es/, http://aramzs.me, http://jeremyboggs.net/


  Funded and maintained by Digital Scholar. Initially developed for the Roy Rosenzweig Center for History and New Media at George Mason University, with funding from the Alfred P. Sloan Foundation.

## Building this plugin

PressForward uses a number of build steps to create a usable plugin. When building the plugin from the source, you will need to run the following commands:

1. `npm install` to install the necessary dependencies.
2. `npm run build` to build NPM-controlled assets, including much of the JavaScript in the plugin. If you are developing, you can use `npm run start` to watch for changes.
3. `composer install --no-dev` to install the necessary runtime PHP dependencies. For development, you can use `composer install`, which includes dependencies required for testing and development.
4. If you are not running the `SCRIPT_DEBUG` WordPress constant, you may need to run `gulp minify-core-js && gulp minify-core-css` to generate minified versions of some of PF's assets, in order to see your changes to certain legacy files not controlled by NPM.

## Important Note

If you were running this plugin pre-2.3 and have upgraded, you need to go into the Feeder menu and click the `Switch feeds to new retrieval setup` button.

## Project goals:

-	Retrieve items within the WordPress dashboard as an independent process (no required attachment to external servers)

-	Retrieve their full content to read from within WordPress's dashboard.

-	Allow individuals or groups to mark items for consideration and discuss them within the dashboard, either from the retrieved items or via bookmarklet.

-	Easily post about retrieved items as the end point of a smoothly integrated editorial process.

-	Provide the entire process in a attractive, responsive and mobile friendly design.

-	Provide an open source back-end that is easily extendable for other developers and that can be hooked into by other independent WordPress plugins.

Testing, contributions, and feedback welcome.

Latest stable releases are available at [this project's tags page](https://github.com/PressForward/pressforward/tags).

Bugs can be reported in [the issue section](https://github.com/PressForward/pressforward/issues).

To install this plugin in WordPress, just download the latest tagged release or [this repository as a zip file](https://github.com/PressForward/pressforward/archive/master.zip) and upload it to your WordPress plugins section.

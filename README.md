# Wireshell 0.3.4
**An extendable ProcessWire command line companion**

Aiming for: a command line interface for ProcessWire (like Drush is for Drupal), for running certain (e.g. maintenance, installation) tasks quickly - without having to use the ProcessWire admin Interface.
Since ProcessWire has a powerful API and an easy way of being bootstrapped into CLIs like this, I think such a tool has a certain potential in the PW universe.

It's totally not the first approach of this kind. But: this one is easily extendable, and based on PHP (specifically: the Console component of the Symfony Framework). Every command is tidily wrapped in its own class, dependencies are clearly visible.

## Install Wireshell on your system
Wireshell requires Composer and a local PHP installation >= 5.4.0.

1. Download and install Composer (if it isn't on your system already), globally: https://getcomposer.org/doc/00-intro.md#globally
2. Run `$ composer global require wireshell/wireshell`
3. Add Wireshell to your system path: `export PATH="$HOME/.composer/vendor/bin:$PATH"` in `.bashrc` (or similar) on unix-based systems, and `%appdata%\Composer\vendor\bin` on Windows.
4. You should be able to run `$ wireshell` or `$ php wireshell` now.

## Commands

Available commands in Wireshell:

### New

#### General ProcessWire installation

```
$ wireshell new /path/where/to/install
```

Use `--dev` option for dev branch instead. Further available options (with example values):

```
--dbHost=127.0.0.1 --dbUser=db-user --dbName=db-name --dbPass=db-password --dbEngine=MyISAM --dbPort=3306
--dbCharset=utf8 --httpHosts=pw.dev --adminUrl=processwire --username=admin --userpass=abcd1234
--useremail=someone@example.com --profile=/path/to/myprofile.zip --dev --no-install --chmodDir=775 --chmodFile=644
```

If you don't pass the options, it will ask interactively or use default values.
For MAMP users, provide at least `--dbHost=127.0.0.1 --chmodDir=777`.

#### Custom profile ProcessWire installation π

```
$ wireshell new /path/where/to/install --profile=/path/to/myprofile.zip
```

You can also install profiles. Current structure of zip is as:

```
myprofile/
    site-default/
        modules
        templates
    composer.json
```

#### Download ProcessWire only

```
$ wireshell new /path/where/to/install --no-install
```

Downloads and unzips ProcessWires master branch into current directory.


### User Create

```
$ wireshell user:create {user-name}
```

Creating a user. Available options (with example values):

```
--email=otto@example.org --roles=superuser,editor
```

Use `--roles` for setting one or more user roles (given the supplied role(s) exist). Role `guest` is attached by default.

**Alias:** `$ wireshell u:c`


### Role Create

```
$ wireshell role:create {role-name}
```

Creating a role named editor.

**Alias:** `$ wireshell r:c`


#### Template Create

```
$ wireshell template:create {template-name}
```

Creating a template, and corresponding empty php file in `sites/templates`. Use `--nofile` to prevent file creation. Further available options (with example values):

```
--fields=body,website
```

Field `title` is attached by default.

**Alias:** `$ wireshell c:t`

#### Template Fields

```
$ wireshell template:fields {template-name} --fields={field-name},{field-name}
```

Assign existing field(s) to existing templates, so `--field` option is mandatory here.

**Alias:** `$ wireshell t:f`


#### Field Create

```
$ wireshell field:create {field-name}
```

Creates a text field {field-name}. Available options:

```
--label=Field --desc="Fancy description for field" --type={text|textarea|email|datetime|checkbox|file|float|image|integer|page|url}
```

**Alias:** `$ wireshell f:c`

#### Module Download

```
$ wireshell mod:download {module-name},{module-name}
```

Downloads a module.

**Alias:** `$ wireshell m:dl`


#### Module Enable

```
$ wireshell mod:enable {module-name}
```

Enables and installs a (present!) module.

**Alias:** `$ wireshell m:e`

#### Module Disable

```
 $ wireshell mod:disable {module-name}
```

Disables and uninstalls a module.

Available option:

```
$ wireshell mod:disable --rm {module-name}
```

Disables, uninstalls and deletes a module. 

**Alias:** `$ wireshell m:d`

#### Show Version

```
$ wireshell show:version
```

Outputs the version number of the current ProcessWire installation

**Alias:** `$ wireshell s:v`

#### Show Admin URL

```
$ wireshell show:admin
```

Outputs the admin page url of the current ProcessWire installation

**Alias:** `$ wireshell s:a`

#### Serve

```
$ wireshell serve
```

A wrapper for 'php -S localhost:8000, fires the small PHP web server and lets you bypass the configuration of a virtual host (the database environment must be present, though). Mainly an example for passing through console commands.

### Backup DB

```
$ wireshell backup:db
```

Connects to MySQL database and dumps its complete content into an sql file in the PW installation's root folder. When no file name provided defaulting to a date-and-time based filename.

```
--filename=some_filename
```


## Target group
Comparable to the usage context of (Laravel) Artisan and Drush, I see Wireshell as a tool aiming to help at local development, and aimed at developers who use the console anyway. So if you're developing locally and dealing with many local installations, the tool could help you in speeding things up.

## Current state
Wireshell is still in an early phase. If you encounter bugs, please report them in the project's [GitHub Issues](https://github.com/marcus-herrmann/wireshell/issues). Cheers!

## Technical Background
[The Symfony Console component](http://symfony.com/doc/current/components/console/introduction.html). NewCommand mainly consists of Taylor Otwell's [Laravel Installer](https://github.com/laravel/installer), and partly methods from [Somas PW Online installer](https://github.com/somatonic/PWOnlineInstaller) (moving all PW files a "folder up" after de-zipping the received files from GitHub). Also, big thanks to this PHP Screencast series: [https://laracasts.com/series/how-to-build-command-line-apps-in-php/]

## Potential
See the code, other commands can be added in easily since they are only classes. Symfony and the Console component are written in modern, comprehensible, maintainable PHP. Also, as far as the road-map for ProcessWire 3 goes it will be support Composer. So maybe there could be even more possibilities for Wireshell in the future.

And what made me love Drush in the first place were commands like `drush dl modulename && drush en modulname` (Downloads and installs modules without touching the GUI). I want that for PW too! :)

## Version History

* 0.3.3 Hotfix autoload path
* 0.3.2 Added Show Admin Url Command, added `bin` to composer.json
* 0.3.1 Change readme: Installation via Packagist
* 0.3.0 `NewCommand` now installs PW instead of just downloading it (thanks to @HariKT), added Commands for Fields, Modules, Backup
* 0.2.0 Added Create Template Command, extended Create User Command
* 0.1.0 Initial

## Acknowledgements
[HariKT](https://github.com/harikt) for his big contribution of a real "NewCommand" and command line based installer within Wireshell!

## Feedback please
If you have the time, maybe the slightest need for a tool like this and like to test things out - please grab a copy and go for a test drive with Wireshell and leave feedback in the ProcessWire forum and bugs as GitHub Issues. Thanks!





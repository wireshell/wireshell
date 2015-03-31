# Wireshell 0.2.0
## An extendable ProcessWire CLI

Aiming for: a command line companion for ProcessWire (like Drush is for Drupal), for running certain (e.g. maintenance, installation) tasks quickly - without having to use the ProcessWire admin Interface.
Since ProcessWire has a powerful API and an easy way of being bootstrapped into CLIs like this, I think such a tool has a certain potential in the PW universe.

It's totally not the first approach of this kind. But: this one should be easily extendable - and is based on PHP (specifically: the Console component of the Symfony Framework). Every command is tidily wrapped in its own class, dependencies are clearly visible, and so on.

### Commands

Currently, Wireshell  consists of the following basic commands:

#### Fresh installation

```
$ wireshell one-click-install /path/where/to/install --dbUser=db-user --dbName=db-name --dbPass=db-password --httpHosts=pw.dev --adminUrl=processwire --username=admin --userpass=abcd1234 --useremail=someone@example.com
```

If you don't pass the values, it will ask interactively.

#### Profile installation

```
$ wireshell one-click-install /path/where/to/install --dbUser=db-user --dbName=db-name --dbPass=db-password --httpHosts=pw.dev --adminUrl=processwire --username=admin --userpass=abcd1234 --useremail=someone@example.com --profile=/path/to.zip
```

You can also install profiles. Current structure of zip is as

```
myprofile/
    site-default/
        modules
        templates
    composer.json
```

#### New

```
    $ wireshell new
```

Downloads and unzips ProcessWires master branch into current directory. Use `--dev` option for dev branch instead: `$ wireshell new --dev`. Use `$ wireshell new foobar` to download into foobar directory.


#### Create-user

```
    $ wireshell create-user otto
```

Creating a user called otto. Use `--email=otto@example.org` option to provide the email for that user. Use `--roles=superuser,editor` for setting one or more user roles (given the supplied role(s) exist). Role `guest` is attached by default.

**Alias:** `$ wireshell c-u`


#### Create-role

```
    $ wireshell create-role editor
```

Creating a role named editor.

**Alias:** `$ wireshell c-r`


#### Create-template

```
    $ wireshell create-template contact
```

Creating a template called contact, and corresponding empty php file in `sites/templates`. Use `--nofile` to prevent file creation. Use `--fields=body,website` to attach existing fields to the template. Field `title` is attached by default.

**Alias:** `$ wireshell c-t`


#### Serve

```
    $ wireshell serve
```

A wrapper for 'php -S localhost:8000,  fires the small PHP web server and lets you bypass the configuration of a virtual host (the database environment must be present, though). Mainly an example for passing through console commands.

**Alias:** `$ wireshell s`



### Installation (on unix-based systems)
Wireshell uses Composer to manage its dependencies.

1. Download and install Composer (if it isn't on your system already), globally: [https://getcomposer.org/doc/00-intro.md#globally]
2. Download/clone Wireshell.
3. Via console, navigate into the folder where you downloaded Wireshell into
    OR
3. Put all the Wireshell files into the root of a local ProcessWire installation
4. CHMOD the "wireshell" file executable
5. `$ composer install`
6. You should be able to use the "php wireshell" command now
7. For convenience, create an alias like `alias wireshell='php /path/to/installation/wireshell'` in your ~/.bashrc.

If this little tool has a future, I'll wrap it into a PHAR or submit it to packagist.org for an easier installation process (e.g. `$ composer global require wireshell/wireshell`).


### Target group
Comparable to the usage context of (Laravel) Artisan and Drush, I see Wireshell as a tool aiming to help at local development, and aimed at developers who use the console anyway. So if you're developing locally and dealing with many local installations, the tool could help you in speeding things up.

### Current state
As of now Wireshell is totally experimental - and not yet tested on other systems than mine ;) Right now, I can't tell if it's usable outside of OS X (but since it's based on [Composer](http://getcomposer.org) and requires PHP 5.4 it should be. Certain steps and paths may differ, though).

**Please note**: Under some circumstances (MAMP PHP) you can't connect to ProcessWire API until you state `127.0.0.1` instead of `localhost` as `$config->dbHost` in config.php

### Prerequisites
Composer, local ProcessWire sites, Local PHP >= 5.4, OS X or Linux

### Technical Background
[The Symfony Console component](http://symfony.com/doc/current/components/console/introduction.html). NewCommand mainly consists of Taylor Otwell's [Laravel Installer](https://github.com/laravel/installer), and partly methods from [Somas PW Online installer](https://github.com/somatonic/PWOnlineInstaller) (moving all PW files a "folder up" after de-zipping the received files from GitHub). Also, big thanks to this PHP Screencast series: [https://laracasts.com/series/how-to-build-command-line-apps-in-php/]

### Potential
See the code, other commands can be added in easily since they are only classes. Symfony and the Console component are written in modern, comprehensible, maintainable PHP. Also, as far as the road-map for ProcessWire 3 goes it will be support Composer. So maybe there could be even more possibilities for Wireshell in the future.

And what made me love Drush in the first place were commands like `drush dl modulename && drush en modulname` (Downloads and installs modules without touching the GUI). I want that for PW too! :)

### Version History

* 0.1.0 Initial
* 0.2.0 Added Create Template Command, extended Create User Command

### Feedback please
If you have the time, maybe the slightest need for a tool like this and like to test things out - please grab a copy and go for a test drive with Wireshell and leave feedback in the ProcessWire forum and bugs as GitHub Issues. Thanks!





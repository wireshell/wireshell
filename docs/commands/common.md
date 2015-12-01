![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Common**

---

## New

General ProcessWire installation.

```sh
$ wireshell new {directory}
```

### Available options:

```sh
--dbUser : Database user
--dbPass : Database password
--dbName : Database name
--dbHost : Database host, default: `localhost`
--dbPort : Database port, default: `3306`
--dbEngine : Database engine, default: `MyISAM`
--dbCharset : Database characterset, default: `utf8`
--timezone : Timezone
--chmodDir : Directory mode, default `755`
--chmodFile : File mode, defaults `644`
--httpHosts : Hostname without `www` part
--adminUrl : Admin url
--username : Admin username
--userpass : Admin password
--useremail : Admin email address
--profile : Default site profile: `path/to/profile.zip` OR one of `beginner, blank, classic, default, languages`
--dev : Download dev branch
--devns : Download devns branch (dev with namespace support)
--sha : Download specific commit
--no-install : Disable installation
```

### Examples

Download and unzip ProcessWires master branch into current directory.

```sh
$ wireshell new /path/where/to/install --no-install
```

Custom profile ProcessWire installation.

```sh
$ wireshell new /path/where/to/install --profile=/path/to/myprofile.zip
```

Predefined profile `languages`.

```sh
$ wireshell new /path/where/to/install --profile=languages
```

Download and install `dev` branch.

```sh
$ wireshell new /path/where/to/install --dev
```

Download and install `devns` branch - ProcessWire 3.x with namespace support.

```sh
$ wireshell new /path/where/to/install --devns
```

Download and install specific commit.

```sh
$ wireshell new /path/where/to/install --sha=cffb682836517065d7dd7acf187545a4a80f1769
```

---

## Upgrade

Updates ProcessWire to latest stable release.

```sh
$ wireshell upgrade
```

### Available options:

```sh
--dev : Download dev branch
--devns : Download devns branch (dev with namespace support)
--sha : Download specific commit
--check : Just check for core upgrades.
--download : Just download core upgrades.
```

### Examples

Download and install `dev` branch.

```sh
$ wireshell upgrade --dev
```

Check if an update is available.

```sh
$ wireshell upgrade --check
A ProcessWire core upgrade is available: dev 2.7.2
```

Just download update, do not install.

```sh
$ wireshell upgrade --download
```

---

## Status

Output information on current ProcessWire installation and server environment.
You can get additional information about PHP versions and paths and/or image diagnostics

```sh
$ wireshell status
```

### Available options:

```sh
--image : get image diagnostics
--php : get diagnose about PHP versions and paths
```

### Examples

Output information about current ProcessWire installation as well as php versions/paths and image diagnostics.

```sh
$ wireshell status --php --image

 ========================= ===============================
  ProcessWire
 ========================= ===============================
  Version                   2.7.1
  Admin URL                 /processwire/
  Advanced mode             Off
  Debug mode                On
  Timezone                  Europe/Berlin
  HTTP hosts                pw.dev, www.pw.dev
  Admin theme               AdminThemeDefault
  Prepended template file   _init.php
  Appended template file    _main.php
  Database host             localhost
  Database name             pw
  Database user             xxx
  Database password         xxx
  Database port             3306
  Installation path         /Users/xxx/pw
 ========================= ===============================

 =============== =====================
  wireshell
 =============== =====================
  Version         0.5.1
  Documentation   http://wireshell.pw
  License         MIT
 =============== =====================

 ========================= ===============================
  PHP Diagnostics
 ========================= ===============================
  Version                   5.6.16
  Handler                   Command Line Interface
  System Information        xxx
  Timezone
  Max Memory                128M
  Max execution time        0
  Maximum input time        -1
  Upload Max Filesize       1G
  Post Max Size             8M
  Max Input Vars            1000
  Max Input Nesting Level   64
  XDebug Extension          is loaded (*)
 ========================= ===============================

 ===================== ====================================
  Image Diagnostics
 ===================== ====================================
  GD library            Bundled (2.1.0 compatible)
  GD JPG Support        Supported
  GD PNG Support        Supported
  GD GIF Support        Supported
  GD FreeType Support   Supported
  Exif read data        Available
  Imagick Extension     Not available
 ===================== ====================================
```

---

## Serve

A wrapper for `php -S localhost:8000`, fires the small PHP web server and lets you bypass the configuration of a virtual host.
Mainly an example for passing through console commands.

```sh
$ wireshell serve
```

---

## List

List all available commands.

```sh
$ wireshell list

Available commands:
  help              Displays help for a command
  list              Lists commands
  new               Creates a new ProcessWire project
  serve             Serve ProcessWire via built in PHP webserver
  status            Returns versions, paths and environment info
  upgrade           Checks for core upgrades.
backup
  backup:db         Performs database dump
  ...
```

---


## Help

Display help for a  given command.

```sh
$ wireshell --help {command}
```

## Examples

```sh
$ wireshell --help page:create

Usage:
 page:create [--template="..."] [--parent="..."] [--title="..."] name

Arguments:
 name

Options:
 --template            Template
 --parent              Parent Page
 --title               Title
```

---

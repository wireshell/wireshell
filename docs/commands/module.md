![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Module**

---

## Download

Downloads one or more ProcessWire modules.

```sh
$ wireshell module:download {module-class},{module-class}
```

### Available options:

```sh
--github : Download module via github; use this option if the module is not added to the ProcessWire module directory yet
--branch : Define specific branch to download from, if you use this option, --github is required
```

### Examples

Separate module names with commas to download two or more modules at once.

```sh
$ wireshell module:download FlagPages,ImageExtra
```

Download module via github.

```sh
$ wireshell module:download ContinentsAndCountries --github=justonestep/processwire-countries
```

If you want to download a branch, you have to provide the --github option, too.

```sh
$ wireshell module:download FlagPages --github=marcus-herrmann/ProcessWire-FlagPages --branch=develop
```

---

## Enable

Enable and install a module. If the module is not found in the particular installation, wireshell downloads it first.

```sh
$ wireshell module:enable {module-class},{module-class}
```

### Available options:

```sh
--github : Download module via github; use this option if the module is not added to the ProcessWire module directory yet
--branch : Define specific branch to download from, if you use this option, --github is required
```

### Examples

Separate module names with commas to enable two or more modules at once.

```sh
$ wireshell module:download FlagPages,ImageExtra
```

Download and enable module via github.

```sh
$ wireshell module:download ContinentsAndCountries --github=justonestep/processwire-countries
```

Download `develop` branch and enable module.

```sh
$ wireshell module:download FlagPages --github=marcus-herrmann/ProcessWire-FlagPages --branch=develop
```

---

## Disable

Disables and uninstalls one or more modules.

```sh
$ wireshell module:disable {class-name},{class-name}
```

### Available options:

```sh
--rm : Remove module files
```

### Examples

Deinstall modules but keep files.

```sh
$ wireshell module:disable FlagPages,ImageExtra
```

Deinstall modules and remove files.

```sh
$ wireshell module:disable FlagPages,ImageExtra --rm
```

---

## Upgrade

Upgrades given module(s).

```sh
$ wireshell module:upgrade {class-name},{class-name}*
```

\* This argument is optional. If you want to check for module updates, just skip it.

### Available options:

```sh
--check : Just check for module upgrades.
```

### Examples

Check if module upgrades are available.

```sh
$ wireshell module:upgrade

An upgrade is available for:
  - FlagPages: 0.0.8 -> 0.2.3
  - ImageExtra: 0.0.1 -> 0.0.3
```

Download and upgrade existing module `ImageExtra`.

```sh
$ wireshell module:upgrade ImageExtra

An upgrade for ImageExtra is available: 0.0.3
Downloading module ImageExtra...
  840.40 KB/840.40 KB ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓  100%

 Preparing module...

 Module ImageExtra downloaded successfully.

Module `ImageExtra` was updated successfully.
```

---

## Generate

Generates a module via [modules.pw](http://modules.pw/).

```sh
$ wireshell module:generate {class-name}
```

### Available options:

```sh
--title : Module title
--mod-version : Module version
--author : Module author
--link : Module link
--summary : Module summary
--type : Module type
--extends : Module extends
--implements : Module implements (Interface)
--require-pw : ProcessWire version compatibility
--require-php : PHP version compatibility
--is-autoload : autoload = true
--is-singular : singular = true
--is-permanent : permanent = true
--with-external-json : external json config file
--with-copyright : Adds copyright in comments
--with-uninstall : Adds uninstall method
--with-sample-code : Adds sample code
--with-config-page : Adds config page
```

### Examples

For more information on values and module authoring, visit the great generator [modules.pw](http://modules.pw/).

---

![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Module**

## Download

Downloads one or more ProcessWire modules.

```sh
$ wireshell module:download {module-class},{module-class}
```

### Available options:

```sh
--github : Download module via github; use this option if the module isn't added to the ProcessWire module directory yet
--branch : Define specific branch to download from, if you use this option, --github is required
```

### Examples

Download two modules at once.

```sh
$ wireshell module:download FlagPages,ImageExtra
```

Download module via github.

```sh
$ wireshell module:download ContinentsAndCountries --github=justonestep/processwire-countries
```

Download `develop` branch, you have to provide the `--github` option as well.

```sh
$ wireshell module:download FlagPages --github=marcus-herrmann/ProcessWire-FlagPages --branch=develop
```

---

## Enable

Enable and install a module. If the module is not found in the particular installation, wireshell downloads it.

```sh
$ wireshell module:enable {module-class},{module-class}
```

### Available options:

```sh
--github : Download module via github; use this option if the module isn't added to the ProcessWire module directory yet
--branch : Define specific branch to download from, if you use this option, --github is required
```

### Examples

Enable two modules at once.

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
--require-pw : Module's ProcessWire version compatibility
--require-php : Module's PHP version compatibility
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

For more information on values and module authioring, visit the great generator [modules.pw](http://modules.pw/).

---

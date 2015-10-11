# wireshell
**ProcessWire command-line companion**

Version 1.0.0alpha1

A command-line interface for CMS/CMF [ProcessWire](https://processwire.com) based on Symfony Console component.
Can be easily extended through ProcessWire's ability of being bootstrapped into other applications, its great [API](https://processwire.com/api/) and
Symfony Console's [modular command approach](http://symfony.com/doc/current/components/console/introduction%20%20%20%20%20%20.html).

## Documentation

Find more background, documentation and examples on [http://wireshell.pw](http://wireshell.pw)

### Quicklinks

* [Installation](http://wireshell.pw/#installation)
* [Commands: Common](http://wireshell.pw/#common)
* [Commands: Template](http://wireshell.pw/#template)
* [Commands: Field](http://wireshell.pw/#field)
* [Commands: Module](http://wireshell.pw/#module)
* [Commands: User](http://wireshell.pw/#user)
* [Commands: Role](http://wireshell.pw/#role)
* [Commands: Backup](http://wireshell.pw/#backup)
* [Contributing to wireshell](http://wireshell.pw/#contributing)
* [FAQ](http://wireshell.pw/#faq)
* [Contributors](http://wireshell.pw/#contributors)
* [Code Of Conduct](http://wireshell.pw/#code-of-conduct)
* [Support](http://wireshell.pw/#support)

## Changelog

* 0.4.1  (5/17/15) Fixed some bugs regarding PHP 5.4/5.5
* 0.4.0  (5/15/15) Big update with a lot of contributed
                  commands and interfaces: "Module Download" command, extended
                  "Module Enable" command, enhanced "User Create", added "User Delete", "User
                  List" and "User Update" (thanks @justonestep). "Module Generate" command using
                  <a href="http://modules.pw">modules.pw</a>
                  (thanks @nicoknoll), added "Status" command listing information on
                  development, ProcessWire installation, image libraries (thanks
                  @horst-n). wireshell's code and documentation were extended/cleaned up
                  by @clsource. Also 0.4.0 introduced documentation microsite,
                  [wireshell.pw](http://wireshell.pw)
* 0.3.3 (4/8/15) Just a hotfix release, regarding the autoload path
* 0.3.2 (4/8/15) Added "Show Admin Url" command (since 0.4.0 part of "Status" command), added bin to composer.json
* 0.3.1 (4/8/15) Listed wireshell on Packagist, added that in the readme
* 0.3.0 (4/6/15) "New" Command now installs PW instead of just downloading it (thanks to a great PR by @HariKT), added
commands regarding fields, modules, and database backup
* 0.2.0 (28/3/15) Added "Create Template", extended "Create User" command with role assignment on the fly
* 0.1.0 (27/3/15) Started project :) With basic commands like "New" (just downloading ProcessWire, back then),
"Create User", "Create Role"

## Licence

see LICENCE.md

![wireshell logo](http://wireshell.pw/logo.png)





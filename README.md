# wireshell
**ProcessWire command-line companion**

Version 0.5.0

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
Dates using [ISO 8601 Format](http://www.iso.org/iso/iso8601) (YYYY-MM-DD).

**0.5.1 (2015-xx-xx)** 
  - Improved `new` command, support for site profiles (`path/to/profile.zip` OR one of `beginner, blank, classic, default, languages`), autocompletion for timezone
  - Adds `field:list` command including filters `--all, --template=, --type=`
  - Adds list `field:types` command
  - Extends `field:create` command, allow custom field types
  - Extends `new` command: adds ability to specify version of ProcessWire `--dev, --devns, --sha=`
  - Extends `upgrade` command: adds ability to specify version of ProcessWire `--dev, --devns, --sha=`
  - Removes unnecessary checks of empty
  - Removes command aliases for better list overview #36
  - Fixed wrong iso format
  - Improved changelog format
  - Adds field commands `field:delete` and `field:tag`

**0.5.0 (2015-09-05)** 
  - Adds PW core upgrade command, template:delete and :list commands, page command context including creating, deleting, listing and trash-emptying.

**0.4.1 (2015-05-17)** 
 - Fixed some bugs regarding PHP 5.4/5.5

**0.4.0 (2015-05-15)** 
- Big update with a lot of contributed commands and interfaces. 
- "Module Download" command.
- Extended "Module Enable" command.
- Enhanced "User Create". 
- Added "User Delete", "User List" and "User Update" (thanks @justonestep). 
- "Module Generate" command using <a href="http://modules.pw">modules.pw</a> (thanks @nicoknoll). 
- Added "Status" command listing information on development, ProcessWire installation, image libraries (thanks
                  @horst-n).
- wireshell's code and documentation were extended/cleaned up by @clsource. 
- Also 0.4.0 introduced documentation microsite, [wireshell.pw](http://wireshell.pw).

**0.3.3 (2015-04-08)**
- Just a hotfix release, regarding the autoload path.

**0.3.2 (2015-04-08)** 
- Added "Show Admin Url" command (since 0.4.0 part of "Status" command), added bin to composer.json.

**0.3.1 (2015-04-08)** 
- Listed wireshell on Packagist, added that in the readme.

**0.3.0 (2015-04-06)** 
- "New" Command now installs PW instead of just downloading it (thanks to a great PR by @HariKT). 
- Added commands regarding fields, modules, and database backup.

**0.2.0 (2015-03-28)** 
- Added "Create Template".
- Extended "Create User" command with role assignment on the fly.

**0.1.0 (2015-03-27)** 
- Started project :) With basic commands like "New" (just downloading ProcessWire, back then), "Create User", "Create Role".

## Licence

see [LICENCE.md](/LICENCE.md)

![wireshell logo](http://wireshell.pw/logo.png)





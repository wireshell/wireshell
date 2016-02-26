![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Changelog**

Dates using [ISO 8601 Format](http://www.iso.org/iso/iso8601) (YYYY-MM-DD).

**0.6.0 (2016-02-26)**

- Updates `new` command:

   Introduces `-v` to increase the verbosity of messages
   More detailed error messages
   Makes it possible to use no password during installation

- Adds `module:upgrade` command

**0.5.2 (2016-01-18)**

- Extends `field:list` command, adds filter `--tag=tagname` and `--unused`
- Extends `page:create` command, enables importing field data from json file (thanks @samuell)
- Adds `field:edit` command
- Adds `role:delete` command
- Adds `role:list` command
- Extends `user:delete` command, adds filter `--role=rolename`
- Updates `new` command, optional filter `--directory=path`

**0.5.1 (2015-12-02)** 

- Adds `log:tail` command including filters `--limit=, --text=, --from=, --to=`
- Adds `log:list` command
- Improves `new` command, support for site profiles (`path/to/profile.zip` OR one of `beginner, blank, classic, default, languages`), autocompletion for timezone
- Adds `field:list` command including filters `--all, --template=, --type=`
- Adds list `field:types` command
- Extends `field:create` command, allow custom field types
- Extends `new` command: adds ability to specify version of ProcessWire `--dev, --devns, --sha=`
- Extends `upgrade` command: adds ability to specify version of ProcessWire `--dev, --devns, --sha=`
- Removes unnecessary empty state checks
- Removes command aliases for better list overview #36
- Fixes wrong iso format
- Improves changelog format
- Adds field commands `field:delete` and `field:tag`
- Adds tag option for `field:create` command
- Extends `user:create` command, ask for password and email address
- Extends `backup:db` command, add option `--target`

**0.5.0 (2015-09-05)** 

- Adds PW core `upgrade` command
- Adds `template:delete` and `template:list` commands
- Adds page commands: `page:create`, `page:delete`, `page:list` and `page:emptytrash`.

**0.4.1 (2015-05-17)** 

- Fixes some bugs regarding PHP 5.4/5.5

**0.4.0 (2015-05-15)** 

- Big update with a lot of contributed commands and interfaces 
- Adds `module:download` command
- Extends `module:enable` command
- Enhances `user:create`
- Adds user commands: `user:update`, `user:delete` and `user:create`
- Adds `module:generate` command using <a href="http://modules.pw">modules.pw</a> (thanks @nicoknoll)
- Adds `status` command listing information on development, ProcessWire installation, image libraries (thanks @horst-n).
- wireshell's code and documentation were extended/cleaned up (thanks @clsource)
- Also 0.4.0 introduced documentation microsite, [wireshell.pw](http://wireshell.pw)

**0.3.3 (2015-04-08)**

- Just a hotfix release, regarding the autoload path

**0.3.2 (2015-04-08)** 

- Adds "Show Admin Url" command (since 0.4.0 part of `status` command)
- Adds bin to composer.json

**0.3.1 (2015-04-08)** 

- Listed wireshell on Packagist
- Adds that in the readme

**0.3.0 (2015-04-06)** 

- `new` command now installs PW instead of just downloading it (thanks to a great PR by @HariKT)
- Adds commands regarding `fields`, `modules`, and database `backup`

**0.2.0 (2015-03-28)** 

- Adds `template:create`
- Extends `user:create` command with role assignment on the fly

**0.1.0 (2015-03-27)** 

- Started project :) With basic commands like `new` (just downloading ProcessWire, back then), `user:create`, `role:create`

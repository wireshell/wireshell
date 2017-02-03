# @todo

* add tests!
* new command: ask has been outsourced to Tools
* new/upgrade command: branch develop??
* log:tail command: ask has been outsourced to Tools
* optimize PwConnector and PwUserTools
* collect `availableWhatevers`
* Installer :: `$this->error` improve error message output
* optimize questions: add autocompletion instead of choice / text
* check `use`, remove not used ones
* change input argument / option to `isArray`
* maybe use replace output add some places (@see Downloader)
* add shell autocompletion
* rewrite `$tools->writeError(); exit(1);` to `$tools->writeErrorAndExit();`
* update packages (composer.json)

| package            | atm                  | upgrade to |
|--------------------|----------------------|------------|
| php                | >=5.4.0              | >=5.5.9    |
| symfony/console    | ~2.0                 | ~3.2       |
| symfony/filesystem | ~2.5                 | ~3.2       |
| monolog/monolog    | ~1.12                | ~1.22      |
| raulfraile/distill | ~0.9,!=0.9.3,!=0.9.4 | ~0.9.10    |

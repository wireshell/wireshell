### Installation

wireshell requires [Composer](https://getcomposer.org/), a local PHP installation >= 5.4.0, and, of course, [ProcessWire](https://processwire.com/).

Download and install Composer (if it isn't on your system already), [globally](https://getcomposer.org/doc/00-intro.md#globally):

- Run `$ composer global require wireshell/wireshell`
- Add wireshell to your system path: `export PATH="$HOME/.composer/vendor/bin:$PATH"` in .bashrc (or similar) on unix-based systems, and `%appdata%\Composer\vendor\bin` on Windows.
- You should be able to run `$ wireshell` or `$ php wireshell` now.

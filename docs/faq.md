![Wireshell Logo](assets/img/favicon-16x16.png){.logo} **Frequently Asked Questions**

> "I use MAMP PRO for Mac and get the error message 'Error: Exception: SQLSTATE[HY000] [2002]...'"

You got two possibilities here:

1. Allow network access to MAMP's MySQL: In MAMP 3's main window, go to the "MySQL" tab, and activate "Allow network access to MySQL" > From this computer. From then on you can swap localhost with 127 .0.0.1 in your local PW installations and wireshell will work.
2. [Solve it](http://stackoverflow.com/a/16688151) by changing to MAMP's mysql.sock.

---

> "I'm on Windows and either can not or wish not to put wireshell in my path, or have several PHP versions installed"

Instead of adding something to the path simply create a single `wireshell.bat` file and put it into your already existing system path.

[Read more](https://processwire.com/talk/topic/9494-wireshell-an-extendable-processwire-command-line-interface/page-2#entry93297) at this ProcessWire forum post where Horst explains his solution in detail.

---

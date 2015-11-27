![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Log**

---

## List

List all available log files.

```sh
$ wireshell log:list
```

### Examples

List log files.

```sh
$ wireshell log:list

6 logs
 ================ ================ ========= ===========
  Name             Modified         Entries   Size
 ================ ================ ========= ===========
  errors           11 minutes ago   68        12 kB
  exceptions       36 minutes ago   23        3 kB
  messages         19 hours ago     6         552 bytes
  modules          19 hours ago     134       13 kB
  session          2 days ago       3         284 bytes
  system-updater   3 days ago       16        854 bytes
 ================ ================ ========= ===========
```

---

## Tail

List the most recent lines added to the log file (sorted newest to oldest).

```sh
$ wireshell log:tail {selector}
```

### Available options:

```sh
--limit : Specify number of lines. Default: 10. (int)
--text : Text to find. (string)
--from : Oldest date to match entries. (int|string) *
--to : Newest date to match entries. (int|string) *
```

\* You can use all integers and strings that are accepted by DateTime constructor. For example: 
* -2days
* 2015-11-27
* 1448617151

### Examples

Get all log files as suggestion if you enter a non existing selector.

```sh
$ wireshell log:tail error

Log 'error' does not exist, choose one of `errors, exceptions, messages, modules, session, system-updater`
```

Output **messages** log, show 10 lines (default);

```sh
$ wireshell log:tail messages

Log messages
 ===================== ======= ================================================== ======================================
  Date                  User    URL                                                Message
 ===================== ======= ================================================== ======================================
  2015-11-26 14:39:27   admin   http://pw.dev/processwire/page/sort/               Updated sort for 2 pages
  2015-11-26 14:39:16   admin   http://pw.dev/processwire/page/sort/               Updated sort for 6 pages
  2015-11-26 14:39:09   admin   http://pw.dev/processwire/page/sort/               Updated sort for 8 pages
  2015-11-26 13:51:17   admin   http://pw.dev/processwire/page/sort/               Updated sort for 6 pages
  2015-11-26 13:51:13   admin   http://pw.dev/processwire/page/sort/               Updated sort for 6 pages
  2015-11-26 13:49:07   admin   http://pw.dev/processwire/setup/field/edit?id=44   Added tags to DB schema for 'images'
 ===================== ======= ================================================== ======================================

(6 in set, total: 6)
```

Output **modules** log, show 2 lines until yesterday.

```sh
$ wireshell log:tail modules --limit=2 --to=-1days

Log modules
 ===================== ======= ======================================= ======================================
  Date                  User    URL                                     Message
 ===================== ======= ======================================= ======================================
  2015-11-25 16:29:46   admin   http://pw.dev/processwire/module/       Failed to delete module 'Helloworld'
  2015-11-24 15:19:24   admin   http://pw.dev/processwire/xml-parser/   Saved module 'XmlParser' config data
 ===================== ======= ======================================= ======================================

(2 in set, total: 134)
```

Use `from` and `to` filters to reduce the list.

```sh
$ wireshell log:tail system-updater --limit=2 --from=2015-11-20 --to=2015-11-25

Log system-updater
 ===================== ====== ===== =================================
  Date                  User   URL   Message
 ===================== ====== ===== =================================
  2015-11-24 14:44:38                Update #13: Completed!
  2015-11-24 14:44:37                Update #13: Initializing update
 ===================== ====== ===== =================================

(2 in set, total: 16)
```

Find all **session** log entries which match "timed out".

```sh
$ wireshell log:tail session --text="timed out"

Log session
 ===================== ====== ===== =====================================================================================
  Date                  User   URL   Message
 ===================== ====== ===== =====================================================================================
  2015-11-25 16:26:14   -      ?     User 'admin' - Session timed out (session older than 86400 seconds) (IP: 127.0.0.1)
 ===================== ====== ===== =====================================================================================

(1 in set, total: 3)
```

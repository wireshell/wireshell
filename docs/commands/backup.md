![Wireshell Logo](/img/favicon-16x16.png){.logo} **Backup**

---

## Database

Connects a to MySQL database and dumps its complete content into a sql file in the PW installation's root folder.
Defaults to a date-and-time based file name when no file name is provided.

```sh
$ wireshell backup:db
```

### Available options:

```sh
--filename : Provide a file name for the dump
--target : Provide a file path for the dump (relative to ProcessWire root directory or absolute)
```

### Examples

Dump database into existing folder.

```sh
$ wireshell backup:db --filename=ymd-bak --target=db

Dumped database into `db/ymd-bak.sql` successfully.
```

Dump database into non-existing folder.

```sh
$ wireshell backup:db --filename=ymd-bak --target=nonexisting

Export failed with message: Unable to move the temporary file. Please make sure that the provided target exists.
```

You can use absolute as well as relative paths.

* db
* "../db"
* /Users/username/Downloads

```sh
$ wireshell backup:db --filename=ymd-bak --target="../db"

Dumped database into `db/ymd-bak.sql` successfully.
```

## Images

Performs images backup.

```sh
$ wireshell backup:images
```

### Available options:

```sh
--selector : can either be a page name or a page id
--field : refer to the image field that contents will be backupped (defaults to images)
--target : store the backup files into a particular folder
```

### Examples

Dump images into a specific folder.

```sh
$ wireshell backup:images --target=images

Dumped 2 images into /Users/username/Projects/pw/images successfully.
```

Dump images that refer to the field `logo`. Provide field and selector.

```sh
$ wireshell backup:images --field=logo --selector=1171

Dumped 2 images into /Users/username/Projects/pw/dump-2015-11-30-09-46-32 successfully.
```

Dump images that refer to a non-existing field `nologo`.

```sh
$ wireshell backup:images --field=nologo

No images found. Recheck your options.
```

---

![Wireshell Logo](http://wireshell.pw/favicon-16x16.png) **Page**

## Create

---

## Delete

Puts a page into the trash. Selector is either page name, page id or selector. 

```shell
$ wireshell page:delete {selector}
```

### Available options:

```shell
\--rm : forces deletes the selected page without putting it in the trash first
```

### Examples

Deletes all pages where the parent id equals 1004:

```shell
$ wireshell page:delete \--rm "has_parent=1004"
```

Deletes page with id 1005:

```shell
$ wireshell page:delete 1005
```

Deletes pages with id 1002 and 1003:

```shell
$ wireshell page:delete 1002,1003
```

Deletes pages with page name *About*:

```shell
$ wireshell page:delete About
```

---

## Empty Trash

---

## List

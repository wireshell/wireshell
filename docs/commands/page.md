![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Page**

---

## Create

Creates a new page with the given parameters

---

## Delete

Puts a page into the trash. Selector is either page name, page id or selector. 

```sh
$ wireshell page:delete {selector}
```

### Available options:

```sh
--rm : force deletes the selected page without putting it in the trash first
```

### Examples

Deletes all pages where the parent id equals 1004:

```sh
$ wireshell page:delete --rm "has_parent=1004"
```

Deletes page with id 1005:

```sh
$ wireshell page:delete 1005
```

Deletes pages with id 1002 and 1003:

```sh
$ wireshell page:delete 1002,1003
```

Deletes pages with page name *About*:

```sh
$ wireshell page:delete About
```

---

## Empty Trash

Empties ProcessWire's Trash.

---

## List

Outputs current ProcessWire install's page structure with hierarchy, titles, IDs, and page names.

---

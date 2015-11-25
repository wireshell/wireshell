![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Page**

---

## Create

Creates a new page with the given parameters

```sh
$ wireshell page:create {page-name} --template={template-name} --parent={parent-page-name} --title="Some title"
```

### Available options:

```sh
--template : template for new page
--parent : parent page name
--title : page title
```

### Examples

Creates a new page.

```sh
$ wireshell page:create example --template=basic-page --parent=home --title="Example Page"
```

Creates multiple pages.

```sh
$ wireshell page:create example-1,example-2,example-3 --template=basic-page --parent=home
```

Create new page, ask for template.

```sh
$ wireshell page:create newpage --title="Child page example 3"

Please enter the template : basic-page
```

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

## List

Outputs current ProcessWire install's page structure with hierarchy, titles, IDs, and page names.

```sh
$ wireshell page:list
```

### Available options:

```sh
--all : Get a list of all pages (recursiv) without admin-pages
--trash : Get a list of trashed pages (recursiv) without admin-pages
--level : How many levels to show
--start : start page id
```
### Examples

Lists all pages.

```sh
$ wireshell page:list

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|      |-- Child page example 1 { 1002, basic-page }
|      |-- Child page example 3 { 1014, basic-page }
|  |-- Site Map { 1005, sitemap }
```

Get a list of all (including hidden) pages (recursiv) without admin-pages.

```sh
$ wireshell page:list --all

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|      |-- Child page example 1 { 1002, basic-page }
|      |-- Child page example 3 { 1014, basic-page }
|  |-- Site Map { 1005, sitemap }
|  |-- Search { 1000, search }
|  |-- 404 Page { 27, basic-page }
```

Get a list of trashed pages (recursiv) without admin-pages.

```sh
$ wireshell page:list --trash

|-- Trash { 7, admin }
|  |-- Child page example 2 { 1004, basic-page }
```

Get a list of pages output 1 level.

```sh
$ wireshell page:list --level=1

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|  |-- Site Map { 1005, sitemap }
```

Get a list of pages start by page where id equals 1001.

```sh
$ wireshell page:list --start=1001

|-- About { 1001, basic-page }
|  |-- Child page example 1 { 1002, basic-page }
|  |-- Child page example 3 { 1014, basic-page }
```

Get a list of all pages inluding trashed pages ouput 1 level.

```sh
$ wireshell page:list --all --trash --level=1

|-- Home { 1, home }
|  |-- About { 1001, basic-page }
|  |-- Site Map { 1005, sitemap }
|  |-- Search { 1000, search }
|  |-- 404 Page { 27, basic-page }
|  |-- Trash { 7, admin }
```

![Wireshell Logo](/assets/img/page-list.gif)

---

## Empty Trash

Empties ProcessWire's trash.

```sh
$ wireshell page:emptytrash
```

---

![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **Field**

---

## List

List all available fields grouped by tag.

```sh
$ wireshell field:list
```

### Available options:

```sh
--all : Show built-in fields; by default system/permanent fields are not shown
--unused : Show unused fields
--template : Filter by template; when selected, only the fields from a specific template will be shown
--type : Filter by field type; when specified, only fields of the selected type will be shown
--tag : Filter by tag; when selected, only the fields with a specific tag will be shown
```

### Examples

List all fields grouped by tag.

```sh
$ wireshell field:list

SEO
================= ======================== ================== ===========
 Name              Label                    Type               Templates
================= ======================== ================== ===========
 seo_canonical     Canonical Link           TextLanguage       16
 seo_custom        Custom                   TextareaLanguage   16
 seo_description   Description              TextLanguage       16
 seo_image         Image                    TextLanguage       16
 seo_keywords      Keywords                 TextLanguage       16
================= ======================== ================== ===========


UNTAGGED
============================= ============================= ================== ===========
 Name                          Label                         Type               Templates
============================= ============================= ================== ===========
 autoslide                     Auto Slide                    Checkbox           1
 category                      Kategorie                     Select             1
============================= ============================= ================== ===========

(7 in set)
```

List all fields including system/permanent fields which are not shown by default.

```sh
$ wireshell field:list --all

SEO
================= ======================== ================== ===========
 Name              Label                    Type               Templates
================= ======================== ================== ===========
 seo_canonical     Canonical Link           TextLanguage       16
 seo_custom        Custom                   TextareaLanguage   16
 seo_description   Description              TextLanguage       16
 seo_image         Image                    TextLanguage       16
 seo_keywords      Keywords                 TextLanguage       16
================= ======================== ================== ===========


UNTAGGED
============================= ============================= ================== ===========
 Name                          Label                         Type               Templates
============================= ============================= ================== ===========
 autoslide                     Auto Slide                    Checkbox           1
 category                      Kategorie                     Select             1
 language                      Language                      Page               1
 language_files                Core Translation Files        File               1
 language_files_site           Site Translation Files        File               1
============================= ============================= ================== ===========


(10 in set)
```

List all fields of type `TextLanguage` which belong to the template `basic_page`.

```sh
$ wireshell field:list --type=TextLanguage --template=basic_page

 SEO
 ================= ================ ============== ===========
  Name              Label            Type           Templates
 ================= ================ ============== ===========
  seo_canonical     Canonical Link   TextLanguage   16
  seo_description   Description      TextLanguage   16
  seo_image         Image            TextLanguage   16
  seo_keywords      Keywords         TextLanguage   16
 ================= ================ ============== ===========

(4 in set)
```

---

## Create

Create a field. By default type is set to `text`;
To get a list of all available field types, run `$ wireshell field:types` ([more about types](#types)).

```sh
$ wireshell field:create {fieldname}
```

### Available options:

```sh
--label : field label
--desc : field description
--tag : field tag
--type : Type of field: text|textarea|email|datetime|checkbox|file|float|image|integer|page|url or custom field
```

### Examples

Create a text field without description,  custom label (label will be the same as the name) or tag.

```sh
$ wireshell field:create headline
```

Create an integer field with custom label, description and tag.

```sh
$ wireshell field:create headline --label="This is a headline" --desc="Some description" --tag=basic --type=integer
```

Create a field of type `CroppableImage`.

```sh
$ wireshell field:create croppable_image --type=FieldtypeCroppableImage
```

You can also skip `Fieldtype`.

```sh
$ wireshell field:create croppable_image --type=CroppableImage
```

---

## Clone

Clone an existing field.

```sh
$ wireshell field:clone {field}
```

### Available options:

```sh
--name : provide a new field name for the cloned field
```

### Examples

If you simply clone the field `headline` then the new field name will be `headline_1`. In case this name is already taken you'll get `headline_2` and so on.
You can provide a new field name for the cloned field by using the `--name` option (see example below).

```sh
$ wireshell field:clone headline
```

Provide a new field name.

```sh
$ wireshell field:clone headline --name=header
```

---

## Edit

Edit a field. Change name, label, description and/or notes.

```sh
$ wireshell field:edit {fieldname}
```

### Available options:

```sh
--name : change field name
--label : change field label
--description : change field description
--notes : change field notes
```

### Examples

Edit a text field, change name and label.

```sh
$ wireshell field:edit headline --name=header --label=Header
```

---

## Tag

Tag one or more existing fields.

```sh
$ wireshell field:tag {fieldname} --tag={tag}
```

### Available options:

```sh
--tag : tag name
```

### Examples

Tag field `headline` with `basic`.

```sh
$ wireshell field:tag headline --tag=basic
```

---

## Types

Get a list of all available field types.

```sh
$ wireshell field:types
```

### Examples

List all field types.

```sh
$ wireshell field:types

Fieldtypes

 - FieldtypeCheckbox
 - FieldtypeDatetime
 - FieldtypeEmail
 - FieldtypeFieldsetClose
 - FieldtypeFieldsetOpen
 - FieldtypeFieldsetTabOpen
 - FieldtypeFile
 - FieldtypeFloat
 - FieldtypeImage
 - FieldtypeInteger
 - FieldtypeModule
 - FieldtypePage
 - FieldtypePageTitle
 - FieldtypePassword
 - FieldtypeText
 - FieldtypeTextarea
 - FieldtypeURL
 - FieldtypeCroppableImage

(18 in set)
```

--

## Delete

Delete one or more fields at once.

```sh
$ wireshell field:delete {fieldname},{fieldname}
```

### Examples

Delete one field.

```sh
$ wireshell field:delete headline
```

Delete multiple fields at once. Provide comma-separated list.

```sh
$ wireshell field:delete headline,body,link
```

---

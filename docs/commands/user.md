![Wireshell Logo](/assets/img/favicon-16x16.png){.logo} **User**

---

## List

Lists all users.

```sh
$ wireshell user:list
```

### Available options:

```sh
--role : for filtering by user role (given the role exist)
```

### Examples

Lists all users.

```sh
$ wireshell user:list

Users: 2

 ========== =========== =========== ==================
  Username   E-Mail      Superuser   Roles
 ========== =========== =========== ==================
  admin      pw@ws.com   ✔           guest, superuser
  guest                              guest
 ========== =========== =========== ==================
```

Lists all superusers.

```sh
$ wireshell user:list --role=superuser

Users: 1

 ========== =========== =========== ==================
  Username   E-Mail      Superuser   Roles
 ========== =========== =========== ==================
  admin      pw@ws.com   ✔           guest, superuser
 ========== =========== =========== ==================
```

---

## Create

Creating a user.

```sh
$ wireshell user:create {user-name}
```

### Available options:

```sh
--email : mail address for the user 
--password : password for the user
--roles : assign user roles, comma separated (given the role exist), role`guest is attached by default
```

### Examples

Creates a new user by given email, password and role.

```sh
$ wireshell user:create editor --email="editor@ws.pw" --password=cgBG+T9e7Nu2 --roles=editor
```

Creates a new user with role guest.

```sh
$ wireshell user:create pwguest --email="guest@ws.pw" --password=ws6jem6un3V&
```

Creates a new user with roles superuser and editor.

```sh
$ wireshell user:create pwadmin --roles=superuser,editor

Please enter a email address : pwadmin@ws.pw
Please enter a password :
```

---

## Delete

Deletes a user or multiple users at once.

```sh
$ wireshell user:delete {user-name},{user-name}
```

### Examples

Deletes a user.

```sh
$ wireshell user:delete pweditor
```

Deletes multiple users.

```sh
$ wireshell user:delete pwadmin,pwguest
```

---

## Update

Updates an existing user.

```sh
$ wireshell user:update {user-name}
```

### Available options:

```sh
--email : mail address for the user 
--password : password for the user
--roles : assign user roles, comma separated (given the role exist), role`guest is attached by default
```

### Examples

Updates an user; sets new email address.

```sh
$ wireshell user:update pweditor --email=otto@example.org
```

Updates an user; sets new email, password and roles.

```sh
$ wireshell user:update pwguest --email=otto@example.org --roles=superuser,editor --password=somepass
```

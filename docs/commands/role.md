![Wireshell Logo](/img/favicon-16x16.png){.logo} **Role**

---

## List

List available roles.

```sh
$ wireshell role:list
```

### Examples

List all roles.

```sh
$ wireshell role:list

  - editor
  - guest
  - newsletter
  - superuser
```

---

## Create

Create new role(s) with the given parameters.

```sh
$ wireshell role:create {role-name,role-name}
```

### Examples

Create a new role.

```sh
$ wireshell role:create editor

Role 'editor' created successfully!
```

---

## Delete

Delete role(s) with the given parameters.

```sh
$ wireshell role:delete {role-name,role-name}
```

### Examples

Delete a role.

```sh
$ wireshell role:delete editor

Role 'editor' deleted successfully!
```

---

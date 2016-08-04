# Vhost commands

The commands in the `vhost` namespace show, add and remove vhosts.

Vhosts are a symlink to the designated, publicly accessible web-directory of an application,
and can be enabled and disabled without removing them.

## vhost:add

Adds a new vhost for an application.

This command adds a symbolic link in the directory set by `vhosts-dir`,
which links to the `web-dir` configured in the applications' `.cliconfig.json`.

```bash
lars@devnull:~$ clic vhost:add idp.vbgn.be prod/authserver
Created vhost idp.vbgn.be for prod/authserver (/home/lars/apps/prod/authserver/web)
```

## Getting vhost information

### vhost:list

Lists all vhosts known to `clic`, along with some more information.

```bash
lars@devnull:~$ clic vhost:list
+------------------+-----------------+--------+
| Vhost            | Application     | Status |
+------------------+-----------------+--------+
| idp.vbgn.be      | prod/authserver | OK     |
| hermes.s.vbgn.be | staging/hermes  | OK     |
| auth.vbgn.be     | prod/authserver | OK     |
+------------------+-----------------+--------+
```

### vhost:show

You can also get more detailed information for one vhost by passing it on the command line:

```bash
lars@devnull:~$ clic vhost:show idp.vbgn.be
Application: prod/authserver
Link: /home/boss/lars/www/idp.vbgn.be
Target: /home/boss/lars/apps/prod/authserver/web
Status: OK
```

## Enabling and disabling vhosts

Vhosts can be quickly disabled and re-enabled with a single command.
This allows to temporarily take an application offline for maintenance.

### vhost:enable

Re-enables a disabled vhost. This command makes the vhost symlink link back to its original location.

This command takes as many arguments as there are vhosts to be enabled,
or it can be applied to all known vhosts with the `--all|-A` option.

```bash
lars@devnull:~$ clic vhost:enable auth.vbgn.be
Enabled vhost auth.vbgn.be
```

```bash
lars@pow ~> clic vhost:enable -A -vvv
Removed /home/lars/www/idp.vbgn.be
Linked /home/lars/www/idp.vbgn.be to /home/lars/apps/prod/authserver/web
Enabled vhost idp.vbgn.be
Removed /home/lars/www/hermes.s.vbgn.be
Linked /home/lars/www/hermes.s.vbgn.be to /home/lars/apps/staging/hermes/.
Enabled vhost hermes.s.vbgn.be
```

If a symlink was changed externally, clic will refuse to modify it to prevent data loss.
If you are sure you want to continue, use the `--force` option.

### vhost:disable

Disables a vhost. This commands makes the vhost symlink point to an invalid location, so it is not accessible.

This command takes as many arguments as there are vhosts to be disabled,
or it can be applied to all known vhosts with the `--all|-A` option.

```bash
lars@devnull:~$ clic vhost:disable auth.vbgn.be
Disabled vhost auth.vbgn.be
```

If a symlink was changed externally, clic will refuse to modify it to prevent data loss.
If you are sure you want to continue, use the `--force` option.

## vhost:fix

Fixes vhosts that do not match their settings.

This command takes as many arguments as there are vhosts to be fixed,
or it can be applied to all known vhosts with the `--all|-A` option.

To prevent accidental modification of externally changed files (or directories that take the place of the original symlink),
no attempt is made to remove files or directories, unless the `--force|-f` option is used.

Directories containing files are only removed when also the `--recursive|-r` option is used.

```bash
lars@devnull:~$ clic vhost:fix -A
 FIX  idp.vbgn.be
 RES  Fixed vhost idp.vbgn.be
 FIX  hermes.s.vbgn.be
 RES  Fixed vhost hermes.s.vbgn.be
 FIX  auth.vbgn.be
 RES  Fixed vhost auth.vbgn.be
```

## vhost:remove

Removes vhosts.
 
This command takes as many arguments as there are vhosts to be removed,
or it can be applied to all known vhosts with the `--all|-A` option.

If a symlink was changed externally, clic will refuse to remove it to prevent data loss.
If you are sure you want to continue, use the `--force` option.

```bash
lars@pow:~$ clic vhost:remove auth.vbgn.be
Removed vhost auth.vbgn.be
```

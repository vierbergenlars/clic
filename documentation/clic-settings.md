# `.clic-settings.json`

All settings can be read with `clic config:get`,
written with `clic config:set` and removed with `clic config:unset`.

Most settings explained here are modified automatically by other commands, and should not be modified manually.

The JSON schema for this file is available at [`res/clic-settings-schema.json`](../res/clic-settings-schema.json), which
is used to validate the file after every read and before every write.

## applications

Application mapping of application names to application settings.
The application directory is determined appending the application name to the `application-dir` configuration parameter.

### repository

Optional, the git repository where the application was downloaded.

### archive-url

Optional, an URL where the archive containing the application was downloaded.

### cliconfig-override

Optional, a json file that is used in place of the applications' `.cliconfig.json` file.

### overrides

All settings from the `.cliconfig.json` file can be overriden by this object. Values that are not overrriden are
kept as specified in the application's `.cliconfig.json` file (or the `cliconfig-override` file)

## vhosts

Vhost mapping of vhost names to their settings.
The vhost symlink is determined by appending the vhost name to the `vhosts-dir` configuration parameter.

### application

Required, the application where this vhost is linked to

### disabled

Optional, defaults to false. Will be true when the vhost has been disabled.

## repositories

Repository mapping of repository names to their ssh alias and ssh private key file.
Only repositories that are cloned over ssh may be in this list, because other protocols do not use private keys for authentication.

### identity-file

Required, absolute path to the private ssh key for authentication to this repository.
Identity-files can be located anywhere on the local filesystem.

### ssh-alias

Required, name of the `.ssh/config` Host that refers to the repositories private ssh key.
The username and hostname of the repository URL is replaced by this value when doing git operations on the repository.

## config

General clic configuration parameters

### applications-dir

Optional, defaults to `$HOME/apps`. Root-directory where all applications are placed in.
The applications' name is appended to this value to determine the root-directory of the application.

### vhosts-dir

Optional, defaults to `$HOME/public_html`. Root-directory where all vhost links are placed in.
The vhosts' name is appended to this value to determine the name of the symlink for the vhost.

### ssh-dir

Optional, defaults to `$HOME/.ssh`. Directory where `ssh` keeps its configuration file and keys.

### clic-dir

Optional, defaults to `$HOME/.clic`. Directory where `clic` stores additional files and temporary files.

## global-vars

Map of variable names to their values.
These variables apply to all applications, and are overriden by a variable with the same name on application-level.

# Example

`.clic-settings.json` file with
 * default path settings
 * one deploy key for the `git@github.com:vierbergenlars/hermes.git` repository
 * 3 applications with some configuration variables
    * `prod/authserver`, cloned from a publicly accessible repository
    * `prod/owncloud`, downloaded and extracted from an archive, with an external `.cliconfig.json` file
    * `test/hermes`, cloned from a private repository with a deploy key
 * 2 vhosts:
    * `idp.vbgn.be`, a vhost for the `prod/authserver` application
    * `owncloud.vbgn.be`, a disabled vhost linked to the `prod/owncloud` applicatoin
 * 1 global variable `mysql/user` that applies to all applications

```json
{
  "config": {
    "applications-dir": "\/home\/lars\/apps",
    "vhosts-dir": "\/home\/lars\/www",
    "ssh-dir": "\/home\/lars\/.ssh"
  },
  "repositories": {
    "git@github.com:vierbergenlars\/hermes.git": {
      "ssh-alias": "bb7c101df09615efbf3f4b3f6ffb6b0a84b07c0d-hermes",
      "identity-file": "\/home\/lars\/.ssh\/id_rsa-bb7c101df09615efbf3f4b3f6ffb6b0a84b07c0d-hermes"
    }
  },
  "applications": {
    "prod\/authserver": {
      "repository": "https:\/\/github.com\/vierbergenlars\/authserver",
      "overrides": {
        "vars": {
          "mysql\/database": "lars_idp_test",
          "app\/environment": "prod",
          "mail\/transport": "mail",
          "mail\/sender": "lars@ulyssis.org",
          "app\/configured": "1"
        }
      }
    },
    "prod\/owncloud": {
      "archive-url": "https:\/\/download.owncloud.org\/community\/owncloud-9.0.3.tar.bz2",
      "cliconfig-override": "\/home\/lars\/.clic\/overrides\/1223fca28c5cb646c1c90ecfdcd9bfa2a0d4601b-f371b4ad7130e1d528e99e2f888bb7e6d36b129e.tar.gz\/.clic-owncloud.json",
      "overrides": {
        "vars": {
          "mysql\/database": "lars_owncloud",
          "app\/data_dir": "\/var\/opt\/owncloud\/data",
          "app\/trusted_domain": "owncloud.vbgn.be"
        }
    },
    "test\/hermes": {
        "repository": "git@github.com:vierbergenlars\/hermes.git"
    }
  },
  "vhosts": {
    "idp.vbgn.be": {
      "application": "prod\/authserver"
    },
    "owncloud.vbgn.be": {
      "application": "prod\/owncloud",
      "disabled": true
    }
  },
  "global-vars": {
    "mysql\/user": "lars",
  }
}
```

```bash
clic config:set global-vars[mysql/user] lars

clic application:clone git@github.com:vierbergenlars/hermes.git test/hermes

clic application:clone https://github.com/vierbergenlars/authserver prod/authserver
# ... Followed by an automatic configuration with prompts for parameters

clic application:extract https://download.owncloud.org/community/owncloud-9.0.3.tar.bz2 prod/owncloud
clic application:override:config prod/owncloud --type http https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae/archive/c5cae8c83d7f40648b9824868858d78a58ca6ca7.zip
clic application:exec install prod/owncloud
# ... Configuration with prompts for parameters

clic vhost:add idp.vbgn.be prod/authserver
clic vhost:add owncloud.vbgn.be prod/owncloud

clic vhost:disable owncloud.vbgn.be
```

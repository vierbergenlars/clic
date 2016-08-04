# Application commands

The commands in the `application` namespace show, add, modify and remove applications and their configuration.

## Adding new applications

There can be three different cases when adding a new application:
 * the application already exists: register it using `application:add`
 * the application has to be downloaded as an archive: download and extract it with `application:extract`
 * the application lives on `master` in a git repository: clone it with `application:clone`

### application:add

When an application already exists in the `applications-dir`, you can make clic aware of its existence with `clic application:add <appname>`.

If it is a git repository, its remote is automatically detected and added in the applications' configuration.
Alternatively, the git remote can also be set with the `--remote` option.

If the application is not in a git repository, clic cannot determine where it came from. It will be added to clic
without any extra metadata.
If the application originates from an archive that was downloaded from somewhere, the source can be set with the `--archive-url` option.

```bash
# The directory ~/apps/authserver contains a git repository cloned from https://github.com/vierbergenlars/authserver
lars@devnull:~$ clic application:add authserver
Registered application authserver with repository https://github.com/vierbergenlars/authserver

# the directory ~/apps/owncloud contains an application downloaded from the OwnCloud website
lars@pow ~> clic application:add owncloud --archive-url https://download.owncloud.org/community/owncloud-9.0.3.zip
Registered application owncloud (downloaded from https://download.owncloud.org/community/owncloud-9.0.3.zip)
```

### application:extract

When you have an application packaged in a `zip`, `rar`, `tar`, `tar.gz`, `tar.bz2`, `tar.xz` or `tar.Z` format,
you can have clic take care of extracting it (and also of downloading it if you want)

Leaving out the application name as second parameter will result in a guess of the application name from the filename 
of the archive.

The applications' `post-extract` script is automatically run after unpacking of the archive. To prevent this script
from running automatically, use `--no-scripts`.

```bash
lars@devnull:~$ clic application:extract https://github.com/vierbergenlars/authserver/archive/master.tar.gz prod/authserver
Registered application prod/authserver (downloaded from https://github.com/vierbergenlars/authserver/archive/master.tar.gz)
  RUN  ln -sf archive .clic-scripts/active && mkdir -p .clic-scripts/tmp && wget $($CLIC config:get "applications[$CLIC_APPNAME][archive-url]" | sed 's/\.zip$/.tar.gz/') -O .clic-scripts/tmp/update.tar.gz && $CLIC application:exec install "$CLIC_APPNAME"
  RUN  bash .clic-scripts/configure.sh </dev/tty >/dev/tty 2>/dev/tty
 Name of the database:
 >
# Application set-up script is now asking for more information to configure authserver after it was downloaded and extracted.
```

```bash
# Alternatively, you download your archive yourself
lars@devnull:~$ wget https://github.com/vierbergenlars/authserver/archive/master.tar.gz -O /tmp/authserver-master.tar.gz
lars@devnull:~$ clic application:extract /tmp/authserver-master.tar.gz authserver
Created directory /home/lars/apps/authserver
Registered application authserver (without repository)
[...]
```

With the `--override` option, you can have `clic` add an override file for the application immediately after archive extraction, before the `post-extract`
script is run. (See [`application:override`](#application-override) for details)

```bash
lars@devnull:~$ clic app:extract https://download.owncloud.org/community/owncloud-9.1.0.zip -o https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae prod/owncloud
Downloading https://download.owncloud.org/community/owncloud-9.1.0.zip...OK
Extracting /home/lars/.clic/tmp/e5a999cf698bd7c25f43206cfa26c18c807a72e8/owncloud-9.1.0.zip to /home/lars/apps/prod/owncloud...OK
Registered application prod/owncloud (downloaded from https://download.owncloud.org/community/owncloud-9.1.0.zip)
  RUN  'git' 'clone' 'https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae' '/home/lars/.clic/overrides/03cb957bf94f8acaa2d0bc30a862381a33ad316d-9fcbad1a0f8025b98d7e875f614fdaae'
  ERR  Cloning into '/home/lars/.clic/overrides/03cb957bf94f8acaa2d0bc30a862381a33ad316d-9fcbad1a0f8025b98d7e875f614fdaae'...
  ERR
  RES  Command ran successfully
Registered /home/lars/.clic/overrides/03cb957bf94f8acaa2d0bc30a862381a33ad316d-9fcbad1a0f8025b98d7e875f614fdaae/.clic-owncloud.json as override file for prod/owncloud
  RUN  bash "$CLIC_APPCONFIG_DIR"/install.sh </dev/tty >/dev/tty 2>/dev/tty
 Name of the database:
 >
# Application set-up script is now asking for more information to configure owncloud after it was downloaded and extracted.
```

### application:clone

An application can also be cloned straight from its git repository.

When cloning a repository over ssh, a deploy key is automatically generated and registered for that repository
with the [`repository:generate-key` command](repository.md#repository-generate-key).
The public key is shown so you can add it to the repositories' deploy keys before proceeding.
Generating a deploy key can be skipped by using the `--no-deploy-key` option, but clic is smart enough to generate
only one deploy key per repository, even when it is cloned multiple times.

Leaving out the application name as second parameter will result in a guess of the application name from the repository name.

The applications' `post-clone` script is automatically run after unpacking of the archive. To prevent this script
from running automatically, use `--no-scripts`.

```bash
lars@devnull:~$ clic app:clone git@github.com:vierbergenlars/authserver.git authserver
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCh5Z18k6vNOLkVHZXkJz19vJojtXsHuXa4Kk0GmwDZEBOVjFpd7zdptCSsi3x3YZI9UUgkNNmk0FfobJTlvOmv2aEmdb2iDt7QD6aENMBpfXes+ifYm+R8SxAPMFfX+2ajtgJff6Fo7alqRZF/9Nqic6K43307rKqglAzdXivYwDpZYTO0zEIKpWSXkMEK3bnCUwuopPGW7URSY9I5uPFJ16y02kC65suk4ImPervpGLZZJrGmz7Ukn3HKwpPCHOAoA27cdAJpEIZb5pku9ePcQHE9Wj/Z8b8uJGzXmtwNe61gIHDUYhQI9222byRPU36YDn8vEHR6tcBScrlQ9n5t clic-deploy-key-f31ee4bb0b3a131fff33eb67d70e192c8a80f27d-authserver@devnull
Registered private key /home/lars/.ssh/id_rsa-f31ee4bb0b3a131fff33eb67d70e192c8a80f27d-authserver for repository git@github.com:vierbergenlars/authserver.git
Please set the public key printed above as a deploy key for the repository
 Is the deploy key uploaded? (yes/no) [yes]:
 >
  RUN  'git' 'clone' 'f31ee4bb0b3a131fff33eb67d70e192c8a80f27d-authserver:vierbergenlars/authserver.git' '/home/lars/apps/authserver'
  ERR  Cloning into '/home/lars/apps/authserver'...
  ERR
  RES  Command ran successfully
Registered application authserver with repository git@github.com:vierbergenlars/authserver.git
  RUN  ln -sf git .clic-scripts/active && $CLIC application:exec install "$CLIC_APPNAME"
  RUN  bash .clic-scripts/configure.sh </dev/tty >/dev/tty 2>/dev/tty
 Name of the database:
 >
# Application set-up script is now asking for more information to configure authserver after it was downloaded and extracted.
```

With the `--override` option, you can have `clic` add an override file for the application immediately after archive extraction, before the `post-extract`
script is run. (See [`application:override`](#application-override) for details)

## Executing application scripts

Every application can define custom shell scripts in its `.cliconfig.json` file.

These shell scripts are executed with `clic application:execute <appName> <scriptName>`

The first parameter is the name of the application to execute the script from.
The second parameter is the name of the script to execute (as defined in the `scripts` object of the applications' `.cliconfig.json`).

```bash
lars@devnull:~$ clic application:execute prod/authserver install
  RUN  bash .clic-scripts/install.sh </dev/tty >/dev/tty 2>/dev/tty
  RUN  bash .clic-scripts/configure.sh </dev/tty >/dev/tty 2>/dev/tty
 Name of the database:
 >
[...]
  RES  Command ran successfully
```

## Application variables

Configuration data for the application can be stored in application variables. Only strings can be stored in a variable.

Variables can be stored in a couple of locations. In order of precedence:
 * variables stored in the applications' override section in `.clic-settings.json`
 * variables stored in the applications' `.cliconfig.json` file (or in the file that overrides it)
 * global variables, in the `.clic-settings.json` configuration file.

Variables are looked-up from top to bottom. The first location where the variable can be found will be used.

Setting variables always writes to the applications' override section in `.clic-settings.json`, as the local configuration
for the application is stored there.

### application:variable:get

Application variables can be read with the `clic application:variable:get` command.

```bash
lars@devnull:~$ clic application:variable:get prod/authserver mysql/user
lars
```

Additionally, filter functions can be applied to the result with `--filter`.

Multiple filters can be chained, and must accept the output of the previous function as first and only argument.
The first filter in the chain must accept the type of the variable (string, integer or \stdClass for objects).
The last filter in the chain must return a string, array, \stdClass or \Traversable of values to be printed, or null to print nothing.

Typical usage is quoting the variable, by using `json_encode` or `var_export`

```bash
lars@devnull:~$ clic application:variable:get prod/authserver mysql/user --filter=json_encode
"lars"
```

### application:variable:set

Application variables are set or changed with the `clic application:variable:set` command.

```bash
lars@devnull:~$ clic application:variable:set prod/authserver mysql/database lars_authserver
```

This way a value can be set on the command line. For embedding in scripts, it is also possible to ask the user
for the value to set interactively by omitting the 3rd argument.

To only set the variable if it does not exist yet as an application-level override, use the `--if-not-exists` option.
To only set the variable if it does not exist yet as a global variable, use the `--if-not-global-exists` option.

Additional options allow to configure how this prompt looks and acts:
 * `--default`: The default value that will be used when the user does not enter anything at the prompt.
 * `--default-existing-value`: The default value is set to the current value of the variable, if it exists.
 * `--description`: The question that is asked to the user (defaults to the variable name)

## Getting application information

### application:list

Lists all applications known to `clic`, along with some more information.

```bash
lars@devnull:~$ clic application:list
+-----------------+--------------------------------------------------+-------------+--------+
| Application     | Repository                                       | Vhosts      | Status |
+-----------------+--------------------------------------------------+-------------+--------+
| staging/hermes  | git@github.com:vierbergenlars/hermes.git         |             | OK     |
| prod/authserver | https://github.com/vierbergenlars/authserver.git | idp.vbgn.be | OK     |
+-----------------+--------------------------------------------------+-------------+--------+
```

### application:show

You can also get more detailed information for one application by passing it on the command line:

```bash
lars@devnull:~$ clic application:show prod/authserver
Path: /home/lars/apps/prod/authserver
Repository: https://github.com/vierbergenlars/authserver.git
Vhost: idp.vbgn.be (OK)
Status: OK
```

## application:override

Sometimes an application has a `.cliconfig.json` file that does not suit your needs, or does not have one at all.

An alternative configuration file for the application can be specified with this command. The `.cliconfig.json` file packaged
with the application itself will be ignored completely.

The first argument is the application to override the configuration file from, the second argument is the location
of the file that will be used as alternative configuration file.

The configuration file argument can be a json file or an archive (zip, rar, tar, tar.gz, tar.bz2, tar.xz or tar.Z),
and may be the path to a file on the local filesystem, a git repository or a http(s) url where the file can be downloaded.

If the type is not detected correctly, you can use the `--type` option to explicitly set the correct resource type (`file`, `git` or `http`).

```bash
lars@devnull:~$ clic application:override prod/owncloud https://gist.github.com/vierbergenlars/9fcbad1a0f8025b98d7e875f614fdaae
```

To remove the alternative configuration file, run the command with an empty second parameter: `clic application:override prod/owncloud ''`

## application:remove

Removes an application from `clic`.

By default the application is only removed from the application index of `clic`, and is not removed from disk.
With the `--purge` option, the application directory is removed completely.

It is not allowed to remove applications that are referred to by vhosts, the remove will fail when such vhosts still exist.
You can have the vhosts removed together with the application by using the `--remove-vhosts` option.

```bash
lars@devnull:~$ clic application:remove --purge prod/authserver --remove-vhosts
Removed vhost idp.vbgn.be
Remove application prod/authserver...Purging /home/lars/apps/prod/authserver
OK
```

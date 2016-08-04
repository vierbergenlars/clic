# clic

User-friendly PHP application deployment and set-up.

`clic` keeps track of applications and their configuration parameters, helps with setting up deploy keys for private
git repositories, and helps with publishing the application by linking the application to a publically accessible location.

## License

`clic` is licensed under the terms of the MIT license.

See the [license.md](https://github.com/vierbergenlars/clic/blob/master/license.md) file for a full copy of the license.

## Installation

### Option 1: As a phar file (with phar-composer)

The preferred way to install `clic` is with the [`phar-composer` tool](https://github.com/clue/phar-composer).
You will first need to [install](https://github.com/clue/phar-composer/#install) phar-composer. It is a very useful tool
that allows you to create runnable phar files from Composer packages.

If you have phar-composer installed, you can run:

```bash
sudo phar-composer install vierbergenlars/clic
```

to have it build and install the phar file in your `$PATH`, which allows you to run it as `clic` from the commandline.

Or you can run

```bash
phar-composer build vierbergenlars/clic
```
and copy the resulting phar file manually to where you want it.

### Option 2: As a phar file (download)

If you do not want to install phar-composer, you can download the `clic.phar` file from the [latest release](https://github.com/vierbergenlars/clic/releases/latest).

Don't forget to make the file executable with `chmod +x clic.phar` and move it somewhere within your `$PATH`.

### Option 3: As a global composer installation

`clic` can be installed with [composer](https://getcomposer.org).

```bash
composer global require vierbergenlars/clic
```

You can use this to install CLI utilities globally, all you need
is to add the `COMPOSER_HOME/vendor/bin` dir to your `PATH` env var.

COMPOSER_HOME is `c:\Users\<user>\AppData\Roaming\Composer` on Windows
and `/home/<user>/.composer` on unix systems.

### Option 4: From source

`clic` can also be installed by downloading and extracting [an archive](https://github.com/vierbergenlars/clic/archive/master.zip) or cloning the repository.

Next, run a `composer install` within the application's directory to install dependencies.

Finally, add the `bin/` folder to your `PATH`, or to symlink `bin/clic` to a folder in your `PATH`.

## Initial configuration

Initial configuration and setup of directories are set up with `clic config:init`

Global configuration is stored in `~/.clic-settings.json` (or the file referred to with the `--config` option) 
More information about the file format is available in the [documentation](https://github.com/vierbergenlars/clic/tree/master/documentation/clic-settings.md),
though you probably do not need to edit this file manually.

## Usage

You clone/extract *applications* from a repository or tarball. Then a script to complete the install and enter configuration
parameters is launched as defined in the applications' `.cliconfig.json` file.
The application may then be made publicly accessible by adding *vhost* that refers to the application. This is a symlink
that has the applications' `web-dir` as target. This way files that should not be publicly accessible are kept out of
the document root.

### Applications

Commands to manipulate applications are available within the `application` namespace

```
application:add           Add an existing application
application:clone         Create a new application from remote repository
application:execute       Executes application scripts
application:extract       Create a new application from an archive
application:list          Lists all applications
application:override      Changes the configuration file for an application
application:remove        Removes an application
application:show          Shows application information
application:variable:get  Shows variable value for an application
application:variable:set  Sets variable value for an application
```

Details are available in the [application section of the documentation](https://github.com/vierbergenlars/clic/tree/master/documentation/application.md)

### Vhosts

Commands to manipulate vhosts are available within the `vhost` namespace.

```
vhost:add                 Add web-accessible entrypoint to an application
vhost:disable             Disables one or more vhosts
vhost:enable              Enables one or more vhosts
vhost:fix                 Fixes one or more vhosts
vhost:list                Lists all vhosts
vhost:remove              Remove web-accessible entrypoint to an application
vhost:show                Shows vhost information
```

Details are available in the [vhost section of the documentation](https://github.com/vierbergenlars/clic/tree/master/documentation/vhost.md)

### Repositories

Ssh deploy keys for private repositories can also be managed with `clic`. They are available in the `repository` namespace.

```
repository:add            Add deploy key to a repository
repository:generate-key   Generates deploy key to a repository
repository:list           Lists all repositories
repository:remove         Remove deploy key from a repository
repository:show           Shows repository information
```

Details are available in the [repository section of the documentation](https://github.com/vierbergenlars/clic/tree/master/documentation/repository.md)

### Configuration

All configuration parameters can be manipulated with the commands in the `config` namespace.
These are low-level commands, most of the time higher-level commands are used to manipulate these values.

```
config:get                   Shows configuration value
config:init                  Initialize configuration
config:set                   Sets configuration values
config:unset                 Removes configuration values
```

Details are available in the [config section of the documentation](https://github.com/vierbergenlars/clic/tree/master/documentation/config.md)

## License

[MIT](https://github.com/vierbergenlars/clic/tree/master/license.md)

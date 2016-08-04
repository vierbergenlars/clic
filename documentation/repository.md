# Repository commands

The commands in the `repository` namespace show, add and remove repository configurations.

Per-repository settings are currently the an ssh deploy-key and its corresponding ssh alias.

## Adding a repository

You can register an existing deploy key with a repository, or have clic generate a new one for you
(this is done automatically when cloning a repository with [`application:clone`](application.md#application-clone), if no key is present yet).

### repository:add

Registers an existing ssh key as a deploy key for your repository.

By default, a random hexadecimal ssh alias is generated and inserted into your ssh config file.
This ensures the proper private key is used when doing git operations on the remote repository.

It is possible to use your own alias for the repository with the `--alias` option.

```bash
# rsa_key is an existing private ssh key
lars@devnull:~$ clic repository:add git@github.com:vierbergenlars/authserver.git rsa_key
Registered private key /home/lars/rsa_key for repository git@github.com:vierbergenlars/authserver.git
```

### repository:generate-key

Generates a new ssh deploy key.

A target filename can optionally be passed, it defaults to a random name starting with 'id_rsa-' in the configured `ssh-dir` directory.

Adding a comment to the key is possible with the `--comment|-C` option.
Immediately adding the generated key to a repository is possible with the `--target-repository|-R` option.
The public key can be shown on the console by using the `--print-public-key|-P` option.

```bash
# Note: The public key is actually printed on one line, but is hard-wrapped here to avoid an overly large scroll-bar
lars@devnull:~$ clic repository:generate-key ~/.ssh/id_rsa-authserver -P -C "Authserver deploy key" -T="git@github.com:vierbergenlars/authserver.git"
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDFLORlNFSX4hjI1XP9Q5k4pDjMbxJKDn5Q9qN8tzC4JYQCKGSW4ujpc8KQRyYL/h87zkoU5ErYVhm5IqbF
i7eMKozgZXUQ5T/MusLW/kLtwmxIDBc7AOjhuUE8431BMQ4ouJu13p4wxl+dfKZph+9i/a51pSntiqgkkOe4sbhfH7b5y6TDtW1M28HEBMRA3EOQuNfSOMvz
z8hsu+1R0ukdhxKLKityHh2/qjNc4pD9XIJpdr0KbWRm5CjpM9B2zmHvqt4bDi7DI3l/vNJKBBMqOSiz7Z342j6WvqvALxjCHE+lNrkWQmgl52XdJ03ATV4E
Y6jfy+l4iDavxbWLBVNr Authserver deploy key
Registered private key /home/lars/.ssh/id_rsa-authserver for repository git@github.com:vierbergenlars/authserver.git
```

To only get the ssh key as output of the command, use the `--quiet|-q` option, which will suppress printing of all status messages.

## Getting repository information

### repository:list

Lists all repositories known to `clic`, along with some more information.

```bash
lars@devnull:~$ clic repository:list
+----------------------------------------------+---------------------------------------------------------------------+--------+
| Repository                                   | SSH key                                                             | Status |
+----------------------------------------------+---------------------------------------------------------------------+--------+
| git@github.com:vierbergenlars/hermes.git     | /home/lars/.ssh/id_rsa-2227b573be395151a7f5e9935f024ce724ae921d     | OK     |
| 2227b573be395151a7f5e9935f024ce724ae921d     | 2048 MD5:08:70:08:9d:8f:39:ea:a9:d1:69:15:ce:ea:ff:92:62 (RSA)      |        |
|                                              | /home/lars/.ssh/id_rsa-2227b573be395151a7f5e9935f024ce724ae921d.pub |        |
| git@github.com:vierbergenlars/authserver.git | /home/lars/.ssh/id_rsa-4b0078424a5514c8610617a2d83c303f27a09aa0     | OK     |
| 4b0078424a5514c8610617a2d83c303f27a09aa0     | 2048 MD5:7a:0a:6a:42:7f:26:99:35:6d:68:50:09:d8:3b:4b:22 (RSA)      |        |
+----------------------------------------------+---------------------------------------------------------------------+--------+
```

### repository:show

You can also get more detailed information for one repository by passing it on the command line:

```bash
lars@devnull:~$ clic repository:show git@github.com:vierbergenlars/authserver.git
Private key file: /home/boss/lars/.ssh/id_rsa-4b0078424a5514c8610617a2d83c303f27a09aa0
Fingerprint: 2048 MD5:7a:0a:6a:42:7f:26:99:35:6d:68:50:09:d8:3b:4b:22 AB CDE (RSA)
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDMw35/LkjbpI56wCJMHA4f13jRTLu0aNKWBnp5lE4eaEG58F6I0+UHeIfJpjspe99iTYgyrl8vx9DS9zQqa2VGGmRFjNtRBnMIaMGwIiLT/ZDwb/CXkGGvhKc1gYYhmR+sHNUd9GhGa7SqwPiA2+PSC86HomSfkIlbGj5gcw49i3OqJwxEf6TN4pzUEmkH2Yg8I9+X21ZBEsemh2++S3UWHZffKweGgVjK+KoEAG6n67VDPYUn/8r/Zw93RCMWn/2T47pSZ45nRYdmX30axA+BK2W2fALwdxE9W/LffY71TnTb9sAhcRNCjJ+G09pKh5y0qt2y/wDxRAMOdCKLpPoF AB CDE
Status: OK
```

To only get the ssh key as output of the command, use the `--quiet|-q` option, which will suppress printing of all other output.


## repository:remove

Removes a repository, its deploy keys and ssh alias.

```bash
lars@devnull:~$ clic repository:remove git@github.com:vierbergenlars/authserver.git 
 Are you sure you want to remove the ssh key for "git@github.com:vierbergenlars/authserver.git"? This action is irreversible. (yes/no) [yes]:
 >
Removed file /home/lars/.ssh/id_rsa-4b0078424a5514c8610617a2d83c303f27a09aa0
Removed file /home/lars/.ssh/id_rsa-4b0078424a5514c8610617a2d83c303f27a09aa0.pub
Removed repository git@github.com:vierbergenlars/authserver.git
```

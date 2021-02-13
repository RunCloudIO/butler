# About Butler

Butler is a replacement for [Laravel Valet](https://github.com/laravel/valet) that works inside Docker. So, no more tinkering with brew, fixing things, etc when things go south. Since Butler is based on Laravel Valet, please take a look at [Laravel Valet Documentation](https://laravel.com/docs/master/valet) before using Butler.

Butler codebase is 100% taken from Laravel Valet and a few codebase was inspired (taken) from [Laravel Sail](https://github.com/laravel/sail). Since Valet was designed for MacOS, a few tweak from main code need to be changed inside Butler to give the same experience as using Laravel Valet.

# Butler Manifesto

I hate it when things doesn't work on my machine after I have setup everything. Things just don't work sometimes and I believe you have face the same problem. When I create Butler, it is because brew service give ton shit of error. Not to mention, when upgrading your Mac sometimes you face new error. 

Like every programmer, instead of fixing broken things. Why not make a new solution? I like how Laravel Valet works but to deal with errors (not causing by Laravel Valet), it just consumed my daily life experience in developing my product. To combat this, Butler was born. 

To make things simple inside your development machine, Butler should make your life easy without having to install PHP, Nginx or DNSmasq inside your Mac. Thus, keeping your Mac clean and you can easily setup your development environment when you buy a new Mac with your hard earned money.

Butler aim to replicate the simplicity of using Laravel Valet and thus I will not add other cool shit feature to Butler if it does not available inside Laravel Valet. Any **PR** that add a feature which not exist inside Valet will **be rejected** without hesitation. This project is my first project in Docker because I want to learn how to use Docker. There will be part of this code which you will feel like a **n00b** that code this project, and that is because it is. If you have any improvement to make, don't hesitate to make PR or the noob code will stay forever.

# Todo

- [ ] valet share
- [ ] valet fetch-share-url
- [ ] valet unsecure --all

# TLDR;

```
$ ./install.sh
$ cd www/default
$ mkdir mysite
$ cd mysite
$ echo "<?php phpinfo();" > index.php
$ # update DNS to 127.0.0.1
$ open -a "Google Chrome" http://mysite.test
```

# Installation

Requirement:

1. [Docker](https://www.docker.com/)

To start with Butler, clone this repository and run `install.sh`

```
$ git clone https://github.com/RunCloudIO/butler.git
$ cd butler
$ ./install.sh
```
**IMPORTANT PART**. After the installation succeeded, change your DNS inside System Preferences > Network > Advanced > DNS to **`127.0.0.1`**. Failure to do so **will prevent** you from running custom domain TLD.


To update, just `git pull` and then run `install.sh` again. If you have **moved** the folder to a different path, simply run `install.sh` inside the new path to make sure `butler` command know about it.

# Tips & Tricks

To make your life easier, it is better to use your daily command rather than invoking `butler` directly. Before doing this, make sure you have **REMOVE** Laravel Valet completely. So, here it is. Edit `~/.bash_profile` and append these lines:

```
alias valet="butler valet"
alias php="butler php"
alias composer="butler composer"
```

After that, `source ~/.bash_profile` and you may use `valet`, `php`, `composer` just like you have installed them natively.

# Usage

### Basic usage
You will have `butler` installed inside `/usr/local/bin/butler`. Thus, you can invoke `butler` command anywhere.

`butler` command without any argument is same as running `valet` without any argument. You also can run `butler valet` if you prefer it that way.

Valet default path was set to `/var/www/default`. So you may create your 1st project inside there, which is inside host is `www/default` directory.

### Butler specific command

```bash
$ butler start # start butler process
$ butler reload # reload processes if you change .env or docker-compose.yaml
$ butler reset # reset everything to original state but keep your item in mounted folder
$ butler restart # restart all butler services
$ butler stop # stop all butler services
```

### Using PHP and Composer

Since you are not installing any PHP inside your Mac, you can run php using `butler php`. Thus, running `php artisan migrate` can be run using `butler php artisan migrate`. Or if you prefer the shortest way `butler artisan migrate`, or `butler art migrate`.

Same as using composer, you can run `butler composer create-project laravel/laravel example-app` to install Laravel.

**PLEASE TAKE NOTE** that running PHP based command (php, valet, composer, artisan) only supported on **`DEFAULT_WWW_PATH`** that you have set inside **`.env`**. 

Running PHP based command ouside of `DEFAULT_WWW_PATH` folder is equivalent to running the command inside `/var/www/`. If you need to run outside that folder, you need to manually mount your folder and interact directly with the Docker container.

### Change PHP version

Since we are using Docker, **changing PHP version** is **easier** than ever. You just need to update `.env` by changing `BUTLER_PHP_VERSION` to either version *8.0, 7.4, 7.3, 7.2, 7.1, or 7.0*. Then just issue `butler reload` for it to take effect.

### Laravel Valet park and link command

You can only run `butler valet park` or `butler valet link` inside`DEFAULT_WWW_PATH` defined in `.env`. If you run these command outside the `DEFAULT_WWW_PATH` directory, it will automatically run your command inside `/var/www` in the container. 

You may create another folder inside `DEFAULT_WWW_PATH` and register it as new parked paths or linked path. So you can divide your codebase per project basis inside here. E.g: `/var/www/{defaults,project1,project2,project3,link1,link2}`.
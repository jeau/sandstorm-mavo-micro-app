# Build Sandstorm micro-apps with mavo

This package makes it easy to create micro-applications for [Sandstorm](http://sandstorm.io), a self-hostable web productivity suite. 

It's a prototype of a [Sandstorm](http://sandstorm.io) application based on Mavo. [Mavo](http://mavo.io) is an original project developed by [Lea Verou](http://lea.verou.me) and [CSAIL team at MIT](https://www.csail.mit.edu).

_"Mavo is a language that lets you create and edit interactive websites and apps with nothing more than HTML. Mavo allows you to make websites editable and saveable to the cloud via Github, DropBox or other services"_ … and Sandstorm, why not!

Sandstorm provides the ability to customize existing micro-applications, create new ones, store files and publish static pages.

## Micro-app package specifications 

Mavo extends the syntax of HTML to describe Web applications that manage, store, and transform data. Inside this Sandstorm package, a micro-application has a simple and codeless structure. 

Micro-app template 

- `name` — root directory micro-app name
    - `config.html` — config file, the config data are editable with [Mavo](http://mavo.io) to update the micro-app settings
    - `config.yaml` — config data from `config.html`  
    - `images` — directory for multimedia uploads from the pages  
    - `pages` — directory for pages (one sub-directory per page) 
        - `home` — home page of the micro-application, can't be deleted 
            - `body.html` — content of the page    
            - `data.json` — mavo data of the page
        - `page` — a second page with the same sub-structure
            - `body.html` — content of the page    
            - `data.json` — mavo data of the page
        - ...

So, this micro-app could be interpreted and edited possibly in other context.

It is quite possible to run the micro-app outside of Sandstorm with the internal php server, this is convenient to build the micro-app skeleton. To do that, from the root directory, launch :

- `php -S localhost:8080`
- access the micro-app with your browser.

## Features

For each each Sandstorm grain :

- admin grain can edit, create and delete pages ; publish and unpublish static pages
- editor can update data with mavo interface
- viewer can view pages

## Issues

- Need to refresh the page after changing settings.
- The textarea form field is permanently in edit mode.

## Developing

Launch a local [Sandstorm](http://sandstorm.io) instance :

- Install [Vagrant](https://www.vagrantup.com/downloads.html).
- Install [VirtualBox ](https://www.virtualbox.org/wiki/Downloads).
- Install [Vagrant-SPK](https://github.com/sandstorm-io/vagrant-spk).
- Clone this repo and from the top-level directory, run:
    - `vagrant-spk vm up` to start a virtual Linux machine containing Sandstorm
    - `vagrant-spk dev` to make this app available in development mode, then your system is running a Sandstorm instance:
        - You should visit it in your web browser now by opening this link: http://local.sandstorm.io:6080
        - Log in with a dev account, choose *Alice (admin)* as the user to sign in with
        - Click the *Micro App* icon, then *Create new instance* to spin up a new micro-app instance.

### Packaging

To create a Sandstorm package (SPK) file, containing the app and all its dependencies. 

- Stop the `vagrant-spk dev` server : type `Ctrl-C`.
- To create the SPK file, run: `vagrant-spk pack ~/export-path/package.spk`

You can upload this spk file inside your own Sandtorm server to test it.

## Components 

This application uses the following libraries:

- [mavo](http://mavo.io/) — an HTML-based language for creating web applications without programming. 
- [spyc](https://github.com/mustangostang/spyc) — a simple YAML loader/dumper class for PHP.
- [Concise CSS](http://concisecss.com/) — a lightweight CSS framework.

## TODO

- manage configuration parameters
- import / export micro-apps 
- git access to Sanstorm grain
- replace the php internal web server by a serious one 

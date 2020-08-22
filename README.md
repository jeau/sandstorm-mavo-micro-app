# Build Sandstorm micro-apps with mavo

This package makes it easy to create micro-applications for [Sandstorm](http://sandstorm.io), a self-hostable web productivity suite. 

It's a prototype of a [Sandstorm](http://sandstorm.io) application based on Mavo. [Mavo](http://mavo.io) is an original project developed by [Lea Verou](http://lea.verou.me) and [CSAIL team at MIT](https://www.csail.mit.edu).

_"Mavo is a language that lets you create and edit interactive websites and apps with nothing more than HTML. Mavo allows you to make websites editable and saveable to the cloud via Github, DropBox or other services"_ … and Sandstorm, why not!

Sandstorm offers the ability to create new derivative micro-applications and customize existing.

## Micro-app package specifications 

Mavo extends the syntax of HTML to describe Web applications that manage, store, and transform data. 

It is quite possible to run the micro-app outside of Sandstorm with Go, this is convenient to build the micro-app skeleton. To do that, from the root directory, launch :

- `go run microapp.go`
- access the micro-app with your browser: `http://localhost:8000`

## Features 

For each each Sandstorm grain (in progress) :

- admin grain can edit, create and delete pages ; publish and unpublish static pages
- editor can update data with mavo interface
- viewer can view pages

## Issues 

- the Sandstorm backend storage of mavo data is not yet implemented

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
- Add your public key in `.sandstorm/sandstorm-pkgdef.capnp`. You can generated it with 'vagrant-spk keygen' command.   
- To create the SPK file, run: `vagrant-spk pack ~/export-path/package.spk`



You can upload this spk file inside your own Sandtorm server to test it.

## Components 

This application uses the following libraries:

- [Mavo](http://mavo.io/) — an HTML-based language for creating web applications without programming. 
- [Goland](https://golang.org/) — Go pieces of code by the Go Authors and community .

## TODO

- learning Go ;)
- Go storage backend for Mavo data 
- Documentation 
- Implement Sandstorm roles for each each grain (may be limited by Mavo)  :
  - **Admins** can edit code pages ; publish and unpublish static pages
  - **User** can update data with mavo interface
  - **Visitor** can view pages



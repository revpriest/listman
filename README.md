# Nextcloud App Tutorial

[![PHPUnit GitHub Action](https://github.com/nextcloud/app-tutorial/workflows/PHPUnit/badge.svg)](https://github.com/nextcloud/app-tutorial/actions?query=workflow%3APHPUnit)
[![Node GitHub Action](https://github.com/nextcloud/app-tutorial/workflows/Node/badge.svg)](https://github.com/nextcloud/app-tutorial/actions?query=workflow%3ANode)
[![Lint GitHub Action](https://github.com/nextcloud/app-tutorial/workflows/Lint/badge.svg)](https://github.com/nextcloud/app-tutorial/actions?query=workflow%3ALint)

This is a simple mail-list manager app.
 
## Try it 
To install it change into your Nextcloud's apps directory:

    cd nextcloud/apps

Then run:

    git clone https://github.com/revpriest/listman.git listman

Then install the dependencies using:

    make composer

## Frontend development

The app tutorial also shows the very basic implementation of an app frontend using [Vue.js](https://vuejs.org/). To build the frontend code after doing changes to its source in `src/` requires to have Node and npm installed.

- 👩‍💻 Run `make dev-setup` to install the frontend dependencies
- 🏗 To build the Javascript whenever you make changes, run `make build-js`

To continuously run the build when editing source files you can make use of the `make watch-js` command.

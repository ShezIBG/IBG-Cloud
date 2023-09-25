# Eticom v2

  - [Development](#development)
    - [Prerequisites](#prerequisites)
    - [Test server](#test-server)
    - [IDE](#ide)
  - [Testing and deployment](#testing-and-deployment)
    - [Server configuration](#server-configuration)
    - [Testing](#testing)
    - [Build and deploy to live server](#build-and-deploy-to-live-server)
    - [Build and deploy to test VM](#build-and-deploy-to-test-vm)
  - [Folders and files](#folders-and-files)
    - [Top level](#top-level)
  - [Features](#features)
    - [White labeling and theming](#white-labeling-and-theming)
    - [Payment systems](#payment-systems)
 
# Development

## Prerequisites

To build and deploy Eticom Cloud, you need a Mac or Linux-based system, or other POSIX-compliant system that can run shell scripts.

You also need to install **Node.js** with **NPM**.

Install **Angular CLI** on your development machine.

```
$ npm install -g @angular/cli
```

## Test server

The test server is a local virtual machine running on **VirtualBox**, backup can be found in the handover folder. It's a standard LAMP server running Ubuntu with everything set up. The Eticom Cloud web folder is at `/var/www/html/eticom`.

The server's default IP address is **192.168.10.19** for running at Eticom offices. It should use a bridged connection to the LAN, so that it's accessible by other computers on the network. To test payment system webhooks, the office public IP port 8080 should forward to port 80 of the test server. If the test server's IP address is changed, it must also be updated in the following source files:

 - `.vscode/ftp-sync.json`
 - `build.sh`
 - `configurator/src/app/api.service.ts`
 - `v3/src/app/api.service.ts`

This test server should be up and running while changing code in this repository to make sure files are kept in sync by the IDE.

## IDE

I'm using **Visual Studio Code** as the development environment. There are configuration files in the handover folder for the **ftp-sync plugin v0.3.9 by Łukasz Wroński**, which automatically copies files to the test VM when saved.

# Testing and deployment

## Server configuration

The build script assumes that you have set up SSH connections to your local development server as well as the live eticomcloud.com webserver. This means you should be able to connect just by running `ssh eticomcloud.com` on the command line without any authentication prompts. To set this up, add your authentication details to `/etc/ssh/ssh_config`.

On the server, you need to create a config file in the `config` folder based on `config/config.php.sample`. There should be one config file **for each domain or IP address you use to connect** and they should be named `config_DOMAIN.php`. See [White labeling and theming](#white-labeling-and-theming) section for more details.

On the live server, you also need to set up `git` to be able to pull the master branch of the eticom_v2 repository in the main folder.

## Testing

To test Eticom Cloud in its entirety, open `https://192.168.10.19/eticom` in your browser. As the IDE should keep the files in sync, the old framework will always be up to date, but in order to test the v3 or configurator modules, you will need to deploy them first (see next section).

**In full testing mode, the `$auto_auth` variable in `api/v3.php` must be `false`.**

---

You can also test the **v3** and **configurator** Angular applications separately with live reload.

```sh
# Go to the correct subdirectory (v3 or configurator)
$ cd v3

# Run Angular in test mode with live reload
$ ng serve
```

This command will keep running while you're developing and will reload the browser on code changes. For **v3**, open `http://localhost:4200/{module}` in your browser, e.g. `http://localhost:4200/billing`. For **configurator**, open `http://localhost:4200?id={building ID}`.

**In Angular testing mode, the `$auto_auth` variable in `api/v3.php` must be `true`.**

---

*If the `$auto_auth` variable has wrong value for the testing mode, you might get logged out, redirected or even pushed into a redirect loop.*

## Build and deploy to live server

If you've made changes to any of the Angular modules (v3 and configurator):

```sh
# First time only. Initial installation of NPM packages needed to build all Angular modules
$ ./build.sh npm

# Build all Angular modules (v3 and configurator)
$ ./build.sh angular

# Copy all Angular modules to live server
$ ./build.sh deploy --prod
```

If you've made changes to any backend PHP files, make sure you push your changes to the master branch of the eticom_v2 GitHub repo, then:

```sh
# Connect to eticomcloud.com
$ ssh eticomcloud.com

# Go to the main Eticom Cloud web folder
$ cd /var/www/eticom02

# Pull latest changes via git
$ git pull

# If you've added any new PHP Composer packages, you need to install them
$ ./composer.phar install
```

## Build and deploy to test VM

The Angular modules can be tested separately, but you'll need to deploy them to the test server if you want to test the whole package.

Angular modules are built the same way as for production, then deployed without the `--prod` flag:

```sh
# First time only. Initial installation of NPM packages needed to build all Angular modules
$ ./build.sh npm

# Build all Angular modules (v3 and configurator)
$ ./build.sh angular

# Copy all Angular modules to test server
$ ./build.sh deploy
```

Usually there is no need to copy PHP files, as Visual Studio Code should automatically copy them over when changed.

# Folders and files

Gives you an overview of what is what and where everything is in the code base.

## Top level

Folders are in **bold**, files are *italics*.

| Name | Description |
| ---- | ----------- |
| **ajax** | API endpoints for the old Eticom Cloud. Also contains code for old modal popups for the old system. |
| **api** | API endpoint for new Angular-based applications, including v3, configurator and even the SmoothPower portal. |
| **branding** | Asset files for white-labeling (Elanet). |
| **config** | Application configuration files. |
| **configurator** | Angular application for the building configurator. This is an internal tool used only by Eticom. Elanet also has access to it with limited toolbox. |
| **data/widgets** | Dashboard widgets for the old system. All old dashboards are built from these. |
| **inc** | Include files to initialise the application session and old headers and JavaScript scripts. |
| **lib** | All PHP class files that are used throughout the application. |
| **print** | Everything to do with generating on-the-fly PDF printouts: invoices, utility summaries, Elanet contracts. |
| **scripts** | Terminal scripts written in PHP. Also contains cron jobs to be run on the web server. |
| **user-content** | This folder is for the server only, not deployed. It stores all images uploaded by users. |
| **v3** | Angular application for all modern modules. Has to be compiled and deployed onto the server. |
| **vendor** | Not checked in. PHP composer modules mostly for third-party payment providers. |
| **view** | PHP code for dashboards and screens in the old modules. |
| *.htaccess* | Rewrite rules and redirects. |
| *barcode.php* | Endpoint to generate barcodes on the fly. Returns binary image file. |
| *build.sh* | Build script used to build and deploy Eticom Cloud modules. |
| *gocardless_auth.php* | It handles the response from GoCardless when a payment gateway is authorised by a client. |
| *gocardless_customer_flow.php* | It handles the response from GoCardless when a customer authorises their Direct Debit mandate. |
| *gocardless_webhook.php* | It handles GoCardless events that are sent whenever a payment's or mandate's status changes. |
| *index.php* | Eticom Cloud base entry point, redirects to v3 login screen. |
| *login.php* | Logs a user in or redirect to v3 login screen if not authorised. |
| *stripe_auth.php* | It handles the respose from Stripe when a client authorises their payment gateway. |
| *stripe_webhook.php* | It handles events from Stripe that are sent whenever a payment's status changes. |
| All other PHP files | They are containers for the old framework for all new modules that are developed in the v3 Angular application. All v3 modules are loaded in a full-screen iframe but also keep the top navigation bar from the old framework. |

# Features

## White labeling and theming

Eticom Cloud has been white labeled for Elanet. To avoid having to split the codebase or having to keep two deployments in sync, changes have been made to the system to make switching as painless as possible.

Both Eticom Cloud (eticomcloud.com) and Elanet (portal.elanet.co.uk) use the same underlying database, which means you can use the same login details on both, and have access to all features.

Application config files in the `config` folder must be defined per domain / hostname to make sure the correct styling is used when the site is accessed. In the config, the `BRANDING` define can either be `eticom` or `elanet`. The `CUSTOM_CSS` define is used to include a custom CSS file in the old framework, which overrides styling of the top bar and login screens.

In the **v3 Angular application**, `index.html` is renamed to `index.php` during deployment, which makes it possible to expose the current branding in a public JavaScript variable.

The following changes have been made to the system that depend on branding:

 - HTML `<title>` elements and company logos are customised.
 - Carousel widget on home screen shows Elanet's logo instead of Eticom features.
 - Twitter feed widget shows the Elanet website in an iframe.
 - Emails sent by the system (password reset, etc.) are sent via Elanet's email server and doesn't mention Eticom.
 - Elanet has their own version of the home screen in the database (`home-elanet`), which changes the layout a bit.
 - Customer account pages in v3 (account/account-details) has additional Elanet only features.

You can find all of these changes by searching for "branding" in the code.

These changes also needed some restructuring of the **v3** styles under `v3/src/assets/less`:

| File | Description |
| ---- | ----------- |
| *styles.less* | It's the main entry point of v3 styles. It includes `elements.less` and `core.less` once, then includes `themed.less` twice per branding (once for dark, once for light theme). Dark theme is achieved by changing the light and dark colour variables. |
| *elements.less* | It was included with the base theme as is, mostly mixins used by the main theme files. |
| *core.less* | Same structure as `themed.less`, but includes only CSS values that doesn't involve colour. This means it only needs to be included once. |
| *themed.less* | Same structure as `core.less`, but includes only CSS values that involve colours. Font colours, borders, backgrounds. This is included twice per branding. |

By default the whole application uses the light theme by applying the `.theme-light` CSS class to the `<html>` element. You can switch any part of the page to dark theme by adding the `.theme-dark` class to a container.

## Payment systems

Eticom Cloud uses **GoCardless** and **Stripe** to take payments from our clients, as well as take payments on behalf of our clients from their customers. GoCardless takes Direct Debit payments which takes a number of days to complete, while Stripe takes credit/debit card payments that are taken instantly.

Both payment systems work in similar ways. The main Eticom account is info@eticom.co.uk. When a customer wants to take a payment through our platform, they have to go to their company settings page / payment gateways tab. They then add a payment gateway and click the authorise button. This will redirect them to the payment provider where they need to either log into an existing account or create a new one.

Once authorised, they get redirected back to Eticom Cloud to `stripe_auth.php` or `gocardless_auth.php`, where we save their authorisation keys.

Whenever we take a payment on a customer's behalf, we make a request to our payment account, passing through the customer's authorisation keys. Even though Stripe takes the payment instantly, it works the same way as GoCardless, as in it reports the change in the payment's status via webhooks. Webhooks are processed in `stripe_webhook.php` and `gocardless_webhook.php`.

Money that we collect on behalf of our customers will go directly into their GoCardless and Stripe accounts, we simply initiate the transaction between the two parties. We don't charge any additional fees per transation.
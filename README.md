# ALIXAR ERP & CRM

----------------------------------------------------------------------------------
This document is pending review.
Alxarafe is based on a Dolibarr fork and is an **unstable application** at the moment.
----------------------------------------------------------------------------------


[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alxarafe/alixar/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/alxarafe/alixar/?branch=main)
[![Build Status](https://scrutinizer-ci.com/g/alxarafe/alixar/badges/build.png?b=main)](https://scrutinizer-ci.com/g/alxarafe/alixar/build-status/main)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/Alxarafe/Alixar/issues?utf8=✓&q=is%3Aopen%20is%3Aissue)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg?style=flat-square)](https://php.net/)

Alixar is an evolution of [Dolibarr](https://github.com/Dolibarr/dolibarr).

## LICENSE

Alixar is released under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version (GPL-3+).

See the [COPYING](https://github.com/alxarafe/alixar/blob/develop/COPYING) file for a full copy of the license.

Other licenses apply for some included dependencies. See [COPYRIGHT](https://github.com/alxarafe/alixar/blob/develop/COPYRIGHT) for a full list.

## INSTALLING

### Simple setup

Releases can be downloaded from [official website](https://alxarafe.com/).

### Advanced setup

You can use a web server and a supported database (MariaDB, MySQL or PostgreSQL) to install the standard version.

On GNU/Linux, first check if your distribution has already packaged Alixar.

#### Generic install steps

- Verify that your installed PHP version is supported [see PHP support](https://wiki.alixar.org/index.php/Releases).

- Uncompress the downloaded .zip archive to copy the `alixar/htdocs` directory and all its files inside your web server root or get the files directly from GitHub (recommended if you know git as it makes it easier if you want to upgrade later):

  `git clone https://github.com/alxarafe/alixar -b x.y`     (where x.y is the main version like 3.6, 9.0, ...)

- Set up your web server to use `alixar/htdocs` as root if your web server does not already define a directory to point to.

- Create an empty `htdocs/conf/conf.php` file and set *write* permissions for your web server user (*write* permission will be removed once install is finished)

- From your browser, go to the alixar "install/" page

  The URL will depend on how your web configuration directs to your alixar installation. It may look like:

  `http://localhost/alixar/htdocs/install/`

  or

  `http://localhost/alixar/install/`

  or

  `http://youralixarvirtualhost/install/`

- Follow the installer instructions

### SaaS/Cloud Setup

If you lack the time to install it yourself, consider exploring commercial 'ready-to-use' Cloud offerings (refer to https://saas.alixar.org). Keep in mind that this third option comes with associated costs.

## UPGRADING

Alixar supports upgrading, usually without the need for any (commercial) support (depending on if you use any commercial extensions). It supports upgrading all the way from any version after 2.8 without breakage. This is unique in the ERP ecosystem and a benefit our users highly appreciate!

Follow these step-by-step instructions to seamlessly upgrade Alixar to the latest version:

- At first make a backup of your Alixar files & then [see](https://wiki.alixar.org/index.php/Installation_-_Upgrade#Upgrade_Alixar)
- Verify that your installed PHP version is supported by the new version [see PHP support](https://wiki.alixar.org/index.php/Releases).
- Overwrite all old files from the 'alixar' directory with files provided in the new version's package.
- At your next access, Alixar will redirect you to the "install/" page to follow the upgrade process.
  If an `install.lock` file exists to lock any other upgrade process, the application will ask you to remove the file manually (you should find the `install.lock` file in the directory used to store generated and uploaded documents, in most cases, it is the directory called "*documents*").

## WHAT'S NEW

See the [ChangeLog](https://github.com/alxarafe/alixar/blob/develop/ChangeLog) file.

## FEATURES

### Main application/modules (all optional)

- Third-Parties Management: Customers, Prospects (Leads) and/or Suppliers + Contacts
- Members/Membership/Foundation management

 Product Management

- Products and/or Services catalogue
- Stock / Warehouse management + Inventory
- Barcodes
- Batches / Lots / Serials
- Product Variants
- Bill of Materials (BOM)
- Manufacturing Orders (MO)

 Customer/Sales Management

- Customers/Prospects + Contacts management
- Opportunities or Leads management
- Commercial proposals management (online signing)
- Customer Orders management
- Contracts/Subscription management
- Interventions management
- Ticket System (+ Knowledge management)
- Partnership management
- Shipping management
- Customer Invoices/Credit notes and payment management
- Point of Sale (POS)

 Supplier/Purchase Management

- Suppliers/Vendors + Contacts
- Supplier (pricing) requests
- Purchase Orders management
- Delivery/Reception
- Supplier Invoices/Credit notes and payment management
- INCOTERMS

 Finance/Accounting

- Invoices/Payments
- Bank accounts management
- Direct debit and Credit transfer management (European SEPA)
- Accounting management
- Donations management
- Loan management
- Margins
- Reports

 Collaboration

- Shared calendar/agenda (with `ical` and `vcal` import/export for third-party tools integration)
- Projects & Tasks management
- Event organization
- Ticket System
- Surveys

 HR - Human Resources Management

- Employee leave management
- Expense reports
- Recruitment management
- Employee/staff management
- Timesheets

### Other application/modules

- Electronic Document Management (EDM)
- Bookmarks
- Reporting
- Data export/import
- Barcodes
- LDAP connectivity
- ClickToDial integration
- Mass emailing
- RSS integration
- Social platforms linking
- Payment platforms integration (PayPal, Stripe, Paybox...)
- Email-Collector

(around 100 modules available by default, 1000+ addons at the official marketplace Dolistore.com)

### Other general features

- Multi-Language Support (Localization in most major languages)
- Multi-users and groups with finely-grained rights
- Multi-Currency
- Multi-Company (by adding an external module)
- Very user-friendly and easy to use
- Customizable dashboards
- Highly customizable: enable only the modules you need, add user personalized fields, choose your skin, several menu managers (can be used by internal users as a back-office with a particular menu, or by external users as a front-office with another one)
- APIs (REST, SOAP)
- Code that is easy to understand, maintain and develop (PHP with no heavy framework; trigger and hook architecture)
- Support a lot of country-specific features:
  - Spanish Tax RE and ISPF
  - French NPR VAT rate (VAT called "Non Perçue Récupérable" for DOM-TOM)
  - Canadian double taxes (federal/province) and other countries using cumulative VAT
  - Tunisian tax stamp
  - Argentina invoice numbering using A,B,C...
  - Greece fetch customer vat details from AADE, all invoice types, MyData(external free module)
  - ZATCA e-invoicing QR-Code
  - Compatible with [European directives](https://europa.eu/legislation_summaries/taxation/l31057_en.htm) (2006/112/CE ... 2010/45/UE)
  - Compatible with data privacy rules (Europe's GDPR, ...)
  - ...
- Flexible PDF & ODT generation for invoices, proposals, orders...
- ...

### System Environment / Requirements

- PHP
- MariaDB, MySQL or PostgreSQL
- Compatible with all Cloud solutions that match PHP & MySQL or PostgreSQL prerequisites.

See exact requirements on the [Wiki](https://wiki.alixar.org/index.php/Prerequisite)

### Extending

Alixar can be extended with a lot of other external applications or modules from third-party developers available at the [DoliStore](https://www.dolistore.com).

## WHAT ALIXAR CAN'T DO YET

These are features that Alixar does **not** yet fully support:

- Tasks dependencies in projects
- Payroll module
- Native embedded Webmail, but you can send email to contacts in Alixar with e.g. offers, invoices, etc.
- Alixar can't do coffee (yet)

## DOCUMENTATION

Administrator, user, developer and translator's documentation are available along with other community resources in the [Wiki](https://wiki.alixar.org).

## CONTRIBUTING

This project exists thanks to all the people who contribute.
Please read the instructions on how to contribute (report a bug/error, a feature request, send code, ...)  [[Contributing](https://github.com/alxarafe/alixar/blob/develop/.github/CONTRIBUTING.md)]

## CREDITS

Alixar is the work of many contributors over the years and uses some fine PHP libraries.

See [COPYRIGHT](https://github.com/alxarafe/alixar/blob/develop/COPYRIGHT) file.

## NEWS AND SOCIAL NETWORKS

Follow Alixar project on:

- [GitHub](https://github.com/alxarafe/alixar)

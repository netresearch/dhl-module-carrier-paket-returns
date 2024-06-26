DHL Paket Returns Shipping Carrier Extension
============================================

The DHL Paket Returns extension for Magento® 2 integrates the _DHL Retoure API_
API into the order processing workflow.

Description
-----------
This extension enables merchants to request return labels for orders
via the [DHL Retoure API](https://entwickler.dhl.de/en/) (DHL Geschäftskundenversand-API).

Requirements
------------
* PHP >= 8.2
* PHP JSON extension

Compatibility
-------------
* Magento >= 2.4.6

Installation Instructions
-------------------------

Install sources:

    composer require dhl/module-carrier-paket-returns

Enable module:

    ./bin/magento module:enable Dhl_PaketReturns
    ./bin/magento setup:upgrade

Flush cache and compile:

    ./bin/magento cache:flush
    ./bin/magento setup:di:compile

Uninstallation
--------------
To unregister the carrier module from the application, run the following command:

    ./bin/magento module:uninstall --remove-data Dhl_PaketReturns
    composer update

This will automatically remove source files, clean up the database, update package dependencies.

Support
-------
In case of questions or problems, please have a look at the
[Support Portal (FAQ)](http://dhl.support.netresearch.de/) first.

If the issue cannot be resolved, you can contact the support team via the
[Support Portal](http://dhl.support.netresearch.de/) or by sending an email
to <dhl.support@netresearch.de>.

License
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2023 DHL Paket GmbH

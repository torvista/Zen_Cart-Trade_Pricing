# DO NOT USE: THIS WORK IN PROGRESS WAS INTERRUPTED!

# Zen Cart - Trade Pricing
For advanced users only!

## Compatibility
This was developed in a ZC158 installation...so may need some ZC158 notifiers adding to a ZC157 fileset and may need some other bits from ZC158. Debugs will tell all.
This was developed for my site and not intended as a plugin for all. It is made public for advanced users. Report bugs but try and fix them yourself / do not expect instant support.

## Features/Useage
1. Define a customer as having a trade discount level. There may be multiple levels 1/2/3 etc.
2. Define a corresponding discount range per manufacturer: 10%-15%-20%.
Note the customer pricing level(eg. "2") must correspond to a discount level (eg. "15%").
It does supports absolute discount like 10-15% which doesn't make much sense...but that came from the original Dual Pricing concept. Maybe of use with specific products.
3. A multiple discount range on a product overrides that set by manufacturer.

You can switch off the observers interfering by going to any admin configuration page, then changing the gID=6 at the end of the address:

https://SITE_ADMIN/index.php?cmd=configuration&gID=6

This shows all the one-off constants that don't have their own admin page.

Set "Plugin Trade Pricing - enabled" to false.

## Limitations
1. Ignores any specials or discount pricing: just applies the trade discount to the original price + attribute prices.
2. Uses observers for nearly all modifications apart from template files.

## Code
### Admin - manufacturers
Adds field for discount structure. Uses a modified manufacturers.php file with observers that may be added to ZC158.

### Admin - customers 
Adds field for discount level. Uses a modified customers.php with extra formatting around the observer-added section, that may be added to ZC158.

### Admin - product edit
Adds field for discount level.

## Installation
### Database
There are several new fields to be added.
The uninstall sql shows the new fields that are added:
- ALTER TABLE `customers` DROP `customers_tp`;
- ALTER TABLE `manufacturers` DROP `manufacturers_tp`;
- ALTER TABLE `products` DROP `products_tp`;
- ALTER TABLE `orders_products` DROP `products_rrp_tp`;
- DELETE FROM `configuration` WHERE configuration_key = "PLUGIN_TRADE_PRICING";

The installation is executed by ADMIN\includes\classes\observers\auto.PluginTradePricingAdmin.php

uncommenting
// $this->install();

and refreshing the admin page.

Adding discounts to manufacturers, and product exceptions is too tedious to do manually.
So there are code blocks to do this by sql, uncommenting

- $this->install_test_data();
- $this->install_real_data();

Look at the end of the file to customise for your shop.
I made these statements by copying and pasting the table from the manufacturers page into a spreadsheet and using formulas to create the SQL queries.

### Files
Observers are used as much as possible.
Use file comparison software to see what is what.

When I have modified a core file, I leave an adjacent copy of the original in place for easy comparison, eg.
- manufacturers.php - modified
- manufacturers.158 php - original for comparison

## Possible Bugs
I use this with my Direct Debit payment module. Maybe there will be debugs associated with this missing.
I use this with my heavily modified DynamicPrice Updater. Maybe there will be debugs associated with this missing.
Why haven't I checked first? There is a limit to how much I am willing to do altruistically and I have done enough, I think!

## Debugging
How prices are calculated and displayed in Zen Cart is a world of pain.
The code is littered with lots of debugging output that is enabled by setting
- $debug_tpr = true;

in
\includes\functions\extra_functions\plugin_trade_pricing_functions.php

Ugly, but necessary for me trying to understand what the hell is going on. I expect odd pricing cases to come to light with use, so am leaving them in for now.

The observers do not modify the existing pricing code, but replace it with simpler calculations using the original code as a reference.

You'll need a spreadsheet to design and test prices and their correct calculations in the various scenarios: taxed/untaxed, attributes, priced-by-attributes or not. Do not trust someone else's code.

## Updates
I am a shop-owner, not a paid developer with clients. I did this for my shop and it is not currently live, as I first need other functionality in place for trade clients. So, you may find bugs before me.
Report bugs you have fixed and ones you cannot.
Updates will be posted to GitHub when possible.

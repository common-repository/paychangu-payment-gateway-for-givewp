=== Paychangu Payment Gateway for GiveWP ===
Contributors: paychangu, wafsite
Tags: givewp, donation, payment, paychangu
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Paychangu Gateway Add-on for Give

== Description ==

Accept Credit card, Debit card, Airtel Money and TNM Mpamba.

= Receive money from anyone with Our Borderless Payment Collection Platform. Payout straight to your bank account or mobile money. =

Signup for an account [here](https://in.paychangu.com/register)

== PayChangu API (External Service) ==
This plugin uses PayChangu API to process GiveWP payments via PayChangu Payment Gateway.

1. https://api.paychangu.com/payment -> Send payment request with payment data to PayChangu
2. https://api.paychangu.com/verify-payment -> Get payment results from PayChangu

Please refer to PayChangu Terms and Conditions [here](https://paychangu.com/terms), and Paychangu Privacy Policy [here](https://paychangu.com/privacy-policy).

== Installation ==

= Automatic installation =
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of Give, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

= Manual Installation =
1. Download the plugin zip file.
2. Login to your WordPress Admin. Click on "Plugins > Add New" from the left menu.
3. Click on the "Upload" option, then click "Choose File" to select the zip file you downloaded. Click "OK" and "Install Now" to complete the installation.
4. Activate the plugin.
For FTP manual installation, [check here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).


== Setting Up the Paychangu Add-On ==

= Setup =
1. Log into your WordPress admin Dashboard.
2. On the left side navigation menu, hover over "Donations" and click on "Settings".
3. From this page, click the "Payment Gateways" tab and choose "Paychangu" link. You will be presented with the Paychangu Settings Screen.

= Paychangu Account Information =
1. "Prefix":  Enter a prefix for your invoice numbers. If you use your Paychangu account for multiple stores ensure this prefix is unique as Paychangu will not allow orders with the same invoice number.
3. "Public Key": Enter the Public Key from your Paychangu Account. This field is required.
4. "Secret Key": Enter the Secret Key from your Paychangu Account. This field is required.

== Changelog ==

= 1.0.0 =
* Initial version

= 1.1.0 =
* Update to support the new API version
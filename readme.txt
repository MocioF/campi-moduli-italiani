=== Campi Moduli Italiani ===
Contributors: mociofiletto
Donate link: https://paypal.me/GiuseppeF77
Tags: contact form 7, wpforms, comuni italiani, codice fiscale, firma digitale
Requires at least: 4.7
Tested up to: 5.7.1
Requires PHP: 5.6
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html

Plugin to create useful fields for Italian sites, to be used in the modules produced with Contact Form 7 and WPForms.

== Description ==
This plugin creates form tags for Contact Form 7 and form fields WPForms.

= Contact Form 7 =
4 form-tags (and corresponding mail-tags) are available in this version:
* [comune]: creates a series of select for the selection of an Italian municipality
* [cf]: creates a field for entering the Italian tax code of a natural person
* [stato]: creates the ability to select a state
* [formsign]: creates the possibility to digitally sign the e-mails sent with a pair of keys attributed to each individual form

= WPForms =
2 fields types are available:
* Cascade selection of an Italian municipality (returning Istat's municipality code as value)
* A field to select a state (returning Istat's country code as value)

== Data used ==
At the time of installation, the plugin downloads the data it uses from the Istat and from the Revenue Agency websites. This data can be updated from the administration console.
Downloading and entering data into the database takes several minutes: be patient during the activation phase.
The selection of the municipalities was created starting from the code of https://wordpress.org/plugins/regione-provincia-comune/

This plugin uses data made available by ISTAT and the Agenzia delle entrate (Italian revenue agency).
In particular, data made available at these URLs are acquired and stored:

* https://www.istat.it/it/archivio/6789
* https://www.istat.it/it/archivio/6747
* https://www1.agenziaentrate.gov.it/documentazione/versamenti/codici/ricerca/VisualizzaTabella.php?ArcName=COM-ICI

The data published on the ISTAT website are covered by a Creative Commons license - Attribution (CC-by) (https://creativecommons.org/licenses/by/3.0/it/), as indicated here: https://www.istat.it/it/note-legali
The data taken from the website of the Agenzia delle entrate are in the public domain and constitute a public database made available to allow tax compliance and, more generally, to allow the identification of physical persons with the Italian public administrations, through the personal fiscal code.
The data on the Agenzia delle entrate website can be freely stored on your computer or printed (https://www.agenziaentrate.gov.it/portale/web/guest/privacy). The data are managed by the Ufficio Archivio of the Agenzia delle entrate.
This plugin uses the data taken from the website of the Agenzia delle entrate exclusively for the purpose of carrying out a formal regularity check of the pesonal tax code.
This plugin does not include any links on the external pages of the website on which it is used, neither to the Agenzia delle entrate's site nor to the ISTAT's website; in particular, no form of direct link is made, nor of deep linking.

== How to use form tags in Contact Form 7 ==

[comune]
`[comune]` has a manager in the CF7 form creation area that allows you to set various options.
In particular, it is possible to set the "kind" attribute to "tutti" (all); "attuali" (current), "evidenza_cessati" (evidence ceased). In the first and third cases, in different ways, both the currently existing municipalities and those previously closed are proposed (useful, for example, to allow the selection of the municipality of birth). In the "attuali" mode, however, only the selection of the currently existing municipalities is allowed (useful to allow the selection of the Municipality of residence / domicile).
It is also possible to set the "comu_details" option, to show an icon after the select cascade that allows the display of a modal table with the statistical details of the territorial unit.
The value returned by the group is always the ISTAT code of the selected municipality. The corresponding mail-tag converts this value into the name of the municipality followed by the indication of the automotive code of the province.
From version 1.1.1 hidden fields are also populated with the strings corresponding to the denomination of the region, province and municipality selected, useful for being used in plugins that directly capture the data transmitted by the form (such as "Send PDF for Contact Form 7" )
The cascade of select can also be used outside of CF7, using the [comune] shortcode (options similar to those of the form tag for Contact Form 7).

[cf]
`[cf]` has a manager in the CF7 form creation area that allows you to set the various options.
In particular, it is possible to set various validation options allowing you to find the correspondence of the tax code with other fields of the form.
Specifically, it is possible to verify that the tax code corresponds with the foreign state of birth (selected by means of a select [stato]), the Italian municipality of birth (selected by means of a cascade of select [comune]), gender (indicating the name of a form field that returns "M" or "F"), the date of birth. If multiple fields are used to select the date of birth, one for the day, one for the month and one for the year, it is possible to find the correspondence of the tax code with these values.

[stato]
`[stato]` has a manager in the CF7 form creation area that allows you to set various options.
In particular, it is possible to set the selection of only the currently existing states ("only_current" option) and it is possible to set the "use_continent" option to have the select values divided by continent. The field always returns the ISTAT code of the foreign state (code 100 for Italy). The ISTAT code is the type of data expected by [cf], for the verification of the tax code.

[formsign]
`[formsign]` DOES NOT have a manager in the CF7 form creation area.
To use it, simply insert the tag followed by the field name in your own form: for example [formsign firmadigitale]. This tag will create a hidden field in the form with attribute name = "firmadigitale" and no value.
To use the code, it is also necessary to insert the [firmadigitale] field in the email or email that the form sends (it is recommended at the end of the email).
In this way, at the end of the email will be written a two-line sequence containing:
an md5 hash of the data transmitted with the module (not of the content of any attached files)
a digital signature of the hash.
The signature is affixed by generating a pair of RSA keys, attributed to each form.
By checking the hash and the signature, it will be possible to verify that the emails have actually been sent by the form and that the data transmitted by the user correspond to what has been registered.
To facilitate data feedback, it is preferable to use "Flamingo" for archiving sent messages. In fact, in the Flamingo admin screen, a specific box is created that allows feedback of the hash and the digital signature entered in the email.
The system is useful in the event that through the form it is expected to receive applications for registration or applications etc... and avoids disputes regarding the data that the candidates claim to have sent and what is recorded by the system in Flamingo.

== Installation ==

= Automatic installation =

1. Plugin admin panel and `add new` option.
2. Search in the text box `campi-moduli-italiani`.
3. Position yourself on the description of this plugin and select install.
4. Activate the plugin from the WordPress admin panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (Istat and Agenzia delle entrate and then the data is imported into the database)

= Manual installation of ZIP files =

1. Download the .ZIP file from this screen.
2. Select add plugin option from the admin panel.
3. Select `upload` option at the top and select the file you downloaded.
4. Confirm installation and activation of plugins from the administration panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (Istat and Agenzia delle entrate and then the data is imported into the database)

= Manual FTP installation =

1. Download the .ZIP file from this screen and unzip it.
2. FTP access to your folder on the web server.
3. Copy the whole `campi-moduli-italiani` folder to the `/wp-content/plugins/` directory
4. Activate the plugin from the WordPress admin panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (Istat and Agenzia delle entrate and then the data is imported into the database)

== Frequently Asked Questions ==

= How to get default values from the context ? =
Since version 1.2, [comune], [stato] and [cf] support standard Contact Form 7 method to get values from the context.
More, all of them support predefined values in tag.
Look here for more informations: https://contactform7.com/getting-default-values-from-the-context/
[comune] uses javascript to be filled with default or context value.

== Screenshots ==

1. Image of the [stato] and [comune] form tags in a form
2. Image of the form-tag [cf] in a form
3. Image of the "digital signature" block inserted at the bottom of an email using the form-tag [formsign]
4. Image of the hash code verification meta-box and digital signature in Flamingo
5. Image of the admin screen, from which it is possible to update the data

== Changelog ==
= 2.0.1 =
* Minor bug fixes

= 2.0.0 =
* added a field to select a municipality to WPForms
* removed variable definition from global scope
* added use of options' groups in country selection

= 1.3.0 =
* first integration with wpforms

= 1.2.2 =
* modified table _comuni_variazioni (ISTAT changed the file's format)
* modified table _comuni_soppressi (ISTAT changed the file's format)
* updated jquery-ui-dialog.css to version used in WP 5.6
* added standard wpcf7's classes to [comune] (wpcf7-select), [stato] (wpcf7-select) and [cf] (wpcf7-text)
* changed behaviour of option "use_label_element" in [comune]: if not set, no strings will be shown before selects
* changed previous first elements used as labels in selects of [comune]
* added option to use a label in [stato] (Select a Country) 
* changed class name: gcmi_wrap to gcmi-wrap
* for [comune] it is now possible to set custom classes both for the span container and for the selects
*
* [comune] shortcode (not for CF7):
* changed class name: gcmi_comune to gcmi-comune
* added options "use_label_element"; default to true
* removed p and div tags

= 1.2.1 =
* Bug fix: fixed [stato] not replacing mail-tag with contry name

= 1.2.0 =
* Added support for default values from the context in [comune], [cf] and [stato]. Contact Form 7 standard sintax is used. Read: https://contactform7.com/getting-default-values-from-the-context/
* Minor bug fixes

= 1.1.3 =
* Minor bug fixes

= 1.1.2 =
* Fixed charset for https://www.istat.it/storage/codici-unita-amministrative/Elenco-comuni-italiani.csv (data set "comuni_attuali", table _gcmi_comuni_attuali). Please update the table from admin console if some names have characters mismatch
* Minor bug fix in class-gcmi-comune.php

= 1.1.1 =
* Added hidden fields that contain the name of the municipality, province and region selected to be used within plugins that create PDFs
* Set set_time_limit (360) in the activation routine
* Added readme.txt in English

= 1.1.0 =
* Modified email signature check: the form ID is determined directly from Flamingo data and is no longer entered in the body of the email
* Insert links to reviews and support page on the plugin page
* Modified "comuni attuali" database import routines, following modification in ISTAT files since June 2020
* Modified remote file update detection system

= 1.0.3 =
* Bug fix: error in hash calculation on modules/formsign/wpcf7-formsign-formtag.php

= 1.0.2 =
* Updates of some translation strings.
* Bug fix (addslashes before calculating verification hash)

= 1.0.1 =
* Updated the text domain to the slug assigned by wordpress.

= 1.0.0 =
* First release of the plugin.

== Upgrade Notice ==
= 2.0.0 =
Integrated with WPForms

= 1.1.0 =
ISTAT has changed the format of its database.
After this update it is necessary to update the table relating to the current municipalities [comuni_attuali].
It is also recommended to update the tables relating to the municipalities suppressed [comuni_soppressi] and to the variations [comuni_variazioni]

= 1.0.0 =
First installation

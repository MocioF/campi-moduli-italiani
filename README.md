# Campi Moduli Italiani #

[![tests](https://github.com/MocioF/campi-moduli-italiani/workflows/Tests/badge.svg)](https://github.com/MocioF/campi-moduli-italiani/actions?query=workflow%3ATests)
[![deployment](https://github.com/MocioF/campi-moduli-italiani/workflows/Deployment/badge.svg)](https://github.com/MocioF/campi-moduli-italiani/actions?query=workflow%3ADeployment)
[![License](https://img.shields.io/badge/license-GLPv2-blue.svg)](./LICENSE.txt)
[![PHP compatibility](https://plugintests.com/plugins/wporg/campi-moduli-italiani/php-badge.svg)](https://plugintests.com/plugins/wporg/campi-moduli-italiani/latest)
[![WP compatibility](https://plugintests.com/plugins/wporg/campi-moduli-italiani/wp-badge.svg)](https://plugintests.com/plugins/wporg/campi-moduli-italiani/latest)

**Campi Moduli Italiani** is a WordPress plugin to create useful fields for Italian sites, to be used in the forms produced with Contact Form 7 and WPForms.

![Campi Moduli Italiani for Wordpress](assets/banner-1544x500.png)

**Install it now:**
[https://wordpress.org/plugins/campi-moduli-italiani/](https://wordpress.org/plugins/campi-moduli-italiani/)

## Description ##
This plugin creates form tags for Contact Form 7 and form fields for WPForms.

### Contact Form 7 ###
6 form-tags (and corresponding mail-tags) are available in this version:
* [comune]: creates a series of select for the selection of an Italian municipality
* [comunetext]: (since 2.4.0) creates a text field to type an Italian municipality and choose it from a list of suggestions
* [cf]: creates a field for entering the Italian tax code of a natural person
* [stato]: creates the ability to select a country
* [statotext]: (since 2.4.0) creates a text field to type a country and choose it from a list of suggestions
* [formsign]: creates the possibility to digitally sign the e-mails sent with a private key attributed to each individual form

### WPForms ###
4 fields types are available:
* Cascade selection of an Italian municipality (returning Istat's municipality code as value)
  Since version 2.2.0 it is possible to create filters to limit to a list the municipalities shown by the field.
* "Municipality (text)": (since 2.4.0) a text field to type an Italian municipality and choose it from a list of suggestions, with the same options of the cascade selection (returning Istat's municipality code as raw value)
* A field to select a state (since 2.4.0 the country name is the value shown in e-mails and smart tags, Istat's country code is the raw value)
* "Country (text)": (since 2.4.0) a text field to type a country and choose it from a list of suggestions (returning Istat's country code as raw value)

### Shortcodes ###
The fields can also be used outside of the forms, with these shortcodes: [comune], [comunetext] (since 2.4.0), [stato] (since 2.4.0) and [statotext] (since 2.4.0).

## Data used ##
At the time of activation, the plugin downloads the data it uses from the ISTAT SITUAS services (https://situas.istat.it). This data can be updated from the administration console, which also shows when ISTAT has published changes.
Downloading and entering data into the database takes a few minutes: be patient during the activation phase.
The selection of the municipalities was created starting from the code of https://wordpress.org/plugins/regione-provincia-comune/

This plugin uses data made available by ISTAT through the SITUAS portal.
In particular, data from these SITUAS reports are acquired and stored:

* List of codes and names of the territorial units (current municipalities)
* Suppressed or ceded municipalities
* Administrative and territorial changes since 1991
* List of codes and names of the foreign territorial units (countries)

The list of foreign countries ceased since 1990 is distributed with the plugin; an automated test checks it against the SITUAS report of historical foreign countries.

The data published by ISTAT are covered by a Creative Commons license - Attribution (CC-by) (https://creativecommons.org/licenses/by/3.0/it/), as indicated here: https://www.istat.it/it/note-legali
The cadastral codes of the municipalities, used to check the place of birth in the tax code, are published by ISTAT as an elaboration on data of the Agenzia delle entrate (Italian revenue agency).
By Italian law (art. 52 d.lgs. 82/2005) all data, that are not personal data, published by an Italian administration without an explicit license are open data (CC0).
This plugin uses the cadastral codes exclusively for the purpose of carrying out a formal regularity check of the personal tax code.
This plugin does not include any links to the ISTAT's website on the external pages of the website on which it is used; in particular, no kind of direct link is made, nor of deep linking.

## How to use form tags in Contact Form 7 ##

The text fields [comunetext] and [statotext] (since 2.4.0) are an alternative to the select fields: the user types a few letters and chooses from a list of suggestions, using the mouse or the keyboard. They are accessible (WAI-ARIA combobox pattern) and they do not use external libraries.

[comune]
`[comune]` has a manager in the CF7 form creation area that allows you to set various options.
In particular, it is possible to set the "kind" attribute to "tutti" (all); "attuali" (current), "evidenza_cessati" (evidence ceased). In the first and third cases, in different ways, both the currently existing municipalities and those previously closed are proposed (useful, for example, to allow the selection of the municipality of birth). In the "attuali" mode, however, only the selection of the currently existing municipalities is allowed (useful to allow the selection of the Municipality of residence / domicile).
It is also possible to set the "comu_details" option, to show an icon after the select cascade that allows the display of a modal table with the statistical details of the territorial unit.
The value returned by the group is always the ISTAT code of the selected municipality. The corresponding mail-tag converts this value into the name of the municipality followed by the indication of the automotive code of the province.
From version 1.1.1 hidden fields are also populated with the strings corresponding to the denomination of the region, province and municipality selected, useful for being used in plugins that directly capture the data transmitted by the form (such as "Send PDF for Contact Form 7" )
The cascade of select can also be used outside of CF7, using the [comune] shortcode (options similar to those of the form tag for Contact Form 7).
Since version 2.2.0 it is possible to create filters to limit to a list the municipalities shown by the field.

[comunetext]
`[comunetext]` (since 2.4.0) has a manager in the CF7 form creation area. It accepts the same options of [comune]: "kind" ("tutti", "attuali", "evidenza_cessati"), "filtername" (a filter created with the filters' builder), "comu_details", "use_label_element", id, class and wrapper class. The default value can be the ISTAT code or the name of the municipality; the "placeholder" option uses it as placeholder.
The field shows a text input with a list of suggestions: municipalities with the same name are distinguished by the automotive code of the province, ceased municipalities are marked.
The value returned is always the ISTAT code of the selected municipality, as for [comune]; the corresponding mail-tag converts it into the name of the municipality followed by the automotive code of the province. Hidden fields with the same suffixes of [comune] (_formatted, _targa, _reg_desc, _prov_desc) are also populated.
If the user types the name without choosing a suggestion (or JavaScript is not available), the text is resolved on the server when it matches a single municipality; otherwise the user is asked to choose from the list.
The field can also be used outside of CF7, using the [comunetext] shortcode (attributes: name, kind, filtername, id, comu_details, class, use_label_element, value, placeholder).

[cf]
`[cf]` has a manager in the CF7 form creation area that allows you to set the various options.
In particular, it is possible to set various validation options allowing you to find the correspondence of the tax code with other fields of the form.
Specifically, it is possible to verify that the tax code corresponds with the foreign state of birth (selected by means of a select [stato] or a text field [statotext]), the Italian municipality of birth (selected by means of a cascade of select [comune] or a text field [comunetext]), gender (indicating the name of a form field that returns "M" or "F"), the date of birth. If multiple fields are used to select the date of birth, one for the day, one for the month and one for the year, it is possible to find the correspondence of the tax code with these values.

[stato]
`[stato]` has a manager in the CF7 form creation area that allows you to set various options.
In particular, it is possible to set the selection of only the currently existing states ("only_current" option) and it is possible to set the "use_continent" option to have the select values divided by continent. The field always returns the ISTAT code of the foreign state (code 100 for Italy). The ISTAT code is the type of data expected by [cf], for the verification of the tax code.
The same select can also be used outside of CF7, using the [stato] shortcode (attributes: name, id, class, use_label_element, first_as_label, use_continent, only_current, value).

[statotext]
`[statotext]` (since 2.4.0) has a manager in the CF7 form creation area. It accepts the options of [stato]: "only_current", "use_continent" (here the continent is shown next to the name of each suggestion) and "first_as_label" (here it becomes the placeholder "Select a Country"), plus "use_label_element", id, class and wrapper class. The default value can be the ISTAT code or the Italian name of the country; the "placeholder" option uses it as placeholder.
Countries can be searched by Italian name, alternative name, name in another language or ISO code (for example "USA" or "DE"); ceased countries are marked with "(ceased)". The list of countries is sent with the page and filtered in the browser, without requests to the server while typing.
The value returned is always the ISTAT code of the country, as for [stato], so the field can be used with [cf] to check the country of birth; the corresponding mail-tag converts it into the name of the country.
The field can also be used outside of CF7, using the [statotext] shortcode (attributes: name, id, class, use_label_element, only_current, use_continent, value, placeholder).

[formsign]
`[formsign]` now has a manager in the CF7 form creation area.
To use it, simply insert the tag followed by the field name in your own form: for example [formsign firmadigitale]. This tag will create a hidden field in the form with attribute name = "firmadigitale" and no value.
To use the code, it is also necessary to insert the [firmadigitale] mail-tag in the email or emails that the form sends (it is recommended at the end of the email).
In this way, in the email body it will be written a two-lines sequence containing:
an md5 hash of the data transmitted with the module (not of the content of any attached files)
a digital signature of the hash.
If you use html email, you can style the output using a wp option named: "gcmi-forsign-css" with a css as value.
The signature is affixed by generating a pair of RSA keys, attributed to each form.
By checking the hash and the signature, it will be possible to verify that the emails have actually been sent by the form and that the data transmitted by the user correspond to what has been registered.
To facilitate data feedback, it is preferable to use "Flamingo" for archiving sent messages. In fact, in the Flamingo admin screen, a specific box is created that allows feedback of the hash and the digital signature entered in the email.
The system is useful in the event that through the form it is expected to receive applications for registration or applications etc... and avoids disputes regarding the data that the candidates claim to have sent and what is recorded by the system in Flamingo.

## Code

Want to check the code? [https://github.com/MocioF/campi-moduli-italiani](https://github.com/MocioF/campi-moduli-italiani)

## Installation ##

### Automatic installation ###

1. Plugin admin panel and `add new` option.
2. Search in the text box `campi-moduli-italiani`.
3. Position yourself on the description of this plugin and select install.
4. Activate the plugin from the WordPress admin panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (ISTAT) and then the data is imported into the database

### Manual installation of ZIP files ###

1. Download the .ZIP file from this screen.
2. Select add plugin option from the admin panel.
3. Select `upload` option at the top and select the file you downloaded.
4. Confirm installation and activation of plugins from the administration panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (ISTAT) and then the data is imported into the database

### Manual FTP installation ###

1. Download the .ZIP file from this screen and unzip it.
2. FTP access to your folder on the web server.
3. Copy the whole `campi-moduli-italiani` folder to the `/wp-content/plugins/` directory
4. Activate the plugin from the WordPress admin panel.
NOTE: activation takes several minutes, because the updated data tables are downloaded from the official sites (ISTAT) and then the data is imported into the database

## Frequently Asked Questions ##

### How to get default values from the context ? ###
Since version 1.2, [comune], [stato] and [cf] support standard Contact Form 7 method to get values from the context; [comunetext] and [statotext] support it too (since 2.4.0).
More, all of them support predefined values in tag.
Look here for more informations: https://contactform7.com/getting-default-values-from-the-context/
[comune] uses javascript to be filled with default or context value.


### How do I report a bug? ###
You can create an issue in our Github repo:
[https://github.com/MocioF/campi-moduli-italiani](https://github.com/MocioF/campi-moduli-italiani)

## Screenshots ##

|![Image of the [stato] and [comune] form tags in a form](assets/screenshot-1.png)|![Image of the form-tag [cf] in a form](assets/screenshot-2.png)|![Image of the "digital signature" block inserted at the bottom of an email using the form-tag [formsign]](assets/screenshot-3.png)|![Image of the hash code verification meta-box and digital signature in Flamingo](assets/screenshot-4.png)|![Image of the admin screen, from which it is possible to update the data](assets/screenshot-5.png)|
| :---: | :---: | :---: | :---: | :---: |
| Image of the [stato] and [comune] form tags in a form | Image of the form-tag [cf] in a form | Image of the "digital signature" block inserted at the bottom of an email using the form-tag [formsign] | Image of the hash code verification meta-box and digital signature in Flamingo | Image of the admin screen, from which it is possible to update the data |

## License ##

Campi Moduli Italiani is an Open Source project licensed under [GPL v2](./LICENSE.txt).

## Contributions, Issues, Bugs ##

Plugin code is hosted on a public repository on [GitHub](https://github.com/MocioF/campi-moduli-italiani).
Reach me over there to help and suggest.

## Open Source contributors ##

| [<img src="https://avatars.githubusercontent.com/u/26635356?s=460&v=4" width="117">](https://github.com/MocioF) |
| :---: |
| [MocioF](https://github.com/MocioF) |
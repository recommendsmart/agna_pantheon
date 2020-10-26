# Feature: Rocketship Contact Office

FA link: https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.i8yxfrryg2ju
Wires: https://o1ugsw.axshare.com/#g=1&p=r006b-contact

## Core:
Contains the Contact Office content type and several view modes. 
Rewrites the Content search index to add its own search view mode.
Contains path alias pattern
Contains RDF mapping and Metatag definition
Contains Canonical Image and Description field, used for SEO. Renamed to 
Location Image
and Teaser to conform to the wires.

### View modes:
##### Full
* Canonical view mode
##### Map Info
* Used as the Info window popup for Google Maps pins
##### Footer
* Used to show the Contact Office in the footer. Rendered as part of the "Contact"
block placed in the theme's footer region upon site installation.
##### Search Index
* Set up for easy adding of search later. Renders everything that should be
searchable so Search API can index all that data.
##### Contact Office Promoted [Advanced]
* Used in Advanced to show a Main Contact Office above the map on the overview page
##### Teaser [Advanced]
* Used below the map on the overview page to show the satellite offices (
all offices that are not marked as a main office)


### Permissions
* Core does not give the webadmin permission to add more Contact Office nodes or delete
existing Contact Office nodes. This because Core only gives them a single "Contact Office" 
page.  
* Advanced does give the webadmin permission to add and delete Contact Office nodes, 
because they can now have multiple offices each with their own contact form.
* An empty Contact Office node is created during installation of the Core feature, so
the webadmin always has one node to work with.

### Webform
* A webform is created as part of this Feature. 
* This webform is not editable by the client, even if they have permissions 
to edit any webform. Only users with the Manage Webforms permission can change
 this form. That is also why this form specifically is added to the Whitelist.
* There will also be a tab on the detail page that will take the client to a 
View which will only shows the webform submissions for that node. They can 
still access the general submissions view as well of course.

#### TOKEN:
This module creates a custom token that is used to determine where to send
Contact form submissions. See the .module file for more information, but 
essentially if the client fills in an email in the "Email submission 
override" field, any Contact form submissions done from that node will be 
sent to that email address instead of the default site mail. This way, each 
Contact Office can have its email address.

##### IMPORTANT:
Even though the client can't edit the webform, they can translate it. And the
 Elements (YAML) is available for translation there (so they can translate 
 the labels), as well as the Email settings (so they can translate the 
 mails). Be aware of this, as the client should be trained in how to translate
this properly.

### FieldFormatters
* Contains the "ContactUsButton" formatter, used on boolean fields.
* This outputs a link to the node with the anchor for the webform set
* By outputting the full link to the node, it also works when used
in the Contact Office Promoted view mode to link to the detail page.
* The anchor is placed in the webform using a form_alter.

### Promotion options
* If only Core is enabled, the "sticky" promotion option is hidden.
* The "promoted" sticky option is renamed to "Show this office in the footer
 of your site"
* All those offices are shown in the footer of the website using a view.
* The "promotion options" details is therefore set to be open by default



## Basic:
Does not exist. Straight to Advanced we go.



## Advanced
In Advanced the webadmin can have multiple offices. They can mark an office
as a Main Contact Office, which will then be shown above the Google Map on the
"Contact Offices" overview page. "Mark as main office" is a relabeling of the "sticky"
promotion option.

The Google Map meanwhile shows all Contact Offices: main and satellite. The infowindow
for this map uses Teaser view modes.

Below the Google Map are all offices which are not marked as a main office,
rendered in Teaser view modes.

The Advanced module migrates a basic page on installation which will
have all three views set up as three separate overview paragraphs.

It grants the webadmin permission to add and delete all Contact Offices. They are now
 fully in control.

##### Display Field Formatter
It also adds a custom field formatter to the footer of the detail page which
will by default link back to the node that was just migrated (the UUID is
also migrated so we can use that to always grab the right node). This DSField
does have the option to select a different UUID if, for some reason, the 
original node is deleted or it has to link somewhere else.


## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f006-contact-office`

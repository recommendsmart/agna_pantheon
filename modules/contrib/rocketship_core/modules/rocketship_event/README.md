# Feature: Rocketship Events

FA link:  
https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.7rp1ss6nb6y
Wires:  
https://o1ugsw.axshare.com/#g=1&p=r014b_c-events

## Core:
Contains the Event content type and several view modes. 
Rewrites the Content search index to add its own search view mode.
Contains path alias pattern
Contains RDF mapping and Metatag definition
Contains Canonical Image and Description field, used for SEO
Contains Event CUSTOM ENTITY. 
Everything that varies by date range, so per actual event, should be added 
there.
By default, besides start date and end date, there is also a price field. 
This way clients can set a price per event.

#### Extras:
* Custom widget that can be used on the start or end date which only asks for
 a time. This widget
sets the date part to be equal to another timestamp field. Can be used if 
each Event entity should be the same day, only with different start and end 
times.
* Custom validation to ensure end date is always later than start date
* A range of DisplaySuite fields. Each event will have its own requirements 
when it comes to formatting the dates, use these and create new ones to meet 
those requirements. 
* Custom webform element. This is useful if multiple events are available, to let the visitor select from a list of those dates. Essentially a reference field dropdown specifically for our Event custom entity.

## Basic:
Contains a migrate to create the overview pages (all overview pages are Basic
 Pages with an Overview paragraph). The UUID is also migrated, so that node's
  UUID will always be the same. This can be used for Context Plugins, or 
  custom code.

Note: enabling migrations locally will migrate the node, but when deploying 
and letting Configuration Management enable the module, the migrates will NOT
 run. CIM enables modules before importing the config so hook_install doesn't
  have the migrates available to run at that time. If you want to automatically
run the migrates on deploy, enable the module in a hook_update.

#### IMPORTANT:
Basic will also create the Archive page, and the Archive view. But clients 
have to buy Advanced for access to it, so if they did NOT buy Advanced delete
 that page after site installation and remove that view from the 
 rocketship_event_basic_overview_field_options_alter() in rocketship_job_basic
 .module.

Adds a webform to the bottom of the detail page. This webform is not
 editable by the client, even if they have permissions to edit any webform. 
 Only users with the Manage Webforms permission can change this form. That is
  also why this form specifically is added to the Whitelist.

There will also be a tab on the detail page that will take the client to a 
View which will only show the webform submissions for that node. 

The Scroll-To field's target is set in a form_alter, so the button in the 
header can scroll down to the webform.

#### IMPORTANT:
Even though the client can't edit the webform, they can translate it. And the
 Elements (YAML) is  available for translation there (so they can translate 
 the labels), as well as the Email settings (so they can translate the mails)
 . Be aware of this, as the client should be trained in how to translate this
  properly.

## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f014-event`

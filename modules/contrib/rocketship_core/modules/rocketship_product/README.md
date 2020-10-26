# Rocketship Product

FA link:   
https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.ft4gltyszpk2
Wires:  
https://o1ugsw.axshare.com/#g=1&p=r009b-products

## Core
Contains the Product content type and several view modes. 
Rewrites the Content search index to add its own search view mode.
Contains path alias pattern
Contains RDF mapping and Metatag definition
Contains Canonical Image and Description field, used for SEO
Uses the Label:Value custom field so clients can add properties to their 
products
These properties can't be filtered on, naturally

## Basic
Contains a migrate to create the overview page (all overview pages are Basic 
Pages with an Overview paragraph). The UUID is also migrated, so that node's 
UUID will always be the same. This can be used for Context Plugins, or custom
 code.

Note: enabling migrations locally will run the migration, but when deploying 
and letting Configuration Management enable the module, the migrates will NOT
 run. CMI enables modules before importing the config so hook_install doesn't
  have the migrates available to run at that time. If you want to automatically
run the migrates on deploy, enable the module in a hook_update.

## Advanced
Adds filtering in the form of three vocabularies and three facet filters. 
Rabbithole is setup, as per usual. You'll have to rename the filters, as they
 have generic names currently, to match the client's needs. There is an info 
 block on the overview added with more explanation. 

Note that rabbit hole does not work for User 1, as they automatically have 
the "bypass rabbithole settings" permission. So always check Rabbithole 
settings as webadmin and anonymous.

## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f009-product`

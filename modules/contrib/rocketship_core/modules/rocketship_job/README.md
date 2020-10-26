# Rocketship Jobs

FA link:  
https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.iytf5i9a7i74

Wires:  
https://o1ugsw.axshare.com/#g=1&p=r012b-jobs

## Core
Contains the Job content type and several view modes. 
Rewrites the Content search index to add its own search view mode.
Contains path alias pattern
Contains RDF mapping and Metatag definition
Contains Canonical Image and Description field, used for SEO

## Basic:
Contains a migrate to create the overview page (all overview pages are Basic 
Pages with an Overview paragraph). The UUID is also migrated, so that node's 
UUID will always be the same. This can be used for Context Plugins, or custom
 code.

Note: enabling migrations locally will migrate the node, but when deploying 
and letting Configuration Management enable the module, the migrates will NOT
 run. CIM enables modules before importing the config so hook_install doesn't
  have the migrates available to run at that time. If you want to automatically
run the migrates on deploy, enable the module in a hook_update.

## Advanced
Advanced adds a webform to the bottom of the detail page. This webform is not
 editable by the client,  even if they have permissions to edit any webform. 
 Only users with the Manage Webforms permission can change this form. That is
  also why this form specifically is added to the Whitelist.

There will also be a tab on the detail page that will take the client to a 
View which will only show the webform submissions for that node. 

The Scroll-To field's target is set in a form_alter, so the button in the 
header can scroll down to the webform.

Adds three taxonomy vocabularies and facets for them to the overview page.
The facet is custom, we extended the basic LinksWidget so we could give it 
our own theme function. Adds a search block to the overview.

Rabbit hole is setup to redirect to the overview page, pre-filtered. Note 
that rabbit hole does not work for User 1, as they automatically have the 
"bypass rabbithole settings" permission. So always check Rabbithole settings
 as webadmin and anonymous.

#### IMPORTANT:
Even though the client can't edit the webform, they can translate it. And the
 Elements (YAML) is  available for translation there (so they can translate 
 the labels), as well as the Email settings (so they can translate the mails)
 . Be aware of this, as the client should be trained in how to translate this
  properly.


Advanced Search:

uses a search api query alter to set ->keys() and the generic 
SearchRedirectBlock from rocketship_blocks to provide a search block

#### Important: 
This module sets search api server to index and match on partial words.
This is fine for Cooldrops, but obviously not for Solutions. So if you need 
proper search, use Solr, and use stemming. See Kevin Van Belle's article on 
this topic.

## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f012-job`

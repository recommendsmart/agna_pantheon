# Rocketship Blog

FA link: https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.qydt9c17yjw7
Wires: https://o1ugsw.axshare.com/#g=1&p=r002b-blog

## Core:
Contains the Blog content type and several view modes. 
Rewrites the Content search index to add its own search view mode.
Contains path alias pattern
Contains RDF mapping and Metatag definition
Contains Canonical Image and Description field, used for SEO

## Basic:
Contains a migrate to create the overview page (all overview pages are Basic 
Pages with an Overview paragraph). The UUID is also migrated, so that node's 
UUID will always be the same. This can be used for Context Plugins, or custom
 code.

Note: enabling migrations locally will run the migration, but when deploying
 and letting Configuration Management enable the module, the migrates will
 NOT run. CMI enables modules before importing the config so hook_install
 doesn't have the migrates available to run at that time. If you want to
 automatically run the migrates on deploy, enable the module in a hook_update.

## Advanced
Add the Blog Tags taxonomy vocabulary, as well as a facet above the existing
 overview to filter the blog items. Updates the view modes to output the 
 selected terms. Rabbit hole is setup to redirect to the overview page, 
 pre-filtered. Note that rabbit hole does not work for User 1, as they 
 automatically have the "bypass rabbithole settings" permission. So always 
 check Rabbithole settings as webadmin and anonymous.

The facet is custom, we extended the basic LinksWidget so we could give it 
our own theme function.

On the detail page, Related Blog items is added.

## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f002-blog`

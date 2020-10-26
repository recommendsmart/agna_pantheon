# Rocketship Realisations

FA link: https://docs.google.com/document/d/1JJLHBrf9HjFR0qNgCOMphM5oakNqfGLsRuan92sNp3A/edit#heading=h.qydt9c17yjw7
Wires: https://o1ugsw.axshare.com/#g=1&p=r002b-realisations

## Core:
Contains the Realisations content type and several view modes. 
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

## Theming

If you are using a Rocketship theme, there are Sass files included 
for theming.  
The library loading the relevant CSS and JS is only loaded when the module is active. This is done using a check in the active theme for active feature module.

If you are NOT your own theming, a very basic 'structural' library can be loaded using a config form at:  
`/admin/config/system/rocketship-features/features-f007-realisation/settings`

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Entity Recycle module provides a trash / recycle bin functionality for
content entities. This module allows users to soft-delete entities and only users
with the correct permission can permanently delete or restore an entity. This module
may be useful for preventing an accidental delete of an entity. The module creates
a locked boolean field on an entity and if the field is set to TRUE then the entity is
considered to be in the recycle bin. The entity in the recycle bin can only be seen
by users with a 'view entity recycle bin' permission.

 * For a full description of the module visit:
   https://www.drupal.org/project/entity_recycle

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/entity_recycle


FEATURES
------------
  - Recycle bin / Trash functionality.
  - Soft delete functionality.
  - Restore deleted items.
  - Purge/Delete items in the recycle bin after a certain amount of time.

REQUIREMENTS
------------

This module requires no modules outside of the Drupal core.


INSTALLATION
------------

 * Install the Entity Recycle module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

  1. Navigate to Administration > Extend and enable the module.

  2. When the module is enabled, it will automatically enable recycle bin
     functionality for all nodes and a view will be created to see all
     content in the recycle bin (admin/content/node/recycle-bin).

  3. Navigate to Administration > Configuration > Content authoring > Entity Recycle to configure.

  4. Enable entity types for which you want to have the recycle bin functionality. There is also an option to automatically purge items in the recycle bin.


NOTES
------------
  By default, this module does not alter any existing views, so in order to
  remove recycle bin items from showing up in the view results you need to
  add a field filter (recycle_bin) and set the default value to FALSE. For reference
  see the Content Recycle Bin view.


MAINTAINERS
-----------
Maintainers:
 * Nejc Koporec(nkoporec) - https://www.drupal.org/u/nkoporec

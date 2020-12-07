CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Basic usage
 * Advanced usage
 * Maintainers


INTRODUCTION
------------

The Template Entities module provides a template feature to end-users to facilitate quick creation of content. To avoid confusion, it has nothing to do with Twig templates or theming! The original customer use case was to allow a marketing manager to create and maintain a set of landing page templates which could be used by content editors to create campaign pages. It works particularly well with complex content (e.g. built with layout builder, paragrahps, or panels) where the structure is an intrinsic part of the content. Beyond nodes, there are plenty of use cases for templates of other entity types including content blocks, taxonomy terms, and paragraphs.

The module makes the business of creating, maintaining and using templates entirely that of the content team without any need for site builder or developer input beyond initial configuration.


FEATURES
--------

* Define different template types for different entities and bundles.
* A basic UI to create and manage templates.
* A basic UI to use templates.
* Manage and "create from" permissions for each template type.
* Views support - list templates as required.
* Plugins to adapt behaviour for different entity types.
* API Hooks to arbitrarily customise template behaviour.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Template Entities module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


BASIC USAGE
-----------

1. Create a template type.
2. Create a piece of content to use as a template.
3. Create a template using the content created before.

To create new content from templates, either:

* Navigate to the template listing page and use the 'new from template' operation.
* Navigate to the template page and use the 'new from template' action button.
* If the "Add action link to collection pages" was checked when creating the template type, then navigate to the entity collection page (e.g. for nodes, /admin/content), and use the "Add using .... template" action to select a template and create the new content.


ADVANCED USAGE
--------------

Typically, the steps above will be performed by different roles assigned permissions as follows:

* **Site builder/administrator** sets up the template types with the "`Administer all templates and template types`" permission.
* **Content managers** creates and maintains templates using permissions for each template type "`TEMPLATE_TYPE: Administer templates".
* **Content editors and contributors** creates new content using templates using permissions for each template type "`TEMPLATE_TYPE: Create new content from templates`". The "`CONTENT_TYPE: Create new content`" is not needed to create from templates so, if desired, editors and contributors can be restricted to creating content from templates only.

Note that entities created from templates have no ongoing association with the template. Changes to a template will not affect entities previously created from it.

Template entities are field-able and so additional template metadata can be added such as an image/screenshot of the template, or a category field.

Views can be used to override template selection pages e.g. to provide a grid of template screenshots to select from.

When creating a template type with the "Add action link to collection pages" option checked, the module will attempt to determine the collection page for the entity type and bundle. If for example a view has been created to list landing pages, use the "Override collection pages" field to tell Template Entities which pages to put the add action link on.

SIMILAR MODULES
---------------
Quick node clone - nodes only and no form step.
Entity clone - all entities but no form step.
Cloner - plugin API based - template entities could have been built on top.
Gutenburg - page templates.


MAINTAINERS
-----------

 * Andy Chapman - https://www.drupal.org/u/chaps2

Supporting organization:

 * Locologic Limited - https://www.locologic.co.uk

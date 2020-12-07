# DEVELOPER NOTES

Whilst 90% of use cases will probably be concerned with template nodes I can envisage a number of valid use cases for other entity types. Hence the code and core module UX has been designed to work with any entity type.

# UX

## Core UX to create a template
1. Create an entity which will be used as a template.
2. Create a new template with a reference to the entity created in step 1.

## Core UX to use a template
1. View a list of templates.
2. Select and view an individual template.
3. Click on the "New from template" action.

# Template plugins
* Any variance in templating with different types of entities can be handled with template plugins.
* A "deriver" (EntityTemplatePluginDeriver) provides a default plugin implementation for all content entity types. To override a derived plugin, create a plugin with annotated id of "canonical_entities:ENTITY_TYPE" e.g. "canonical_entities:node" @see NoteTemplate.
* All template plugins extend TemplatePluginBase.
* A node plugin sets created time and owner (author).
* A block plugin (block/add) saving goes to admin/structure/block/block-content.

# Routing, Actions and Tasks

A number of routes with corresponding actions and tasks are added to provide a default template UX for any entity type.

## Template entity routes

These are routes for working with templates provided by the html route provider of the Template entity type: TemplateHtmlRouteProvider (see Template entity annotation).

1. Standard template admin entity routes - provided by the template entity route provider superclass AdminHtmlRouteProvider.
2. Route to create new content from a template entity using template entity path: ```entity.template.new_from_template``` (actually redirects to an entity specific route so that entity creation is within the expected entity creation context).
3. Route to select which template entity of a given template type to create new content from: ```entity.template.new_from_template_page```. Each template listed links to the ```entity.template.new_from_template``` route.

## Additional routes to other entities
1. Create new content using path based on the source entity type canonical path (or create path): ```entity.$entity_type_id.new_from_template```.
2. View a list of template entities linked to an entity: ```entity.$entity_type_id.templates```.

Both the above are provided by RouteSubscriber:routes() callback declared in template_entities.routing.yml using paths provided by link templates added to entity info by template_entities_entity_type_alter ('new-from-template' and 'templates').

## Actions on collection pages

By default the module will add action buttons to collection pages for the entity type being templated. Collection pages are discovered by checking for in the following order:

* an entity "collection" link template
* collection routes that match the pattern "entity.$entity_type_id.collection" or "$entity_type_id.collection"
* If neither of the above yields a suitable route then the system.admin_content route is used.

# Cloning
* The module uses the createDuplicate() method provided by implementations of EntityInterface.
* The TemplateController performs the actual duplication and then returns a new entity form for the duplicate entity using the entityFromBuilder() method provided by ControllerBase. This in turn uses the entity.builder service i.e. EntityFormBuilder.
* The generic form build approach could do with per-entity refinement to adjust values e.g. turn values into placeholders, prevent changing selected fields etc.

## Entity clone module
The entity clone module also uses the createDuplicate() method but adds a subsequent clone operation (can be set per entity type) with pre and post clone operation events. The clone operation is responsible for saving the cloned entity which is not the behaviour needed to create content from templates.

Hence entity clone is not suitable for use by Template Entities but we could re-use the pattern to provide per-entity "new from template" handlers and events.

# Access control

## Typical roles
### Template administrator
* Should be able to create and manage template types for use by template authors.
* Will also need to be able to assign permissions to permit creation, management and use of specific template types.

### Template author
* Should be able to create templates for specific template types. (create <type> template).

### Template user
* Should be able to view and create content from specific template types. (create from <type> template).

## Access control overrides
In order to implement access control the following are used:

* An access check service access_check.entity.tenplates. This is used to check access to the "Templates" route added to entities that are used as templates.

# Template entity hiding
Using actual entities as template entities allows templating to be applied to any entity type and bundle.

This approach requires that all regular template-enabled entity queries are altered to filter out template entities.

The "template_entities_allow_templates" query tag is used to tag any query that should not have template filtering applied.

## EntityQuery
Uses entity query decorators declared in the module's service yaml file to left join to the template entity reference field table to filter out entities used as template entities. Is not applied if:

1. EntityQuery->accessCheck returns false (e.g. for EntityStorageInterface->loadByProperties()). Block content listing in layout builder uses this method.

2. The 'allow_templates' setting is set to true. This is used on entity reference fields where templates should be included in the results - see Template::baseFieldDefinitions().

## ViewsQueryAlter
Uses hook_views_query_alter() to alter views queries in a similar way to entity queries.

## Query alter hook
For non-views and non-standard entity queries, a query alter hook is implemented which calls entity type specific callbacks in template plugins. Currently implemented by the TermTemplate plugin only:

 * Taxonomy term listing uses a hierarchical query (TermStorage->loadTree()) which needs altering.

# Plugins

## Plugins with forms
Template plugins are similar to block plugins in that configuration options may be presented with a plugin configuration form embedded in the template type form.

To simplify plugin configuration forms, TemplatePluginBase implements the plugin configuration form interface to ensure consistent behaviour. Sub classes need just to implement configureForm(), configureValidate(), configureSubmit() methods. The base configuration form can be completely overridden by including a plugin form annotation for a "configure" form in the template plugin and implementing the PluginWithFormsInterface and providing a configure plugin form class.


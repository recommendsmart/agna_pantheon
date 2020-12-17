<?php

namespace Drupal\entity_reference_views\Plugin\views\display;

use Drupal\views\Plugin\views\display\Attachment;

/**
 * The plugin that handles an field attachment display.
 *
 * Field attachment displays are displays that are 'attached' to a field.
 * The main purpose of this display is to provide
 * 'entity_reference_field_display' which is used to render the allowed fields.
 *
 * @todo Add referenced entity id as argument by default.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "entity_reference_field_attachment",
 *   title = @Translation("Field attachment"),
 *   help = @Translation("Attachments added to a field to display proprties better."),
 *   theme = "views_view",
 *   contextual_links_locations = {""},
 *   entity_reference_field_display = TRUE
 * )
 */
class EntityReferenceFieldAttachment extends Attachment {

}

<?php

/**
 * @file
 * Contains template.page.inc.
 *
 * Page callback for Template entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Prepares variables for Template templates.
 *
 * Default template: template.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_template(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for list of available templates.
 *
 * Default template: template-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of templates.
 *
 * @throws \Drupal\Core\Entity\EntityMalformedException
 *
 * @see node_add_page()
 */
function template_preprocess_template_content_add_list(array &$variables) {
  $variables['templates'] = [];

  /** @var \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination */
  $redirect_destination = \Drupal::service('redirect.destination');

  if (!empty($variables['content'])) {
    /** @var \Drupal\template_entities\Entity\TemplateInterface $template */
    foreach ($variables['content'] as $template) {
      $variables['templates'][$template->id()] = [
        'template' => $template->id(),
        'new_link' => $template->toLink($template->label(), 'new-from-template', [
            'attributes' => ['class' => 'admin-item__link'],
            'query' => ['destination' => $redirect_destination->get()],
          ]),
        'description' => [
          '#markup' => $template->getDescription(),
        ],
      ];

    }
  }

}

<?php

namespace Drupal\group_flex\Form;

use Drupal\group\Entity\Form\GroupTypeForm as GroupTypeFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Form controller for group type forms.
 */
class GroupTypeForm extends GroupTypeFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    $form['group_flex_enabler'] = [
      '#title' => t('Enable group flex'),
      '#type' => 'checkbox',
      '#default_value' => $type->getThirdPartySetting('group_flex', 'group_flex_enabler', 0),
    ];
    $form['group_flex'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="group_flex_enabler"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['group_flex']['group_type_visibility'] = [
      '#title' => t('Group type visibility'),
      '#type' => 'radios',
      '#options' => [
        GROUP_FLEX_TYPE_VIS_PUBLIC => t('Public (visible by anybody authorised to view a "Group" of that type'),
        GROUP_FLEX_TYPE_VIS_PRIVATE => t('Private (visible by members only)'),
        GROUP_FLEX_TYPE_VIS_FLEX => t('Let owner decide'),
      ],
      '#description' => t('When the visibility is "public", roles that can actually view "Groups" of this type can be defined through the group type permissions page.'),
      '#default_value' => $type->getThirdPartySetting('group_flex', 'group_type_visibility', GROUP_FLEX_TYPE_VIS_PUBLIC),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    parent::save($form, $form_state);
    $type = $this->entity;

    // Only act when the group type is saved correctly.
    if ($type && $type instanceof GroupTypeInterface) {
      // Note: we are saving this but when permissions change later this might
      // not match anymore or when converting group type from flexible to other.
      // This is the responsibility of the site administrator.
      /** @var \Drupal\group_flex\GroupFlexGroupTypeSaver $group_flex_saver */
      $group_flex_saver = \Drupal::service('group_flex.group_type_saver');
      $group_flex_saver->save($type, $form_state);

      $type->setThirdPartySetting('group_flex', 'group_flex_enabler', $form_state->getValue('group_flex_enabler'));
      $type->setThirdPartySetting('group_flex', 'group_type_visibility', $form_state->getValue('group_type_visibility'));
      $type->save();
    }

  }

}

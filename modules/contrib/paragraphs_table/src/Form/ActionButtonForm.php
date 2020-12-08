<?php

namespace Drupal\paragraphs_table\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ActionButtonForm extends FormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
    );
  }

  public function getFormId() {
    return 'paragraphs_table_add';
  }

  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, $field_name = NULL, $targetBundle = NULL) {

    if (!$form_state->has('entity')) {
      $form_state->set('entity', $entity);
      $form_state->set('field_name', $field_name);
      $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
      foreach ($display->getComponents() as $name => $options) {
        if ($name != $field_name) {
          $display->removeComponent($name);
        }
      }
      $form_state->set('form_display', $display);
    }
    $form_state->get('form_display')->buildForm($entity, $form, $form_state);
    $triggering_input = $form_state->getUserInput();
    $triggering_element = $form_state->getTriggeringElement();
    $trigger_mode = FALSE;
    if(!empty($triggering_element) && !empty($triggering_element["#name"])){
      $temp = explode("_",$triggering_element["#name"]);
      $trigger_mode = end($temp);
    }
    // Add the field form.
    if (!empty($form[$field_name]["widget"]["add_more"])) {
      unset($form[$field_name]["widget"]["add_more"]["#suffix"]);
      $child = implode('_',['add_more','button',$targetBundle]);
      if (!empty($form[$field_name]["widget"]["add_more"][$child])) {
        unset($form[$field_name]["widget"]["add_more"][$child]["#attributes"]["class"]);
      }
      $edit_btn = 'edit_'.$field_name;
      $triggering_btn_add = FALSE;
      $triggering_btn_edit = FALSE;
      if(!empty($triggering_input)){
        if(!empty($triggering_input["_triggering_element_name"]) && $triggering_input["_triggering_element_name"] == implode('_',[$field_name,$targetBundle,'add_more'])){
          $triggering_btn_add = TRUE;
        }
        if(!empty($triggering_input[$edit_btn]) || in_array($trigger_mode, ['remove','duplicate'])){
          $triggering_btn_edit = TRUE;
        }
      }

      $form['#disable_inline_form_errors'] = TRUE;
      $form['#rebuild'] = TRUE;
      $form[$field_name]["widget"]['#no_header'] = true;
      foreach ($entity->get($field_name)->getValue() as $child => $value) {
        if (!empty($value) && !$triggering_btn_edit ) {
          $form[$field_name]["widget"][$child]["#access"] = FALSE;
        }
      }

      if(!$triggering_btn_add ){
        $form[$field_name]["widget"]["add_more"]['edit'] = [
          '#type' => 'submit',
          '#name' => $edit_btn,
          '#value' => $this->t('Edit'),
          '#access' => TRUE
        ];
        if (!empty($triggering_input) && $triggering_btn_edit){
          $form[$field_name]["widget"]["add_more"]['edit']['#access'] = FALSE;
        }
      }

      if($triggering_btn_edit || $triggering_btn_add){
        $form[$field_name]["widget"]["add_more"]['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save'),
        ];
      }

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $form_state->get('form_display')->validateFormValues($entity, $form, $form_state);
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\Core\Form\FormInterface::submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $form_state->set('entity', $entity);
    $entity->save();
    $current_path = \Drupal::service('path.current')->getPath();
    return new RedirectResponse($current_path);
  }

  /**
   * Returns a cloned entity containing updated field values.
   *
   * Calling code may then validate the returned entity, and if valid, transfer
   * it back to the form state and save it.
   */
  protected function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    $entity = clone $form_state->get('entity');
    $field_name = $form_state->get('field_name');

    $form_state->get('form_display')
      ->extractFormValues($entity, $form, $form_state);
    return $entity;
  }
}

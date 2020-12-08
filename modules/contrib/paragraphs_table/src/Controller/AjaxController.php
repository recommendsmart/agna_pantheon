<?php

namespace Drupal\paragraphs_table\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for paragraphs item routes.
 */
class AjaxController extends ControllerBase {

  protected $renderBuild;

  protected $fieldsDefinition;

  /*
   * return output JSON object value
   */
  public function json($field_name, $host_type, $host_id, $typeData = FALSE) {
    $message = [];
    $entity = $this->entityTypeManager()->getStorage($host_type)->load($host_id);
    if (empty($entity)) {
      $message[] = $this->t("%type %id doesn't exist", [
        "%type" => $host_type,
        "%id" => $host_id,
      ]);
    }
    $paragraph_field = $entity->hasField($field_name) ? $entity->$field_name : FALSE;
    if (empty($paragraph_field)) {
      $message[] = $this->t("%field that does not exist on entity type %type", [
        "%field" => $field_name,
        "%type" => $host_type,
      ]);
    }

    if ($paragraph_field && $paragraph_field_items = $paragraph_field->referencedEntities()) {
      $setting = $this->getFieldSetting($entity, $field_name);
      $components = $this->getComponents($paragraph_field, $setting['view_mode']);
      $typeLabel = \Drupal::request()->get('type');
      if (empty($typeLabel)) {
        $typeLabel = 'data';
      }
      switch ($typeData){
        case 'table':
          $data = $this->getTable($paragraph_field_items, $components, $setting);
          break;
        case 'data':
          $data = $this->getData($paragraph_field_items, $components, $setting);
          break;
        default:
          $data = [
            $typeLabel => $this->getResults($paragraph_field_items, $components, $setting),
          ];
          break;
      }
      return new JsonResponse($data);
    }
    else {
      $message[] = $this->t("%field is not paragraphs", [
        "%field" => $field_name,
      ]);
    }
    return new JsonResponse([
      'error' => $message,
    ]);
  }

  /*
   * return output JSON data value
   */
  public function jsondata($field_name, $host_type, $host_id) {
    return $this->json($field_name, $host_type, $host_id, 'data');
  }

  /*
   * return html render field paragraphs
   */
  public function ajax($field_name, $host_type, $host_id) {
    return $this->json($field_name, $host_type, $host_id, 'table');
  }

  protected function getFieldSetting($entity, $field_name) {
    $bundle = $entity->bundle();
    $repository = \Drupal::service('entity_display.repository');
    $viewDisplay = $repository->getViewDisplay($entity->getEntityTypeId(), $bundle, 'default');
    $fieldComponent = $viewDisplay->getComponent($field_name);
    return $fieldComponent['settings'];
  }

  protected function getComponents($paragraph_field, $view_mode = 'default') {
    $field_definition = $paragraph_field->getFieldDefinition();
    $targetBundle = array_key_first($field_definition->getSetting("handler_settings")["target_bundles"]);
    $targetType = $field_definition->getSetting('target_type');
    $fieldsDefinitions = $this->entityTypeManager()->getStorage($targetType)
      ->create(['type' => $targetBundle])->getFieldDefinitions();
    $repository = \Drupal::service('entity_display.repository');
    $viewDisplay = $repository->getViewDisplay($targetType, $targetBundle, $view_mode);
    $components = $viewDisplay->getComponents();
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    foreach ($components as $field_name => $component) {
      if ($fieldsDefinitions[$field_name] instanceof FieldConfigInterface) {
        $this->fieldsDefinition[$field_name] = $fieldsDefinitions[$field_name];
        $components[$field_name]['title'] = $fieldsDefinitions[$field_name]->getLabel();
      }
    }
    $storage = $this->entityTypeManager()->getStorage('entity_view_display');
    $this->renderBuild = $storage->load(implode('.', [$targetType, $targetBundle, $view_mode]));

    return $components;
  }

  public function getResults($entities, $components, $setting = []) {
    $data = FALSE;
    foreach ($entities as $delta => $entitie) {
      $table_entity = $this->renderBuild->build($entitie);
      $objectData = new \stdClass();
      foreach ($components as $field_name => $field) {
        $table_entity[$field_name]['#label_display'] = 'hidden';
        $value = trim(strip_tags(render($table_entity[$field_name])));
        if (in_array($this->fieldsDefinition[$field_name]->getType(), [
          'integer',
          'list_integer',
          'number_integer',
        ])) {
          $value = (int) $value;
        }
        if (in_array($this->fieldsDefinition[$field_name]->getType(), ['boolean'])) {
          $list_value = $table_entity[$field_name]["#items"]->getValue();
          $value = (int) $list_value[0]['value'];
        }
        if (in_array($this->fieldsDefinition[$field_name]->getType(), [
          'decimal',
          'list_decimal',
          'number_decimal',
          'float',
          'list_float',
          'number_float',
        ])) {
          $value = (float) $value;
        }
        if (!is_numeric($value) && empty($value) && !empty($setting["empty_cell_value"])) {
          $value = $setting["empty_cell_value"];
        }

        $objectData->$field_name = $value;
      }
      $data[$delta] = $objectData;
    }
    return $data;
  }

  public function getTable($entities, $components, $setting = []) {
    $data = FALSE;
    foreach ($entities as $delta => $entity) {
      $table_entity = $this->renderBuild->build($entity);
      foreach ($components as $field_name => $field) {
        $table_entity[$field_name]['#label_display'] = 'hidden';
        $value = trim(render($table_entity[$field_name]));
        if (!is_numeric($value) && empty($value) && !empty($setting["empty_cell_value"])) {
          $value = $setting["empty_cell_value"];
        }
        $data[$delta][] = $value;
      }
    }
    return $data;
  }
  public function getData($entities, $components, $setting = []) {
    $data = FALSE;
    $header = [];
    foreach ($components as $field_name => $field) {
      $header[] = $field['title'];
    }
    foreach ($entities as $delta => $entity) {
      $table_entity = $this->renderBuild->build($entity);

      foreach ($components as $field_name => $field) {
        $table_entity[$field_name]['#label_display'] = 'hidden';
        $value = trim(strip_tags(render($table_entity[$field_name])));
        if (in_array($this->fieldsDefinition[$field_name]->getType(), [
          'integer',
          'list_integer',
          'number_integer',
        ])) {
          $value = (int) $value;
        }
        if (in_array($this->fieldsDefinition[$field_name]->getType(), ['boolean'])) {
          $list_value = $table_entity[$field_name]["#items"]->getValue();
          $value = (int) $list_value[0]['value'];
        }
        if (in_array($this->fieldsDefinition[$field_name]->getType(), [
          'decimal',
          'list_decimal',
          'number_decimal',
          'float',
          'list_float',
          'number_float',
        ])) {
          $value = (float) $value;
        }
        if (!is_numeric($value) && empty($value) && !empty($setting["empty_cell_value"])) {
          $value = $setting["empty_cell_value"];
        }

        $data[$delta][] = $value;
      }
    }

    array_unshift($data,$header);

    return $data;
  }
}

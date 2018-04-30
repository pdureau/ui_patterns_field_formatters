<?php

namespace Drupal\ui_patterns_field_formatters\Plugin\UiPatterns\Source;

use Drupal\ui_patterns\Plugin\PatternSourceBase;

/**
 * Defines Field values source plugin.
 *
 * @UiPatternsSource(
 *   id = "field_properties",
 *   label = @Translation("Field properties"),
 *   tags = {
 *     "field_properties"
 *   }
 * )
 */
class FieldPropertiesSource extends PatternSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSourceFields() {
    $sources = [];
    $storageDefinition = $this->getContextProperty('storageDefinition');
    $fields = $storageDefinition->getPropertyNames();
    foreach ($fields as $field) {
      if (!$this->getContextProperty('limit')) {
        $sources[] = $this->getSourceField($field, $storageDefinition->getPropertyDefinition($field)->getLabel());
      }
      elseif (in_array($field, $this->getContextProperty('limit'))) {
        $sources[] = $this->getSourceField($field, $storageDefinition->getPropertyDefinition($field)->getLabel());
      }
    }
    return $sources;
  }

}

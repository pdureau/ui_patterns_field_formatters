<?php

namespace Drupal\ui_patterns_field_formatters\Plugin\UiPatterns\Source;

use Drupal\ui_patterns\Plugin\PatternSourceBase;

/**
 * Defines Field values source plugin.
 *
 * @UiPatternsSource(
 *   id = "field_wrapper",
 *   label = @Translation("Field wrapper"),
 *   tags = {
 *     "field_wrapper"
 *   }
 * )
 */
class FieldWrapperSource extends PatternSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSourceFields() {
    $sources = [];
    $sources[] = $this->getSourceField('label', 'Label');
    $sources[] = $this->getSourceField('items', 'Items');
    return $sources;
  }

}

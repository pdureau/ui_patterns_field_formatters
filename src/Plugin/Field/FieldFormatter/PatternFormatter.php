<?php

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ui_patterns\Form\PatternDisplayFormTrait;
use Drupal\ui_patterns\UiPatternsSourceManager;
use Drupal\ui_patterns\UiPatternsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin implementation of the 'pattern' formatter.
 *
 * @FieldFormatter(
 *   id = "pattern_formatter",
 *   label = @Translation("Pattern"),
 *   field_types = {
 *     "boolean",
 *     "changed",
 *     "comment",
 *     "created",
 *     "datetime",
 *     "decimal",
 *     "email",
 *     "entity_reference",
 *     "file",
 *     "float",
 *     "image",
 *     "integer",
 *     "language",
 *     "link",
 *     "list_float",
 *     "list_integer",
 *     "list_string",
 *     "map",
 *     "password",
 *     "path",
 *     "string",
 *     "string_long",
 *     "telephone",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "timestamp",
 *     "uri",
 *     "uuid"
 *   },
 * )
 */
class PatternFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use PatternDisplayFormTrait;

  /**
   * UI Patterns manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  protected $patternsManager;

  /**
   * UI Patterns source manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsSourceManager
   */
  protected $sourceManager;

  /**
   * A module manager object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\ui_patterns\UiPatternsManager $patterns_manager
   *   UI Patterns manager.
   * @param \Drupal\ui_patterns\UiPatternsSourceManager $source_manager
   *   UI Patterns source manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, UiPatternsManager $patterns_manager, UiPatternsSourceManager $source_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->patternsManager = $patterns_manager;
    $this->sourceManager = $source_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.ui_patterns'),
      $container->get('plugin.manager.ui_patterns_source'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'pattern' => '',
      'variants' => '',
      'pattern_mapping' => [],
      // Used by ui_patterns_settings.
      'pattern_settings' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $field_storage_definition = $this->fieldDefinition->getFieldStorageDefinition();
    $context = [
      'storageDefinition' => $field_storage_definition,
      'limit' => $field_storage_definition->getPropertyNames(),
    ];
    // Some modifications to make 'variant' default value working.
    $configuration = $this->getSettings();
    $pattern = $this->getSetting('pattern');
    $pattern_variant = $this->getCurrentVariant($pattern);
    if (isset($pattern_variant)) {
      $configuration['pattern_variant'] = $pattern_variant;
    }

    $this->buildPatternDisplayForm($form, 'field_properties', $context, $configuration);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $pattern = $this->getSetting('pattern');

    if (!empty($pattern)) {
      $pattern_definition = $this->patternsManager->getDefinition($pattern);

      $label = $this->t('None');
      if (!empty($this->getSetting('pattern'))) {
        $label = $pattern_definition->getLabel();
      }
      $summary[] = $this->t('Pattern: @pattern', ['@pattern' => $label]);

      $pattern_variant = $this->getCurrentVariant($pattern);
      if (isset($pattern_variant)) {
        $variant = $this->getSetting('variants')[$pattern_definition->id()];
        $variant = $pattern_definition->getVariant($variant)->getLabel();
        $summary[] = $this->t('Variant: @variant', ['@variant' => $variant]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $pattern = $this->getSetting('pattern');

      // Set pattern fields.
      $fields = [];
      $mapping = $this->getSetting('pattern_mapping');
      $mapping = $mapping[$pattern]['settings'];
      foreach ($mapping as $source => $field) {
        if ($field['destination'] === '_hidden') {
          continue;
        }
        // Get rid of the source tag.
        $source = explode(":", $source)[1];
        $fields[$field['destination']] = (string) $item->get($source)->getValue();
      }

      // Set pattern render array.
      $elements[$delta] = [
        '#type' => 'pattern',
        '#id' => $this->getSetting('pattern'),
        '#fields' => $fields,
      ];

      // Set the variant.
      $pattern_variant = $this->getCurrentVariant($pattern);
      if (isset($pattern_variant)) {
        $elements[$delta]['#variant'] = $pattern_variant;
      }

      // Set the settings.
      $settings = $this->getSetting('pattern_settings');
      $pattern_settings = !empty($settings) && isset($settings[$pattern]) ? $settings[$pattern] : NULL;
      if (isset($pattern_settings)) {
        $elements[$delta]['#settings'] = $pattern_settings;
      }

      // Set pattern context.
      $entity = $items->getEntity();
      $elements[$delta]['#context'] = [
        'type' => 'field_formatter',
        'entity' => $entity,
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultValue(array $configuration, $field_name, $value) {
    // Some modifications to make 'destination' default value working.
    $pattern = $configuration['pattern'];
    if (isset($configuration['pattern_mapping'][$pattern]['settings'][$field_name][$value])) {
      return $configuration['pattern_mapping'][$pattern]['settings'][$field_name][$value];
    }
    return NULL;
  }

  /**
   * Checks if a given pattern has a corresponding value on the variants array.
   *
   * @param string $pattern
   *   Pattern ID.
   *
   * @return string|null
   *   Variant ID.
   */
  protected function getCurrentVariant($pattern) {
    $variants = $this->getSetting('variants');
    return !empty($variants) && isset($variants[$pattern]) ? $variants[$pattern] : NULL;
  }

}

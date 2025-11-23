<?php

namespace Drupal\graphql_translatable_config_pages\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\translatable_config_pages\TranslatableConfigPagesManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The config_page data producer.
 *
 * @DataProducer(
 *   id = "translatable_config_page",
 *   name = @Translation("Translatable Config Page"),
 *   description = @Translation("Load a config page by type."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Config Page")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Type"),
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Language"),
 *     )
 *   }
 * )
 */
class TranslatableConfigPage extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\config_pages\ConfigPagesInterface.
   *
   * @var \Drupal\translatable_config_pages\TranslatableConfigPagesManager
   */
  protected $configPagesLoader;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition,
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('translatable_config_pages.manager'),
    );
  }

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\translatable_config_pages\TranslatableConfigPagesManagerInterface $configPagesLoader
   *   The config pages loader.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    TranslatableConfigPagesManagerInterface $configPagesLoader,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->configPagesLoader = $configPagesLoader;
  }

  /**
   * The resolver.
   *
   * @param string $type
   *   The type of the config page.
   * @param string $language
   *   The language of the config page.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field
   *   The field context.
   */
  public function resolve(string $type, string $language, FieldContext $field) {
    $configPage = $this->configPagesLoader->loadConfig($type);
    if (!$configPage) {
      return NULL;
    }
    if ($language && $configPage->hasTranslation($language)) {
      $configPage = $configPage->getTranslation($language);
      $field->addCacheContexts(["static:language:{$language}"]);
    }

    // If a specific language is passed, use it for the context language.
    // We can safely assume that all language resolving inside a config page
    // should be based on the current language (passed in as an argument).
    // This fixes a bug (or unexpected behavior) where an untranslated config
    // page would resolve all fields in its default language instead of the
    // "current language".
    $contextLanguage = $language ?: $configPage->language()->getId();
    $field->setContextValue('language', $contextLanguage);
    return $configPage;
  }

}

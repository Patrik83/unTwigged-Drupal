<?php

namespace Drupal\graphql_translatable_config_pages\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_core_schema\CoreSchemaExtensionInterface;

/**
 * A schema extension for config pages.
 *
 * @SchemaExtension(
 *   id = "translatable_config_pages",
 *   name = "Translatable Config Pages",
 *   description = "An extension that provides translatable config pages.",
 *   schema = "core_composable"
 * )
 */
class TranslatableConfigPages extends SdlSchemaExtensionPluginBase implements CoreSchemaExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeDependencies() {
    return ['translatable_config_pages'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'globalConfig', $builder->compose(
      $builder->produce('translatable_config_page')
        ->map('type', $builder->fromValue('global'))
        ->map('language', $builder->produce('current_language'))
    ));

  }

}

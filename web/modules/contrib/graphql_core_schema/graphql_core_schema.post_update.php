<?php

/**
 * @file
 * Post-update functions for the GraphQL Core Schema module.
 */

declare(strict_types=1);

use Drupal\Core\Entity\ContentEntityTypeInterface;

/**
 * Update the schema configuration for core_composable servers for the new configuration options.
 */
function graphql_core_schema_post_update_new_configuration() {
  $storage = \Drupal::entityTypeManager()->getStorage('graphql_server');
  $ids = array_values($storage->getQuery()->accessCheck(FALSE)->execute());
  foreach ($ids as $id) {
    /** @var \Drupal\graphql\Entity\Server $server */
    $server = $storage->load($id);
    if ($server && $server->schema === 'core_composable') {
      $server->schema_configuration['core_composable']['entity_base_fields']['fields'] = [
        'uuid' => 1,
        'label' => 1,
        'langcode' => 1,
        'getConfigTarget' => 1,
        'uriRelationships' => 1,
        'referencedEntities' => 1,
        'entityTypeId' => 1,
        'isNew' => 1,
        'accessCheck' => 1,
      ];
      $server->schema_configuration['core_composable']['generate_value_fields'] = 1;
      $server->save();
    }
  }
}

/**
 * Preserve the exporting of all bundles for enabled entity types.
 */
function graphql_core_schema_post_update_enable_all_bundles() {
  // We are introducing a new configuration option to choose which bundles
  // are included in the GraphQL schema. In previous versions, all bundles were
  // included by default. Enable all bundles for all enabled entity types so
  // that existing configurations are not affected.
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('graphql_server');
  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
  $bundle_info = \Drupal::service('entity_type.bundle.info');

  /** @var \Drupal\graphql\Entity\ServerInterface[] $servers */
  $servers = $storage->loadByProperties(['schema' => 'core_composable']);
  foreach ($servers as $server) {
    $config = $server->get('schema_configuration');
    if (!empty($config['core_composable']['enabled_entity_types'])) {
      $entity_type_ids = array_filter(array_keys($config['core_composable']['enabled_entity_types']));

      foreach ($entity_type_ids as $entity_type_id) {
        if (!$entity_type_manager->getDefinition($entity_type_id) instanceof ContentEntityTypeInterface) {
          continue;
        }
        $bundle_ids = array_keys($bundle_info->getBundleInfo($entity_type_id));
        $config['core_composable']['bundles'][$entity_type_id] = array_combine($bundle_ids, $bundle_ids);
      }
    }
    $server->set('schema_configuration', $config);
    $server->save();
  }
}

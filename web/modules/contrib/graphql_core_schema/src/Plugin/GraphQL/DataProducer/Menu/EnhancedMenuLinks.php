<?php

namespace Drupal\graphql_core_schema\Plugin\GraphQL\DataProducer\Menu;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return the menu links of a menu.
 *
 * @DataProducer(
 *   id = "menu_links_with_params",
 *   name = @Translation("Menu links"),
 *   description = @Translation("Returns the menu links of a menu."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Menu link"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "menu" = @ContextDefinition("entity:menu",
 *       label = @Translation("Menu")
 *     ),
 *     "root" = @ContextDefinition("string",
 *        label = @Translation("Root"),
 *        required = FALSE
 *      ),
 *      "activeTrailIds" = @ContextDefinition("any",
 *         label = @Translation("activeTrailIds"),
 *         required = FALSE
 *      ),
 *     "minDepth" = @ContextDefinition("integer",
 *        label = @Translation("minDepth"),
 *        required = FALSE
 *      ),
 *     "maxDepth" = @ContextDefinition("integer",
 *         label = @Translation("maxDepth"),
 *         required = FALSE
 *      )
 *   }
 * )
 */
class EnhancedMenuLinks extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('menu.link_tree')
    );
  }

  /**
   * MenuItems constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MenuLinkTreeInterface $menuLinkTree) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->menuLinkTree = $menuLinkTree;
  }

  /**
   * Resolver.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The Menu.
   * @param mixed $root
   *   A menu link plugin ID that should be used as the root.
   * @param mixed $activeTrailIds
   *   The IDs from the currently active menu link to the root of the whole tree.
   * @param mixed $minDepth
   *   The minimum depth of menu links in the resulting tree relative to the root.
   * @param mixed $maxDepth
   *   The maximum depth of menu links in the resulting tree relative to the root.
   * @param FieldContext $context
   *   The GraphQL field context.
   *
   * @return array
   *   The menu tree links.
   */
  public function resolve(MenuInterface $menu, $root, $activeTrailIds, $minDepth, $maxDepth) {
    $params = new MenuTreeParameters();

    if (!empty($root)) {
      $params->setRoot($root);
    }
    if (!empty($minDepth)) {
      $params->setMinDepth($minDepth);
    }
    if (!empty($maxDepth)) {
      $params->setMaxDepth($maxDepth);
    }
    if (!empty($activeTrailIds)) {
      $params->setActiveTrail($activeTrailIds);
    }

    $tree = $this->menuLinkTree->load($menu->id(), $params);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $terms = array_filter($this->menuLinkTree->transform($tree, $manipulators), function (MenuLinkTreeElement $item) {
      return $item->link instanceof MenuLinkInterface && $item->link->isEnabled();
    });

    return $terms;
  }

}

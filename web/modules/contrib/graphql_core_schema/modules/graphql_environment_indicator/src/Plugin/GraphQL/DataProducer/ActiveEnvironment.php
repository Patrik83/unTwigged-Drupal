<?php

namespace Drupal\graphql_environment_indicator\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A data producer to get the active environment.
 *
 * @DataProducer(
 *   id = "active_environment",
 *   name = @Translation("Active Environment"),
 *   description = @Translation("Return the currently active environment."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Active Environment")
 *   ),
 * )
 */
class ActiveEnvironment extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The active environment.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $activeEnvironment;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * ActiveEnvironment constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $account,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->activeEnvironment = $configFactory->get('environment_indicator.indicator');
    $this->currentUser = $account;

  }

  /**
   * The resolver.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The active environment config.
   */
  public function resolve() {
    if (!$this->activeEnvironment) {
      return NULL;
    }

    $title = $this->activeEnvironment->get('name');
    if (empty($title) || !$this->currentUser->hasPermission('access environment indicator')) {
      return NULL;
    }

    return $this->activeEnvironment;
  }

}

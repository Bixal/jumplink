<?php

namespace Drupal\jumplink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Jumplinks' block.
 *
 * @Block(
 *  id = "jumplinks",
 *  admin_label = @Translation("Jumplinks"),
 * )
 */
class Jumplinks extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Configuration settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array
                              $configuration,
                              $plugin_id,
                              $plugin_definition,
                              CurrentRouteMatch $currentRouteMatch,
                              ConfigFactory $config
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->config->get('jumplink.settings');
    $paragraph_field_name = $config->get('field_name');
    $paragraph_machine_name = $config->get('paragraph_machine_name');
    $paragraph_type = $config->get('paragraph_type');
    $field_name = $config->get('field_name');
    $build = ['#theme' => 'jumplinks', '#jumplinks' => NULL];
    $jumplinks = [];

    $current_node = $this->currentRouteMatch->getParameter('node');

    if ($current_node == NULL || !$current_node->hasField($paragraph_machine_name)) {
      return $build;
    }

    $paragraphs = $current_node->get($paragraph_machine_name)->getValue();

    foreach ($paragraphs as $p) {
      $paragraph = Paragraph::load($p['target_id']);
      if ($this->validateParagraph($paragraph, $paragraph_field_name)) {
        if (!empty($paragraph_type) || $paragraph_type !== $paragraph->getType()) {
          $jumplinks[] = $this->buildJumplink($paragraph, $field_name);
        }
      }
    }

    if (count($jumplinks) >= 2) {
      $build['#jumplinks'] = $jumplinks;
    }

    return $build;
  }

  /**
   * @param Paragraph $paragraph
   * @param $field_name
   * @return array A renderable array with our jumplink.
   */
  private function buildJumplink(Paragraph $paragraph, $field_name){
    return [
      'header' => $paragraph->get($field_name)->value,
      'path' => "#paragraph-{$paragraph->id()}",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    /* If you depends on \Drupal::routeMatch()
    you must set context of this block with 'route' context tag.
    every new route this block will rebuild */
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

  /**
   * @param Paragraph $paragraph
   * @param string $field_name
   *   Optional. Limit Jumplinks to a single field bundle on paragraphs.
   * @return bool
   *
   * Validate we have a paragraph, and that the correct data is available.
   */
  private function validateParagraph(Paragraph $paragraph, $field_name){
    if ($paragraph instanceof Paragraph) {
      if ($paragraph->__isset($field_name) && !$paragraph->get($field_name)->isEmpty()) {
        return true;
      }
    }
    return false;
  }
}

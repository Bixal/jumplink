<?php

namespace Drupal\jumplink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render = [
      '#type' => 'markup',
      '#markup' => '<div class="section-jump-links"><div class="jump-links">',
      '#weight' => -50,
    ];
    $jumplinks = [];
    $build['jumplinks'] = '';
    $current_node = $this->currentRouteMatch->getParameter('node');
    if ($current_node == NULL) {
      return $build;
    }
    if ($current_node->hasField('field_paragraph')) {
      $paragraphs = $current_node->get('field_paragraph')->getValue();
      foreach ($paragraphs as $p) {
        $paragraph = Paragraph::load($p['target_id']);
        if ($paragraph &&
        $paragraph->getType() == 'basic_text' &&
        $paragraph->__isset('field_plain_title') &&
        !$paragraph->get('field_plain_title')->isEmpty()) {
          $jumplinks[] = [
            'header' => $paragraph->get('field_plain_title')->value,
            'path' => "#" . Html::getId($paragraph->get('field_plain_title')->value),
          ];
        }
      }
    }
    if (count($jumplinks) >= 2) {
      foreach ($jumplinks as $jumplink) {
        $render['#markup'] .= "<div class='jump-link'><a href='{$jumplink['path']}'>{$jumplink['header']}</a></div>";
      }
      $render['#markup'] .= '</div></div>';
      $build['jumplinks'] = $render;
    }
    return $build;
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

}

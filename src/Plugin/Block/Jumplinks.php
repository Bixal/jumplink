<?php

namespace Drupal\jumplink\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Jumplinks' block.
 *
 * @Block(
 *  id = "jumplinks",
 *  admin_label = @Translation("Jumplinks"),
 * )
 */
class Jumplinks extends BlockBase implements ContainerFactoryPluginInterface {

  protected $current_route_match;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentRouteMatch $current_route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->current_route_match = $current_route_match;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
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
    $current_node = $this->current_route_match->getParameter('node');
    if ($current_node == null) return $build;
    if ($current_node->hasField('field_paragraph')) {
      $paragraphs = $current_node->get('field_paragraph')->getValue();
      foreach($paragraphs as $p) {
        $paragraph = \Drupal\paragraphs\Entity\Paragraph::load( $p['target_id'] );
        if($paragraph &&
        $paragraph->getType() == 'basic_text' &&
        $paragraph->__isset('field_plain_title') &&
        !$paragraph->get('field_plain_title')->isEmpty()) {
          $jumplinks[] = [
            'header' => $paragraph->get('field_plain_title')->value,
            'path' => "#paragraph-{$paragraph->id()}"
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

  public function getCacheTags() {
    //With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }

  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }
}

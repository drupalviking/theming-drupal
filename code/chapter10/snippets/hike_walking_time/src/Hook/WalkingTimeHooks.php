<?php

namespace Drupal\hike_walking_time\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The walking time, as a pseudo-field any theme can show.
 */
class WalkingTimeHooks {

  use StringTranslationTrait;

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Implements hook_entity_extra_field_info().
   */
  #[Hook('entity_extra_field_info')]
  public function extraFieldInfo(): array {
    $extra['node']['hike']['display']['walking_time'] = [
      'label' => $this->t('Walking time'),
      'description' => $this->t("Estimated with Naismith's rule."),
      'weight' => 10,
      'visible' => FALSE,
    ];
    return $extra;
  }

  /**
   * Implements hook_ENTITY_TYPE_view() for nodes.
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, EntityInterface $node, EntityViewDisplayInterface $display, string $view_mode): void {
    if ($display->getComponent('walking_time') && !$node->get('field_distance')->isEmpty()) {
      $minutes = $node->get('field_distance')->value * 12
        + (int) $node->get('field_elevation_gain')->value / 100 * 10;
      $build['walking_time'] = [
        '#markup' => $this->t('About @time on foot', [
          '@time' => $this->dateFormatter->formatInterval(round($minutes / 10) * 600),
        ]),
      ];
    }
  }

}

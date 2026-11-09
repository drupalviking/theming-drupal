<?php

namespace Drupal\lens_trail\Hook;

use Drupal\block\Entity\Block;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Four template suggestions core doesn't offer.
 */
class SuggestionHooks {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * By content type: page--node--hike.html.twig.
   */
  #[Hook('theme_suggestions_page_alter')]
  public function pageByContentType(array &$suggestions, array $variables): void {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $suggestions[] = 'page__node__' . $node->bundle();
    }
  }

  /**
   * By view mode: field--node--field-date--card.html.twig.
   */
  #[Hook('theme_suggestions_field_alter')]
  public function fieldByViewMode(array &$suggestions, array $variables): void {
    $element = $variables['element'];
    $suggestions[] = 'field__' . $element['#entity_type'] . '__' . $element['#field_name'] . '__' . $element['#view_mode'];
  }

  /**
   * By region: block--footer.html.twig.
   */
  #[Hook('theme_suggestions_block_alter')]
  public function blockByRegion(array &$suggestions, array $variables): void {
    $id = $variables['elements']['#id'] ?? NULL;
    if ($id && $block = Block::load($id)) {
      // Before the others, so the block's own suggestions still win.
      array_unshift($suggestions, 'block__' . $block->getRegion());
    }
  }

  /**
   * By a field's value: node--hike--card--hard.html.twig.
   */
  #[Hook('theme_suggestions_node_alter')]
  public function nodeByDifficulty(array &$suggestions, array $variables): void {
    $node = $variables['elements']['#node'];
    if ($node->bundle() === 'hike' && !$node->get('field_difficulty')->isEmpty()) {
      $suggestions[] = 'node__hike__' . $variables['elements']['#view_mode'] . '__' . $node->get('field_difficulty')->value;
    }
  }

}

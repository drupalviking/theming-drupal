<?php

/**
 * @file
 * Chapter 19, exercise 6 (Appendix B), on the core site: More news, at the end
 * of a news item's page, the three newest news items except the one on the
 * page, as cards, with the All news link.
 *
 * What the Views UI does, as a script: a second block display on the News
 * view, with a contextual filter on the node ID, taken from the URL and
 * excluded; then the block, in the Content region, on news items only.
 *
 * Run from the site's folder: ddev drush php:script book2-site/more-news.php
 */

use Drupal\block\Entity\Block;
use Drupal\views\Entity\View;

$view = View::load('news');
$latest = $view->getDisplay('block_1');
$view->getExecutable()->newDisplay('block', 'More news', 'block_2');
$more = &$view->getDisplay('block_2');
$more['display_options'] = [
  'title' => 'More news',
  'block_description' => 'More news',
  'pager' => ['type' => 'some', 'options' => ['items_per_page' => 3, 'offset' => 0]],
  'arguments' => [
    'nid' => [
      'id' => 'nid',
      'table' => 'node_field_data',
      'field' => 'nid',
      'entity_type' => 'node',
      'entity_field' => 'nid',
      'plugin_id' => 'node_nid',
      // The node on the page, from the URL...
      'default_action' => 'default',
      'default_argument_type' => 'node',
      'default_argument_options' => [],
      // ...left out, instead of being the only one.
      'not' => TRUE,
      'specify_validation' => FALSE,
      'break_phrase' => FALSE,
    ],
  ],
  'defaults' => ['title' => FALSE, 'pager' => FALSE, 'arguments' => FALSE, 'empty' => FALSE] + $latest['display_options']['defaults'],
  // No "No news yet" here: with nothing to show, the block isn't shown.
  'empty' => [],
  'block_hide_empty' => TRUE,
] + $latest['display_options'];
$view->save();

\Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.news')
  ->set('display.block_2.display_options.title', 'Más noticias')
  ->set('display.block_2.display_options.use_more_text', 'Todas las noticias')
  ->save();

Block::load('lens_trail_more_news')?->delete();
Block::create([
  'id' => 'lens_trail_more_news',
  'theme' => 'lens_trail',
  'region' => 'content',
  'weight' => 100,
  'plugin' => 'views_block:news-block_2',
  'settings' => [
    'id' => 'views_block:news-block_2',
    'label' => '',
    'label_display' => 'visible',
    'provider' => 'views',
    'views_label' => '',
    'items_per_page' => 'none',
  ],
  'visibility' => [
    'entity_bundle:node' => [
      'id' => 'entity_bundle:node',
      'negate' => FALSE,
      'context_mapping' => ['node' => '@node.node_route_context:node'],
      'bundles' => ['news' => 'news'],
    ],
  ],
])->save();

drupal_flush_all_caches();
echo "Done.\n";

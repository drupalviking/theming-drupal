<?php

/**
 * @file
 * Chapter 19, step 01, on both test sites: the club's news, which could only
 * be reached by its address. The chapter doesn't walk through building this:
 * the page is Book 1's Chapter 12, exercise 1, done as the exercise says.
 *
 * - A view, News (news): published news items in their own language, newest
 *   first, with a line for when there's no news yet.
 *   - Page: /news, titled News, ten teasers to a page with a full pager, and
 *     a News link in the main menu after Albums.
 *   - Block: Latest news, the two newest items in the Card view mode, with
 *     an All news link to the page.
 * - News's Teaser display (the text is Body on core, Content on Drupal CMS): the photo (as on hike cards) and the text trimmed
 *   to 300 characters; no links. A Card display: the same, trimmed to 160.
 * - A date format, Day date (day_date), for the news templates.
 * - Spanish: Noticias, Últimas noticias, Todas las noticias, the empty text.
 * - On the core site only: the Latest news block in the sidebar, on the front
 *   page, after Next hikes. (On Drupal CMS, Home is a Canvas page: the block
 *   goes in there, in step 05.)
 *
 * Run from the site's folder: ddev drush php:script book2-site/01-news.php
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Entity\View;

// A date format for the day a news item came out: "5 November 2026", in
// Spanish "5 de Noviembre de 2026". The templates use it by its machine name.
DateFormat::load('day_date')?->delete();
DateFormat::create([
  'id' => 'day_date',
  'label' => 'Day date',
  'pattern' => 'j F Y',
])->save();
\Drupal::languageManager()->getLanguageConfigOverride('es', 'core.date_format.day_date')
  ->set('label', 'Fecha del día')
  ->set('pattern', 'j \\d\\e F \\d\\e Y')
  ->save();

// The core site's style for a photo in a list; Drupal CMS has none by that
// name, but a 3:2 one with the focal point (the shape the teaser crops to).
// The news text: Body on the core site, Content on Drupal CMS.
$text = FieldConfig::loadByName('node', 'news', 'field_body') ? 'field_body' : 'field_content';
$style = ImageStyle::load('max_650x650') ? 'max_650x650' : '3_2_720x480_focal_point_webp';
// The displays.
$photo = [
  'type' => 'media_thumbnail',
  'label' => 'hidden',
  'settings' => [
    'image_link' => '',
    'image_style' => $style,
    'image_loading' => ['attribute' => 'lazy'],
  ],
  'weight' => 0,
];
$body = fn (int $length) => [
  'type' => 'text_trimmed',
  'label' => 'hidden',
  'settings' => ['trim_length' => $length],
  'weight' => 1,
];
foreach (['teaser' => 300, 'card' => 160] as $mode => $length) {
  $display = EntityViewDisplay::load("node.news.$mode") ?? EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'news',
    'mode' => $mode,
    'status' => TRUE,
  ]);
  foreach (array_keys($display->getComponents()) as $name) {
    $display->removeComponent($name);
  }
  $display->setComponent('field_photo', $photo)
    ->setComponent($text, $body($length))
    ->setStatus(TRUE)
    ->save();
}

// The view.
View::load('news')?->delete();

$filter = fn (string $field, string $plugin, $value, array $extra = []) => [
  'id' => $field,
  'table' => 'node_field_data',
  'field' => $field,
  'entity_type' => 'node',
  'entity_field' => $field,
  'plugin_id' => $plugin,
  'value' => $value,
  'group' => 1,
] + $extra;

View::create([
  'id' => 'news',
  'label' => 'News',
  'module' => 'views',
  'description' => 'The club\'s news, newest first.',
  'base_table' => 'node_field_data',
  'base_field' => 'nid',
  'display' => [
    'default' => [
      'id' => 'default',
      'display_title' => 'Default',
      'display_plugin' => 'default',
      'position' => 0,
      'display_options' => [
        'title' => 'News',
        'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
        'cache' => ['type' => 'tag', 'options' => []],
        'query' => ['type' => 'views_query', 'options' => []],
        'exposed_form' => ['type' => 'basic', 'options' => []],
        'pager' => ['type' => 'full', 'options' => ['items_per_page' => 10, 'offset' => 0]],
        'style' => ['type' => 'default', 'options' => ['row_class' => '', 'default_row_class' => TRUE, 'uses_fields' => FALSE]],
        'row' => ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'teaser']],
        'fields' => [],
        'filters' => [
          'status' => $filter('status', 'boolean', '1'),
          'type' => $filter('type', 'bundle', ['news' => 'news']),
          'default_langcode' => $filter('default_langcode', 'boolean', '1', ['operator' => '=']),
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'entity_type' => 'node',
            'entity_field' => 'created',
            'plugin_id' => 'date',
            'order' => 'DESC',
            'granularity' => 'second',
          ],
        ],
        'empty' => [
          'area_text_custom' => [
            'id' => 'area_text_custom',
            'table' => 'views',
            'field' => 'area_text_custom',
            'plugin_id' => 'text_custom',
            'empty' => TRUE,
            'content' => 'No news yet.',
            'tokenize' => FALSE,
          ],
        ],
        'rendering_language' => '***LANGUAGE_language_interface***',
        'display_extenders' => [],
      ],
    ],
    'page_1' => [
      'id' => 'page_1',
      'display_title' => 'Page',
      'display_plugin' => 'page',
      'position' => 1,
      'display_options' => [
        'path' => 'news',
        // Not inherited from the default display: each display has its own.
        'rendering_language' => '***LANGUAGE_language_interface***',
        'menu' => [
          'type' => 'normal',
          'title' => 'News',
          'description' => '',
          // After Albums (2). About and Join us move down one, below.
          'weight' => 3,
          'expanded' => FALSE,
          'menu_name' => 'main',
          'parent' => '',
          'context' => '',
        ],
        'display_extenders' => [],
      ],
    ],
    'block_1' => [
      'id' => 'block_1',
      'display_title' => 'Latest news',
      'display_plugin' => 'block',
      'position' => 2,
      'display_options' => [
        'title' => 'Latest news',
        'defaults' => ['title' => FALSE, 'pager' => FALSE, 'row' => FALSE, 'use_more' => FALSE, 'use_more_always' => FALSE, 'use_more_text' => FALSE, 'link_display' => FALSE, 'link_url' => FALSE],
        'pager' => ['type' => 'some', 'options' => ['items_per_page' => 2, 'offset' => 0]],
        'row' => ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'card']],
        'use_more' => TRUE,
        'use_more_always' => TRUE,
        'use_more_text' => 'All news',
        'link_display' => 'page_1',
        'link_url' => '',
        'block_description' => 'Latest news',
        'rendering_language' => '***LANGUAGE_language_interface***',
        'display_extenders' => [],
      ],
    ],
  ],
])->save();

// About and Join us move down one, to make room for News after Albums. (Links
// with the same weight go in alphabetical order, which isn't the same order
// in Spanish: Álbumes, Noticias, but Albums, News.)
foreach (['About' => 4, 'Join us' => 5] as $title => $weight) {
  foreach (\Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => 'main', 'title' => $title]) as $link) {
    $link->set('weight', $weight)->save();
  }
}

// Spanish.
\Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.news')
  ->set('label', 'Noticias')
  ->set('display.default.display_options.title', 'Noticias')
  ->set('display.default.display_options.empty.area_text_custom.content', 'Aún no hay noticias.')
  ->set('display.page_1.display_options.menu.title', 'Noticias')
  ->set('display.block_1.display_options.title', 'Últimas noticias')
  ->set('display.block_1.display_options.use_more_text', 'Todas las noticias')
  ->save();

// The block, on the core site's front page.
$theme = \Drupal::config('system.theme')->get('default');
if ($theme === 'lens_trail') {
  Block::load('lens_trail_latest_news')?->delete();
  Block::create([
    'id' => 'lens_trail_latest_news',
    'theme' => 'lens_trail',
    'region' => 'sidebar',
    'weight' => 2,
    'plugin' => 'views_block:news-block_1',
    'settings' => [
      'id' => 'views_block:news-block_1',
      'label' => '',
      'label_display' => 'visible',
      'provider' => 'views',
      'views_label' => '',
      'items_per_page' => 'none',
    ],
    'visibility' => [
      'request_path' => [
        'id' => 'request_path',
        'pages' => '<front>',
        'negate' => FALSE,
      ],
    ],
  ])->save();
}

drupal_flush_all_caches();
echo "Done.\n";

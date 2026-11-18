<?php

/**
 * @file
 * Chapter 19, step 04, on both test sites: the albums.
 *
 * - An album's Card display: its photos as thumbnails (the template shows the
 *   first). The Albums page (albums, page_1) shows its albums in it, instead
 *   of fields.
 * - Albums from this hike (albums, block_1) stays on fields, but only two:
 *   the title and the first photo, as a thumbnail. (The hike and its date
 *   were the hike the block is on.)
 * - A new view, Photo themes (photo_themes): the photos tagged with the photo
 *   theme whose page we're on, in the Album display, 24 to a page. A block
 *   display, "Photos", placed on photo theme pages (core site only; on
 *   Drupal CMS, see step 05).
 * - Spanish for the new view.
 * - More photos with photo themes: each theme had one photo. The themes are
 *   added to what the photos have, by the photos' and the themes' names.
 *
 * Run from the site's folder: ddev drush php:script book2-site/04-albums.php
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\Entity\View;

// The core site's style for a photo in a list; Drupal CMS has none by that
// name, but a 3:2 one with the focal point (the shape the teaser crops to).
$style = ImageStyle::load('max_650x650') ? 'max_650x650' : '3_2_720x480_focal_point_webp';
$thumbnail = [
  'type' => 'media_thumbnail',
  'label' => 'hidden',
  'settings' => [
    'image_link' => '',
    'image_style' => $style,
    'image_loading' => ['attribute' => 'lazy'],
  ],
  'weight' => 0,
];

// The album's Card display.
$display = EntityViewDisplay::load('node.gallery.card') ?? EntityViewDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'gallery',
  'mode' => 'card',
  'status' => TRUE,
]);
foreach (array_keys($display->getComponents()) as $name) {
  $display->removeComponent($name);
}
$display->setComponent('field_photos', $thumbnail)->setStatus(TRUE)->save();

// The Albums page: rows in the Card display.
$view = View::load('albums');
$page = &$view->getDisplay('page_1');
$page['display_options']['defaults']['row'] = FALSE;
$page['display_options']['row'] = [
  'type' => 'entity:node',
  'options' => ['relationship' => 'none', 'view_mode' => 'card'],
];

// Albums from this hike: the title and the first photo.
$default = $view->getDisplay('default');
$fields = $default['display_options']['fields'];
$block = &$view->getDisplay('block_1');
$block['display_options']['defaults']['fields'] = FALSE;
$block['display_options']['fields'] = [
  'title' => $fields['title'],
  'field_photos' => [
    'type' => 'media_thumbnail',
    'settings' => $thumbnail['settings'],
    'delta_limit' => 1,
    'label' => '',
  ] + $fields['field_photos'],
];
$block['display_options']['fields']['title']['settings']['link_to_entity'] = FALSE;
$view->save();

// Photo themes.
View::load('photo_themes')?->delete();
$filter = fn (string $field, string $plugin, $value) => [
  'id' => $field,
  'table' => 'media_field_data',
  'field' => $field,
  'entity_type' => 'media',
  'entity_field' => $field,
  'plugin_id' => $plugin,
  'value' => $value,
  'group' => 1,
];
View::create([
  'id' => 'photo_themes',
  'label' => 'Photo themes',
  'module' => 'views',
  'description' => 'The photos with a photo theme, on the theme\'s page.',
  'base_table' => 'media_field_data',
  'base_field' => 'mid',
  'display' => [
    'default' => [
      'id' => 'default',
      'display_title' => 'Default',
      'display_plugin' => 'default',
      'position' => 0,
      'display_options' => [
        'title' => 'Photos',
        'access' => ['type' => 'perm', 'options' => ['perm' => 'view media']],
        'cache' => ['type' => 'tag', 'options' => []],
        'query' => ['type' => 'views_query', 'options' => []],
        'exposed_form' => ['type' => 'basic', 'options' => []],
        'pager' => ['type' => 'mini', 'options' => ['items_per_page' => 24, 'offset' => 0]],
        'style' => ['type' => 'default', 'options' => ['row_class' => '', 'default_row_class' => TRUE, 'uses_fields' => FALSE]],
        'row' => ['type' => 'entity:media', 'options' => ['relationship' => 'none', 'view_mode' => 'album']],
        'fields' => [],
        'arguments' => [
          'field_themes_target_id' => [
            'id' => 'field_themes_target_id',
            'table' => 'media__field_themes',
            'field' => 'field_themes_target_id',
            'plugin_id' => 'numeric',
            // The term whose page we're on.
            'default_action' => 'default',
            'default_argument_type' => 'taxonomy_tid',
            'default_argument_options' => [
              'term_page' => '1',
              'node' => FALSE,
              'limit' => FALSE,
              'vids' => [],
              'anyall' => ',',
            ],
            'specify_validation' => TRUE,
            // Not a photo theme's page: no photos, and no block, rather than
            // the empty text on every page.
            'validate' => ['type' => 'entity:taxonomy_term', 'fail' => 'not found'],
            'validate_options' => ['bundles' => ['photo_themes' => 'photo_themes'], 'access' => FALSE, 'operation' => 'view', 'multiple' => 0],
          ],
        ],
        'filters' => [
          'status' => $filter('status', 'boolean', '1'),
          'bundle' => $filter('bundle', 'bundle', ['image' => 'image']),
          'default_langcode' => $filter('default_langcode', 'boolean', '1') + ['operator' => '='],
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'media_field_data',
            'field' => 'created',
            'entity_type' => 'media',
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
            'content' => 'No photos with this theme yet.',
            'tokenize' => FALSE,
          ],
        ],
        'rendering_language' => '***LANGUAGE_language_interface***',
        'display_extenders' => [],
      ],
    ],
    'block_1' => [
      'id' => 'block_1',
      'display_title' => 'Photos',
      'display_plugin' => 'block',
      'position' => 1,
      'display_options' => [
        'block_description' => 'Photos with this theme',
        'rendering_language' => '***LANGUAGE_language_interface***',
        'display_extenders' => [],
      ],
    ],
  ],
])->save();

\Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.photo_themes')
  ->set('label', 'Temas de fotos')
  ->set('display.default.display_options.title', 'Fotos')
  ->set('display.default.display_options.empty.area_text_custom.content', 'Aún no hay fotos con este tema.')
  ->set('display.block_1.display_options.block_description', 'Fotos con este tema')
  ->save();

// Photo themes on the photos.
$themes = [
  'Turnhouse Hill across Glencorse Reservoir' => ['Hills', 'Water', 'Landscape'],
  'Edinburgh\'s Old Town from Calton Hill' => ['City'],
  'Carnethy Hill from Turnhouse Hill' => ['Hills', 'Landscape'],
  'The view from Carnethy Hill' => ['Hills', 'Landscape'],
  'Glencorse Reservoir from the hillside' => ['Water', 'Landscape'],
  'A burn in the Pentland Hills in winter' => ['Winter', 'Water', 'Hills'],
];
$terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($themes as $photo => $names) {
  foreach (\Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $photo]) as $media) {
    $tids = array_column($media->get('field_themes')->getValue(), 'target_id');
    foreach ($names as $name) {
      foreach ($terms->loadByProperties(['vid' => 'photo_themes', 'name' => $name]) as $term) {
        $tids[] = $term->id();
      }
    }
    $media->set('field_themes', array_values(array_unique($tids)))->save();
  }
}

// The block, on the core site's photo theme pages.
if (\Drupal::config('system.theme')->get('default') === 'lens_trail') {
  Block::load('lens_trail_photo_theme_photos')?->delete();
  Block::create([
    'id' => 'lens_trail_photo_theme_photos',
    'theme' => 'lens_trail',
    'region' => 'content',
    'weight' => 100,
    'plugin' => 'views_block:photo_themes-block_1',
    'settings' => [
      'id' => 'views_block:photo_themes-block_1',
      'label' => '',
      'label_display' => '0',
      'provider' => 'views',
      'views_label' => '',
      'items_per_page' => 'none',
    ],
    'visibility' => [
      'entity_bundle:taxonomy_term' => [
        'id' => 'entity_bundle:taxonomy_term',
        'negate' => FALSE,
        'context_mapping' => ['taxonomy_term' => '@taxonomy_term.taxonomy_term_route_context:taxonomy_term'],
        'bundles' => ['photo_themes' => 'photo_themes'],
      ],
    ],
  ])->save();
}

drupal_flush_all_caches();
echo "Done.\n";

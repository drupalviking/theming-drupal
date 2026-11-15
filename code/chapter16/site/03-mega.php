<?php

/**
 * @file
 * Chapter 16's mega menu on the core test site: the site building the
 * chapter does by hand, after 03-mega-modules.sh has installed Menu Item
 * Extras and Views Reference.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder. The
 * same script does the Drupal CMS site, where two things differ: the photo's
 * image style (Drupal CMS has its own 16:9 styles, not Chapter 14's banner
 * styles) and the archive link's ID, so the script finds the link by its path.
 *
 * 1. Main navigation's links translatable again: Menu Item Extras gives each
 *    menu its own bundle, and the first book's setting was for the old one.
 * 2. Hikes becomes a menu link of its own: the hikes view's menu entry can't
 *    have fields. Hike archive moves under it.
 * 3. A view of the top-level areas, and a Next hikes display for the menu.
 * 4. A Menu panel view mode for photos.
 * 5. Two fields on Main navigation's links: Panel lists (Views Reference)
 *    and Panel photo (Media), with their form and display settings.
 * 6. The Hikes link's panel: the two lists and the winter photo.
 * 7. The panel's headings in Spanish: the views' titles and the more link
 *    are configuration, translated on the views' Translate tab.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\views\Entity\View;

// 1. Translatable, as the first book set up menu links.
$old = ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'menu_link_content');
$settings = ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'main');
$settings->setDefaultLangcode($old->getDefaultLangcode())
  ->setLanguageAlterable($old->isLanguageAlterable())
  ->setThirdPartySetting('content_translation', 'enabled', TRUE)
  ->save();

// 2. Hikes as a menu link, with the archive under it.
$hikes_view = View::load('hikes');
$display = &$hikes_view->getDisplay('page_1');
$display['display_options']['menu']['type'] = 'none';
$hikes_view->save();

$hikes = MenuLinkContent::create([
  'title' => 'Hikes',
  'link' => ['uri' => 'internal:/hikes'],
  'menu_name' => 'main',
  'weight' => 1,
  'expanded' => TRUE,
  'langcode' => 'en',
]);
$hikes->save();
$hikes->addTranslation('es', ['title' => 'Excursiones'] + $hikes->toArray())->save();

$archives = \Drupal::entityTypeManager()->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main', 'link.uri' => 'internal:/hikes/archive']);
current($archives)->set('parent', 'menu_link_content:' . $hikes->uuid())->save();

// 3a. The areas: the top level of the Areas vocabulary, as links.
$name_field = [
  'id' => 'name',
  'table' => 'taxonomy_term_field_data',
  'field' => 'name',
  'entity_type' => 'taxonomy_term',
  'entity_field' => 'name',
  'plugin_id' => 'field',
  'label' => '',
  'type' => 'string',
  'settings' => ['link_to_entity' => TRUE],
  'element_default_classes' => FALSE,
];
$filter = fn(string $id, string $table, string $field, string $plugin, array $more) => [
  'id' => $id, 'table' => $table, 'field' => $field, 'plugin_id' => $plugin, 'group' => 1,
] + $more;
View::create([
  'id' => 'areas',
  'label' => 'Areas',
  'description' => 'The top-level areas, for the Hikes menu panel.',
  'base_table' => 'taxonomy_term_field_data',
  'base_field' => 'tid',
  'display' => [
    'default' => [
      'id' => 'default',
      'display_title' => 'Default',
      'display_plugin' => 'default',
      'position' => 0,
      'display_options' => [
        'title' => 'Areas',
        'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
        'cache' => ['type' => 'tag', 'options' => []],
        'query' => ['type' => 'views_query', 'options' => []],
        'pager' => ['type' => 'none', 'options' => ['offset' => 0]],
        'style' => ['type' => 'html_list', 'options' => ['type' => 'ul', 'class' => '', 'wrapper_class' => '']],
        'row' => ['type' => 'fields', 'options' => []],
        'fields' => ['name' => $name_field],
        'filters' => [
          'vid' => $filter('vid', 'taxonomy_term_field_data', 'vid', 'bundle', ['entity_type' => 'taxonomy_term', 'entity_field' => 'vid', 'value' => ['areas' => 'areas']]),
          'status' => $filter('status', 'taxonomy_term_field_data', 'status', 'boolean', ['entity_type' => 'taxonomy_term', 'entity_field' => 'status', 'value' => '1']),
          'parent_target_id' => $filter('parent_target_id', 'taxonomy_term__parent', 'parent_target_id', 'numeric', ['entity_type' => 'taxonomy_term', 'entity_field' => 'parent', 'operator' => '=', 'value' => ['min' => '', 'max' => '', 'value' => '0']]),
          'langcode' => $filter('langcode', 'taxonomy_term_field_data', 'langcode', 'language', ['entity_type' => 'taxonomy_term', 'entity_field' => 'langcode', 'value' => ['***LANGUAGE_language_interface***' => '***LANGUAGE_language_interface***']]),
        ],
        'sorts' => [
          'weight' => ['id' => 'weight', 'table' => 'taxonomy_term_field_data', 'field' => 'weight', 'entity_type' => 'taxonomy_term', 'entity_field' => 'weight', 'plugin_id' => 'standard', 'order' => 'ASC'],
          'name' => ['id' => 'name', 'table' => 'taxonomy_term_field_data', 'field' => 'name', 'entity_type' => 'taxonomy_term', 'entity_field' => 'name', 'plugin_id' => 'standard', 'order' => 'ASC'],
        ],
        'rendering_language' => '***LANGUAGE_language_interface***',
      ],
    ],
    'block_1' => [
      'id' => 'block_1',
      'display_title' => 'Top-level areas',
      'display_plugin' => 'block',
      'position' => 1,
      'display_options' => ['block_description' => 'Top-level areas'],
    ],
  ],
])->save();

// 3b. The next two hikes, as a date and a title, with the view's own link to
// all the upcoming hikes.
$hikes_view = View::load('hikes');
$executable = $hikes_view->getExecutable();
$executable->newDisplay('block', 'Next hikes, in the menu', 'block_2');
$executable->setDisplay('block_2');
$block = $executable->getDisplay();
// This display's own settings, not the view's defaults: overridden first,
// or setOption() would change the defaults, and the hikes page with them.
foreach (['title', 'pager', 'style', 'row', 'fields', 'use_more', 'use_more_always', 'use_more_text'] as $option) {
  // FALSE: not the defaults. Without it, the call switches back and forth,
  // and row, which goes with style, would switch back.
  $block->setOverride($option, FALSE);
}
$block->setOption('title', 'Next hikes');
$block->setOption('block_description', 'Next hikes, in the menu');
$block->setOption('pager', ['type' => 'some', 'options' => ['offset' => 0, 'items_per_page' => 2]]);
$block->setOption('style', ['type' => 'html_list', 'options' => ['type' => 'ul', 'class' => '', 'wrapper_class' => '']]);
$block->setOption('row', ['type' => 'fields', 'options' => []]);
$block->setOption('use_more', TRUE);
$block->setOption('use_more_always', TRUE);
$block->setOption('use_more_text', 'All upcoming hikes');
$block->setOption('fields', []);
$executable->addHandler('block_2', 'field', 'node__field_date', 'field_date', [
  'label' => '',
  'type' => 'datetime_custom',
  'settings' => ['timezone_override' => '', 'date_format' => 'D j F · G:i'],
  'element_type' => 'span',
  'element_class' => 'hike-date',
  'element_default_classes' => FALSE,
]);
$executable->addHandler('block_2', 'field', 'node_field_data', 'title', [
  'label' => '',
  'type' => 'string',
  'settings' => ['link_to_entity' => TRUE],
  'element_default_classes' => FALSE,
]);
$executable->save();

// 4. A view mode for a photo in a menu panel: the photo, then its credit. A
// 16:9 photo, 640 pixels wide: Chapter 14's on core, Focal Point's nearest on
// Drupal CMS.
$image_style = ImageStyle::load('banner_small') ? 'banner_small' : '16_9_608x342_focal_point_webp';
if (!EntityViewMode::load('media.menu_panel')) {
  EntityViewMode::create(['id' => 'media.menu_panel', 'label' => 'Menu panel', 'targetEntityType' => 'media'])->save();
}
$media_display = EntityViewDisplay::load('media.image.menu_panel') ?? EntityViewDisplay::create([
  'targetEntityType' => 'media',
  'bundle' => 'image',
  'mode' => 'menu_panel',
  'status' => TRUE,
]);
$media_display->setComponent('field_media_image', [
  'type' => 'image',
  'label' => 'hidden',
  'settings' => ['image_style' => $image_style, 'image_link' => '', 'image_loading' => ['attribute' => 'lazy']],
  'weight' => 0,
])
  ->setComponent('field_photographer', ['type' => 'string', 'label' => 'hidden', 'weight' => 1])
  ->setComponent('field_license', ['type' => 'list_default', 'label' => 'hidden', 'weight' => 2])
  ->setComponent('field_source', ['type' => 'link', 'label' => 'hidden', 'weight' => 3])
  ->removeComponent('name')
  ->removeComponent('thumbnail')
  ->removeComponent('created')
  ->removeComponent('uid')
  ->removeComponent('field_themes')
  ->save();

// 5. The fields on Main navigation's links.
$lists_storage = FieldStorageConfig::loadByName('menu_link_content', 'field_panel_lists');
if (!$lists_storage) {
  $lists_storage = FieldStorageConfig::create([
    'field_name' => 'field_panel_lists',
    'entity_type' => 'menu_link_content',
    'type' => 'viewsreference',
    'cardinality' => -1,
    'settings' => ['target_type' => 'view'],
  ]);
  $lists_storage->save();
}
if (!FieldConfig::loadByName('menu_link_content', 'main', 'field_panel_lists')) {
  // The storage itself, not its name: Drupal CMS can have read the site's
  // field list earlier in this request, before the storage existed.
  FieldConfig::create([
    'field_storage' => $lists_storage,
    'bundle' => 'main',
    'label' => 'Panel lists',
    // The same lists and photo in both languages: the views and the media
    // item are translated themselves.
    'translatable' => FALSE,
    'description' => 'Lists from views, shown in the link\'s panel on wide screens.',
    'settings' => [
      'handler' => 'default:view',
      'handler_settings' => [],
      'plugin_types' => ['block' => 'block'],
      'preselect_views' => ['areas' => 'areas', 'hikes' => 'hikes'],
      'enabled_settings' => ['title' => 'title'],
    ],
  ])->save();
}
$photo_storage = FieldStorageConfig::loadByName('menu_link_content', 'field_panel_photo');
if (!$photo_storage) {
  $photo_storage = FieldStorageConfig::create([
    'field_name' => 'field_panel_photo',
    'entity_type' => 'menu_link_content',
    'type' => 'entity_reference',
    'cardinality' => 1,
    'settings' => ['target_type' => 'media'],
  ]);
  $photo_storage->save();
}
if (!FieldConfig::loadByName('menu_link_content', 'main', 'field_panel_photo')) {
  FieldConfig::create([
    'field_storage' => $photo_storage,
    'bundle' => 'main',
    'label' => 'Panel photo',
    'translatable' => FALSE,
    'description' => 'A photo for the link\'s panel on wide screens.',
    'settings' => [
      'handler' => 'default:media',
      'handler_settings' => ['target_bundles' => ['image' => 'image']],
    ],
  ])->save();
}
$form = EntityFormDisplay::load('menu_link_content.main.default') ?? EntityFormDisplay::create([
  'targetEntityType' => 'menu_link_content',
  'bundle' => 'main',
  'mode' => 'default',
  'status' => TRUE,
]);
$form->setComponent('field_panel_lists', ['type' => 'viewsreference_select', 'weight' => 20])
  ->setComponent('field_panel_photo', ['type' => 'media_library_widget', 'weight' => 21, 'settings' => ['media_types' => []]])
  ->save();
$view = EntityViewDisplay::load('menu_link_content.main.default') ?? EntityViewDisplay::create([
  'targetEntityType' => 'menu_link_content',
  'bundle' => 'main',
  'mode' => 'default',
  'status' => TRUE,
]);
$view->setComponent('field_panel_lists', [
  'type' => 'viewsreference_formatter',
  'label' => 'hidden',
  'settings' => ['plugin_types' => ['block']],
  'weight' => 0,
])
  ->setComponent('field_panel_photo', [
    'type' => 'entity_reference_entity_view',
    'label' => 'hidden',
    'settings' => ['view_mode' => 'menu_panel', 'link' => FALSE],
    'weight' => 1,
  ])
  ->save();

// 6. The Hikes panel.
$hikes = MenuLinkContent::load($hikes->id());
$hikes->set('field_panel_lists', [
  ['target_id' => 'areas', 'display_id' => 'block_1', 'data' => serialize(['title' => '1'])],
  ['target_id' => 'hikes', 'display_id' => 'block_2', 'data' => serialize(['title' => '1'])],
]);
$photo = current(\Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => 'A burn in the Pentland Hills in winter']));
$hikes->set('field_panel_photo', $photo->id());
$hikes->save();

// 7. Áreas, Próximas excursiones and Todas las próximas excursiones.
$language_manager = \Drupal::languageManager();
$language_manager->getLanguageConfigOverride('es', 'views.view.areas')
  ->set('display.default.display_options.title', 'Áreas')
  ->save();
$override = $language_manager->getLanguageConfigOverride('es', 'views.view.hikes');
$override->set('display.block_2.display_options.title', 'Próximas excursiones')
  ->set('display.block_2.display_options.use_more_text', 'Todas las próximas excursiones')
  ->save();
echo "Done.\n";

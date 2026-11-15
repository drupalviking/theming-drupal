<?php

/**
 * @file
 * Chapter 17's site building for the slider's other slides, done by hand in
 * the book.
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder,
 * after 01-hero.php.
 * 1. A Hero, later slides view mode for media: the Hero's display, loaded
 *    lazily.
 * 2. The album's Hero display: its photos in that view mode (the template
 *    prints the first).
 * 3. A Hero display in the albums view: the newest album, in the Hero view
 *    mode.
 * 4. A Hero slide block type: a heading, a text, a link and an optional
 *    photo, translatable.
 * 5. A Hero slide, Join the club, in English and Spanish.
 * 6. Both blocks in the Hero region, after the next hike, on the front page.
 */

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Entity\View;

// 1. Media: Hero, later slides.
if (!EntityViewMode::load('media.hero_later')) {
  EntityViewMode::create(['id' => 'media.hero_later', 'label' => 'Hero, later slides', 'targetEntityType' => 'media'])->save();
}
if (!EntityViewDisplay::load('media.image.hero_later')) {
  EntityViewDisplay::load('media.image.hero')->createCopy('hero_later')->save();
}
$display = EntityViewDisplay::load('media.image.hero_later');
$image = $display->getComponent('field_media_image');
$image['settings']['image_loading'] = ['attribute' => 'lazy'];
$display->setComponent('field_media_image', $image)->save();

// 2. The album's Hero display.
$display = EntityViewDisplay::load('node.gallery.hero') ?: EntityViewDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'gallery',
  'mode' => 'hero',
]);
$display->setStatus(TRUE);
foreach (array_keys($display->getComponents()) as $name) {
  $display->removeComponent($name);
}
$display->setComponent('field_photos', [
  'type' => 'entity_reference_entity_view',
  'label' => 'hidden',
  'settings' => ['view_mode' => 'hero_later', 'link' => FALSE],
  'weight' => 0,
]);
$display->save();

// 3. The albums view's Hero display: the newest album (the default sort is
// the hike's date, newest first).
$view = View::load('albums');
$displays = $view->get('display');
$displays['block_2'] = [
  'id' => 'block_2',
  'display_title' => 'Hero',
  'display_plugin' => 'block',
  'position' => count($displays),
  'display_options' => [
    'title' => 'Newest album',
    'block_description' => 'Newest album, in the hero',
    'pager' => ['type' => 'some', 'options' => ['offset' => 0, 'items_per_page' => 1]],
    'style' => ['type' => 'default', 'options' => []],
    'row' => ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'hero']],
    'defaults' => ['title' => FALSE, 'pager' => FALSE, 'style' => FALSE, 'row' => FALSE],
    'rendering_language' => '***LANGUAGE_language_interface***',
    'display_extenders' => [],
    'block_hide_empty' => TRUE,
  ],
  'cache_metadata' => $displays['block_1']['cache_metadata'] ?? [],
];
$view->set('display', $displays);
$view->save();

// 4. The Hero slide block type and its fields.
if (!BlockContentType::load('hero_slide')) {
  BlockContentType::create([
    'id' => 'hero_slide',
    'label' => 'Hero slide',
    'description' => 'A slide in the front page hero, such as the invitation to join.',
    'revision' => TRUE,
  ])->save();
}
$fields = [
  'field_heading' => ['string', 'Heading', TRUE, [], []],
  'field_text' => ['string', 'Text', FALSE, [], []],
  'field_link' => ['link', 'Link', TRUE, [], ['link_type' => 17, 'title' => 2]],
  'field_photo' => ['entity_reference', 'Photo', FALSE, ['target_type' => 'media'], [
    'handler' => 'default:media',
    'handler_settings' => ['target_bundles' => ['image' => 'image']],
  ]],
];
foreach ($fields as $name => [$type, $label, $required, $storage_settings, $settings]) {
  if (!FieldStorageConfig::loadByName('block_content', $name)) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'block_content',
      'type' => $type,
      'settings' => $storage_settings,
    ])->save();
  }
  if (!FieldConfig::loadByName('block_content', 'hero_slide', $name)) {
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => 'block_content',
      'bundle' => 'hero_slide',
      'label' => $label,
      'required' => $required,
      'translatable' => TRUE,
      'settings' => $settings,
    ])->save();
  }
}
$form = EntityFormDisplay::load('block_content.hero_slide.default') ?: EntityFormDisplay::create([
  'targetEntityType' => 'block_content',
  'bundle' => 'hero_slide',
  'mode' => 'default',
  'status' => TRUE,
]);
$form->setComponent('info', ['type' => 'string_textfield', 'weight' => -10])
  ->setComponent('field_heading', ['type' => 'string_textfield', 'weight' => 0])
  ->setComponent('field_text', ['type' => 'string_textfield', 'weight' => 1])
  ->setComponent('field_link', ['type' => 'link_default', 'weight' => 2])
  ->setComponent('field_photo', ['type' => 'media_library_widget', 'weight' => 3])
  ->save();
$display = EntityViewDisplay::load('block_content.hero_slide.default') ?: EntityViewDisplay::create([
  'targetEntityType' => 'block_content',
  'bundle' => 'hero_slide',
  'mode' => 'default',
  'status' => TRUE,
]);
$display->setComponent('field_photo', [
  'type' => 'entity_reference_entity_view',
  'label' => 'hidden',
  'settings' => ['view_mode' => 'hero_later', 'link' => FALSE],
  'weight' => 0,
])->save();
\Drupal::service('content_translation.manager')->setEnabled('block_content', 'hero_slide', TRUE);
$config = \Drupal\language\Entity\ContentLanguageSettings::loadByEntityTypeBundle('block_content', 'hero_slide');
$config->setLanguageAlterable(TRUE)->setDefaultLangcode('site_default')->save();

// 5. Join the club, in both languages.
$existing = \Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties(['info' => 'Hero: Join the club']);
$join = reset($existing) ?: BlockContent::create(['type' => 'hero_slide', 'info' => 'Hero: Join the club', 'langcode' => 'en']);
$join->set('field_heading', 'Join the club');
$join->set('field_text', 'Membership is £20 a year, £10 for students.');
$join->set('field_link', ['uri' => 'entity:node/2', 'title' => 'How to join']);
$join->save();
$es = $join->hasTranslation('es') ? $join->getTranslation('es') : $join->addTranslation('es', $join->toArray());
$es->set('info', 'Hero: Únete al club');
$es->set('field_heading', 'Únete al club');
$es->set('field_text', 'La cuota es de 20 £ al año, 10 £ para estudiantes.');
$es->set('field_link', ['uri' => 'entity:node/2', 'title' => 'Cómo unirse']);
$es->save();

// 6. The two blocks, after the next hike.
$blocks = [
  'lens_trail_newest_album_hero' => ['views_block:albums-block_2', 'Newest album', 1, ['views_label' => '', 'items_per_page' => 'none', 'provider' => 'views']],
  'lens_trail_join_hero' => ['block_content:' . $join->uuid(), 'Join the club', 2, ['provider' => 'block_content', 'info' => '', 'view_mode' => 'full']],
];
foreach ($blocks as $id => [$plugin, $label, $weight, $settings]) {
  $block = Block::load($id) ?: Block::create(['id' => $id, 'theme' => 'lens_trail', 'plugin' => $plugin]);
  $block->setRegion('hero');
  $block->setWeight($weight);
  $block->set('settings', ['id' => $plugin, 'label' => $label, 'label_display' => '0'] + $settings);
  $block->setVisibilityConfig('request_path', ['id' => 'request_path', 'pages' => '<front>', 'negate' => FALSE]);
  $block->save();
}

echo "Done.\n";

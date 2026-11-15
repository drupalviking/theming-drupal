<?php

/**
 * @file
 * Chapter 17's site building on the Drupal CMS site, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the Drupal CMS site's
 * folder, then rebuild the caches (so Canvas finds the new view blocks and
 * components), then run 05-cms-home.php.
 * 1. A responsive image style, Slide: Drupal CMS's 4:3 and 16:9 focal point
 *    styles, as wide as the window.
 * 2. Two view modes for media, Slide and Slide, later (Drupal CMS's own Hero
 *    is for a page's featured image), with that style: the first loads at
 *    once, the second lazily.
 * 3. A Hero view mode for content: the hike's (photo and date) and the
 *    album's (photos) displays.
 * 4. The Hero displays of the hikes and albums views, as on the core site.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\views\Entity\View;

// 1. The Slide responsive image style.
$slide = ResponsiveImageStyle::load('slide') ?: ResponsiveImageStyle::create(['id' => 'slide', 'label' => 'Slide']);
$slide->setBreakpointGroup('mercury');
$slide->removeImageStyleMappings();
$slide->addImageStyleMapping('mercury.narrow', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => ['sizes' => '100vw', 'sizes_image_styles' => ['4_3_500x375_focal_point_webp', '4_3_700x525_focal_point_webp', '4_3_960x720_focal_point_webp']],
]);
$slide->addImageStyleMapping('mercury.medium', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => ['sizes' => '100vw', 'sizes_image_styles' => ['16_9_1008x567_focal_point_webp', '16_9_1312x738_focal_point_webp', '16_9_1800x1080_focal_point_webp']],
]);
$slide->setFallbackImageStyle('16_9_1312x738_focal_point_webp');
$slide->save();

// 2. Media: Slide and Slide, later.
foreach (['slide' => ['Slide', 'eager'], 'slide_later' => ['Slide, later', 'lazy']] as $mode => [$label, $loading]) {
  if (!EntityViewMode::load("media.$mode")) {
    EntityViewMode::create(['id' => "media.$mode", 'label' => $label, 'targetEntityType' => 'media'])->save();
  }
  if (!EntityViewDisplay::load("media.image.$mode")) {
    EntityViewDisplay::load('media.image.banner')->createCopy($mode)->save();
  }
  $display = EntityViewDisplay::load("media.image.$mode");
  $image = $display->getComponent('field_media_image');
  $image['settings']['responsive_image_style'] = 'slide';
  $image['settings']['image_loading'] = ['attribute' => $loading];
  $display->setComponent('field_media_image', $image)->save();
}

// 3. Content: the Hero view mode, for hikes and albums.
if (!EntityViewMode::load('node.hero')) {
  EntityViewMode::create(['id' => 'node.hero', 'label' => 'Hero', 'targetEntityType' => 'node'])->save();
}
$components = [
  'hike' => [
    'field_photo' => ['type' => 'entity_reference_entity_view', 'label' => 'hidden', 'settings' => ['view_mode' => 'slide', 'link' => FALSE], 'weight' => 0],
    'field_date' => ['type' => 'datetime_default', 'label' => 'hidden', 'settings' => ['timezone_override' => '', 'format_type' => 'day_date_and_time'], 'weight' => 1],
  ],
  'gallery' => [
    'field_photos' => ['type' => 'entity_reference_entity_view', 'label' => 'hidden', 'settings' => ['view_mode' => 'slide_later', 'link' => FALSE], 'weight' => 0],
  ],
];
foreach ($components as $bundle => $fields) {
  $display = EntityViewDisplay::load("node.$bundle.hero") ?: EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => $bundle,
    'mode' => 'hero',
  ]);
  $display->setStatus(TRUE);
  foreach (array_keys($display->getComponents()) as $name) {
    $display->removeComponent($name);
  }
  foreach ($fields as $name => $component) {
    $display->setComponent($name, $component);
  }
  $display->save();
}

// 4. The views' Hero displays.
$view = View::load('hikes');
$displays = $view->get('display');
$hero = $displays['block_1'];
$hero['id'] = 'block_3';
$hero['display_title'] = 'Hero';
$hero['position'] = count($displays);
$hero['display_options']['title'] = 'Next hike';
$hero['display_options']['block_description'] = 'Next hike, in the hero';
$hero['display_options']['pager'] = ['type' => 'some', 'options' => ['offset' => 0, 'items_per_page' => 1]];
$hero['display_options']['style'] = ['type' => 'default', 'options' => []];
$hero['display_options']['row'] = ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'hero']];
$hero['display_options']['defaults'] = ['title' => FALSE, 'pager' => FALSE, 'style' => FALSE, 'row' => FALSE, 'use_more' => FALSE, 'use_more_always' => FALSE];
$hero['display_options']['use_more'] = FALSE;
$hero['display_options']['use_more_always'] = FALSE;
$displays['block_3'] = $hero;
$view->set('display', $displays);
$view->save();

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
];
$view->set('display', $displays);
$view->save();

echo "Done.\n";

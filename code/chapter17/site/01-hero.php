<?php

/**
 * @file
 * Chapter 17's site building for the front page hero, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder.
 * 1. A responsive image style, Hero: the Banner's image styles, as wide as
 *    the window (4:3 on phones, 16:9 from 40rem).
 * 2. A Hero view mode for media, showing the image with it, loaded at once.
 * 3. A Hero view mode for content, and the hike's Hero display: the photo in
 *    the media Hero view mode, and the date.
 * 4. A Hero display in the hikes view: the next hike, in the Hero view mode.
 * 5. Its block in the Hero region, on the front page only, without a title.
 * 6. The page title block hidden on the front page (the region template
 *    prints the title there instead).
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\views\Entity\View;

// 1. The Hero responsive image style.
$hero = ResponsiveImageStyle::load('hero') ?: ResponsiveImageStyle::create(['id' => 'hero', 'label' => 'Hero']);
$hero->setBreakpointGroup('lens_trail');
$hero->removeImageStyleMappings();
$hero->addImageStyleMapping('lens_trail.narrow', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => ['sizes' => '100vw', 'sizes_image_styles' => ['banner_phone', 'banner_phone_large']],
]);
$hero->addImageStyleMapping('lens_trail.medium', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => ['sizes' => '100vw', 'sizes_image_styles' => ['banner_small', 'banner', 'banner_large']],
]);
$hero->setFallbackImageStyle('banner');
$hero->save();

// 2. Media: the Hero view mode, as a copy of the Banner display.
if (!EntityViewMode::load('media.hero')) {
  EntityViewMode::create(['id' => 'media.hero', 'label' => 'Hero', 'targetEntityType' => 'media'])->save();
}
if (!EntityViewDisplay::load('media.image.hero')) {
  EntityViewDisplay::load('media.image.banner')->createCopy('hero')->save();
}
$display = EntityViewDisplay::load('media.image.hero');
$image = $display->getComponent('field_media_image');
$image['settings']['responsive_image_style'] = 'hero';
$image['settings']['image_loading'] = ['attribute' => 'eager'];
$display->setComponent('field_media_image', $image)->save();

// 3. Content: the Hero view mode, and the hike's display in it.
if (!EntityViewMode::load('node.hero')) {
  EntityViewMode::create(['id' => 'node.hero', 'label' => 'Hero', 'targetEntityType' => 'node'])->save();
}
$display = EntityViewDisplay::load('node.hike.hero') ?: EntityViewDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'hike',
  'mode' => 'hero',
]);
$display->setStatus(TRUE);
foreach (array_keys($display->getComponents()) as $name) {
  $display->removeComponent($name);
}
$display->setComponent('field_photo', [
  'type' => 'entity_reference_entity_view',
  'label' => 'hidden',
  'settings' => ['view_mode' => 'hero', 'link' => FALSE],
  'weight' => 0,
]);
$display->setComponent('field_date', EntityViewDisplay::load('node.hike.card')->getComponent('field_date'));
$display->save();

// 4. The hikes view's Hero display: a copy of the Next hikes block, with one
// hike in the Hero view mode, and no More link.
$view = View::load('hikes');
$displays = $view->get('display');
$hero = $displays['block_1'];
$hero['id'] = 'block_3';
$hero['display_title'] = 'Hero';
$hero['position'] = count($displays);
$hero['display_options']['title'] = 'Next hike';
$hero['display_options']['block_description'] = 'Next hike, in the hero';
$hero['display_options']['pager'] = ['type' => 'some', 'options' => ['offset' => 0, 'items_per_page' => 1]];
$hero['display_options']['row']['options']['view_mode'] = 'hero';
$hero['display_options']['use_more'] = FALSE;
$hero['display_options']['use_more_always'] = FALSE;
$displays['block_3'] = $hero;
$view->set('display', $displays);
$view->save();

// 5. The block, in the Hero region, on the front page only.
$block = Block::load('lens_trail_next_hike_hero') ?: Block::create([
  'id' => 'lens_trail_next_hike_hero',
  'theme' => 'lens_trail',
  'plugin' => 'views_block:hikes-block_3',
]);
$block->setRegion('hero');
$block->setWeight(0);
$block->set('settings', [
  'id' => 'views_block:hikes-block_3',
  'label' => 'Next hike',
  'label_display' => '0',
  'provider' => 'views',
  'views_label' => '',
  'items_per_page' => 'none',
]);
$block->setVisibilityConfig('request_path', ['id' => 'request_path', 'pages' => '<front>', 'negate' => FALSE]);
$block->save();

// 6. The page title, everywhere but the front page.
$title = Block::load('lens_trail_page_title');
$title->setVisibilityConfig('request_path', ['id' => 'request_path', 'pages' => '<front>', 'negate' => TRUE]);
$title->save();

echo "Done.\n";

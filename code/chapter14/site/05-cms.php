<?php

/**
 * @file
 * Chapter 14's site building on the Drupal CMS site, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the Drupal CMS site's
 * folder. It uses Drupal CMS's own image styles, which already crop around
 * the focal point.
 * 1. A responsive image style, Banner: 4:3 below 40rem, 16:9 from there, with
 *    the theme's breakpoints. The Banner display uses it, and loads at once.
 * 2. A responsive image style, Album: squares, with the sizes of the grid.
 *    The Album display uses it.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

$banner = ResponsiveImageStyle::load('banner') ?: ResponsiveImageStyle::create(['id' => 'banner', 'label' => 'Banner']);
$banner->setBreakpointGroup('mercury');
$banner->removeImageStyleMappings();
$banner->addImageStyleMapping('mercury.narrow', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    'sizes' => 'calc(100vw - 2rem)',
    'sizes_image_styles' => ['4_3_500x375_focal_point_webp', '4_3_700x525_focal_point_webp', '4_3_960x720_focal_point_webp'],
  ],
]);
$banner->addImageStyleMapping('mercury.medium', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    // Mercury's page is Tailwind's container: a fixed width from each of
    // Tailwind's breakpoints on, less 1rem of padding on each side.
    'sizes' => '(min-width: 96rem) 1504px, (min-width: 80rem) 1248px, (min-width: 64rem) 992px, (min-width: 48rem) 736px, 608px',
    'sizes_image_styles' => ['16_9_704x396_focal_point_webp', '16_9_1008x567_focal_point_webp', '16_9_1312x738_focal_point_webp', '16_9_1800x1080_focal_point_webp'],
  ],
]);
$banner->setFallbackImageStyle('16_9_1008x567_focal_point_webp');
$banner->save();

$album = ResponsiveImageStyle::load('album') ?: ResponsiveImageStyle::create(['id' => 'album', 'label' => 'Album']);
$album->setBreakpointGroup('responsive_image');
$album->removeImageStyleMappings();
$album->addImageStyleMapping('responsive_image.viewport_sizing', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    // Two columns on phones, three from 40rem, four from 64rem, in the
    // container's fixed widths.
    'sizes' => '(min-width: 96rem) 364px, (min-width: 80rem) 300px, (min-width: 64rem) 236px, (min-width: 48rem) 235px, (min-width: 40rem) 192px, calc(50vw - 1.5rem)',
    'sizes_image_styles' => ['1_1_300x300_focal_point_webp', '1_1_600x600_focal_point_webp'],
  ],
]);
$album->setFallbackImageStyle('1_1_300x300_focal_point_webp');
$album->save();

foreach (['banner' => ['banner', 'eager'], 'album' => ['album', 'lazy']] as $mode => [$style, $loading]) {
  $display = EntityViewDisplay::load("media.image.$mode");
  $image = $display->getComponent('field_media_image');
  $image['type'] = 'responsive_image';
  $image['settings'] = ['responsive_image_style' => $style, 'image_link' => '', 'image_loading' => ['attribute' => $loading]];
  $display->setComponent('field_media_image', $image)->save();
}
echo "Done.\n";

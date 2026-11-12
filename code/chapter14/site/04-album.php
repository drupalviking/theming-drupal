<?php

/**
 * @file
 * Chapter 14's site building for the album, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder.
 * 1. Two square image styles, Album square and Album square large, cropped
 *    around the focal point.
 * 2. A responsive image style, Album, with them and the sizes of the grid.
 * 3. The Album display of Image media shows the photo with it, lazily.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

foreach (['album_square' => ['Album square', 320], 'album_square_large' => ['Album square large', 640]] as $name => [$label, $size]) {
  $style = ImageStyle::load($name) ?: ImageStyle::create(['name' => $name, 'label' => $label]);
  foreach ($style->getEffects() as $effect) {
    $style->deleteImageEffect($effect);
  }
  $style->addImageEffect(['id' => 'focal_point_scale_and_crop', 'weight' => 0, 'data' => ['width' => $size, 'height' => $size, 'crop_type' => 'focal_point']]);
  $style->addImageEffect(['id' => 'image_convert_avif', 'weight' => 1, 'data' => ['extension' => 'webp']]);
  $style->save();
}

$album = ResponsiveImageStyle::load('album') ?: ResponsiveImageStyle::create(['id' => 'album', 'label' => 'Album']);
$album->setBreakpointGroup('responsive_image');
$album->removeImageStyleMappings();
$album->addImageStyleMapping('responsive_image.viewport_sizing', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    // Two columns on phones, three from 40rem, four from 64rem (where the
    // sidebar takes a third of the page), and no wider than 185px from 80rem.
    'sizes' => '(min-width: 80rem) 185px, (min-width: 64rem) calc(15vw - 0.75rem), (min-width: 40rem) calc(33vw - 1.6rem), calc(50vw - 2rem)',
    'sizes_image_styles' => ['album_square', 'album_square_large'],
  ],
]);
$album->setFallbackImageStyle('album_square');
$album->save();

$display = EntityViewDisplay::load('media.image.album');
$image = $display->getComponent('field_media_image');
$image['type'] = 'responsive_image';
$image['settings'] = ['responsive_image_style' => 'album', 'image_link' => '', 'image_loading' => ['attribute' => 'lazy']];
$display->setComponent('field_media_image', $image)->save();
echo "Done.\n";

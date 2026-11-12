<?php

/**
 * @file
 * Chapter 14's site building for art direction, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder,
 * after 02-focal-point.sh.
 * 1. Image media's forms get the Focal Point widget.
 * 2. The three Banner image styles crop around the focal point.
 * 3. Two new 4:3 image styles for phones, Banner phone and Banner phone large.
 * 4. The Banner responsive image style uses the theme's breakpoints: 4:3
 *    below 40rem, 16:9 from there.
 * 5. A focal point on the Glencorse Reservoir photo, a portrait: on the far
 *    shore, a third of the way down, so the wide crop keeps the hills and the
 *    tops of both trees (in the book, a click on the photo in the media form).
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\crop\Entity\Crop;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

foreach (['default', 'media_library'] as $mode) {
  $form = EntityFormDisplay::load("media.image.$mode");
  $widget = $form->getComponent('field_media_image');
  $widget['type'] = 'image_focal_point';
  $widget['settings'] = [
    'progress_indicator' => 'throbber',
    'preview_image_style' => 'max_650x650',
    'preview_link' => TRUE,
    'offsets' => '50,50',
  ];
  $form->setComponent('field_media_image', $widget)->save();
}

$crop = fn(int $width, int $height) => [
  'id' => 'focal_point_scale_and_crop',
  'weight' => 0,
  'data' => ['width' => $width, 'height' => $height, 'crop_type' => 'focal_point'],
];
$avif = ['id' => 'image_convert_avif', 'weight' => 1, 'data' => ['extension' => 'webp']];

$styles = [
  'banner_small' => ['Banner small', 640, 360],
  'banner' => ['Banner', 1280, 720],
  'banner_large' => ['Banner large', 1920, 1080],
  'banner_phone' => ['Banner phone', 480, 360],
  'banner_phone_large' => ['Banner phone large', 960, 720],
];
foreach ($styles as $name => [$label, $width, $height]) {
  $style = ImageStyle::load($name) ?: ImageStyle::create(['name' => $name, 'label' => $label]);
  foreach ($style->getEffects() as $effect) {
    $style->deleteImageEffect($effect);
  }
  $style->addImageEffect($crop($width, $height));
  $style->addImageEffect($avif);
  $style->save();
  $style->flush();
}

$banner = ResponsiveImageStyle::load('banner');
$banner->setBreakpointGroup('lens_trail');
$banner->removeImageStyleMappings();
$banner->addImageStyleMapping('lens_trail.narrow', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    'sizes' => 'calc(100vw - 3rem)',
    'sizes_image_styles' => ['banner_phone', 'banner_phone_large'],
  ],
]);
$banner->addImageStyleMapping('lens_trail.medium', '1x', [
  'image_mapping_type' => 'sizes',
  'image_mapping' => [
    'sizes' => '(min-width: 80rem) 790px, (min-width: 64rem) 62vw, calc(100vw - 3rem)',
    'sizes_image_styles' => ['banner_small', 'banner', 'banner_large'],
  ],
]);
$banner->setFallbackImageStyle('banner');
$banner->save();
// The photo of the hike Glencorse Reservoir winter loop. (The site has three
// files with that name, so it's found through the hike.)
$hikes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => 'Glencorse Reservoir winter loop']);
$file = reset($hikes)->get('field_photo')->entity->get('field_media_image')->entity;
$crop = Crop::findCrop($file->getFileUri(), 'focal_point') ?: Crop::create([
  'type' => 'focal_point',
  'entity_id' => $file->id(),
  'entity_type' => 'file',
  'uri' => $file->getFileUri(),
]);
// Focal Point keeps the point in pixels of the original photo (1728 x 3072).
$crop->setPosition(864, 1100)->save();
foreach (array_keys($styles) as $name) {
  ImageStyle::load($name)->flush($file->getFileUri());
}
echo "Done.\n";

<?php

/**
 * @file
 * Chapter 14's site building for the first step, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder.
 * The Banner display of Image media loads its photo at once (Eager), not
 * when it scrolls into view (Lazy).
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

$banner = EntityViewDisplay::load('media.image.banner');
$image = $banner->getComponent('field_media_image');
$image['settings']['image_loading']['attribute'] = 'eager';
$banner->setComponent('field_media_image', $image)->save();
echo "Done.\n";

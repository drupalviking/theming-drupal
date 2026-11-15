<?php

/**
 * @file
 * Chapter 18, step 03, on the core test site, as the chapter does it by hand:
 *
 * - Lucía's comment on Pentlands, 21 November, waiting since Book 1's
 *   Chapter 15 in Content > Comments > Unapproved comments, is published.
 * - The album's Hike and Area show their labels (the fact template prints
 *   them as dt).
 * - The hike's Teaser display shows what the Card display shows (photo, date,
 *   difficulty), since its template is the card's.
 *
 * Run from the site's folder: ddev drush php:script book2-site/03-other-pages.php
 */

use Drupal\comment\Entity\Comment;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

foreach (\Drupal::entityTypeManager()->getStorage('comment')->loadByProperties(['status' => 0]) as $comment) {
  $comment->setPublished()->save();
}

$display = EntityViewDisplay::load('node.gallery.default');
foreach (['field_hike', 'field_area'] as $name) {
  $component = $display->getComponent($name);
  $component['label'] = 'above';
  $display->setComponent($name, $component);
}
$display->save();

$card = EntityViewDisplay::load('node.hike.card');
$teaser = EntityViewDisplay::load('node.hike.teaser');
foreach (array_keys($teaser->getComponents()) as $name) {
  $teaser->removeComponent($name);
}
foreach ($card->getComponents() as $name => $component) {
  $teaser->setComponent($name, $component);
}
$teaser->save();

echo "Done.\n";

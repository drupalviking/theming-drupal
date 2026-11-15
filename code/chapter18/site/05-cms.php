<?php

/**
 * @file
 * Chapter 18, step 05, on the Drupal CMS test site: the displays, as the
 * chapter sets them up by hand, the same as on the core site.
 *
 * - Hike, Default display: Date and Hike leader labels hidden, the four
 *   facts' labels above, Elevation gain off the page.
 * - Album, Default display: Hike and Area labels above.
 * - News, Default display: the photo in the Banner view mode, as an area's,
 *   and Content's label hidden.
 * - Area, Default display: the photo in the Banner view mode, with its credit
 *   on it, as on the core site (Drupal CMS's 16:9 wide listed the credit's
 *   fields under it).
 * - Hike, Card display: only the photo. node--hike--card.html.twig reads
 *   the card's values from the node; rendering the photo passes its cache
 *   tags on.
 * - Lucía's comment, which is plain text, without the <p> tags it was saved
 *   with (Book 1 fixed it the same way).
 *
 * Run from the site's folder: ddev drush php:script book2-site/05-cms.php
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

$set_labels = function (EntityViewDisplay $display, array $names, string $label): void {
  foreach ($names as $name) {
    $component = $display->getComponent($name);
    $component['label'] = $label;
    $display->setComponent($name, $component);
  }
};

$display = EntityViewDisplay::load('node.hike.default');
$set_labels($display, ['field_date', 'field_hike_leader'], 'hidden');
$set_labels($display, ['field_meeting_place', 'field_distance', 'field_difficulty', 'field_area'], 'above');
$display->removeComponent('field_elevation_gain');
$display->save();

$display = EntityViewDisplay::load('node.gallery.default');
$set_labels($display, ['field_hike', 'field_area'], 'above');
$display->save();

$display = EntityViewDisplay::load('node.news.default');
$component = $display->getComponent('field_photo');
$component['settings']['view_mode'] = 'banner';
$display->setComponent('field_photo', $component);
$set_labels($display, ['field_content'], 'hidden');
$display->save();

$display = EntityViewDisplay::load('taxonomy_term.areas.default');
$component = $display->getComponent('field_photo');
$component['settings']['view_mode'] = 'banner';
$display->setComponent('field_photo', $component)->save();

$card = EntityViewDisplay::load('node.hike.card');
foreach (array_keys($card->getComponents()) as $name) {
  $card->removeComponent($name);
}
$card->setComponent('field_photo', [
  'type' => 'entity_reference_entity_view',
  'label' => 'hidden',
  'settings' => ['view_mode' => 'default'],
]);
$card->save();

foreach (\Drupal::entityTypeManager()->getStorage('comment')->loadByProperties(['comment_body.format' => 'plain_text']) as $comment) {
  $comment->set('comment_body', [
    'value' => strip_tags($comment->get('comment_body')->value),
    'format' => 'plain_text',
  ]);
  $comment->save();
}

echo "Done.\n";

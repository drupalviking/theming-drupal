<?php

/**
 * @file
 * Chapter 13's site building on the core test site, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder.
 * 1. Card display: the photo, as the media's thumbnail in max_650x650.
 * 2. A new view mode, Card in a list (card_list), for hikes, with the same
 *    fields as Card.
 * 3. The hikes view's page (/hikes) shows Card in a list.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\views\Entity\View;

$photo = [
  'type' => 'media_thumbnail',
  'label' => 'hidden',
  'settings' => ['image_link' => '', 'image_style' => 'max_650x650', 'image_loading' => ['attribute' => 'lazy']],
  'third_party_settings' => [],
  'weight' => 0,
  'region' => 'content',
];
$card = EntityViewDisplay::load('node.hike.card');
$card->setComponent('field_photo', $photo)->save();

if (!EntityViewMode::load('node.card_list')) {
  EntityViewMode::create(['id' => 'node.card_list', 'label' => 'Card in a list', 'targetEntityType' => 'node'])->save();
}
$list = EntityViewDisplay::load('node.hike.card_list') ?: EntityViewDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'hike',
  'mode' => 'card_list',
  'status' => TRUE,
]);
foreach (array_keys($list->getComponents()) as $name) {
  $list->removeComponent($name);
}
foreach ($card->getComponents() as $name => $options) {
  $list->setComponent($name, $options);
}
$list->setStatus(TRUE)->save();

$view = View::load('hikes');
$display = &$view->getDisplay('page_1');
$display['display_options']['defaults']['row'] = FALSE;
$display['display_options']['row'] = ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'card_list']];
$view->save();
echo "Done.\n";

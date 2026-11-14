<?php

/**
 * @file
 * Chapter 13's Canvas work on the Drupal CMS test site, done by hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder.
 * Replaces Book 1's Next hikes view block on the Home page with a Next hikes
 * section: a heading, and three Hike cards for the club's next three hikes,
 * each linking to its hike page, with the hike's photo when it has one.
 */

use Drupal\Component\Uuid\Php as Uuid;

$storage = \Drupal::entityTypeManager()->getStorage('canvas_page');
$page = $storage->load(1);
$versions = [];
foreach (['sdc.mercury.section', 'sdc.mercury.heading', 'sdc.mercury.hike-card'] as $id) {
  $versions[$id] = \Drupal::entityTypeManager()->getStorage('component')->load($id)->getActiveVersion();
}

// Remove an earlier run's section, so the script can be run again.
// Book 1's view block, and a Next hikes section from an earlier run, go; the
// new section takes the view block's place.
$replaced = fn (array $item) => ($item['label'] ?? '') === 'Next hikes'
  || $item['component_id'] === 'block.views_block.hikes-block_1';
$position = NULL;
$items = [];
foreach ($page->get('components')->getValue() as $item) {
  if (empty($item['parent_uuid']) && $replaced($item)) {
    $position ??= count($items);
    continue;
  }
  $items[] = $item;
}
$tree = array_column($items, NULL, 'uuid');
foreach ($items as $item) {
  if (isset($item['parent_uuid']) && !isset($tree[$item['parent_uuid']])) {
    unset($tree[$item['uuid']]);
  }
}
$items = array_values($tree);
$position ??= count($items);
$new = [];
$uuid = new Uuid();
$section = $uuid->generate();
$new[] = [
  'uuid' => $section,
  'component_id' => 'sdc.mercury.section',
  'component_version' => $versions['sdc.mercury.section'],
  'label' => 'Next hikes',
  'inputs' => [
    'width' => '90%',
    'columns' => '33-33-33',
    'mobile_columns' => '1',
    'margin_block_start' => '48',
    'margin_block_end' => '48',
    'padding_block_start' => '0',
    'padding_block_end' => '0',
    'section_header' => TRUE,
    'section_footer' => FALSE,
  ],
];
$new[] = [
  'uuid' => $uuid->generate(),
  'component_id' => 'sdc.mercury.heading',
  'component_version' => $versions['sdc.mercury.heading'],
  'parent_uuid' => $section,
  'slot' => 'header_slot',
  'inputs' => [
    'heading_text' => 'Next hikes',
    'level' => 2,
    'text_size' => 'default',
    'text_color' => 'default',
    'align' => 'left',
  ],
];
$hikes = [
  ['Turnhouse Hill and Carnethy Hill', '2026-11-21T09:30:00', 'moderate', 'About 2 hours 20 min on foot'],
  ['North Berwick Law and the harbour', '2026-12-05T09:30:00', 'easy', 'About 1 hour 20 min on foot'],
  ['Glencorse Reservoir winter loop', '2026-12-19T09:30:00', 'easy', 'About 1 hour 50 min on foot'],
];
$nodes = \Drupal::entityTypeManager()->getStorage('node');
foreach ($hikes as [$title, $date, $difficulty, $walking_time]) {
  $hike = current($nodes->loadByProperties(['type' => 'hike', 'title' => $title]));
  $media = $hike->get('field_photo')->target_id;
  $inputs = [
    'title' => $title,
    // Stored as the Canvas editor stores a link: Canvas Translate (Book 1's
    // Chapter 18) can't read a bare string here.
    'url' => ['uri' => 'entity:node/' . $hike->id()],
    'heading_level' => 3,
    'layout' => 'top',
    'date' => $date,
    'difficulty' => $difficulty,
    'walking_time' => $walking_time,
  ];
  if ($media) {
    $inputs['photo'] = ['target_id' => $media];
  }
  $new[] = [
    'uuid' => $uuid->generate(),
    'component_id' => 'sdc.mercury.hike-card',
    'component_version' => $versions['sdc.mercury.hike-card'],
    'parent_uuid' => $section,
    'slot' => 'main_slot',
    'inputs' => $inputs,
  ];
}
array_splice($items, $position, 0, $new);
$page->set('components', $items);
$violations = $page->validate();
foreach ($violations as $violation) {
  echo $violation->getPropertyPath(), ': ', $violation->getMessage(), "\n";
}
if (!count($violations)) {
  $page->save();
  echo "Saved.\n";
}

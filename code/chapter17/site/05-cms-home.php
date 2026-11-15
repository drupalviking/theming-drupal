<?php

/**
 * @file
 * Chapter 17's Canvas work on the Drupal CMS site's Home page, done by hand in
 * the book.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder, after
 * 05-cms.php and a cache rebuild.
 * Replaces Mercury's CTA at the top of Home (Book 1's welcome, with its h1
 * and How to join button) with a Hero slider: the Next hike and Newest album
 * view blocks, and a Hero for joining, in the Color style. The slider takes
 * the page's h1, for screen readers. In English and Spanish: in Canvas, each
 * translation of a page has its own copy of the components.
 */

use Drupal\Component\Uuid\Php as Uuid;

$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->load(1);
$components = \Drupal::entityTypeManager()->getStorage('component');
$version = fn (string $id) => $components->load($id)->getActiveVersion();

$text = [
  'en' => [
    'label' => 'Club highlights',
    'heading' => 'Welcome to Lens & Trail',
    'join' => 'Join the club',
    'join_text' => 'Membership is £20 a year, £10 for students.',
    'join_link' => 'How to join',
  ],
  'es' => [
    'label' => 'Lo último del club',
    'heading' => 'Te damos la bienvenida a Lens & Trail',
    'join' => 'Únete al club',
    'join_text' => 'La cuota es de 20 £ al año, 10 £ para estudiantes.',
    'join_link' => 'Cómo unirse',
  ],
];

// In Canvas, the translations of a page share its tree of components: which
// ones, where, in what order. Only their inputs are translated. So the tree
// is built once, in English, and each language gets its own inputs, matched
// by UUID.
$spanish = [];
foreach ($page->getTranslation('es')->get('components')->getValue() as $item) {
  $spanish[$item['uuid']] = $item['inputs'];
}

$uuid = new Uuid();
$ids = ['slider' => $uuid->generate(), 'hike' => $uuid->generate(), 'album' => $uuid->generate(), 'join' => $uuid->generate()];
$inputs = fn (array $t) => [
  $ids['slider'] => ['label' => $t['label'], 'heading' => $t['heading'], 'interval' => 7],
  $ids['hike'] => ['label' => 'Next hike', 'label_display' => '0', 'views_label' => '', 'items_per_page' => NULL],
  $ids['album'] => ['label' => 'Newest album', 'label_display' => '0', 'views_label' => '', 'items_per_page' => NULL],
  $ids['join'] => [
    'style' => 'color',
    'heading' => $t['join'],
    'heading_level' => 2,
    'text' => $t['join_text'],
    // Stored as the Canvas editor stores a link (see Chapter 13's script).
    'url' => ['uri' => 'entity:node/3'],
    'link_text' => $t['join_link'],
  ],
];

// Take out the CTA and what's in it, and an earlier run's slider and what's
// in it.
$items = $page->getTranslation('en')->get('components')->getValue();
$remove = [];
foreach ($items as $item) {
  if (empty($item['parent_uuid']) && in_array($item['component_id'], ['sdc.mercury.cta', 'sdc.mercury.hero-slider'], TRUE)) {
    $remove[] = $item['uuid'];
  }
}
$items = array_values(array_filter($items, fn ($item) => !in_array($item['uuid'], $remove, TRUE) && !in_array($item['parent_uuid'] ?? '', $remove, TRUE)));

// The slider first, with its three slides.
$new = [
  ['uuid' => $ids['slider'], 'component_id' => 'sdc.mercury.hero-slider'],
  ['uuid' => $ids['hike'], 'component_id' => 'block.views_block.hikes-block_3', 'parent_uuid' => $ids['slider'], 'slot' => 'slides'],
  ['uuid' => $ids['album'], 'component_id' => 'block.views_block.albums-block_2', 'parent_uuid' => $ids['slider'], 'slot' => 'slides'],
  ['uuid' => $ids['join'], 'component_id' => 'sdc.mercury.hero', 'parent_uuid' => $ids['slider'], 'slot' => 'slides'],
];
foreach ($new as &$item) {
  $item['component_version'] = $version($item['component_id']);
  $item['inputs'] = [];
}
unset($item);
array_splice($items, 0, 0, $new);

// English first, with the new tree. Saving it gives the Spanish translation
// the same tree, with English inputs in the new components.
$english = $inputs($text['en']);
foreach ($items as &$item) {
  $item['inputs'] = $english[$item['uuid']] ?? $item['inputs'];
}
unset($item);
$page->getTranslation('en')->set('components', $items);
$page->save();

// Then Spanish: the same tree, with the Spanish inputs it had before, and
// Spanish text in the new components.
$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->loadUnchanged(1);
$translation = $page->getTranslation('es');
$spanish += $inputs($text['es']);
$tree = $translation->get('components')->getValue();
foreach ($tree as &$item) {
  $item['inputs'] = $spanish[$item['uuid']] ?? $item['inputs'];
}
unset($item);
$translation->set('components', $tree);

$violations = $page->validate();
foreach ($violations as $violation) {
  echo $violation->getPropertyPath(), ': ', $violation->getMessage(), "\n";
}
if (!count($violations)) {
  $page->save();
  echo "Saved.\n";
}

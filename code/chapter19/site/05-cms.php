<?php

/**
 * @file
 * Chapter 19, step 05, on the Drupal CMS site only, after 01 to 04.
 *
 * - News lists its items in the Card display, as Drupal CMS lists content
 *   (and as its term pages do): the page and Latest news both. So does the
 *   Hike archive.
 * - Photos with this theme goes in the Mercury theme's page layout in Canvas
 *   (Drupal CMS has no Block layout for its front-end theme), in the Content
 *   slot after Albums from this hike. Like that block, it's on every page,
 *   and only shows something on a photo theme's page.
 * - Home (a Canvas page) gets a Latest news section after Next hikes: a
 *   Section with a Heading and the Latest news block, its two cards side by
 *   side (the Section's "Views grid"). In English and Spanish.
 *
 * Run from the site's folder: ddev drush php:script book2-site/05-cms.php
 */

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\views\Entity\View;

// News in the Card display.
$view = View::load('news');
$default = &$view->getDisplay('default');
$default['display_options']['row']['options']['view_mode'] = 'card';
$view->save();

// The Hike archive too: Drupal CMS's hikes have no Teaser template, but a
// Card one (Chapter 18).
$view = View::load('hikes');
$archive = &$view->getDisplay('page_3');
$archive['display_options']['defaults']['row'] = FALSE;
$archive['display_options']['row'] = ['type' => 'entity:node', 'options' => ['relationship' => 'none', 'view_mode' => 'card']];
$view->save();

// Canvas makes a component for each block when caches are rebuilt: the one
// for Photos with this theme exists since 04-albums.php. (Rebuilding them
// here, halfway, would leave this request unable to save the Canvas page.)
$components = \Drupal::entityTypeManager()->getStorage('component');
$version = fn (string $id) => $components->load($id)->getActiveVersion();
$uuid = new Uuid();

// The page layout: Photos with this theme after Albums from this hike.
$variant = \Drupal::entityTypeManager()->getStorage('page_variant')->load('theme_mercury');
$tree = $variant->get('component_tree');
foreach ($tree as $id => $item) {
  if ($item['component_id'] === 'block.views_block.photo_themes-block_1') {
    unset($tree[$id]);
  }
}
$position = array_search('block.views_block.albums-block_1', array_column($tree, 'component_id'), TRUE);
$albums = array_values($tree)[$position];
$id = $uuid->generate();
$new = [$id => [
  'parent_uuid' => $albums['parent_uuid'],
  'slot' => 'content',
  'uuid' => $id,
  'component_id' => 'block.views_block.photo_themes-block_1',
  'component_version' => $version('block.views_block.photo_themes-block_1'),
  'inputs' => ['label' => 'Photos with this theme', 'label_display' => '0', 'views_label' => '', 'items_per_page' => NULL],
]];
$tree = array_slice($tree, 0, $position + 1, TRUE) + $new + array_slice($tree, $position + 1, NULL, TRUE);
$variant->set('component_tree', $tree)->save();

// Home: a Latest news section, before Newest photos. In Canvas, a page's
// translations share its tree of components; only the inputs are translated
// (see Chapter 17's script).
$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->load(1);
$ids = ['section' => $uuid->generate(), 'heading' => $uuid->generate(), 'news' => $uuid->generate()];
$inputs = fn (string $heading) => [
  $ids['section'] => [
    'width' => '90%',
    'columns' => '100',
    'views_columns' => '50-50',
    'mobile_columns' => '1',
    'margin_block_start' => '48',
    'margin_block_end' => '48',
    'padding_block_start' => '0',
    'padding_block_end' => '0',
    'section_header' => TRUE,
    'section_footer' => FALSE,
  ],
  $ids['heading'] => ['heading_text' => $heading, 'level' => 2, 'text_size' => 'default', 'text_color' => 'default', 'align' => 'left'],
  $ids['news'] => ['label' => 'Latest news', 'label_display' => '0', 'views_label' => '', 'items_per_page' => NULL],
];

$spanish = [];
foreach ($page->getTranslation('es')->get('components')->getValue() as $item) {
  $spanish[$item['uuid']] = $item['inputs'];
}

// Take out an earlier run's section and what's in it.
$items = $page->getTranslation('en')->get('components')->getValue();
$old = [];
foreach ($items as $item) {
  if ($item['component_id'] === 'block.views_block.news-block_1') {
    $old[] = $item['parent_uuid'];
  }
}
$items = array_values(array_filter($items, fn ($item) => !in_array($item['uuid'], $old, TRUE) && !in_array($item['parent_uuid'] ?? '', $old, TRUE)));

$new = [
  ['uuid' => $ids['section'], 'component_id' => 'sdc.mercury.section'],
  ['uuid' => $ids['heading'], 'component_id' => 'sdc.mercury.heading', 'parent_uuid' => $ids['section'], 'slot' => 'header_slot'],
  ['uuid' => $ids['news'], 'component_id' => 'block.views_block.news-block_1', 'parent_uuid' => $ids['section'], 'slot' => 'main_slot'],
];
foreach ($new as &$item) {
  $item['component_version'] = $version($item['component_id']);
  $item['inputs'] = [];
}
unset($item);
$photos = array_search('block.views_block.photos-block_1', array_column($items, 'component_id'), TRUE);
array_splice($items, $photos === FALSE ? count($items) : $photos, 0, $new);

$english = $inputs('Latest news');
foreach ($items as &$item) {
  $item['inputs'] = $english[$item['uuid']] ?? $item['inputs'];
}
unset($item);
$page->getTranslation('en')->set('components', $items);
$page->save();

$page = \Drupal::entityTypeManager()->getStorage('canvas_page')->loadUnchanged(1);
$translation = $page->getTranslation('es');
$spanish += $inputs('Últimas noticias');
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
}

drupal_flush_all_caches();
echo "Done.\n";

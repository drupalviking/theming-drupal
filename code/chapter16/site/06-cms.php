<?php

/**
 * @file
 * Chapter 16 on the Drupal CMS site: the search moves next to the menu
 * button, done by hand in the book (Canvas > the page template).
 *
 * Run with: ddev drush php:script <this file>, from the Drupal CMS site's
 * folder, after step 06-cms is on the site (its Navbar has the new slot,
 * "Next to the menu button") and 03-mega.php has run there.
 *
 * The Navbar in the page template uses the version of the component it was
 * placed with, which didn't have the slot; it moves to the active version
 * first. Running it again changes nothing.
 */

$components = \Drupal::entityTypeManager()->getStorage('component');
$variant = \Drupal::entityTypeManager()->getStorage('page_variant')->load('theme_mercury');
$tree = $variant->get('component_tree');

foreach ($tree as $uuid => $item) {
  if ($item['component_id'] === 'sdc.mercury.navbar') {
    $tree[$uuid]['component_version'] = $components->load('sdc.mercury.navbar')->getActiveVersion();
    $navbar = $uuid;
  }
}
foreach ($tree as $uuid => $item) {
  if (($item['parent_uuid'] ?? NULL) === $navbar && $item['component_id'] === 'block.views_exposed_filter_block.search-search') {
    $tree[$uuid]['slot'] = 'tools';
  }
}

$variant->set('component_tree', $tree)->save();
echo "Done.\n";

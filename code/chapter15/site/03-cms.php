<?php

/**
 * @file
 * Chapter 15 on the Drupal CMS site: the header and the footer in Canvas's
 * page variant, done by hand in the book (Canvas > the page template).
 *
 * Run with: ddev drush php:script <this file>, from the Drupal CMS site's
 * folder, after step 03-cms is on the site (it has the Color scheme toggle
 * component).
 *
 * The first book filled the page template already: the logo, the main menu,
 * the language switcher and a search box in the Navbar; the meeting times,
 * Join the club and the footer menu in the Footer. All of that stays. This
 * adds what the design adds:
 * 1. The Navbar: the site's name next to the logo, and the color scheme
 *    toggle in the CTAs slot.
 * 2. The Footer: the logo and name above the meeting times; the User account
 *    menu (Log in) next to the footer menu; the license line (a Text) and
 *    Powered by Drupal; the first book's footer texts at the design's small
 *    size, and Join the club as a small heading.
 * Running it again changes nothing: what it adds is only added once.
 */

use Drupal\Component\Uuid\Php as Uuid;

$components = \Drupal::entityTypeManager()->getStorage('component');
$components->load('block.system_menu_block.account')->enable()->save();

$variant = \Drupal::entityTypeManager()->getStorage('page_variant')->load('theme_mercury');
$tree = $variant->get('component_tree');
$navbar = $footer = NULL;
foreach ($tree as $uuid => $item) {
  if ($item['component_id'] === 'sdc.mercury.navbar') {
    $navbar = $uuid;
  }
  if ($item['component_id'] === 'sdc.mercury.footer') {
    $footer = $uuid;
  }
}

// Is there a component with this ID in this slot already?
$has = function (string $parent, string $slot, string $id) use (&$tree): ?string {
  foreach ($tree as $uuid => $item) {
    if (($item['parent_uuid'] ?? NULL) === $parent && ($item['slot'] ?? NULL) === $slot && $item['component_id'] === $id) {
      return $uuid;
    }
  }
  return NULL;
};
// Add a component to a slot, at its end or at its start. The tree is in
// order, so the start means before the slot's first component.
$generator = new Uuid();
$add = function (string $parent, string $slot, string $id, array $inputs, bool $first = FALSE) use (&$tree, $components, $generator, $has) {
  if ($has($parent, $slot, $id)) {
    return;
  }
  $uuid = $generator->generate();
  $new = [
    'parent_uuid' => $parent,
    'slot' => $slot,
    'uuid' => $uuid,
    'component_id' => $id,
    'component_version' => $components->load($id)->getActiveVersion(),
    'inputs' => $inputs,
  ];
  $position = count($tree);
  if ($first) {
    foreach (array_keys($tree) as $index => $key) {
      if (($tree[$key]['parent_uuid'] ?? NULL) === $parent && ($tree[$key]['slot'] ?? NULL) === $slot) {
        $position = $index;
        break;
      }
    }
  }
  $tree = array_slice($tree, 0, $position, TRUE) + [$uuid => $new] + array_slice($tree, $position, NULL, TRUE);
};

$branding = [
  'label' => 'Site branding',
  'label_display' => '0',
  'use_site_logo' => TRUE,
  'use_site_name' => TRUE,
  'use_site_slogan' => FALSE,
];
$text = fn(string $html) => [
  'text' => ['value' => $html, 'format' => 'canvas_html_block'],
  'text_size' => 'text-xs',
  'text_color' => 'default',
];

// 1. The Navbar.
$logo = $has($navbar, 'logo', 'block.system_branding_block');
$tree[$logo]['inputs']['use_site_name'] = TRUE;
$add($navbar, 'links', 'sdc.mercury.color-scheme-toggle', []);

// 2. The Footer.
$add($footer, 'footer_first', 'block.system_branding_block', $branding, TRUE);
$add($footer, 'footer_utility_first', 'block.system_menu_block.account', [
  'label' => 'User account menu',
  'label_display' => '0',
  'level' => 1,
  'depth' => NULL,
  'expand_all_items' => FALSE,
]);
$add($footer, 'footer_utility_last', 'sdc.mercury.text', $text(
  '<p>Photos and text: <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a></p>'));
$add($footer, 'footer_utility_last', 'block.system_powered_by_block', [
  'label' => 'Powered by Drupal',
  'label_display' => '0',
]);

// The first book's texts in the footer at the design's sizes.
foreach ($tree as $uuid => $item) {
  if (($item['parent_uuid'] ?? NULL) !== $footer) {
    continue;
  }
  if ($item['component_id'] === 'sdc.mercury.text') {
    $tree[$uuid]['inputs']['text_size'] = 'text-xs';
  }
  if ($item['component_id'] === 'sdc.mercury.heading') {
    $tree[$uuid]['inputs']['text_size'] = 'heading-responsive-xl';
  }
}

$variant->set('component_tree', $tree)->save();
echo "Done.\n";

<?php

/**
 * @file
 * Chapter 15 on the Drupal CMS site: the header and the footer in Canvas's
 * page variant, done by hand in the book (Canvas > the page template).
 *
 * Run with: ddev drush php:script <this file>, from the Drupal CMS site's
 * folder, after step 03-cms is on the site (it has the Color scheme toggle
 * component).
 * 1. Join us in the main menu (the How to join page).
 * 2. The Navbar: the logo with the site's name, and in its CTAs slot the
 *    language switcher and the color scheme toggle.
 * 3. The Footer: the logo and name; Meet us (a Heading and a Text, since
 *    this site has no content blocks); the footer menu and the User account
 *    menu (Log in); the license line (a Text) and Powered by Drupal.
 * Running it again puts the same things back, not a second copy.
 */

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\menu_link_content\Entity\MenuLinkContent;

// 1.
$join = \Drupal::entityTypeManager()->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main', 'title' => 'Join us']);
if (!$join) {
  MenuLinkContent::create([
    'menu_name' => 'main',
    'title' => 'Join us',
    'link' => ['uri' => 'entity:canvas_page/2'],
    'weight' => 10,
  ])->save();
}

// 2. and 3.
$components = \Drupal::entityTypeManager()->getStorage('component');
// The User account menu's block isn't offered in Canvas until it's enabled
// (Canvas > Components, or here).
$components->load('block.system_menu_block.account')->enable()->save();

// Saved as an entity, so Canvas works out the variant's dependencies again.
$variant = \Drupal::entityTypeManager()->getStorage('page_variant')->load('theme_mercury');
$tree = $variant->get('component_tree');

// Find the Navbar and the Footer, and keep only what Mercury put in them.
$navbar = $footer = NULL;
foreach ($tree as $uuid => $item) {
  if ($item['component_id'] === 'sdc.mercury.navbar') {
    $navbar = $uuid;
  }
  if ($item['component_id'] === 'sdc.mercury.footer') {
    $footer = $uuid;
  }
}
foreach ($tree as $uuid => $item) {
  if (in_array($item['parent_uuid'] ?? NULL, [$navbar, $footer], TRUE)
    && !in_array($item['component_id'], ['block.system_menu_block.main', 'block.system_menu_block.footer'], TRUE)) {
    unset($tree[$uuid]);
  }
}

$generator = new Uuid();
$add = function (string $parent, string $slot, string $id, array $inputs) use (&$tree, $components, $generator) {
  $uuid = $generator->generate();
  $tree[$uuid] = [
    'parent_uuid' => $parent,
    'slot' => $slot,
    'uuid' => $uuid,
    'component_id' => $id,
    'component_version' => $components->load($id)->getActiveVersion(),
    'inputs' => $inputs,
  ];
};
$branding = [
  'label' => 'Site branding',
  'label_display' => '0',
  'use_site_logo' => TRUE,
  'use_site_name' => TRUE,
  'use_site_slogan' => FALSE,
];
// The footer's text is small: Mercury's smallest size, text-xs.
$text = fn(string $html) => [
  'text' => ['value' => $html, 'format' => 'canvas_html_block'],
  'text_size' => 'text-xs',
  'text_color' => 'default',
];

$add($navbar, 'logo', 'block.system_branding_block', $branding);
$add($navbar, 'links', 'block.language_block.language_interface', [
  'label' => 'Language switcher',
  'label_display' => '0',
]);
$add($navbar, 'links', 'sdc.mercury.color-scheme-toggle', []);

$add($footer, 'footer_first', 'block.system_branding_block', $branding);
$add($footer, 'footer_last', 'sdc.mercury.heading', [
  'heading_text' => 'Meet us',
  'level' => 2,
  'text_size' => 'heading-responsive-xl',
  'text_color' => 'default',
  'align' => 'left',
]);
$add($footer, 'footer_last', 'sdc.mercury.text', $text(
  '<p><strong>Hikes:</strong> every other Saturday at 9:30.<br><strong>Photo evenings:</strong> the first Thursday of the month at 19:00, in a café in Leith.</p>'
  . '<p>Email: <a href="mailto:hello@lens-and-trail-cms.ddev.site">hello@lens-and-trail-cms.ddev.site</a></p>'
  . '<p>Follow us on <a href="https://www.flickr.com/groups/lens-and-trail/">Flickr</a></p>'));
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

$variant->set('component_tree', $tree)->save();
echo "Done.\n";

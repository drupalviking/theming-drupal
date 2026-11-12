<?php

/**
 * @file
 * Chapter 15's site building for the header, on the core test site, done by
 * hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder.
 * 1. The Site branding block shows the logo and the name, not the slogan.
 * 2. The main menu block shows only the top level. (The menus chapter puts
 *    the second level in a dropdown.)
 * 3. The User account menu block (Log in) moves to the footer.
 * 4. The favicon is the theme's favicon.svg (Appearance > Settings >
 *    Lens & Trail > Favicon). A new install gets it from the theme's
 *    config/install; this site had the theme already.
 */

use Drupal\block\Entity\Block;

$branding = Block::load('lens_trail_site_branding');
$settings = $branding->get('settings');
$settings['use_site_logo'] = TRUE;
$settings['use_site_name'] = TRUE;
$settings['use_site_slogan'] = FALSE;
$branding->set('settings', $settings)->save();

$menu = Block::load('lens_trail_main_menu');
$settings = $menu->get('settings');
$settings['level'] = 1;
$settings['depth'] = 1;
$menu->set('settings', $settings)->save();

Block::load('lens_trail_account_menu')->setRegion('footer')->save();

\Drupal::configFactory()->getEditable('lens_trail.settings')
  ->set('favicon.use_default', FALSE)
  ->set('favicon.path', 'themes/custom/lens_trail/favicon.svg')
  ->set('favicon.mimetype', 'image/svg+xml')
  ->save();
echo "Done.\n";

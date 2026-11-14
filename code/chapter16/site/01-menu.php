<?php

/**
 * @file
 * Chapter 16's first step on the core test site, done by hand in the book:
 * the main menu block shows two levels again (Chapter 15 set it to one), so
 * Hike archive is in the HTML under Hikes.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder.
 */

use Drupal\block\Entity\Block;

$menu = Block::load('lens_trail_main_menu');
$settings = $menu->get('settings');
$settings['level'] = 1;
$settings['depth'] = 2;
$settings['expand_all_items'] = FALSE;
$menu->set('settings', $settings)->save();
echo "Done.\n";

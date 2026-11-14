<?php

/**
 * @file
 * Chapter 15's search icon on the core test site: the first book's search box
 * block moves from Highlighted into the header's right-hand group, done by
 * hand in the book (Block layout).
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder,
 * after tools/use-step.sh 15 04. The block goes into Secondary menu, after
 * the language switcher (weight 0); the color scheme toggle comes after both.
 */

use Drupal\block\Entity\Block;

Block::load('lens_trail_search_box')->setRegion('secondary_menu')->setWeight(10)->save();
echo "Done.\n";

<?php

/**
 * @file
 * Chapter 7's Block layout steps on the core test site, done by hand in the
 * book (What happens to blocks in a removed region).
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder,
 * after tools/use-step.sh 07 01 (the theme's own regions). Join the club and
 * Next hikes were in Right sidebar, which the theme no longer has, so Drupal
 * disabled them and moved them to Header. They're enabled again, in Sidebar.
 */

use Drupal\block\Entity\Block;

foreach (['lens_trail_join_the_club' => 0, 'lens_trail_next_hikes' => 1] as $id => $weight) {
  Block::load($id)->setRegion('sidebar')->setWeight($weight)->enable()->save();
}
echo "Done.\n";

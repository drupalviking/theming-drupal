<?php

/**
 * @file
 * Chapter 5's Block layout moves on the core test site, done by hand in the
 * book (the table after Figure 5.3).
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder,
 * after tools/use-step.sh 05 01 (Lens & Trail installed and the default).
 * Drupal put every block from a region Olivero has and Lens & Trail doesn't
 * into the first region, Left sidebar. They go to the regions in the table.
 */

use Drupal\block\Entity\Block;

$moves = [
  // Above Main page content (weight 0).
  'lens_trail_page_title' => ['content', -10],
  'lens_trail_help' => ['help', 0],
  'lens_trail_join_the_club' => ['sidebar_second', 0],
  'lens_trail_next_hikes' => ['sidebar_second', 1],
  'lens_trail_newest_photos' => ['footer', 0],
  'lens_trail_club_info' => ['footer', 1],
  'lens_trail_footer' => ['footer', 2],
  'lens_trail_powered' => ['footer', 3],
];
foreach ($moves as $id => [$region, $weight]) {
  Block::load($id)->setRegion($region)->setWeight($weight)->save();
}
echo "Done.\n";

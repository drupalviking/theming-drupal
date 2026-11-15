<?php

/**
 * @file
 * Chapter 18, step 01, on the core test site: the hike's Default display, as
 * the chapter sets it up by hand.
 *
 * - Date and Hike leader: labels hidden (the template says "Led by").
 * - The four facts keep their labels: the fact template prints them.
 * - Elevation gain: off the page; it's in the walking time.
 *
 * Run from the site's folder: ddev drush php:script book2-site/01-hike-page.php
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

$display = EntityViewDisplay::load('node.hike.default');
foreach (['field_date', 'field_hike_leader'] as $name) {
  $component = $display->getComponent($name);
  $component['label'] = 'hidden';
  $display->setComponent($name, $component);
}
foreach (['field_meeting_place', 'field_distance', 'field_difficulty', 'field_area'] as $name) {
  $component = $display->getComponent($name);
  $component['label'] = 'above';
  $display->setComponent($name, $component);
}
$display->removeComponent('field_elevation_gain');
$display->save();

echo "Done.\n";

<?php

/**
 * @file
 * Chapter 18's new interface text in Spanish, done by hand in the book
 * (Configuration > Regional and language > User interface translation).
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder.
 * Runs on both test sites.
 */

$storage = \Drupal::service('locale.storage');
$strings = [
  // The walking time, from the preprocess chapter, which had no Spanish yet.
  'About @time on foot' => 'Alrededor de @time a pie',
  'Led by @name' => 'Guía: @name',
  // The line at the end of a printed page.
  'Printed from @url' => 'Impreso desde @url',
];
foreach ($strings as $source => $translation) {
  $string = $storage->findString(['source' => $source, 'context' => '']) ?? $storage->createString(['source' => $source, 'context' => ''])->save();
  $storage->createTranslation(['lid' => $string->lid, 'language' => 'es', 'translation' => $translation])->save();
}
\Drupal::service('cache.default')->deleteAll();
\Drupal::service('cache.render')->deleteAll();
echo "Done.\n";

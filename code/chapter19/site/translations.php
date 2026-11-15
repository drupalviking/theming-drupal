<?php

/**
 * @file
 * Chapter 19's new interface text in Spanish, done by hand in the book
 * (Configuration > Regional and language > User interface translation).
 *
 * Run with: ddev drush php:script <this file>, from the site's folder.
 * Runs on both test sites.
 */

use Drupal\Component\Gettext\PoItem;

$storage = \Drupal::service('locale.storage');
$strings = [
  // The album card's count: a plural, both forms in one string.
  '1 photo' . PoItem::DELIMITER . '@count photos' => '1 foto' . PoItem::DELIMITER . '@count fotos',
  // The name of the archive's months, for screen readers.
  'Hikes by month' => 'Excursiones por mes',
];
foreach ($strings as $source => $translation) {
  $string = $storage->findString(['source' => $source, 'context' => '']) ?? $storage->createString(['source' => $source, 'context' => ''])->save();
  $storage->createTranslation(['lid' => $string->lid, 'language' => 'es', 'translation' => $translation])->save();
}
\Drupal::service('cache.default')->deleteAll();
\Drupal::service('cache.render')->deleteAll();
echo "Done.\n";

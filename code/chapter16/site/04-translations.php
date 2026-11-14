<?php

/**
 * @file
 * Chapter 16's new interface text in Spanish, done by hand in the book
 * (Configuration > Regional and language > User interface translation).
 *
 * Run with: ddev drush php:script <this file>, from either site's folder.
 * "Menu" and "Close" are Drupal's own, and Spanish already has them; the
 * dropdown button's name is ours. Drupal keeps a file of each language's
 * script translations, made from the strings it has seen in the scripts;
 * it's made again on the next page view.
 */

$storage = \Drupal::service('locale.storage');
$strings = [
  'More in @title' => 'Más en @title',
];
foreach ($strings as $source => $translation) {
  $string = $storage->findString(['source' => $source, 'context' => '']) ?? $storage->createString(['source' => $source, 'context' => ''])->save();
  $storage->createTranslation(['lid' => $string->lid, 'language' => 'es', 'translation' => $translation])->save();
}
_locale_invalidate_js('es');
\Drupal::service('cache.default')->deleteAll();
echo "Done.\n";

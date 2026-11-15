<?php

/**
 * @file
 * Chapter 17's new interface text in Spanish, done by hand in the book
 * (Configuration > Regional and language > User interface translation).
 *
 * Run with: ddev drush php:script <this file>, from the core site's folder.
 * All of it is in the theme's templates, so Drupal translates it with the
 * page; the slider's script only reads it from the HTML.
 */

$storage = \Drupal::service('locale.storage');
$strings = [
  'Next hike' => 'Próxima excursión',
  'See the hike' => 'Ver la excursión',
  'Newest album' => 'Álbum más reciente',
  'See the photos' => 'Ver las fotos',
  'Club highlights' => 'Lo último del club',
  'carousel' => 'carrusel',
  'slide' => 'diapositiva',
  '@number of @total' => '@number de @total',
  'Slide @number of @total' => 'Diapositiva @number de @total',
  'Pause the slides' => 'Pausar las diapositivas',
  'Play the slides' => 'Reanudar las diapositivas',
  'Previous slide' => 'Diapositiva anterior',
  'Next slide' => 'Diapositiva siguiente',
  // The photo credit's, from the images chapter, which had no Spanish yet.
  'Photo:' => 'Foto:',
];
foreach ($strings as $source => $translation) {
  $string = $storage->findString(['source' => $source, 'context' => '']) ?? $storage->createString(['source' => $source, 'context' => ''])->save();
  $storage->createTranslation(['lid' => $string->lid, 'language' => 'es', 'translation' => $translation])->save();
}
\Drupal::service('cache.default')->deleteAll();
\Drupal::service('cache.render')->deleteAll();
echo "Done.\n";

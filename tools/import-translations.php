<?php

/**
 * @file
 * Saves configuration translations from a folder, for a recipe's config.
 *
 * A recipe installs only the default configuration, not the language
 * overrides that translate it (the Spanish titles of a view, say), and
 * Drupal CMS doesn't have the Config module that drush config:import
 * --partial needs. tools/hikes-recipe.php writes the overrides to
 * OUTPUT_DIR/translations/language/LANGCODE/NAME.yml; this saves each one,
 * replacing what the site had for that name and language.
 *
 * Usage, from the Drupal CMS site's folder, after the recipe:
 *   ddev drush php:script import-translations.php -- OUTPUT_DIR/translations
 */

use Symfony\Component\Yaml\Yaml;

$dir = $extra[0] ?? NULL;
if (!$dir || !is_dir("$dir/language")) {
  throw new \InvalidArgumentException('Usage: drush php:script import-translations.php -- TRANSLATIONS_DIR');
}

$count = 0;
foreach (glob("$dir/language/*", GLOB_ONLYDIR) as $language_dir) {
  $langcode = basename($language_dir);
  foreach (glob("$language_dir/*.yml") as $file) {
    $name = basename($file, '.yml');
    $override = \Drupal::languageManager()->getLanguageConfigOverride($langcode, $name);
    $override->setData(Yaml::parseFile($file))->save();
    $count++;
  }
}
echo "$count translations saved.\n";

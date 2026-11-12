<?php

/**
 * @file
 * Makes a recipe that adds Book 1's hikes and albums to the Drupal CMS test site.
 *
 * Book 1 builds the club's site on Drupal core; its Drupal CMS site has no
 * Hike content type. This book themes hikes on both sites, so tools/setup.sh
 * runs this script on the Drupal CMS site and applies the recipe it makes.
 *
 * The recipe comes from Book 1's export of the core site (its sites/core):
 * the Hike and Gallery (album) content types with their fields and displays,
 * the Areas vocabulary, the Hike leader role, the photo credit fields and the
 * Banner and Album view modes of Image media, and the hikes and albums with
 * the areas, photos, files and hike leaders they use. (Not the albums'
 * comments: comments aren't set up on the Drupal CMS site.)
 *
 * Usage, from the Drupal CMS site's folder:
 *   ddev drush php:script hikes-recipe.php -- BOOK1_SITES_CORE OUTPUT_DIR
 */

use Symfony\Component\Yaml\Yaml;

$hike_fields = ['field_area', 'field_body', 'field_date', 'field_difficulty', 'field_distance',
  'field_elevation_gain', 'field_hike_leader', 'field_meeting_place', 'field_photo'];
$credit_fields = ['field_license', 'field_photographer', 'field_source'];

$config = [
  'node.type.hike', 'language.content_settings.node.hike',
  'core.entity_form_display.node.hike.default',
  'core.entity_view_display.node.hike.default',
  'core.entity_view_display.node.hike.teaser',
  'core.entity_view_display.node.hike.card',
  'taxonomy.vocabulary.areas', 'language.content_settings.taxonomy_term.areas',
  'field.storage.taxonomy_term.field_photo', 'field.field.taxonomy_term.areas.field_photo',
  'core.date_format.day_date_and_time',
  'pathauto.pattern.hikes', 'pathauto.pattern.excursiones',
  'pathauto.pattern.areas', 'pathauto.pattern.areas_es',
  // The Hike leader field lists only users with this role.
  'user.role.hike_leader',
  // Albums.
  'node.type.gallery', 'language.content_settings.node.gallery',
  'field.storage.node.field_photos', 'field.storage.node.field_hike',
  'field.field.node.gallery.field_area', 'field.field.node.gallery.field_body',
  'field.field.node.gallery.field_hike', 'field.field.node.gallery.field_photos',
  'core.entity_form_display.node.gallery.default',
  'core.entity_view_display.node.gallery.default',
  'core.entity_view_display.node.gallery.teaser',
  'pathauto.pattern.albums', 'pathauto.pattern.albums_es',
  // The media view modes the hike page and the albums use.
  'core.entity_view_mode.media.banner', 'core.entity_view_mode.media.album',
  'core.entity_view_display.media.image.banner',
  'core.entity_view_display.media.image.album',
];
foreach ($hike_fields as $field) {
  $config[] = "field.storage.node.$field";
  $config[] = "field.field.node.hike.$field";
}
foreach ($credit_fields as $field) {
  $config[] = "field.storage.media.$field";
  $config[] = "field.field.media.image.$field";
}

// What differs on the Drupal CMS site.
const WORKFLOW = 'basic_editorial';       // Book 1's core site has "editorial".
// Image styles and responsive image styles the core site has and Drupal CMS
// doesn't, and Drupal CMS's nearest ones.
const IMAGE_STYLES = ['max_1300x1300' => 'uncropped_960w_webp'];
const RESPONSIVE_STYLES = ['banner' => '16_9_wide'];
const TEXT_FORMATS = ['basic_html' => 'content_format', 'full_html' => 'content_format'];
const ID_FIELDS = ['node' => 'nid', 'taxonomy_term' => 'tid', 'media' => 'mid', 'file' => 'fid', 'user' => 'uid'];

[$src, $out] = array_slice($extra ?? [], 0, 2) + [NULL, NULL];
if (!$src || !$out) {
  throw new \InvalidArgumentException('Usage: drush php:script hikes-recipe.php -- BOOK1_SITES_CORE OUTPUT_DIR');
}

function write_yaml(string $path, array $data): void {
  @mkdir(dirname($path), 0777, TRUE);
  file_put_contents($path, Yaml::dump($data, 20, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
}

function rename_formats(array &$value): void {
  foreach ($value as $key => &$item) {
    if ($key === 'format' && is_string($item) && isset(TEXT_FORMATS[$item])) {
      // Drupal CMS has one text format for editors, Content.
      $item = TEXT_FORMATS[$item];
    }
    elseif (is_array($item)) {
      rename_formats($item);
    }
  }
}

function entity_refs(array $value): array {
  $refs = [];
  foreach ($value as $key => $item) {
    if ($key === 'entity' && is_string($item)) {
      $refs[] = $item;
    }
    elseif (is_array($item)) {
      $refs = array_merge($refs, entity_refs($item));
    }
  }
  return $refs;
}

/**
 * Leaves out what the Drupal CMS site doesn't have.
 */
function clean(array &$data): void {
  $kind = $data['_meta']['entity_type'];
  rename_formats($data);
  if ($kind === 'media') {
    // Image media isn't translatable there.
    unset($data['translations']);
  }
  $sections = ['default'];
  foreach (array_keys($data['translations'] ?? []) as $langcode) {
    $sections[] = $langcode;
  }
  foreach ($sections as $name) {
    if ($name === 'default') {
      $section = &$data['default'];
    }
    else {
      $section = &$data['translations'][$name];
    }
    // New IDs on this site, so they can't clash with its own content.
    unset($section[ID_FIELDS[$kind] ?? '']);
    // Keep Book 1's aliases as they are. With Pathauto on, the import makes
    // the English aliases again, but not the Spanish ones.
    foreach ($section['path'] ?? [] as $i => $item) {
      $section['path'][$i]['pathauto'] = 0;
    }
    if (in_array($kind, ['user', 'media'])) {
      // Not translatable there, so they have no content_translation_* fields.
      foreach (array_keys($section) as $key) {
        if (str_starts_with($key, 'content_translation_')) {
          unset($section[$key]);
        }
      }
    }
    if ($kind === 'media') {
      unset($section['field_themes']);
    }
    if ($kind === 'node') {
      unset($section['comment']);
      // Drupal CMS puts every new content type in its workflow (an ECA model,
      // "Enable moderation for new content types"), albums too, which Book 1
      // didn't. Without a state, an album would come in as a draft.
      $section['moderation_state'] ??= [['value' => 'published']];
    }
    if ($kind === 'user') {
      unset($section['field_about_me'], $section['field_camera'], $section['user_picture']);
      // Of Book 1's roles, only Hike leader comes along.
      if (isset($section['roles'])) {
        $section['roles'] = array_values(array_filter($section['roles'],
          fn($role) => ($role['target_id'] ?? NULL) === 'hike_leader'));
        if (!$section['roles']) {
          unset($section['roles']);
        }
      }
    }
    unset($section);
  }
}

if (is_dir($out)) {
  \Drupal::service('file_system')->deleteRecursive($out);
}

// The configuration.
foreach ($config as $name) {
  $data = Yaml::parseFile("$src/config/$name.yml");
  unset($data['uuid'], $data['_core']);
  if (isset($data['dependencies']['config'])) {
    $data['dependencies']['config'] = array_map(
      fn($dep) => $dep === 'workflows.workflow.editorial' ? 'workflows.workflow.' . WORKFLOW : $dep,
      $data['dependencies']['config']);
  }
  if (isset($data['permissions'])) {
    $data['permissions'] = array_map(
      fn($perm) => str_replace('use editorial transition', 'use ' . WORKFLOW . ' transition', $perm),
      $data['permissions']);
  }
  // Comments aren't set up on the Drupal CMS site: no comment field.
  unset($data['content']['comment'], $data['hidden']['comment']);
  if (isset($data['dependencies'])) {
    foreach ($data['dependencies'] as $kind => $deps) {
      $data['dependencies'][$kind] = array_values(array_filter(array_map(function ($dep) {
        foreach (IMAGE_STYLES as $from => $to) {
          $dep = $dep === "image.style.$from" ? "image.style.$to" : $dep;
        }
        foreach (RESPONSIVE_STYLES as $from => $to) {
          $dep = $dep === "responsive_image.styles.$from" ? "responsive_image.styles.$to" : $dep;
        }
        return $dep;
      }, $deps), fn($dep) => !str_contains($dep, 'comment')));
    }
  }
  foreach ($data['content'] ?? [] as $field => $settings) {
    $style = $settings['settings']['image_style'] ?? NULL;
    if ($style && isset(IMAGE_STYLES[$style])) {
      $data['content'][$field]['settings']['image_style'] = IMAGE_STYLES[$style];
    }
    $style = $settings['settings']['responsive_image_style'] ?? NULL;
    if ($style && isset(RESPONSIVE_STYLES[$style])) {
      $data['content'][$field]['settings']['responsive_image_style'] = RESPONSIVE_STYLES[$style];
    }
  }
  write_yaml("$out/config/$name.yml", $data);
}

// The content: the hikes and albums, and everything they point at.
$by_uuid = [];
foreach (glob("$src/content/content/*/*.yml") as $file) {
  $data = Yaml::parseFile($file);
  $by_uuid[$data['_meta']['uuid']] = ['file' => $file, 'data' => $data];
}
$todo = array_keys(array_filter($by_uuid,
  fn($e) => $e['data']['_meta']['entity_type'] === 'node' && in_array($e['data']['_meta']['bundle'], ['hike', 'gallery'])));
$keep = [];
while ($todo) {
  $uuid = array_pop($todo);
  if (isset($keep[$uuid]) || !isset($by_uuid[$uuid])) {
    continue;
  }
  $data = $by_uuid[$uuid]['data'];
  clean($data);
  $keep[$uuid] = $data;
  $refs = $data;
  unset($refs['_meta']);
  array_push($todo, ...entity_refs($refs));
}

$counts = [];
foreach ($keep as $uuid => $data) {
  $data['_meta']['depends'] = array_intersect_key($data['_meta']['depends'] ?? [], $keep);
  if (!$data['_meta']['depends']) {
    unset($data['_meta']['depends']);
  }
  $from = $by_uuid[$uuid]['file'];
  $type = basename(dirname($from));
  write_yaml("$out/content/$type/" . basename($from), $data);
  if ($data['_meta']['entity_type'] === 'file') {
    // The importer looks for the file by the name in its URI.
    $name = basename($data['default']['uri'][0]['value']);
    copy(dirname($from) . "/$name", "$out/content/$type/$name");
  }
  $counts[$data['_meta']['entity_type']] = ($counts[$data['_meta']['entity_type']] ?? 0) + 1;
}

write_yaml("$out/recipe.yml", [
  'name' => 'Lens & Trail hikes and albums (Drupal CMS site)',
  'description' => "Book 1's hikes and albums, for theming them on the Drupal CMS site. Made by tools/hikes-recipe.php; do not edit by hand.",
  'type' => 'Site',
  // Albums get into the workflow by themselves (see clean()).
  'config' => ['actions' => ['workflows.workflow.' . WORKFLOW => ['addNodeTypes' => ['hike']]]],
]);
ksort($counts);
echo "$out: " . count($config) . " config items; content " . json_encode($counts) . "\n";

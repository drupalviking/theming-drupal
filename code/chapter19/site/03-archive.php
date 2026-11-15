<?php

/**
 * @file
 * Chapter 19, step 03, on both test sites: the Hike archive's months.
 *
 * - A date format, Month and year (month_year): "January 2027", in Spanish
 *   "Enero de 2027". The theme uses it by its machine name, in
 *   LensTrailHooks::preprocessViewsViewSummary().
 * - The months' links went to the Hikes page (/hikes), which has no month
 *   filter: an attachment links to its view's first page unless it's told
 *   otherwise. Now they go to the archive's own page, /hikes/archive/202701.
 * - Spanish for that page's title, "Hikes in January 2027", which had none:
 *   "Excursiones de Enero de 2027".
 *
 * Run from the site's folder: ddev drush php:script book2-site/03-archive.php
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\views\Entity\View;

DateFormat::load('month_year')?->delete();
DateFormat::create([
  'id' => 'month_year',
  'label' => 'Month and year',
  'pattern' => 'F Y',
])->save();
\Drupal::languageManager()->getLanguageConfigOverride('es', 'core.date_format.month_year')
  ->set('label', 'Mes y año')
  ->set('pattern', 'F \d\e Y')
  ->save();

$view = View::load('hikes');
$attachment = &$view->getDisplay('attachment_1');
$attachment['display_options']['defaults']['link_display'] = FALSE;
$attachment['display_options']['link_display'] = 'page_3';
$view->save();

\Drupal::languageManager()->getLanguageConfigOverride('es', 'views.view.hikes')
  ->set('display.page_3.display_options.arguments.field_date_value_year_month.title', 'Excursiones de {{ arguments.field_date_value_year_month }}')
  ->save();

drupal_flush_all_caches();
echo "Done.\n";

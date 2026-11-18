<?php

/**
 * @file
 * Chapter 19, step 02, on both test sites: enough news for a pager. The club
 * had one news item; this adds five more, from the days after the website
 * opened, in English and Spanish, and shows five to a page on the News page.
 *
 * Run again, it replaces the five items (matched by title) instead of adding
 * them twice.
 *
 * Run from the site's folder: ddev drush php:script book2-site/02-more-news.php
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\Entity\Node;
use Drupal\views\Entity\View;

$items = [
  [
    'date' => '2026-11-06 10:15',
    'photo' => 'The view from Carnethy Hill',
    'en' => [
      'Photo evening: autumn in the Pentlands',
      '<p>Thank you to everyone who came to Thursday\'s photo evening in Leith. Fourteen of us looked at autumn photos from the Pentland Hills, and argued about cropping until the café closed.</p><p>The next photo evening is on the first Thursday of December. The theme is "water".</p>',
    ],
    'es' => [
      'Velada de fotos: otoño en los Pentland',
      '<p>Gracias a todos los que vinieron a la velada de fotos del jueves en Leith. Catorce personas vimos fotos de otoño de las colinas Pentland y discutimos sobre encuadres hasta que cerró el café.</p><p>La próxima velada es el primer jueves de diciembre. El tema es «el agua».</p>',
    ],
  ],
  [
    'date' => '2026-11-09 18:40',
    'photo' => 'A burn in the Pentland Hills in winter',
    'en' => [
      'What to bring on a winter hike',
      '<p>The days are short and the hills are cold. On our winter hikes, bring waterproofs, a warm layer, a hat and gloves, something to eat, and a head torch, even if we plan to be back before dark.</p><p>Keep your camera batteries in an inside pocket: the cold empties them fast.</p>',
    ],
    'es' => [
      'Qué llevar en una caminata de invierno',
      '<p>Los días son cortos y las colinas, frías. En nuestras caminatas de invierno, lleva ropa impermeable, una capa de abrigo, gorro y guantes, algo de comer y una linterna frontal, aunque pensemos volver antes de que oscurezca.</p><p>Guarda las baterías de la cámara en un bolsillo interior: el frío las vacía enseguida.</p>',
    ],
  ],
  [
    'date' => '2026-11-10 12:00',
    'photo' => NULL,
    'en' => [
      'A walk for new members',
      '<p>New to the club? On Saturday 28 November we\'ll take a short walk up Calton Hill, with time for photos of the Old Town at sunset and a chat about the club\'s hikes.</p><p>Meet at the foot of the steps on Regent Road at 15:00. No booking needed.</p>',
    ],
    'es' => [
      'Un paseo para nuevos socios',
      '<p>¿Eres nuevo en el club? El sábado 28 de noviembre subiremos a Calton Hill en un paseo corto, con tiempo para fotografiar la Ciudad Vieja al atardecer y charlar sobre las caminatas del club.</p><p>Quedamos al pie de las escaleras de Regent Road a las 15:00. No hace falta reservar.</p>',
    ],
  ],
  [
    'date' => '2026-11-12 09:30',
    'photo' => 'Carnethy Hill from Turnhouse Hill',
    'en' => [
      'The club\'s photo competition opens',
      '<p>This year\'s photo competition is open to all members. Send up to three photos taken on a club hike in 2026, by the end of December.</p><p>The winners will be shown at the January photo evening, and on this website.</p>',
    ],
    'es' => [
      'Abre el concurso de fotos del club',
      '<p>El concurso de fotos de este año está abierto a todos los socios. Envía hasta tres fotos tomadas en una caminata del club en 2026, antes de que acabe diciembre.</p><p>Las ganadoras se mostrarán en la velada de fotos de enero y en este sitio web.</p>',
    ],
  ],
  [
    'date' => '2026-11-14 16:20',
    'photo' => NULL,
    'en' => [
      'Membership fees for 2027',
      '<p>Membership stays at £20 a year, and £10 for students. Fees for 2027 are due in January; the treasurer will send a reminder.</p>',
    ],
    'es' => [
      'Las cuotas de 2027',
      '<p>La cuota sigue siendo de 20 £ al año, y 10 £ para estudiantes. Las cuotas de 2027 se pagan en enero; el tesorero enviará un recordatorio.</p>',
    ],
  ],
];

// The news text: Body on the core site, Content on Drupal CMS. (Node::create()
// skips values for fields the content type doesn't have, without a word.)
$text = FieldConfig::loadByName('node', 'news', 'field_body') ? 'field_body' : 'field_content';
// Its text format: Basic HTML on core, Content on Drupal CMS.
$format = FilterFormat::load('basic_html') ? 'basic_html' : 'content_format';
$media = \Drupal::entityTypeManager()->getStorage('media');
$nodes = \Drupal::entityTypeManager()->getStorage('node');

foreach ($items as $item) {
  [$title, $body] = $item['en'];
  $nodes->delete($nodes->loadByProperties(['type' => 'news', 'title' => $title]));

  $photo = $item['photo'] ? current($media->loadByProperties(['name' => $item['photo']])) : NULL;
  $created = strtotime($item['date'] . ' Europe/London');
  $node = Node::create([
    'type' => 'news',
    'langcode' => 'en',
    'title' => $title,
    'uid' => 1,
    'status' => 1,
    'created' => $created,
    'changed' => $created,
    $text => ['value' => $body, 'format' => $format],
    'field_photo' => $photo ? [['target_id' => $photo->id()]] : [],
  ]);
  // News goes through the editorial workflow, where saving a draft is the
  // default: published is a moderation state, not only the status.
  if ($node->hasField('moderation_state')) {
    $node->set('moderation_state', 'published');
  }
  $node->save();

  [$title, $body] = $item['es'];
  $translation = $node->addTranslation('es', [
    'title' => $title,
    $text => ['value' => $body, 'format' => $format],
    'uid' => 1,
    'status' => 1,
    'created' => $created,
  ]);
  if ($translation->hasField('moderation_state')) {
    $translation->set('moderation_state', 'published');
  }
  $translation->save();
}

// Five to a page: with six items, the News page has a second page.
$view = View::load('news');
$display = &$view->getDisplay('default');
$display['display_options']['pager']['options']['items_per_page'] = 5;
$view->save();

drupal_flush_all_caches();
echo "Done.\n";

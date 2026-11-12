<?php

/**
 * @file
 * Chapter 15's site building for the footer, on the core test site, done by
 * hand in the book.
 *
 * Run with: ddev drush php:script <this file>, from the site's folder, after
 * the theme has the Footer bottom region (step 02-footer).
 * 1. A second Site branding block, in the footer: logo, name and slogan.
 * 2. The Club info block gets the title "Meet us" (in Spanish,
 *    "Encuéntranos"), shown, and its Email field becomes a mailto: link.
 * 3. A content block, License: "Photos and text: CC BY-SA 4.0", in
 *    Footer bottom, with its Spanish translation.
 * 4. User account menu and Powered by Drupal move to Footer bottom.
 * 5. The Newest photos block in the footer is turned off. The front page's
 *    own layout (Book 1's Layout Builder) already shows the newest photos,
 *    and the other pages don't need them.
 */

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// 1.
$branding = Block::load('lens_trail_footer_branding') ?: Block::create([
  'id' => 'lens_trail_footer_branding',
  'theme' => 'lens_trail',
  'plugin' => 'system_branding_block',
]);
$branding->set('settings', [
  'id' => 'system_branding_block',
  'label' => 'Site branding (footer)',
  'label_display' => '0',
  'provider' => 'system',
  'use_site_logo' => TRUE,
  'use_site_name' => TRUE,
  'use_site_slogan' => TRUE,
]);
$branding->setRegion('footer')->setWeight(-10)->save();

// 2.
$club = Block::load('lens_trail_club_info');
$settings = $club->get('settings');
$settings['label'] = 'Meet us';
$settings['label_display'] = 'visible';
$club->set('settings', $settings)->setWeight(0)->save();
\Drupal::languageManager()->getLanguageConfigOverride('es', 'block.block.lens_trail_club_info')
  ->set('settings.label', 'Encuéntranos')
  ->save();
$display = EntityViewDisplay::load('block_content.club_info.default');
$email = $display->getComponent('field_email');
$email['type'] = 'email_mailto';
$display->setComponent('field_email', $email)->save();
Block::load('lens_trail_footer')->setWeight(10)->save();

// 3.
$license = \Drupal::entityTypeManager()->getStorage('block_content')
  ->loadByProperties(['info' => 'License']);
$license = reset($license) ?: BlockContent::create(['type' => 'basic', 'info' => 'License']);
$license->set('body', [
  'value' => '<p>Photos and text: <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a></p>',
  'format' => 'basic_html',
]);
$license->save();
$spanish = $license->hasTranslation('es') ? $license->getTranslation('es') : $license->addTranslation('es', $license->toArray());
$spanish->set('info', 'Licencia');
$spanish->set('body', [
  'value' => '<p>Fotos y textos: <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.es">CC BY-SA 4.0</a></p>',
  'format' => 'basic_html',
]);
$spanish->save();
$block = Block::load('lens_trail_license') ?: Block::create([
  'id' => 'lens_trail_license',
  'theme' => 'lens_trail',
  'plugin' => 'block_content:' . $license->uuid(),
]);
$block->set('settings', [
  'id' => 'block_content:' . $license->uuid(),
  'label' => 'License',
  'label_display' => '0',
  'provider' => 'block_content',
  'status' => TRUE,
  'info' => '',
  'view_mode' => 'full',
]);
$block->setRegion('footer_bottom')->setWeight(0)->save();

// 4.
Block::load('lens_trail_account_menu')->setRegion('footer_bottom')->setWeight(5)->save();
Block::load('lens_trail_powered')->setRegion('footer_bottom')->setWeight(10)->save();

// 5.
Block::load('lens_trail_newest_photos')->disable()->save();
echo "Done.\n";

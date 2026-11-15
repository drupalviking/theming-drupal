<?php

namespace Drupal\lens_trail\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;

/**
 * Hook implementations for lens_trail.
 */
class LensTrailHooks {

  use StringTranslationTrait;

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected ThemeSettingsProvider $themeSettings,
  ) {}

  /**
   * @file
   * Functions to support theming.
   */

  /**
   * Implements hook_preprocess_image_widget().
   */
  #[Hook('preprocess_image_widget')]
  public function preprocessImageWidget(array &$variables): void {
    $data = &$variables['data'];
    // This prevents image widget templates from rendering preview container
    // HTML to users that do not have permission to access these previews.
    // @todo revisit in https://drupal.org/node/953034
    // @todo revisit in https://drupal.org/node/3114318
    if (isset($data['preview']['#access']) && $data['preview']['#access'] === FALSE) {
      unset($data['preview']);
    }
  }

  /**
   * Implements hook_styleguide_sections_alter().
   *
   * The club's own Colors and Text pages in SDC Styleguide, instead of the
   * module's samples. The values come from the design tokens: styleguide.json
   * is written by scripts/tokens.py --styleguide. When a theme gives the module
   * any sections, the module's own are gone, so both pages are here.
   */
  #[Hook('styleguide_sections_alter')]
  public function styleguideSectionsAlter(array &$sections): void {
    $tokens = json_decode(file_get_contents(dirname(__DIR__, 2) . '/styleguide.json'), TRUE);
    $sections['colors'] = [
      'name' => $this->t('Colors'),
      'roles' => $tokens['roles'],
      'pairs' => $tokens['pairs'],
    ];
    $sections['text'] = [
      'name' => $this->t('Text'),
      'type_scale' => $tokens['type_scale'],
    ];
  }

  /**
   * Implements hook_preprocess_field().
   *
   * The Banner photo is the biggest thing at the top of a hike or news page,
   * so the browser should fetch it before anything else it finds on the way.
   * Drupal has a setting for loading="eager", but none for fetchpriority.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    $element = $variables['element'];
    if ($element['#field_name'] !== 'field_media_image' || $element['#view_mode'] !== 'banner') {
      return;
    }
    foreach ($variables['items'] as &$item) {
      // The Responsive image formatter takes #attributes for the img element.
      // (The plain Image formatter calls them #item_attributes.)
      $item['content']['#attributes']['fetchpriority'] = 'high';
    }
  }

  /**
   * Implements hook_preprocess_media().
   *
   * A photo in an album links to a large version of itself. Image fields can
   * only link to the original file, which is far bigger than a screen needs
   * (and has no AVIF version), so the link and the lightbox's sizes come from
   * the Max image styles.
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(array &$variables): void {
    $media = $variables['media'];
    if ($media->bundle() !== 'image' || $variables['view_mode'] !== 'album' || $media->get('field_media_image')->isEmpty()) {
      return;
    }
    $uri = $media->get('field_media_image')->entity->getFileUri();
    $srcset = [];
    foreach (['max_650x650' => 650, 'max_1300x1300' => 1300, 'max_2600x2600' => 2600] as $name => $width) {
      $srcset[] = ImageStyle::load($name)->buildUrl($uri) . " {$width}w";
    }
    $variables['large'] = [
      'href' => ImageStyle::load('max_1300x1300')->buildUrl($uri),
      'srcset' => implode(', ', $srcset),
    ];
  }

  /**
   * Implements hook_preprocess_node().
   *
   * Adds an estimated walking time to hike cards, with Naismith's rule: 12
   * minutes for each kilometer, and 10 more for each 100 meters of climb.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if ($node->bundle() !== 'hike' || !in_array($variables['view_mode'], ['card', 'card_list'], TRUE) || $node->get('field_distance')->isEmpty()) {
      return;
    }
    $minutes = $node->get('field_distance')->value * 12
      + (int) $node->get('field_elevation_gain')->value / 100 * 10;
    // It's an estimate: nearest ten minutes.
    $variables['walking_time'] = $this->dateFormatter->formatInterval(round($minutes / 10) * 600);
  }

  /**
   * Implements hook_form_system_theme_settings_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function themeSettingsAlter(array &$form, FormStateInterface $form_state): void {
    $form['color_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Color scheme'),
      '#description' => $this->t('What visitors see before they choose for themselves.'),
      '#options' => [
        'system' => $this->t("Follow the visitor's device"),
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ],
      '#default_value' => $this->themeSettings->getSetting('color_scheme') ?? 'system',
    ];
  }

  /**
   * Implements hook_preprocess_html().
   *
   * Tells the CSS when the site has a fixed color scheme, and the toggle what
   * the site's choice is.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $scheme = $this->themeSettings->getSetting('color_scheme');
    if (in_array($scheme, ['light', 'dark'], TRUE)) {
      $variables['html_attributes']->setAttribute('data-color-scheme', $scheme);
    }
    // The toggle goes back to this when a visitor chooses "Site default".
    $variables['#attached']['drupalSettings']['lensTrail']['colorScheme'] = $scheme;
  }

}

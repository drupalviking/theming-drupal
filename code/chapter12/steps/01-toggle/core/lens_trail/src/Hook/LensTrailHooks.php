<?php

namespace Drupal\lens_trail\Hook;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
   * Implements hook_preprocess_node().
   *
   * Adds an estimated walking time to hike cards, with Naismith's rule: 12
   * minutes for each kilometer, and 10 more for each 100 meters of climb.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if ($node->bundle() !== 'hike' || $variables['view_mode'] !== 'card' || $node->get('field_distance')->isEmpty()) {
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
    // The toggle goes back to this when a visitor chooses "System".
    $variables['#attached']['drupalSettings']['lensTrail']['colorScheme'] = $scheme;
  }

}

<?php

namespace Drupal\lens_trail\Hook;

use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Hook implementations for lens_trail.
 */
class LensTrailHooks {

  use StringTranslationTrait;

  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected ThemeSettingsProvider $themeSettings,
    protected PathMatcherInterface $pathMatcher,
    protected TitleResolverInterface $titleResolver,
    protected RequestStack $requestStack,
    protected RouteMatchInterface $routeMatch,
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
   * and the Hero photo at the top of the front page, so the browser should
   * fetch it before anything else it finds on the way. Drupal has a setting
   * for loading="eager", but none for fetchpriority.
   *
   * On a hike's page, the distance fact also says how long the walk takes.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    $element = $variables['element'];
    if ($element['#field_name'] === 'field_distance' && $element['#bundle'] === 'hike' && $element['#view_mode'] === 'full') {
      $variables['extra'] = $this->walkingTime($element['#object']);
      return;
    }
    if ($element['#field_name'] !== 'field_media_image' || !in_array($element['#view_mode'], ['banner', 'hero'], TRUE)) {
      return;
    }
    foreach ($variables['items'] as &$item) {
      // The Responsive image formatter takes #attributes for the img element.
      // (The plain Image formatter calls them #item_attributes.)
      $item['content']['#attributes']['fetchpriority'] = 'high';
    }
  }

  /**
   * Implements hook_theme_suggestions_field_alter().
   *
   * A hike's key facts, on its page, are pairs of a label and a value in a
   * description list, and so are an album's hike and area. One template for
   * all of them: field--fact.html.twig.
   */
  #[Hook('theme_suggestions_field_alter')]
  public function themeSuggestionsFieldAlter(array &$suggestions, array $variables): void {
    $element = $variables['element'];
    $facts = [
      'hike' => ['field_meeting_place', 'field_distance', 'field_difficulty', 'field_area'],
      'gallery' => ['field_hike', 'field_area'],
    ];
    if ($element['#entity_type'] === 'node' && $element['#view_mode'] === 'full' && in_array($element['#field_name'], $facts[$element['#bundle']] ?? [], TRUE)) {
      $suggestions[] = 'field__fact';
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
   * Adds an estimated walking time to hike cards.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if ($node->bundle() === 'hike' && in_array($variables['view_mode'], ['card', 'card_list', 'teaser'], TRUE)) {
      $variables['walking_time'] = $this->walkingTime($node);
    }
  }

  /**
   * An estimated walking time, with Naismith's rule: 12 minutes for each
   * kilometer, and 10 more for each 100 meters of climb.
   */
  protected function walkingTime(NodeInterface $node): ?string {
    if ($node->get('field_distance')->isEmpty()) {
      return NULL;
    }
    $minutes = $node->get('field_distance')->value * 12
      + (int) $node->get('field_elevation_gain')->value / 100 * 10;
    // It's an estimate: nearest ten minutes.
    return $this->t('About @time on foot', ['@time' => $this->dateFormatter->formatInterval(round($minutes / 10) * 600)]);
  }

  /**
   * Implements hook_preprocess_page().
   *
   * Hike and news pages print their own h1, after the photo and the date
   * (see node--hike--full.html.twig and node--news--full.html.twig), so the
   * Page title block goes. Block layout can show a block only for some
   * content types, but not hide it for one.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $node = $variables['node'] ?? NULL;
    if (!$node instanceof NodeInterface || !in_array($node->bundle(), ['hike', 'news'], TRUE)) {
      return;
    }
    foreach (Element::children($variables['page']['content']) as $key) {
      if (($variables['page']['content'][$key]['#plugin_id'] ?? NULL) === 'page_title_block') {
        unset($variables['page']['content'][$key]);
      }
    }
  }

  /**
   * Implements hook_preprocess_region().
   *
   * On the front page, the page's title goes in the Hero region, which comes
   * before the content region, so the h1 comes before the hero's headings.
   * The page title block is hidden on the front page in Block layout.
   */
  #[Hook('preprocess_region')]
  public function preprocessRegion(array &$variables): void {
    if ($variables['region'] !== 'hero' || !$this->pathMatcher->isFrontPage()) {
      return;
    }
    $route = $this->routeMatch->getRouteObject();
    $variables['page_title'] = $this->titleResolver->getTitle($this->requestStack->getCurrentRequest(), $route);
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

  /**
   * Implements hook_preprocess_views_view_summary().
   *
   * A summary of hikes by month, such as the one on the Hike archive, gets
   * its months from the hike's Date field: "202701". Views does turn the
   * months of the Authored on date into "January 2027", but not those of a
   * date field, so the theme does it here.
   */
  #[Hook('preprocess_views_view_summary')]
  public function preprocessViewsViewSummary(array &$variables): void {
    if (!$this->yearMonthArgument($variables['view'])) {
      return;
    }
    foreach ($variables['rows'] as $row) {
      $row->link = $this->monthName($row->link) ?? $row->link;
    }
  }

  /**
   * Implements hook_views_pre_render().
   *
   * The same for the title of one month's page, "Hikes in 202612". Views
   * calls this hook in the theme too, after the view has its title and its
   * results and before they are rendered.
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    $argument = $this->yearMonthArgument($view);
    $month = $argument ? $this->monthName((string) $argument->getValue()) : NULL;
    if ($month) {
      $view->setTitle(str_replace($argument->getValue(), $month, $view->getTitle()));
    }
  }

  /**
   * The view's last argument, if it's a year and month from a date field.
   */
  protected function yearMonthArgument(ViewExecutable $view): ?ArgumentPluginBase {
    $argument = $view->argument ? end($view->argument) : NULL;
    return $argument?->getPluginId() === 'datetime_year_month' ? $argument : NULL;
  }

  /**
   * "202701" as "January 2027", in the Month and year date format.
   */
  protected function monthName(string $year_month): ?string {
    // The middle of the month, so no time zone can move it to the next one.
    $date = \DateTime::createFromFormat('Ymd H:i', $year_month . '15 12:00', new \DateTimeZone('UTC'));
    return preg_match('/^\d{6}$/', $year_month) && $date
      ? $this->dateFormatter->format($date->getTimestamp(), 'month_year', '', 'UTC')
      : NULL;
  }

}

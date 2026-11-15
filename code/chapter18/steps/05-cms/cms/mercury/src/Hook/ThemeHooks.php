<?php

declare(strict_types=1);

namespace Drupal\mercury\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Breadcrumb\ChainBreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\mercury\RenderCallbacks;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains hook implementations for Mercury.
 */
final class ThemeHooks {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The Drupal root.
   */
  private static ?string $appRoot = NULL;

  public function __construct(
    private readonly ThemeSettingsProvider $themeSettings,
    private readonly RequestStack $requestStack,
    private readonly ThemeExtensionList $themeList,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly TitleResolverInterface $titleResolver,
    private readonly ChainBreadcrumbBuilderInterface $breadcrumb,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly DateFormatterInterface $dateFormatter,
    #[Autowire(param: 'app.root')] string $appRoot,
  ) {
    self::$appRoot ??= $appRoot;
  }

  /**
   * Implements hook_element_info_alter().
   */
  #[Hook('element_info_alter')]
  public function alterElementInfo(array &$info): void {
    $info['component']['#pre_render'][] = [RenderCallbacks::class, 'preRenderComponent'];
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function alterLibraryInfo(array &$libraries, string $extension): void {
    $override = static function (string $name, string $replacement) use (&$libraries): void {
      $old_parents = ['global', 'css', 'theme', $name];
      $new_parents = [...array_slice($old_parents, 0, -1), $replacement];
      $css_settings = NestedArray::getValue($libraries, $old_parents);
      NestedArray::setValue($libraries, $new_parents, $css_settings);
      NestedArray::unsetValue($libraries, $old_parents);
    };
    if ($extension === 'mercury') {
      if (file_exists(self::$appRoot . '/theme.css')) {
        $override('src/theme.css', '/theme.css');
      }
      if (file_exists(self::$appRoot . '/fonts.css')) {
        $override('src/fonts.css', '/fonts.css');
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_system_theme_settings_alter')]
  public function themeSettingsFormAlter(array &$form): void {
    $form['scheme'] = [
      '#type' => 'radios',
      '#title' => t('Color scheme'),
      '#default_value' => $this->themeSettings->getSetting('scheme'),
      '#options' => [
        'light' => t('Light'),
        'dark' => t('Dark'),
      ],
    ];
    $message = $this->t("See <code>@path</code> to learn how to customize Mercury's fonts, colors, and components.", [
      '@path' => $this->themeList->getPath('mercury') . '/CUSTOMIZING.md',
    ]);
    $this->messenger()->addMessage($message, 'info');
  }

  /**
   * Implements template_preprocess_image_widget().
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
   * Implements template_preprocess_html().
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $variables['scheme'] = $this->themeSettings->getSetting('scheme');
    // Get the theme base path for font preloading.
    $variables['mercury_path'] = $this->requestStack->getCurrentRequest()->getBasePath() . '/' . $this->themeList->getPath('mercury');
  }

  /**
   * Implements template_preprocess_page().
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    // @see \Drupal\Core\Block\Plugin\Block\PageTitleBlock::build()
    $variables['title'] = [
      '#type' => 'page_title',
      '#title' => $variables['page']['#title'] ?? $this->titleResolver->getTitle(
        $this->requestStack->getCurrentRequest(),
        $this->routeMatch->getRouteObject(),
      ),
    ];

    // @see \Drupal\system\Plugin\Block\SystemBreadcrumbBlock::build()
    $variables['breadcrumb'] = $this->breadcrumb->build($this->routeMatch)
      ->toRenderable();

    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === 'entity.canvas_page.canonical' || str_starts_with($this->routeMatch->getRouteObject()?->getPath() ?? '', '/canvas/')) {
      $variables['rendered_by_canvas'] = TRUE;
    }
    elseif ($route_name === 'entity.node.canonical' && $this->moduleHandler->moduleExists('canvas')) {
      $node = $this->routeMatch->getParameter('node');
      assert($node instanceof NodeInterface);

      $variables['rendered_by_canvas'] = (bool) $this->entityTypeManager->getStorage('content_template')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->condition('content_entity_type_id', 'node')
        ->condition('content_entity_type_bundle', $node->getType())
        ->condition('content_entity_type_view_mode', 'full')
        ->condition('status', TRUE)
        ->execute();
    }
    else {
      $variables['rendered_by_canvas'] = FALSE;
    }

    // Lens & Trail: hike and news pages print their own h1, after the photo
    // and the date (node--hike--full.html.twig, node--news--full.html.twig).
    $node = $variables['node'] ?? NULL;
    if ($node instanceof NodeInterface && in_array($node->bundle(), ['hike', 'news'], TRUE)) {
      $variables['title'] = NULL;
    }
  }

  /**
   * Implements hook_preprocess_field().
   *
   * The Banner photo, and the first slide's on Home, are fetched first, as on
   * the core site. A hike's distance gets the walking time.
   */
  #[Hook('preprocess_field')]
  public function preprocessField(array &$variables): void {
    $element = $variables['element'];
    // Lens & Trail: on a hike's page, the walking time under the distance
    // (field--fact.html.twig prints it).
    if ($element['#field_name'] === 'field_distance' && $element['#bundle'] === 'hike' && $element['#view_mode'] === 'full') {
      $variables['extra'] = $this->walkingTime($element['#object']);
    }
    if ($element['#field_name'] !== 'field_media_image' || !in_array($element['#view_mode'], ['banner', 'slide'], TRUE)) {
      return;
    }
    foreach ($variables['items'] as &$item) {
      $item['content']['#attributes']['fetchpriority'] = 'high';
    }
  }

  /**
   * Implements hook_theme_suggestions_field_alter().
   *
   * A hike's key facts, on its page, are pairs of a label and a value in a
   * description list, and so are an album's hike and area: the core theme's
   * field--fact.html.twig for all of them.
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
   * Implements hook_preprocess_node().
   *
   * A hike in a list (the Hike card, from node--hike--card.html.twig) gets
   * its walking time.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    $node = $variables['node'];
    if ($node->bundle() === 'hike' && $variables['view_mode'] === 'card') {
      $variables['walking_time'] = $this->walkingTime($node);
    }
  }

  /**
   * An estimated walking time, as on the core site: Naismith's rule, 12
   * minutes for each kilometer and 10 more for each 100 meters of climb.
   */
  private function walkingTime(NodeInterface $node): ?string {
    if ($node->get('field_distance')->isEmpty()) {
      return NULL;
    }
    $minutes = $node->get('field_distance')->value * 12
      + (int) $node->get('field_elevation_gain')->value / 100 * 10;
    // It's an estimate: nearest ten minutes.
    return (string) $this->t('About @time on foot', ['@time' => $this->dateFormatter->formatInterval((int) round($minutes / 10) * 600)]);
  }

  /**
   * Implements hook_preprocess_media().
   *
   * A photo in an album links to a large version of itself, as on the core
   * site. Drupal CMS's uncropped image styles give the sizes: the largest is
   * 960 pixels wide.
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(array &$variables): void {
    $media = $variables['media'];
    if ($media->bundle() !== 'image' || $variables['view_mode'] !== 'album' || $media->get('field_media_image')->isEmpty()) {
      return;
    }
    $uri = $media->get('field_media_image')->entity->getFileUri();
    $styles = $this->entityTypeManager->getStorage('image_style');
    $srcset = [];
    foreach (['uncropped_500w_webp' => 500, 'uncropped_720w_webp' => 720, 'uncropped_960w_webp' => 960] as $name => $width) {
      $srcset[] = $styles->load($name)->buildUrl($uri) . " {$width}w";
    }
    $variables['large'] = [
      'href' => $styles->load('uncropped_960w_webp')->buildUrl($uri),
      'srcset' => implode(', ', $srcset),
    ];
  }

}

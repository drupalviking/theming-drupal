#!/usr/bin/env bash
# Chapter 14: Focal Point on the core test site, which runs Drupal 12 before
# Focal Point and Crop have Drupal 12 releases (see the chapter's box).
#
# Usage, from the core test site's folder (../lens-and-trail-theme):
#   bash ../theming-drupal/code/chapter14/site/02-focal-point.sh
set -euo pipefail
BOOK2=$(cd "$(dirname "$0")/../../.." && pwd)

# 1. Let Composer install modules whose releases don't list Drupal 12 yet.
ddev composer config --no-plugins allow-plugins.mglaman/composer-drupal-lenient true
ddev composer require mglaman/composer-drupal-lenient
ddev composer config --json extra.drupal-lenient.allowed-list '["drupal/focal_point", "drupal/crop"]'

# 2. The Drupal 12 merge requests from both issue queues, as patches.
mkdir -p patches
cp "$BOOK2/code/chapter14/patches/"*.patch patches/
ddev composer config --json extra.patches.drupal/crop '[{"description": "Drupal 12 compatibility https://www.drupal.org/project/crop/issues/3629654 (MR !37)", "url": "patches/crop-3629654-drupal-12.patch"}]'
ddev composer config --json extra.patches.drupal/focal_point '[{"description": "Drupal 12 readiness https://www.drupal.org/project/focal_point/issues/3634508 (MR !67, with its src/ Hook folder renamed to src/Hook)", "url": "patches/focal_point-3634508-drupal-12.patch"}]'

# 3. The module; Composer applies the patches as it installs it.
ddev composer require drupal/focal_point
ddev drush pm:install focal_point -y

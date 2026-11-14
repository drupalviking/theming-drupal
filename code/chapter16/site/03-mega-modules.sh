#!/usr/bin/env bash
# Chapter 16's two modules for the mega menu, on the core test site, as the
# chapter does it: Menu Item Extras (fields on menu links) and Views Reference
# (a field that points at a view display).
#
# Views Reference 8.x-2.0-beta14 says it supports Drupal 12, but isn't ready.
# The patch, ours and not posted upstream (2026-11-14), fixes four things:
# its select widget's massageFormValues() lacks the return type core's base
# class now has (a fatal error on any form with field widgets); its settings
# plugin manager is annotation-only, which Drupal 12 refuses; its formatter
# throws away the cacheability it merges (a warning, and stale lists); and
# its view title preprocess function in the .module file is no longer called
# on Drupal 12, so the lists lose their headings. See the chapter's box. The
# same commands work on the Drupal CMS site (Drupal 11), where the patch
# applies and changes nothing that matters.
#
# Usage, from the core test site's folder (../lens-and-trail-theme):
#   bash ../theming-drupal/code/chapter16/site/03-mega-modules.sh
set -euo pipefail
BOOK2=$(cd "$(dirname "$0")/../../.." && pwd)

mkdir -p patches
cp "$BOOK2/code/chapter16/patches/viewsreference-drupal-12.patch" patches/
ddev composer config --json extra.patches.drupal/viewsreference '[{"description": "Drupal 12: massageFormValues() return type, and an attribute class for the settings plugins. Ours (2026-11-14)", "url": "patches/viewsreference-drupal-12.patch"}]'
ddev composer require drupal/menu_item_extras:^3.2 'drupal/viewsreference:^2.0@beta'
# The site keeps its patches in patches.lock.json (Book 1's Chapter 22).
ddev composer patches-relock
ddev composer patches-repatch
ddev drush pm:install menu_item_extras viewsreference -y

#!/usr/bin/env bash
# Chapter 13's SDC Styleguide on the core test site, as the chapter does it by
# hand: the module from Composer, installed, and the components of core's
# Navigation module and of Olivero left out of the explorer.
#
# Usage, from the core test site's folder (../lens-and-trail-theme):
#   bash ../theming-drupal/code/chapter13/site/styleguide.sh
set -euo pipefail

ddev composer require drupal/sdc_styleguide
ddev drush pm:install sdc_styleguide -y
ddev drush config:set sdc_styleguide.settings component_explorer.exclude_modules '["navigation"]' --input-format=yaml -y
ddev drush config:set sdc_styleguide.settings component_explorer.exclude_themes '["olivero"]' --input-format=yaml -y
# Saving the settings doesn't clear the explorer's cache.
ddev drush cache:rebuild

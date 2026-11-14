#!/usr/bin/env bash
# Put one of a chapter's theme snapshots on the test sites.
#
# Usage: tools/use-step.sh <chapter> <step> [--no-default]
#   e.g. tools/use-step.sh 04 01      (or the full name: 01-generate)
#        tools/use-step.sh d 01       (an appendix: code/appendix-d/)
#
# A step folder has one subfolder per test site, each holding theme folders:
#
#   code/chapterNN/steps/NN-name/core/<theme>/   -> the core test site
#   code/chapterNN/steps/NN-name/cms/<theme>/    -> the Drupal CMS test site
#
# The test sites are DDEV projects outside this repository (see tools/setup.sh
# and PROJECT-LOG.md). DDEV only mounts a project's own folder into its
# container, so a symlink to this repository wouldn't work there: the theme is
# copied (rsync --delete) into the site's web/themes/custom/. Each theme is
# installed and, unless --no-default, set as the site's default theme.
#
# Site paths can be changed with CORE_DIR and CMS_DIR.
set -euo pipefail

cd "$(dirname "$0")/.."
REPO=$(pwd)

# The sites tools/setup.sh makes, next to this book's folder.
BASE=${BASE:-$(dirname "$REPO")}
CORE_DIR=${CORE_DIR:-$BASE/lens-and-trail-theme}
CMS_DIR=${CMS_DIR:-$BASE/lens-and-trail-cms-theme}

if [[ $# -lt 2 ]]; then
  echo "Usage: tools/use-step.sh <chapter> <step> [--no-default]" >&2
  exit 1
fi

# A chapter is a number (05); an appendix is a letter (d), in code/appendix-d/.
if [[ "$1" =~ ^[A-Za-z]$ ]]; then
  chapter="appendix-$(echo "$1" | tr '[:upper:]' '[:lower:]')"
else
  chapter="chapter$1"
fi
step_dir=$(find "code/$chapter/steps" -maxdepth 1 -type d -name "$2*" 2>/dev/null | sort | head -n 1)
if [[ -z "$step_dir" ]]; then
  echo "No step matching '$2' in code/$chapter/steps" >&2
  exit 1
fi
set_default=1
[[ "${3:-}" == "--no-default" ]] && set_default=0

found=0
for site in core cms; do
  [[ -d "$step_dir/$site" ]] || continue
  if [[ $site == core ]]; then site_dir=$CORE_DIR; else site_dir=$CMS_DIR; fi
  if [[ ! -d "$site_dir/web" ]]; then
    echo "The $site test site isn't at $site_dir (run tools/setup.sh, or set $(echo "$site" | tr a-z A-Z)_DIR)" >&2
    exit 1
  fi
  mkdir -p "$site_dir/web/themes/custom"
  for theme_dir in "$step_dir/$site"/*/; do
    theme=$(basename "$theme_dir")
    # Every step's files have the same modification times, from the git
    # checkout. Drupal caches parsed YAML files (the libraries and info files)
    # and PHP caches compiled classes by path and modification time, so a
    # copied file that keeps its time looks unchanged to them, and the previous
    # step's version stays in use, even after a cache rebuild. --no-times gives
    # each copied file the time of copying; --checksum makes rsync compare
    # contents, because a file with a new content but the same size and time
    # would otherwise be skipped.
    rsync -a --no-times --checksum --delete "$REPO/$theme_dir" "$site_dir/web/themes/custom/$theme/"
    (
      cd "$site_dir"
      ddev drush theme:install -y "$theme"
      if [[ $set_default == 1 ]]; then
        ddev drush config:set -y system.theme default "$theme"
      fi
      # The web server's PHP can still run a class from the previous step
      # (src/Hook/*.php), when the new file hasn't reached the container by the
      # time it checks: pages then fail with "does not have a method". A
      # graceful reload of PHP-FPM empties its opcache. It comes before the
      # cache rebuild, so no page is built while it reloads.
      ddev exec 'killall -USR2 php-fpm' >/dev/null 2>&1 || true
      # Twig and the asset library definitions are cached; a rebuild picks up
      # the new templates, libraries and components.
      ddev drush cache:rebuild
    )
    echo "$site: $theme now from $step_dir"
    found=1
  done
done

if [[ $found == 0 ]]; then
  echo "$step_dir has no core/ or cms/ folder with a theme in it" >&2
  exit 1
fi

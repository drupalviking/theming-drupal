#!/usr/bin/env bash
# Put one of a chapter's theme snapshots on the test sites.
#
# Usage: tools/use-step.sh <chapter> <step> [--no-default]
#   e.g. tools/use-step.sh 04 01      (or the full name: 01-generate)
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

chapter="chapter$1"
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
    rsync -a --delete "$REPO/$theme_dir" "$site_dir/web/themes/custom/$theme/"
    (
      cd "$site_dir"
      ddev drush theme:install -y "$theme"
      if [[ $set_default == 1 ]]; then
        ddev drush config:set -y system.theme default "$theme"
      fi
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

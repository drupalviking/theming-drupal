#!/usr/bin/env bash
# Build the book's two test sites from the site-building book's export.
#
# Usage: tools/setup.sh [core|cms]     (both when no argument)
#
#   Book 1's sites/core/  ->  ~/websites/open-university/lens-and-trail-theme
#   Book 1's sites/cms/   ->  ~/websites/open-university/lens-and-trail-cms-theme
#
# Book 1 (../developing-open-sourced-web-software-with-drupal) exports its
# finished sites with its tools/export-sites.sh: the codebase (Composer files
# and patches), the configuration, and the content as a recipe. This script
# rebuilds a site from that export:
#
#   1. a new DDEV project with the codebase, and `composer install`;
#   2. `drush site:install --existing-config`: Drupal installed with Book 1's
#      configuration (the same site UUID, modules, content types, views...);
#   3. `drush recipe`: Book 1's content (hikes, albums, photos, menus...),
#      then Book 1's after-import.php for what an import can't bring;
#   4. a DDEV snapshot called `start`: the site as Book 1 leaves it.
#
# Which version of Book 1: data/book1.yml pins a commit of Book 1's repository,
# so a change in Book 1 never changes this book's sites by surprise. To move to
# a newer Book 1, change the commit there, delete the sites and run this again.
# BOOK1_REF=WORKTREE uses Book 1's working copy instead (for testing an export
# before it's committed).
#
# An existing site is left alone: delete it first to rebuild it
# (ddev delete -O <name>, then remove the folder). Log in with admin / 12345.
set -euo pipefail

cd "$(dirname "$0")/.."
REPO=$(pwd)
BASE=${BASE:-$HOME/websites/open-university}
BOOK1=${BOOK1:-$BASE/developing-open-sourced-web-software-with-drupal}
if [[ -z "${BOOK1_REF:-}" ]]; then
  # The value of "ref:", without quotes or a trailing comment.
  BOOK1_REF=$(sed -n 's/^ref: *//p' data/book1.yml | sed 's/ *#.*//' | tr -d "'\"")
fi

build_site() {
  local name=$1 project=$2
  local dir=$BASE/$project
  if [[ -d "$dir" ]]; then
    echo "$project already exists; leaving it as it is"
    return
  fi

  # Book 1's export at the pinned commit (or its working copy).
  local export
  export=$(mktemp -d)
  if [[ "$BOOK1_REF" == WORKTREE ]]; then
    cp -R "$BOOK1/sites/$name" "$export/"
  else
    git -C "$BOOK1" archive "$BOOK1_REF" "sites/$name" | tar -x -C "$export" --strip-components=1
  fi
  local from=$export/$name
  if [[ ! -f "$from/config/core.extension.yml" ]]; then
    echo "No export of the $name site in Book 1 at $BOOK1_REF" >&2
    exit 1
  fi

  echo "== $project from Book 1's sites/$name ($BOOK1_REF)"
  mkdir -p "$dir"
  cp -R "$from/codebase/." "$dir/"
  mkdir -p "$dir/config"
  cp -R "$from/config" "$dir/config/sync"
  # The rest of the export (the content recipe, after-import.php and its
  # data) stays together, next to the site.
  mkdir -p "$dir/book1-export"
  cp -R "$from/content" "$dir/book1-export/"
  cp "$from"/*.php "$from"/*.json "$dir/book1-export/" 2>/dev/null || true
  # Files the configuration points to (a theme's logo), into the public files.
  if [[ -d "$from/files" ]]; then
    mkdir -p "$dir/web/sites/default/files"
    cp -R "$from/files/." "$dir/web/sites/default/files/"
  fi
  rm -rf "$export"

  cd "$dir"
  # Book 1's DDEV settings (PHP version, database...), under this project's name.
  sed -i '' "s/^name: .*/name: $project/" .ddev/config.yaml
  ddev start
  ddev composer install --no-interaction

  # settings.php (DDEV makes one on start, including its settings.ddev.php),
  # with the sync folder outside the web root, as Book 1's Chapter 21 sets it.
  chmod u+w web/sites/default web/sites/default/settings.php
  if ! grep -q "config/sync" web/sites/default/settings.php; then
    cat >> web/sites/default/settings.php <<'SETTINGS'

// Book 1's configuration, installed with drush site:install --existing-config.
$settings['config_sync_directory'] = '../config/sync';
SETTINGS
  fi

  # Drupal can't install a profile from configuration when the profile has a
  # hook_install(), and Standard has one (it sets the front page, gives user 1
  # the administrator role...). Book 1's configuration already has all of that,
  # so the rebuild installs it with Minimal, which has none. The only visible
  # difference: the Status report says "Minimal" as the install profile.
  if grep -q "^profile: standard" config/sync/core.extension.yml; then
    sed -i '' 's/^profile: standard$/profile: minimal/; s/^  standard: 1000$/  minimal: 1000/' config/sync/core.extension.yml
  fi
  ddev drush site:install --existing-config -y \
    --account-name=admin --account-pass=12345
  # Standard's hook_install() gives user 1 the administrator role; Minimal's
  # doesn't, so do it here.
  ddev drush user:role:add administrator admin
  ddev drush recipe ../book1-export/content
  # What the import can't bring (Focal Point's crops), from Book 1's export.
  if [[ -f book1-export/after-import.php ]]; then
    ddev drush php:script book1-export/after-import.php -- /var/www/html/book1-export
  fi
  # Pathauto doesn't generate the aliases of translations that come in with an
  # import (the Spanish hikes and terms). Generating them again fills them in;
  # the English ones come out the same.
  if ddev drush pm:list --status=enabled --field=name 2>/dev/null | grep -qx pathauto; then
    ddev drush pathauto:aliases-generate update all
  fi
  ddev drush cache:rebuild
  ddev snapshot --name=start
  cd "$REPO"
  echo "Ready: https://$project.ddev.site (admin / 12345; DDEV snapshot 'start' is Book 1's final state)"
}

case "${1:-both}" in
  core) build_site core lens-and-trail-theme ;;
  cms) build_site cms lens-and-trail-cms-theme ;;
  both)
    build_site core lens-and-trail-theme
    build_site cms lens-and-trail-cms-theme
    ;;
  *) echo "Usage: tools/setup.sh [core|cms]" >&2; exit 1 ;;
esac

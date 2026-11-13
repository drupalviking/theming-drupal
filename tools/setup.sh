#!/usr/bin/env bash
# Build the book's two test sites from the site-building book's export.
#
# Usage: tools/setup.sh [core|cms]     (both when no argument)
#
# You don't need to have worked through Book 1: this script fetches it.
# The sites are made next to this book's folder:
#
#   Book 1's sites/core/  ->  ../lens-and-trail-theme      (Drupal core)
#   Book 1's sites/cms/   ->  ../lens-and-trail-cms-theme  (Drupal CMS)
#
# What you need: DDEV (with Docker or another container runtime it supports),
# Git, and an internet connection. Building both sites takes a while: Composer
# downloads Drupal and the modules twice.
#
# Book 1 (Developing Open Sourced Web Software with Drupal) exports its
# finished sites with its tools/export-sites.sh: the codebase (Composer files
# and patches), the configuration, and the content as a recipe. This script
# rebuilds a site from that export:
#
#   1. a new DDEV project with the codebase, and `composer install`;
#   2. `drush site:install --existing-config`: Drupal installed with Book 1's
#      configuration (the same site UUID, modules, content types, views...);
#   3. `drush recipe`: Book 1's content (hikes, albums, photos, menus...),
#      then Book 1's after-import.php for what an import can't bring;
#   4. on the Drupal CMS site only, Book 1's hikes and albums: Book 1 builds
#      them on the core site alone, and this book themes them on both.
#      tools/hikes-recipe.php makes a recipe from Book 1's core export (the Hike
#      and Gallery content types, the areas, the hikes, the albums and their
#      photos) that fits Drupal CMS;
#   5. a DDEV snapshot called `start`: the site as Book 1 leaves it (with the
#      hikes and albums on the Drupal CMS site).
#
# Which version of Book 1: data/book1.yml pins a commit of Book 1's repository,
# so a change in Book 1 never changes this book's sites by surprise. To move to
# a newer Book 1, change the commit there, delete the sites and run this again.
#
# Where Book 1 comes from: a copy of Book 1's repository next to this book's
# folder (../developing-open-sourced-web-software-with-drupal) if there is one;
# otherwise it's cloned from data/book1.yml's url: into that place.
#
# For the authors:
#   BOOK1_REF=WORKTREE  use Book 1's working copy, not the pinned commit
#                       (to test an export before it's committed)
#   BASE=/some/folder   make the sites somewhere else
#
# An existing site is left alone: delete it first to rebuild it
# (ddev delete -O <name>, then remove the folder). Log in with admin / 12345.
set -euo pipefail

cd "$(dirname "$0")/.."
REPO=$(pwd)
BASE=${BASE:-$(dirname "$REPO")}
BOOK1=${BOOK1:-$BASE/developing-open-sourced-web-software-with-drupal}

# A value from data/book1.yml, without quotes or a trailing comment.
book1_setting() {
  sed -n "s/^$1: *//p" data/book1.yml | sed 's/ *#.*//' | tr -d "'\""
}
BOOK1_REF=${BOOK1_REF:-$(book1_setting ref)}

for tool in ddev git; do
  if ! command -v "$tool" >/dev/null; then
    echo "This script needs $tool. See Book 1's Chapter 4, or https://ddev.com." >&2
    exit 1
  fi
done

# Book 1's repository: use a copy that's there, or clone it. A copy that
# doesn't have the pinned commit yet is fetched.
if [[ ! -d "$BOOK1/.git" ]]; then
  echo "== Cloning Book 1 into $BOOK1"
  git clone "$(book1_setting url)" "$BOOK1"
fi
if [[ "$BOOK1_REF" != WORKTREE ]] && ! git -C "$BOOK1" cat-file -e "$BOOK1_REF^{commit}" 2>/dev/null; then
  git -C "$BOOK1" fetch --all --quiet
  if ! git -C "$BOOK1" cat-file -e "$BOOK1_REF^{commit}" 2>/dev/null; then
    echo "Book 1's repository ($BOOK1) has no commit $BOOK1_REF, the one data/book1.yml asks for." >&2
    echo "If you're an author: has it been pushed? Otherwise, please report it." >&2
    exit 1
  fi
fi

# sed -i works differently on macOS and Linux; this works on both.
replace_in() {
  local file=$1 expression=$2
  sed "$expression" "$file" > "$file.tmp" && mv "$file.tmp" "$file"
}

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
  replace_in .ddev/config.yaml "s/^name: .*/name: $project/"
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
    replace_in config/sync/core.extension.yml 's/^profile: standard$/profile: minimal/; s/^  standard: 1000$/  minimal: 1000/'
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
  # Book 1 builds the hikes and albums only on the core site, and this book
  # themes them on both: the Drupal CMS site gets them from Book 1's core export, through a
  # recipe that tools/hikes-recipe.php makes to fit Drupal CMS, with the lists
  # (views), the About page and their menu links. A recipe can't bring config
  # translations, so tools/import-translations.php saves the Spanish ones.
  if [[ "$name" == cms ]]; then
    echo "== Book 1's hikes and albums on the Drupal CMS site"
    if [[ "$BOOK1_REF" == WORKTREE ]]; then
      mkdir -p book1-export/core
      cp -R "$BOOK1/sites/core/config" "$BOOK1/sites/core/content" book1-export/core/
    else
      git -C "$BOOK1" archive "$BOOK1_REF" sites/core/config sites/core/content | tar -x -C book1-export --strip-components=1
    fi
    cp "$REPO/tools/hikes-recipe.php" "$REPO/tools/import-translations.php" book1-export/
    ddev drush php:script book1-export/hikes-recipe.php -- /var/www/html/book1-export/core /var/www/html/book1-export/hikes
    ddev drush recipe ../book1-export/hikes
    ddev drush php:script book1-export/import-translations.php -- /var/www/html/book1-export/hikes/translations
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
  echo "Ready: $(ddev describe "$project" -j | sed -n 's/.*"primary_url": *"\([^"]*\)".*/\1/p' | head -1) (admin / 12345; DDEV snapshot 'start' is Book 1's final state, with Book 1's hikes and albums on the Drupal CMS site)"
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

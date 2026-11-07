#!/usr/bin/env bash
# Make the book's two test sites from the site-building book's final sites.
#
# Usage: tools/setup.sh [core|cms]     (both when no argument)
#
#   ~/websites/open-university/lens-and-trail      -> lens-and-trail-theme
#   ~/websites/open-university/lens-and-trail-cms  -> lens-and-trail-cms-theme
#
# The copies are separate DDEV projects, so this book never changes the sites
# the first book's screenshots are taken from (and the other way round).
# The site-building book's sites must be in their final state (Chapter 22)
# and running. An existing copy is left alone: delete it first to remake it
# (ddev delete -O <name>, then remove the folder).
#
# Same approach as the site-building book's lens-and-trail-live (Chapter 21):
# copy the files, give DDEV a new project name, import the database.
set -euo pipefail

BASE=${BASE:-$HOME/websites/open-university}

copy_site() {
  local from=$1 to=$2
  if [[ -d "$BASE/$to" ]]; then
    echo "$to already exists; leaving it as it is"
    return
  fi
  if [[ ! -d "$BASE/$from/.ddev" ]]; then
    echo "No DDEV project at $BASE/$from" >&2
    exit 1
  fi
  local dump
  dump=$(mktemp -t "$from.XXXXXX").sql.gz
  (cd "$BASE/$from" && ddev export-db --file="$dump")
  # Not the DDEV project's generated files or its snapshots: those belong to
  # the original project.
  rsync -a --exclude .ddev/db_snapshots --exclude .ddev/.ddev-docker-compose-full.yaml \
    "$BASE/$from/" "$BASE/$to/"
  (
    cd "$BASE/$to"
    ddev config --project-name="$to"
    ddev start
    ddev import-db --file="$dump"
    ddev drush cache:rebuild
    ddev snapshot --name=start
  )
  rm -f "$dump"
  echo "Ready: https://$to.ddev.site (DDEV snapshot 'start' is the first book's final state)"
}

case "${1:-both}" in
  core) copy_site lens-and-trail lens-and-trail-theme ;;
  cms) copy_site lens-and-trail-cms lens-and-trail-cms-theme ;;
  both)
    copy_site lens-and-trail lens-and-trail-theme
    copy_site lens-and-trail-cms lens-and-trail-cms-theme
    ;;
  *) echo "Usage: tools/setup.sh [core|cms]" >&2; exit 1 ;;
esac

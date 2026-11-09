#!/usr/bin/env bash
# Chapter 8's cleanup: remove the templates and CSS files the club's theme no
# longer needs. Run it on a theme that's at the end of Chapter 7.
#
# Usage: cleanup.sh web/themes/custom/lens_trail
set -euo pipefail

cd "$1"
test -f lens_trail.info.yml || { echo "No lens_trail.info.yml in $1" >&2; exit 1; }

rm -f \
  css/components/action-links.css \
  css/components/breadcrumb.css \
  css/components/button.css \
  css/components/field.css \
  css/components/icons.css \
  css/components/indented.css \
  css/components/item-list.css \
  css/components/link.css \
  css/components/links.css \
  css/components/menu.css \
  css/components/messages.css \
  css/components/more-link.css \
  css/components/node.css \
  css/components/pager.css \
  css/components/tabs.css \
  css/components/user.css \
  templates/block/block--local-actions-block.html.twig \
  templates/block/block--local-tasks-block.html.twig \
  templates/block/block--system-branding-block.html.twig \
  templates/block/block--system-menu-block.html.twig \
  templates/block/block.html.twig \
  templates/content-edit/filter-caption.html.twig \
  templates/content-edit/filter-tips.html.twig \
  templates/content-edit/node-add-list.html.twig \
  templates/content/comment.html.twig \
  templates/content/links--node.html.twig \
  templates/content/mark.html.twig \
  templates/content/media.html.twig \
  templates/content/node.html.twig \
  templates/content/page-title.html.twig \
  templates/content/taxonomy-term.html.twig \
  templates/dataset/item-list.html.twig \
  templates/field/field--comment.html.twig \
  templates/field/field--node--created.html.twig \
  templates/field/field--node--title.html.twig \
  templates/field/field--node--uid.html.twig \
  templates/field/field--text-long.html.twig \
  templates/field/field--text-with-summary.html.twig \
  templates/field/field--text.html.twig \
  templates/field/field.html.twig \
  templates/field/file-audio.html.twig \
  templates/field/file-link.html.twig \
  templates/field/file-video.html.twig \
  templates/field/image.html.twig \
  templates/field/link-formatter-link-separate.html.twig \
  templates/field/time.html.twig \
  templates/misc/status-messages.html.twig \
  templates/navigation/breadcrumb.html.twig \
  templates/navigation/menu-local-task.html.twig \
  templates/navigation/menu-local-tasks.html.twig \
  templates/navigation/menu.html.twig \
  templates/user/user.html.twig \
  templates/user/username.html.twig \
  templates/views/views-mini-pager.html.twig \
  templates/views/views-view-grouping.html.twig \
  templates/views/views-view-summary-unformatted.html.twig \
  templates/views/views-view-summary.html.twig \
  templates/views/views-view.html.twig

# Folders the removals left empty.
find templates -type d -empty -delete

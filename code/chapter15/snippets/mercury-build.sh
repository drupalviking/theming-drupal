# tools/use-step.sh replaces the theme's folder, node_modules included, so
# install the packages again before the build.
ddev exec -d /var/www/html/web/themes/custom/mercury npm ci
ddev exec -d /var/www/html/web/themes/custom/mercury npm run build

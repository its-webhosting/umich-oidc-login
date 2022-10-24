
# Building UMich OIDC Login

```bash
composer global require wp-coding-standards/wpcs
composer global require --dev humbug/php-scoper
export PATH=${PATH}:${HOME}/.config/composer/vendor/bin

git clone git@github.com:its-webhosting/umich-oidc-login.git
cd umich-oidc-login
composer install

php-scoper add-prefix
sed -i 's/\\UMich_OIDC\\Vendor\\WP_/\\WP_/' build/vendor/wp-user-manager/wp-optionskit/includes/class-wpok-rest-server.php
composer dump-autoload --working-dir build
```


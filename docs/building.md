
# Building the UMich OIDC Login plugin from source

To build the UMich OIDC Login plugin, you need
* bash
* git
* Docker

The UMich OIDC Login plugin should be built using PHP 7.3, so it can be used with both PHP 7.3 and 8.x.

## Get the plugin source code

```bash
git clone git@github.com:its-webhosting/umich-oidc-login.git
cd umich-oidc-login
export PATH="$(pwd)/tools:${PATH}"
```

## Use Composer to install PHP dependencies

### Build recent Composer with PHP 7.3

The official Docker image for Composer supports only PHP 8.  Build our own image that uses PHP 7.3 so that plugin can be used with both PHP 7.3 and PHP 8.x.

```bash
pushd scratch
git clone https://github.com/composer/docker.git composer-docker
cd composer-docker/latest
sed -i .old 's/^FROM php:8-alpine/FROM php:7.3-alpine/' Dockerfile
docker build -t composer:php7.3 .
popd
```

### Build Composer packages

Composer installs everything into `vendor` and then PHP-Scoper copies it into `build`.

We are using PHP-Scoper so we can put UMICH OIDC Login plugin dependencies into their own PHP namespace to avoid conflicts with other plugins' dependencies.  PHP 7.3 requires PHP-Scoper 0.15.0.

If PHP-Scoper says the build directory exists and asks if you want to proceed, answer "no".  This will continue, using the existing directory.  Responding "yes" will re-create the directory, destroying any Gutenberg/React code that has been built there.

```bash
pushd umich-oidc-login

rm -rf vendor build

run-composer global require --dev humbug/php-scoper:0.15.0
run-composer install
run-composer global exec -- php-scoper add-prefix
run-composer dump-autoload --working-dir build
```

## Build Gutenberg and React code

```bash
rm -rf node_modules includes/admin/wp-react-optionskit/node_modules

run-node npm install --include dev  # ensure @wordpress/scripts devDependency gets installed
run-node npm run-script build:metabox

cd includes/admin/wp-react-optionskit/
run-node npm install --include dev  # ensure @wordpress/scripts devDependency gets installed
run-node npm run-script build
popd
```

Stop here, you're done.

## Update plugin dependencies

When you need to update the plugin dependencies (for example, when releasing a new version of the plugin), follow the steps below.  The steps will first remove old dependency artifacts, then update the config files, and finally rebuild the plugin.  Avoid running the composer or npm `update` command as that won't get everything right.

### Update Composer-managed dependencies

```bash
rm -rf scratch/composer-*-cache
cd umich-oidc-login
rm -rf composer.lock build vendor
cd ..
```

Edit `composer.json`, manually look up the newest version of each package, check the changelog for each package, and edit the file to have the desired version.

NOTE: specifying `"paragonie/constant_time_encoding": "^2.7.0",` forces PHP 7.3 compatibility for `phpseclib`. This dependency should be removed when we drop support for PHP 7.x.  As of June 2025, 2.7 was the latest minor release for 2.x.

### Update NodeJS dependencies

```bash
cd umich-oidc-login
rm -rf build node_modules package-lock.json
```

Edit `../tools/run-node` and update the image version to be the major version number listed in `package.json` in the branch for the latest release of [wordpress/wordpress-develop](https://github.com/WordPress/wordpress-develop/tree/trunk).

Edit `package.json`:
* For all `@wordpress/*` packages, change the version of the package to the _exact_ version (no caret) listed in `package.json` in the branch for the latest release of [wordpress/wordpress-develop](https://github.com/WordPress/wordpress-develop/tree/trunk).
* For all other packages, manually look up the newest version of each package, check the changelog for each package, and edit the file to have the desired version, usually using the caret notation.

```bash
pushd includes/admin/wp-react-optionskit
rm -rf build node_modules package-lock.json
```

Edit `package.json`:
* For all `@wordpress/*` packages, change the version of the package to the _exact_ version (no caret) listed in the [Changelog for the latest release of WordPress](https://wordpress.org/documentation/article/wordpress-versions/).
* For all other packages, manually look up the newest version of each package, check the changelog for each package, and edit the file to have the desired version, usually using the caret notation.


### Now rebuild everything

Follow the instructions above to
* [build Composer packages](#build-composer-packages)
* [build React packages](#build-gutenberg-and-react-code)

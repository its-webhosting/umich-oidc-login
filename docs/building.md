
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
cd composer-docker/2.5
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
run-composer global require --dev humbug/php-scoper:0.15.0
run-composer install
run-composer global exec -- php-scoper add-prefix
run-composer dump-autoload --working-dir build
```

## Build Gutenberg and React code

```bash
run-node npm install --production=false  # ensure @wordpress/scripts devDependnecy gets installed
run-node npm run-script build:metabox

cd includes/admin/wp-react-optionskit/
run-node npm install --production=false  # ensure @wordpress/scripts devDependnecy gets installed
run-node npm run-script build
popd
```

Stop here, you're done.

## Update plugin dependencies

When you need to update the plugin dependencies (for example, when releasing a new version of the plugin), follow the steps below.

### Update Composer-managed dependencies

```bash
rm -rf scratch/composer-*-cache
cd umich-oidc-login
rm -rf composer.lock build vendor
run-composer update
cd ..
```

### Update NodeJS dependencies

```bash
cd umich-oidc-login
rm -rf node_modules package-lock.json
run-node npm update
```

You may want to edit `package.json` and fix package version numbers more aggressively, by hand.  If you do, then re-run the commands above to get a new `package-lock.json`.

```bash
pushd includes/admin/wp-react-optionskit
rm -rf build node_modules package-lock.json
run-node npm update
```

You may want to edit `package.json` and fix package version numbers more aggressively, by hand.  If you do, then re-run the commands above to get a new `package-lock.json`.


### Rebuild everything

Follow the instructions above to
* [build Composer packages](#build-composer-packages)
* [build React packages](#build-gutenberg-and-react-code)

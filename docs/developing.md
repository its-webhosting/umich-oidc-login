# Developing the UMich OIDC Login plugin

How to set up a local development environment to run and modify the plugin.

The development environment is set up at https://wp.internal/

Unless specified, all commands below should be run from the top-level directory of the plugin source code repository.

## Requirements

* git
* bash
* Docker and Docker Compose
* [mkcert](https://github.com/FiloSottile/mkcert) (see below)

## Get the plugin source code

```bash
git clone git@github.com:its-webhosting/umich-oidc-login.git
cd umich-oidc-login
export PATH="$(pwd)/tools:${PATH}"
```

## Create TLS/SSL certificate

Although it should be possible to use HTTP in most cases, we do the extra work for HTTPS in order to avoid special cases with third party systems and OIDC Identity Providers.

If you are using a Mac with Homebrew, install `mkcert` by running

```bash
brew install mkcert nss
```

Otherwise, refer to the [mkcert documentation](https://github.com/FiloSottile/mkcert) for how to install `mkcert` on your platform.

```bash
rm -rf scratch/[a-z]*
mkdir scratch/certs
pushd scratch/certs
mkcert -cert-file wp.internal.crt -key-file wp.internal.key wp.internal
mkcert -install
popd
```

Add `wp.internal` to your hosts file so the name can be used in URLs:

```bash
echo "127.0.0.1 wp.internal" | sudo tee -a /etc/hosts
```

## Install WordPress

```bash
# clean up to ensure we get fresh builds of everything
docker compose down --volumes
docker image prune --all
docker volume prune --all

echo "DB_NAME='wordpress'" > .env
echo "DB_ROOT_PASSWORD='$(openssl rand -base64 24 | cut -c 1-32)'" >> .env

docker compose up

# Important: If you leave the username as "admin", choose an email
# address that is not also used for SSO for a different username.
run-wp core install \
    --url=https://wp.internal \
    --title="Internal" \
    --skip-email \
    --admin_user=admin \
    --admin_email="YOU@example.com"  # replace with your email address
```

_COPY AND SAVE THE ADMIN PASSWORD_ from the output of the `wp core install` command above, you will need to use it often.

Turn on debug logging.

```bash
run-wp config set WP_DEBUG true --raw
run-wp config set WP_DEBUG_DISPLAY true --raw
run-wp config set SCRIPT_DEBUG true --raw
run-wp config set WP_DEBUG_LOG /tmp/wp-debug.log
```

Other configuration:

```bash
run-wp rewrite structure '/%postname%/'
run-wp plugin delete akismet hello
run-wp plugin install wp-native-php-sessions --activate
```

Make sure everything works by visiting https://wp.internal/

And log in at https://wp.internal/wp-admin/
* Make sure everything is OK in the admin dashbaord.
* Update core, plugins, and themes to latest.

## UMICH OIDC Login plugin

BUILD THE PLUGIN BY FOLLOWING THE INSTRUCTIONS IN [docs/building.md](building.md).

Then,

```bash
run-wp plugin activate umich-oidc-login
```

Watch for errors using

```bash
tail -f scratch/logs/wordpress/wp-debug.log
```

## Other tasks

### Check for errors, warning and coding style issues

```bash
check-code
```

If you get the following error
```
Error while loading rules from rules directory - ENOENT: no such file or directory, scandir '/home/node/app/node_modules/npm-package-json-lint/src/rules'
```
then run
```bash
pushd node_modules/npm-package-json-lint/src
cp -a rules rules-dir
sed -i -e "s/'rules'/'rules-dir'/" Rules.js
popd
```

### Develop the plugin settings page

This currently uses Node.js >= 20 installed on the local system, not from a Docker image.

```bash
TOP=$(pwd)
cd umich-oidc-login/includes/admin/wp-react-optionskit
# --hot=true must be last command line option
env WDS_SOCKET_PORT=0 npx wp-scripts start \
    --host="wp.internal" --server-type=https \
    --server-options-cert="${TOP}/scratch/certs/wp.internal.crt" \
    --server-options-key="${TOP}/scratch/certs/wp.internal.key" \
    --server-options-ca="$(mkcert -CAROOT)/rootCA.pem"\
    --live-reload --hot=true
```

### Clean up and start over

Delete the WordPress database and all WordPress files.

```bash
docker-compose down --volumes  # deletes the DB data volume
rm -rf scratch/wordpress scratch/composer-php* scratch/logs
```

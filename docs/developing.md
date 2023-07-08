# Developing the UMich OIDC Login plugin

How to set up a local development environment to run and modify the plugin.

The development environment is set up at https://wp.local/

Unless specified, all commands below should be run from the top-level directory of the plugin source code repostory.

## Requirements

* git
* bash
* Docker and Docker Compose
* [mkcert](https://github.com/FiloSottile/mkcert) (see below)

## Create TLS/SSL certificate

Although it should be possible to use HTTP in most cases, we do the extra work for HTTPS in order to avoid special cases with third party systems and OIDC Identity Providers.

If you are using a Mac with Homebrew, install `mkcert` by running

```bash
brew install mkcert nss
```

Otherwise, refer to the [mkcert documentation](https://github.com/FiloSottile/mkcert) for how to install `mkcert` on your platform.

```bash
mkdir scratch/certs
pushd scratch/certs
mkcert -cert-file wp.local.crt -key-file wp.local.key wp.local
mkcert -install
popd
```

Add `wp.local` to your hosts file so the name can be used in URLs:

```bash
echo "127.0.0.1 wp.local" | sudo tee -a /etc/hosts
```

## Install WordPress

```bash
echo "DB_NAME='wordpress'" > .env
echo "DB_ROOT_PASSWORD='$(openssl rand -base64 24 | cut -c 1-32)'" >> .env
docker-compose up

run-wp core install \
    --url=https://wp.local \
    --title="Local WordPress Test" \
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

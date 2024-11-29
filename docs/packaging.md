# Packaging and publishing the UMich OIDC Login plugin

## Update plugin dependencies

Perform the steps in the ["Update plugin dependencies" section of the build instructions.](building.md#update-plugin-dependencies)

This is important to address any security issues as well as to prevent getting so far out of date that it becomes very difficult to update.

## Increment version numbers

The version numbers should be incremented (as appropriate) before the plugin is rebuilt.

### composer.json

Edit `umich-oidc-login/composer.json`

* If code was changed or dependencies were updated, update the version number in `composer.json`.

### Settings page React app (wp-react-optionskit)

Edit `umich-oidc-login/includes/admin/wp-react-optionskit/package.json`

* Update dependencies to newer versions, if needed.
* If code was changed or dependencies were updated, update the version number in `package.json`.

### metabox React app

Edit `umich-oidc-login/package.json`

* Update dependencies to newer versions, if needed.
* Update the version number in `package.json` to be the new plugin version number.

### Main plugin file

Edit `umich-oidc-login/umich-oidc-login.php`

* In the plugin header comment at the top of the file:
  * Update `Version:` to be the new plugin version number.
  * Update `Tested up to:` to the version of WordPress used for development and testing.
* Update define for UMICH_OIDC_LOGIN_VERSION to the new plugin version number.
* Update define for UMICH_OIDC_LOGIN_VERSION_INT to the integer representation of the new plugin version number.
  * For alpha and beta releases, subtract 100 and then use the last two digits as a serial number for alpha/beta releases.  For example, version 1.2.0-alpha1 is written `1019900` (1.1.99.00).


### README.txt

Update `umich-oidc-login/README.txt` (note: this is the `.txt` file in the subdirectory, _not_ the `.md` file in the repository's main directory).

* In the plugin header:
	* Update `Tested up to:` to the version of WordPress used for development and testing.
  * Update `Stable tag:` to new plugin version number.
* Update the feature list, if needed.
* Update the screenshot list, if needed.
	* Put new or updated screenshots in the wordpress.org SVN repo `assets` directory.
* Add a changelog entry for the new plugin version.
* Add an upgrade notice for the new plug version:  Why a user should upgrade.  No more than 300 characters.

Check the `README.txt` file for problems using both
* https://wpreadme.com
* https://wordpress.org/plugins/developers/readme-validator/


## Rebuild the plugin

Check for errors, warning and coding style issues.  Fix anything that is found.

```bash
check-code
```

Remove any previous build

```bash
cd umich-oidc-login
rm -rf build node_modules vendor \
    includes/admin/wp-react-optionskit/build \
    includes/admin/wp-react-optionskit/node_modules \
    includes/admin/wp-react-optionskit/vendor
```

Build the plugin according to the [build instructions](building.md).

Test the plugin locally to be sure everything works.

## Update upstream git repository

```bash
git diff
git status
git add --all  # change to be any files modified above
git commit -m "version X.Y.Z-a"  # change this to the new plugin version number
git push origin
```

## Create zip file for the new release

Copy the plugin files (`umich-oidc-login` subdirectory) to a release directory _outside the git repo_.  This will allow us to modify the files without affecting git.

```bash
TOP=$(pwd)
cd /path/to/your/releases/directory

rm -rf old-release
mkdir old-release
mv umich-oidc-login* old-release

cp -a "${TOP}/umich-oidc-login" .

# Remove files that should not be in the released plugin:
rm -rf umich-oidc-login/vendor  # un-namespaced composer packages
rm umich-oidc-login/scoper.inc.php  # don't want this being run by a web server; only needed when building
rm -rf umich-oidc-login/node_modules  # only needed when building
rm -rf umich-oidc-login/includes/admin/wp-react-optionskit/node_modules  # only needed when building
```

We're not deleting `composer.json`, `composer.lock`, `package.json` and `package-lock.json` in order to be clear about what code we're shipping.

Compare the files in the old and new release.  Look for things that got added that should not be shipped.

```bash
( cd old-release/umich-oidc-login ; find . -print ) | sort > old-release/file_list
( cd umich-oidc-login ; find . -print ) | sort > file_list
diff -u old-release/file_list file_list
```

Create the zip file:

```bash
zip -r umich-oidc-login.zip umich-oidc-login
```

Test the zip file on one or more other WordPress websites.

## Publish the release on GitHub

Go to
https://github.com/its-webhosting/umich-oidc-login/releases

* Click "Draft a new release" button
* Choose a tag -> Create new tag on publish: v1.2.0
* Release title: 1.2.0
* For the release notes:

```markdown
### Release notes
* (paste the bullet points from the new Changelog entry from `README.txt`)
```

* Binaries: upload umich-oidc-login.zip that was prepared for wordpress.org
* Publish release

## Publish the release in the WordPress plugin directory

* SVN Resources:
  * https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
  * Book: https://svnbook.red-bean.com/en/1.7/index.html
* SVN URL: https://plugins.svn.wordpress.org/umich-oidc-login
* Public URL for the plugin in the WordPress plugin directory: https://wordpress.org/plugins/umich-oidc-login

Keep the SVN repo directory _outside the git repo_.

The commands below copy files from `/path/to/your/releases/directory` which was used to create the zip file above.

```bash
cd /path/to/your/svn/directory

# When checking out the SVN repo for the first time:
svn list https://plugins.svn.wordpress.org/umich-oidc-login
svn co --username umichitswebhosting https://plugins.svn.wordpress.org/umich-oidc-login

# To update an existing copy of the repo with a new release:
cd umich-oidc-login  # this is the SVN subdirectory
svn up
svn stat
# show what will be changed -- make sure this is correct:
rsync -a -v -v --delete --dry-run \
    /path/to/your/releases/directory/umich-oidc-login/ trunk \
    2>&1 | grep -v uptodate
rsync -a -v --delete \
    /path/to/your/releases/directory/umich-oidc-login/ trunk
svn stat

# delete files that are no longer in the new release:
svn stat | perl -n -e 'print "$1\n" if /^!\s+(.+)/;'
svn stat | perl -n -e 'print "$1\n" if /^!\s+(.+)/;' | xargs svn delete
# add new files:
svn stat | perl -n -e 'print "$1\n" if /^\?\s+(.+)/;'
svn stat | perl -n -e 'print "$1\n" if /^\?\s+(.+)/;' | xargs svn add

# make sure everything looks good:
svn stat

svn ci -m "Updating trunk to version X.Y.Z"  # CHANGE version number!

# Tag the new release that you just added above:
svn cp trunk tags/X.Y.X  # CHANGE version number!
svn ci -m "Creating tag X.Y.Z"  # CHANGE version number!
```

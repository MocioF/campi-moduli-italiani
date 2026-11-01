#!/usr/bin/env bash

if [ $# -lt 1 ]; then
  echo "Run utilities"
  echo "----------------------"
	echo "  usage: $0 <command> [options]"
  echo "    <command> can be any of: up, down, console, setup, wordpress, deps, phpunit, test, format, bundle, svnsync"
  echo " .  [options]: --verbose"
	exit 1
fi

COMMAND=$1
VERBOSE=$2

PLUGIN_PATH="/var/www/html/wp-content/plugins/campi-moduli-italiani"

compose_exec() {
  declare command=$1

  docker-compose exec -T wordpress /bin/bash -l -c "$command"
}

plugin_cmd() {
  declare command=$1

  echo "cd $PLUGIN_PATH && $command"
}

setup() {
  wordpress
  deps
  phpunit
}

up() {
  docker-compose up -d
}

down() {
  docker-compose down
}

console() {
  docker-compose exec wordpress /bin/bash
}

wordpress() {
  CMD='wp core install --url=https://localhost --title="Campi Moduli Italiani" --admin_user=admin --admin_password=admin --admin_email=info@campi-moduli-italiani.it --allow-root &&
       wp plugin update --all --allow-root'
  compose_exec "$CMD"
}

deps() {
  CMD=$(plugin_cmd 'rm -rf vendor && composer install')
  compose_exec "$CMD"
}

phpunit() {
  CMD=$(plugin_cmd './bin/install-wp-tests.sh $WORDPRESS_DB_NAME $WORDPRESS_DB_USER $WORDPRESS_DB_PASSWORD $WORDPRESS_DB_HOST latest true')
  compose_exec "$CMD"
}

test() {
  CMD=$(plugin_cmd 'vendor/bin/phpunit --debug')
  compose_exec "$CMD"
}

format() {
  CMD=$(plugin_cmd 'vendor/bin/phpcbf')
  compose_exec "$CMD"
}

DIST_PATH="dist"
SVN_URL="${SVN_URL:-https://plugins.svn.wordpress.org/campi-moduli-italiani}"
SVN_PATH="$DIST_PATH/svn"
OUTPUT_PATH="$DIST_PATH/source"

create_dist() {
  rm -rf $DIST_PATH
  mkdir -p $SVN_PATH $OUTPUT_PATH
  cp -Rp admin img includes integrations languages modules campi-moduli-italiani.php settings.php index.php license.txt readme.txt readme-it.txt $OUTPUT_PATH
}

bundle() {
  create_dist
  if [[ -z "$ZIP_NAME" ]]; then
    OUTPUT_ZIP=campi-moduli-italiani.zip
  else
    OUTPUT_ZIP=$ZIP_NAME
  fi
  # Lo zip contiene la cartella campi-moduli-italiani/: caricato da "Aggiungi plugin",
  # WordPress lo installa nella cartella giusta qualunque sia il nome del file zip.
  local ZIP_ROOT="$DIST_PATH/zip"
  rm -rf "$ZIP_ROOT"
  mkdir -p "$ZIP_ROOT/campi-moduli-italiani"
  cp -Rp "$OUTPUT_PATH/." "$ZIP_ROOT/campi-moduli-italiani/"
  echo "Zip: dist/$OUTPUT_ZIP"
  (cd "$ZIP_ROOT" && zip -q -r "../$OUTPUT_ZIP" campi-moduli-italiani)
  rm -rf "$ZIP_ROOT"
}

# Versione dichiarata in un file: riga "Etichetta: valore", spazi e \r esclusi.
read_header() {
  declare file=$1 label=$2
  grep -m1 -E "^[[:space:]*]*${label}:" "$file" | sed -E "s/^[[:space:]*]*${label}:[[:space:]]*//" | tr -d '\r[:space:]'
}

svnsync() {
  create_dist
  svn co -q "$SVN_URL" $SVN_PATH

  # Solo i file di assets/ (banner, icone, screenshot): assets/data contiene dati di sviluppo.
  find assets -maxdepth 1 -type f -exec cp {} "$SVN_PATH/assets/" \;

  # Il trunk diventa una copia esatta del pacchetto: --delete toglie i file eliminati dal
  # repository (prima restavano nel trunk e finivano anche nei tag).
  rsync -a --delete --exclude '.svn' "$OUTPUT_PATH/" "$SVN_PATH/trunk/"

  (
    cd $SVN_PATH || exit 1
    # Per SVN un file sparito dal disco è solo "mancante" (!): va eliminato con svn rm,
    # altrimenti il commit non registra la cancellazione.
    svn status trunk assets | grep '^!' | cut -c9- | while IFS= read -r missing; do
      svn rm -q --force "$missing" 2>/dev/null || true
    done
    svn add -q --force trunk assets
    svn diff && svn stat
  )
}

svnpush() {
  if [ ! -d "$SVN_PATH/tags" ]; then
    echo "You need to run svnsync first"
    exit 1
  fi

  if (cd $SVN_PATH && svn status | grep -e '^[?!]'); then
    echo "There are changes not added to the SVN, or missing files not removed (run svnsync again)"
    exit 1
  fi

  if [ -z "$SVN_USERNAME" ]; then
    echo "You need to provide the username as SVN_USERNAME=myname"
    exit 1
  fi

  if [ -z "$SVN_PASSWORD" ]; then
    echo "You need to provide the username as SVN_PASSWORD=mypassword"
    exit 1
  fi

  if [ ! -z "$SVN_TAG" ] && [ -d "$SVN_PATH/tags/$SVN_TAG" ]; then
    echo "The tag $SVN_TAG already exists"
    exit 1
  fi

  if [ ! -z "$SVN_TAG" ]; then
    # Il tag deve corrispondere alla versione dichiarata nei file che verranno pubblicati.
    local STABLE VERSION
    STABLE=$(read_header "$SVN_PATH/trunk/readme.txt" "Stable tag")
    VERSION=$(read_header "$SVN_PATH/trunk/campi-moduli-italiani.php" "Version")
    if [[ "$SVN_TAG" == *-* ]] || [[ "$VERSION" == *-* ]]; then
      echo "Development versions are not published: SVN_TAG=$SVN_TAG, Version=$VERSION"
      exit 1
    fi
    if [ "$STABLE" != "$SVN_TAG" ] || [ "$VERSION" != "$SVN_TAG" ]; then
      echo "Version mismatch: SVN_TAG=$SVN_TAG, Stable tag=$STABLE, Version=$VERSION"
      exit 1
    fi
    cd $SVN_PATH && svn cp trunk tags/$SVN_TAG && svn ci -m "Version $SVN_TAG" --username $SVN_USERNAME --password $SVN_PASSWORD
  else
    cd $SVN_PATH && svn ci -m "Sync trunk" --username $SVN_USERNAME --password $SVN_PASSWORD
  fi
}

#----------------------------------------------

case $COMMAND in
  "up" | "down" | "setup" | "wordpress" | "deps" | "phpunit" | "test" | "format" | "console" | "bundle" | "svnsync" | "svnpush")
    if [[ $VERBOSE == '--verbose' ]]; then
      set -ex
    else
      echo "Executing command $COMMAND..."
    fi

    eval $COMMAND
    ;;
  *)
    echo "Command not supported: $COMMAND"
    ;;
esac

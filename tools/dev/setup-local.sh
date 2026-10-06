#!/usr/bin/env bash
# Lokalny WordPress do testów i do budowania demo — bez Dockera i bez MySQL
# (PHP 8.1+ z pdo_sqlite, wtyczka SQLite Database Integration, serwer wbudowany w PHP).
#
# Użycie:
#   tools/dev/setup-local.sh <katalog roboczy> [<plik wordpress-playground.zip>]
#
# Z plikiem ZIP z WordPress Playground (wp-content z bazą .ht.sqlite i ilustracjami)
# serwis startuje z tymi samymi treściami co demo; bez niego — czysta instalacja,
# do której trzeba włączyć wtyczkę TSL Core (wczyta strukturę serwisu).
# Wynik: http://localhost:8090 (panel: /wp-admin).
set -euo pipefail
REPO="$(cd "$(dirname "$0")/../.." && pwd)"
DIR="${1:?Podaj katalog roboczy, np. ~/tsl-local}"
ZIP="${2:-}"
PORT="${PORT:-8090}"
mkdir -p "$DIR" && cd "$DIR"
[ -d wordpress ] || { curl -sSL https://wordpress.org/latest.tar.gz | tar xz; }
[ -f wp-cli.phar ] || curl -sSLO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
WPC="wordpress/wp-content"
if [ -n "$ZIP" ]; then
	rm -rf src && mkdir src && unzip -q "$ZIP" -d src
	cp -r src/wp-content/uploads "$WPC/" 2>/dev/null || true
	mkdir -p "$WPC/database" && cp src/wp-content/database/.ht.*/.ht.sqlite "$WPC/database/.ht.sqlite" 2>/dev/null \
		|| cp src/wp-content/database/.ht.sqlite "$WPC/database/.ht.sqlite"
fi
# Kod z repozytorium (dowiązania — zmiany w repo widać od razu).
ln -sfn "$REPO/wordpress/wp-content/themes/tsl-editorial" "$WPC/themes/tsl-editorial"
ln -sfn "$REPO/wordpress/wp-content/plugins/tsl-core" "$WPC/plugins/tsl-core"
# SQLite zamiast MySQL.
if [ ! -d "$WPC/plugins/sqlite-database-integration" ]; then
	curl -sSL -o sq.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.zip && unzip -q sq.zip -d "$WPC/plugins"
fi
sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$PWD/$WPC/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	"$WPC/plugins/sqlite-database-integration/db.copy" > "$WPC/db.php"
cat > wordpress/wp-config.php <<CFG
<?php
define('DB_NAME','wp'); define('DB_USER',''); define('DB_PASSWORD',''); define('DB_HOST',''); define('DB_CHARSET','utf8mb4'); define('DB_COLLATE','');
define('DB_DIR', __DIR__ . '/wp-content/database/'); define('DB_FILE', '.ht.sqlite');
foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as \$k) define(\$k, '$(head -c 24 /dev/urandom | base64)');
\$table_prefix = 'wp_';
define('WP_HOME','http://localhost:$PORT'); define('WP_SITEURL','http://localhost:$PORT');
define('WP_DEBUG', true); define('WP_DEBUG_DISPLAY', false); define('WP_DEBUG_LOG', true);
if ( ! defined('ABSPATH') ) define('ABSPATH', __DIR__ . '/');
require_once ABSPATH . 'wp-settings.php';
CFG
cat > router.php <<'PHP'
<?php
$root = __DIR__ . '/wordpress';
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($path !== '/' && is_file($root . $path)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
PHP
WP="php wp-cli.phar --path=wordpress"
[ "$(id -u)" = 0 ] && WP="$WP --allow-root"
if [ -z "$ZIP" ] && ! $WP core is-installed 2>/dev/null; then
	$WP core install --url="http://localhost:$PORT" --title="TSL Manager" --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
	$WP plugin activate tsl-core
fi
$WP language core install pl_PL --activate 2>/dev/null || true
$WP theme activate tsl-editorial
$WP rewrite flush 2>/dev/null || true
echo "Start serwera: cd $DIR && PHP_CLI_SERVER_WORKERS=4 php -S localhost:$PORT -t wordpress router.php"

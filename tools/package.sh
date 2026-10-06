#!/usr/bin/env bash
# Paczki ZIP do instalacji w WordPressie (Wygląd → Motywy → Dodaj / Wtyczki → Dodaj → Wyślij na serwer).
# Wynik: dist/tsl-editorial-<wersja>.zip, dist/tsl-core-<wersja>.zip
set -euo pipefail
cd "$(dirname "$0")/.."
WP=wordpress/wp-content
THEME_V=$(sed -n 's/^Version:[[:space:]]*//p' $WP/themes/tsl-editorial/style.css | tr -d '\r')
CORE_V=$(sed -n 's/^ \* Version:[[:space:]]*//p' $WP/plugins/tsl-core/tsl-core.php | tr -d '\r')
mkdir -p dist
rm -f "dist/tsl-editorial-$THEME_V.zip" "dist/tsl-core-$CORE_V.zip"
(cd $WP/themes && zip -qr -X "../../../dist/tsl-editorial-$THEME_V.zip" tsl-editorial -x '*.DS_Store')
(cd $WP/plugins && zip -qr -X "../../../dist/tsl-core-$CORE_V.zip" tsl-core -x '*.DS_Store')
ls -l dist/*.zip

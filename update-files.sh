#!/bin/bash
set -e

echo "🚀 Collecting updated files..."

MARKER=".last_update_check"
mkdir -p updates

[ ! -f "$MARKER" ] && touch "$MARKER"

find public -type f -newer "$MARKER" \
! -path "public/cache/*" \
! -path "public/log/*" \
! -path "public/uploads/*" \
! -path "public/lang/custom/*" \
| while read file; do

    target="updates/$file"
    mkdir -p "$(dirname "$target")"
    cp "$file" "$target"

    echo "✔ Copied: $file"

done

for file in VERSION README.md CHANGELOG.md tools/language-split-tool.php; do
    if [ -f "$file" ] && [ "$file" -newer "$MARKER" ]; then
        target="updates/$file"
        mkdir -p "$(dirname "$target")"
        cp "$file" "$target"

        echo "✔ Copied: $file"
    fi
done

touch "$MARKER"

echo "✅ Done collecting updates!"

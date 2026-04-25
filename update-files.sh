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
| while read file; do

    target="updates/$file"
    mkdir -p "$(dirname "$target")"
    cp "$file" "$target"

    echo "✔ Copied: $file"

done

touch "$MARKER"

echo "✅ Done collecting updates!"

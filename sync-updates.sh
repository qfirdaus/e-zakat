#!/bin/bash

# =========================
# CONFIG
# =========================
BASE_PATH="/mnt/d/www"
MASTER_PROJECT="iqs-framework"

PROJECTS=(
    "e-base"
    "e-dqms"
    "e-facility"
    "e-hepa"
    "e-pr"
)

LOG_FILE="sync.log"
CONFLICT_LOG="conflict.log"
LOCK_FILE=".sync.lock"

DRY_RUN=false
FORCE=false
BACKUP=false
FIX_PERM=false
ONLY=""

# =========================
# COLOR
# =========================
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

# =========================
# COUNTER
# =========================
UPDATED_COUNT=0
NEW_COUNT=0
CONFLICT_COUNT=0

declare -A P_UPDATED
declare -A P_NEW
declare -A P_CONFLICT

should_skip_update_file() {
    local path="$1"
    case "$path" in
        public/lang/custom/*|updates/public/lang/custom/*)
            return 0
            ;;
    esac
    return 1
}

is_collectable_update_file() {
    local path="$1"
    case "$path" in
        public/*|tools/|tools/language-split-tool.php|VERSION|README.md|CHANGELOG.md)
            return 0
            ;;
    esac
    return 1
}

# =========================
# COLLECT FROM MASTER (GIT BASED)
# =========================
collect_updates() {

    MASTER="$BASE_PATH/$MASTER_PROJECT"
    DEST="updates"

    echo "🔍 Detecting git changes..."

    cd "$MASTER" || exit

    git status --porcelain | while read status file; do

        if ! is_collectable_update_file "$file"; then
            continue
        fi

        if should_skip_update_file "$file"; then
            echo -e "${YELLOW}SKIPPED  → $file${NC}"
            continue
        fi

        REL="$file"
        SOURCE="$MASTER/$file"
        TARGET="$DEST/$REL"

        if [ -d "$SOURCE" ]; then
            find "$SOURCE" -type f | while read source_file; do
                REL_FILE="${source_file#$MASTER/}"
                if ! is_collectable_update_file "$REL_FILE"; then
                    continue
                fi
                if should_skip_update_file "$REL_FILE"; then
                    echo -e "${YELLOW}SKIPPED  → $REL_FILE${NC}"
                    continue
                fi

                TARGET_FILE="$DEST/$REL_FILE"
                mkdir -p "$(dirname "$TARGET_FILE")"
                cp "$source_file" "$TARGET_FILE"
                echo -e "${GREEN}NEW      → $REL_FILE${NC}"
            done
            continue
        fi

        mkdir -p "$(dirname "$TARGET")"
        cp "$SOURCE" "$TARGET"

        case "$status" in
            M*) echo -e "${CYAN}MODIFIED → $REL${NC}" ;;
            A*) echo -e "${GREEN}NEW      → $REL${NC}" ;;
            \?\?) echo -e "${GREEN}UNTRACKED → $REL${NC}" ;;
            *) echo -e "${YELLOW}CHANGE   → $REL${NC}" ;;
        esac

    done

    echo "✅ Collect complete"
}

# =========================
# MENU
# =========================
show_menu() {
    clear
    echo "========================================"
    echo "        SYNC ALL PROJECTS MENU"
    echo "========================================"
    echo "1. Sync All Projects (Normal)"
    echo "2. Dry Run (Preview sahaja)"
    echo "3. Force Sync (Overwrite semua)"
    echo "4. Backup + Sync"
    echo "5. Clear Updates Folder"
    echo "6. Fix Permission"
    echo "7. Sync Specific Project"
    echo "8. Exit"
    echo "9. Collect Updates from Master (Git)"
    echo "========================================"

    read -p "Pilih option: " choice

    case $choice in
        1) ;;
        2) DRY_RUN=true ;;
        3) FORCE=true ;;
        4) BACKUP=true ;;
        5)
            echo ""
            echo "⚠ This will DELETE all files in updates/"
            read -p "Archive before delete? (y/n): " arc

            if [ "$arc" = "y" ]; then
                ARCHIVE_DIR="$BASE_PATH/_updates_archive/$(date +%Y%m%d_%H%M%S)"
                mkdir -p "$ARCHIVE_DIR"
                cp -r updates/* "$ARCHIVE_DIR"/ 2>/dev/null
                echo "📦 Archived to $ARCHIVE_DIR"
            fi

            read -p "Proceed to clear updates? (y/n): " confirm
            if [ "$confirm" = "y" ]; then
                find updates -type f -delete
                echo "✅ updates/ cleaned"
            else
                echo "❌ Cancelled"
            fi
            exit 0
            ;;
        6) FIX_PERM=true ;;
        7)
            echo ""
            echo "Available project:"
            for p in "${PROJECTS[@]}"; do
                echo "- $p"
            done
            echo ""
            read -p "Masukkan nama project: " ONLY
            ;;
        8) exit 0 ;;
        9)
            collect_updates
            exit 0
            ;;
        *) echo "Invalid"; sleep 1; show_menu ;;
    esac
}

# =========================
# PRINT TABLE
# =========================
print_row() {
    printf "${4}│ %-12s │ %-8s │ %-44s │${NC}\n" "$1" "$2" "$3"
}

# =========================
# FLAGS
# =========================
for arg in "$@"; do
    case $arg in
        --dry-run) DRY_RUN=true ;;
        --force) FORCE=true ;;
        --backup) BACKUP=true ;;
        --fix-permission) FIX_PERM=true ;;
        --only=*) ONLY="${arg#*=}" ;;
    esac
done

# =========================
# MENU TRIGGER
# =========================
if [ $# -eq 0 ]; then
    show_menu
fi

# =========================
# LOCK
# =========================
if [ -f "$LOCK_FILE" ]; then
    echo -e "${RED}❌ Another sync is running!${NC}"
    exit 1
fi

touch "$LOCK_FILE"
trap "rm -f $LOCK_FILE; exit 1" INT TERM

echo "🚀 Starting sync..."
echo "" > $LOG_FILE
echo "" > $CONFLICT_LOG

echo ""
echo "┌──────────────┬──────────┬──────────────────────────────────────────────┐"
printf "│ %-12s │ %-8s │ %-44s │\n" "PROJECT" "ACTION" "FILE"
echo "├──────────────┼──────────┼──────────────────────────────────────────────┤"

if [ "$DRY_RUN" = false ]; then
    read -p "Proceed with sync? (y/n): " confirm
    [ "$confirm" != "y" ] && rm -f "$LOCK_FILE" && exit 0
fi

mapfile -t FILES < <(find updates -type f ! -path "updates/public/lang/custom/*")

for project in "${PROJECTS[@]}"; do

    if [[ -n "$ONLY" && "$project" != "$ONLY" ]]; then
        continue
    fi

    TARGET="$BASE_PATH/$project"
    [ ! -d "$TARGET" ] && continue

    if [ "$BACKUP" = true ]; then
        cp -r "$TARGET/public" "$TARGET/public_backup_$(date +%Y%m%d_%H%M%S)"
    fi

    for file in "${FILES[@]}"; do

        REL_PATH="${file#updates/}"
        if should_skip_update_file "$REL_PATH"; then
            print_row "$project" "SKIP" "$REL_PATH" "$YELLOW"
            continue
        fi

        SOURCE="$file"
        TARGET_FILE="$TARGET/$REL_PATH"

        mkdir -p "$(dirname "$TARGET_FILE")"

        if [ "$DRY_RUN" = true ]; then
            print_row "$project" "DRY" "$REL_PATH" "$CYAN"
            continue
        fi

        if [ "$FORCE" = true ]; then
            cp "$SOURCE" "$TARGET_FILE.tmp" && mv "$TARGET_FILE.tmp" "$TARGET_FILE"
            print_row "$project" "FORCE" "$REL_PATH" "$RED"
            ((UPDATED_COUNT++))
            ((P_UPDATED[$project]++))
            continue
        fi

        if [ -f "$TARGET_FILE" ]; then

            if cmp -s "$SOURCE" "$TARGET_FILE"; then
                continue
            fi

            if [ "$SOURCE" -nt "$TARGET_FILE" ]; then
                print_row "$project" "UPDATED" "$REL_PATH" "$GREEN"
                ((UPDATED_COUNT++))
                ((P_UPDATED[$project]++))
            else
                print_row "$project" "CONFLICT" "$REL_PATH" "$YELLOW"
                ((CONFLICT_COUNT++))
                ((P_CONFLICT[$project]++))
                continue
            fi

        else
            print_row "$project" "NEW" "$REL_PATH" "$GREEN"
            ((NEW_COUNT++))
            ((P_NEW[$project]++))
        fi

        cp "$SOURCE" "$TARGET_FILE.tmp" && mv "$TARGET_FILE.tmp" "$TARGET_FILE"

    done

    if [ "$FIX_PERM" = true ]; then
        chown -R www-data:www-data "$TARGET/public"
    fi

done

echo "└──────────────┴──────────┴──────────────────────────────────────────────┘"

rm -f "$LOCK_FILE"

echo ""
echo "====== PER PROJECT SUMMARY ======"

for project in "${PROJECTS[@]}"; do
    printf "%-12s → Updated: %-3s | New: %-3s | Conflict: %-3s\n" \
        "$project" \
        "${P_UPDATED[$project]:-0}" \
        "${P_NEW[$project]:-0}" \
        "${P_CONFLICT[$project]:-0}"
done

echo ""
echo "====== SUMMARY ======"
echo -e "${GREEN}Updated:${NC} $UPDATED_COUNT"
echo -e "${GREEN}New:${NC} $NEW_COUNT"
echo -e "${YELLOW}Conflict:${NC} $CONFLICT_COUNT"

echo ""
echo "🎉 Sync complete!"

#!/bin/bash

# =========================
# CONFIG
# =========================
BASE_PATH="/mnt/d/www"

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

PREV_LIST=".master_file_list_prev"
CUR_LIST=".master_file_list_current"

DRY_RUN=false
FORCE=false
BACKUP=false
SAFE_DELETE=false
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
# GLOBAL COUNTER
# =========================
UPDATED_COUNT=0
NEW_COUNT=0
CONFLICT_COUNT=0

# =========================
# PER PROJECT COUNTER
# =========================
declare -A P_UPDATED
declare -A P_NEW
declare -A P_CONFLICT

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
    echo "5. Safe Delete"
    echo "6. Fix Permission"
    echo "7. Sync Specific Project"
    echo "8. Exit"
    echo "========================================"

    read -p "Pilih option: " choice

    case $choice in
        1) ;;
        2) DRY_RUN=true ;;
        3) FORCE=true ;;
        4) BACKUP=true ;;
        5) SAFE_DELETE=true ;;
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
        --safe-delete) SAFE_DELETE=true ;;
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

# =========================
# TABLE HEADER
# =========================
echo ""
echo "┌──────────────┬──────────┬──────────────────────────────────────────────┐"
printf "│ %-12s │ %-8s │ %-44s │\n" "PROJECT" "ACTION" "FILE"
echo "├──────────────┼──────────┼──────────────────────────────────────────────┤"

# =========================
# CONFIRM
# =========================
if [ "$DRY_RUN" = false ]; then
    read -p "Proceed with sync? (y/n): " confirm
    [ "$confirm" != "y" ] && rm -f "$LOCK_FILE" && exit 0
fi

# =========================
# PREP FILE LIST (FIX)
# =========================
mapfile -t FILES < <(find updates -type f)

# =========================
# LOOP PROJECT
# =========================
for project in "${PROJECTS[@]}"; do

    # ONLY filter (project level)
    if [[ -n "$ONLY" && "$project" != "$ONLY" ]]; then
        continue
    fi

    TARGET="$BASE_PATH/$project"
    [ ! -d "$TARGET" ] && continue

    # backup
    if [ "$BACKUP" = true ]; then
        cp -r "$TARGET/public" "$TARGET/public_backup_$(date +%Y%m%d_%H%M%S)"
    fi

    # =========================
    # LOOP FILES (FIXED)
    # =========================
    for file in "${FILES[@]}"; do

        REL_PATH="${file#updates/}"
        SOURCE="$file"
        TARGET_FILE="$TARGET/$REL_PATH"

        mkdir -p "$(dirname "$TARGET_FILE")"

        # DRY RUN
        if [ "$DRY_RUN" = true ]; then
            print_row "$project" "DRY" "$REL_PATH" "$CYAN"
            continue
        fi

        # FORCE
        if [ "$FORCE" = true ]; then
            cp "$SOURCE" "$TARGET_FILE.tmp" && mv "$TARGET_FILE.tmp" "$TARGET_FILE"
            print_row "$project" "FORCE" "$REL_PATH" "$RED"
            ((UPDATED_COUNT++))
            ((P_UPDATED[$project]++))
            continue
        fi

        # EXISTING
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

    # =========================
    # SAFE DELETE
    # =========================
    if [ "$SAFE_DELETE" = true ] && [ -f "$PREV_LIST" ] && [ -f "$CUR_LIST" ]; then
        comm -23 "$PREV_LIST" "$CUR_LIST" > deleted_files.txt

        while read del; do
            TARGET_DEL="$TARGET/$del"
            if [ -f "$TARGET_DEL" ]; then
                rm "$TARGET_DEL"
                print_row "$project" "DELETE" "$del" "$RED"
            fi
        done < deleted_files.txt
    fi

    # =========================
    # FIX PERMISSION
    # =========================
    if [ "$FIX_PERM" = true ]; then
        chown -R www-data:www-data "$TARGET/public"
    fi

done

# =========================
# FOOTER
# =========================
echo "└──────────────┴──────────┴──────────────────────────────────────────────┘"

rm -f "$LOCK_FILE"

# =========================
# SUMMARY
# =========================
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

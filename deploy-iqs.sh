#!/bin/bash

PROJECT_NAME="iqs-framework"
PROJECT_PATH="/var/www/iqs-framework"
BRANCH="main"
SERVICE="php8.3-fpm"
URL="https://iqs-framework.upnm.edu.my"
LOG_FILE="/var/log/deploy-$PROJECT_NAME.log"

echo "==============================" >> $LOG_FILE
echo "🚀 Starting deploy: $(date)" >> $LOG_FILE

cd $PROJECT_PATH || exit

# simpan commit lama
OLD_COMMIT=$(git rev-parse HEAD)
echo "Old commit: $OLD_COMMIT" >> $LOG_FILE

# check perubahan
echo "🔍 Checking for updates..." | tee -a $LOG_FILE
git fetch origin >> $LOG_FILE 2>&1

LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse origin/$BRANCH)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✅ No changes. Already up to date." | tee -a $LOG_FILE
    exit 0
fi

echo "⬇️ Pulling latest changes..." | tee -a $LOG_FILE
git pull origin $BRANCH >> $LOG_FILE 2>&1

# detect perubahan
CHANGES=$(git diff --name-only HEAD@{1} HEAD)

echo "📂 Changed files:" | tee -a $LOG_FILE
echo "$CHANGES" | tee -a $LOG_FILE

# flags
RESTART_PHP=false
RELOAD_NGINX=false

if echo "$CHANGES" | grep -E "\.php|\.env"; then
    RESTART_PHP=true
fi

if echo "$CHANGES" | grep -E "nginx|\.conf|ssl"; then
    RELOAD_NGINX=true
fi

# =========================
# HEALTH CHECK (NEW)
# =========================
echo "🧪 Running health check..." | tee -a $LOG_FILE

if ! curl -sSf --max-time 5 $URL > /dev/null; then
    echo "❌ ERROR detected! Rolling back..." | tee -a $LOG_FILE

    git reset --hard $OLD_COMMIT >> $LOG_FILE 2>&1
    sudo systemctl reload $SERVICE >> $LOG_FILE 2>&1

    echo "🔁 Rollback DONE at $(date)" | tee -a $LOG_FILE
    exit 1
fi

# =========================
# ACTION (original logic)
# =========================
if [ "$RESTART_PHP" = true ]; then
    echo "🔄 Restarting PHP-FPM..." | tee -a $LOG_FILE
    sudo systemctl restart $SERVICE >> $LOG_FILE 2>&1
fi

if [ "$RELOAD_NGINX" = true ]; then
    echo "🔄 Reloading Nginx..." | tee -a $LOG_FILE
    sudo nginx -t && sudo systemctl reload nginx >> $LOG_FILE 2>&1
fi

echo "✅ Deploy complete!" | tee -a $LOG_FILE

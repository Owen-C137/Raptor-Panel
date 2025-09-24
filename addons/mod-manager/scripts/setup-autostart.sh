#!/bin/bash

# Mod Manager Queue Worker Auto-Starter
# This script automatically starts the queue worker when the system boots
# and ensures it's always running

SCRIPT_NAME="mod-harvest-autostart"
PROJECT_DIR="/var/www/raptorpanel_dev"
WORKER_SCRIPT="$PROJECT_DIR/addons/mod-manager/scripts/queue-worker.sh"
SYSTEMD_SERVICE="/etc/systemd/system/mod-harvest-worker.service"

install_systemd_service() {
    echo "📦 Installing systemd service for automatic queue worker startup..."
    
    # Create systemd service file
    sudo tee "$SYSTEMD_SERVICE" > /dev/null << EOF
[Unit]
Description=Mod Manager Queue Worker
After=network.target mysql.service redis.service
Requires=mysql.service redis.service

[Service]
Type=forking
User=www-data
Group=www-data
WorkingDirectory=$PROJECT_DIR
ExecStart=$WORKER_SCRIPT start
ExecStop=$WORKER_SCRIPT stop
ExecReload=$WORKER_SCRIPT restart
Restart=always
RestartSec=3
TimeoutStartSec=60
TimeoutStopSec=60

# Environment
Environment=PATH=/usr/local/bin:/usr/bin:/bin
Environment=PHP_INI_SCAN_DIR=/etc/php/8.3/cli/conf.d

[Install]
WantedBy=multi-user.target
EOF

    # Reload systemd and enable service
    sudo systemctl daemon-reload
    sudo systemctl enable mod-harvest-worker.service
    
    echo "✅ Systemd service installed and enabled"
    echo "   Service will automatically start on boot"
    echo "   Use: sudo systemctl start mod-harvest-worker"
    echo "   Use: sudo systemctl stop mod-harvest-worker"
    echo "   Use: sudo systemctl status mod-harvest-worker"
}

install_cron_job() {
    echo "📦 Installing cron job for queue worker monitoring..."
    
    # Add cron job to check and restart queue worker every 5 minutes
    (crontab -l 2>/dev/null | grep -v "mod-harvest-worker"; echo "*/5 * * * * $WORKER_SCRIPT start >/dev/null 2>&1") | crontab -
    
    echo "✅ Cron job installed"
    echo "   Queue worker will be checked every 5 minutes"
    echo "   If stopped, it will automatically restart"
}

show_manual_startup() {
    echo "📋 Manual Startup Commands:"
    echo "   Start worker: $WORKER_SCRIPT start"
    echo "   Stop worker:  $WORKER_SCRIPT stop"
    echo "   Status:       $WORKER_SCRIPT status"
    echo "   Restart:      $WORKER_SCRIPT restart"
}

case "$1" in
    systemd)
        install_systemd_service
        ;;
    cron)
        install_cron_job
        ;;
    manual)
        show_manual_startup
        ;;
    *)
        echo "Mod Manager Queue Worker Auto-Starter"
        echo "Usage: $0 {systemd|cron|manual}"
        echo
        echo "Options:"
        echo "  systemd - Install as systemd service (recommended for production)"
        echo "  cron    - Install cron job to monitor and restart worker"
        echo "  manual  - Show manual startup commands"
        echo
        echo "For production servers, use 'systemd' option."
        echo "For development, use 'manual' option."
        exit 1
        ;;
esac
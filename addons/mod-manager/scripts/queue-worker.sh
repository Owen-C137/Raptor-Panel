#!/bin/bash

# Mod Manager Queue Worker Startup Script
# Usage: ./start-queue-worker.sh [stop|start|restart|status]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="/var/www/raptorpanel_dev"
PIDFILE="$PROJECT_DIR/storage/mod-harvest-worker.pid"
LOGFILE="$PROJECT_DIR/storage/logs/mod-harvest-worker.log"

# Ensure log directory exists
mkdir -p "$(dirname "$LOGFILE")"

start_worker() {
    if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
        echo "Queue worker is already running (PID: $(cat "$PIDFILE"))"
        return 1
    fi
    
    echo "Starting mod-harvest queue worker..."
    cd "$PROJECT_DIR"
    
    # Start the queue worker in background
    nohup php artisan queue:work \
        --queue=mod-harvest \
        --sleep=1 \
        --tries=3 \
        --timeout=300 \
        --memory=512 \
        > "$LOGFILE" 2>&1 &
    
    # Save PID
    echo $! > "$PIDFILE"
    echo "Queue worker started with PID: $(cat "$PIDFILE")"
    echo "Log file: $LOGFILE"
}

stop_worker() {
    if [ ! -f "$PIDFILE" ]; then
        echo "No PID file found. Queue worker may not be running."
        return 1
    fi
    
    PID=$(cat "$PIDFILE")
    if kill -0 "$PID" 2>/dev/null; then
        echo "Stopping queue worker (PID: $PID)..."
        kill "$PID"
        rm -f "$PIDFILE"
        echo "Queue worker stopped."
    else
        echo "Queue worker is not running (PID: $PID not found)"
        rm -f "$PIDFILE"
    fi
}

status_worker() {
    if [ -f "$PIDFILE" ] && kill -0 $(cat "$PIDFILE") 2>/dev/null; then
        echo "Queue worker is running (PID: $(cat "$PIDFILE"))"
        echo "Log file: $LOGFILE"
        echo "Recent log entries:"
        tail -5 "$LOGFILE" 2>/dev/null || echo "No log entries found"
    else
        echo "Queue worker is not running"
        if [ -f "$PIDFILE" ]; then
            echo "Removing stale PID file"
            rm -f "$PIDFILE"
        fi
    fi
}

case "$1" in
    start)
        start_worker
        ;;
    stop)
        stop_worker
        ;;
    restart)
        stop_worker
        sleep 2
        start_worker
        ;;
    status)
        status_worker
        ;;
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        echo
        echo "Commands:"
        echo "  start   - Start the mod-harvest queue worker"
        echo "  stop    - Stop the mod-harvest queue worker"
        echo "  restart - Restart the mod-harvest queue worker"
        echo "  status  - Show queue worker status"
        exit 1
        ;;
esac
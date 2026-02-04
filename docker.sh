#!/bin/bash

# Change Management System - Docker Helper Script
# Makes Docker commands easier to use

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Change Management System${NC}"
    echo -e "${BLUE}  Docker Helper Script${NC}"
    echo -e "${BLUE}================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed!"
        echo "Install from: https://docs.docker.com/get-docker/"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed!"
        echo "Install from: https://docs.docker.com/compose/install/"
        exit 1
    fi
}

start_services() {
    print_info "Starting all services..."
    docker-compose up -d
    print_success "All services started!"
    echo ""
    print_info "Services are now available at:"
    echo "  - Web Application: http://localhost:8092"
    echo "  - phpMyAdmin: http://localhost:8093"
    echo "  - MySQL Port: localhost:3307"
    echo ""
    print_info "Default login: admin / admin123"
}

stop_services() {
    print_info "Stopping all services..."
    docker-compose down
    print_success "All services stopped!"
}

restart_services() {
    print_info "Restarting all services..."
    docker-compose restart
    print_success "All services restarted!"
}

rebuild_services() {
    print_info "Rebuilding and restarting services..."
    docker-compose up -d --build
    print_success "Services rebuilt and started!"
}

show_logs() {
    if [ -z "$1" ]; then
        print_info "Showing all logs (Ctrl+C to exit)..."
        docker-compose logs -f
    else
        print_info "Showing logs for $1 (Ctrl+C to exit)..."
        docker-compose logs -f "$1"
    fi
}

show_status() {
    print_info "Container status:"
    docker-compose ps
    echo ""
    print_info "Resource usage:"
    docker stats --no-stream change-management-web change-management-db change-management-phpmyadmin 2>/dev/null || true
}

backup_database() {
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    print_info "Creating database backup: $BACKUP_FILE"
    
    docker exec change-management-db mysqldump \
        -u root \
        -proot_password_change_me \
        change_management > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        print_success "Backup created: $BACKUP_FILE"
    else
        print_error "Backup failed!"
        exit 1
    fi
}

restore_database() {
    if [ -z "$1" ]; then
        print_error "Please specify backup file!"
        echo "Usage: $0 restore <backup-file.sql>"
        exit 1
    fi
    
    if [ ! -f "$1" ]; then
        print_error "File not found: $1"
        exit 1
    fi
    
    print_info "Restoring database from: $1"
    
    docker exec -i change-management-db mysql \
        -u root \
        -proot_password_change_me \
        change_management < "$1"
    
    if [ $? -eq 0 ]; then
        print_success "Database restored successfully!"
    else
        print_error "Restore failed!"
        exit 1
    fi
}

reset_database() {
    print_info "Resetting database..."
    
    # Confirm
    read -p "Are you sure? This will DELETE ALL DATA! (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        print_info "Cancelled."
        exit 0
    fi
    
    # Drop and recreate
    docker exec -i change-management-db mysql -u root -proot_password_change_me -e \
        "DROP DATABASE IF EXISTS change_management; CREATE DATABASE change_management;"
    
    # Import fresh data
    docker exec -i change-management-db mysql -u root -proot_password_change_me change_management < database.sql
    
    print_success "Database reset complete!"
}

shell_access() {
    if [ "$1" == "web" ] || [ -z "$1" ]; then
        print_info "Accessing web container shell..."
        docker exec -it change-management-web bash
    elif [ "$1" == "db" ]; then
        print_info "Accessing database shell..."
        docker exec -it change-management-db bash
    elif [ "$1" == "mysql" ]; then
        print_info "Accessing MySQL CLI..."
        docker exec -it change-management-db mysql -u root -proot_password_change_me
    else
        print_error "Unknown container: $1"
        echo "Available: web, db, mysql"
        exit 1
    fi
}

clean_all() {
    print_info "Cleaning all containers, images, and volumes..."
    
    read -p "This will DELETE ALL DATA! Continue? (yes/no): " confirm
    if [ "$confirm" != "yes" ]; then
        print_info "Cancelled."
        exit 0
    fi
    
    docker-compose down -v
    docker system prune -af
    
    print_success "Cleanup complete!"
}

show_help() {
    print_header
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  start           - Start all services"
    echo "  stop            - Stop all services"
    echo "  restart         - Restart all services"
    echo "  rebuild         - Rebuild and restart services"
    echo "  status          - Show container status"
    echo "  logs [service]  - Show logs (optional: web, db, phpmyadmin)"
    echo "  backup          - Backup database"
    echo "  restore <file>  - Restore database from backup"
    echo "  reset           - Reset database (⚠️ deletes all data)"
    echo "  shell [type]    - Access container shell (web, db, mysql)"
    echo "  clean           - Clean all containers and volumes (⚠️ deletes all)"
    echo "  help            - Show this help"
    echo ""
    echo "Examples:"
    echo "  $0 start"
    echo "  $0 logs web"
    echo "  $0 backup"
    echo "  $0 restore backup_20250130.sql"
    echo "  $0 shell mysql"
    echo ""
}

# Main script
print_header
check_docker

case "$1" in
    start)
        start_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        ;;
    rebuild)
        rebuild_services
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs "$2"
        ;;
    backup)
        backup_database
        ;;
    restore)
        restore_database "$2"
        ;;
    reset)
        reset_database
        ;;
    shell)
        shell_access "$2"
        ;;
    clean)
        clean_all
        ;;
    help|--help|-h|"")
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        echo ""
        show_help
        exit 1
        ;;
esac
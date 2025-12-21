#!/bin/bash

# Database Setup Script for Multiple Environments
# This script helps set up databases for different environments

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored messages
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to show usage
show_usage() {
    cat << EOF
Usage: $0 <environment> [action]

Environments:
  production  - Production database setup
  staging     - Staging database setup (for release preparation)
  beta        - Beta database setup (for testing)
  dev         - Development database setup

Actions:
  create      - Create database and user (default)
  migrate     - Run database migrations
  seed        - Seed database with test data
  backup      - Backup database
  restore     - Restore database from backup
  reset       - Drop and recreate database (WARNING: destructive!)
  status      - Show database status

Examples:
  $0 staging create          # Create staging database
  $0 staging migrate         # Run migrations on staging
  $0 staging backup          # Backup staging database
  $0 beta reset              # Reset beta database

EOF
}

# Check if environment is provided
if [ $# -eq 0 ]; then
    show_usage
    exit 1
fi

ENVIRONMENT=$1
ACTION=${2:-create}

# Set environment-specific variables
case $ENVIRONMENT in
    production)
        DB_NAME="hypnose_stammtisch"
        DB_USER="hypnose_user"
        ENV_FILE="backend/.env"
        PORT=8000
        ;;
    staging)
        DB_NAME="hypnose_stammtisch_staging"
        DB_USER="staging_user"
        ENV_FILE="backend/.env.staging"
        PORT=8001
        ;;
    beta)
        DB_NAME="hypnose_stammtisch_beta"
        DB_USER="beta_user"
        ENV_FILE="backend/.env.beta"
        PORT=8002
        ;;
    dev|development)
        DB_NAME="hypnose_stammtisch_dev"
        DB_USER="dev_user"
        ENV_FILE="backend/.env"
        PORT=8000
        ;;
    *)
        print_error "Unknown environment: $ENVIRONMENT"
        show_usage
        exit 1
        ;;
esac

# Function to prompt for MySQL root password
get_mysql_password() {
    if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
        # Password already set (e.g., from environment variable)
        return
    fi
    read -sp "Enter MySQL root password: " MYSQL_ROOT_PASSWORD
    echo
    export MYSQL_ROOT_PASSWORD
}

# Function to create database
create_database() {
    print_info "Creating database: $DB_NAME"
    get_mysql_password
    
    # Create database
    ERROR_OUTPUT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1)
    
    if [ $? -eq 0 ]; then
        print_info "Database $DB_NAME created successfully"
    else
        print_error "Failed to create database"
        print_error "MySQL error: $ERROR_OUTPUT"
        exit 1
    fi
    
    # Prompt for user password
    read -sp "Enter password for database user '$DB_USER': " DB_PASSWORD
    echo
    
    # Create user and grant privileges
    ERROR_OUTPUT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<EOF 2>&1
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
)
    
    if [ $? -eq 0 ]; then
        print_info "User $DB_USER created and privileges granted"
    else
        print_error "Failed to create user"
        print_error "MySQL error: $ERROR_OUTPUT"
        exit 1
    fi
    
    print_info "Database setup completed successfully!"
    print_warn "Don't forget to update $ENV_FILE with the database credentials"
}

# Function to run migrations
run_migrations() {
    print_info "Running migrations for $ENVIRONMENT environment..."
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error "Environment file $ENV_FILE not found"
        print_warn "Please create it from the example file first"
        exit 1
    fi
    
    cd backend
    
    # Check if migration script exists
    if [ -f "migrations/001_initial_schema.sql" ]; then
        print_info "Importing initial schema..."
        get_mysql_password
        mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME" < migrations/001_initial_schema.sql
        print_info "Schema imported successfully"
    else
        print_warn "No migration files found"
    fi
    
    cd ..
}

# Function to seed database
seed_database() {
    print_info "Seeding database for $ENVIRONMENT environment..."
    
    cd backend
    
    if [ -f "cli/cli.php" ]; then
        php cli/cli.php migrate --seed
        print_info "Database seeded successfully"
    else
        print_warn "CLI tool not found, skipping seed"
    fi
    
    cd ..
}

# Function to backup database
backup_database() {
    print_info "Backing up database: $DB_NAME"
    
    BACKUP_DIR="backend/backups"
    mkdir -p "$BACKUP_DIR"
    
    BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_$(date +%Y%m%d_%H%M%S).sql"
    
    get_mysql_password
    # Redirect stderr to capture errors, stdout goes to backup file
    ERROR_OUTPUT=$(mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME" > "$BACKUP_FILE" 2>&1 >/dev/null)
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        print_info "Database backed up to: $BACKUP_FILE"
        
        # Compress backup
        gzip "$BACKUP_FILE"
        print_info "Backup compressed: ${BACKUP_FILE}.gz"
    else
        print_error "Backup failed"
        if [ -n "$ERROR_OUTPUT" ]; then
            print_error "MySQL error: $ERROR_OUTPUT"
        fi
        # Clean up partial backup file
        rm -f "$BACKUP_FILE"
        exit 1
    fi
}

# Function to restore database
restore_database() {
    print_info "Restoring database: $DB_NAME"
    
    # List available backups
    BACKUP_DIR="backend/backups"
    if [ ! -d "$BACKUP_DIR" ]; then
        print_error "No backups directory found"
        exit 1
    fi
    
    print_info "Available backups:"
    ls -lh "$BACKUP_DIR"/${DB_NAME}_*.sql.gz 2>/dev/null || {
        print_error "No backups found for $DB_NAME"
        exit 1
    }
    
    read -p "Enter backup filename to restore: " BACKUP_FILE
    
    if [ ! -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
        print_error "Backup file not found: $BACKUP_FILE"
        exit 1
    fi
    
    print_warn "This will overwrite the current database. Are you sure? (yes/no)"
    read -p "> " CONFIRM
    
    if [ "$CONFIRM" != "yes" ]; then
        print_info "Restore cancelled"
        exit 0
    fi
    
    get_mysql_password
    
    if [[ $BACKUP_FILE == *.gz ]]; then
        ERROR_OUTPUT=$(gunzip -c "$BACKUP_DIR/$BACKUP_FILE" 2>&1 | mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME" 2>&1)
        EXIT_CODE=$?
    else
        ERROR_OUTPUT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME" < "$BACKUP_DIR/$BACKUP_FILE" 2>&1)
        EXIT_CODE=$?
    fi
    
    if [ $EXIT_CODE -eq 0 ]; then
        print_info "Database restored successfully"
    else
        print_error "Restore failed"
        if [ -n "$ERROR_OUTPUT" ]; then
            print_error "MySQL error: $ERROR_OUTPUT"
        fi
        exit 1
    fi
}

# Function to reset database
reset_database() {
    print_warn "WARNING: This will delete all data in $DB_NAME!"
    print_warn "Are you sure you want to continue? (yes/no)"
    read -p "> " CONFIRM
    
    if [ "$CONFIRM" != "yes" ]; then
        print_info "Reset cancelled"
        exit 0
    fi
    
    # Create backup first
    print_info "Creating backup before reset..."
    backup_database
    
    get_mysql_password
    
    print_info "Dropping database..."
    ERROR_OUTPUT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>&1)
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -ne 0 ]; then
        print_error "Failed to drop database"
        if [ -n "$ERROR_OUTPUT" ]; then
            print_error "MySQL error: $ERROR_OUTPUT"
        fi
        exit 1
    fi
    
    print_info "Recreating database..."
    ERROR_OUTPUT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1)
    EXIT_CODE=$?
    
    if [ $EXIT_CODE -eq 0 ]; then
        print_info "Database reset successfully"
        run_migrations
    else
        print_error "Reset failed"
        if [ -n "$ERROR_OUTPUT" ]; then
            print_error "MySQL error: $ERROR_OUTPUT"
        fi
        exit 1
    fi
}

# Function to show database status
show_status() {
    print_info "Database Status for $ENVIRONMENT environment"
    echo "----------------------------------------"
    echo "Database Name: $DB_NAME"
    echo "Database User: $DB_USER"
    echo "Environment File: $ENV_FILE"
    echo "Backend Port: $PORT"
    echo "----------------------------------------"
    
    get_mysql_password
    
    # Check if database exists
    DB_EXISTS=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES LIKE '$DB_NAME';" 2>/dev/null | grep "$DB_NAME" | wc -l)
    
    if [ $DB_EXISTS -eq 1 ]; then
        print_info "Database exists: YES"
        
        # Show table count
        TABLE_COUNT=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" 2>/dev/null | wc -l)
        echo "Number of tables: $((TABLE_COUNT - 1))"
        
        # Show database size
        DB_SIZE=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = '$DB_NAME';" 2>/dev/null | tail -n 1)
        echo "Database size: ${DB_SIZE} MB"
    else
        print_warn "Database exists: NO"
    fi
    
    # Check if user exists
    USER_EXISTS=$(mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SELECT User FROM mysql.user WHERE User='$DB_USER' AND Host='localhost';" 2>/dev/null | grep "$DB_USER" | wc -l)
    
    if [ $USER_EXISTS -eq 1 ]; then
        print_info "Database user exists: YES"
    else
        print_warn "Database user exists: NO"
    fi
    
    # Check if environment file exists
    if [ -f "$ENV_FILE" ]; then
        print_info "Environment file exists: YES"
    else
        print_warn "Environment file exists: NO"
    fi
}

# Execute action
case $ACTION in
    create)
        create_database
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        seed_database
        ;;
    backup)
        backup_database
        ;;
    restore)
        restore_database
        ;;
    reset)
        reset_database
        ;;
    status)
        show_status
        ;;
    *)
        print_error "Unknown action: $ACTION"
        show_usage
        exit 1
        ;;
esac

print_info "Done!"

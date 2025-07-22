#!/bin/bash

# CoderStew Website Production Deployment Script
# Automated deployment to Unraid with SSL certificates and monitoring

set -e  # Exit on any error

echo "🚀 CoderStew Website Production Deployment"
echo "=========================================="

# Configuration
PROJECT_NAME="coderstew_website"
DOCKER_COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE="backend/.env.production"
TRAEFIK_ENV_FILE="docker/traefik/.env"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed or not in PATH"
        exit 1
    fi
    
    # Check Docker Compose
    if ! docker compose version &> /dev/null; then
        print_error "Docker Compose is not available"
        exit 1
    fi
    
    # Check if running as root (required for port 80/443)
    if [[ $EUID -eq 0 ]]; then
        print_warning "Running as root. Make sure this is intended for production."
    fi
    
    print_success "Prerequisites check passed"
}

# Create required directories
create_directories() {
    print_status "Creating required directories..."
    
    mkdir -p docker/traefik/certs
    mkdir -p secrets
    mkdir -p logs
    mkdir -p backup
    
    # Set proper permissions for SSL certificates
    chmod 700 docker/traefik/certs
    
    print_success "Directories created"
}

# Generate secrets if they don't exist
generate_secrets() {
    print_status "Checking database secrets..."
    
    if [[ ! -f "secrets/mysql_root_password.txt" ]]; then
        print_warning "MySQL root password not found. Generating..."
        openssl rand -base64 32 > secrets/mysql_root_password.txt
        chmod 600 secrets/mysql_root_password.txt
    fi
    
    if [[ ! -f "secrets/mysql_password.txt" ]]; then
        print_warning "MySQL user password not found. Generating..."
        openssl rand -base64 32 > secrets/mysql_password.txt
        chmod 600 secrets/mysql_password.txt
    fi
    
    print_success "Database secrets ready"
}

# Verify environment configuration
check_environment() {
    print_status "Verifying environment configuration..."
    
    if [[ ! -f "$ENV_FILE" ]]; then
        print_error "Production environment file not found: $ENV_FILE"
        print_error "Please create the production environment file first"
        exit 1
    fi
    
    if [[ ! -f "$TRAEFIK_ENV_FILE" ]]; then
        print_warning "Traefik environment file not found. Using defaults."
    fi
    
    print_success "Environment configuration verified"
}

# Create external networks
create_networks() {
    print_status "Creating Docker networks..."
    
    # Create Traefik public network if it doesn't exist
    if ! docker network ls | grep -q "traefik_public"; then
        docker network create traefik_public
        print_success "Created traefik_public network"
    else
        print_status "traefik_public network already exists"
    fi
}

# Build production images
build_images() {
    print_status "Building production Docker images..."
    
    docker compose -f "$DOCKER_COMPOSE_FILE" build --no-cache
    
    print_success "Production images built successfully"
}

# Deploy the application
deploy_application() {
    print_status "Deploying application..."
    
    # Pull latest images for services that don't build locally
    docker compose -f "$DOCKER_COMPOSE_FILE" pull
    
    # Start the application
    docker compose -f "$DOCKER_COMPOSE_FILE" up -d
    
    print_success "Application deployed"
}

# Wait for services to be healthy
wait_for_services() {
    print_status "Waiting for services to become healthy..."
    
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker compose -f "$DOCKER_COMPOSE_FILE" ps | grep -q "healthy"; then
            print_success "Services are healthy"
            return 0
        fi
        
        print_status "Attempt $attempt/$max_attempts - Waiting for services..."
        sleep 10
        ((attempt++))
    done
    
    print_error "Services did not become healthy within expected time"
    print_status "Checking service status..."
    docker compose -f "$DOCKER_COMPOSE_FILE" ps
    docker compose -f "$DOCKER_COMPOSE_FILE" logs --tail=50
}

# Run Laravel setup tasks
setup_laravel() {
    print_status "Running Laravel setup tasks..."
    
    # Run migrations
    docker compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan migrate --force
    
    # Clear and cache configuration
    docker compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan config:cache
    docker compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan route:cache
    docker compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan view:cache
    
    # Generate application key if needed
    if ! grep -q "APP_KEY=base64:" "$ENV_FILE"; then
        docker compose -f "$DOCKER_COMPOSE_FILE" exec -T app php artisan key:generate --force
    fi
    
    print_success "Laravel setup completed"
}

# Display deployment information
show_deployment_info() {
    print_success "🎉 Deployment completed successfully!"
    echo ""
    echo "Application URLs:"
    echo "  - Main site: https://coderstew.com"
    echo "  - Admin panel: https://coderstew.com/admin"
    echo "  - API: https://coderstew.com/api"
    echo "  - Traefik dashboard: https://traefik.coderstew.com"
    echo ""
    echo "Service Status:"
    docker compose -f "$DOCKER_COMPOSE_FILE" ps
    echo ""
    echo "SSL Certificates:"
    echo "  - Let's Encrypt certificates will be automatically generated"
    echo "  - Certificate storage: ./docker/traefik/certs/"
    echo ""
    echo "Monitoring:"
    echo "  - Logs: docker compose -f $DOCKER_COMPOSE_FILE logs -f"
    echo "  - Stats: docker compose -f $DOCKER_COMPOSE_FILE top"
    echo ""
    print_warning "Important: Make sure your DNS is pointing to this server"
    print_warning "Important: Firewall ports 80 and 443 must be open"
}

# Rollback function
rollback() {
    print_error "Deployment failed. Rolling back..."
    docker compose -f "$DOCKER_COMPOSE_FILE" down
    exit 1
}

# Main deployment flow
main() {
    # Set up error handling
    trap rollback ERR
    
    echo "Starting deployment at $(date)"
    
    check_prerequisites
    create_directories
    generate_secrets
    check_environment
    create_networks
    build_images
    deploy_application
    wait_for_services
    setup_laravel
    show_deployment_info
    
    echo "Deployment completed at $(date)"
}

# Handle command line arguments
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "stop")
        print_status "Stopping services..."
        docker compose -f "$DOCKER_COMPOSE_FILE" down
        print_success "Services stopped"
        ;;
    "restart")
        print_status "Restarting services..."
        docker compose -f "$DOCKER_COMPOSE_FILE" restart
        print_success "Services restarted"
        ;;
    "logs")
        docker compose -f "$DOCKER_COMPOSE_FILE" logs -f "${2:-}"
        ;;
    "status")
        docker compose -f "$DOCKER_COMPOSE_FILE" ps
        ;;
    "update")
        print_status "Updating application..."
        docker compose -f "$DOCKER_COMPOSE_FILE" pull
        docker compose -f "$DOCKER_COMPOSE_FILE" up -d
        setup_laravel
        print_success "Update completed"
        ;;
    *)
        echo "Usage: $0 {deploy|stop|restart|logs|status|update}"
        echo ""
        echo "Commands:"
        echo "  deploy  - Full deployment (default)"
        echo "  stop    - Stop all services"
        echo "  restart - Restart all services"
        echo "  logs    - Show logs (optionally specify service name)"
        echo "  status  - Show service status"
        echo "  update  - Update and restart services"
        exit 1
        ;;
esac
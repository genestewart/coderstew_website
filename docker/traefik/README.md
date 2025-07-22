# Traefik Reverse Proxy & SSL Configuration

This directory contains the Traefik v3.0 configuration for the CoderStew website production deployment, including automatic SSL certificate management via Let's Encrypt.

## Files Overview

- `traefik.yml` - Main Traefik static configuration
- `dynamic.yml` - Dynamic routing rules, middleware, and SSL settings  
- `.env` - Environment variables for Traefik configuration
- `certs/` - SSL certificate storage directory
- `generate-self-signed.sh` - Script for local development certificates

## Features

### SSL/TLS Configuration
- **Automatic Let's Encrypt certificates** for production domains
- **HTTP to HTTPS redirect** for all traffic
- **Modern TLS configuration** (TLS 1.2/1.3, strong cipher suites)
- **Self-signed certificates** for local development

### Security Middleware
- **Security headers** (HSTS, CSP, XSS protection, etc.)
- **Rate limiting** (100 req/min general, 60 req/min for API)
- **CORS configuration** for API endpoints
- **Content compression** (gzip)

### Routing Configuration  
- **Main website** routing with www to non-www redirect
- **API endpoints** with dedicated rate limiting rules
- **Admin panel** routing with security middleware
- **Traefik dashboard** on secure subdomain

## Production Deployment

### Prerequisites
1. Domain DNS pointing to server IP
2. Ports 80 and 443 open in firewall
3. Docker and Docker Compose installed
4. Valid email address for Let's Encrypt

### Quick Start
1. Update domain configuration in `.env` file
2. Run the deployment script:
   ```bash
   ./deploy-production.sh
   ```

### Manual Setup
1. Create the external network:
   ```bash
   docker network create traefik_public
   ```

2. Start the production stack:
   ```bash
   docker compose -f docker-compose.prod.yml up -d
   ```

3. Monitor certificate generation:
   ```bash
   docker compose -f docker-compose.prod.yml logs traefik
   ```

## Local Development

For local development with SSL:

1. Generate self-signed certificates:
   ```bash
   ./docker/traefik/generate-self-signed.sh
   ```

2. Add to `/etc/hosts`:
   ```
   127.0.0.1 coderstew.local
   127.0.0.1 www.coderstew.local
   127.0.0.1 traefik.coderstew.local
   ```

3. Import the generated certificate to your browser

## Configuration Details

### Domain Configuration
Update the following in `.env`:
- `DOMAIN=coderstew.com`
- `WWW_DOMAIN=www.coderstew.com`
- `TRAEFIK_DASHBOARD_DOMAIN=traefik.coderstew.com`

### SSL Certificate Storage
- Production: `./certs/acme.json` (auto-generated)
- Development: `./certs/default.crt` and `./certs/default.key`

### Rate Limiting
- General traffic: 100 requests/minute with 50 burst
- API endpoints: 60 requests/minute with 20 burst
- Login endpoints: 5 requests/minute

### Health Checks
- Web service: `/health` endpoint
- Interval: 30 seconds
- Timeout: 10 seconds
- Retries: 3

## Monitoring

### Service Status
```bash
docker compose -f docker-compose.prod.yml ps
```

### View Logs
```bash
# All services
docker compose -f docker-compose.prod.yml logs -f

# Traefik only
docker compose -f docker-compose.prod.yml logs -f traefik
```

### Traefik Dashboard
Access at: `https://traefik.coderstew.com`

## Troubleshooting

### SSL Certificate Issues
1. Check Let's Encrypt rate limits
2. Verify DNS resolution
3. Check firewall ports 80/443
4. Review Traefik logs for ACME errors

### Common Issues
- **Port conflicts**: Ensure no other services on ports 80/443
- **DNS propagation**: Allow 24-48 hours for DNS changes
- **Firewall**: Verify ports are open for Let's Encrypt validation
- **Rate limits**: Use staging server for testing (see traefik.yml)

### Certificate Renewal
Certificates auto-renew 30 days before expiration. To force renewal:
```bash
docker compose -f docker-compose.prod.yml restart traefik
```

## Security Notes

- SSL certificates stored in `./certs/` with restricted permissions
- Security headers configured per OWASP recommendations  
- Rate limiting prevents abuse and DDoS attacks
- Regular security updates via Alpine-based images

## Support

For issues related to:
- SSL configuration: Check Traefik documentation
- Let's Encrypt: Check certificate authority status
- Docker networking: Verify network configuration
- Domain routing: Check DNS and Traefik rules
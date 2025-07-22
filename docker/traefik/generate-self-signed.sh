#!/bin/bash

# Generate Self-Signed SSL Certificates for Local Development
# This script creates self-signed certificates for testing Traefik SSL setup

CERT_DIR="/home/gene/Documents/VSCode/coderstew_website/docker/traefik/certs"
DOMAIN="coderstew.local"

echo "🔐 Generating self-signed SSL certificates for local development..."

# Create certificates directory if it doesn't exist
mkdir -p "$CERT_DIR"

# Generate private key
openssl genrsa -out "$CERT_DIR/default.key" 2048

# Generate certificate signing request
openssl req -new -key "$CERT_DIR/default.key" -out "$CERT_DIR/default.csr" -subj "/C=US/ST=CA/L=San Francisco/O=CoderStew/OU=Development/CN=$DOMAIN"

# Generate self-signed certificate
openssl x509 -req -days 365 -in "$CERT_DIR/default.csr" -signkey "$CERT_DIR/default.key" -out "$CERT_DIR/default.crt" -extensions v3_req -extfile <(
cat <<-EOF
[v3_req]
keyUsage = keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names

[alt_names]
DNS.1 = $DOMAIN
DNS.2 = www.$DOMAIN
DNS.3 = localhost
DNS.4 = traefik.$DOMAIN
IP.1 = 127.0.0.1
IP.2 = 0.0.0.0
EOF
)

# Clean up CSR file
rm "$CERT_DIR/default.csr"

# Set proper permissions
chmod 600 "$CERT_DIR/default.key"
chmod 644 "$CERT_DIR/default.crt"

echo "✅ Self-signed certificates generated successfully!"
echo "   - Certificate: $CERT_DIR/default.crt"
echo "   - Private Key: $CERT_DIR/default.key"
echo ""
echo "🔧 To use these certificates for local development:"
echo "   1. Add '$DOMAIN' and 'www.$DOMAIN' to your /etc/hosts file"
echo "   2. Import the certificate to your browser's trusted certificate store"
echo "   3. Start Docker Compose with the development configuration"
echo ""
echo "⚠️  Note: These certificates are only for local development."
echo "   Production will use Let's Encrypt certificates automatically."
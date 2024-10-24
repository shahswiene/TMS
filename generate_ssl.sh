#!/bin/bash

# Generate SSL certificate and key
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/key.pem \
    -out /etc/ssl/cert.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/OU=Unit/CN=localhost"

echo "Self-signed SSL certificate generated successfully."
ls -la /etc/ssl
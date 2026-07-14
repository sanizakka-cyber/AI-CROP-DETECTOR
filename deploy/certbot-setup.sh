#!/usr/bin/env bash
set -e

sudo apt update
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d msas.online -d www.msas.online

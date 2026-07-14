#!/usr/bin/env bash
set -e

APP_DIR="/var/www/msas/web"
REPO_DIR="/var/www/msas"

mkdir -p "$APP_DIR"
cd "$REPO_DIR"

if [ -d .git ]; then
  git pull origin main || true
fi

cd "$APP_DIR"
if [ -d .next ]; then
  rm -rf .next
fi

cp -R "$REPO_DIR/web/." "$APP_DIR"
cd "$APP_DIR"
npm install --omit=dev
npm run build

if [ ! -d "$APP_DIR/.next/standalone" ]; then
  echo "Standalone build was not produced" >&2
  exit 1
fi

systemctl daemon-reload
systemctl restart msas-web
systemctl enable msas-web

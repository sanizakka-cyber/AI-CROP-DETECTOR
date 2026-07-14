# MSAS deployment quickstart

## Build the web app

```bash
cd web
npm install
npm run build
```

## Run locally

```bash
cd web
PORT=3000 node .next/standalone/server.js
```

## Host configuration

- Reverse proxy to port 3000
- Serve via Nginx
- Enable SSL with Let's Encrypt
- Domain: msas.online

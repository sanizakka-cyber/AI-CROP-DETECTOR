"""
cPanel Python App entry point.
Phusion Passenger expects a WSGI app named `application`.
FastAPI is ASGI — a2wsgi bridges the two.
"""
import sys
import os

sys.path.insert(0, os.path.dirname(__file__))

from a2wsgi import ASGIMiddleware
from main import app

# Passenger will call this as a WSGI app
application = ASGIMiddleware(app)

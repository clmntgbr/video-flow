import os
import json

from api import status
from flask import Flask

app = Flask(__name__)

app.add_url_rule('/status', 'status', status)

"""Fetch the exact PHP-8.5 revision audited by this repository into a local directory."""
import argparse
import base64
import json
import urllib.request
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('destination', type=Path)
args = parser.parse_args()
args.destination.mkdir(parents=True, exist_ok=True)
revision = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e'
for name in ['zend_language_parser.y', 'zend_language_scanner.l', 'zend_compile.c']:
    url = f'https://api.github.com/repos/php/php-src/contents/Zend/{name}?ref={revision}'
    with urllib.request.urlopen(url) as response:
        data = json.load(response)
    (args.destination / name).write_bytes(base64.b64decode(data['content']))
print(revision)

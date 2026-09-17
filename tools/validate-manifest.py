"""Validate JSON Schema and package integrity. Requires tools/requirements.txt."""
import argparse
import json
from pathlib import Path
import sys

from jsonschema import Draft202012Validator

ROOT = Path(__file__).resolve().parents[1]


def validate(manifest, root=ROOT):
    schema = json.loads((ROOT / 'schema/php-grammar.schema.json').read_text())
    Draft202012Validator.check_schema(schema)
    errors = [f'{error.json_path}: {error.message}' for error in
              sorted(Draft202012Validator(schema).iter_errors(manifest), key=lambda e: e.json_path)]
    if errors:
        return errors
    versions = set()
    for i, package in enumerate(manifest['versions']):
        if package['version'] in versions:
            errors.append(f'$.versions[{i}].version: duplicate version {package["version"]}')
        versions.add(package['version'])
        paths = {key: package[key] for key in ('grammar', 'documentation', 'conformanceFixtures')}
        paths.update({f'metadata.{key}': value for key, value in package.get('metadata', {}).items()})
        for field, relative in paths.items():
            path = (root / relative).resolve()
            if not path.is_relative_to(root.resolve()) or not path.exists():
                errors.append(f'$.versions[{i}].{field}: missing or outside package: {relative}')
            elif field != 'conformanceFixtures' and not path.is_file():
                errors.append(f'$.versions[{i}].{field}: expected a file: {relative}')
            elif field == 'conformanceFixtures' and not path.is_dir():
                errors.append(f'$.versions[{i}].{field}: expected a directory: {relative}')
    primitives = set(manifest.get('lexicalPrimitives', ['code-unit']))
    definitions = set(manifest.get('lexicalPrimitiveDefinitions', {}))
    if definitions - primitives or ('schemaVersion' in manifest and primitives != definitions):
        errors.append('$.lexicalPrimitiveDefinitions: definitions must correspond to declared primitives')
    return errors


def main():
    parser = argparse.ArgumentParser(__doc__)
    parser.add_argument('manifest', nargs='?', type=Path, default=ROOT / 'php-grammar.json')
    args = parser.parse_args()
    try:
        errors = validate(json.loads(args.manifest.read_text()), args.manifest.resolve().parent)
    except (OSError, ValueError) as error:
        print(f'{args.manifest}: {error}', file=sys.stderr)
        return 1
    if errors:
        print('\n'.join(errors), file=sys.stderr)
        return 1
    print('Manifest schema 1.0 and package paths: PASS')
    return 0


if __name__ == '__main__':
    sys.exit(main())

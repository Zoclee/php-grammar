"""Negative schema and package integrity regression examples."""
import copy
import importlib.util
import json
from pathlib import Path
import unittest
import sys

sys.dont_write_bytecode = True

spec = importlib.util.spec_from_file_location('manifest', Path(__file__).with_name('validate-manifest.py'))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class ManifestTests(unittest.TestCase):
    def setUp(self):
        self.data = json.loads((module.ROOT / 'php-grammar.json').read_text())

    def test_current_and_legacy(self):
        self.assertEqual([], module.validate(self.data))
        legacy = {'versions': self.data['versions']}
        self.assertEqual([], module.validate(legacy))

    def test_invalid_fields(self):
        for field, value in [('version', '8.5.1'), ('grammar', '../outside'), ('grammar', '/absolute'),
                             ('grammar', 'C:/drive'), ('grammar', 'grammar\\file'), ('grammar', 'grammar/./file'),
                             ('grammar', 'missing.ebnf'), ('status', 'unknown'),
                             ('phpSource', {'branch': 'PHP-8.5', 'commit': 'HEAD'}), ('metadata', {'bad':'../outside'})]:
            with self.subTest(field=field, value=value):
                data = copy.deepcopy(self.data)
                data['versions'][0][field] = value
                self.assertTrue(module.validate(data))

    def test_structure_and_primitives(self):
        for key, value in [('schemaVersion','2.0'), ('versions', []), ('lexicalPrimitives',['code-unit','code-unit']),
                           ('lexicalPrimitiveDefinitions', {'code-unit': {'meaning':'x'}})]:
            with self.subTest(key=key):
                data = copy.deepcopy(self.data)
                data[key] = value
                self.assertTrue(module.validate(data))
        self.data['versions'].append(copy.deepcopy(self.data['versions'][0]))
        self.assertTrue(module.validate(self.data))


if __name__ == '__main__':
    unittest.main()

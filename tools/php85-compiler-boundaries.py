"""Inventory direct fatal diagnostic sites at the pin; not an equivalence proof.

Classify the diagnostic's execution phase, not the file containing its helper.
Review indirect/deferred validators separately; this inventory does not claim
that every error path has an independently isolated fixture.
"""
import argparse
import hashlib
import json
import re
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('source_directory', type=Path)
parser.add_argument('--check', action='store_true')
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
lock = json.loads((root / 'tools/php85-source-lock.json').read_text())
sources = {}
for name, digest in lock.items():
    data = (args.source_directory / name).read_bytes()
    if hashlib.sha256(data).hexdigest() != digest:
        raise SystemExit(f'{name}: source differs from pin')
    sources[name] = data.decode()
compiler = sources['zend_compile.c']
# Named C function definitions, including multiline signatures and trailing comments.
definitions = list(re.finditer(r'(?m)^(?:static |inline |ZEND_API )*[\w *]+\b([a-zA-Z_]\w*)\([^;{}]*\)\s*(?:/\*[^*]*\*/\s*)?\{', compiler))
functions = {m[1]: compiler[m.start():definitions[i+1].start() if i+1 < len(definitions) else len(compiler)] for i,m in enumerate(definitions)}
parser_calls = set(re.findall(r'\b(zend_\w+)\s*\(', sources['zend_language_parser.y']))
early = set(parser_calls & functions.keys())
while True:
    reached = early | {call for name in early for call in re.findall(r'\b(zend_\w+)\s*\(', functions[name]) if call in functions}
    if reached == early: break
    early = reached
rows = []
for match in re.finditer(r'\b(zend_error(?:_noreturn(?:_unchecked)?)?|zend_throw_exception(?:_ex)?)\s*\(', compiler):
    start = match.start()
    end = compiler.find(';', start)
    diagnostic = compiler[start:end+1]
    if not any(token in diagnostic for token in ['E_COMPILE_ERROR', 'E_ERROR', 'zend_ce_compile_error', 'error_level']):
        continue
    preceding = [m for m in definitions if m.start() <= start]
    name = preceding[-1][1] if preceding else '(global)'
    phase = 'parser-action' if name in early else 'contextual-compilation'
    if name in ['zend_try_ct_eval_array', 'zend_eval_const_expr']:
        phase = 'constant-folding'
    if name == 'zend_stack_limit_error':
        phase = 'implementation-resource-limit'
    messages = re.findall(r'"((?:[^"\\]|\\.)*)"', diagnostic)
    rows.append({'line': compiler.count('\n', 0, start)+1, 'function': name, 'phase': phase,
                 'diagnostic': ' '.join(messages) or '(computed diagnostic)'})
result = {'source_pin':'7a4c62795365ed6a97a0184c96375b9fb4d53b1e', 'sha256': lock['zend_compile.c'],
          'scope':'Every direct fatal diagnostic call in zend_compile.c; parser-reachable helpers classified before folding. Runtime/deferred helpers in other translation units and individual-path fixture completeness remain outside this inventory.',
          'parser_reachable_functions': sorted(early), 'sites': rows}
output = json.dumps(result, indent=2)+'\n'
path = root/'docs/8.5/compiler-boundaries.json'
if args.check:
    if path.read_text() != output: raise SystemExit('Compiler boundary inventory is stale')
else:
    path.write_text(output)
print({'sites':len(rows),'parser_action_sites':sum(r['phase']=='parser-action' for r in rows),'functions':len(set(r['function'] for r in rows))})

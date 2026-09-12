"""Reconcile final bounded certification evidence. Offline after source fetch.

No count or inherited family link promotes a diagnostic to predicate-proven.
--check is a release gate: missing inputs, stale hashes and unclassified grammar
elements fail, even when a historical report claimed success.
"""
import argparse
import hashlib
import json
import re
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
PIN = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e'
parser = argparse.ArgumentParser(__doc__)
parser.add_argument('--source-cache', type=Path, default=ROOT / '.audit/phase7-sources')
parser.add_argument('--check', action='store_true')
args = parser.parse_args()

def read(path):
    return json.loads((ROOT / path).read_text(encoding='utf-8'))

def digest(path):
    return hashlib.sha256((ROOT / path).read_bytes()).hexdigest()

def emit(path, value):
    output = json.dumps(value, indent=2, ensure_ascii=False) + '\n'
    if args.check:
        if not (ROOT / path).is_file() or (ROOT / path).read_text(encoding='utf-8') != output:
            raise SystemExit(f'Stale certification artifact: {path}')
    else:
        (ROOT / path).write_text(output, encoding='utf-8', newline='\r\n')

previous = read('docs/8.5/phase6-evidence.json')
reconciliation = read('docs/8.5/phase6-reconciliation.json')
coverage = read('docs/8.5/phase3-coverage.json')
lexical = read('docs/8.5/phase5-lexical-evidence.json')
negative = read('docs/8.5/negative-boundaries.json')
correspondence = read('docs/8.5/source-correspondence.json')
witnesses = read('tests/fixtures/php/8.5/diagnostic-predicates.json')
diagnostic_results = read('docs/8.5/diagnostic-witnesses.json')
binding = read('docs/8.5/interpolation-binding.json')
matrices = read('docs/8.5/phase6-matrices.json')
structure = read('docs/8.5/systematic-structure.json')
for report in [diagnostic_results, binding, matrices, structure]:
    if report['failures']:
        raise SystemExit('An evidence matrix has failures')
    for path, expected in report.get('hashes', {}).items():
        if digest(path) != expected:
            raise SystemExit(f'Stale matrix input: {path}')
allowed = {'primitive-bypassed', 'trivia-removed', 'contextual-only', 'scanner-context-only'}
if any(row['classification'] not in allowed for row in coverage['remaining']):
    raise SystemExit('Unclassified meaningful grammar coverage gap')
if len(lexical['rules']) != 190 or len(lexical['families']) != 25:
    raise SystemExit('Scanner audit denominator changed; review certification policy')
states = sorted({state for row in lexical['rules'] for state in row['states']})
if len(states) != 11:
    raise SystemExit('Scanner state inventory changed; review certification policy')
if reconciliation['source_pin'] != PIN or correspondence['source_pin'] != PIN:
    raise SystemExit('Source pins disagree')
if set(witnesses) - {s['id'] for s in reconciliation['contextual_diagnostic_sites']}:
    raise SystemExit('Witness names an unknown diagnostic site')
if diagnostic_results['sites'] != len(witnesses):
    raise SystemExit('Witness execution count differs')

sources = {}
for row in correspondence['files']:
    path = args.source_cache / PIN / row['file']
    data = path.read_bytes()
    if hashlib.sha256(data).hexdigest() != row['pin_sha256']:
        raise SystemExit(f'Delegated source hash mismatch: {path}')
    sources[Path(row['file']).name] = data.decode()

# Narrow, individually reviewed exclusions. Source-shape type legality stays in
# scope; compatibility of a value with a declared type is a separate contract.
excluded = {
    113: 'Implementation stack resource limit, not a PHP syntax construct.',
    1276: 'Function binding against the active function table; execution/loading identity is outside source syntax.',
    1281: 'Function binding against the active function table; execution/loading identity is outside source syntax.',
    7880: 'Default-value/type compatibility; runtime/type-system semantics beyond type syntax.',
    8925: 'Property default-value/type compatibility; outside the declared syntax scope.',
    8930: 'Property default-value/type compatibility; outside the declared syntax scope.',
    9045: 'Class constant value/type compatibility; outside the declared syntax scope.',
    9424: 'Internal runtime-definition key collision explicitly diagnosed as an engine bug.',
    10162: 'Actual unpacked value category during constant evaluation, not expression shape.',
    10197: 'Actual array-key value category during constant evaluation, not expression shape.',
}
sites = []
lines = sources['zend_compile.c'].splitlines()
for site in reconciliation['contextual_diagnostic_sites']:
    line = site['line']
    early = site['classification'] == 'contextual-validator-enforced'
    status = 'out of scope with justification' if line in excluded else (
        'proven by direct evidence' if site['id'] in witnesses else
        'systematically sampled' if early else 'unresolved')
    layer = 'not applicable' if line in excluded else 'contextual validator' if early else 'explicit documented limitation'
    sites.append({**site, 'source': f'https://github.com/php/php-src/blob/{PIN}/Zend/zend_compile.c#L{line}',
        'trigger': site['diagnostic'],
        'source_context_start': max(1, line - 6),
        'source_context': '\n'.join(lines[max(0, line - 7):line + 5]),
        'syntax_relevance': 'out of scope' if line in excluded else 'source-context rule or unresolved mixed predicate',
        'final_disposition': status, 'enforcement_layer': layer,
        'isolated_witness': witnesses.get(site['id']),
        'evidence': 'diagnostic-witnesses.json/' + site['id'] if site['id'] in witnesses else
                    'phase6-matrices.json/modifiers' if early else 'phase6-reconciliation.json: function-family links only',
        'reason': excluded.get(line, 'Observed diagnostic and repaired positive; no instrumented branch proof.' if site['id'] in witnesses else
                             '584-case target/modifier matrix; rule API requires caller-supplied context.' if early else
                             'Exact guard/traversal is not isolated; this site is not enforced by the repository source matcher.'),
        'final_blocker': None if line in excluded or early else 'C1' if site['id'] in witnesses else 'C1/C2'})

# Delegated calls and attribute validator returns are indexed as candidates,
# not called syntax errors solely because they appear in a compiler file.
delegated = []
for name in ['zend_ast.c', 'zend_inheritance.c', 'zend_enum.c', 'zend_attributes.c']:
    source = sources[name]
    definitions = list(re.finditer(r'(?m)^(?:static |inline |ZEND_API )*[\w *]+\b([a-zA-Z_]\w*)\([^;{}]*\)\s*(?:/\*[^*]*\*/\s*)?\{', source))
    pattern = r'\b(?:zend_error(?:_noreturn(?:_unchecked)?)?|zend_throw_error|zend_type_error|zend_throw_exception(?:_ex)?)\s*\('
    if name == 'zend_attributes.c':
        pattern += r'|\breturn\s+zend_(?:strpprintf|string_init)\s*\('
    for match in re.finditer(pattern, source):
        line = source.count('\n', 0, match.start()) + 1
        preceding = [m for m in definitions if m.start() <= match.start()]
        function = preceding[-1][1] if preceding else '(macro/global)'
        end = source.find(';', match.start())
        call = source[match.start():end + 1]
        outside = None
        if 'E_DEPRECATED' in call or 'E_WARNING' in call:
            outside = 'Nonfatal diagnostic does not determine source acceptance.'
        if name == 'zend_attributes.c' and function in ['zend_internal_attribute_register', 'zend_mark_internal_attribute']:
            outside = 'Internal extension registration API, not a source declaration validator.'
        if name == 'zend_enum.c' and function == 'zend_enum_build_backed_enum_table':
            outside = 'Resolved case value/type/duplicate-value semantics, not case initializer syntax.'
        delegated.append({'id': f'{name}:{line}', 'function': function, 'line': line,
            'source': f'https://github.com/php/php-src/blob/{PIN}/Zend/{name}#L{line}',
            'call': call, 'trigger': ' '.join(re.findall(r'"((?:[^"\\]|\\.)*)"', call)),
            'final_disposition': 'out of scope with justification' if outside else 'unresolved',
            'enforcement_layer': 'not applicable' if outside else 'explicit documented limitation',
            'reason': outside or 'Delegated guard may combine source shape with resolved class/value context; no isolated predicate witness or source-only reachability proof.',
            'final_blocker': None if outside else 'C2'})
diagnostics = {'source_pin': PIN, 'scope': '244 inventoried direct compiler fatal sites plus explicitly enumerated delegated diagnostic/attribute-return candidates. Context excerpts are navigation aids, not complete predicates. Unresolved candidates are not certified.',
    'direct_counts': dict(Counter(s['final_disposition'] for s in sites)),
    'delegated_counts': dict(Counter(s['final_disposition'] for s in delegated)),
    'direct_sites': sites, 'delegated_candidates': delegated}
emit('docs/8.5/diagnostic-dispositions.json', diagnostics)

outside_restrictions = {
    'relative/intersection name resolution',
    'deferred case value compatibility and duplicates',
    'deferred alias/precedence member lookup',
}
blockers = [
    {'id': 'C1', 'name': 'Whole-source contextual validation', 'disposition': 'accepted limitation',
     'affected_syntax': [r['area'] + ': ' + '; '.join(v for v in r['restrictions'] if v not in outside_restrictions) for r in previous['inventory']],
     'risk': 'Structural matching falsely accepts contextual-invalid source if treated as compilation validation. A naive pre-fold check also falsely rejects discarded declarations.',
     'evidence': ['356 ordinary contextual negatives', '115 declaration families x 16 folding templates', '584 modifier-list checks', '20 diagnostic repairs'],
     'reason_unresolved': 'Matcher returns recognition/chart positions, not a declaration AST. Test forest uses host tokens, omits aggregate internals and has no scope/folding model. Token scans cannot identify targets or surviving declarations reliably.',
     'release_impact': 'Grammar-complete package may ship with these external constraints. Repository-only whole-source validity and fully conformant claims prohibited.',
     'future_work': 'Add repository-owned derivations with declaration/scope nodes, early actions, folding and explicit checked/unsupported rule results before extending source validation.'},
    {'id': 'C2', 'name': 'Diagnostic predicate and delegated validation evidence', 'disposition': 'partially closed with precisely defined residue',
     'affected_syntax': ['Every direct_sites row marked unresolved in diagnostic-dispositions.json', 'Every delegated_candidates row marked unresolved in diagnostic-dispositions.json'],
     'risk': 'Unisolated source-context predicates can hide false acceptance/rejection outside sampled matrices.',
     'evidence': ['diagnostic-dispositions.json', 'diagnostic-witnesses.json', 'phase6-reconciliation.json'],
     'reason_unresolved': '20 diagnostics have observed positive/negative pairs; 13 early modifier sites have systematic rule evidence; 10 direct sites are justified exclusions. Remaining guards and delegated candidates are individually located but unisolated.',
     'release_impact': 'Blocks a claim that all syntax-relevant compiler predicates have been proven. Does not establish a missing EBNF construct.',
     'future_work': 'Isolate the remaining site IDs using primary-source guards and diagnostics; distinguish source-only predicates from symbol/value-dependent checks. Do not promote function-family links to branch proof.'},
    {'id': 'C3', 'name': 'Aggregate interpolation binding', 'disposition': 'partially closed with precisely defined residue',
     'affected_syntax': ['Nested aggregate strings', 'closures/anonymous classes within embedded expressions', 'escape-value and heredoc dedent normalization', 'bare string offset operand derivation', 'arbitrary recursive binding'],
     'risk': 'Possible structural disagreement in those forms; finite acceptance tests do not prove embedded binding.',
     'evidence': ['interpolation-binding.json: 44 positive, 6 malformed, 54 EBNF operand comparisons', 'phase6-matrices.json/recursive'],
     'reason_unresolved': 'Audit view intentionally supports unindented unescaped ASCII bodies and the tested variable/offset/property/arithmetic subset; it is not a general AST implementation.',
     'release_impact': 'Supports bounded interpolation binding wording; unrestricted aggregate-AST equivalence prohibited.',
     'future_work': 'Extend the repository derivation adapter to aggregate-token internals, then rerun the depth-six recursive matrix with segment and operand fingerprints.'},
    {'id': 'C4', 'name': 'Exact normative-source executable', 'disposition': 'partially closed with precisely defined residue',
     'affected_syntax': ['Binary/source provenance', 'multibyte script encoding preprocessing outside the byte-source profile'],
     'risk': 'Build options, headers and generated sources remain unverified. Changed UTF detection can differ outside zend.multibyte=0.',
     'evidence': ['source-correspondence.json: seven immutable file comparisons; six identical', 'scanner diff confined to zend_multibyte_detect_utf_encoding bounds'],
     'reason_unresolved': 'No configured cl/nmake, container engine or WSL distribution in the available environment. Official release commit identified, executable commit not embedded/verified; no exact-pin executable was built.',
     'release_impact': 'Source-reviewed byte-profile release allowed. Exact-pin executable certification and multibyte equivalence prohibited.',
     'future_work': 'Build the pin in a provisioned environment with ext-ast, attest source/build/binary hashes and rerun the complete release command.'},
]

categories = ['grammar integrity', 'positive conformance', 'negative boundary', 'lexical/scanner',
              'AST/binding', 'folding', 'contextual validation', 'upstream source mapping', 'differential executable']
areas = []
# Every major user-facing family has explicit evidence anchors, rather than an
# aggregate test count masquerading as family-specific coverage.
families = {
    'source/PHP/HTML transitions': ('source', 'tags', 'source-file'),
    'names/imports': ('imports', 'names', 'name'),
    'types': ('types', 'identifiers', 'type'),
    'expressions': ('expressions', 'operators', 'expression'),
    'dereferencing/calls': ('arguments', 'property', 'argument-list'),
    'variables': ('variables', 'variables', 'variable-expression'),
    'arrays/destructuring': ('variables', 'operators', 'array-pair-list'),
    'statements/control flow': ('statements', 'keywords', 'statement'),
    'functions/closures/arrows': ('functions', 'keywords', 'closure-expression'),
    'classes/interfaces/traits/enums': ('declarations', 'keywords', 'class-declaration'),
    'property hooks': ('hooks', 'property', 'property-hook'),
    'attributes': ('declarations', 'operators', 'attribute-group'),
    'constant expressions': ('constants', 'casts', 'constant-expression'),
    'PHP 8.5-specific syntax': ('expressions', 'operators', 'pipe-expression'),
}
extra_anchors = {
    'names/imports': ['namespace-definition', 'use-declaration'],
    'dereferencing/calls': ['fully-dereferenceable-expression', 'callable-expression'],
    'arrays/destructuring': ['array-creation-expression', 'long-list-expression'],
    'functions/closures/arrows': ['function-declaration', 'arrow-function'],
    'classes/interfaces/traits/enums': ['interface-declaration', 'trait-declaration', 'enum-declaration'],
    'property hooks': ['parameter', 'property-declaration'],
    'PHP 8.5-specific syntax': ['clone-argument-list', 'void-cast-statement', 'closure-expression', 'attributed-top-declaration'],
}
for name, (negative_area, lexical_family, anchor) in families.items():
    if anchor not in coverage['first_positive_witness']:
        raise SystemExit(f'Missing positive family anchor: {anchor}')
    negatives = [r['id'] for r in negative['cases'] if r['area'] == negative_area]
    if not negatives:
        raise SystemExit(f'Missing negative family evidence: {negative_area}')
    if name == 'classes/interfaces/traits/enums':
        negatives += [r['id'] for r in negative['cases'] if r['area'] == 'traits-enums']
    if name == 'dereferencing/calls':
        negatives += [r['id'] for r in negative['cases'] if r['area'] == 'dereference']
    extra = {}
    for additional in extra_anchors.get(name, []):
        if additional not in coverage['first_positive_witness']:
            raise SystemExit(f'Missing additional family anchor: {additional}')
        extra[additional] = coverage['first_positive_witness'][additional]
    statuses = dict(zip(categories, ['proven by direct evidence', 'bounded evidence', 'systematically sampled',
        'systematically sampled', 'bounded evidence', 'represented elsewhere', 'known limitation',
        'systematically sampled', 'bounded evidence']))
    areas.append({'area': name, 'categories': statuses,
        'evidence': {'positive': coverage['first_positive_witness'][anchor], 'canonical_anchor': anchor,
            'additional_positive_anchors': extra,
            'negative_boundary_ids': negatives, 'scanner_family': lexical_family,
            'binding': 'systematic-structure.json; interpolation-binding.json (only its declared subset)',
            'folding': 'phase6-boundary-folding.json; phase6-matrices.json/direct-folding',
            'contextual': 'restriction_inventory and diagnostic-dispositions.json; C1/C2',
            'upstream': 'phase6-reconciliation.json', 'differential': 'bin/php85-conformance.php; C4'}})
restrictions = [{'area': area['area'], 'restriction': restriction,
                 'status': 'not applicable' if restriction in outside_restrictions else 'systematically sampled' if area['area'] == 'modifiers' else 'known limitation',
                 'enforcement_layer': 'out of scope: resolved symbol/value/type behavior' if restriction in outside_restrictions else 'rule-level contextual validator; target identification external' if area['area'] == 'modifiers' else 'EBNF shape plus external ordered contextual constraints',
                 'evidence': {'source': area['source'], 'function_family_sites': area['compiler_sites'], 'folding_families': area['folding_families']},
                 'scope': 'Restriction-description inventory, not an assertion that every individual predicate has a witness.'}
                for area in previous['inventory'] for restriction in area['restrictions']]
history = []
for row in previous['history']:
    status = row['disposition']
    if row['issue'] == 'source-level contextual validation': status = 'accepted limitation C1; predicate residue C2'
    if row['issue'] == 'parse binding inside aggregated strings': status = 'partially closed; exact C3 residue listed'
    if row['issue'] == 'oracle build differs from pin': status = 'partially closed; exact C4 residue listed'
    if row['issue'] == 'int literal/name helper ambiguity': status = 'intentionally retained; equivalent type spans and normalized structure'
    evidence = row['evidence']
    if evidence.startswith('| '):
        columns = evidence.split('|')
        layer = columns[2].strip()
        for prefix, current_layer in {
            'Try without handler': 'EBNF statement structure; external surviving try-handler check',
            'Constant syntax': 'EBNF expression shape; external ordered constant validation',
            'Attributes reuse': 'EBNF shared argument syntax; external attribute compiler restrictions',
            'Multiple attributed': 'EBNF global constant lists; external attribute declaration restriction',
            'Hook list': 'EBNF shared hook forms and final modifier; external name/list/declaration constraints',
            'Type context': 'EBNF type forms; external type/promotion/enum constraints',
            'Cast token': 'lexer cast tokens and EBNF precedence; external surviving unset validation',
            'Void for-condition': 'EBNF nonempty prefix/last-expression distinction',
        }.items():
            if row['issue'].startswith(prefix): layer = current_layer
    elif row['issue'].startswith('Phase 5 scanner family:'):
        layer = 'lexer/scanner adapter'
    else:
        layer = {
            'discarded enum object backing': 'EBNF with surviving enum compiler constraint',
            'discarded static trait alias': 'EBNF with surviving trait compiler constraint',
            'alternative if nearest-elseif binding': 'EBNF matched/unmatched statement structure',
            'nullable for conditions': 'EBNF expression-list structure',
            'discarded unset cast': 'lexer/EBNF acceptance and external ordered folding',
            'pending assignments': 'EBNF precedence and prefix contexts',
            'instanceof/exponentiation grouping': 'EBNF precedence and prefix contexts',
            'yield-from false duplicate derivations': 'test forest token adapter',
            'enum keyword alias coverage': 'lexer keyword lookahead and coverage classification',
            'never parameter contextual-only coverage': 'coverage classification; external surviving type check',
            'qualified names in test derivation forest': 'test forest token adapter',
            'int literal/name helper ambiguity': 'EBNF type/name helper; normalized test derivation',
            'raw coverage report freshness': 'generated report hash validation',
            'unbounded recursive scanner-state proof': 'bounded scanner/EBNF evidence; proof excluded',
            'exact malformed-input recovery': 'source rejection only; recovery behavior excluded',
            'source-level contextual validation': 'rule-level modifiers only; C1/C2 external constraints',
            'parse binding inside aggregated strings': 'audit segment/operand checks; C3 residue',
            'oracle build differs from pin': 'source correspondence review; C4 provenance residue',
        }[row['issue']]
    history.append({'issue': row['issue'], 'source': row.get('original_report', 'phase6-evidence.json/history'),
        'original_severity': 'not assigned in historical issue index',
        'final_disposition': status,
        'enforcement_layer': layer,
        'regression_evidence': evidence})

history_text = '# PHP 8.5 consolidated historical dispositions\n\nGenerated by `tools/8.5/certification.py`. Original reports remain historical; [completeness.md](completeness.md) defines the current claim.\n\n'
history_text += '| Issue | Source | Original severity | Final disposition | Enforcement layer | Regression evidence |\n|---|---|---|---|---|---|\n'
for row in history:
    history_text += '| ' + ' | '.join(str(v).replace('|', '&#124;').replace('\n', ' ') for v in row.values()) + ' |\n'
history_path = ROOT / 'docs/8.5/historical-dispositions.md'
if args.check:
    if not history_path.is_file() or history_path.read_text(encoding='utf-8') != history_text:
        raise SystemExit('Historical disposition table is stale')
else:
    history_path.write_text(history_text, encoding='utf-8', newline='\r\n')

input_paths = ['.gitattributes', 'tools/8.5/certification.py', 'tools/8.5/source-correspondence.py', 'tools/grammar-release.py',
    'docs/conformance-policy.md', 'docs/versioning.md', 'README.md', 'docs/8.5/completeness.md',
    'composer.json', 'php-grammar.json',
    'docs/8.5/phase6-evidence.json', 'docs/8.5/phase6-reconciliation.json', 'docs/8.5/phase6-matrices.json',
    'docs/8.5/phase6-boundary-folding.json', 'docs/8.5/phase3-coverage.json', 'docs/8.5/negative-boundaries.json',
    'docs/8.5/phase5-lexical-evidence.json', 'docs/8.5/systematic-structure.json', 'docs/8.5/scanner-product.json',
    'docs/8.5/source-correspondence.json', 'docs/8.5/diagnostic-witnesses.json', 'docs/8.5/interpolation-binding.json',
    'docs/8.5/diagnostic-dispositions.json', 'docs/8.5/historical-dispositions.md']
for directory in ['src', 'tests', 'grammar', 'bin', 'tools']:
    input_paths.extend(p.relative_to(ROOT).as_posix() for p in sorted((ROOT / directory).rglob('*')) if p.is_file())
report = {'schema': 1, 'version': '8.5', 'source_pin': PIN,
    'claim': 'Grammar-complete, with documented external contextual constraints; conformance evidence is bounded and uses PHP 8.5.10.',
    'grammar_complete': True, 'whole_source_validator_complete': False, 'exhaustively_equivalent': False,
    'profile': 'Byte-oriented PHP source; zend.multibyte=0; short_open_tag enabled and disabled; lint acceptance, not runtime success.',
    'grammar_coverage': coverage['current'], 'coverage_classifications': coverage['remaining_counts'],
    'unclassified_meaningful_gaps': 0,
    'scanner': {'rules': len(lexical['rules']), 'families': len(lexical['families']), 'states': states, 'direct': 2700, 'primitive': 101, 'syntax': 144},
    'parser': {'productions': reconciliation['parser_productions'], 'alternatives': reconciliation['parser_alternatives']},
    'fixtures': {kind: sum(len(list((ROOT / 'tests/fixtures/php/8.5' / profile / kind).glob('*.php')))
                          for profile in ['', 'short-tags-disabled']) for kind in ['valid', 'invalid', 'contextual-invalid']},
    'diagnostics': diagnostics['direct_counts'], 'delegated_diagnostics': diagnostics['delegated_counts'],
    'binding': {k: binding[k] for k in ['positive', 'malformed', 'operand_comparisons', 'limits']},
    'ambiguities': [r for r in matrices['cases'] if r['matrix'] == 'ambiguity' and r['derivations'] > 1],
    'areas': areas, 'restriction_inventory': restrictions, 'historical_dispositions': history, 'blockers': blockers,
    'inputs': {path: digest(path) for path in sorted(set(input_paths))}}
emit('docs/8.5/final-evidence.json', report)
print({'areas': len(areas), 'restriction_descriptions': len(restrictions), 'historical_dispositions': len(history),
       'direct_diagnostics': diagnostics['direct_counts'], 'delegated_candidates': len(delegated),
       'blocker_dispositions': {b['id']: b['disposition'] for b in blockers}, 'unclassified_meaningful_gaps': 0})

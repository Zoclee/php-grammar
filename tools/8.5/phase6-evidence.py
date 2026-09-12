"""Generate the consolidated Phase 6 evidence, history and finite blocker index."""
import argparse
import hashlib
import json
import re
from pathlib import Path

root = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(__doc__)
parser.add_argument('--check', action='store_true')
args = parser.parse_args()

def read(name):
    return json.loads((root / name).read_text(encoding='utf-8'))

reconciliation = read('docs/8.5/phase6-reconciliation.json')
boundaries = read('tests/fixtures/php/8.5/parser-compiler-boundaries.json')['cases']
negative = read('docs/8.5/negative-boundaries.json')
lexical = read('docs/8.5/phase5-lexical-evidence.json')

# Each list is the audited restriction inventory, not a claim that each rule
# has a repository implementation. The API currently implements early modifiers.
areas = {
    'modifiers': ('class|anonymous|modifier', ['duplicate modifiers', 'conflicting visibility', 'abstract/final', 'anonymous abstract/final', 'target-specific readonly/static/final', 'set visibility', 'promoted-property modifiers']),
    'types': ('type|typename|parameter-(void|never)', ['void/never by position', 'static by position', 'self/parent scope', 'nullable mixed/void/never/null', 'duplicate/redundant union/intersection', 'DNF structural grouping', 'relative/intersection name resolution']),
    'parameters-functions': ('parameter|params|promotion|func_decl|return|generator|yield|closure_(uses|binding)', ['constructor-only promotion', 'abstract promotion', 'variadic promotion', 'variadic order/default', 'duplicate parameter names', 'by-reference parameters/returns/captures', 'void/never returns', 'generator and yield-from restrictions', 'NoDiscard and reserved declarations']),
    'properties-hooks': ('prop|hook', ['readonly type/default/static restrictions', 'private final properties', 'abstract ordinary properties', 'get/set names', 'duplicate/empty hooks', 'get/set parameter count/reference/default/variadic', 'abstract/final/body combinations', 'interface property/default/visibility restrictions', 'promotion/hooks interaction', 'parent hook scope']),
    'enums': ('enum', ['backing int/string', 'backed case requires value', 'unbacked case forbids value', 'forbidden properties/abstract methods', 'case declarations in other class kinds', 'deferred case value compatibility and duplicates']),
    'traits': ('trait|alias|insteadof', ['method-target alias modifiers before folding', 'static/abstract aliases after folding', 'qualified precedence reference', 'class-name lists', 'interface trait use', 'deferred alias/precedence member lookup']),
    'writes': ('assign|writ|list|foreach|unset|isset|dim|var_inner|global|static_var|short_circuit', ['assignment/compound/inc-dec writable operands', 'reference and nullsafe restrictions', '$this/$GLOBALS writes', 'unset calls/empty dimensions', 'isset expression/empty dimensions', 'foreach key/value/reference targets', 'destructuring empty/keyed/mixed syntax/unpack/literal targets']),
    'calls-attributes': ('args|call|attribute|pipe|fcc', ['named/positional/unpack order', 'duplicate named arguments', 'constructor/nullsafe callable conversion', 'attribute unpack/conversion/constant arguments', 'unparenthesized arrow pipe operand']),
    'constant-folding': ('const_expr|eval_const|ct_eval|cast|conditional', ['ordinary versus initializer traversal', 'removed unset versus real cast', 'invalid surviving constant-expression nodes', 'static/noncapturing closure requirement', 'class and callable name folding', 'array unpack/offset errors during evaluation', 'warning-sensitive folding']),
    'control-scopes': ('namespace|label|goto|break|switch|match|try|declare|halt|class_name|class_fetch|already_in_use|begin_|bind_function', ['dangling else/alternative binding', 'break/continue depth', 'duplicate labels/defaults', 'goto into loops', 'try requires catch/finally on compilation', 'namespace placement/mixing/import collisions', 'encoding/strict_types/declare', 'halt outermost scope', 'reserved/special names and nested declarations']),
}
inventory = []
for area, (pattern, restrictions) in areas.items():
    cases = [c for c in boundaries if re.search(pattern, c['id'] + ' ' + c['evidence'])]
    sites = [s['id'] for s in reconciliation['contextual_diagnostic_sites'] if re.search(pattern, s['function'])]
    inventory.append({'area': area, 'restrictions': restrictions,
        'classifications': ['contextual-validator-enforced', 'EBNF-enforced'] if area == 'modifiers' else ['EBNF-enforced', 'folding-sensitive', 'documented semantic/out-of-scope'],
        'implementation': 'Php85ModifierValidator: early modifier-list rules only' if area == 'modifiers' else 'Structural EBNF plus contextual documentation and PHP differential oracle; no complete repository rule implementation',
        'positive': [c['fixtures']['discarded'] for c in cases],
        'structural_negative': [c['negative'] for c in negative['cases'] if re.search(pattern, c['area'] + ' ' + c['production'])],
        'contextual': [c['fixtures']['live'] for c in cases if not c['live_valid']],
        'folding_families': [c['id'] for c in cases], 'compiler_sites': sites,
        'lexical': 'phase5-lexical-evidence.json: all cases pass through the repository lexer',
        'binding': 'systematic-structure.json; phase6-matrices.json/ambiguity',
        'source': 'phase6-reconciliation.json (pinned parser alternatives/compiler diagnostic locations)',
        'scope_note': 'Links select area-related evidence and may overlap. Positive discarded witnesses prove parser acceptance, not live declaration validity.'})

history = []
text = (root / 'docs/8.5/audit-remediation.md').read_text(encoding='utf-8')
table = text.split('## Issues and regressions', 1)[1].split('## Contextual constraints', 1)[0]
superseded = ['Try without handler', 'Constant syntax', 'Attributes reuse', 'Multiple attributed', 'Hook list', 'Type context', 'Cast token', 'Void for-condition']
for line in table.splitlines():
    if not line.startswith('| ') or line.startswith('| Issue'):
        continue
    issue = line.split('|')[1].strip()
    history.append({'issue': issue, 'original_report': 'audit-remediation.md#issues-and-regressions',
                    'disposition': 'superseded with explanation' if any(issue.startswith(s) for s in superseded) else 'fixed',
                    'evidence': line,
                    'current': 'The original proposed structural restriction was subsequently corrected or moved to the ordered contextual/folding layer; current fixtures, canonical anchors and function-family dispositions are indexed in phase6-reconciliation.json.' if any(issue.startswith(s) for s in superseded) else
                               'Retained regression fixtures and current ordinary/structure/scanner runs exercise the correction; historical counts do not describe the current corpus.'})
for issue, status, evidence in [
    ('discarded enum object backing', 'fixed', 'boundary-enum-object-discarded.php'),
    ('discarded static trait alias', 'fixed', 'boundary-alias-static-discarded.php'),
    ('alternative if nearest-elseif binding', 'fixed', 'parser-structure-invalid.json; systematic-structure.json'),
    ('nullable for conditions', 'fixed', 'remediation-folding-evidence.json: for-empty/for-leading-comma/for-trailing-comma'),
    ('discarded unset cast', 'folding-sensitive and tested', 'remediation-unset-and.php; phase6-matrices.json/direct-folding'),
    ('pending assignments', 'fixed', 'systematic-structure.json/pending-prefix'),
    ('instanceof/exponentiation grouping', 'fixed', 'systematic-structure.json/instanceof-power-prefix'),
    ('yield-from false duplicate derivations', 'fixed', 'DerivationForest token adapter; systematic-structure.json'),
    ('enum keyword alias coverage', 'scanner-context-only and tested', 'reserved-non-modifiers/alternative:25; phase5-lexical-evidence.json'),
    ('never parameter contextual-only coverage', 'superseded with explanation', 'boundary-parameter-never-live/retained/discarded.php; discarded declaration is a valid positive witness'),
    ('qualified names in test derivation forest', 'fixed', 'phase6-matrices.json/ambiguity name cases'),
    ('int literal/name helper ambiguity', 'intentionally outside syntax scope', 'phase6-matrices.json: two raw derivations, one normalized structure in two class-member cases; lexical/helper interpretation only'),
    ('raw coverage report freshness', 'fixed', 'Regenerated Phase 3 and dependent lexical evidence hashes'),
    ('unbounded recursive scanner-state proof', 'superseded with explanation', '156 pairwise bounded cases; arbitrary-depth mathematical proof is not a release criterion'),
    ('exact malformed-input recovery', 'intentionally outside syntax scope', '16 malformed comparisons reject; no recovery AST/diagnostic text contract'),
    ('source-level contextual validation', 'still unresolved', 'C1/C2'),
    ('parse binding inside aggregated strings', 'still unresolved', 'C3'),
    ('oracle build differs from pin', 'still unresolved', 'C4'),
]:
    history.append({'issue': issue, 'disposition': status, 'evidence': evidence})
for name, family in lexical['families'].items():
    history.append({'issue': 'Phase 5 scanner family: ' + name,
                    'disposition': 'fixed' if family['status'] == 'fixed' else 'scanner-context-only and tested',
                    'evidence': 'phase5-lexical-evidence.json/families/' + name,
                    'current': 'Direct byte/token cases plus scanner-product.json; represented-differently dispositions remain intentional token abstractions.'})

blockers = [
    {'id': 'C1', 'behavior': 'Whole-source contextual checks beyond early modifier lists are not implemented by a repository-owned source validator.',
     'area': ['early encoding-declaration literal-AST check (compile:6922)', 'types/parameters/returns', 'properties/hooks/promotion', 'enums/traits', 'write/reference/call contexts', 'constants/attributes', 'control flow/namespaces/declaration scope'],
     'impact': 'false accept if structural matching is misused as full source validation',
     'why': 'These parser-valid forms can fail compilation; generic EBNF matching intentionally accepts contextual-invalid fixtures.',
     'evidence': ['115 boundary families', '70 direct folding cases', '584 early modifier cases', '356 contextual-negative ordinary files'],
     'closure': 'Add a repository source/derivation-to-context representation and ordered folding-aware validation for the listed areas; preserve early modifier checks and distinguish checked from unsupported rules. Do not use PHP lint as the implementation.'},
    {'id': 'C2', 'behavior': 'Diagnostic predicate isolation and indirect/deferred checks are not established by function-family links.',
     'area': ['zend_compile.c direct diagnostic sites listed below', 'zend_ast.c constant-expression evaluation', 'zend_inheritance.c declaration-time hook/property validation', 'zend_enum.c forbidden-member checks', 'zend_attributes.c internal attribute checks'],
     'site_ids': [s['id'] for s in reconciliation['contextual_diagnostic_sites'] if s['blocker'] == 'C2'],
     'impact': 'possible false accept/reject at contextual/folding layer; no observed mismatch in current matrices',
     'why': 'One family witness does not prove every diagnostic guard, traversal order or delegated helper.',
     'evidence': ['244 direct fatal sites with line/function/diagnostic', '115 live/retained/discarded families', 'six pinned PHPT reductions'],
     'closure': 'Attach a diagnostic-specific positive/negative witness or justified out-of-scope disposition to each listed site; trace delegated helpers in the named files, separating class loading, actual type compatibility and runtime constant evaluation from source-only checks.'},
    {'id': 'C3', 'behavior': 'Normalized AST/derivation equivalence within aggregate interpolated-string/heredoc tokens is not measured.',
     'area': ['Lexer/StringSyntax embedded expressions', 'encaps_var and encaps_var_offset', 'DerivationForest string-token adapter'],
     'impact': 'possible structural disagreement; bounded whole-source acceptance currently agrees',
     'why': 'The production matcher sees one string token. Recursive acceptance does not expose embedded operand spans to the structure matrix.',
     'evidence': ['2700 lexical cases', '482 scanner-product cases', '156 bounded recursive combinations', '15342 structure cases outside aggregated-string internals'],
     'closure': 'Expose an audit-only embedded-token/derivation view, then compare Zend encapsulation operand fingerprints for the existing depth-six matrix; preserve the public aggregate-token contract.'},
    {'id': 'C4', 'behavior': 'Executable observations use PHP 8.5.10 rather than a build of the pinned PHP-8.5 revision.',
     'area': ['scanner/parser/compiler source-to-oracle correspondence'],
     'impact': 'possible false accept/reject or structural disagreement if the pin differs in audited syntax paths',
     'why': 'The three source hashes establish source identity, not executable identity.',
     'evidence': ['tools/8.5/source-lock.json', 'all differential report PHP versions', 'source pin 7a4c62795365ed6a97a0184c96375b9fb4d53b1e'],
     'closure': 'Build the exact pin with ext-ast and rerun the matrices, or document all parser/scanner/compiler changes between the release and pin with targeted evidence.'},
]
hash_paths = ['tools/8.5/phase6-evidence.py', 'docs/8.5/phase6-reconciliation.json', 'docs/8.5/phase6-matrices.json',
              'docs/8.5/phase6-upstream-tests.json', 'docs/8.5/phase5-lexical-evidence.json', 'docs/8.5/systematic-structure.json',
              'docs/8.5/audit-remediation.md']
report = {'source_pin': reconciliation['source_pin'],
          'layers': ['source bytes', 'scanner/tokenization', 'parser acceptance and early parser actions', 'parse/AST binding',
                     'constant folding/discarded branches', 'surviving contextual declaration/write/type checks',
                     'source validity within the configured PHP profile'],
          'architecture': 'Rule-level Php85ModifierValidator accepts identified canonical modifier spellings/target, never raw source. It runs before folding. Null certifies only the modifier-list rule; source-level contextual closure remains C1.',
          'inventory': inventory, 'history': history, 'blockers': blockers,
          'hashes': {p: hashlib.sha256((root / p).read_bytes()).hexdigest() for p in hash_paths}}
output = json.dumps(report, indent=2, ensure_ascii=False) + '\n'
path = root / 'docs/8.5/phase6-evidence.json'
if args.check:
    if path.read_text(encoding='utf-8') != output:
        raise SystemExit('Phase 6 evidence index is stale')
else:
    path.write_text(output, encoding='utf-8', newline='\r\n')
print({'areas': len(inventory), 'history_dispositions': len(history), 'blockers': len(blockers)})

"""Reconcile the pinned parser with explicit canonical anchors; verify freshness.

This is an evidence index, not a parser-equivalence proof. RHS alternatives and
action calls are preserved individually. Missing anchors fail generation.
"""
import argparse
import hashlib
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PIN = '7a4c62795365ed6a97a0184c96375b9fb4d53b1e'

# Explicit renamed, inlined, or split productions. All other names must have
# an exact underscore-to-hyphen canonical counterpart; no fuzzy matching.
ALIASES = '''
start source-file
semi_reserved semi-reserved-identifier
ampersand variable-like parameter lexical-variable
identifier semi-reserved-identifier
namespace_name qualified-name
attribute_decl attribute
attribute_group attribute-list
attribute attribute-group
attributes attribute-groups
attributed_statement inner-declaration
attributed_top_statement attributed-top-declaration
possible_comma argument-list array-pair-list attribute-group parameter-list
inline_use_declarations inline-use-declaration-list
unprefixed_use_declarations unprefixed-use-declaration-list
use_declarations use-declaration-list
const_list constant-list declare-directive-list
catch_list catch-list
catch_name_list catch-type-list
optional_variable catch-clause
finally_statement finally-clause
unset_variables unset-variable-list
function_name name-identifier
function_declaration_statement function-declaration
is_reference parameter
is_variadic parameter
class_declaration_statement class-declaration
anonymous_class_modifiers anonymous-class class-modifiers
anonymous_class_modifiers_optional anonymous-class
trait_declaration_statement trait-declaration
interface_declaration_statement interface-declaration
enum_declaration_statement enum-declaration
enum_case_expr enum-case-initializer
extends_from extends-clause
interface_extends_list interface-extends-clause
implements_list implements-clause
for_statement matched-for-statement unmatched-for-statement
foreach_statement matched-foreach-statement unmatched-foreach-statement
declare_statement matched-declare-statement unmatched-declare-statement
switch_case_list switch-statement
case_list switch-case-list
match match-expression
non_empty_match_arm_list match-arm-list
match_arm_cond_list match-arm-condition-list
while_statement matched-while-statement unmatched-while-statement
if_stmt_without_else matched-if-statement unmatched-if-statement
if_stmt matched-if-statement unmatched-if-statement
alt_if_stmt_without_else alternative-if-statement alt-elseif-list
alt_if_stmt alternative-if-statement
non_empty_parameter_list parameter-list
attributed_parameter parameter
optional_cpp_modifiers parameter-modifiers
type_expr type
type simple-type
type_without_static simple-type-without-static
type_expr_without_static type-without-static
union_type_without_static_element union-type-without-static-element
non_empty_argument_list ordinary-argument-list
non_empty_clone_argument_list clone-argument-list
argument_no_expr argument-no-expression
global_var_list global-variable-list
global_var global-variable
static_var_list static-variable-list
static_var static-variable
class_statement_list class-member-list interface-member-list enum-member-list
attributed_class_statement property-declaration method-declaration class-constant-declaration enum-case
class_statement class-member interface-member enum-member
class_name_list name-list
trait_adaptations trait-adaptation-block
trait_adaptation_list trait-adaptation-block
absolute_trait_method_reference trait-method-reference trait-precedence
property_modifiers property-modifier-list
class_const_modifiers class-constant-modifiers
non_empty_member_modifiers property-modifier-list method-modifiers parameter-modifiers class-constant-modifiers property-hook-modifiers
member_modifier property-modifier method-modifier parameter-modifier class-constant-modifier property-hook-modifier trait-alias-modifier
property property-element
optional_property_hook_list parameter property-hook-block
optional_parameter_list property-hook
class_const_list class-constant-list
class_const_decl class-constant-element
const_decl constant-element declare-directive
echo_expr_list expression-list
echo_expr expression
for_cond_exprs for-condition-expression-list
for_exprs for-expression-list
non_empty_for_exprs nonempty-for-expression-list
new_non_dereferenceable primary-expression
expr expression
inline_function closure-expression arrow-function
fn arrow-function-header
function function-declaration closure-expression method-declaration
returns_ref function-declaration closure-expression arrow-function-header method-declaration property-hook
lexical_vars closure-expression
lexical_var_list lexical-variable-list
lexical_var lexical-variable
backticks_expr backtick-string-part-list
ctor_arguments constructor-argument-list
scalar literal dereferenceable-scalar constant class-constant
optional_expr expression return-statement break-statement continue-statement
variable_class_name fully-dereferenceable-expression
fully_dereferenceable fully-dereferenceable-expression
callable_expr callable-expression
variable variable-expression
simple_variable variable-like
possible_array_pair array-pair-list
non_empty_array_pair_list array-pair-list
encaps_list encapsulated-string-part
encaps_var encapsulated-variable
encaps_var_offset encapsulated-offset
internal_functions_in_yacc primary-expression include-expression
isset_variables isset-variable-list
'''
ALIASES = {line.split()[0]: line.split()[1:] for line in ALIASES.strip().splitlines()}
INTERNAL = {'backup_doc_comment', 'backup_fn_flags', 'backup_lex_pos'}
NOTES = {
    'start': 'Source tags, shebang and HTML are handled by the repository scanner/adapter before the statement list.',
    'expr': 'Bison precedence and associativity become explicit EBNF layers, pending-prefix contexts and closed yield-key contexts. See systematic-structure.json.',
    'statement': 'Bison dangling-else precedence becomes matched/unmatched productions; alternative-if boundaries use closed-inner-statement-list.',
    'inner_statement': 'Nested halt-compiler reduction always raises YYERROR; omitted from valid inner declarations. Malformed halt-inner regression rejects it.',
    'type_without_static': 'Builtin names other than array/callable are T_STRING/name in Zend. EBNF exposes builtin literals as informative helper alternatives as well as name.',
    'type': 'Zend type is an atomic type, while canonical type includes composites; canonical simple-type is the atomic equivalent.',
    'clone_argument_list': 'Single unnamed clone operand without comma is handled by unary clone, avoiding duplicate call/unary derivations.',
    'array_pair_list': 'Zend nullable entries and list trimming become optional EBNF entries. Surviving array/destructuring validity is contextual.',
    'property_hook_modifiers': 'Parser action filters general member modifiers to final-only; repeated final still needs early contextual rejection.',
    'class_statement_list': 'All class-like declarations share member syntax; declaration-kind legality is checked only on surviving declarations.',
    'trait_alias': 'Method-target conversion is early; static/abstract alias rejection is later and folding-sensitive. enum keyword branch is scanner-context-only.',
    'ampersand': 'Two scanner lookahead tokens have the same terminal spelling. Type/reference position disambiguates their use.',
    'encaps_list': 'Repository lexer aggregates strings and validates interpolation recursively; canonical lexical bodies are primitive-bypassed.',
    'encaps_var': 'Aggregated string token preserves bytes; StringSyntax validates embedded parser forms.',
    'encaps_var_offset': 'Numeric-string token and negative offset conversion are scanner/adapter concerns, not runtime numeric equivalence.',
    'for_cond_exprs': 'The entire condition list may be empty, but comma-separated elements may not be omitted. A nonempty prefix may contain void casts; the final condition must be an expression. See non_empty_for_exprs and the remediation for-loop fixtures.',
    'function_name': 'readonly is the additional permitted function-name keyword; method identifiers have the broader semi-reserved set. Positive audit-names-readonly-function fixture exercises the inlined name production.',
}


def branches(body):
    """Split Bison alternatives outside quoted terminals, C actions and comments."""
    result, rhs, actions = [], [], []
    depth = 0
    action = []
    tokens = re.findall(r'/\*[\s\S]*?\*/|//[^\n]*|"(?:\\.|[^"\\])*"|\'(?:\\.|[^\'\\])*\'|.', body, re.S)
    for token in tokens:
        if token.startswith(('/*', '//')):
            continue
        if token == '{':
            depth += 1
        if depth:
            action.append(token)
            if token == '}':
                depth -= 1
                if not depth:
                    actions.append(''.join(action))
                    action = []
            continue
        if token in ['|', ';']:
            result.append((' '.join(''.join(rhs).split()), '\n'.join(actions)))
            rhs, actions = [], []
            if token == ';':
                break
        else:
            rhs.append(token)
    if depth:
        raise ValueError('Unclosed C action')
    return result


def main():
    parser = argparse.ArgumentParser(__doc__)
    parser.add_argument('source_directory', type=Path)
    parser.add_argument('--check', action='store_true')
    args = parser.parse_args()
    lock = json.loads((ROOT / 'tools/php85-source-lock.json').read_text())
    sources = {}
    for name, digest in lock.items():
        data = (args.source_directory / name).read_bytes()
        if hashlib.sha256(data).hexdigest() != digest:
            raise SystemExit(f'{name}: source hash differs from pin')
        sources[name] = data.decode()
    ebnf = (ROOT / 'grammar/8.5/php.ebnf').read_text()
    names = set(re.findall(r'^([a-z][a-z0-9-]*) =', ebnf, re.M))
    coverage = json.loads((ROOT / 'docs/8.5/phase3-coverage.json').read_text())
    negative = json.loads((ROOT / 'docs/8.5/negative-boundaries.json').read_text())['cases']
    boundaries = json.loads((ROOT / 'tests/fixtures/php/8.5/parser-compiler-boundaries.json').read_text())['cases']
    compiler = json.loads((ROOT / 'docs/8.5/compiler-boundaries.json').read_text())
    text = sources['zend_language_parser.y']
    definitions = list(re.finditer(r'^([a-z][a-z_]*):', text, re.M))
    rows = []
    for i, match in enumerate(definitions):
        name = match[1]
        anchors = [] if name in INTERNAL else ALIASES.get(name, [name.replace('_', '-')])
        if set(anchors) - names:
            raise SystemExit(f'{name}: unknown anchors {set(anchors) - names}')
        body = text[match.end():definitions[i+1].start() if i+1 < len(definitions) else text.index('\n%%', match.end())]
        alternatives = []
        for number, (rhs, action) in enumerate(branches(body), 1):
            calls = sorted(set(re.findall(r'\b(zend_\w+)\s*\(', action)))
            phase = 'syntax/AST-construction'
            if name in INTERNAL:
                phase = 'internal-state'
            elif name == 'inner_statement' and 'T_HALT_COMPILER' in rhs:
                phase = 'error-only'
            elif set(calls) & set(compiler['parser_reachable_functions']):
                phase = 'parser-action/scanner-context'
            alternatives.append({'id': f'{name}:{number}', 'rhs': rhs, 'action_calls': calls, 'layer': phase,
                                 'ebnf': [] if phase == 'error-only' else anchors})
        positives = {a: coverage['first_positive_witness'].get(a, coverage.get('primitive_witnesses', {}).get(a)) for a in anchors}
        lexical_evidence = {}
        for anchor in anchors:
            if positives[anchor] is None:
                lexical_evidence[anchor] = 'phase5-lexical-evidence.json: lexical body bypass; families identifiers/keywords for names, interpolation/backticks for encapsulated forms'
        rows.append({'zend': name, 'line': text.count('\n', 0, match.start()) + 1,
                     'ebnf': anchors, 'alternatives': alternatives,
                     'abstraction': NOTES.get(name, 'Internal metadata only; no source terminal. Generator flags are subsequently consumed by contextual compilation.' if name in INTERNAL else
                                              'List recursion/options are represented by EBNF repetition/options; AST allocation and source locations are not syntax constraints.'),
                     'positive_witnesses': positives,
                     'lexical_evidence': lexical_evidence,
                     'negative_boundaries': [n['id'] for n in negative if n['production'] in anchors],
                     'contextual_families': [c['id'] for c in boundaries if re.search(r'\b' + name + r'\b', c['evidence'])],
                     'evidence_scope': 'Canonical production witnesses and boundary families; not isolated witnesses for every Zend alternative.'})
    sites = []
    for site in compiler['sites']:
        families = [c['id'] for c in boundaries if re.search(r'\b' + site['function'] + r'\b', c['evidence'])]
        classification = ('contextual-validator-enforced' if site['phase'] == 'parser-action' and 'modifier' in site['function'] else
                          'documented semantic/out-of-scope' if site['phase'] == 'implementation-resource-limit' else
                          'folding-sensitive' if site['phase'] != 'parser-action' else 'unimplemented-contextual')
        sites.append({'id': 'compile:' + str(site['line']), **site, 'classification': classification,
                      'family_evidence': families,
                      'evidence_scope': 'Function-family evidence only; individual diagnostic predicates require isolation.',
                      'blocker': None if classification == 'contextual-validator-enforced' or site['phase'] == 'implementation-resource-limit' else 'C2'})
    hashes = {p: hashlib.sha256((ROOT / p).read_bytes()).hexdigest() for p in [
        'grammar/8.5/php.ebnf', 'docs/8.5/phase3-coverage.json', 'docs/8.5/negative-boundaries.json',
        'tests/fixtures/php/8.5/parser-compiler-boundaries.json', 'docs/8.5/compiler-boundaries.json', 'tools/php85-reconcile.py']}
    report = {'source_pin': PIN, 'source_hashes': lock, 'hashes': hashes,
              'method': 'Exact-name or explicit reviewed production anchors; every RHS and action call retained. Evidence links are indexed at production/function-family granularity, not proofs of branch coverage.',
              'parser_productions': len(rows), 'parser_alternatives': sum(len(r['alternatives']) for r in rows),
              'reconciliation': rows, 'contextual_diagnostic_sites': sites,
              'remaining_classifications': [r for r in coverage['remaining'] if r['classification'] in ['contextual-only', 'scanner-context-only']]}
    output = json.dumps(report, indent=2) + '\n'
    path = ROOT / 'docs/8.5/phase6-reconciliation.json'
    if args.check:
        if path.read_text() != output:
            raise SystemExit('Phase 6 reconciliation is stale')
    else:
        path.write_text(output)
    print({'productions': len(rows), 'alternatives': report['parser_alternatives'], 'diagnostic_sites': len(sites)})


if __name__ == '__main__':
    main()

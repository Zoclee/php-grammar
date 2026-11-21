<?php
declare(strict_types=1);
require __DIR__ . '/../lib/bootstrap.php';
require dirname(__DIR__,2).'/vendor/autoload.php';
require __DIR__ . '/../lib/ChangeTraceability.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\ChangeTraceability as T;
use PhpGrammar\Ebnf\Parser;
chdir(dirname(__DIR__,2));
$out='docs/8.6/structural-certification';
$baseline=S::read("$out/baseline.json");
function preserve(string $p): string {
    global $out,$baseline;
    $dest="$out/predecessor/$p";
    if(!is_file($dest)) {
        if(hash_file('sha256',$p)!==$baseline['inputs'][$p]) throw new RuntimeException('Unexpected pre-edit bytes: '.$p);
        if(!is_dir(dirname($dest))) mkdir(dirname($dest),0777,true);copy($p,$dest);
    }
    return file_get_contents($p);
}
function put(string $p,string $s):void { file_put_contents($p,$s); }
$mode=$argv[1]??'';
if($mode==='--governance-grammar') {
    if(is_file("$out/gate-b.json")) throw new RuntimeException('Implementation already applied');
    $before=S::read("$out/hooks-before.json");if($before['errors']!==[])throw new RuntimeException('Hook oracle controls failed');
    $source=S::read('docs/8.6/ga-finalization/build-source.json')['sourceHashes'];
    $slices=[];
    foreach(['Zend/zend_language_parser.y','Zend/zend_language_scanner.l','Zend/zend_compile.c']as$p){
        $file='.audit/phase8b/source/'.$p;if(hash_file('sha256',$file)!==$source[$p])throw new RuntimeException('GA source changed');$slices[$p]=$source[$p];
    }
    $proof=['finding'=>'CA86-001-STRUCTURAL','sourceCommit'=>'8aaada3f9605d81a38b8240f93c7389dfbc77ef9','sources'=>$slices,
        'parser'=>'Zend/zend_language_parser.y:1160 property_hook uses T_STRING, not identifier/semi_reserved',
        'compiler'=>'Zend/zend_compile.c:9426 zend_get_property_hook_kind_from_name; visited-AST get/set legality remains contextual',
        'production'=>'hook-name','before'=>'"get" | "set"','after'=>'identifier','oracleControls'=>90,
        'oracleEvidence'=>['path'=>"$out/hooks-before.json",'sha256'=>hash_file('sha256',"$out/hooks-before.json")],
        'historical85'=>'Unchanged; the same historical restriction is a separate correction obligation, not new PHP 8.6 syntax.',
        'obligations'=>['case-insensitive get/set','unknown identifiers including non-ASCII bytes','keyword/numeric/qualified/variable/string negatives','live/retained/discarded','promoted parameter hooks','Markdown parity','version boundary: representation correction only']];
    put("$out/gate-b.json",S::json($proof));
    $ebnf=preserve('grammar/8.6/php.ebnf');
    $old='(* Hook-name terminals are case-insensitive, without reserving these names'."\n".'   outside hooks. Unknown names are excluded even in discarded declarations. *)'."\n".'hook-name ='."\n".'    "get" | "set" ;';
    if(!str_contains($ebnf,$old))throw new RuntimeException('Unexpected hook production');
    $ebnf=str_replace($old,'(* Hook names are lexical identifiers. Visited declarations require get/set,'."\n".'   compared case-insensitively by the compiler; see php.md. *)'."\n".'hook-name ='."\n".'    identifier ;',$ebnf);
    $ebnf=str_replace('Provisional standalone PHP 8.6 syntactic EBNF','Standalone PHP 8.6.0 GA structural EBNF',$ebnf);
    $ebnf=str_replace('PHP86-001 only; other PHP 8.6 changes remain pending.','PHP86-001 syntax and CA86-001 structural correction; contextual checks are external.',$ebnf);
    put('grammar/8.6/php.ebnf',$ebnf);
    $md=preserve('grammar/8.6/php.md');
    $start=strpos($md,'## Status and scope');$end=strpos($md,'This standalone working specification');
    if($start===false||$end===false)throw new RuntimeException('Unexpected specification header');
    $header=<<<'MD'
## Status and scope

**PHP 8.6.0 GA structural specification; structural certification pending.**
The authoritative release is `php-8.6.0`, branch `PHP-8.6`, commit
`8aaada3f9605d81a38b8240f93c7389dfbc77ef9`. The independent
[structural contract](../../docs/structural-certification.md) certifies the
standalone structural grammar and byte-oriented lexical profile, not exhaustive
compiler, contextual, AST, folding, or runtime equivalence. Structural matching
does not establish whole-source validity.

PHP86-001 supplies partial application and callable trailing commas.
PHP86-003 through PHP86-007 describe external contextual restrictions;
PHP86-010 describes diagnostics rather than syntax removals. CA86-001-STRUCTURAL
corrects hook names to lexical identifiers, with get/set legality checked only
on visited declarations. PHP 8.5 remains unchanged; this correction is not a new
PHP 8.6 language feature. See [changes](changes.md) for exact source mappings.

Current normative source correspondence is the authenticated GA
[parser](https://github.com/php/php-src/blob/8aaada3f9605d81a38b8240f93c7389dfbc77ef9/Zend/zend_language_parser.y),
[scanner](https://github.com/php/php-src/blob/8aaada3f9605d81a38b8240f93c7389dfbc77ef9/Zend/zend_language_scanner.l)
and [compiler](https://github.com/php/php-src/blob/8aaada3f9605d81a38b8240f93c7389dfbc77ef9/Zend/zend_compile.c).
Historical Phase 3–8, PHP 8.5 and RC4 observations retained below describe their
original audits; they are not final GA execution results. The seven original
extended-conformance blockers remain unresolved under their original contracts.

MD;
    $md=substr($md,0,$start).$header."\n".substr($md,$end);
    $md=str_replace('# PHP 8.6 syntax specification (provisional)','# PHP 8.6.0 structural syntax specification',$md);
    $md=str_replace('This standalone working specification','This standalone specification',$md);
    $md=str_replace('hook-name = "get" | "set" ;','hook-name = identifier ;',$md);
    $a=strpos($md,'These terminals are case-insensitive:');$b=strpos($md,'The surrounding syntax follows', $a);
    $md=substr($md,0,$a)."The hook name is a lexical identifier (`T_STRING` in the GA parser), not a\nsemi-reserved name. Reserved keywords, qualified names, variables and numeric\nliterals cannot occupy this position. The compiler compares `get` and `set`\ncase-insensitively when it visits the declaration; unknown names are structurally\naccepted but fail that contextual check if visited.\n\n".substr($md,$b);
    $a=strpos($md,'**Compatibility change:** unknown hook names');$b=strpos($md,'See also the [official property-hook manual]', $a);
    $md=substr($md,0,$a)."**CA86-001-STRUCTURAL correction:** unknown identifier hook names now match\nstructurally, including discarded declarations. Live and retained declarations\nstill require case-insensitive get/set under `zend_compile_property_hooks`.\nThe same distinction applies to promoted parameter hooks. The maintainer's\n[explanation](https://github.com/php/php-src/issues/24795#issuecomment-6455687623)\nconfirms that dead ASTs can skip compilation. The GA parser's `property_hook`\nuses `T_STRING`; compiler hook-kind validation is contextual. Ninety focused\ncontrols and the [Gate B record](../../docs/8.6/structural-certification/gate-b.json)\njustify this one-production correction. PHP 8.5's get/set-only EBNF remains\nunchanged and requires a separate historical correction. Original extended\nCA86-001 results and obligations are retained, not retroactively passed.\n\n".substr($md,$b);
    $md=preg_replace_callback('/(<!-- BEGIN GENERATED EBNF -->\s*```ebnf\s*).*?(\s*```\s*<!-- END GENERATED EBNF -->)/s',static fn($m)=>$m[1].rtrim($ebnf).$m[2],$md,1,$count);
    if($count!==1)throw new RuntimeException('Generated EBNF marker missing');put('grammar/8.6/php.md',$md);
    $agent=preserve('AGENTS.md');
    $agent=str_replace('## Core Principles',"## Certification levels\n\nMaintainer authorization, final structural-release phase: stable structural grammar\ncertification and extended compiler/conformance certification are separate. Follow\n[the structural contract](docs/structural-certification.md) for a stable structural\nrelease. Its independent gates must prove parser/scanner correspondence, correct\nstandalone grammar, parity, syntax witnesses, change traceability, current PHP 8.5\ncompatibility and no unresolved material structural defect within the declared\nprofile. It does not require exhaustive compiler, AST, folding or runtime equivalence.\n\nPreserve the original 36-gate and additive 38-obligation contracts, all seven\nblockers, source profiles and historical failed results unchanged in meaning. Their\nremaining extended obligations are not silently removed or relabeled. The missing\nhistorical manifest is disclosed; current compatibility is not historical renewal.\nKnown structural or lexical defects cannot be excluded merely to obtain sign-off.\nGate B, evidence-backed corrections and separate PHP 8.5 preservation still apply.\nThe older phase-specific completion instructions below continue to govern their\noriginal phases and extended contracts; structural promotion uses the new explicit\ncontract and its complete release evidence.\n\n## Core Principles",$agent);put('AGENTS.md',$agent);
    $policy=preserve('docs/conformance-policy.md');
    $policy=str_replace('## Defined claims',"## Two independent certification levels\n\nThe maintainer-authorized [structural contract](structural-certification.md),\n`php86-structural-v1`, defines stable structural grammar certification. It certifies\na complete standalone EBNF within an explicit byte-source profile, parser/scanner\ncorrespondence, normative parity, source-backed syntax and version-boundary tests,\ntraceability, current compatibility and absence of known material in-scope defects.\nIt does not assert exhaustive parser/compiler acceptance, contextual enforcement,\nAST/opcode, folding or runtime equivalence, or 100% fixture coverage.\n\nThe existing 27-gate PHP 8.5 release system, 36-gate PHP 8.6 system and additive\n38-obligation contracts retain their original claims, checks and failed outcomes.\nThey provide historical and extended-conformance evidence. Structural success\ndoes not close their blockers or renew historical certification. The legacy\nrelease-gate section below continues to describe those contracts, not an implicit\nsubstitution rule. Known structural false acceptance/rejection must be corrected\nor explicitly explained by a source-backed structural profile; naming a defect\ncontextual does not remove that requirement.\n\n## Defined claims",$policy);put('docs/conformance-policy.md',$policy);
    $version=preserve('docs/versioning.md');$version.="\n## 18. Stable structural certification\n\nThe maintainer-authorized `php86-structural-v1` contract in\n[structural certification](structural-certification.md) is an independent release\nroute for a stable structural grammar. Stable status identifies this bounded\nclaim, not compiler equivalence. The preceding phase and extended-conformance\nrequirements remain attached to their original contracts. Promotion requires all\nnew structural gates, independent verification and a final frozen package inventory.\nThe original extended gates and blockers remain visible. PHP 8.5 preservation\nmeans unchanged grammar, specification, fixtures and historical evidence plus\ncurrent API/tool compatibility; it does not authenticate unavailable historical bytes.\n";put('docs/versioning.md',$version);
    $changes=preserve('grammar/8.6/changes.md');
    $changes=str_replace('**Registered draft; provisional and incomplete working document.**','**PHP 8.6.0 GA structural grammar; structural certification pending.**',$changes);
    $changes=str_replace('No complete PHP 8.6 compatibility or certification is claimed.','No exhaustive PHP compiler compatibility is claimed. Structural certification is independent of the retained extended contracts.',$changes);
    $changes=str_replace('## 3. Modified syntax',"## 3. Modified syntax\n\n**CA86-001-STRUCTURAL — representation correction, not new PHP syntax.**\n`hook-name` now references `identifier`, matching GA parser `property_hook`\n(`T_STRING`, line 1160). The compiler's case-insensitive get/set legality check\nremains contextual and applies to visited declarations. The ninety\n[hook controls](../../tests/fixtures/php/8.6/structural-hooks/cases.json) cover\ncase, unknown identifiers, keywords and other token categories, live/retained/\ndiscarded declarations and promoted parameters.\n[Gate B evidence](../../docs/8.6/structural-certification/gate-b.json) records\nsource hashes and obligations. PHP 8.5 is unchanged and retains a separate\nhistorical correction obligation; the cross-version matcher difference is not a\nPHP 8.6 language addition. The five PHP86-001 production changes below are retained.\n",$changes);
    $changes=str_replace('Exactly five productions change; none are added, removed,','Exactly five PFA productions change; none are added, removed,',$changes);
    $changes.="\nUnder the independently authorized [structural contract](../../docs/structural-certification.md),\nCA86-001-STRUCTURAL is corrected by the evidence above. Original PHP86-013,\nPHP86-014, PHP86-015, CA86-001, C6-CONTEXT, C6-COVERAGE and C6-PROVENANCE\nremain unresolved in the preserved extended contracts. See the\n[structural scope decisions](../../docs/8.6/structural-certification/scope.json);\nno original failed result is relabeled as a pass.\n";put('grammar/8.6/changes.md',$changes);
    echo "Governance and one-production Gate B correction applied.\n";
} elseif($mode==='--traceability') {
    $map=S::read('tools/8.6/change-map-ga-v2.json');$map['contract']='php86-structural-traceability-v1';
    $old=(new Parser())->parse(file_get_contents('grammar/8.5/php.ebnf'))->productionMap();$new=(new Parser())->parse(file_get_contents('grammar/8.6/php.ebnf'))->productionMap();
    $map['productions']['hook-name']=['kind'=>'modified','before'=>hash('sha256',json_encode(T::structure($old['hook-name']->expression))),'after'=>hash('sha256',json_encode(T::structure($new['hook-name']->expression))),'finding'=>'CA86-001-STRUCTURAL'];
    $fixtures=[];foreach(S::read('tests/fixtures/php/8.6/structural-hooks/cases.json')['cases']as$c)$fixtures[$c['fixture']]=$c['sha256'];
    $map['findings']['CA86-001-STRUCTURAL']=['productions'=>['hook-name','property-hook'],'sources'=>['https://github.com/php/php-src/blob/'.$map['pin'].'/Zend/zend_language_parser.y#L1160','https://github.com/php/php-src/blob/'.$map['pin'].'/Zend/zend_compile.c#L9426'],'fixtures'=>$fixtures,'classification'=>'historical representation correction; not new 8.6 syntax'];
    $map['productions']['yield-key']=['kind'=>'modified','before'=>hash('sha256',json_encode(T::structure($old['yield-key']->expression))),'after'=>hash('sha256',json_encode(T::structure($new['yield-key']->expression))),'finding'=>'SG86-002'];
    $fixtures=[];foreach(S::read('tests/fixtures/php/8.6/structural-evidence/cases.json')['cases']as$c)$fixtures[$c['fixture']]=$c['sha256'];
    $map['findings']['SG86-002']=['productions'=>['yield-key'],'sources'=>['https://github.com/php/php-src/blob/'.$map['pin'].'/Zend/zend_language_parser.y#L1383'],'fixtures'=>$fixtures,'classification'=>'historical prefix composition correction; not new 8.6 syntax'];
    $before=T::sections(file_get_contents('grammar/8.5/php.md'));$after=T::sections(file_get_contents('grammar/8.6/php.md'));
    foreach(array_unique(array_merge(array_keys($before),array_keys($after)))as$section)if(($before[$section]??null)!==($after[$section]??null)){
        $prior=$map['documentation'][$section]??['reason'=>'GA structural scope and source reconciliation','findings'=>['CA86-001-STRUCTURAL']];
        $map['documentation'][$section]=$prior+[];$map['documentation'][$section]['before']=hash('sha256',$before[$section]??'');$map['documentation'][$section]['after']=hash('sha256',$after[$section]??'');
    }
    $map['ebnfSha256']=hash_file('sha256','grammar/8.6/php.ebnf');$map['changesSha256']=hash_file('sha256','grammar/8.6/changes.md');
    put('tools/8.6/change-map-structural-v1.json',S::json($map));
    $manifest=json_decode(preserve('php-grammar.json'),true,512,JSON_THROW_ON_ERROR);
    $manifest['versions'][1]['metadata']['changeTraceability']='tools/8.6/change-map-structural-v1.json';
    $manifest['versions'][1]['metadata']['structuralCertification']='docs/structural-certification.md';
    put('php-grammar.json',str_replace("\n","\r\n",json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n"));
    $r=T::validate(getcwd(),$map);echo S::json(['errors'=>$r['errors'],'differences'=>$r['differences']]);exit($r['errors']===[]?0:1);
} else throw new RuntimeException('Use --governance-grammar or --traceability');

<?php
declare(strict_types=1);
namespace PhpGrammar\Tools;
require_once __DIR__.'/GaCertification.php';
require_once __DIR__.'/ChangeTraceability.php';
use PhpGrammar\Ebnf\{Parser,ReferenceNode};
use PhpGrammar\Ebnf\Validation\GrammarValidator;
use PhpGrammar\Php\Conformance\{GrammarRepository,GrammarCoverageAnalyzer,PhpGrammarMatcher};
use PhpGrammar\Repository\RepositoryManifest;

/** Independent structural claim. No legacy gate or blocker is modified. */
final class StructuralCertification
{
    public const CONTRACT='php86-structural-v1';
    public const SCHEMA='php86-structural-evidence-v1';
    public const OUT='docs/8.6/structural-certification';
    public const BASELINE='6b06748bf3bc7cc7badbd3c432e794c36511c5619f6cb7c0d7f62f7bdb56478b';
    public const CLAIM='Standalone source-backed PHP 8.6.0 structural grammar in the documented byte-source profile; contextual/compiler/AST/folding/runtime equivalence is not certified.';
    public function __construct(public readonly string $root,private readonly array $overrides=[]) {}
    public function text(string $p):string { return $this->overrides[$p]??(is_file($this->root.'/'.$p)?file_get_contents($this->root.'/'.$p):throw new \RuntimeException('Missing input: '.$p)); }
    public function read(string $p):array { return json_decode($this->text($p),true,512,JSON_THROW_ON_ERROR); }
    public function hash(string $p):string {
        if(isset($this->overrides[$p]))return hash('sha256',$this->overrides[$p]);
        if(!is_file($this->root.'/'.$p))throw new \RuntimeException('Missing input: '.$p);
        return hash_file('sha256',$this->root.'/'.$p);
    }
    public function baseline():array {
        if($this->hash(self::OUT.'/baseline.json')!==self::BASELINE)throw new \RuntimeException('Unauthenticated structural baseline');
        return $this->read(self::OUT.'/baseline.json');
    }
    public static function gates():array { return [
        'official-ga-source'=>'Official annotated GA tag and immutable source identity',
        'source-locks'=>'Authenticated parser/scanner/compiler source and executable identities',
        'exact-ga-build'=>'Full retained source/build/generated-file/toolchain/binary provenance',
        'parser-scanner-correspondence'=>'Recompute every retained production, action and scanner slice mapping',
        'standalone-ebnf'=>'Standalone loading without another grammar package',
        'grammar-integrity'=>'EBNF syntax, references, naming, reachability and nullability',
        'markdown-parity'=>'Exact canonical/generated Markdown production parity',
        'reviewed-alternatives'=>'Five PFA changes preserved and only the justified hook production correction',
        'change-traceability'=>'Source-backed production/prose mapping and complete ten-section changes',
        'source-references'=>'Immutable source references and complete reviewed source mappings',
        'lexical-profiles'=>'Both short-tag profiles and version-specific lexer boundaries',
        'positive-negative-syntax'=>'Every registered positive and structural-negative fixture',
        'ordinary-ga-differential'=>'Complete retained ordinary corpus against GA with explicit hook correction',
        'hook-stage-boundaries'=>'Ninety identifier/keyword/case/live/retained/discarded/promoted controls',
        'version-boundaries'=>'Paired PHP 8.5/8.6 language boundaries and representation-correction controls',
        'pfa-differential'=>'GA partial application and first-class-callable boundaries',
        'targeted-structural-witnesses'=>'Focused uncovered alternatives, token categories and yield-key correction controls',
        'classified-coverage'=>'Measured production/alternative coverage with source-backed gap classifications',
        'contextual-scope'=>'Explicit external constraints and preservation of all seven extended blockers',
        'no-structural-defects'=>'Reject unresolved material structural defects and false correction claims',
        'public-api-cli'=>'Public API, standalone consumption and CLI compatibility',
        'historical-preservation'=>'Unchanged PHP 8.5 artifacts and immutable extended evidence',
        'current-compatibility85'=>'Current PHP 8.5 shared implementation, source and paired-boundary checks',
        'full-regression'=>'Entire current repository PHPUnit suite',
        'infrastructure-negatives'=>'Mutation tests refusing invalid, missing, stale or falsified certification inputs',
        'package-integrity'=>'Complete distribution file/hash inventory and Composer metadata',
        'composer-metadata'=>'Strict Composer package validation',
    ]; }
    public function inventory():array {
        $r=(new GaCertification($this->root))->inventory();
        foreach(array_keys($r)as$p)if(str_starts_with($p,self::OUT.'/runs/')||$p==='docs/8.6/structural-release-signoff.md')unset($r[$p]);
        // The current extended summary is a frozen historical input to this contract.
        $r[GaCertification::OUT.'.md']=$this->hash(GaCertification::OUT.'.md');
        $r['LICENSE']=$this->hash('LICENSE'); ksort($r);return$r;
    }
    public function requiredTests():array {return ['tests/StructuralCertificationTest.php','tests/Php/Conformance/Php86StructuralHooksTest.php','tests/Php/Conformance/Php86StructuralWitnessTest.php','tests/Php/Conformance/Php86IntegrationTest.php','tests/Php/Conformance/Php86TraceabilityTest.php','tests/Repository/ConsumerCliTest.php'];}
    public function grammarErrors():array {
        $empty=['whitespace','source-file','top-statement-list','optional-type-without-static','return-type','parameter-list','array-pair-list','constant-array-pair-list','backtick-string-part-list','inner-statement-list','expression-statement','for-expression-list','for-condition-expression-list','switch-case-list','catch-list','parameter-modifiers','class-modifiers','class-member-list','property-hook-list','property-hook-modifiers','method-modifiers','class-constant-modifiers','line-comment-text','block-comment-text','doc-comment-text','single-quoted-string-content','heredoc-body','nowdoc-body','halt-compiler-data'];
        try {$g=(new Parser())->parse($this->text('grammar/8.6/php.ebnf'));return array_map(static fn($e)=>$e->code.': '.$e->message,(new GrammarValidator(allowedEmptyProductions:$empty))->validate($g)->errors);}catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function parityErrors():array {
        preg_match('/<!-- BEGIN GENERATED EBNF -->\R```ebnf\R(.*?)\R```\R<!-- END GENERATED EBNF -->/s',$this->text('grammar/8.6/php.md'),$m);
        return isset($m[1])&&trim($m[1])===trim($this->text('grammar/8.6/php.ebnf'))?[]:['Canonical/Markdown parity failure'];
    }
    public function alternativeErrors():array {
        try {
            $b=$this->baseline();$path=self::OUT.'/predecessor/grammar/8.6/php.ebnf';
            if($this->hash($path)!==$b['inputs']['grammar/8.6/php.ebnf'])return['Changed predecessor grammar'];
            $before=(new Parser())->parse($this->text($path))->productionMap();$after=(new Parser())->parse($this->text('grammar/8.6/php.ebnf'))->productionMap();
            if(array_keys($before)!==array_keys($after))return['Unreviewed production inventory'];$errors=[];
            foreach($before as$name=>$p){
                if($name==='hook-name'){
                    if(!($after[$name]->expression instanceof ReferenceNode)||$after[$name]->expression->name!=='identifier')$errors[]='Hook name must be the T_STRING-equivalent identifier production';
                }elseif($name==='yield-key'){
                    preg_match('/^yield-key =\R.*?;/ms',$this->text($path),$match);$expected=$match[0];
                    foreach(['arrow-function-header , closed-yield-arrow-expression','include-operator , closed-yield-include-expression','"print" , closed-yield-print-expression']as$part)$expected=str_replace($part,substr($part,0,strpos($part,',')+2).'closed-yield-throw-expression',$expected);
                $expected=str_replace('"print" , closed-yield-throw-expression','"print" , ( closed-yield-print-expression | [ closed-yield-print-prefix-context ] , ( "throw" | arrow-function-header | include-operator ) , closed-yield-throw-expression )',$expected);
                $expected=(new Parser())->parse($expected)->production('yield-key')->expression;
                    if(ChangeTraceability::structure($expected)!==ChangeTraceability::structure($after[$name]->expression))$errors[]='Unreviewed yield-key correction';
                }elseif(ChangeTraceability::structure($p->expression)!==ChangeTraceability::structure($after[$name]->expression))$errors[]='Unreviewed structural alternative: '.$name;
            }return$errors;
        }catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function sourceErrors():array {
        try {
            $b=$this->baseline();$p='tools/8.6/source-lock-ga-v2.json';$errors=[];
            if($this->hash($p)!==$b['inputs'][$p])$errors[]='Stale/unreviewed GA source lock';
            $lock=$this->read($p);
            if(($lock['versions']['8.6']['commit']??$lock['sourceCommit']??GaCertification::PIN)!==GaCertification::PIN)$errors[]='Wrong source commit';
            $auth=$this->read('docs/8.6/ga-finalization/build-source.json')['sourceHashes'];
            foreach($lock['files']['8.6']as$file=>$hash){
                if(($auth[$file]??null)!==$hash||$this->hash(GaCertification::SOURCES.'/'.$file)!==$hash)$errors[]='Source correspondence mismatch: '.$file;
            }
            foreach(['8.5'=>GaCertification::PHP,'8.6'=>GaCertification::GA]as$v=>$file)if($this->hash($file)!==$lock['executables'][$v])$errors[]='Wrong executable: '.$v;
            if($this->hash(dirname(GaCertification::GA).'/php8.dll')!=='9b2a8bad3ff00c1aa57c83676cff79a830df63ed5af1fc3eb6d55de8b8ab57f9')$errors[]='Wrong GA DLL';
            return$errors;
        }catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function metadataErrors():array {
        try {
            $m=$this->read('php-grammar.json');$errors=(new ManifestValidator())->validate(json_decode(json_encode($m)), $this->root);
            $p=$m['versions'][1]??[];
            if(($p['version']??null)!=='8.6'||!in_array($p['status']??null,['draft','stable'],true)||($p['phpSource']??null)!==['branch'=>'PHP-8.6','commit'=>GaCertification::PIN]
                ||($p['metadata']['changeTraceability']??null)!=='tools/8.6/change-map-structural-v1.json'||($p['metadata']['structuralCertification']??null)!=='docs/structural-certification.md')$errors[]='Incorrect structural package metadata';
            if($m['versions'][0]!==$this->baseline()['manifest']['versions'][0])$errors[]='PHP 8.5 manifest changed';
            foreach($this->requiredTests()as$t)if(!is_file($this->root.'/'.$t)||trim($this->text($t))==='')$errors[]='Missing required test: '.$t;
            if(($p['status']??null)==='stable'){
                $promotion=$this->read(self::OUT.'/promotion.json');$prior=$this->read($promotion['report']);
                if($this->hash($promotion['report'])!==$promotion['sha256']||($prior['structuralCertified']??false)!==true||($promotion['independentlyVerifiedBeforePromotion']??false)!==true)$errors[]='Stable promotion lacks prior independently verified structural certification';
            }
            return$errors;
        }catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function boundaryErrors():array {
        $e=[];foreach($this->baseline()['inputs']as$p=>$h)if(str_starts_with($p,'tests/fixtures/version-boundaries/')||$p==='tests/fixtures/php/8.6/boundary-cases.json'){
            if($this->hash($p)!==$h)$e[]='Unreviewed version-boundary expectation: '.$p;
        }return$e;
    }
    public function historicalErrors():array {
        $e=[];foreach($this->baseline()['inputs']as$p=>$h)if(str_starts_with($p,'grammar/8.5/')||str_starts_with($p,'tests/fixtures/php/8.5/')||str_starts_with($p,'docs/8.5/')||str_starts_with($p,'tools/8.5/')){
            if($this->hash($p)!==$h)$e[]='Historical input changed: '.$p;
        }return$e;
    }
    public static function receiptErrors(array $r,string $bytes,string $digest):array {
        return hash('sha256',$bytes)===$digest&&hash('sha256',Support::json($r))===$digest?[]:['Tampered structural evidence'];
    }
    public function scopeErrors():array {
        try {
            $s=$this->read(self::OUT.'/scope.json');$errors=[];
            if(($s['contract']??null)!=='php86-structural-scope-v1'||($s['sourceCommit']??null)!==GaCertification::PIN)$errors[]='Invalid scope contract';
            if(array_column($s['blockers']??[],'id')!==['PHP86-013','PHP86-014','PHP86-015','CA86-001','C6-CONTEXT','C6-COVERAGE','C6-PROVENANCE'])$errors[]='Lost extended blocker';
            foreach($s['blockers']??[]as$row){if(($row['extendedStatus']??null)!=='unresolved'||empty($row['structuralImpact'])||empty($row['evidence']))$errors[]='Unsupported scope disposition';foreach($row['evidence']??[]as$p)$this->text($p);}
            $defects=$s['knownStructuralDefects']??[];
            $stable=$this->read('php-grammar.json')['versions'][1]['status']==='stable';
            if(array_column($defects,'id')!==['CA86-001-STRUCTURAL','SG86-002']||array_unique(array_column($defects,'status'))!==[$stable?'corrected-verified':'corrected-pending-execution'])$errors[]='Unresolved or omitted material structural defect';
            if($stable){$promotion=$this->read(self::OUT.'/promotion.json');foreach($defects as$defect)if(($defect['executionEvidence']??null)!==$promotion['report'])$errors[]='Correction lacks executed evidence';}
            $errors=[...$errors,...$this->alternativeErrors()];
            foreach(['get/set','contextual','PHP86-013','C6-CONTEXT']as$term)if(!str_contains($this->text('grammar/8.6/php.md'),$term))$errors[]='Missing contextual boundary: '.$term;
            return$errors;
        }catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function preservationErrors():array {
        try {
            $base=$this->baseline();$transition=$this->read(self::OUT.'/transition.json');$errors=[];
            if(($transition['baselineSha256']??null)!==self::BASELINE)$errors[]='Unanchored preservation transition';
            foreach($base['inputs']as$p=>$hash){
                $now=$this->hash($p);if($now===$hash)continue;
                // Historical language artifacts and evidence can never be permitted edits.
                if(str_starts_with($p,'grammar/8.5/')||str_starts_with($p,'tests/fixtures/php/8.5/')||str_starts_with($p,'docs/8.5/')||str_starts_with($p,'tools/8.5/')
                    ||(str_starts_with($p,'docs/8.6/')&&!in_array($p,['docs/8.6/structural-release-signoff.md'],true))
                    ||in_array($p,['tools/lib/GaCertification.php','tools/lib/Certification86.php','tools/8.6/certification-ga.php','tools/8.6/certification.php'],true)){$errors[]='Historical input changed: '.$p;continue;}
                $row=$transition['changes'][$p]??[];
                if(($row['before']??null)!==$hash||($row['after']??null)!==$now||empty($row['reason']))$errors[]='Unreviewed transition: '.$p;
                if($this->hash(self::OUT.'/predecessor/'.$p)!==$hash)$errors[]='Lost predecessor: '.$p;
            }
            foreach($transition['changes']??[]as$p=>$r)if(!isset($base['inputs'][$p])||$r['before']!==$base['inputs'][$p]||$this->hash($p)!==$r['after'])$errors[]='Stale transition entry: '.$p;
            return$errors;
        }catch(\Throwable$e){return[$e->getMessage()];}
    }
    public function unitCommand(?string $filter=null):array {
        $cmd=[$this->root.'/'.GaCertification::PHP,'-n','-d','memory_limit=1G','-d','extension='.$this->root.'/.audit/php85/ext/php_mbstring.dll','vendor/phpunit/phpunit/phpunit','--do-not-cache-result','--no-progress'];
        return$filter===null?$cmd:[...$cmd,'--filter',$filter];
    }
    public function commands(string $out):array {
        $php=[$this->root.'/'.GaCertification::PHP,'-n','-d','memory_limit=1G'];$commands=[];
        $pair=['--php85='.$this->root.'/'.GaCertification::PHP,'--php86='.$this->root.'/'.GaCertification::GA];
        $sources=['--sources85='.$this->root.'/.audit/phase8d/sources85','--sources86='.$this->root.'/'.GaCertification::SOURCES];
        foreach(self::gates()as$id=>$description){
            $commands[$id]=match($id){
                'exact-ga-build'=>[...$php,'tools/8.6/ga-finalization.php','--verify-build'],
                'lexical-profiles','version-boundaries'=>[...$php,'tools/8.6/integration-check-ga-v2.php',...$pair,...$sources,'--report='.$out.'/'.$id.'.json'],
                'pfa-differential'=>[...$php,'tools/8.6/pfa-check-structural-v1.php',...$pair,'--sources='.$this->root.'/'.GaCertification::SOURCES,'--report='.$out.'/'.$id.'.json'],
                'hook-stage-boundaries'=>[...$php,'tools/8.6/structural-hooks.php','--check',$out.'/'.$id.'.json'],
                'targeted-structural-witnesses'=>[...$php,'tools/8.6/structural-witnesses.php','--check',$out.'/'.$id.'.json'],
                'positive-negative-syntax'=>$this->unitCommand('PhpConformanceFixtureTest'),
                'public-api-cli'=>$this->unitCommand('Consumer|ConformanceVersionSupportTest|Php86IntegrationTest'),
                'full-regression'=>$this->unitCommand(),
                'infrastructure-negatives'=>$this->unitCommand('StructuralCertificationTest|Php86StructuralHooksTest|Php86StructuralWitnessTest'),
                'package-integrity'=>[...$php,'tools/8.6/structural-package.php','--output',$out.'/'.$id.'.json'],
                'composer-metadata'=>$this->composerCommand(),
                default=>[...$php,'tools/8.6/structural-certification.php','--gate',$id,'--output',$out.'/'.$id.'.json'],
            };
        }return$commands;
    }
    private function composerCommand():array {
        $php=$this->root.'/'.GaCertification::PHP;$command=Support::composer('composer',$php);
        return$command[0]===$php?[$php,'-n','-d','extension='.$this->root.'/.audit/php85/ext/php_openssl.dll',...array_slice($command,1),'validate','--strict']:[...$command,'validate','--strict'];
    }
    public function ordinary():array {
        $rows=[];$errors=[];$m=PhpGrammarMatcher::forRepositoryRoot($this->root);
        $inventory=$this->read('docs/8.6/scanner-grammar-binding-closure/inventory.json')['cases'];
        $hookFiles=['tests/fixtures/php/8.6/known-discrepancies/coverage-hook-discarded.php','tests/fixtures/php/8.6/known-discrepancies/coverage-hook-live.php','tests/fixtures/php/8.6/known-discrepancies/coverage-hook-retained.php'];
        foreach($inventory as$c){
            if($this->hash($c['fixture'])!==$c['sha256']){$errors[]='Changed ordinary fixture: '.$c['fixture'];continue;}
            $expected=$c['expected'];if(in_array($c['fixture'],$hookFiles,true))$expected['structuralAccepted']=true;
            $command=[$this->root.'/'.GaCertification::GA,'-n','-d','short_open_tag=1','-d','zend.multibyte=0','-d','display_errors=1','-d','log_errors=0','-d','error_reporting=-1','-l',$c['fixture']];
            $run=Support::run($command,$this->root);$native=$m->matches('8.6',$this->text($c['fixture']))->matched;
            if($run['exit_code']!==$expected['lintExit']||DifferentialInventory::diagnostics($run['output'])!==$expected['diagnostics']||$native!==$expected['structuralAccepted'])$errors[]='Ordinary structural/oracle mismatch: '.$c['fixture'];
            $rows[]=['fixture'=>$c['fixture'],'sha256'=>$c['sha256'],'predecessorExpectation'=>$c['expected'],'expected'=>$expected,'native'=>$native,'command'=>$command,'result'=>$run];
        }return['observations'=>$rows,'errors'=>$errors];
    }
    public function coverage():array {
        $w=[];$r=(new GrammarCoverageAnalyzer(RepositoryManifest::fromRepositoryRoot($this->root)))->analyze('8.6',[],static function($id,$c)use(&$w){foreach([...$c->matchedProductions(),...$c->matchedAlternatives()]as$t)$w[$t][]=$id;});
        $ledger=$this->read('docs/8.6/source-completeness-ledger.json');$map=[];
        foreach($ledger['parser']['rows']as$name=>$row)foreach($row['ebnf']as$anchor)$map[$anchor][]=$name;
        $prior=$this->read('docs/8.6/scanner-grammar-binding-closure/coverage.json');
        $priorRows=array_column($prior['rows'],null,'id');$focused=[];$errors=[];
        $witnessReport=$this->read(self::OUT.'/witnesses-v4.json');
        if($witnessReport['errors']!==[]||$witnessReport['sourceCommit']!==GaCertification::PIN)$errors[]='Invalid focused structural evidence';
        $m=PhpGrammarMatcher::forRepositoryRoot($this->root);
        foreach($witnessReport['observations']as$case){
            if($this->hash($case['fixture'])!==$case['sha256']){$errors[]='Stale focused witness: '.$case['id'];continue;}
            if(!$case['native']||!$case['ruleMatched'])continue;
            $context=$case['id']==='constructor-fcc'&&$case['lint']['exit_code']===255&&str_contains($case['lint']['output'],'Cannot create Closure for new expression');
            if($case['lint']['exit_code']!==0&&!$context)continue;
            $collector=new \PhpGrammar\Ebnf\Coverage\CoverageCollector();
            if(!$m->withCoverage($collector)->matchesRule('8.6',$case['rule'],$case['fragment'])->matched){$errors[]='Focused rule no longer matches';continue;}
            foreach($collector->matchedAlternatives()as$id)$focused[$id][]=['case'=>$case['id'],'fixture'=>$case['fixture'],'sha256'=>$case['sha256'],'kind'=>$context?'source-backed contextual boundary':'GA-positive source and focused rule witness'];
        }
        $classifications=[];
        $reserved=(new Parser())->parse($this->text('grammar/8.6/php.ebnf'))->production('reserved-non-modifiers')->expression;
        $enumId=null;foreach($reserved->alternatives as$i=>$node)if($node instanceof \PhpGrammar\Ebnf\LiteralNode&&$node->value==='enum')$enumId='reserved-non-modifiers/alternative:'.($i+1);
        foreach([...$r->unexercisedProductions(),...$r->unexercisedAlternatives()]as$id){
            $old=$priorRows[$id]??null;
            if(isset($focused[$id]))$classifications[$id]=['classification'=>'focused-structural-evidence','witnesses'=>$focused[$id]];
            elseif($old!==null&&($old['directMatchingMeaningful']??true)===false&&in_array($old['predecessor']['original']['classification']??'', ['primitive-bypassed','trivia-removed'],true))$classifications[$id]=['classification'=>$old['predecessor']['original']['classification'],'reason'=>$old['predecessor']['original']['reason'],'evidence'=>'docs/8.6/scanner-grammar-binding-closure/coverage.json','scannerEvidence'=>'docs/8.6/source-completeness-ledger.json#/scanner','isolatedChartHitClaim'=>false];
            elseif($id===$enumId)$classifications[$id]=['classification'=>'scanner-lookahead representation','reason'=>'The scanner emits T_ENUM only with declaration lookahead. In callable names enum is T_STRING and matches the identifier route. The redundant keyword spelling branch is not a distinct accepted construct.','source'=>'Zend/zend_language_scanner.l enum lookahead; Zend/zend_language_parser.y reserved_non_modifiers','witness'=>'reserved-'.((int)substr($enumId,strrpos($enumId,':')+1)-1),'isolatedChartHitClaim'=>false];
            else{$classifications[$id]=['classification'=>'unresolved'];$errors[]='Unclassified meaningful structural gap: '.$id;}
        }
        return['productions'=>$r->exercisedProductions(),'totalProductions'=>$r->totalProductions(),'alternatives'=>$r->exercisedAlternatives(),'totalAlternatives'=>$r->totalAlternatives(),
            'remainingProductions'=>$r->unexercisedProductions(),'remainingAlternatives'=>$r->unexercisedAlternatives(),'witnesses'=>$w,
            'priorClassifications'=>$prior['rows'],'sourceAnchors'=>$map,'denominatorChange'=>'hook-name no longer has two terminal alternatives; one identifier reference replaces them. No production removed.',
            'classifications'=>$classifications,'focusedWitnesses'=>$focused,
            'limitation'=>'Completed chart-item witnesses are not unique-derivation, AST-equivalence or instrumented scanner-hit evidence.','errors'=>$errors];
    }
    public function compatibility(string $out):array {
        $config=CompatibilityEvidence::defaults($this->root);$config['php86']=$this->root.'/'.GaCertification::GA;
        $config['sources85']=$this->root.'/.audit/phase8d/sources85';$config['sources86']=$this->root.'/'.GaCertification::SOURCES;$config['source-cache']=$this->root.'/.audit/phase8d/source-cache';
        $commands=(new CompatibilityEvidence($this->root))->commands($config,$this->root.'/'.$out);
        // Full regression is an independently required outer gate, not executed twice.
        unset($commands['full-regression']);
        $commands['source-lock86']=[$config['php85'],'-n','tools/8.6/structural-certification.php','--gate','source-locks'];
        $i=array_search('tools/8.6/integration-check.php',$commands['paired-boundaries'],true);$commands['paired-boundaries'][$i]='tools/8.6/integration-check-ga-v2.php';
        return$commands;
    }
    public function packageInventory():array {
        $r=$this->inventory();foreach(array_keys($r)as$p)if(str_starts_with($p,'vendor/'))unset($r[$p]);
        return$r;
    }
    public function archiveErrors(array $details):array {
        $errors=[];
        if(($details['files']??null)!==$this->packageInventory())return['Distribution inventory differs from current package'];
        $path=$details['archive']??'';
        if(!preg_match('~^'.self::OUT.'/runs/[a-z0-9_-]+/package\.tar$~D',$path)||!is_file($this->root.'/'.$path))return['Missing/unsafe distribution archive'];
        if($this->hash($path)!==($details['archiveSha256']??null))return['Tampered distribution archive'];
        $archive=new \PharData($this->root.'/'.$path);$files=[];
        foreach(new \RecursiveIteratorIterator($archive)as$file){$p=str_replace('\\','/',$file->getPathname());$marker='/package.tar/';$p=substr($p,strpos($p,$marker)+strlen($marker));$files[$p]=hash_file('sha256',$file->getPathname());}
        ksort($files);$expected=$details['files'];ksort($expected);
        if($files!==$expected)$errors[]='Distribution contents/hash mismatch';return$errors;
    }
    public function gate(string $id,string $output=''):array {
        if(!isset(self::gates()[$id]))throw new \RuntimeException('Unknown structural gate');$details=[];$errors=[];
        try {switch($id){
            case 'official-ga-source':
                $b=$this->baseline();foreach(['officialTag','remoteTags']as$key)if($b[$key]['exit_code']!==0)$errors[]='Unverified remote identity';
                if(!str_contains($b['officialTag']['output'],GaCertification::PIN."\trefs/tags/php-8.6.0^{}")||!str_contains($b['officialTag']['output'],'48a5ee8a311db69103a6fa25b330e5ff3847af3d'))$errors[]='Incorrect official tag';
                $details=['tag'=>'php-8.6.0','sourceCommit'=>GaCertification::PIN,'baselineSha256'=>self::BASELINE];break;
            case 'source-locks':$errors=$this->sourceErrors();break;
            case 'parser-scanner-correspondence':$details=(new GaLocalObligations($this->root))->derive();$errors=$details['errors'];break;
            case 'standalone-ebnf':
                $tmp=sys_get_temp_dir().'/php86-structural-'.bin2hex(random_bytes(6));mkdir($tmp.'/grammar/8.6',0777,true);
                try{$manifest=$this->read('php-grammar.json');$manifest['versions']=[$manifest['versions'][1]];
                    foreach(['php.ebnf','php.md']as$f)file_put_contents($tmp.'/grammar/8.6/'.$f,$this->text('grammar/8.6/'.$f));
                    $m=RepositoryManifest::fromArray($tmp,$manifest);$g=(new GrammarRepository($m))->load('8.6');
                    if(count($g->productions())!==361||!PhpGrammarMatcher::forManifest($m)->matches('8.6','<?php f(?);')->matched)$errors[]='Standalone loading failed';
                }finally{foreach(['php.ebnf','php.md']as$f)unlink($tmp.'/grammar/8.6/'.$f);rmdir($tmp.'/grammar/8.6');rmdir($tmp.'/grammar');rmdir($tmp);}break;
            case 'grammar-integrity':$errors=$this->grammarErrors();break;
            case 'markdown-parity':$errors=$this->parityErrors();break;
            case 'reviewed-alternatives':$errors=$this->alternativeErrors();break;
            case 'change-traceability':$details=ChangeTraceability::validate($this->root,$this->read('tools/8.6/change-map-structural-v1.json'),$this->overrides);$errors=$details['errors'];break;
            case 'source-references':
                $details=(new GaLocalObligations($this->root))->derive();$errors=$details['errors'];
                $map=$this->read('tools/8.6/change-map-structural-v1.json');foreach($map['findings']as$id=>$r)if(empty($r['sources'])||empty($r['fixtures'])||empty($r['productions']))$errors[]='Missing mapping: '.$id;
                if(!isset($map['findings']['CA86-001-STRUCTURAL']))$errors[]='Missing hook mapping';
                foreach(['docs/8.6/source-completeness-ledger.json','tools/8.6/certification-source-lock.json']as$p)if($this->hash($p)!==$this->baseline()['inputs'][$p])$errors[]='Missing/stale source mapping: '.$p;
                $errors=[...$errors,...$this->boundaryErrors()];break;
            case 'ordinary-ga-differential':$details=$this->ordinary();$errors=$details['errors'];break;
            case 'classified-coverage':$details=$this->coverage();$errors=$details['errors'];break;
            case 'contextual-scope':case 'no-structural-defects':$errors=$this->scopeErrors();break;
            case 'historical-preservation':$errors=$this->preservationErrors();break;
            case 'current-compatibility85':
                $dir=dirname($output).'/compatibility';if(!is_dir($dir))mkdir($dir,0777,true);
                foreach($this->compatibility($dir)as$name=>$cmd){$log=$dir.'/'.$name.'.log';$r=Support::run($cmd,$this->root,$this->root.'/'.$log);$details[$name]=['command'=>$cmd,'exit_code'=>$r['exit_code'],'log'=>$log,'sha256'=>hash_file('sha256',$this->root.'/'.$log)];if($r['exit_code']!==0)$errors[]='Current compatibility failed: '.$name;}break;
            case 'package-integrity':
                $errors=$this->metadataErrors();$details['files']=$this->packageInventory();
                $composer=$this->read('composer.json');if(($composer['name']??null)!=='php-grammar/php-grammar'||isset($composer['version'])||($composer['bin']??[])!==['bin/php-grammar'])$errors[]='Incorrect Composer package metadata';
                foreach(['/.audit','/.phpunit.cache','/vendor']as$excluded)if(!in_array($excluded,$composer['archive']['exclude']??[],true))$errors[]='Temporary artifacts not excluded';
                foreach(array_keys($details['files'])as$p)if(str_starts_with($p,'.audit/')||str_starts_with($p,'.git/')||str_starts_with($p,'vendor/'))$errors[]='Unsafe distribution input';
                break;
            default:throw new \RuntimeException('Gate requires its declared external command');
        }}catch(\Throwable$e){$errors[]=$e->getMessage();}
        return['contract'=>self::CONTRACT,'gate'=>$id,'details'=>$details,'errors'=>$errors];
    }
    public function verify(array $r,string $path):array {
        $errors=[];
        if(($r['contract']??null)!==self::CONTRACT||($r['schema']??null)!==self::SCHEMA||($r['claim']??null)!==self::CLAIM||($r['sourceCommit']??null)!==GaCertification::PIN)return['Unsupported structural evidence identity/claim'];
        if(!preg_match('~^'.self::OUT.'/runs/[a-z0-9_-]+/evidence\.json$~D',$path)||dirname($path)!==($r['output']??null))return['Invalid structural revision path'];
        $commands=$this->commands($r['output']);if(array_keys($r['results']??[])!==array_keys($commands))return['Missing/reordered structural gate'];
        if(($r['inputs']??null)!==$this->inventory()||($r['inputsUnchanged']??null)!==true)$errors[]='Stale structural dependencies';
        $receipt=$this->read($r['output'].'/receipt.json');$errors=[...$errors,...self::receiptErrors($r,$this->text($path),$receipt['evidenceSha256']??'')];
        $passed=true;
        foreach($commands as$id=>$cmd){$row=$r['results'][$id];if($row['command']!==$cmd||$row['obligation']!==self::gates()[$id]||!is_int($row['exit_code'])||$row['log']!==$r['output'].'/'.$id.'.log'||$this->hash($row['log'])!==$row['sha256'])$errors[]='Gate receipt mismatch: '.$id;
            if($row['exit_code']!==0)$passed=false;
            if($id==='full-regression'&&$row['exit_code']===0&&CompatibilityEvidence::totals($this->text($row['log']))===null)$errors[]='Missing executed PHPUnit totals';
            $p=$r['output'].'/'.$id.'.json';if(is_file($this->root.'/'.$p)){$data=$this->read($p);if(isset($data['errors'])&&(($data['errors']===[])!==($row['exit_code']===0)))$errors[]='Gate report contradiction: '.$id;}
        }
        if(($r['structuralCertified']??null)!==$passed||($r['extendedCertified']??null)!==false)$errors[]='False certification decision';
        $actual=[];foreach(Support::files($this->root.'/'.$r['output'])as$p){$rel=substr($p,strlen($this->root)+1);if(in_array($rel,[$path,$r['output'].'/receipt.json'],true))continue;$actual[$rel]=hash_file('sha256',$p);}ksort($actual);
        if($actual!==($r['artifacts']??null))$errors[]='Tampered/missing run artifacts';
        $schema=$this->read('schema/structural-certification.schema.json');$keys=array_keys($r);$required=$schema['required'];sort($keys);sort($required);if($keys!==$required)$errors[]='Unknown/missing evidence schema fields';
        // Recompute semantic input gates; command receipts are not enough.
        foreach(['official-ga-source','source-locks','parser-scanner-correspondence','grammar-integrity','markdown-parity','reviewed-alternatives','change-traceability','source-references','classified-coverage','contextual-scope','no-structural-defects','historical-preservation']as$id){$check=$this->gate($id);$original=$this->read($r['output'].'/'.$id.'.json');if($check!==$original)$errors[]='Independent gate reconstruction differs: '.$id;}
        $package=$this->read($r['output'].'/package-integrity.json');$check=$this->gate('package-integrity');
        if($package['details']['files']!==$check['details']['files']||$package['errors']!==$check['errors'])$errors[]='Package metadata reconstruction differs';
        if($package['errors']===[])$errors=[...$errors,...$this->archiveErrors($package['details'])];
        $compat=$this->read($r['output'].'/current-compatibility85.json');$expected=$this->compatibility($r['output'].'/compatibility');
        if(array_keys($compat['details'])!==array_keys($expected))$errors[]='Incomplete current compatibility';
        foreach($expected as$id=>$cmd){$row=$compat['details'][$id]??[];if(($row['command']??null)!==$cmd||!isset($row['log'],$row['sha256'])||$this->hash($row['log'])!==$row['sha256'])$errors[]='Changed compatibility command/log: '.$id;}
        return$errors;
    }
}

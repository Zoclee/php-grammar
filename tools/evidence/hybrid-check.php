<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/CompatibilityEvidence.php';
require_once __DIR__ . '/../lib/Certification86.php';
use PhpGrammar\Tools\Support as S;
use PhpGrammar\Tools\HistoricalEvidence as H;
use PhpGrammar\Tools\CompatibilityEvidence as E;
use PhpGrammar\Tools\Certification86 as C;

// Invoked only by certification.php's explicitly selected hybrid contract.
$root = str_replace('\\','/',realpath(S::ROOT));
$e = new E($root);
$inventory = ['contract'=>'historical-plus-current-v1','legacyContract'=>'phase7a-schema1',
    'mapping'=>['historical-certification-check'=>['disposition'=>'retained unchanged; not substituted',
        'additionalRequiredGates'=>['historical-artifact-integrity85','current-compatibility85']]],
    'gates'=>C::gates()+['historical-artifact-integrity85'=>['historical-integrity','Verify immutable 8.5 ledger and every historical input byte; not a historical test rerun.'],
        'current-compatibility85'=>['current-compatibility','Independently verify current bounded 8.5 regression evidence, shared inputs and paired boundaries.']]];
if ($args['inventory']) { echo S::json($inventory); exit(0); }
if ($args['prepare']) throw new RuntimeException('Hybrid preparation cannot overwrite legacy evidence; generate a separate compatibility revision first');
$historical = H::verify($root, $args['historical-snapshot'], $args['historical-supplement']);
$compatibility = [];
try {
    $file = H::safeFile($root, $args['compatibility-report']);
    if ($file === null) throw new RuntimeException('Missing compatibility evidence');
    $compatibility = $e->verify(json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR),str_replace('\\','/',dirname($file)));
} catch(Throwable $ex) { $compatibility[]=$ex->getMessage(); }
$legacyErrors = [];
try {
    $legacy = $c->read($args['report']);
    $legacyErrors = $c->check($legacy);
    if ($args['claims'] || $args['run']) $legacyErrors = [...$legacyErrors,...$c->claimErrors($legacy)];
} catch(Throwable $ex) { $legacyErrors[]=$ex->getMessage(); }
$errors=[...$legacyErrors,...$historical['errors'],...$compatibility];
echo S::json(['contract'=>$inventory['contract'],'legacyErrors'=>$legacyErrors,
    'historicalArtifactIntegrity'=>$historical['complete'],'historicalErrors'=>$historical['errors'],
    'currentCompatibility'=>$compatibility===[],'compatibilityErrors'=>$compatibility,
    'errors'=>$errors,'certificationGranted'=>false,'note'=>'38 additive obligations; the original historical gate is retained. Successor evidence cannot grant PHP 8.6 certification.']);
exit($args['run'] || $errors!==[] ? 1 : 0);

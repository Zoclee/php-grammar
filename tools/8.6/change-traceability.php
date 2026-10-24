<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
require dirname(__DIR__).'/lib/ChangeTraceability.php';
$options=getopt('', ['report:']);
try {
    $map=json_decode(file_get_contents(__DIR__.'/change-map.json'),true,512,JSON_THROW_ON_ERROR);
    $report=\PhpGrammar\Tools\ChangeTraceability::validate(dirname(__DIR__,2),$map);
    if(isset($options['report'])) file_put_contents($options['report'],json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    echo json_encode(['differences'=>$report['differences'],'documentarySections'=>count($report['documentationSections']),'openFindings'=>$report['openFindings'],'errors'=>$report['errors']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
    exit($report['errors']===[]?0:1);
} catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}

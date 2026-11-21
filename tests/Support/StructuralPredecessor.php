<?php
declare(strict_types=1);
namespace PhpGrammar\Tests\Support;

/** Historical assertions run on authenticated pre-structural bytes, never new semantics. */
final class StructuralPredecessor
{
    private static ?string $verified=null;
    public static function root():string {
        if(self::$verified!==null)return self::$verified;
        $root=str_replace('\\','/',dirname(__DIR__,2));
        $path=$root.'/docs/8.6/structural-certification/baseline.json';
        if(hash_file('sha256',$path)!=='6b06748bf3bc7cc7badbd3c432e794c36511c5619f6cb7c0d7f62f7bdb56478b')throw new \RuntimeException('Untrusted predecessor baseline');
        $base=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        $snapshot=$root.'/.audit/structural-predecessor';
        foreach($base['inputs']as$p=>$hash){
            $dest=$snapshot.'/'.$p;
            if(!is_file($dest)){
                $old=$root.'/docs/8.6/structural-certification/predecessor/'.$p;
                $source=is_file($old)?$old:$root.'/'.$p;
                if(!is_file($source)||hash_file('sha256',$source)!==$hash)throw new \RuntimeException('Missing authenticated predecessor: '.$p);
                if(!is_dir(dirname($dest)))mkdir(dirname($dest),0777,true);copy($source,$dest);
            }
            if(hash_file('sha256',$dest)!==$hash)throw new \RuntimeException('Altered predecessor: '.$p);
        }
        return self::$verified=$snapshot;
    }
}

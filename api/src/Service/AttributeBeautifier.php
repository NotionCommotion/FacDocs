<?php
declare(strict_types = 1);

namespace App\Service;

use PhpToken;

class AttributeBeautifier
{
    public function parseFile(string $filename):string
    {

        $isAttribute = false;
        $file = '';
        foreach (PhpToken::tokenize(file_get_contents($filename)) as $token) {
            if(!$isAttribute) {
                if($token->isIgnorable() || $token->getTokenName()!=='T_ATTRIBUTE') {
                    $file.=$token->text;
                    continue;
                }
                // A new attribute
                $isAttribute = true;
                $countB=0;
                $countP = 0;
                $attribute=[];
            }

            if($token->isIgnorable()) {
                continue;
            }
            $c=count_chars($token->text, 1);
            $countB = $countB + ($c[91]??0) - ($c[93]??0);
            $countP = $countP + ($c[40]??0) - ($c[41]??0);
            $attribute[]=$token->text;

            if($countB===0) {
                //End of attribute
                if($countP!==0) {
                    throw new \Exception('Invalid attritbute');
                }
                $isAttribute = false;
                $file .= $this->assembleAttribute($attribute);
            }
        }
        return $file;
    }

    private function assembleAttribute(array $a):string
    {
        return $c < 4
        ?implode('', $a)
        :implode(PHP_EOL, $this->reduce(array_slice($a, 1, -1)));
    }

    private function reduce(array $a):array
    {
        $c = count($a);
        for($i = 1; $i <= $c-1; $i++) {
            switch($x) {
                case '(':
                break;
            }
            if($a[$i+1] === ':') {
                // Property
                if($a[$i+2] === '[') {
                    // array
                    $x = $this->x();

                }
            }
            printf('%s %s %s %s %s %s'.PHP_EOL, $c, $i, 2+$i, $a[2+$i], $c-2-$i, $a[$c-2-$i]);
        }
        exit;
        for ($i = 0; $i <= ($c-5)/2; $i++) {
            printf('%s %s %s %s %s %s'.PHP_EOL, $c, $i, 2+$i, $a[2+$i], $c-2-$i, $a[$c-2-$i]);
        }
        exit;
    }

    public function getAttributes(string $classname):array
    {
        $attributes = [];
        $r = (new \ReflectionClass($classname));
        foreach(array_merge([$r], $r->getProperties(), $r->getMethods(), $r->getConstants()) as $obj) {
            foreach($obj->getAttributes() as $attr) {
                $arguements=[];
                foreach($attr->getArguments() as $argName=>$arg) {
                    $arguements[$argName] = $this->expand($arg);
                    /*
                    if(is_iterable($arg)){
                    $arguements[$argName] = [];
                    foreach($arg as $i=>$a) {
                    $arguements[$argName][$i] = sprintf('$a: %s ', is_object($a)?get_class($a):$a);
                    }
                    }
                    elseif(is_object($arg)) {
                    $arguements[$argName] = sprintf('$arg: %s %s ', get_class($arg), $arg->getName());
                    }
                    else {
                    $arguements[$argName] = $arg;
                    }
                    */
                }
                $attributes[] = ['type'=>get_class($obj), 'subtype'=>get_class($attr), 'class'=>$obj->getName(), 'name'=>$attr->getName(), 'arguements'=>$arguements];
            }
        }
        return $attributes;
    }

    private function expand($p)
    {
        if(is_iterable($p)){
            $r = [];
            foreach($p as $i=>$v) {
                $r[$i] = $this->expand($v);
            }
            return $r;
        }
        return is_object($p)?get_class($p):$p;
    }
}

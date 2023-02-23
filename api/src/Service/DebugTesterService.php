<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

final class DebugTesterService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function print($thing, bool $showSubMethods=false, array $excludedMethods=[], array $extraMethods = [])
    {     
        echo(PHP_EOL.str_repeat('-', 80).PHP_EOL);
        echo($this->infoToString($this->getInfo($thing), false).PHP_EOL);

        if(is_object($thing)) {
            $this->deep($thing, $showSubMethods, $excludedMethods, $extraMethods);
        }
        elseif(is_array($thing) && ($first = array_values($thing)[0]??null) && is_object($first)) {
            $this->deep($first, $showSubMethods, $excludedMethods, $extraMethods);
        }
        echo(PHP_EOL.str_repeat('-', 80).PHP_EOL);
    }

    private function deep(object $o, bool $showSubMethods=false, array $excludedMethods=[], array $extraMethods = [])
    {     
        $rs = $this->getMethodResults($o, $excludedMethods, $extraMethods);

        $methods = get_class_methods($o);
        printf('%sALL METHODS (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($methods));
        //echo(implode(PHP_EOL, $methods).PHP_EOL);

        printf('%sMETHODS WITH RETURNED NON-NULL VALUES (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($rs['validMethods']));
        foreach($rs['validMethods'] as $method=>$info) {
            echo(PHP_EOL.$method.PHP_EOL);
            echo($this->infoToString($info, $showSubMethods).PHP_EOL);
        }
        
        printf('%sMETHODS WHICH RETURNED SELF (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($rs['selfMethods']));
        echo(implode(', ', $rs['selfMethods']).PHP_EOL);

        printf('%sMETHODS WHICH RETURNED NULL (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($rs['nullMethods']));
        echo(implode(', ', $rs['nullMethods']).PHP_EOL);

        printf('%sMETHODS NOT INVOKED DUE TO REQUIRED ARGUEMENTS (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($rs['parameterMethods']));
        $results = array_map(function($k, $v){
            return sprintf('%s(%s)', $k, implode(', ', $v));}, array_keys($rs['parameterMethods']), array_values($rs['parameterMethods'])
        );
        echo(implode(', ', $results).PHP_EOL);

        printf('%sMETHODS WHICH CAUSED AN EXCEPTION'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($rs['exceptionMethods']));
        foreach($rs['exceptionMethods'] as $method=>$info) {
            echo($method.' - '.$info.PHP_EOL);
        }

        printf('%sMETHODS EXCLUDED (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($excludedMethods));
        echo(implode(PHP_EOL, $excludedMethods).PHP_EOL);

        $properties = get_object_vars($o);
        printf('%sPUBLIC PROPERTIES (QUANTITY: %d)'.PHP_EOL, str_repeat('-', 40).PHP_EOL, count($properties));
        echo(implode(PHP_EOL, $properties).PHP_EOL);
    }

    private function getMethodResults(object $o, array $excludedMethods=[], array $extraMethods = []):array
    {
        $rs = ['validMethods'=>[], 'nullMethods'=>[], 'parameterMethods'=>[], 'selfMethods'=>[], 'exceptionMethods'=>[]];
        foreach(get_class_methods($o) as $method) {
            if($method==='__construct'){
                continue;
            }
            if(in_array($method, $excludedMethods)){
                continue;
            }
            $r = new ReflectionMethod(get_class($o), $method);
            if($r->getNumberOfRequiredParameters()) {
                $rs['parameterMethods'][$method]=array_map(function($parameter){return '$'.$parameter->getName();}, $r->getParameters());
            }
            else {
                try {
                    $value=$o->$method();
                }
                catch(\Exception $e) {
                    $rs['exceptionMethods'][$method] = $e->getMessage();
                }
                if(is_null($value)) {
                    $rs['nullMethods'][] = $method;
                }
                elseif(is_object($value) && get_class($value)===get_class($o)) {
                    $rs['selfMethods'][] = $method;
                }
                else {
                    $rs['validMethods'][$method] = $this->getInfo($value);
                }
            }
        }
        return $rs;
    }

    public function toArray(mixed $a):mixed
    {

        if(is_object($a) && !is_iterable($a)) {
            $a = (array) $a;
        }
        if(is_iterable($a)) {
            $l = [];
            foreach($a as $k=>$v) {
                $l[is_int($k)?$k:str_replace(['\\', "\0"], ['.', ':'], trim($k))] = $this->toArray($v);
            }
            return $l;
        }
        else {
            return $a;
        }
    }

    public function getInfo($thing):array
    {
        if(is_object($thing)) {
            $arr = [
                'type' => gettype($thing),
                'class' => get_class($thing),
                'filename' => (new \ReflectionClass(get_class($thing)))->getFileName(),
                'methods' => get_class_methods($thing),
            ];
        }
        elseif(is_array($thing)) {
            $arr = [
                'type' => gettype($thing),
                'count' => count($thing),
                'arrayType' => $this->isAssoc($thing)?'assocative':'sequencial',
                'keys' => $this->isAssoc($thing)?array_keys($thing):'0 to '.count($thing),
                'firstElement'=>($first = array_values($thing)[0]??null)?$this->getInfo($first):null
            ];
        }
        else {
            $arr = [
                'type' => gettype($thing),
                'value' => $thing
            ];
        }
        return $arr;
    }

    private function isAssoc(array $arr):bool
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function infoToString(array $arr, bool $showSubMethods=false):string
    {
        switch($arr['type']) {
            case 'object':
                $arr['methods'] = $showSubMethods?implode(', ', $arr['methods']):sprintf('Quantity of %d', count($arr['methods']));
                break;
            case 'array':
                $arr['keys'] = $this->isAssoc($arr)&&is_array($arr['keys'])?implode(', ', $arr['keys']):$arr['keys'];
                $arr['firstElement'] = $arr['firstElement']?$this->infoToString($arr['firstElement']):'EMPTY';
                break;
        }
        return implode(PHP_EOL, $this->combineKeyValues($arr));
    }

    private function combineKeyValues(array $arr):array
    {
        return array_map(function($k, $v){return "$k: $v";}, array_keys($arr), array_values($arr));
    }

    public function debugThing2($thing, string $msg=''):array
    {
        echo('--Debug Start -------------------------------------------------------'.PHP_EOL);
        echo('--Debug End --------------------------------------------------------'.PHP_EOL);
        if($msg) echo($msg.PHP_EOL);
        if(is_object($thing)) {
            $arr = [
                'type' => gettype($thing),
                'class' => get_class($thing),
                'filename' => (new \ReflectionClass(get_class($thing)))->getFileName(),
                'methods' => get_class_methods($thing),
            ];
            $class = get_class($thing);
            printf('%50s %s'.PHP_EOL, $class, (new \ReflectionClass($class))->getFileName());
            print_r(get_class_methods($thing));
        }
        elseif(is_array($thing)) {
            $arr = [
                'type' => gettype($thing),
                'count' => count($thing),
                'arrayType' => $this->isAssoc($thing)?'assocative':'sequencial',
                'keys' => $this->isAssoc($thing)?array_keys($thing):'0 to '.count($thing),
                'firstElement'=>$thing?$this->debugThing($thing):'EMPTY'
            ];
        }
        /*
        exit;
        echo(get_class($router->getRouteCollection()).PHP_EOL);
        //print_r(get_class_methods($router->getRouteCollection()));
        foreach($router->getRouteCollection() as $route) {
        echo(get_class($route).PHP_EOL);
        print_r(get_class_methods($route));
        exit;            
        }
        exit;
        */
        echo('--Debug End --------------------------------------------------------'.PHP_EOL);
    }

}

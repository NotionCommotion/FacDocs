<?php

declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Yaml\Yaml;
use App\Traits\HelperTrait;
use NilPortugues\Sql\QueryFormatter\Formatter;

class DirtyJsonEncoder implements EncoderInterface, DecoderInterface
{
    use HelperTrait;
    
    //public function __construct(private Formatter $formatter){}

    public function encode(mixed $data, string $format, array $context = []): string
    {
        exit('decode '.$format.PHP_EOL);
        return Yaml::dump($data);
    }

    public function supportsEncoding(string $format):bool
    {
        return 'dirtyjson' === $format;
    }

    public function decode(string $data, string $format, array $context = []):mixed
    {
        $formatter = new Formatter();
        foreach($this->getSqlQueries($this->cleanJson($data)) as $query) {
            echo($formatter->format($query).PHP_EOL);
        }
        exit;
        return $queries;
    }

    public function supportsDecoding(string $format, array $context = []):bool
    {
        return 'dirtyjson' === $format;
    }

    private function cleanJson(string $data):array
    {
        $pattern = '
        /
        \{              # { character
            (?:         # non-capturing group
                [^{}]   # anything that is not a { or }
                |       # OR
                (?R)    # recurses the entire pattern
            )*          # previous group zero or more times
        \}              # } character
        /x
        ';
        preg_match_all($pattern, $data, $matches);
        $rows=[];
        foreach($matches[0] as $row) {
            if(is_string($row)) {
                $arr = json_decode($row, true);
                if(json_last_error() === JSON_ERROR_NONE) {
                    $row = $arr;
                }
            }
            $rows[] = $row;
        }
        return $rows;        
    }

    private function getSqlQueries(array $arr):array
    {
        $queries=[];
        foreach($arr as $row) {
            if(isset($row['sql'])) {
                 $queries[] = $this->showQuery($row['sql'], $row['params']);
            }
        }
        return $queries;
    }
}
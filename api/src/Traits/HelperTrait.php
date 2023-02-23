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

namespace App\Traits;

use ReflectionMethod;
use Exception;
use DateTime;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;
use Nette\Utils\Strings;
use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;

trait HelperTrait
{
    protected function myLogger(?string $msg=null, int $offset=0, bool $time=false):void
    {
        $d1 = debug_backtrace()[$offset+1];
        $d2 = debug_backtrace()[$offset+2];
        $args=[];
        foreach($d1['args'] as $arg) {
            switch(gettype($arg)) {
                case 'object': $arg=$arg::class;break;
                case 'array': $arg=json_encode($arg, JSON_THROW_ON_ERROR);break;
                case 'boolean': $arg=$arg?'TRUE':'FALSE';break;
                case 'NULL': $arg='NULL';break;
                case 'unknown type': $arg='unknown type';break;
                case 'resource': case 'resource (closed)': $arg='resource';break;
            }
            $args[] = $arg;
        }
        syslog(LOG_INFO, sprintf('HelperTrait::myLogger%s: %s::%s(%s) called by %s::%s on line %s%s', $time?'('.date("Y-m-d H:i:s").')':'', $d1['class'], $d1['function'], implode(', ', $args), $d2['class'], $d2['function'], $d2['line'], $msg?' | '.$msg:''));
    }

    protected function getTableInfo(Connection $conn, string $table, array $limitedTables=[]): array
    {
        $sql = <<<EOL
SELECT
    tc.constraint_name,
    tc.table_name,
    tc.is_deferrable,
    tc.initially_deferred,
    tc.enforced,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM
    information_schema.table_constraints AS tc
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name='$table';
EOL;
        $rs = [];
        foreach($conn->query($sql)->fetchAll() as $r) {
            if(!$limitedTables || in_array($r['foreign_table_name'], $limitedTables)) {
                $rs[] = sprintf('%s.%s refernces %s.%s - is_deferrable: %s initially_deferred: %s enforced: %s', $r['table_name'], $r['column_name'], $r['foreign_table_name'], $r['foreign_column_name'], $r['is_deferrable'], $r['initially_deferred'], $r['enforced'], );
            }
        }
        return $rs;
    }

    protected function debugObject($value, array $exclude = [], bool $excludeRepeatedMethods = true, ?int $maxCount = null, ?int $currentCount = 0)
    {
        if (\is_object($value)) {
            ++$currentCount;
            $class = $value::class;
            $response = ['class' => $class, 'properties' => [], 'methods' => [], 'uncheckedMethodsWithArguements' => []];
            if (null === $maxCount || $currentCount <= $maxCount) {
                foreach (get_object_vars($value) as $name => $propertyValue) {
                    $response['properties'][$name] = $this->debugObject($propertyValue, $exclude, $excludeRepeatedMethods, $maxCount, $currentCount);
                }
                foreach (get_class_methods($value) as $method) {
                    $r = new ReflectionMethod($class, $method);
                    if ($params = $r->getParameters()) {
                        $response['uncheckedMethodsWithArguements'][$method] = \count($params);
                    } elseif (\in_array($method, $exclude, true)) {
                        $response['methods'][$method] = 'Excluded';
                    } else {
                        try {
                            $methodValue = $value->{$method}();
                            if ($excludeRepeatedMethods && \is_object($methodValue)) {
                                $exclude[] = $method;
                                $response['skipRepeatedMethods'][] = $method;
                            }
                            $response['methods'][$method] = $this->debugObject($methodValue, $exclude, $excludeRepeatedMethods, $maxCount, $currentCount);
                        } catch (Exception $e) {
                            $response['methods'][$method] = 'ERROR: '.$e->getMessage();
                        }
                    }
                }
            } else {
                $response = $class.' (max count)';
            }
        } elseif (\is_array($value)) {
            $response = [];
            foreach ($value as $key => $arrValue) {
                $response[$key] = $this->debugObject($arrValue, $exclude, $excludeRepeatedMethods, $maxCount, $currentCount);
            }
        } elseif (\is_bool($value)) {
            $response = $value ? 'true' : 'false';
        } else {
            $response = $value;
        }

        return $response;
    }

    protected function testDebugObject(array $values): array
    {
        $output = [];
        foreach ($values as $key => $value) {
            if (\is_object($value)) {
                $output[$key] = 'ERROR!!! '.$value::class;
            } elseif (\is_array($value)) {
                $output[$key] = $this->testDebugObject($value);
            } else {
                $output[$key] = $value;
            }
        }

        return $output;
    }

    protected function debug($obj, $msg): self
    {
        if (\is_object($obj)) {
            printf(\PHP_EOL.'%s: %s'.\PHP_EOL, $msg, $obj::class);
            print_r(get_class_methods($obj));
            // print_r(get_object_vars($obj));
        } elseif (\is_string($obj)) {
            printf(\PHP_EOL.'%s: %s'.\PHP_EOL, $msg, $obj);
        } else {
            printf(\PHP_EOL.'%s: %s'.\PHP_EOL, $msg, null === $obj ? 'NULL' : \gettype($obj));
        }

        return $this;
    }

    protected function _display($m, $v): self
    {
        $t = \gettype($v);
        switch ($t) {
            case 'array':
                printf('%s: %s'.\PHP_EOL, $m, $t);
                foreach ($v as $singleV) {
                    $this->_display($m, $singleV);
                }
                break;
            case 'object':
                printf('%s: %s'.\PHP_EOL, $m, $v::class);
                print_r(get_class_methods($v));
                break;
            default:
                printf('%s: (%s) %s'.\PHP_EOL, $m, $t, $v);
                break;
        }

        return $this;
    }

    /**
     * Get SQL from query.
     *
     * See https://stackoverflow.com/a/27621942/1032531
     *
     * @author Yosef Kaminskyi
     */
    private function _showQuery(string $sql, array $paramsList, array $paramsArr): string
    {
        $fullSql = '';
        for ($i = 0; $i < \strlen($sql); ++$i) {
            if ('?' == $sql[$i]) {
                $nameParam = array_shift($paramsList);

                if (\is_string($paramsArr[$nameParam])) {
                    $fullSql .= '"'.addslashes($paramsArr[$nameParam]).'"';
                } elseif (\is_array($paramsArr[$nameParam])) {
                    $sqlArr = '';
                    foreach ($paramsArr[$nameParam] as $var) {
                        if (!empty($sqlArr)) {
                            $sqlArr .= ',';
                        }

                        if (\is_string($var)) {
                            $sqlArr .= '"'.addslashes($var).'"';
                        } else {
                            $sqlArr .= $var;
                        }
                    }

                    $fullSql .= $sqlArr;
                } elseif (\is_object($paramsArr[$nameParam])) {
                    if ($paramsArr[$nameParam] instanceof DateTime) {
                        $fullSql .= sprintf("'%s'", $paramsArr[$nameParam]->format('Y-m-d H:i:s'));
                    } elseif ($paramsArr[$nameParam] instanceof Ulid || $paramsArr[$nameParam] instanceof Uuid) {
                        $fullSql .= sprintf("'%s'", $paramsArr[$nameParam]->toRfc4122());
                    }
                    else {
                        $fullSql .= $paramsArr[$nameParam]->getId();
                    }
                } else {
                    $fullSql .= $paramsArr[$nameParam];
                }
            } else {
                $fullSql .= $sql[$i];
            }
        }

        return $fullSql;
    }

    protected function showQuery(string $sql, array $data, bool $keepLineBreaks=false):string {
        $keys = [];
        $values = [];
        foreach ($data as $key=>$value) {
            $keys[] = is_string($key)?'/:'.$key.'/':'/[?]/';
            $values[] = is_array($value)?'JSON':(is_numeric($value)?$value:"'$value'");
        }
        $sql = preg_replace($keys, $values, $sql, 1, $count);
        return $keepLineBreaks?$sql:str_replace(array("\r", "\n"), ' ', $sql);
    }

    protected function showDoctrineQuery(DoctrineQuery $doctrineQuery, bool $keepLineBreaks = false): string
    {
        return $this->_showQuery($doctrineQuery->getSql(), $this->getListParamsByDql($doctrineQuery->getDql()), $this->getParamsArray($doctrineQuery->getParameters()));
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getParamsArray(ArrayCollection $arrayCollection): array
    {
        $parameters = [];
        foreach ($arrayCollection as $singleArrayCollection) {
            /* @var $val Doctrine\ORM\Query\Parameter */
            $parameters[$singleArrayCollection->getName()] = $singleArrayCollection->getValue();
        }

        return $parameters;
    }

    /**
     * @return string[]
     */
    private function getListParamsByDql(string $dql): array
    {
        $parsedDql = Strings::split($dql, '#:#');
        $length = \count($parsedDql);
        $parmeters = [];
        for ($i = 1; $i < $length; ++$i) {
            if (ctype_alpha($parsedDql[$i][0])) {
                $param = (Strings::split($parsedDql[$i], "#[' ' )]#"));
                $parmeters[] = $param[0];
            }
        }

        return $parmeters;
    }

    protected function getArrayFromCsvFile(string $csvFile): array
    {
        if (($handle = fopen($csvFile, 'r')) === false) {
            throw new InvalidArgumentException('Cannot open '.$csvFile);
        }
        $results = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            ++$row;
            if (4 !== \count($data)) {
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
            }
            if (!$data[0] && !$data[1] && !$data[2] && !$data[3]) {
                // empty
                $results[$row] = null;
                continue;
            }
            if (array_filter($data, function ($v) { return str_contains($v, 'MasterFormat'); }) && array_filter($data, function ($v) { return 'April 2016' === $v; })) {
                // master
                $results[$row] = null;
                continue;
            }

            if ($data[3]) {
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
            }

            $arr = array_values(array_filter($data, function ($v) { return '' !== $v; }));
            if (1 === \count($arr)) {
                $arr = reset($arr);
                if (ctype_digit($arr)) {
                    // pagenumber
                    $results[$row] = null;
                } else {
                    // overflow
                    $counter = 1;
                    while (null === $results[$row - $counter]) {
                        ++$counter;
                    }
                    $results[$row - $counter][1] .= ' '.$arr;
                }
                continue;
            }

            $parts = explode(' ', $arr[0]);
            if (\count($parts) > 1) {
                if (3 === \count($parts)) {
                    if (2 !== \count($arr)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                    }
                    foreach ($parts as $p) {
                        if (!ctype_digit($p)) {
                            throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                        }
                    }
                    // mainsection
                    $results[$row] = [$arr[0], $arr[1]];
                    continue;
                }
                if (2 === \count($parts)) {
                    if (3 !== \count($arr)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                    }
                    foreach ($parts as $p) {
                        if (!ctype_digit($p)) {
                            throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                        }
                    }
                    if (!ctype_digit($arr[1])) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                    }
                    // mainsection
                    $results[$row] = [$arr[0].' '.$arr[1], $arr[2]];
                    continue;
                }
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                continue;
            }

            $parts = explode('.', $arr[0]);
            if (\count($parts) > 1) {
                if (2 !== \count($arr)) {
                    throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                }
                foreach ($parts as $p) {
                    if (!ctype_digit($p)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
                    }
                }
                // subsection
                $results[$row] = [$arr[0], $arr[1]];
                continue;
            }

            if (2 === \count($arr) && ctype_digit($arr[0]) && $arr[1] && !ctype_digit($arr[1])) {
                // fix data
                $results[$row] = [$arr[0], $arr[1]];
                continue;
            }
            if (3 === \count($arr) && ctype_digit($arr[0]) && is_numeric($arr[1]) && $arr[2] && !ctype_digit($arr[2])) {
                // fix data
                if (!ctype_digit($arr[1])) {
                    $t = explode('.', $arr[1]);
                    $arr[1] = sprintf('%s.%s', str_pad($t[0], 4, '0', \STR_PAD_LEFT), str_pad($t[1], 2, '0', \STR_PAD_LEFT));
                }
                $results[$row] = [$arr[0].' '.$arr[1], $arr[2]];
                continue;
            }

            throw new Exception(sprintf('error parsing spec: %s', json_encode($data)));
        }
        fclose($handle);

        return array_values(array_filter($results, function ($v) { return null !== $v; }));
    }
}

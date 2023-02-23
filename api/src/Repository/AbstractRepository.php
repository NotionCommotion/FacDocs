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

namespace App\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Nette\Utils\Strings;
use Symfony\Component\Uid\Ulid;

abstract class AbstractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry, string $class)
    {
        parent::__construct($managerRegistry, $class);
    }

    /**
     * Get SQL from query.
     *
     * See https://stackoverflow.com/a/27621942/1032531
     *
     * @author Yosef Kaminskyi
     *
     * @return int
     */
    protected function showQuery(string $sql, array $paramsList, array $paramsArr): string
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
                    if ($paramsArr[$nameParam] instanceof DateTimeInterface) {
                        $fullSql .= "'".$paramsArr[$nameParam]->format('Y-m-d H:i:s')."'";
                    }
                    elseif ($paramsArr[$nameParam] instanceof Ulid) {
                        $fullSql .= "'".$paramsArr[$nameParam]->toRfc4122()."'";
                    } else {
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

    protected function showDoctrineQuery(Query $query, bool $keepLineBreaks = false): string
    {
        return $this->showQuery($query->getSql(), $this->getListParamsByDql($query->getDql()), $this->getParamsArray($query->getParameters()));
    }

    public function debug($doctrineObject, bool $print = false): ?string
    {
        if (!$print) {
            ob_start();
            \Doctrine\Common\Util\Debug::dump($doctrineObject);

            return ob_get_clean();
        }
        \Doctrine\Common\Util\Debug::dump($doctrineObject);

        return null;
    }

    protected function getWhereInQuery(int $count, int $start = 0): string
    {
        return '?'.implode(', ?', range($start, $start + $count - 1));
    }

    private function getParamsArray(ArrayCollection $arrayCollection): array
    {
        $parameters = [];
        foreach ($arrayCollection as $singleArrayCollection) {
            /* @var $val Doctrine\ORM\Query\Parameter */
            $parameters[$singleArrayCollection->getName()] = $singleArrayCollection->getValue();
        }

        return $parameters;
    }

    private function getListParamsByDql(string $dql): array
    {
        $parsedDql = Strings::split($dql, '#:#');
        $length = is_countable($parsedDql) ? \count($parsedDql) : 0;
        $parmeters = [];
        for ($i = 1; $i < $length; ++$i) {
            if (ctype_alpha($parsedDql[$i][0])) {
                $param = (Strings::split($parsedDql[$i], "#[' ' )]#"));
                $parmeters[] = $param[0];
            }
        }

        return $parmeters;
    }
}

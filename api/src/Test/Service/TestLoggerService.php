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

namespace App\Test\Service;
use App\Test\Model\Api\LogItemInterface;
use App\Test\Model\Api\TestLogItem;
use App\Test\Model\Api\ResponseInterface;

class TestLoggerService implements \IteratorAggregate
{
    private array $logItems=[];
    private ?int $totalLogCount = null;
    private int $addedLogCount = 0;

    public function addLogItem(string $message, ResponseInterface $apiResponse, ?string $notes=null, array $extra=[]):LogItemInterface
    {
        $logItem = $this->getLogItem($message, $apiResponse, $notes, $extra);
        $this->logItems[] = $logItem;
        if(!is_null($this->totalLogCount)) {
            $msg = $this->getProgressMessage().' '.$logItem->getMessage();
            syslog(LOG_INFO, $msg);
            echo($msg.PHP_EOL);
        }
        return $logItem;
    }

    private function getProgressMessage():string
    {
        return sprintf('%d of %d (count: %d initialTotal: %d added: %d)', $this->addedLogCount + $this->count(), $this->totalLogCount, $this->count(), $this->totalLogCount, $this->addedLogCount);
    }

    public function getTotalLogCount():?int
    {
        return $this->totalLogCount;
    }
    public function setTotalLogCount(?int $totalLogCount):self
    {
        if(!is_null($this->totalLogCount)) {
            throw new \Exception('totalLogCount cannot be changed');
        }
        $this->totalLogCount = $totalLogCount;
        return $this;
    }
    public function addToTotalLogCount(int $addedLogCount): self
    {
        $this->addedLogCount = $this->addedLogCount + $addedLogCount;
        return $this;
    }

    public function getLogItem(string $message, ResponseInterface $apiResponse, ?string $notes=null, array $extra=[]):LogItemInterface
    {
        return new TestLogItem($message, $apiResponse, $notes, $extra);
    }

    public function echoLogItem(string $message, ResponseInterface $apiResponse, ?string $notes=null, array $extra=[]):void
    {
        print_r($apiResponse->getResponse()->getInfo());
        echo(PHP_EOL.PHP_EOL.'--- START ECHO ---'.PHP_EOL);
        echo($this->getLogItem($message, $apiResponse, $notes, $extra)->getDebugMessage());
        echo(PHP_EOL.'--- END ECHO ---'.PHP_EOL.PHP_EOL);
    }

    public function echoDebug(string $message, ResponseInterface $apiResponse, ?string $notes=null, array $extra=[]):void
    {
        $logItem = $this->getLogItem($message, $apiResponse, $notes, $extra);
        printf(PHP_EOL.PHP_EOL.'--- START DEBUG (%s) ---%s%s'.PHP_EOL.PHP_EOL, $logItem::class, PHP_EOL, $logItem->getDebugMessage());
        print_r($logItem->getData());
        print_r($apiResponse->getResponse()->getInfo());
        echo(PHP_EOL.'--- END DEBUG ---'.PHP_EOL.PHP_EOL);
    }

    public function getIterator():\Traversable
    {
        return new \ArrayIterator($this->logItems);
    }

    public function count():int
    {
        return count($this->logItems);
    }

    public function debug():array
    {
        $arr=[];
        foreach($this->logItems as $logItem) {
            $arr[] = $logItem->getMessage();
        }
        return $arr;
    }
}

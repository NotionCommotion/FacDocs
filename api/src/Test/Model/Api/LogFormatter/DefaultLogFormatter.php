<?php

declare(strict_types=1);

namespace App\Test\Model\Api\LogFormatter;
use App\Test\Service\TestLoggerService;
use App\Test\Model\Api\TestLogItem;
use App\Test\Model\Api\EntityResponse;
use App\Test\Model\Api\ApiRequest;

class DefaultLogFormatter implements LogFormatterInterface
{
    public function process(TestLoggerService $testLoggerService):void
    {
        echo(PHP_EOL.str_repeat('#', 200).PHP_EOL);
        foreach($testLoggerService as $logItem) {
            echo(PHP_EOL.$this->getBoarder('LOG ITEM START').PHP_EOL);
            $entityResponse = $logItem->getEntityResponse();
            $apiRequest = $entityResponse->getApiRequest();
            if($message = $logItem->getMessage()??$this->getDefaultMessage($apiRequest)) {
                echo($message.PHP_EOL);
            }
            printf('REQUEST: %s %s:%s  Data:%s'.PHP_EOL, $apiRequest->getMethod(), $apiRequest->getPath(), PHP_EOL, json_encode($apiRequest->getData()));
            if($entityResponse->isSuccessful()) {
                printf('SUCCESS RESPONSE: Status: %d:%s   Response: %s'.PHP_EOL, $entityResponse->getStatusCode(), PHP_EOL, json_encode($entityResponse->toArray()));
            }
            else {
                printf('ERROR RESPONSE: Status: %d'.PHP_EOL, $entityResponse->getStatusCode());
            }
            echo(PHP_EOL.$this->getBoarder('LOG ITEM END').PHP_EOL);
        }
        echo(PHP_EOL.str_repeat('-', 100).PHP_EOL.str_repeat('#', 200).PHP_EOL);
    }

    private function getBoarder(string $name):string
    {
        return sprintf('%s %s %s', str_repeat('-', 10), $name, str_repeat('-', 100-strlen($name)));
    }

    private function getDefaultMessage(ApiRequest $apiRequest):?string
    {
        return 'Standard request';
    }
}

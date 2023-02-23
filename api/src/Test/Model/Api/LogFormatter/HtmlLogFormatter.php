<?php

declare(strict_types=1);

namespace App\Test\Model\Api\LogFormatter;
use App\Test\Service\TestLoggerService;
use App\Test\Model\Api\LogItemInterface;
use App\Test\Model\Api\TestLogItem;
use App\Test\Model\Api\MessageLogItem;
use App\Test\Model\Api\EntityResponse;
use App\Test\Model\Api\ApiRequest;

class HtmlLogFormatter implements LogFormatterInterface
{
    private const STANDARD_CSS = [['//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css', 'sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65', 'anonymous']];
    private const STANDARD_JS =  [['//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js', 'sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V', 'anonymous']];

    public function __construct(private string $file)
    {
    }

    public function process(TestLoggerService $testLoggerService):void
    {
        $htmlRows = [];
        $jsRows = [];
        $i=0;
        foreach($testLoggerService as $logItem) {
            if($logItem instanceof TestLogItem) {
                $htmlRows[] = $this->getHtmlRow($logItem, $i);
                $jsRows[] = $this->getJsRow($logItem);
                $i++;
            }
            else {
                $htmlRows[] = $this->getHtmlMessageRow($logItem);
            }
        }
        $html = $this->getHtml($this->getMainPage($htmlRows), $this->getCssResources([], [$this->getCss()]), $this->getJsResources([], [$this->getJs($jsRows)]));
        file_put_contents($this->file, $html);
    }

    private function getJs(array $jsRows):string
    {
        $jsRows = json_encode($jsRows);
        return <<<EOL
<script>
    const data=$jsRows;
	const testModal = document.getElementById('testModal')
	testModal.addEventListener('show.bs.modal', event => {
		function setHeaders(listId, o) {
			let list = document.getElementById(listId);
			empty(list);
			for (const p in o) {
				let li = document.createElement("li");
				li.textContent = p + ": " + ((typeof o[p] == 'array')?o[p].join(';'):o[p]);
				list.appendChild(li);
			}
		}

		function empty(element) {
			while (element.firstElementChild) {
				element.firstElementChild.remove();
			}
		}

		const item = data[event.relatedTarget.getAttribute('data-id')];
		console.log(item);

		document.getElementById('message').textContent = item.message;
		document.getElementById('method').textContent = item.request.method;
		document.getElementById('status').textContent = item.response.statusCode;
		document.getElementById('path').textContent = item.request.path;
		document.getElementById('request-body').textContent = JSON.stringify(item.request.body, null, 2);
		document.getElementById('response-body').textContent = JSON.stringify(item.response.body, null, 2);
		setHeaders('request-headers', item.request.headers);
		setHeaders('response-headers', item.response.headers);
	})
</script>
EOL;
    }

    private function getCss():string
    {
        return <<<EOL
<style type="text/css">
.headers li {
  list-style-type: none;
 }
li.log-row {
  list-style-type: none;
  padding-left: 10px;
 }
li.message-row {
  list-style-type: none;
  font-weight: bold;
 }
#method {
  display: inline-block;
  width: 50px;
}
</style>
EOL;
    }

    private function getHtmlRow(TestLogItem $logItem, int $index):string
    {
        return sprintf('<li class="log-row" data-bs-toggle="modal" data-bs-target="#testModal" data-bs-id="@%d" data-id="%d">%s</li>', $index, $index, $logItem->getMessage());

    }
    private function getHtmlMessageRow(MessageLogItem $logItem):string
    {
        return sprintf('<li class="message-row">%s</li>', $logItem->getMessage());

    }

    private function getJsRow(TestLogItem $logItem):array
    {
        $entityResponse = $logItem->getEntityResponse();
        $apiRequest = $entityResponse->getApiRequest();
        if($this->isSuccessful($entityResponse->getStatusCode())) {
            $response = [
                'statusCode' => $entityResponse->getStatusCode(),
                'headers' => $entityResponse->getHeaders(),
                'body' => $entityResponse->toArray(),
            ];
        }
        else {
            $info = $entityResponse->getInfo();
            $response = [
                'statusCode' => $entityResponse->getStatusCode(),
                'headers' => $info['response_headers'],
                'body' => $info['error']??'[NO ERROR PROVIDED]',
            ];
        }
        return [
            'message' => $logItem->getMessage(),
            'request' =>[
                'method' => $apiRequest->getMethod(),
                'path' => $apiRequest->getPath(),
                'body' => $apiRequest->getBody(),
                'parameters' => $apiRequest->getExtra(), //TBD whether correct.
                'headers' => $apiRequest->getHeaders(true),
            ],
            'response' => $response
        ];
    }

    private function getMainPage(array $htmlRows):string
    {
        $date = date('Y-m-d H:i:s');
        $htmlRows = implode(PHP_EOL, $htmlRows);
        $date = date('Y-m-d H:i:s');
        return <<<EOL
<h2>API TESTING - $date</h2>
<ul id="message-list">
$htmlRows
</ul>
<div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="testModalLabel">Test Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 class="mb-3" id="message"></h5>
                <p><span id="method"></span><span id="path"></span></p>
                <div class="mb-3">
                    <pre><code id="request-body"></code></pre>
                </div>
                <div class="mb-3">
                    <label for="request-headers" class="col-form-label headers">Headers:</label>
                    <ul id="request-headers" class="headers"></ul>
                </div>
                <p>Response - Status: <span id="status"></span></p>
                <div class="mb-3">
                    <pre><code id="response-body"></code></pre>
                </div>
                <div class="mb-3">
                    <label for="response-headers" class="col-form-label headers">Headers:</label>
                    <ul id="response-headers" class="headers"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<hr>
EOL;
    }

    private function getHtml(string $html, string $css, string $js):string
    {   
        $date = date('Y-m-d H:i:s');
        return <<<EOL
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Facdocs Tester</title>
        <meta name="generator" content="HtmlLogFormatter">
        <meta name="description" content="PHP Unit Testing">
        <meta name="creation-date" content="$date">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        $css
    </head>
    <body>
        <div class="container">
            $html
        </div>
        $js
    </body>
</html>
EOL;
    }

    private function getCssResources(array $files=[], array $custom=[]):string
    {
        return $this->getResource('<link rel="stylesheet" href="%s"%s%s>', array_merge(self::STANDARD_CSS,$files), $custom);
    }
    private function getJsResources(array $files=[], array $custom=[]):string
    {
        return $this->getResource('<script src="%s"%s%s></script>', array_merge(self::STANDARD_JS,$files), $custom);
    }
    private function getResource(string $template, array $files=[], array $custom=[]):string
    {
        return implode(PHP_EOL, array_merge(array_map(function(array $r)use($template){return sprintf($template, $r[0], $r[1]?sprintf(' integrity="%s"', $r[1]):'', $r[2]?sprintf(' crossorigin="%s"', $r[2]):'');},$files,),$custom));
    }

    private function getDefaultMessage(ApiRequest $apiRequest):?string
    {
        return 'Standard request';
    }

    private function isSuccessful(int $status):bool
    {
        return (200 <= $status) && ($status < 300);
    }
}

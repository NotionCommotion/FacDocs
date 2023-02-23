<?php

declare(strict_types=1);

namespace App\Test\Model\Api\LogFormatter;
use App\Test\Service\TestLoggerService;
use App\Test\Model\Api\LogItemInterface;
use App\Test\Model\Api\TestLogItem;
use App\Test\Model\Api\MessageLogItem;
use App\Test\Model\Api\ApiRequest;
use ApiPlatform\Symfony\Bundle\Test\Response;

class HtmlTableLogFormatter implements LogFormatterInterface
{
    private const STANDARD_CSS = [['//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css', 'sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65', 'anonymous']];
    private const STANDARD_JS =  [['//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js', 'sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V', 'anonymous']];

    public function __construct(private string $file)
    {
    }

    public function process(TestLoggerService $testLoggerService):void
    {
        $data = [];
        $asserts=0;
        foreach($testLoggerService as $logItem) {
            if($logItem instanceof LogItemInterface) {
                $data[] = $logItem->getData($logItem);
                $asserts += count($logItem->getAsserts());
            }
            else {
                throw new \Exception('Extra rows are not supported');
            }
        }
        $html = $this->getHtml($this->getMainPage(count($data), $asserts), $this->getCssResources([], [$this->getCss()]), $this->getJsResources([], [$this->getJs($data)]));
        file_put_contents($this->file, $html);
    }

    private function getJs(array $data):string
    {
        $data = json_encode($data);
        return <<<EOL
<script>
    const response = {
       "data": $data
    }

    const tableContent = document.getElementById("table-content")
    const tableButtons = document.querySelectorAll("th button");

    function createCell(content, attribute)
    {
        const cell = document.createElement("td");
        cell.setAttribute("data-attr", attribute);
        cell.innerHTML = content;
        return cell;
    }

    const createRow = (obj, count) => {
        const row = document.createElement("tr");
        row.setAttribute("data-bs-toggle", "modal");
        row.setAttribute("data-bs-target", "#detailModal");
        row.appData = {request: obj.request, response: obj.response, access: obj.access, asserts: obj.asserts};
        row.appendChild(createCell(count, 'count'));
        row.appendChild(createCell(obj.message, 'message'));
        row.appendChild(createCell(obj.asserts.length, 'asserts'));
        row.appendChild(createCell(obj.response.anticipatedStatusCode, 'anticipatedStatusCode'));
        row.appendChild(createCell(obj.response.statusCode, 'statusCode'));
        row.appendChild(createCell(obj.request.method, 'method'));
        row.appendChild(createCell(obj.request.path, 'path'));
        return row;
    };

    const getTableContent = (data) => {
        let count = 0;
        data.map((obj) => {
            count++;
            const row = createRow(obj, count);
            tableContent.appendChild(row);
        });
    };

    const sortData = (data, param, direction = "asc") => {
        tableContent.innerHTML = '';
        const sortedData =
        direction == "asc"
        ? [...data].sort(function (a, b) {
            if (a[param] < b[param]) {
                return -1;
            }
            if (a[param] > b[param]) {
                return 1;
            }
            return 0;
        })
        : [...data].sort(function (a, b) {
            if (b[param] < a[param]) {
                return -1;
            }
            if (b[param] > a[param]) {
                return 1;
            }
            return 0;
        });

        getTableContent(sortedData);
    };

    const resetButtons = (event) => {
        [...tableButtons].map((button) => {
            if (button !== event.target) {
                button.removeAttribute("data-dir");
            }
        });
    };

    window.addEventListener("load", () => {
        getTableContent(response.data);

        [...tableButtons].map((button) => {
            button.addEventListener("click", (e) => {
                resetButtons(e);
                if (e.target.getAttribute("data-dir") == "desc") {
                    sortData(response.data, e.target.id, "desc");
                    e.target.setAttribute("data-dir", "asc");
                } else {
                    sortData(response.data, e.target.id, "asc");
                    e.target.setAttribute("data-dir", "desc");
                }
            });
        });
    });

    // Remainder is for modal
    const detailModal = document.getElementById('detailModal')
    detailModal.addEventListener('show.bs.modal', event => {
        function setHeaders(listId, o, cls) {
            let list = document.getElementById(listId);
            empty(list);
            for (const p in o) {
                let li = document.createElement("li");
                if(cls) li.classList.add(cls);
                li.textContent = (typeof o[p] == 'array')?o[p].join(';'):o[p];
                list.appendChild(li);
            }
        }

        function setAssertList(listId, o, cls) {
            let list = document.getElementById(listId);
            empty(list);
            for (const a in o) {
                let assert = document.createElement("li");
                if(cls) assert.classList.add(cls);
                let method = document.createElement("p");
                method.textContent = o[a].name;
                assert.appendChild(method)

                let args = document.createElement("ul");
                assert.appendChild(args);
                for (const e in o[a].arguments) {
                    let arg = document.createElement("li");
                    arg.textContent = e+": "+(typeof o[a].arguments[e] === 'object'? JSON.stringify(o[a].arguments[e]): o[a].arguments[e]);
                    args.appendChild(arg)
                }
                list.appendChild(assert);
            }
        }

        function empty(element) {
            while (element.firstElementChild) {
                element.firstElementChild.remove();
            }
        }

        const item = event.relatedTarget;
        const appData = item.appData;
        
        const aclModal = document.getElementById("modal-acl-data");
        if(appData.access.acl) {
            function permissionToString(p) {
                if(!p) return 'N/A';
                var a = [];
                for (const n in p) {
                    a.push(n+': '+p[n]);
                }
                return a.join(' ');
            }

            const aclList = aclModal.querySelector('.list-group');
            const s = aclList.querySelectorAll("span");

            const member = appData.access.acl.member;
            const aclData = [
                appData.access.isAuthorized?'YES':'NO',
                appData.access.resource.type,
                'user: '+permissionToString(appData.access.acl.resourcePermissionSet.tenant.user)+' member: '+permissionToString(appData.access.acl.resourcePermissionSet.tenant.member),
                'user: '+permissionToString(appData.access.acl.resourcePermissionSet.vendor.user)+' member: '+permissionToString(appData.access.acl.resourcePermissionSet.vendor.member),
                'user: '+permissionToString(appData.access.acl.resourcePermissionSet.tenant.user)+' member: '+permissionToString(appData.access.acl.resourcePermissionSet.tenant.member),
                'user: '+permissionToString(appData.access.acl.resourcePermissionSet.vendor.user)+' member: '+permissionToString(appData.access.acl.resourcePermissionSet.vendor.member),
                appData.access.user.type,
                appData.access.user.roles.join(', '),
                member.resourcePermission?'Yes':'No',
                member.resourcePermission?member.roles.join(', '):'N/A',
                member.resourcePermission?permissionToString(member.resourcePermission):'N/A',
                member.documentPermission?'Yes':'No',
                member.documentPermission?permissionToString(member.documentPermission):'N/A',
            ]

            for (let i = 0; i < aclData.length; i++) {
                s[i].textContent = aclData[i];
            }
            aclModal.style.display = 'block';
        }
        else {
            aclModal.style.display = 'none';
        }

        document.getElementById('modal-message').textContent = '#'+item.firstElementChild.innerHTML+" - "+item.firstElementChild.nextElementSibling.innerHTML;
        //document.getElementById('modal-general').textContent = appData.access.message;
        document.getElementById('modal-method').textContent = appData.request.method;
        document.getElementById('modal-anticipated-status').textContent = appData.response.anticipatedStatusCode;
        document.getElementById('modal-status').textContent = appData.response.statusCode;
        document.getElementById('modal-path').textContent = appData.request.path;
        document.getElementById('modal-request-body').textContent = JSON.stringify(appData.request.body, null, 2);
        document.getElementById('modal-response-body').textContent = JSON.stringify(appData.response.body, null, 2);
        setHeaders('modal-request-headers', appData.request.headers, 'list-group-item');
        setHeaders('modal-response-headers', appData.response.headers, 'list-group-item');
        document.getElementById('modal-assert-quantity').textContent = appData.asserts.length;
        setAssertList('modal-assert-list', appData.asserts, 'list-group-item');
    })
</script>
EOL;
    }

    private function getCss():string
    {
        return <<<EOL
<style type="text/css">
#modal-method {
  display: inline-block;
  width: 80px;
}

/* Remainder is for table */
@import url("https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap");

body {
  font-family: "Lato", sans-serif;
}

.table-container {
  margin: auto;
  max-width: 1200px;
  min-height: 100vh;
  overflow: scroll;
  width: 100%;
}

table {
  border-collapse: collapse;
  width: 100%;
}

thead tr {
  border-bottom: 1px solid #ddd;
  border-top: 1px solid #ddd;
  height: 1px;
}

th {
  font-weight: bold;
  height: inherit;
  padding: 0;
}

th:not(:first-of-type) {
  border-left: 1px solid #ddd;
}

th button {
  background-color: #eee;
  border: none;
  cursor: pointer;
  display: block;
  font: inherit;
  height: 100%;
  margin: 0;
  min-width: max-content;
  padding: 0.5rem 1rem;
  position: relative;
  text-align: left;
  width: 100%;
}

th button::after {
  position: absolute;
  right: 0.5rem;
}

th button[data-dir="asc"]::after {
  content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpolygon points='0, 0 8,0 4,8 8' fill='%23818688'/%3E%3C/svg%3E");
}

th button[data-dir="desc"]::after {
  content: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpolygon points='4 0,8 8,0 8' fill='%23818688'/%3E%3C/svg%3E");
}

tbody tr {
  border-bottom: 1px solid #ddd;
}

td {
  padding: 0.5rem 1rem;
  text-align: left;
}

footer {
  background-color: #ffdfb9;
  margin: 2rem -8px -8px;
  padding: 1rem;
  text-align: center;
}

footer a {
  color: inherit;
  text-decoration: none;
}

footer .heart {
  color: #dc143c;
}
</style>
EOL;
    }

    private function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    private function getMainPage(int $requests, int $asserts):string
    {
        $date = date('Y-m-d H:i:s');
        return <<<EOL
<h2>API TESTING - $date</h2>
<h3>Total Requests - $requests  Total Asserts - $asserts</h2>
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Count</th>
                <th><button id="message">Message</button></th>
                <th><button id="asserts">Asserts</button></th>
                <th><button id="anticipatedStatusCode">Expected Status</button></th>
                <th><button id="status">Actual Status</button></th>
                <th><button id="method">Method</button></th>
                <th><button id="path">Path</button></th>
            </tr>
        </thead>
        <tbody id="table-content"></tbody>
    </table>
</div>
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="modal-message" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-message"></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modal-general" class="mb-3"></div>
                <div id="modal-acl-data" class="mb-3">
                    <h5 class="mb-3">ACL Data</h5>
                    <ul class="list-group">
                        <li class="list-group-item">Is Authorized: <span></span></li>
                        <li class="list-group-item">Resource Type: <span></span></li>
                        <li class="list-group-item">Resource Tenant Access Policy: <span></span></li>
                        <li class="list-group-item">Resource Vendor Access Policy: <span></span></li>
                        <li class="list-group-item">Document Tenant Access Policy: <span></span></li>
                        <li class="list-group-item">Document Vendor Access Policy: <span></span></li>
                        <li class="list-group-item">User Type: <span></span></li>
                        <li class="list-group-item">User Roles: <span></span></li>
                        <li class="list-group-item">Is Resource Member: <span></span></li>
                        <li class="list-group-item">Resource Member Roles: <span></span></li>
                        <li class="list-group-item">Resource Member Permission: <span></span></li>
                        <li class="list-group-item">Is Document Member: <span></span></li>
                        <li class="list-group-item">Document Member Permission: <span></span></li>
                    </ul>
                </div>

                <div id="modal-request" class="mb-3">
                    <h5 class="mb-3">Request</h5>
                    <ul class="list-group">
                        <li class="list-group-item"><span id="modal-method"></span><span id="modal-path"></span></li>
                        <li class="list-group-item">
                            <label for="modal-request-headers" class="col-form-label headers">Headers:</label>
                            <ul id="modal-request-headers" class="list-group"></ul>
                        </li>
                        <li class="list-group-item">Body:<pre><code id="modal-request-body"></code></pre></li>
                    </ul>
                </div>

                <div id="modal-response" class="mb-3">
                    <h5 class="mb-3">Response - Anticipated Status: <span id="modal-anticipated-status"></span> Actual Status: <span id="modal-status"></span></h5>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <label for="modal-response-headers" class="col-form-label headers">Headers:</label>
                            <ul id="modal-response-headers" class="list-group"></ul>
                        </li>
                        <li class="list-group-item">Body:<pre><code id="modal-response-body"></code></pre></li>
                    </ul>
                </div>
                <div id="modal-asserts" class="mb-3">
                    <h5 class="mb-3">Asserts - Quantity of <span id="modal-assert-quantity"></span></h5>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <ul id="modal-assert-list" class="list-group"></ul>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<hr>
</div>
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
}

<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

function getConnection()
{
    global $settings;
    $dbSettings = $settings["settings"]["db"];
    $pdoSettings = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );

    return new PDO("mysql:host=".$dbSettings["host"].";dbname=".$dbSettings["db"], $dbSettings["user"], $dbSettings["pwd"], $pdoSettings);
}

$app->get('/data', function($request, $response) {
    $conn = getConnection();
    $result = [
        "data"=> [],
        "links" => []
    ];

    foreach($conn->query("SELECT * FROM gantt_tasks") as $row){
        $row["open"] = true;
        $result["data"][] = $row;
    }

    $result["links"] = array();
    foreach ($conn->query("SELECT * FROM gantt_links") as $link){
        $result["links"][] = $link;
    }

    $response->withJson($result);
    return $response;
});

function prepareResponse($res, $action, $tid = NULL){
    $result = array(
        'action' => $action
    );
    if(isset($tid) && !is_null($tid)){
        $result['tid'] = $tid;
    }
    $res->withJson($result);
    return $result;
}

function getEvent($data)
{
    return array(
        ':text' => $data["text"],
        ':start_date' => $data["start_date"],
        ':duration' => $data["duration"],
        ':progress' => isset($data["progress"]) ? $data["progress"] : 0,
        ':parent' => $data["parent"]
    );
}

function getLink($data){
    return array(
        ":source" => $data["source"],
        ":target" => $data["target"],
        ":type" => $data["type"]
    );
}

$app->post('/data/task', function($request, $response){
    $event = getEvent($request->getParsedBody());
    $conn = getConnection();
    $query = "INSERT INTO gantt_tasks(text, start_date, duration, progress, parent) ".
  "VALUES (:text,:start_date,:duration,:progress,:parent)";
    $conn->prepare($query)->execute($event);
    return prepareResponse($response, "inserted", $conn->lastInsertId());
});

$app->put('/data/task/{id}', function($request, $response){
    $sid = $request->getAttribute("id");
    $event = getEvent($request->getParsedBody());
    $conn = getConnection();
    $query = "UPDATE gantt_tasks ".
    "SET text = :text, start_date = :start_date, duration = :duration, progress = :progress, parent = :parent ".
    "WHERE id = :sid";

    $conn->prepare($query)->execute(array_merge($event, array(":sid"=>$sid)));
    return prepareResponse($response, "updated");
});

$app->delete('/data/task/{id}', function($request, $response){
    $sid = $request->getAttribute("id");
    $conn = getConnection();
    $query = "DELETE FROM gantt_tasks WHERE id = :sid";

    $conn->prepare($query)->execute(array(":sid"=>$sid));
    return prepareResponse($response, "deleted");
});

$app->post('/data/link', function($request, $response){
    $link = getLink($request->getParsedBody());
    $conn = getConnection();
    $query = "INSERT INTO gantt_links(source, target, type) VALUES (:source,:target,:type)";
    $conn->prepare($query)->execute($link);
    return prepareResponse($response, "inserted", $conn->lastInsertId());
});

$app->put('/data/link/{id}', function($request, $response){
    $sid = $request->getAttribute("id");
    $link = getLink($request->getParsedBody());
    $conn = getConnection();
    $query = "UPDATE gantt_links SET ".
    "source = :source, target = :target, type = :type ".
    "WHERE id = :sid";

    $conn->prepare($query)->execute(array_merge($link, array(":sid"=>$sid)));
    return prepareResponse($response, "updated");
});

$app->delete('/data/link/{id}', function($request, $response){
    $sid = $request->getAttribute("id");
    $conn = getConnection();
    $query = "DELETE FROM gantt_links WHERE id = :sid";

    $conn->prepare($query)->execute(array(":sid"=>$sid));
    return prepareResponse($response, "deleted");
});

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

// Run app
$app->run();

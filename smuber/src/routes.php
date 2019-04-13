<?php

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;
use function Monolog\Handler\error_log;



$app->options('/{routes:.+}', function ($request, $response, $args) {
  return $response;
});

$app->add(function ($req, $res, $next) {
  $response = $next($req, $res);
  return $response
          ->withHeader('Access-Control-Allow-Origin', '*')
          ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});


$app->post('/login', function (Request $request, Response $response, array $args) {
 
    $input = $request->getParsedBody();
    $sql = "SELECT * FROM drivers WHERE userName= :userName";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("userName", $input['userName']);
    $sth->execute();
    $user = $sth->fetchObject();

    // verify email address.
    if(!$user) {
        return $this->response->withJson(['error' => true]);  
    }

    // verify password.
    if ($input['pWord'] != $user->pWord) {
        return $this->response->withJson(['error' => true]);  
    }
    return $this->response->withJson(['error' => false]);

});

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->group('/api', function () use ($app) {
  
    $app->post('/registration', function ($request, $response) {
      #creates new user
      $input = $request->getParsedBody();
      $sql = $this->db->prepare(
        "SELECT userName FROM drivers WHERE userName=:userName"
      );
      $sql->bindParam("userName", $input['userName']);
      $sql->execute();
      $check = $sql->fetchObject();
      if($check === false){
        $qr = "INSERT INTO drivers (userName, pWord, lastName, firstName, id) 
        VALUES (:userName, :pWord, :lastName, :firstName, :id)";
        $sth = $this->db->prepare($qr);
        $sth->bindParam("userName", $input['userName']);
        $sth->bindParam("pWord", $input['pWord']);
        $sth->bindParam("lastName", $input['lastName']);
        $sth->bindParam("firstName", $input['firstName']);
        $sth->bindParam("id", $input['id']);
        $sth->execute();
        return $this->response->withJson($input);
      } else {
        return $this->response->withJson(['error' => true, 'message' => 'Username already in use']);
      }
       
    });

    $app->get('/login/[{userName}]', function ($request, $response, $args) {
       $sth = $this->db->prepare(
         "SELECT * FROM drivers WHERE userName=:userName"
       );
       $sth->bindParam("userName", $args['userName']); $sth->execute();
       $users = $sth->fetchObject();
       return $this->response->withJson($users);
    });

    $app->put('/edit', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE drivers
          SET lastName=:lastName, firstName=:firstName, pWord=:pWord
          WHERE userName=:userName"
      );
      $sth->bindParam("lastName", $input['lastName']);
      $sth->bindParam("firstName", $input['firstName']);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("pWord", $input['pWord']);
      $sth->execute();
      return $this->response->withJson($input);
    });
  
  $app->put('/edit-loc', function($request, $response, $args){
    $input=$request->getParsedBody();
    $sql="UPDATE location SET latitude=:latitude, longitude=:longitude WHERE userName=:userName";
    $sth=$this->db->prepare($sql);
    $sth->bindParam("latitude",$input['latitude']);
    $sth->bindParam("longitude",$input['longitude']);
    $sth->bindParam("userName",$input['userName']);
    $sth->execute();
    return $this->response->withJson($input);
});

  $app->post('/add-loc', function ($request, $response, $args) {
    $input = $request->getParsedBody();
    $sql = "INSERT INTO location (latitude, longitude, userName)
            VALUES (:latitude, :longitude, :userName)";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("latitude",$input['latitude']);
    $sth->bindParam("longitude",$input['longitude']);
    $sth->bindParam("userName",$input['userName']);
    $sth->execute();
    return $this->response->withJson($input);
  });

  $app->get('/get-loc', function($request, $response, $args){
    $sth = $this->db->prepare("SELECT * FROM location WHERE userName=:userName");
    $sth->bindParam("userName", $args['userName']);
    $sth->execute();
    $locationInfo = $sth->fetchAll();
    return $this->response->withJson($locationInfo);


  });

});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
  });

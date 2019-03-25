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
    $sql = "SELECT * FROM users WHERE userName= :userName";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("userName", $input['userName']);
    $sth->execute();
    $user = $sth->fetchObject();

    // verify email address.
    if(!$user) {
        return $this->response->withJson(['error' => true, 'message' => 'Username or Password is not valid.']);  
    }

    // verify password.
    if ($input['pWord'] != $user->pWord) {
        return $this->response->withJson(['error' => true, 'message' => 'Username or Password is not valid.']);  
    }
    return $this->response->withJson(['userName' => $user->userName]);

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
        "SELECT userName FROM users WHERE userName=:userName"
      );
      $sql->bindParam("userName", $input['userName']);
      $sql->execute();
      $check = $sql->fetchObject();
      if($check === false){
        $qr = "INSERT INTO users (userName, pWord, lastName, firstName, email, income, bal) 
        VALUES (:userName, :pWord, :lastName, :firstName, :email, :income, 0)";
        $sth = $this->db->prepare($qr);
        $sth->bindParam("userName", $input['userName']);
        $sth->bindParam("pWord", $input['pWord']);
        $sth->bindParam("lastName", $input['lastName']);
        $sth->bindParam("firstName", $input['firstName']);
        $sth->bindParam("email", $input['email']);
        $sth->bindParam("income", $input['income']);
        #$sth->bindParam("bal", $input['bal']);
        $sth->execute();
        #creates budget items init to 0
        
        $budget_sql = "INSERT INTO budgets (userName, budgetType, active_date, amt) 
        VALUES (:userName, :budgetType, now(), 0)";
        
        $budget_sth = $this->db->prepare($budget_sql);
        $types = array("Savings","Ent.","Util.","Food","Car","House","Misc.");
        $budget_sth->bindParam("userName", $input['userName']);
        foreach($types as $type){
          $budget_sth->bindParam("budgetType", $type); 
          $budget_sth->execute();
        }
        #creates expenses init to 0
        $ex_sql = "INSERT INTO expenses (userName, exType, date, amt) 
        VALUES (:userName, :exType, now(), 0)";
        
        $ex_sth = $this->db->prepare($ex_sql);
        $extypes = array("Ent.","Util.","Food","Car","House","Misc.","Savings");
        $ex_sth->bindParam("userName", $input['userName']);
        foreach($extypes as $extype){
          $ex_sth->bindParam("exType", $extype); 
          $ex_sth->execute();
        }
        return $this->response->withJson($input);
      } else {
        return $this->response->withJson(['error' => true, 'message' => 'Username already in use']);
      }
       
    });

    $app->get('/login/[{userName}]', function ($request, $response, $args) {
       $sth = $this->db->prepare(
         "SELECT * FROM users WHERE userName=:userName"
       );
       $sth->bindParam("userName", $args['userName']); $sth->execute();
       $users = $sth->fetchObject();
       return $this->response->withJson($users);
    });

    $app->put('/edit', function ($request, $response, $args) {
      $input = $request->getParsedBody();
      $sth = $this->db->prepare(
          "UPDATE users
          SET lastName=:lastName, firstName=:firstName, email=:email, pWord=:pWord, income=:income
          WHERE userName=:userName"
      );
      $sth->bindParam("lastName", $input['lastName']);
      $sth->bindParam("firstName", $input['firstName']);
      $sth->bindParam("email", $input['email']);
      $sth->bindParam("userName", $input['userName']);
      $sth->bindParam("pWord", $input['pWord']);
      $sth->bindParam("income", $input['income']);
      $sth->execute();
      return $this->response->withJson($input);
    });

});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
  });
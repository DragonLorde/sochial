<?php

require_once('core/bd.php');
require_once('errorHandler.php');
require_once("app.php");
require_once("service/excel.php");
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class API {
    
    private $base;
    private $params;
    private $data;

    function __construct($bd)
    {
        $this->base = $bd;
        $this->params = explode('/', $_GET['q']);
        if($_POST) {
            $this->data = $_POST;
        } else {
            $this->data = json_decode(file_get_contents("php://input"), true);
        }
        
    }

    private function ChekToken() {
        $token = getallheaders();
        $stmt = $this->base->prepare("SELECT * FROM `settings`");
        $stmt->execute();
        $res = $stmt->fetch();
        if($res['api_token'] == $token['Authorization']) {
            return true;
        }
            return false;
    }

    private function Register() {
        if($this->data['login'] && $this->data['pass']) {
           if(!$this->ChekUser($this->data['login'], $this->data['pass'])) {
                $login = $this->data['login'];
                $pass = $this->data['pass'];
                $first = $this->data['first_name'];
                $last = $this->data['last_name'];
                $patr = $this->data['patr'];
                $type = $this->data['type'];
                $stmt = $this->base->prepare("INSERT INTO `users`(`uuid`, `login`, `pass`, `status`, `first_name`, `last_name`, `patronymic`, `type`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $uuid = uniqid();
                
                $stmt->execute( array( $uuid, $login, $pass, 0 , $first, $last, $patr, $type ) );
                if($stmt) {
                    
                    http_response_code(201);
                    echo json_encode( array(
                        "uuid" => $uuid,
                        "code" => 201,
                        "token" => $this->GetToken()
                    )); 
                } else {
                    ErrorHandler::MakeError("bad request" , "not params" , 401);
                }
           } else {
               ErrorHandler::MakeError("not create" , "user already exists ", 401);
           }
        }
    }


    private function GetToken() {
        $stmt = $this->base->prepare("SELECT * FROM `settings`");
        $stmt->execute();
        $res = $stmt->fetch();
        return $res['api_token'];
    }

    private function ChekUser($login, $pass) {
        $stmt = $this->base->prepare("SELECT * FROM `users` WHERE `login` = ? AND `pass` = ?");
        $stmt->execute( array( $login, $pass ) );
        $res = $stmt->fetch();
        if($res) {
            return true;
        }
            return false;
    }
    
    private function Login() {

        if($this->data['login'] && $this->data['pass']) {
                $login = $this->data['login'];
                $pass = $this->data['pass'];
                $stmt = $this->base->prepare("SELECT * FROM `users` WHERE `login` = ? AND `pass` = ?");
                $stmt->execute( array( $login, $pass ) );
                $res = $stmt->fetch();
                if($res) {
                    $uuid = $this->UpdateUuid($res['id']);
                    $token = $this->GetToken();
                    http_response_code(201);
                    echo json_encode( array(
                        "uuid" => $uuid,
                        "token" => $token,
                        "code" => 201
                    ));
                    exit();
                    return;
                }
                    ErrorHandler::MakeError( "bad request" , "incorect params" , 401 );
        }
    }

    private function UpdateUuid($id) {
        $uuid = uniqid();
        $stmt = $this->base->prepare("UPDATE `users` SET `uuid`= ? WHERE `id` = ?");
        $stmt->execute( array( $uuid, $id ) );
        return $uuid;
    }

    private function AddRes() {
        // $card_id = $this->data['card_id'];
        // $uuid = $this->data['uuid'];
        // $res = $this->data['res'];
        
        // $data = $this->GetId($uuid);
        // $stmt = $this->base->prepare("INSERT INTO `report`( `user_id`, `card_id`, `result`, `date`) VALUES (?, ?, ?, CURRENT_DATE() )");
        // $stmt->execute( array( $data['id'], $card_id, $res ) );
        // if($stmt) {
        //     echo json_encode(
        //         array(
        //             "status" => "true",
        //             "code" => 200
        //         )
        //     );
        // } else {
        //     ErrorHandler::MakeError("nod data" , "bad params" , 404);
        // }
    }

    private function GetUserInfo() {
        $uuid = $this->data['uuid'];
        $res = $this->GetId($uuid);
        echo json_encode( $res, JSON_UNESCAPED_UNICODE );
    }

    private function GetId($uuid) {
        $stmt = $this->base->prepare("SELECT * FROM `users` WHERE `uuid` = ?");
        $stmt->execute( array( $uuid ) );
        $res = $stmt->fetch();
        return $res;
    }

    private function GetResult() {

        $resArr = array(
        );

        $date1 = $this->data['date1'];
        $date2 = $this->data['date2'];
       
            $stmt = $this->base->prepare("SELECT * FROM `report` LEFT OUTER JOIN `light`.users ON `users`.id = report.user_id WHERE `date` BETWEEN ? AND ?");
            $stmt->execute( array($date1, $date2) );
            while($row = $stmt->fetch()) {
                array_push( $resArr, array(
                        
                        "card_id" => $row['card_id'],
                        "result" => $row['result'],
                        "first_name" => $row['first_name'],
                        "last_name" => $row['last_name'],
                        "date" => $row['date']
                    
                ));
            }
            //echo json_encode( $resArr, JSON_UNESCAPED_UNICODE );
            GetExcel($resArr);
        
       
       
    }

    private function GetResultUser() {

        $resArr = array(
        );

        $id = $this->data['id'];
        $date1 = $this->data['date1'];
        $date2 = $this->data['date2'];
        if($date2) {
            $stmt = $this->base->prepare("SELECT * FROM `report` LEFT OUTER JOIN `light`.users ON `users`.id = report.user_id WHERE `date` BETWEEN ? AND ? AND `user_id` = ?");
            $stmt->execute( array($date1, $date2, $id) );
            while($row = $stmt->fetch()) {
                array_push( $resArr, array(
                        
                        "card_id" => $row['card_id'],
                        "result" => $row['result'],
                        "first_name" => $row['first_name'],
                        "last_name" => $row['last_name'],
                        "patronymic" => $row['patronymic'],
                        "date" => $row['date']
                    
                ));
            }
            //echo json_encode( $resArr, JSON_UNESCAPED_UNICODE );
       
            GetExcel($resArr);
        }
       
       
    }

    private function DelUser() {
        $uuid = $this->data['uuid'];
        $stmt = $this->base->prepare("DELETE FROM `users` WHERE `uuid` = ?");
        $stmt->execute( array( $uuid ) );
        if($stmt) {
            http_response_code(200);
            echo json_encode( array(
                "status" => "delite",
                "code" => 200
            ) ); 
        }
    }

    private function GetAllUser() {
        $userArr = [];
        $stmt = $this->base->prepare("SELECT * FROM `users`");
        $stmt->execute( );
        while($row = $stmt->fetch()) {
            array_push( $userArr, $row );
        }
        echo json_encode( $userArr, JSON_UNESCAPED_UNICODE);
    }

    private function GenPass() {
        $uuid = $this->data['uuid'];
        $gen = $this->gen_password(8);

        $stmt = $this->base->prepare("UPDATE `users` SET `pass`= ? WHERE `uuid` = ? ");
        $stmt->execute( array($gen, $uuid) );

        http_response_code(200);
        echo json_encode( array(
            'new_pass' => $gen,
            'status' => true
        ) );
    }

    private function gen_password($length = 6) {
        $password = '';
        $arr = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 
            'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 
            '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
        );
    
        for ($i = 0; $i < $length; $i++) {
            $password .= $arr[random_int(0, count($arr) - 1)];
        }
        return $password;
    }
    



    public function StartApp() {
        switch ($this->params[0]) {
            case 'register':
                $this->Register();
                break;
            
            case 'login':
                $this->Login();
                break;
            case 'addres':
                $this->AddRes();
                break;
            case 'getuser':
                $this->GetUserInfo();
                break;
            case 'getresult':
                $this->GetResult();
                break;
            case 'createresult':
            
            case 'deluser':
                $this->DelUser();
                break;
            case 'getalluser':
                $this->GetAllUser();
                break;
            case 'getresultuser':
                $this->GetResultUser();
                break;
            case 'genpass':
                $this->GenPass();
                break;
            default:
                ErrorHandler::MakeError("bad request" , "not params", 404);
                break;
        }
    }
    
}

$app = new API($conn);
$app->StartApp();





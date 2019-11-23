<?php

// For autoloading of vendor packages.
require 'vendor/autoload.php';

// FOr our own stuff
require 'common.php';


// For debugging
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

session_start();
Logger::init( "admin" );


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Authentication handler
if( ( $method == 'GET' ) && ( isset($_GET['logout'] ) ) ) {
   session_destroy();
   $method = 'LOGIN';

} else if ( ( $method == 'POST') && ( isset( $_POST['login'] ) ) ) {
   $userToken =  ( $_POST['user'] ?? '' );
   $passwordToken =  ( $_POST['password'] ?? '' );
   $loginToken = $userToken . ':' . $passwordToken;
   $validTokens = ( file("main.users", FILE_IGNORE_NEW_LINES) ?? [] );

   if( empty( $validTokens ) ) {
     $loginMessage =  "<h1>No users file!  Create one.</h1>";
   } else if ( empty( $userToken ) ) {
     $loginMessage =  "Username is required.";
   } else if( empty( $passwordToken ) ) {
      $loginMessage =  "Password is required.";
   } else if ( in_array( $loginToken, $validTokens ) ) {
        $_SESSION['user'] = $_POST['user'];
        $method='GET';
   } else {
        session_destroy();
        $loginMessage = "Not a valid user, or incorrect password.";
        $method='LOGIN';
   }
}

if( ! ( $_SESSION['user'] ?? false )  ) {
  $method = 'LOGIN';
}



switch( strtoupper( $method ) ) {

 case 'GET':
    readfile('main.get');
    break;

 case 'OPTIONS':
 case 'POST':
    $data = $method();
    header('Content-type: application/json');
    echo( json_encode( $data ?? [] , JSON_PRETTY_PRINT ) );
    break;

 default:
    $page = file_get_contents('login.get');
    $page = str_replace( [ '<!--message-->','<!--user-->' ], [  $loginMessage ?? '', $_POST['user'] ?? '' ], $page );
    echo $page;
    break;

}




FUNCTION OPTIONS() {

    $requestKeys = array_keys( $_GET ) ?? [];
    $item = $requestKeys[0] ?? '';

    $data=[];
    switch( $item ) {

       case 'log' :
           $data = ['data' => Logger::tail( 1024 ) ];
           break;

       case 'rubrics' :
           $data = ['data' => Data::rubrics() ];
           break;

       case 'classrooms' :
           $data = Data::classrooms( $_GET ?? [] );
           break;

       default:
           $data = Data::submissions( $_GET ?? [] );
           break;
 
           break;

    }
  return $data;
    
}



FUNCTION POST( $sampleData=false ) {

    // Take in some data
    $request = $_GET;
    $requestKeys = (  array_keys( $request ) ?? [] );

    // The first key tells us the action, the value is optional, but can be used to filter stuff
    $action = ( $requestKeys[0] ?? false );

    switch( $action ) {


         case "delete" : 
             // Universal delete handler.  OK.. so really, this is just
             // a file renamer.  Nothing is ever deleted.
             $entityType = $_POST['data']['entity'];  // pluralized.  This is just the data folder: rubrics, etc.

             $entityID = $_POST['data']['id'] ?? false;
             $entityFilter = ['id' => $entityID ];

             // Do we have an entity finder function for this one?
             $matched = Data::select( $entityType,  $entityFilter );
             if( count($matched) == 1 ) {
                   $entityRecord=$matched[0];
                   File::archive( $entityType . '/' . $entityRecord['file'] );
                   $entityName = 'record <b>' . ( $entityRecord['id'] ?? '') . '</b> (' . $entityRecord['file'] . ')';
                   $data = [ 'message' => 'Removed ' . $entityName . ' from ' . $entityType ];

             } else {
                   $data = [ 'message' => 'Could not remove record' ];
             }
             break;


         case "grade" :
            $codeText = $_POST['code'] ?? '';
            $rubricKey = $_POST['rubric'] ?? ''; 
            $data = Grader::score(  $codeText, $rubricKey );
            break;


         case "rubric" :
            $rubricContent = $_POST['data'] ?? '';
            $validate = Data::parseJSON( $rubricContent );
            if( ! ( $validate['valid'] ) ) {
                $data = $validate;
                break;
            }

            $rubric = json_decode( $rubricContent, true );
            if ( ! array_key_exists( 'id',  $rubric ) ) {
               $data = [ 'valid' => false, 'error' => 'Each rubric must have an `id` property.' ];
  
            } else {
               $fileName = 'rubrics/' . $rubric['id'] . '.json';
               File::write( $fileName, $rubricContent ); // Write the raw data to preserve formatting
               $data = [ 'valid' => true, 'message' => 'Saved Rubric : ' . $rubric['id'] .' to ' . $fileName ];
            }          
            break;
               
        
   }
    return $data;

  }




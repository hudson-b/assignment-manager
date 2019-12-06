<?php

// For our own stuff
require 'common.php';

// For debugging
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

session_start();
Logger::init( "admin" );


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';


// Authentication handler
if ( file_exists("main.users") ) {
    $validTokens = file( "main.users", FILE_IGNORE_NEW_LINES);

} else {
    $validTokens = [ '5c241aaec8f939e82157c859c080abb8' ]; // guest:demo
    $loginMessage = "Note: Default login is enabled.  You should create your own <i>main.users</i> file (see project documentation for details).";
}


if( ( $method == 'GET' ) && ( isset($_GET['logout'] ) ) ) {
   session_destroy();
   $method = 'LOGIN';

} else if ( ( $method == 'POST') && ( isset( $_POST['login'] ) ) ) {

   $userToken =  ( $_POST['user'] ?? '' );
   $passwordToken =  ( $_POST['password'] ?? '' );

   $loginToken = md5( $userToken . ':' . $passwordToken );

   if( empty( $validTokens ) ) {
     $loginMessage =  "Missing users file!  Create one.";

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

       case 'gradebook' :
           $data = Data::gradebook( ['classroom' => $_GET[$item ]  ] );
           break;

       default:
           $data = Data::submissions( $_GET ?? [] );
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



         case "graded" :

            $graded = $_POST['data'] ?? [];
            $graded['graded_date'] =  date('Y-m-d h:i:s');

            $submissionID = $graded['submission'];
            $record = Data::submissions( ['submission' => $submissionID ] );

            if( $record ) {
                $record = $record[0];

                $record['graded'] = $graded;
                $fileName = $record['file'];
                File::write( $fileName, $record );

                $data = ['message' => 'Saved grade for ' . $submissionID , 'record' => $record ];

            } else {
                $data = ['message' => 'Could not find submission id ' . $submissionID ];
            }
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
  
            } else if ( ! array_key_exists( 'title',  $rubric ) ) {
               $data = [ 'valid' => false, 'error' => 'Each rubric must have a `title` property.' ];

            } else {
               $fileName = 'rubrics/' . $rubric['id'] . '.json';
               File::write( $fileName, $rubricContent ); // Write the raw data to preserve formatting
               $data = [ 'valid' => true, 'message' => 'Saved Rubric : ' . $rubric['id'] .' to ' . $fileName ];
            }          
            break;
               
        
   }
    return $data;

  }




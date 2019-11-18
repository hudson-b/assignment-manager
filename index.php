<?php

// For autoloading of vendor packages.
require 'vendor/autoload.php';
require 'common.php';

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);


Logger::init();
session_start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';



switch( strtoupper( $method ) ) {
 case 'GET':
    readfile('index.html');
    break;

 case 'OPTIONS':
 case 'POST':
    $data = $method();
    header('Content-type: application/json');
    echo( json_encode( $data ?? [] , JSON_PRETTY_PRINT ) );
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
           $data = Data::classrooms();
           break;

       default:
           $data = Data::submissions( $_GET );
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
    $action = ( $requestKeys[0] ?? 'submission' );

    switch( $action ) {

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

            if( ! ($rubric['title'] ?? '' ) ) {
               $data = [ 'valid' => false, 'error' => 'Must have a title' ];
  
            } else if ( ! ($rubric['id'] ?? '' ) ) {
               $data = [ 'valid' => false, 'error' => 'Must have an ID' ];
  
             } else {
               $fileName = 'data/rubrics/' . $rubric['id'] . '.json';
               File::write( $fileName, $rubric );
               $data = [ 'valid' => true, 'message' => 'Saved Rubric' ];
            }          
            break;
               
        
   }
    return $data;

  }




<?php

// For autoloading of vendor packages.
require 'vendor/autoload.php';
require 'common.php';

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debugging
if ( php_sapi_name() == "cli") {
  $sampleData = file_get_contents("sample.json");
  $_GET=['submission'=>''];
  $method='POST';
}


// Logger::debug("Answering " . $method );
// Logger::debug( json_encode( $_GET  ) );


// The data directory must be writable
is_writable('data') or die( 'The data directory must be writable!' );


switch( strtoupper( $method ) ) {

 case 'GET':
      readfile('index.html');
      break;


 case 'OPTIONS' :
    $requestKeys = array_keys( $_GET ) ?? [];
    $item = $requestKeys[0] ?? '';

    $data=[];
    switch( $item ) {

       case '':
           break;

       case 'log' :
           $data = ['data' => Logger::tail( 1024 ) ];
           break;

       case 'classrooms' :
           $data = Data::classrooms();
           break;

       default:
           $data = Data::submissions( $_GET );
           break;

    }
    
    header('Content-type: application/json');
    echo( json_encode( $data , JSON_PRETTY_PRINT ) );
    break;



 case 'POST':
    // Take in some data
    $request = $_GET;
    $requestKeys = (  array_keys( $request ) ?? [] );

    // The first key tells us the action, the value is optional (ie. schema=something, or classroms )
    $action = ( $requestKeys[0] ?? 'submission' );

    switch( $action ) {

         case "grade" :
             break;


         // Main handler:  Receives posts from REPL
         case 'submission':

              // Immediately store whatever we received in the log directory in its original format.
              // We might need to refer to this later, so keep it *exactly* as it was provided.
              $now = date('Y-m-d-H-i-s');
              $rawFile = 'data/raw/' . $now . '.raw';
              $received = $sampleData ?? file_get_contents('php://input');
              File::write( $rawFile, $received );


              // Try to parse it
              $parsed = json_decode( $received, true );
              if( ! is_array( $parsed ) ) {
                 Logger::error("Could not parse received data!");
                 break;
              }


              // We absolutely must have each component of the data submitted by Repl.it
              $parsedKeys = array_keys( $parsed );
              $requiredKeys = [ 'classroom','student','assignment','submission' ];
              foreach( $requiredKeys as $key ) {
                    $record = $parsed[ $key ] ?? [];
                    if( empty($record) )  {
                         Logger::error("Received data does not have the required structure! Missing " . $key );
                         return;
                    }
              }


              $studentName =  $parsed['student']['last_name'] . ', ' . $parsed['student']['first_name'];
              $assignmentName = $parsed['assignment']['name'];
              $fileCount = count( $parsed['submission']['files'] ?? [] );

              $summary = 'Submission from ' . $studentName . ' : ' . $assignmentName . ' : ' . $fileCount . ' file'.( $fileCount==1 ? '' : 's' );
              Logger::info( $summary );             

              // Keeps only one file per student + assignment
              $fileName = 'data/' . Data::fileID( $parsed ) . '.json';
              File::archive( $fileName  );
              File::write( $fileName,  $parsed);


              break;


   }


}



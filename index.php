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


// Debugging
if ( php_sapi_name() == "cli") {

  // Walk the replay directory
  switch ($argv[1] ?? '' ) {
    case 'replay' : 
      $rawFiles = scandir('data/replay', SCANDIR_SORT_ASCENDING);
      foreach( $rawFiles as $rawFile ) {
            $_POST['_input_'] = file_get_contents('data/replay/' . $rawFile);
            $_GET=['submission'=>''];
            POST();
      }
      break;
  }
  exit;
}
 




// The data directory must be writable
is_writable('data') or die( '<h1>The <i>data</i> directory must be writable!</h1>' );

// Set a global, just to be polite
$_POST['_input_'] = file_get_contents('php://input') ?? '';

switch( strtoupper( $method ) ) {
 case 'GET':
 case 'OPTIONS':
 case 'POST':
    $method();
    break;
}



FUNCTION GET() {
      readfile('index.html');
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
    
    header('Content-type: application/json');
    echo( json_encode( $data , JSON_PRETTY_PRINT ) );
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
               

         // Main handler:  Receives posts from REPL
         case 'submission':

              // *Immediately* log whatever we received in the original format.
              // We might need to refer to this later, so keep it *exactly* as it was provided.
              // I'm serious about this.  Don't futz with it.  Really.
              $received = $_POST['_input_'] ?? '';

              if( empty($received) ) {
                  Logger::error("Received no data in POST!");
                  return;
              }

              $time = microtime(true);
              $micro = sprintf("%06d",($time - floor($time)) * 1000000);
              $now = new DateTime( date('Y-m-d H:i:s.'.$micro, $time) );

              $rawFile = 'data/raw/' . $now->format("Y-m-d-h-i-s-u")  . '.raw';
              File::write( $rawFile, $received );
              Logger::debug("Received : " . $received );

              // Try to parse it.  We expect certain things, and must complain if not found.
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

              // At this point, we hava a properly formatted associatve array
              $parsed['raw'] = $rawFile;

              // Any previous submissions from this student + assignment?
              $previousSubmissions = Data::submissions( ['student' => $parsed['student']['id'], 'assignment' => $parsed['assignment']['id'] ] );
              $submissionNumber = count( $previousSubmissions ) + 1;
              $parsed['submission']['number'] = $submissionNumber;

              
              // Replay logic
              //$receivedDate = $parsed['submission']['time_submitted'];
              //$receivedDate = new DateTime(  $receivedDate );
              $parsed['submission']['time_received'] = $now->format('Y-m-d H:i:s');

              // Store it in the received directory.
              $parsedFile = 'data/received/' . $now->format("Y-m-d-h-i-s-u") . '.json';
              File::write( $parsedFile,  $parsed );

              // touch( $parsedFile, $receivedTimestamp );

              // Create the log entry
              $studentName =  $parsed['student']['last_name'] . ', ' . $parsed['student']['first_name'];
              $assignmentName = $parsed['assignment']['name'];
              $fileCount = count( $parsed['submission']['files'] ?? [] );
       

              $summary = $studentName . ' : ' . $assignmentName . ' : Submission #' . $submissionNumber . ' : ' . $fileCount . ' file'.( $fileCount==1 ? '' : 's' );
              Logger::info( $summary );             


              $data=['response' => 'Received', 'file' => $parsedFile ];
              break;


   }

    header('Content-type: application/json');
    echo( json_encode( $data ?? [] , JSON_PRETTY_PRINT ) );

  }




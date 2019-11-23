<?php
// For autoloading of vendor packages.
require 'vendor/autoload.php';
require 'common.php';

Logger::init( "webhook" );

// Debugging
if ( php_sapi_name() == "cli") {

  // Walk the replay directory
  switch ($argv[1] ?? '' ) {

    case 'replay' : 
      $rawFiles = glob( 'data/replay/*.json' );
      foreach( $rawFiles as $rawFile ) {
            $contents = file_get_contents( $rawFile );
            POST(  $contents );
      }
      break;
  }

  exit;
}


// Call the POST handler
// Set a global, just to be polite
$result = POST( file_get_contents('php://input') ?? '' );

// Send back the response
header('Content-type: application/json');
echo( json_encode( $result ?? [] , JSON_PRETTY_PRINT ) );




FUNCTION POST( $received = '' ) {

              // *Immediately* log whatever we received in the original format.
              // We might need to refer to this later, so keep it *exactly* as it was provided.
              // I'm serious about this.  Don't futz with it.  Really.
              if( empty($received) ) {
                  Logger::error("Received no data in POST!");
                  return;
              }

              $time = microtime(true);
              $micro = sprintf("%06d",($time - floor($time)) * 1000000);
              $now = new DateTime( date('Y-m-d H:i:s.'.$micro, $time) );

              $rawFile = 'raw/' . $now->format("Y-m-d-h-i-s-u")  . '.raw';
              File::write( $rawFile, $received );
              Logger::debug("Received : " . $received );


              // Try to parse it.  We expect certain things, and must complain if not found.
              $parsed = json_decode( $received, true );
              if( ! is_array( $parsed ) ) {
                 Logger::error("Could not parse received data!");
                 return false;
              }


              // We absolutely must have each component of the data submitted by Repl.it
              $parsedKeys = array_keys( $parsed );
              $requiredKeys = [ 'classroom','student','assignment','submission' ];
              foreach( $requiredKeys as $key ) {
                    $record = $parsed[ $key ] ?? [];
                    if( empty($record) )  {
                         Logger::error("Received data does not have the required structure! Missing " . $key );
                         return false;
                    }
              }


              // At this point, we hava a properly formatted associatve array
              $parsed['raw'] = $rawFile;

              // Any previous submissions from this student + assignment?
              $previousSubmissions = Data::submissions( ['student' => $parsed['student']['id'], 'assignment' => $parsed['assignment']['id'] ] );
              $submissionNumber = count( $previousSubmissions ) + 1;
              $parsed['submission']['number'] = $submissionNumber;

              
              // Replay logic
              $receivedDate = $parsed['submission']['time_submitted'];
              $receivedDate = new DateTime(  $receivedDate );
              $parsed['submission']['time_received'] = $receivedDate->format('Y-m-d H:i:s');
              // $parsed['submission']['time_received'] = $now->format('Y-m-d H:i:s');

              // Store it in the received directory.
              $parsedFile = 'received/' . $now->format("Y-m-d-h-i-s-u") . '.json';
              File::write( $parsedFile,  $parsed );

              // touch( $parsedFile, $receivedTimestamp );

              // Create the log entry
              $studentName =  $parsed['student']['last_name'] . ', ' . $parsed['student']['first_name'];
              $assignmentName = $parsed['assignment']['name'];
              $fileCount = count( $parsed['submission']['files'] ?? [] );
       

              $summary = $studentName . ' : ' . $assignmentName . ' : Submission #' . $submissionNumber . ' : ' . $fileCount . ' file'.( $fileCount==1 ? '' : 's' );
              Logger::info( $summary );             


              $data=['response' => 'Received', 'file' => $parsedFile ];


    // Politely reply
    return $data;

  }





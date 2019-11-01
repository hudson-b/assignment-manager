<?php

// For autoloading of vendor packages.
require 'vendor/autoload.php';
require 'common.php';

ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debugging
//if ( php_sapi_name() == "cli") {
//  $_GET=['classrooms'=>''];
//  $method='OPTIONS';
//}


// Logger::debug("Answering " . $method );
// Logger::debug( json_encode( $_GET  ) );




switch( strtoupper( $method ) ) {

 case 'GET':
      readfile('index.html');
      break;


 case 'OPTIONS' :

    $requestKeys = array_keys( $_GET ) ?? [];
    $item = $requestKeys[0] ?? '';

    $data=[];
    switch( $item ) {

       case 'log' :
           $data = Logger::tail( 512 );
           break;

       default:
           $data = Data::received();
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

         case "gradebook" :
             break;

         // Main handler:  Receives posts from REPL
         case 'submission':
              // Immediately store whatever we received in the log directory in its original format
              $now = date('Y-m-d-H-i-s');
              $received = isset( $sample ) ? $sample : file_get_contents('php://input');
              File::write('data/received/'. $now . '.received' , $received );

              // Try to parse it
              $parsed = json_decode( $received, true );
              if( ! is_array( $parsed ) ) {
                 Logger::error("Could not parse received data!");
                 break;
              }

              // We absolutely must have each component of the data submitted by Repl
              $parsedKeys = array_keys( $parsed );
              $requiredKeys = [ 'classroom','student','assignment','submission' ];
              $diffKeys = array_diff($requiredKeys, $parsedKeys );
              if( $diffKeys  ) {
                 Logger::error("Received data does not have the required structure! Problem keys: " . implode( ",", $diffKeys ) );
                 break;
              }

              // Now, let's prepare the record a bit.
              $parsed['submission']['student_id'] = $parsed['student']['id'];
              $parsed['submission']['assignment_id'] = $parsed['assignment']['id'];

              $parsed['files'] = $parsed['submission']['files'];
              unset( $parsed['submission']['files'] );

              foreach( $parsed['files'] as $index=>$fileRecord ) {
                $parsed['files'][$index]['student_id'] = $parsed['student']['id'];
                $parsed['files'][$index]['assignment_id'] = $parsed['assignment']['id'];
              }

              // Build the porfolio structure and store the data 
              $baseFolder = './data/portfolio';

              $classroom = $parsed['classroom'] ?? [];
              Data::write( 'classroom', $classroom );
              $classroomID = $parsed['classroom']['id'];
              $classroomName = trim( $classroom['name'] ?? 'Unknown Classroom');
              $classroomName = str_replace( "/", "-", $classroomName);
              $classroomFolder = $baseFolder . '/' . $classroomName;


              $student = $parsed['student'] ?? [];
              Data::write( 'student', $student );
              $studentID = $parsed['student']['id'];
              $studentName = trim( $student['last_name'] ) . ', ' . trim($student['first_name']);
              $studentFolder = $classroomFolder.'/'.$studentName;


              $assignment = $parsed['assignment'] ?? [];
              $assignment['classroom_id'] = $classroomID;
              Data::write( 'assignment', $assignment );
              $assignmentID = $parsed['assignment']['id'];
              $assignmentName = trim( $assignment['name'] );
              $assignmentFolder = $studentFolder.'/'.$assignmentName;



              // Save the submitted files
              $submission = $parsed['submission'] ?? [];
              $submissionID = $parsed['submission']['id'];
              Data::write( 'submission', $submission );

              Logger::info( "Submission from " . $studentName . ' : ' . $assignmentName );             

              $files = $parsed['files'];
              foreach( $files as $fileItem ) {

                     $fileID = $studentID . '_' . $assignmentID;
                     $fileName=$fileItem['name'];
                     $fileContent=$fileItem['content'];

                     File::write( $assignmentFolder . '/' . $fileName,  $fileContent );

                     $filePath = './data/files/' . $fileID . '.content';
                     File::archive( $filePath );
                     File::write( $filePath,  $fileContent );

              }
              break;

   }

}


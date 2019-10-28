<?php

// For autoloading of vendor packages.
require 'vendor/autoload.php';
require 'common.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Debugging
if ( php_sapi_name() == "cli") {
 //$sample=file_get_contents('sample.json');
 //$method='POST';

 $_GET=['classrooms'=>''];
 $method='OPTIONS';
}


Logger::debug("Answering " . $method );
Logger::debug( json_encode( $_GET  ) );

switch( strtoupper( $method ) ) {

 case 'GET':
      readfile('index.html');
      break;

 case 'OPTIONS':
      // Get the first key (not the value) of the request
      $request = $_GET ?? [];
      $requestKeys = (  array_keys( $request ) ?? [] );
      $entity = ( empty($requestKeys) ) ? '' : $requestKeys[0];

      Logger::debug( 'Entity : ' . $entity );
      switch( $entity ) {

           case 'classroom':
           case 'assignment':
           case 'student':
             $source = Data::read($entity);
             $data=[];
             foreach($source as $key=>$record) {
                    $data[] = $record;
             }
             break;

           case 'log':
            $data = Logger::tail();
            break;

           default:
            $data=[];
            break;
      }
  
    header('Content-type: application/json');
    echo(  json_encode(  ['data'=>$data ], JSON_PRETTY_PRINT ) );
     break;


 case 'POST':

      Logger::info( "POST start" );

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

      Logger::info( "POST end" );
      break;


}



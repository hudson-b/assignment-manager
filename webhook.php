<?php
class repl_webhook extends CDS\Module {

 const ROOT_PATH = '/home/io/Storage/data/repl';

 public function __construct() {
   $this->Permission = [
      'allow' => true
   ];
 }


 public static function Log( $entry ) {
      $now = date('Y-m-d-h-i-s');
      $clientIP = ($_SERVER['REMOTE_ADDR'] ?? '' );

      $entry = '[' . $now . '] ' . $clientIP . ' : ' . $entry . "\n";
      echo '<div>' . $entry . '</div>';

      $logFile = self::ROOT_PATH . '/log.txt';
      file_put_contents(  $logFile, $entry, FILE_APPEND );


 }

 public static function VerifyFolder( $path ) {

   if( ! file_exists( $path ) ) { 
         mkdir( $path );
         self::Log("Created folder: " . $path );
   }
  return $path;

 }



 public function POST( $package = false ) {

      self::Log( "POST received." );

      // Immediately store what we got in the log directory
      $now = date('Y-m-d-h-i-s');
      $received = ( $package ) ? $package : file_get_contents('php://input');

      file_put_contents( self::ROOT_PATH .  '/log/'. $now . '.json' , $received );

      // Try and parse it
      self::Parse( $received );


 } 


 public static function Parse( $received ) {

      $root = self::ROOT_PATH;


      // Parse the JSON we received into an array
      $parsed = json_decode( $received, true );
      if( ! is_array( $parsed ) ) {
          self::Log("Could not parse received data!");
          return false;
      }

      // We absolutely must have each component of the data submitted by Repl
      $required = ['event_name','classroom','student','assignment','submission' ];

      // Format it pretty-like (for later )
      $received = json_encode( $parsed, JSON_PRETTY_PRINT );

       
      $event_name = $parsed['event_name'] ?? '';
      $classroom = $parsed['classroom'] ?? [];
      $student = $parsed['student'] ?? [];
      $assignment = $parsed['assignment'] ?? [];
      $submission = $parsed['submission'] ?? [];
         

      // Move into or make the classroom folder
      $classroomName = trim( $classroom['name'] ?? 'Unknown Classroom');
      $classroomName = str_replace( "/", "-", $classroomName);


      $classroomFolder = $root . '/' . $classroomName;
      self::VerifyFolder( $classroomFolder );
      $classroomInfo = json_encode( $parsed['classroom'] ?? [], JSON_PRETTY_PRINT );
      file_put_contents( $classroomFolder . '/classroom.json' ,  $classroomInfo );


      // Move into or make the student folder
      $studentName = trim( $student['last_name'] ) . ', ' . trim($student['first_name']);
      $studentFolder = $classroomFolder.'/'.$studentName;
      self::VerifyFolder( $studentFolder );

      $studentInfo = json_encode( $student, JSON_PRETTY_PRINT );
      file_put_contents( $studentFolder . '/student.json' ,  $studentInfo );


      // Move into or make the assignment folder
      $assignmentName = trim( $assignment['name'] );
      $assignmentFolder = $studentFolder . '/' . $assignmentName; 
      self::VerifyFolder( $assignmentFolder );

      // Write down all the files that were submitted
      $assignmentFiles = $submission['files'];
      foreach( $assignmentFiles as $item ) {
           
             $fileName=$item['name'];
             $fileContent=$item['content'];
             file_put_contents( $assignmentFolder . '/' . $fileName, $fileContent );
             self::Log( $studentName . ' : ' . $assignmentName . ' : ' . $fileName );             

      }


      // \CDS\Framework::Notify( 'hudson.b@lynchburg.edu', 'REPL - ' . $assignmentName . ' - ' . studentName, '' );
      self::Log( "Parse Complete");

 }



 public function GET() {
   // Let's fake the thing.

   $sample = '{"assignment":{"id":3530417,"name":"Control : 8) Shape area","type":"input_output"},"classroom":{"id":140061,"name":"2019-20 / C S 131 / Fundamentals of Programming","webhook_secret":"37adbc6c-c798-45ac-808b-8dbb6cb3791b"},"submission":{"id":8723431,"status":"submitted_incomplete","time_submitted":"2019-10-15T12:21:25.802Z","time_created":"2019-10-15T12:21:20.223Z","teacher_url":"https://repl.it/teacher/submissions/8723431","student_url":"https://repl.it/student/submissions/8723431","files":[{"name":"main.py","content":"\n# Prompt for the number of sides\n\n# For circles, gather the radius, calculate stuff and display the result\n\n# For squares, gather the side length, calculate stuff, and display the result\n\n# Otherwise, show the error message\n"}]},"student":{"id":2031895,"first_name":"Test","last_name":"Student","email":"hudson.b.student@lynchburg.edu"},"event_name":"student_submits_assignment"}';
   self::POST( $sample );
   return;



   // Walk all directories and build the gradebook for each classroom
   $root = '/home/io/Storage/data/repl/*';

   $classrooms = array_filter( glob( $root ), 'is_dir');
   foreach( $classrooms  as $classroomPath ) {

        $classroomName = basename( $classroomPath );
        echo '<h2>' . $classroomName . '</h2>';

        // Get or create the gradebook
        $gradebookPath = $classroomPath . '/gradebook.json';
        if( file_exists( $gradebookPath ) ) {
            $gradebookJSON = file_get_contents( $gradebookPath );
            $gradebook = json_decode( $gradebookJSON, true );
        } else {
            $gradebook = [];
        }

        // Walk all students
        $students = array_filter( glob( $classroomPath . '/*' ), 'is_dir');
        foreach( $students  as $studentPath ) {

           // Read each json file
           $submissions = array_filter( glob( $studentPath  . '/*.json') , 'is_file');

           foreach( $submissions as $submissionPath ) {
                $submissionJSON = file_get_contents( $submissionPath );
                $submission = json_decode( $submissionJSON, true );

                $assignment = $submission['assignment'] ?? false;
                if ( ! $assignment ) continue;
                $assignmentName = $assignment['name'];
                $student = $submission['student'] ?? false;
               
                var_dump( $student );

           }

        }


       
   }


 }





}


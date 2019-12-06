<?php


// Sanity checker
$autoLoader = 'vendor/autoload.php';
file_exists( $autoLoader ) or die('Missing the required dependencies (run composer install)');
require $autoLoader;


// Global.  We can *only* work with things in our data directory
define("DATAPATH", "data" );
file_exists( DATAPATH ) or die('Missing the data directory!');


// Global error handler.
set_error_handler(
    function ($severity, $message, $file, $line) {
        Logger::error( basename($file)  . ':' . $line . '  ' . $message);
    }
);

// Get moving
File::init();


// ------------------------------------------
// Singleton for logging
// ------------------------------------------
class Logger {

    const FILENAME = DATAPATH.'/manager.log';

    protected static $instance = null;

    public static function init( $channel ) {
      return self::instance( $channel );
    }


    public static function tail( $lineCount=128, $level=100 ) {

       $lastLines = [];
       foreach( \Bcremer\LineReader\LineReader::readLinesBackwards( self::FILENAME ) as $line ) {

             $line = json_decode( $line, true );
             if( $line['level'] < $level ) continue;

             $line['datetime'] = $line['datetime']['date'];

             $lastLines[] = $line;
             if( count( $lastLines ) >= $lineCount ) break;  
        }
        return $lastLines;

   }


    public static function instance( $channel = null) {

        if ( self::$instance === null ) {

           self::$instance = new \Monolog\Logger( $channel ?? 'manager' );

           $streamHandler =  new \Monolog\Handler\StreamHandler( self::FILENAME, \Monolog\Logger::DEBUG);
           $streamHandler->setFormatter( new \Monolog\Formatter\JsonFormatter() );

           self::$instance->pushHandler( $streamHandler );
           self::$instance->pushProcessor( function ($entry) {
                  $entry['remote_addr'] = ( $_SERVER['REMOTE_ADDR'] ?? '' );
                  return $entry;
           });

           if (php_sapi_name() == "cli") {
             $_SERVER['REMOTE_ADDR'] = 'local';
             $streamHandler =  new \Monolog\Handler\StreamHandler( 'php://stdout', \Monolog\Logger::DEBUG);
             $format = "%datetime%\t%level_name%\t%message%\t%context%\t%extra%\n";
             $streamHandler->setFormatter( new \Monolog\Formatter\LineFormatter( $format ) );
             self::$instance->pushHandler( $streamHandler );
           }

           if( ! file_exists( self::FILENAME ) ) self::$instance->info('Created log file');

        }

        return self::$instance;
    }


    public static function __callStatic($method, $args)    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }


}




// ------------------------------------------
// Singleton for file operations
// ------------------------------------------
class File {

         public static function init() {
            $filePath = self::__sanitize( 'manager.log' );
         }

         private static function __sanitize( $filePath ) {

            // Convert to pure ASCII, and preprend the data path
            $cleanPath =  DATAPATH . '/' . \Stringy\StaticStringy::toAscii( $filePath );

            // is_writeable( $realPath ) or die('The path <b>' . $realPath . '</b> is not writeable! ');
            return $cleanPath;
         }

         public static function mkdir( $path ) {
            $path = self::__sanitize( $path );

            if( file_exists( $path ) ) return;
            Logger::debug( "Creating " . $path );
            mkdir( $path , 0775, true );  // Owner full, Group full, Public read+execute
         }


         // Make a backup of an existing file, then return the count of archived files
         public static function archive( $filePath ) {
            $filePath = self::__sanitize( $filePath );

            if( ! file_exists( $filePath ) ) return 'Could not find ' . $filePath;

            $backupPath = $filePath . '.' . time;
            rename( $filePath, $backupPath );

            // $existing = glob( $filePath . '.*'  );
            // return count( $existing );
            return 'Archived ' . $filePath;
         }


         public static function all( $path ) {

         // Find all JSON files in a given folder
          $path = self::__sanitize( $path );
          
          $files = glob( $path . "/*.json" );
         // Remove the DATAPATH portion of the name
          $records=[];
          foreach( $files as $filePath ) {
             $records[] = substr( $filePath, strlen( DATAPATH ) + 1 ); 
           }
          // var_dump( $records );
          return $records;
        }


	
          public static function info( $filePath ) {

             $filePath = self::__sanitize( $filePath );

             $info = new SplFileInfo( $filePath );

             return [
              "sanitized" => self::__sanitize( $filePath ),
              "created" => date('Y-m-d h:i:s', $info->getCTime() ),
              "modified" => date('Y-m-d h:i:s', $info->getMTime() )
            ];

        }


         public static function write( $filePath, $fileContents ) {
               $filePath = self::__sanitize( $filePath );

               self::mkdir( dirname( $filePath ) );

               // If writing an array, format it as JSON before storing.  We're polite that way.
               if( is_array( $fileContents ) ) $fileContents = json_encode( $fileContents, JSON_PRETTY_PRINT );

               if ( file_put_contents( $filePath, $fileContents ) ) {
                    Logger::debug( "Wrote " . $filePath );
               } else {
                    Logger::error( "Unable to save " . $filePath );
               }
             
         }

         public static function read( $filePath, $parseJSON=false ) {
               $filePath = self::__sanitize( $filePath );

               if( ! file_exists( $filePath ) ) {
                  Logger::error( "Unable to find " . $filePath ); 
                  return false;
               }

               $fileContents = file_get_contents( $filePath ) ?? ''; 
               // Parse it?
               if( $parseJSON ) $fileContents = json_decode( $fileContents, true );
               return $fileContents;

         }

}


// ------------------------------------------
// Singleton for data objects (JSON files)
// ------------------------------------------

class Data {


  public static function parseJSON( $content ) {

     $parser = new \Seld\JsonLint\JsonParser();
     try {
       $parser->parse( $content );
       $response = ['valid'=>true, 'content' => json_decode( $content, true ) ];
 
     } catch ( \Exception  $e ) {
       $response = ['valid'=>false, 'error' => $e->getMessage(), 'content'=> $content ];
     }

     return $response;


  }



  public function select( $entityType, $filter=[] ) {
     return forward_static_call( 'self::' . $entityType, $filter  );
  }

  private static function matches( $record, $filter ) {

           if( empty($filter) )  return true;

           foreach( $filter as $filterKey=>$filterValue ) {

                  // Get the record's value for this key
                  $recordValue = $record[ $filterKey ] ?? false;

                  // Subrecord, filter has a subfilter to match
                  if( is_array( $recordValue ) && is_array( $filterValue ) ) {
                      if ( ! self::matches( $recordValue, $filterValue ) ) return false;

                  // Subrecord, filter has an ID number to match
                  } else if ( is_array( $recordValue ) ) {
                      $recordValue = $recordValue['id'];
                  }
                
                  // Make the comparison
                  $matched =  ( $recordValue == $filterValue);
                  //echo $recordValue . '==' . $filterValue . ' : ' . $matched . "\n";
                  if( ! $matched ) return false; // not a match.

          }
          return true;

  }



  public static function rubrics( $filter=[] ) {

        $data=[];
        foreach( File::all('rubrics') as $file ) {

           $content = File::read( $file );
           $parsed = Data::parseJSON( $content );

           if( $parsed['valid'] ) {
              $parsed = $parsed['content'];

           } else {
              $parsed=['title' => $parsed['error']];
           }

           if( self::matches( $parsed, $filter ) == false ) continue;

           // Add in some file-related data
           $parsed['file'] =  basename( $file );
           $parsed['file_info'] = File::info( $file );

           // Explode any includes
           foreach( ($parsed['sections'] ?? [] ) as $sectionKey => $sectionConfig ) {
             $includeList = $sectionConfig['include'] ?? false;
             if( $includeList ) {
                if( ! is_array($includeList ) ) $includeList = [$includeList];
                foreach( $includeList as $includeID ) {
                  $includeConfig = self::rubrics( ['id' => $includeID ] );
                  $parsed['sections'][$sectionKey]['included'] = $includeConfig;
                }
             }
           }

           // Unformatted content
           $parsed['content'] = $content;

           // Must be OK. 
           $data[] = $parsed;

        }


        return array_values( $data );
  }




  public static function classrooms() {

        $data=[];
        foreach( File::all('received')  as $file ) {
           $parsed = File::read( $file, true );
           if( ! $parsed )  continue;
           $classroom = $parsed['classroom'];
           $data[ $classroom['id'] ] = $classroom;
        }
        return array_values( $data );
  }


  public static function submissions( $filter=[] ) {

        $data = [];
        foreach( File::all('received')  as $fileReceived ) {

           $content = File::read( $fileReceived );
          
           $parsed = json_decode( $content,  true );
           if( ! $parsed )  continue;


          // Check the filter.  Could be anything.
           if( ! self::matches( $parsed, $filter ) ) continue;

           // Throw in the filename.  Do this at read time, so we are
           // free to change filenames without borking up the whole system
           $parsed['file'] = $fileReceived;
           $parsed['file_info'] = File::info( $fileReceived );


          // Toss in the bucket
          $data[] = $parsed;

      }

      return array_values( $data );

  }



  public static function gradebook( $filter=[] ) {

    $submissions = self::submissions( $filter );
 
    // Pluck out the grader
    $gradebook = [];
    foreach( $submissions as $submission ) {

         $graded = $submission['graded'] ?? false;
         if( ! $graded ) continue;

         $classroom = $submission['classroom'];
         $classroomName = $classroom['name'];

         $student = $submission['student'];
         $studentID = $student['id'];
         $studentEmail = $student['email'];
         $studentName = $student['name'];
 
         $studentGrades = ( $gradebook[ $studentID ] ??
                           [ 'classroom' => $classroomName, 'id' => $studentID, 'email' => $studentEmail, 'name' => $student['last_name'] . ', ' . $student['first_name'] ] );
 
         $assignment = $submission['assignment'];
         $assignmentID = $assignment['id'];
         $assignmentName = $assignment['name'];
         $studentGrades[ $assignmentName ] = $graded['score'];

         $gradebook[ $studentID ] = $studentGrades;

    }
    return array_values( $gradebook );


  }



}





class Grader {

  private static function grep( $codeLines, $examineObject ) {

       // Make sure we have a valid examine object here.
       if( ! is_array( $examineObject ) )  $examineObject = [ 'filters' => [ $examineObject ] ];

       // Walk all the filters for this object and append
       $found=[];
       foreach( $examineObject['filters'] ?? [] as $filterExpression ) {

           //$matchedLines =  preg_grep( $filterExpression, $codeLines );
           foreach( $codeLines as $lineNumber=>$lineObject ) {

              $lineClass = ( $lineObject['class'] ?? '' );
              $lineContent = ( $lineObject['content'] ?? $lineObject );

              if( ! empty( $lineClass ) ) continue;

              preg_match( $filterExpression, $lineContent, $matched );
              foreach( $matched as $item ) {
                  $found[ $lineNumber ] = $item;
              }
           }
       }
       if( ( $examineObject['unique'] ?? false ) === true ) $found = array_unique( $found );

       return $found;

  }

  private static function compareTo( $itemA, $itemB ) {

     
      
  }

  public static function score( $codeText, $rubricKey ) {

     $rubricFile = DATAPATH . '/rubrics/' . $rubricKey . '.json';

     $rubric = File::read( $rubricFile );
     if( empty( $rubric ) ) return ['error' => json_last_error_msg(), 'file'  => $rubricFile ];
     
     $parsed = Data::parseJSON( $rubric );
     if( ! $parsed['valid'] )  { return $parsed['error']; }

     $rubric = $parsed['rubric'];

     $classifier = $rubric['classifier'] ?? [];


     // Get an array of all code lines, and classify each one
     $codeLines = [];
     foreach( explode( "\n", $codeText )  as $lineNumber=>$lineContent) {

         $class='';
         foreach( $classifier as $classification=>$filterExpression) {
              if( preg_match( $filterExpression, $lineContent ) ) {
                $class = $classification;
                break;
              }
          }

        $codeLines[ $lineNumber ] = [ 'content' => $lineContent, 'class' => $class ?? '' ];
     }
  

     // Examine this code
     $examine=[ '_code_' => $codeLines];


     // Build the custom examine dataset for this rubric
     foreach( $rubric['examine'] ?? [] as $examineKey => $examineObject ) {
       $examine[ $examineKey ] = Grader::grep( $codeLines, $examineObject );
     }

     return ( $examine );

     // Walk every item of the rubric 
     $scored = [];
     foreach( $rubric['scoring'] ?? [] as $rubricCategory => $rubricItems) {
 
         $scored[ $rubricCategory ] = [];

         // Walk every item of the category
         foreach( $rubricItems as $rubricItem ) {

               // What data to consider?
               $consider = $rubricItem['consider'] ?? '';
               
               // Allow for lookup in the examine set, or on-the-fly regex
               if( array_key_exists( $considerSource, $examine ) ) {
                     $consider = $examined[ $considerSource ];
               } else {
                     $consider = preg_grep( $considerSource, $codeLines );
               }

               // What's the condition for passing this test?
               $condition = $rubricItem['condition'] ?? '{$count} > 0';


              // If there is an 'each' condition, test it out
               $each = $rubricItem['each'] ?? false;
               if(  $each  ) {
                foreach( $consider as $considerItem ) {
                }
               }

               // Standard template replacement stuff
               $considerState = [
                    "{consider}" => $consider,
                    "{count}" => count( $consider ),
                    "{condition}" => $condition
               ];

               $conditionExpanded = str_replace( array_keys( $considerState ), $considerState, $condition );
            
               $scored [ $rubricCategory ][] = $considerState;
                  
         }


     }
     return $scored;

  }





  public static function isMixedCase( $value ) {
     return preg_match( '/^(?=.*?[A-Z])(?=.*?[a-z])/', $value ) == 1;
  }
  public static function hasDigits( $value ) {
     return preg_match( '[0-9]', $value ) == 1;
  }
  public static function hasAlpha( $value ) {
     return preg_match( '/^[A-Za-z]/', $value ) == 1;
  }

}







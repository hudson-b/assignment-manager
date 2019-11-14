<?php

set_error_handler(
    function ($severity, $message, $file, $line) {
        Logger::error( basename($file)  . ':' . $line . '  ' . $message);
    }
);



// ------------------------------------------
// Singleton for logging
// ------------------------------------------
class Logger {

    const FILENAME = "data/manager.log";

    protected static $instance = null;

    public static function init() {
      return self::instance();
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

    public static function instance() {

        if ( self::$instance === null ) {

           self::$instance = new \Monolog\Logger('manager');

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
         }

         private static function __sanitize( $filePath ) {
            return \Stringy\StaticStringy::toAscii( $filePath );
         }

         public static function mkdir( $path ) {
            if( file_exists( $path ) ) return;
            Logger::debug( "Creating " . $path );
            mkdir( $path , 0775, true );  // Owner full, Group full, Public read+execute
         }


         // Make a backup of an existing file, then return the count of archived files
         public static function archive( $filePath ) {
            if( ! file_exists( $filePath ) ) return 0;

            $backupPath = $filePath . '.' . filectime( $filePath );
            rename( $filePath, $backupPath );

            $existing = glob( $filePath . '.*'  );
            return count( $existing );
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





class Data {


  const PATH = "data";


  // Create a unique identifier for any submissino
  public static function uniqueID( array $submissionRecord ) {

           $uniqueID = [];
           $components = ['classroom','assignment','student'];
           foreach( $components as $component ) {
                $uniqueID[] = $submissionRecord[ $component ]['id'] ?? '0';
           } 
           return implode( '_', $uniqueID );
  }


  private static function all( $path='parsed' ) {
        $records = glob( self::PATH . "/" . $path . "/*.json" );
        return $records;
  }


  public static function rubrics() {
        $data=[];
        foreach( self::all('rubrics') as $file ) {
           $parsed = File::read( $file, true );
           if( ! $parsed )  continue;
           $parsed['file'] = basename( $file );
           $parsed['modified'] = date("Y-m-d H:i:s", filemtime( $file ) );
           $data[] = $parsed;
        }
        return array_values( $data );
  }



  public static function classrooms() {

        $data=[];
        foreach( self::all('parsed')  as $file ) {
           $content = file_get_contents($file);
           $parsed = json_decode( $content,  true );
           if( ! $parsed )  continue;
           $classroom = $parsed['classroom'];
           $data[ $classroom['id'] ] = $classroom;
        }
        return array_values( $data );
  }


  public static function submissions( $filter=[] ) {

        $data = [];
        foreach( self::all('parsed')  as $fileReceived ) {

           $content = file_get_contents($fileReceived);
          
           $parsed = json_decode( $content,  true );
           if( ! $parsed )  continue;

          // Check the filter.  Could be anything.
          foreach( $filter as $filterKey=>$filterValue ) {
                  $parsedID = ( $parsed[$filterKey]['id'] ?? false );
                  if( $parsedID === false ) continue;
                  if( ! ($parsedID == $filterValue) ) continue 2; // Move along in the *parent* loop,
                                                                  // since this one doesn't match the filter
           }

           // Throw in the filename.  Do this at read time, so we are
           // free to change filenames without borking up the whole system
           $parsed['file_name'] = $fileReceived;

           // Add the grader structure
           $parsed['grader']=[
             'status' => 'ungraded',
             'date' => '',
             'grade' => '',
             'detail' => []
          ];

          // Toss in the bucket
          $data[] = $parsed;

      }

      return array_values( $data );

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

     $rubricFile = 'data/rubrics/' . $rubricKey . '.json';

     $rubric = File::read( $rubricFile );
     if( empty( $rubric ) ) return ['error' => json_last_error_msg(), 'file'  => $rubricFile ];
     
     $parser = new \Seld\JsonLint\JsonParser();
     try {
       $parser->parse( $rubric );
     } catch ( \Exception  $e ) {
        return ['error' => $e->getMessage() ];
     }
     $rubric = json_decode( $rubric, true );

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







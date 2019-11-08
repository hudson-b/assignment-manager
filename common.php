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

               // If writing an array, format it as JSON before storing
               if( is_array( $fileContents ) ) $fileContents = json_encode( $fileContents, JSON_PRETTY_PRINT );

               if ( file_put_contents( $filePath, $fileContents ) ) {
                    Logger::debug( "Wrote " . $filePath );
               } else {
                    Logger::debug( "Unable to save " . $filePath );
               }
             
         }

         public static function read( $filePath, $parseJSON=false ) {
               $filePath = self::__sanitize( $filePath );
               if( ! file_exists( $filePath ) ) throw new \Exception('Invalid path: ' . $filePath );
               $fileContents = file_get_contents( $filePath ) ?? ''; 
               // Parse it?
               if( $parseJSON ) $fileContents = json_decode( $fileContents, true );
               return $fileContents;
         }

}




class Data {


  const PATH = "data";


  public static function fileID( $parsed ) {

           $components = ['classroom','assignment','student'];
           $fileID = [];
           foreach( $components as $component ) {
                $fileID[] = $parsed[ $component ]['id'] ?? '0';
           } 
           return implode( '_', $fileID );
  }


  private static function all() {
        return glob( self::PATH . "/*.json" );
  }

  public static function classrooms() {

        $data=[];
        foreach( self::all()  as $file ) {
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

        foreach( self::all()  as $fileReceived ) {

           $content = file_get_contents($fileReceived);
          
           $parsed = json_decode( $content,  true );
           if( ! $parsed )  continue;

           $newFile = 'data/' . self::fileID( $parsed ) . '.json';
           if( ! file_exists( $newFile ) ) File::write( $newFile, $parsed );


           // Throw in the filename
           $parsed['file_name'] = $fileReceived;

           // Check the filter 
           foreach( $filter as $filterKey=>$filterValue ) {
                  $parsedID = ( $parsed[$filterKey]['id'] ?? false );
                  if( ! $parsedID ) continue;
                  if( ! ($parsedID == $filterValue) ) continue 2; // Move along in the *parent* loop 
           }

          // Do we need to include the content?
          if( !  ( $filter['content'] ?? false ) ) {
           foreach( ( $parsed['submission']['files']) ?? [] as $fileIndex=>$fileRecord ) {
               unset ( $parsed['submission']['files'][$fileIndex]['content'] ); 
            }
          }
      
          // Add the grader record to the submission object
          $parsed['grader']=[
            'status' => 'ungraded',
            'date' => '',
            'grade' =>[ 'letter' => '', 'score' => '' ],
          ];

          $data[] = $parsed;

      }

      return array_values( $data );

  }


 


  public static function read( $entity,  $id=false ) {
     $contents = File::read( $entity, true );
     if( $id ) $contents = ( $contents[$id] ?? false );
     return $contents;
  }


  public static function write( $entity, $record ) {
     $file = self::entityFile( $entity );
     $contents = File::read( $file, true );
     $existingRecord = $contents[ $record['id'] ] ?? [];

     // No need to write when the records are identical
     if( ! empty( array_diff( $record, $existingRecord ) ) ) {
         $contents[ $record['id'] ] = $record;
         File::write(  $file , $contents );
     }
  }


}


class Grader {


  public static function examine( $code='' ) {

     $code = explode( "\n", $code );
     
     // Extract the comments
     $comments = preg_grep( '/^.*#.*$/', $code );

     // Remove the comments
     array_filter( $code, function( $key ) {
         return ! array_key_exists( $key, $comments );       
     }, ARRAY_FILTER_USE_KEY );
  
     // Extract the variables
     $variablesFound = preg_grep( '/(\b[A-Za-z]*.\b)( ?=)(?![=<>])/', $code);
     $variables=[];
     foreach( $variablesFound as $index=>$variable ) {
          $variableName = array_filter( explode( "=", $variable) )[0];
          $variableName = trim( $variableName );
          if( array_key_exists( $variableName, $variables ) ) continue;

          $variableInfo = self::itemInfo($variableName);
          $variableInfo['line'] = $index;
          $variables[ $variableName ] = $variableInfo;
     }

     $functions = preg_grep( '/.*function.*:.*$/', $code);

     $keywords = ['function','if','elif','else','while','for','print','input'];
     foreach( $keywords as $index=>$keyword ) {
        $items = preg_grep( '/[^#]\b' . $keyword . '\b/' , $code);
        unset( $keywords[ $index ] );
        $keywords[ $keyword ] = $items;
     }

     $result =  [
           'comments' => $comments,
           'variables' => $variables,
           'keywords' => $keywords
     ];

    return $result;

  }



  public static function itemInfo( $value ) {

      $type = gettype( $value );

      $info = ['type' => $type, 'value' => $value ];
             
      switch( $type ) {
         case "string":
          $info['isMixedCase'] = self::isMixedCase( $value );
          $info['hasAlpha'] = self::hasAlpha( $value );
          $info['hasDigits'] = self::hasDigits( $value );
          $info['isUpper'] = ctype_upper( $value );
          $info['isLower'] = ctype_lower( $value );
          break;

        case "boolean":
        case "integer":
        case "double":
        case "array":
        case "object":
        default:
         break;
      }
      return $info;

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







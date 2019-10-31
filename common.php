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

    public static function tail( $lineCount=128, $level=200 ) {

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

         public static function archive( $filePath ) {
            if( ! file_exists( $filePath ) ) return;
            $backupPath = $filePath . '.' . filectime( $filePath );
            rename( $filePath, $backupPath );
         }


         public static function write( $filePath, $fileContents ) {

               $filePath = self::__sanitize( $filePath );
               self::mkdir( dirname( $filePath ) );

               // If writing an array, format it as JSON before storing
               if( is_array( $fileContents ) ) $fileContents = json_encode( $fileContents, JSON_PRETTY_PRINT );

               if ( file_put_contents( $filePath, $fileContents ) ) Logger::debug( "Wrote " . $filePath );
             
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




// Simple (really simple) database using JSON source
// and my own schema
class Data {

  const PATH = "data";
  const SCHEMA = "schema.json";


  public static function schema( $entity=false ) {

     $schema = File::read( self::SCHEMA, true );
     if( $entity ) {
        if( ! array_key_exists( $entity, $schema ) ) throw new \Exception('Invalid entity : ' . $entity );
        $schema = $schema[ $entity ];
     }
     return $schema;
  
}


  public static function read( $entity,  $id=false ) {
     $file = self::schema( $entity )['file'];
     $contents = File::read( $file, true );
     if( $id ) $contents = ( $contents[$id] ?? false );
     return $contents;
  }

  public static function write( $entity, $record ) {
     $file = self::schema( $entity )['file'];
     $contents = File::read( $file, true );
     $existingRecord = $contents[ $record['id'] ] ?? [];
     // No need to write when the records are identical
     if( ! empty( array_diff( $record, $existingRecord ) ) ) {
         $contents[ $record['id'] ] = $record;
         File::write(  $file , $contents );
     }

  }


}




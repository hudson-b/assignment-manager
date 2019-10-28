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

    public static function tail( $lineCount=128 ) {

        $fileObject = new SplFileObject( self::FILENAME , 'r');
        $fileObject->seek(PHP_INT_MAX);

        $lineEnd = $fileObject->key();

        $lineStart = ( $lineEnd - $lineCount );
        if( $lineStart < 0 ) $lineStart=0;

        $rawLines = new \LimitIterator( $fileObject, $lineStart, $lineEnd );

        $lastLines = array_reverse(  array_filter(  iterator_to_array($rawLines) ) );
        foreach( $lastLines as &$line ) {
          $line = json_decode( $line );
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
    Logger::info( "Creating " . $path );
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

       $fileContents = ( file_exists( $filePath ) ) ? file_get_contents( $filePath ) : ''; 

       // Parse it?
       if( $parseJSON ) $fileContents = json_decode( $fileContents, true );

       return $fileContents;

 }

}

// Simple (really simple) database using JSON source
class Data {

  const PATH = "data";

  private static function entityPath( $entity ) {
     return './' . self::PATH . '/' . $entity . '.json';
  }


  public static function read( $entity, $id=false ) {
     $contents = File::read( self::entityPath( $entity ), true );
     if( $id ) $contents = ( $contents[$id] ?? false );
     return $contents;
  }

  public static function write( $entity, $record ) {
     $contents = File::read( self::entityPath( $entity ), true );
     
     $existingRecord = $contents[ $record['id'] ] ?? [];

     // No need to write when the records are identical
     if( ! empty( array_diff( $record, $existingRecord ) ) ) {
         $contents[ $record['id'] ] = $record;
         File::write(  self::entityPath( $entity ), $contents );
     }

  }


}



// Singleton for SQLite database
// https://phpdelusions.net/pdo/pdo_wrapper
class Database_SQLITE{

    const FILENAME = "manager.db";

    protected static $instance = null;
    protected function __construct() {}
    protected function __clone() {}


    public static function init() {
      if (! file_exists( self::FILENAME ) ) {
          $ddl = [
               'CREATE TABLE IF NOT EXISTS classroom (  id INTEGER NOT NULL PRIMARY KEY,  name TEXT NOT NULL,  webhook_secret TEXT NOT NULL );',
               'CREATE TABLE IF NOT EXISTS student (  id INTEGER NOT NULL PRIMARY KEY,  first_name TEXT NOT NULL,  last_name TEXT NOT NULL,  email TEXT NOT NULL );',
               'CREATE TABLE IF NOT EXISTS assignment (  id INTEGER NOT NULL PRIMARY KEY,  name TEXT NOT NULL,  type TEXT NOT NULL );',
               'CREATE TABLE IF NOT EXISTS submission (  id INTEGER NOT NULL PRIMARY KEY,  student_id INTEGER NOT NULL,  assignment_id INTEGER NOT NULL,  status TEXT NOT NULL,  time_submitted TEXT NOT NULL,  time_created TEXT NOT NULL,  teacher_url TEXT,  student_url TEXT );',
               'CREATE TABLE IF NOT EXISTS files (  id INTEGER NOT NULL PRIMARY KEY,  submission_id INTEGER NOT NULL,  status TEXT NOT NULL,  time_submitted TEXT NOT NULL,  time_created TEXT NOT NULL,  teacher_url TEXT,  student_url TEXT );'
          ];
          array_map( function($sql) { Database::run( $sql ); },  $ddl );
       }
      return self::instance();
    }


    public static function instance()  {

        if ( self::$instance === null ) {

            $opts  = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => FALSE,
            ];

            // Opens or creates the database file
            $dsn = 'sqlite:' . self::FILENAME;
            self::$instance = new PDO($dsn, null, null, $opts);

        }

        return self::$instance;

    }

    public static function __callStatic($method, $args)    {
        return call_user_func_array(array(self::instance(), $method), $args);
    }


    public static function run($sql, $args = [])   {
        if (!$args)  {
             return self::instance()->query($sql);
        }
        $stmt = self::instance()->prepare($sql);
        $stmt->execute($args);
        return $stmt;
    }


}


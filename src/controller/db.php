<?php 

    require_once('../utils/dotenv.php');

    if ($_ENV["ENVIRONMENT"] !== 'PRODUCTION') {
        (new DotEnv($_SERVER['DOCUMENT_ROOT'].'/.env'))->load();
    }

    class DB {
    
        private static $writeDBConnection;
        private static $readDBConnection;
    
        public static function connectWriteDB() {
            if (self::$writeDBConnection === null) {
                self::$writeDBConnection = new PDO('mysql:host='.$_ENV["DATABASE_CONNECTION_NAME"].';dbname=api;charset=utf8', 'root', $_ENV['DATABASE_PASSWORD']);
                self::$writeDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$writeDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }
            return self::$writeDBConnection;
        }
    
        public static function connectReadDB() {
            if (self::$readDBConnection === null) {
                self::$readDBConnection = new PDO('mysql:host='.$_ENV["DATABASE_CONNECTION_NAME"].';dbname=api;charset=utf8', 'root', $_ENV['DATABASE_PASSWORD']);
                self::$readDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$readDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            }
            return self::$readDBConnection;
        }
    }


?>
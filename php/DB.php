<?php
/**
 * Класс для работы с БД (PDO)
 *
 * @package     myid
 * @author      Leon Rom
 * @version     1.0 (2017/03/08)
 */

class DB
{
    const DRIVER = 'mysql';
    const HOST = 'localhost';
    const NAME = 'cc18';
    const USER = 'leonrom';  
    const PASS = '';    
    const CSET = 'cp1251';
    const TABUSER = 'blogusers';
    const TABHIST = 'history';
    
    private static $_pdo = null;
    private static $_log = null;
    
    private static $_stms = [
        'Test' => null,
        'AddUser' => null,
        'SetName' => null,
        'GetHistory' => null,
        'AddHistory' => null,
    ];

    private static function connectDB($driver, $host, $name, $user, $pass, $char)
    {
        self::$_log->LogInfo("^ connectDB($driver, $host, $name, $user, $pass, $char)");
        try
        {
            $dsn = "$driver:host=$host;charset=$char";
            $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // режим выдачи ошибок надо задавать только в виде исключений.
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_LAZY, // чтобы не писать его в КАЖДОМ запросе
                    PDO::ATTR_EMULATE_PREPARES => false, ];
            $pdo = new PDO($dsn, $user, $pass, $opt);

            $pdo->exec("CREATE DATABASE IF NOT EXISTS $name DEFAULT CHARACTER SET $char"); // COLLATE utf8_general_ci");

            $pdo->exec("USE $name ");
            self::$_log->LogInfo("  DB `$name` created/connected and used");
            return $pdo;
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("connectDB($driver, $host, $name, $user, $pass, $char):\n  $sql\n  " . $e->getMessage());
        }
    }
    
    private static function stmsTest($pdo)
    {
        self::$_log->LogInfo("^ stmsTest(...)");
        $sql = '?';
        try
        {
            $sql = "SELECT count(*) N FROM information_schema.TABLES " .
                            " WHERE (TABLE_SCHEMA = :db) AND (TABLE_NAME = :table);";
            self::$_stms[Test] = $pdo->prepare($sql);
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("stmsTest(...):\n  $sql\n  " . $e->getMessage());
        }
    }
    
    private static function stmsInit($pdo)
    {
        self::$_log->LogInfo("^ stmsInit(...)");
        $sql = '?';
        try
        {
            $sql = "INSERT INTO " . self::TABUSER .
                    " (`id_browse`, `user_name`, `pswrd`, `email`) " .
                    " VALUES(:id_browse, :user_name, :pswrd, :email);";
            self::$_stms[AddUser] = $pdo->prepare($sql);     
            
            $sql = "SELECT user_name FROM " . self::TABUSER .
                    " WHERE server_id= :server_id;";
            self::$_stms[GetName] = $pdo->prepare($sql);
            
            $sql = "UPDATE " . self::TABUSER .
                    " SET user_name= :user_name WHERE server_id= :server_id;";
            self::$_stms[SetName] = $pdo->prepare($sql);
            
            $sql = "SELECT * FROM " . self::TABHIST .
                    " WHERE server_id= :server_id;";
            self::$_stms[GetHistory] = $pdo->prepare($sql);
            
            $sql = "INSERT INTO " . self::TABHIST .
                    " (`server_id`, `dattim`, `typ`, `val`) " .
                    " VALUES(:server_id, :dattim, :typ, :val);";                    
            self::$_stms[AddHistory] = $pdo->prepare($sql);
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("stmsInit(...):\n  $sql\n  " . $e->getMessage());
        }
    }

    private static function openTable($pdo, $db, $table, $sql)
    {
        self::$_log->LogInfo("^ openTable($db, $table)");
        try
        {
            $stmt = self::$_stms[Test];
            $stmt->execute([':db' => $db, ':table' => $table]);
            $N = $stmt->fetchColumn();
            self::$_log->LogInfo("  openTable(...) N=" . $N);

            if (intval($N) <= 0)
            {
                $pdo->exec("CREATE TABLE `$table` ($sql)");
                self::$_log->LogInfo("  `$table` created sql=$sql");
            }
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("openTable($db, $table, $sql):\n  $sql\n  " . $e->getMessage());
        }
    }

    function __construct($log)
    {
        self::$_log = $log;
        $log->LogInfo("^ __construct(...)");
        
        $pdo = self::connectDB(self::DRIVER, 
                            self::HOST, 
                            self::NAME, 
                            self::USER, 
                            self::PASS, 
                            self::CSET);

        if ($pdo && !self::$_pdo)
        {
            $log->LogInfo("  __construct(...):  self::TABUSER");
            self::stmsTest($pdo);
            
            self::openTable($pdo, self::NAME, self::TABUSER, 
                "`server_id` int(11) NOT NULL auto_increment,
                 `id_browse`  varchar(100) NOT NULL,
                 `user_name` varchar(100) NOT NULL,
                 `pswrd` varchar(100) NOT NULL,
                 `email` varchar(100) NOT NULL,
                PRIMARY KEY  (`server_id`)");
        
            $log->LogInfo("  __construct(...):  self::TABHIST");
            self::openTable($pdo, self::NAME, self::TABHIST, 
                "`history_id` int(11) NOT NULL auto_increment,
                 `server_id` int(11) NOT NULL,
                 `dattim`  varchar(22) NOT NULL,
                 `typ`  varchar(10) NOT NULL,
                 `val` varchar(100) NOT NULL,
                PRIMARY KEY  (`history_id`)");
                
            self::stmsInit($pdo);
            self::$_pdo = $pdo;
        }
    }
    
    function __destruct()
    {
        $conn = null;
    }
    
    public static function pdo(){
        return self::$_pdo;
    }
    
    public static function AddUser($id_browse, $user_name){
        self::$_log->LogInfo("^ AddUser($id_browse, $user_name)");
        try
        {
            self::$_stms[AddUser]->execute([
                ':id_browse' => $id_browse, 
                ':user_name' => $user_name,
                ':pswrd' => '?',
                ':email' => '?'
            ]);
            return self::$_pdo->lastInsertId();
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("AddUser($id_browse, $user_name):\n  " . $e->getMessage());
            return null;
        }
    }
    
    public static function SetName($server_id, $user_name){
        self::$_log->LogInfo("^ SetName($server_id, $user_name)");
        try
        {
            self::$_stms[SetName]->execute([
                ':server_id' => $server_id, 
                ':user_name' => $user_name
            ]);
            return true;
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("SetName($server_id, $user_name):\n  " . $e->getMessage());
            return null;
        }
    }

    public static function GetName($server_id){
        self::$_log->LogInfo("^ GetName($server_id)");
        try
        {
            $stmt = self::$_stms[GetName];
            $stmt->execute([
                ':server_id' => $server_id
            ]);
            return $stmt->fetchColumn();
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("GetName($server_id):\n  " . $e->getMessage());
            return null;
        }
    }    
    
    public static function GetHistory($server_id){
        self::$_log->LogInfo("^ GetHistory($server_id)");
        try
        {
            $stmt = self::$_stms[GetHistory];
            $stmt->execute([
                ':server_id' => $server_id
            ]);
            $s = '';
            while ($r = $stmt->fetch())
            {
                $s .= "dattim=$r->dattim, typ=$r->typ, val=$r->val;"; 
            }
            return $s;
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("GetHistory($server_id):\n  " . $e->getMessage());
            return null;
        }
    }  
    
    public static function AddHistory($server_id, $typ, $val){
        self::$_log->LogInfo("^ AddHistory($server_id) typ=$typ, val=$val");
        try
        {
            self::$_stms[AddHistory]->execute([
                ':server_id' => $server_id,
                ':dattim' => date('Y-m-d H:i:s'), // время д.б. UTC
                ':typ' => $typ,
                ':val' => $val,
            ]);
            return true;
        }
        catch(PDOException $e)
        {
            self::$_log->LogError("AddHistory($server_id) typ=$typ, val=$val:\n  " . $e->getMessage());
            return null;
        }
    }  
}
?>

<?php
/**
 * 
 * 
 * 
 */

/*
 *---------------------------------------------------------------
 * DEFINE ALL CONSTANTS WE NEED
 *---------------------------------------------------------------
 * 常量定义的命名约定：
 * 文件夹以PATH_* 开头，文件以FILE_* 开头，依此类推
 */
// 定义文件夹的路径
define('DS',                DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));
define('PATH_BASE',         dirname(__DIR__) . DS . 'src' . DS. 'restful' . DS);
define('PATH_APP',          PATH_BASE . 'app' .        DS);
define('PATH_APP_C',        PATH_BASE . 'app' .        DS . 'mvc' .        DS . 'controllers' .    DS);
define('PATH_APP_M',        PATH_BASE . 'app' .        DS . 'mvc' .        DS . 'models' .         DS);
define('PATH_ASSETS',       PATH_BASE . 'assets' .     DS);
define('PATH_LOGS',         PATH_APP . 'logs' .        DS);
define('PATH_CORE',         PATH_BASE . 'core' .       DS);
define('PATH_CORE_LIBS',    PATH_BASE . 'core' .       DS . 'libs' .       DS);
define('PATH_CORE_HELPERS', PATH_BASE . 'core' .       DS . 'helpers' .    DS);
define('PATH_CORE_DEBUG',   PATH_BASE . 'core' .       DS . 'debugger' .   DS);
define('PATH_CORE_COMMONS', PATH_BASE . 'core' .       DS . 'commons' .    DS);
define('PATH_CORE_PLUGINS', PATH_BASE . 'core' .       DS . 'plugins' .    DS);


// 定义文件的路径
define('EXT',       '.php');
define('FILE_BASE', PATH_BASE . 'index' .   EXT);
define('FILE_LOG',  PATH_LOGS . date('Y-m-d') . '.log');

// 定义跨平台的行尾结束符
define('EOL', PHP_EOL);
/*
 *---------------------------------------------------------------
 * INCLUDE COMMON FUNCTIONS AND LET'S DO SOME SAFETY JOBS FIRST
 *---------------------------------------------------------------
 */

/*
 *---------------------------------------------------------------
 * 引入所需的LIB类
 *---------------------------------------------------------------
 */
$lib_files = glob(PATH_CORE_COMMONS . '*' . EXT);
foreach ($lib_files as $file) {
	@include $file;
	unset($file);
}
unset ($lib_files);

//require_once PATH_CORE_COMMONS . 'commons.php';

// turn off register_globals
unregister_GLOBALS();

// turn off magic quotes
@set_magic_quotes_runtime(0);
if(get_magic_quotes_gpc()) {
    function stripslashes_deep($value) {
      $value = is_array($value) ? array_map('stripslashes_deep', $value) : (isset($value) ? stripslashes($value) : null);
      return $value;
    }

    $_POST   = stripslashes_deep($_POST);
    $_GET    = stripslashes_deep($_GET);
    $_COOKIE = stripslashes_deep($_COOKIE);
}
/*
 *---------------------------------------------------------------
 * 引入所需的LIB类
 *---------------------------------------------------------------
 */
/* $lib_files = glob(PATH_CORE_LIBS . '*' . EXT);
foreach ($lib_files as $file) {
    include $file;
    unset($file);
}
unset ($lib_files);
*/

require  PATH_CORE_LIBS.'config.class.php';

require  PATH_CORE_LIBS.'log.class.php';


/*
 *---------------------------------------------------------------
 * 引入全部controller
 *---------------------------------------------------------------
 */
$controllers_files = glob(PATH_APP_C . '*' . EXT);
foreach ($controllers_files as $file) {
    include $file;
    unset($file);
}
unset ($lib_files); 

/*
 *---------------------------------------------------------------
 * 加载配置文件
 *---------------------------------------------------------------
 */
$config_files = glob(PATH_APP . 'config' . DS . '*' . EXT);
$config = array();
foreach ($config_files as $config_file) {
    $key = substr(strrchr($config_file, DS), 1, -strlen(EXT));
    $config[$key] = include $config_file;
    unset($key);
    unset($config_file);
}
unset($config_files);

/*
 *---------------------------------------------------------------
 * INITIALIZE CONFIG CLASS
 *---------------------------------------------------------------
 * Retrieve config item via: $CFG::get('application.aaa.ddd.eee');
 * 使用单例模式
 */
$CFG = \Config::getInstance($config);
unset($config);
/*
 *---------------------------------------------------------------
 * SET DEFAULT TIMEZONE
 *---------------------------------------------------------------
 */
date_default_timezone_set($CFG::get('application.timezone'));

/*
 *---------------------------------------------------------------
 * SET ERROR REPORTING LEVEL
 *---------------------------------------------------------------
 */
if ($CFG::get('application.error_reporting') === true) {
	ini_set('display_errors','On');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors','Off');
    error_reporting(0);
}

/*
 *---------------------------------------------------------------
 * SET DEBUGGER
 *---------------------------------------------------------------
 */
if ($CFG::get('application.debug') === true) {
    $debug_tool = $CFG::get('application.debug_tool');
    switch ($debug_tool) {
        case 'dbug':
            require PATH_CORE_DEBUG . 'dBug' . DS .'dBug' . EXT;
            break;
        case 'kint':
            require PATH_CORE_DEBUG . 'kint' . DS .'Kint.class' . EXT;
            break;
        default:
            break;
    }
}


// 异常和错误都使用了 error_log() 来写错误
// 只有用户自己的log才使用日志类来写

/*
 *---------------------------------------------------------------
 * SET ERROR HANDLER
 *---------------------------------------------------------------
 *
 * 全部预定义的Error常量对应的数值（即$errorNo）见：http://php.net/manual/en/errorfunc.constants.php
 * error发生时自动触发，或手动触发：trigger_error("发生了一个错误")
 */
set_error_handler(function ($errorNo, $errMsg, $errFilePath, $errLine){
    $logInfo = '['.date('Y-m-d H:i:s').'] Error: '.$errMsg.', on line: '.$errLine.', in file: '.$errFilePath.EOL;
    //echo $logInfo;
    error_log($logInfo, 3, FILE_LOG);
});

/*
 *---------------------------------------------------------------
 * SET EXCEPTION HANDLER FOR EXCEPTION WITHOUT TRY...CATCH...
 *---------------------------------------------------------------
 *
 * 设置一个异常处理器把异常写进log里
 * 专门用于处理 try...catch... 之外的异常
 * 可使用 throw new Exception('异常信息') 手动触发
 */
set_exception_handler(function ($e) {
    $logInfo = '['.date("Y-m-d H:i:s").'] Exception on line: '.$e->getLine().', in file: '.$e->getFile()
               .', with message: '.$e->getMessage().EOL;
    //echo $logInfo;
    error_log($logInfo, 3, FILE_LOG);
});

/*
 *---------------------------------------------------------------
 * SET EXCEPTION HANDLER FOR EXCEPTION WITHIN TRY...CATCH...
 *---------------------------------------------------------------
 *
 * 设置一个用于标识的异常处理器：Phxexception
 *  try {
 *      throw new Phxexception("异常信息");
 *  } catch(Phxexception $e) {
 *      echo $e->getMsg();
 *  }
 */
class Phxexception extends Exception
{
    public function __construct($message) {
        parent::__construct($message);
    }
    // 这里不打印，是因为会在catch里进行打印
    public function getMsg()
    {
        $logInfo = '['.date("Y-m-d H:i:s").'] Phxexception on line: '.$this->getLine().', in file: '.$this->getFile()
                   .', with message: '.$this->getMessage().EOL;
        error_log($logInfo, 3, FILE_LOG);
        return $logInfo;
    }
}
/*
 *---------------------------------------------------------------
 * INCLUDE IoC CONTAINER
 *---------------------------------------------------------------
 * Append your own IoC class in ioc.php file
 */
//include 'ioc'.EXT;

/*
 *---------------------------------------------------------------
 * RESOLVE ALL CLASSES
 *---------------------------------------------------------------
 * $test = IoC::resolve('classname');
 */
//$test = IoC::resolve('classname');

/*
 *---------------------------------------------------------------
 * SET CONTROLLER AND MODEL CLASSES TO BE AUTOLOAD
 *---------------------------------------------------------------
 * 参考：http://php.net/manual/en/function.spl-autoload-register.php
 * $classname  eg. Default_Controller
 */
spl_autoload_register(function ($classname){
    try {
        $fileFound = false;
        
        $filename = PATH_APP_C.strtolower($classname).EXT;
        if( is_file($filename)){
            $fileFound = true;
            include $filename;
        }

        /* $filename = PATH_APP_M.strtolower($classname).EXT;
        if( is_file($filename)){
            $fileFound = true;
            include $filename;
        } */
        
		if( !$fileFound){
	        $newclass = ROOT . '\\' . $classname . '.php';
	        if(! is_file($newclass)){
	        	$classname = 'lib\\' . $classname;
	        	$newclass = ROOT . '\\' . $classname . '.php';
	        }
	        if( is_file($newclass) ){
	        	$fileFound = true;
		        $classname = $newclass;
		        @include_once $classname;
	        }
		}
        
        if($fileFound = false) {
            throw new Phxexception("无法自动加载该类：".$filename);
        }
    } catch(Phxexception $e) {
        echo $e->getMsg();
    }
});
/*
 *---------------------------------------------------------------
 * INCLUDE php-o
 *---------------------------------------------------------------
 */
/* if ($CFG::get('application.php-o') === true) {
    include PATH_CORE_PLUGINS . 'php-o' . DS . 'O.php';
} */


/**
 * 引入原数据库配置
 * =============================================================
 */
define('DIR_ROOT', dirname(__DIR__));
$config = include DIR_ROOT . '/config/config.php';
include_once DIR_ROOT . '/src/lib/classes/Info.php';
//初始化memcache
$mc = new YY\Mcache($config['memcached']);
\Zend\Registry::set('mc', $mc);


//初始化数据库
$dbm = \Zend\Db\Db::factory($config['db']['default']['master']['adapter'], $config['db']['default']['master']);
$dbs = \Zend\Db\Db::factory($config['db']['default']['slave']['adapter'], $config['db']['default']['slave']);
\Zend\Registry::set('default_master', $dbm);//对应config.php里db的配置项
\Zend\Registry::set('default_slave', $dbs);
\Zend\Db\Table\Table::setDefaultAdapter($dbs);

/*
 *---------------------------------------------------------------
 * 初始化路由器
 * 单例化
 *---------------------------------------------------------------
 */
require 'frontcontroller.php';
$frontController = FrontController::getInstance();
$frontController->route();



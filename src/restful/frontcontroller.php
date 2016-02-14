<?php

/**
 * REST总路由控制
 * @author yj
 * 先引导系统，然后单例方式实例化本前端控制器类（FC），FC负责解释请求的变量，接管程序的运行，分发路由至用户自定义的类控制器（AC）
 * 根据请求路径定位具体方法
 *  */
class FrontController {
	
    private static $_instance = null;
    protected $_controller;
    protected $_action;
    protected $_params = array();

   	/**
   	 * 构造方法
   	 * 定位controller与action，
   	 * 整理参数，此参数为路由地址中的设定参数，非url与post中的标准参数：
   	 * 		如：uri为/aaa/bbb/first/1/second/2
   	 * 		参数为：first=>1,second=>2
   	 * 防止直接new
   	 */ 
    private function __construct() {
	    global $CFG;
        // parse uri into string
        $newURI = parse_url($_SERVER['REQUEST_URI']);
        $request_uri = $newURI['path'];
        $request_str = str_replace($CFG::get('application.base_url'), '', $request_uri);
        while(substr($request_str, strlen($request_str)-1) == '/') {
            $request_str = substr($request_str, 0, -1);
        }
        // convert uri into array
        $request_str_arr = explode('/', $request_str);
        
        unset($request_uri);
        unset($request_str);
	
        // first two segments is controller/action
        $this->_controller = empty($request_str_arr['1']) ? $CFG::get('application.default_controller') : $request_str_arr['1'];
        $this->_action     = empty($request_str_arr['2']) ? $CFG::get('application.default_action') : $request_str_arr['2'];

        
        //PATH_BASE . 'keys/', $appid.'_'
        /*
         * URI参数获取：
         * 		当参数只有一个字符串时：为加密参数
         * 		当参数为若干对值时：为一般的参数
         */
        
        for($i = 3; $i < count($request_str_arr); $i++){
            $f = $i%2;
            if($f == 1) $this->_params[$request_str_arr[$i]] = empty($request_str_arr[$i + 1]) ? null : $request_str_arr[$i + 1];
        }
        
        \Log::info('Initialized FC successfully');
    }

    /*
     * 防止直接clone
     */
    private function __clone() {}

    /**
     * 单例
     * @return FrontController
     */
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new FrontController();
        }
        return self::$_instance;
    }
	
	/**
	 * 方法类别识别与重用
	 * 根据控制器和方法名称，执行控制器对应的方法
	 */
	public function route() {
		$request = new \REST\Request (); // request的空类(没有实际功能性用途)
		$request->verb = $_SERVER ['REQUEST_METHOD'];
		$request->posted = null;
		                          
		//取定义类名
		$controllerName = ucfirst ( $this->_controller ) . '_Controller';
		/*
		 * 请求方式分支
		 */
		switch ($request->verb) {
			// GET方法 以get_开始
			case 'GET' :
				$action = 'get_' . $this->_action;
				break;
			// POST、PUT方法以post_开始
			case 'POST' :
			case 'PUT' :
				$action = 'post_' . $this->_action;
				//接收POST信息
				$request->posted = file_get_contents ( 'php://input' );
				break;
			// DELETE方法以del_开始
			case 'DELETE' :
				$action = 'del_' . $this->_action;
				break;
			default :
		}
		
		//具体类方法的路由
		if (class_exists ( 'REST\\app\\controllers\\'.$controllerName, false)) {
			$cName = 'REST\\app\\controllers\\'.$controllerName;
			$controllerHandler = new $cName();
			if (method_exists ( $controllerHandler, 'init' )) {
				$error = $controllerHandler->init ( $this->_params, $request->posted );
			}
			if(isset($error)){
				throw new Exception ( 'Some Error For ：' . $controllerName . '-' . $action . ':' . $error);
			}
			if (! method_exists ( $controllerHandler, $action )) {
				throw new Exception ( 'Unknown Request For ：' . $controllerName . ':' . $action );
			}
			$response = $controllerHandler->$action ( $this->_params, $request->posted );
		} else {
			header ( 'HTTP/1.0 400 Bad Request' );
			$response = "Unknown Request for " . $controllerName;
		}
		
		/*
		 * 总体输出控制
		 * 默认为JSON
		 */
		if (isset($_GET['callback'])){
			echo $_GET['callback'] . '('.json_encode ( $response ).')';
		}else {
			/*
			* 指定跨域
			*/
			header('Access-Control-Allow-Origin: *');
			echo json_encode ( $response );
		}
		
	}
}

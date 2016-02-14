<?php
namespace REST;
use src\lib\model\Member;
require (PATH_CORE_LIBS . 'RSAutil.class.php');

class Controller {
	
	 /*
	 * 统一返回变量
	 */
	protected $_result;
	protected $_baseUrl;	//
	protected $_esnUrl;
	
	protected $_defaultqzid;	//用户默认圈子（备用）
	protected $_mid;
	
	
	/*
	 * 初始化方法
	 * @param &$param 参数地址引用 ，进行适应性参数修正
	 */
	protected function init(array &$param){
		//exit(var_dump($_SERVER));
		//取header的appid与签名
		/* if(!isset($_SERVER['HTTP_AUTHORZATION']) || !isset($_SERVER['HTTP_AUTHSIGN'])){
			\Log::info('no access!');
			return ('no access!');
		}
		$appid = $_SERVER['HTTP_AUTHORZATION'];
		$sign = $_SERVER['HTTP_AUTHSIGN'];
		//取时间戳
		$ts = $_GET['_'];
		if($ts < time()-900 || $ts > time()){
			\Log::info('invalid timestamp!');
			return (json_encode(102));	//无效时间戳
		}
		//进行密钥检测
		$flag = $this->vilidSign($appid, $sign);
		if($flag !== true){
			//密钥验证失败
			\Log::info(json_encode($flag));
			return (json_encode($flag));
		}
		\Log::info($_SERVER['HTTP_AUTHORZATION'].'签名验证通过'); */
		/*
		 * 人员信息
		 */
		if(isset($param['mid'])){
			$memInfo = $this->getMember($param['mid']);
			if(is_array($memInfo) && isset($memInfo['id'])){
				$param['mid'] = $memInfo['id'];
				$this->_mid = $memInfo['id'];
				//取用户的默认圈子ID
				if($memInfo){
					$this->_defaultqzid = $memInfo['default_qz'];
				}
				//如果没有指定圈子ID，则用默认ID
				if(!isset($param['qzid'])){
					$param['qzid'] = $memInfo['default_qz'];
				}
			}
		}else {
			//如果没有用户ID，理论上应该不再往下执行
		}
		
		global $CFG;
		// parse uri into string
		$this->_baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . $CFG::get('application.base_url');
		if($CFG::get('application.esnurl') == ''){
			$this->_esnUrl = 'http://' . $_SERVER['HTTP_HOST'];
		}else {
			$this->_esnUrl = $CFG::get('application.esnurl');
		}
		$this->_result = array(
				'rs'=>null,
				'info'=>'',
				'data'=>null
		);
	}

	/*
	 * 签名验证方法
	 */
	private function vilidSign ($appid, $auth_sign){
		if(!is_file(PATH_BASE . 'keys/' . $appid.'_privkey.pem')){
			return 5;	//密钥文件不存在
		}
		try{
			$rsa = new \RSAutil(null, PATH_BASE . 'keys/' . $appid.'_privkey.pem');
			$source = $rsa->privDecrypt($auth_sign);
			if($source){
				$arr = explode('_', $source);
				if(count($arr) === 2){
					$appid_t=  $arr[0];
					$ts = $arr[1];
					if($appid_t !== $appid){
						return 1;	//签名不匹配
					}
					if($ts < time()-900 || $ts > time()){
						return 2;	//无效时间戳
					}
				}else {
					return 3;		//签名无效
				}
			}else {
				return 4;			//非法签名
			}
		}catch (Exception $e){
			return 0;			//未知错误
		}
		return true;			//验证通过
	}
	
	/*
	 * 根据不同情况获取人员信息
	 */
	protected function getMember($id){
		$t = isset($_GET['t']) ? $_GET['t'] : '0';	//用户标识类型
		$memberM = new \src\lib\model\Member();
		if($t == '0'){	//人员ID
			$memberInfo = $memberM->getInfo($id);
		}elseif ($t == '1'){	//用户名称
			include_once DIR_ROOT . '/lib/yycenterSDK/sdk.php';
			$rs = yycenter_call('User', 'info', $id);
			if ($rs ['succeed']) {
				$userinfo = $rs ['data'];
				$userid = $userinfo['uid'];
				$memberInfo = $memberM->getMemberInfoByUserID($userid);
			}else {
				$memberInfo = array();
			}
		}elseif ($t == '2'){	//用户ID
			$memberInfo = $memberM->getMemberInfoByUserID($id);
		}
		return $memberInfo;
	}
	
	/**
	 * 统一返回方法
	 * @param unknown $rs
	 * @param unknown $info
	 * @param unknown $data
	 * @return Ambigous <multitype:NULL string , unknown>
	 */
	protected function returnFn ($rs, $info, $data = ''){
		$this->_result['rs'] = $rs;
		$this->_result['info'] = $info;
		$this->_result['data'] = $data;
		return $this->_result;
	}
	
	

    //加载模型，返回模型对象实例
    public function loadModel($name)
    {
        require(APP_DIR .'models/'. strtolower($name) .'.php');

        $model = new $name;
        return $model;
    }

    //加载视图，返回视图对象实例
    public function loadView($name)
    {
        $view = new View($name);
        return $view;
    }

    //加载插件
    public function loadPlugin($name)
    {
        require(APP_DIR .'plugins/'. strtolower($name) .'.php');
    }

    //加载辅助类库，返回辅助类库实例
    public function loadHelper($name)
    {
        require(APP_DIR .'helpers/'. strtolower($name) .'.php');
        $helper = new $name;
        return $helper;
    }

    public function to_home()
    {
        global $CFG;
        header('Location: '. $CFG::get('application.hostname').$CFG::get('application.base_url'));
    }
}

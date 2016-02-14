<?php
namespace REST\app\controllers;

use REST\Controller;
class Im_Controller extends Controller{
	
	private static $IM_URL = 'http://172.20.8.202:9090/sysadmin/rest';
	private static $APPID = '8076d91247be9c3195cdb24b476ed7e3';
	private static $APPSECRET = 'C1355E00BD8BA84C8230F37130C63A45';
	
	public function init(array &$param){
		parent::init($param);
	}
	
	/**
	 * 从IM SERVER请求token
	 * @param
	 * @return {code: , message:}
	 */
	public function get_login(array $param){
		/* $postedArr = json_decode($posted, true);
		//$posted不是json串
		if(!$postedArr){
			return 'error: 非法参数！';
		} */
		//引入ucenter sdk入口文件
		include_once ROOT.'/lib/yycenterSDK/sdk.php';
		include_once PATH_CORE_LIBS . '/RestUtil.class.php';
		if(!isset($param['user']) || !isset($param['pwd'])){
			return array(
				'code'=>false,
				'message'=>'用户名或密码错误！'
			);
		}
		$username = $param['user'];
		$password = $param['pwd'];
		$rs = yycenter_call('User', 'authLogin', array('username'=>$username,'password'=>$password));
		if($rs){
			if($rs['succeed']){
				$memberM = new \src\lib\model\Member();
				$memberinfo = $memberM->getMemberInfoByUserID($rs['data']['uid']);
				$postData = array(
					'username'=>$memberinfo['id'],
					'clientId'=>self::$APPID,
					'clientSecret'=>self::$APPSECRET,
					'nickname'=>$memberinfo['name']
				);
				$res = \RestUtil::post(self::$IM_URL.'/yonyou/space/token',json_encode($postData),array("Content-Type: application/json"));
				$header = $res->getHeaders();
				if($header['code'] == '200'){
					$resData = json_decode($res->getResponse(), true);
					$resData['id'] = $memberinfo['id'];
					$resData['username'] = $memberinfo['name'];
					return $resData;
				}else {
					$header['info'] = $res->getResponse();
					return $header;
				}
			}else {
				return array(
					'code'=>false,
					'message'=>$rs['msg']
				);
			}
		}else {
			return array(
				'code'=>false,
				'message'=>'用户信息验证失败!'
			);
		}
	}
}
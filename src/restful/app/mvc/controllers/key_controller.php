<?php
namespace REST\app\controllers;
require_once PATH_CORE_LIBS . 'RSAutil.class.php';
require_once PATH_CORE_LIBS . 'simcode.class.php';
/**
 * 密钥及APPID方法
 * @author yj
 */
class Key_Controller{
	
	/**
	 * 
	 * @param unknown $param
	 */
	public function init($param){
		
	}
	
	/**
	 * 生成新的密钥对
	 * @param unknown $param
	 * @return string|boolean
	 */
	public function get_newpair($param){
		$appid = $_SERVER['HTTP_AUTHORZATION'];
		if($appid){
			//检查此APP与密钥的状态
			$wsuserM = new \src\lib\model\WsUser();
			$appuser = $wsuserM->findUser('',$appid);
			//用户不存在
			if(count($appuser)!=1){
				return null;
			}
			//用户存在
			if($appuser[0]['state']==1){
				//如果已经生成过密钥，返回原来的公钥
				//$pubkeyFile = file_get_contents(PATH_BASE . 'keys/' . $appid.'_pubkey.pem');
				//return base64_encode($pubkeyFile);
				//如果已经生成过密钥，则不予返回
				return;
			}else {
				//如果是新用户，则生成新的密钥对
				$rsa = new \RSAutil();
				$keyArr = $rsa->createKey(PATH_BASE . 'keys/', $appid.'_');
				//改密钥状态为已生成
				$wsuserM->changeState($appid, 1);
				return base64_encode($keyArr['pubkey']);
			}
		}
		return null;
	}
	
	/**
	 * 添加APP用户
	 * @param unknown $param
	 * @param unknown $posted
	 * @return string
	 */
	public function post_wsuser($param, $posted){
		
		$postedArr = json_decode($posted, true);
		//$posted不是json串
		if(!$postedArr){
			return 'error: 非法参数！';
		}
		//检查是否已经用此邮箱申请过APPID
		$wsuserM = new \src\lib\model\WsUser();
		$user = $wsuserM->findUser('','',$postedArr['email']);
		if(count($user)){
			return '此邮箱已经注册过,请到邮箱中查看！';
		}else {
			$id = $wsuserM->addWsUser($postedArr['email'], $postedArr['company_name'], $postedArr['tel']);
			$chars = $id.'_'.$postedArr['email'].'_'.time();
			$encodeChars = \SimCode::encode($chars, \Config::get('application.key'));
			//返回激活链接
			return \Config::get('application.hostname').'key/active'.'?s='.$encodeChars;		
		}
	}
	
	/**
	 * 获取APP用户信息
	 * @param unknown $param
	 * @return \src\lib\model\dbtable\WsUser
	 */
	public function get_wsuser($param){
		$wsuserM = new \src\lib\model\WsUser();
		return $wsuserM->findUser('',$param['appid']);
	}
	
	/**
	 * 激活用户
	 * @param unknown $param
	 * @return number
	 */
	public function get_active($param){
		//获取验证加密码
		$code = $_REQUEST['s'];
		//解码
		$decodeChars = \SimCode::decode($code, \Config::get('application.key'));
		//0：ID 1：email 2：ts
		$infoArr = explode('_', $decodeChars);
		//时间戳检查
		if(intval($infoArr[2])+86400 < time()){
			return '此链接已经失效！';
		}
		$wsuserM = new \src\lib\model\WsUser();
		$id = $infoArr[0];
		$appuser = $wsuserM->findUser($id);
		//如果此APPID存在且未激活
		if(count($appuser)===1 && $appuser[0]['active']==0){
			//激活操作
			$appid = rand(10000000, 99999999);	//生成 随机的8位appid
			$wsuserM->updateUser(array('appid'=>strval($appid)), array('id=?'=>$id)).'===';	//赋予APPID
			$wsuserM->activeUser($id);	//激活用户
			return $appid;
		}else{
			return '无效的链接！';
		}
	}
	
	/**
	 * 修改密钥状态
	 * @param $param
	 * @param $posted
	 * @return string
	 */
	public function get_state($param){
		//$postedArr = json_decode($posted, true);
		//$posted不是json串
		if(!$param['appid'] || !$param['state']){
			return 'error: 非法参数！';
		}
		$wsuserM = new \src\lib\model\WsUser();
		return $wsuserM->changeState($param['appid'], $param['state']);
	}
	
}
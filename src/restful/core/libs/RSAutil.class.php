<?php
/**
 * openssl RSA 密钥对工具
 * @author yj
 * 
 */
class RSAutil {
	private $_privKey;	//私钥
	private $_pubKey;	//公钥
	
	private $_keyPath;
	
	/**
	 * 初始化构造方法
	 * @param string $pubkeyFile 公钥文件路径
	 * @param string $privkeyFile 私钥文件路径
	 * @throws \Exception
	 */
	public function __construct($pubkeyFile = null, $privkeyFile = null){
		if($pubkeyFile){
			$this->setupPubKey($pubkeyFile);
		}
		if($privkeyFile){
			$this->setupPrivKey($privkeyFile);
		}
	}
	
	/**
	 * 生成一对公私钥方法
	 * @return array privkey=>私钥,pubkey=>公钥
	 * 		$filepath 为本地要保存密钥对文件的路径
	 * 		$prefix 要保存文件的前缀
	 */
	public function createKey($filepath = null, $prefix = ''){
		$res = openssl_pkey_new();
		openssl_pkey_export($res, $privKey);
		$this->_privKey = openssl_pkey_get_private($privKey);
		
		$resp = openssl_pkey_get_details($res);
		$pubKey = $resp['key'];
		$this->_pubKey = openssl_pkey_get_public($pubKey);
		
		if(is_dir($filepath)){
			file_put_contents($filepath.'/'.$prefix.'privkey.pem', $privKey);
			file_put_contents($filepath.'/'.$prefix.'pubkey.pem', $pubKey);
		}
		
		return array(
			'privkey' => $privKey,
			'pubkey' => $pubKey
		);
	}
	
	/**
	 * 判断密钥文件是否存在
	 * @return boolean
	 */
	public function hasKey(){
		$pubfile = $this->_keyPath.'pubkey.pem';
		if(is_file($pubfile)){
			return true;
		}else {
			return false;
		}
	}
	
	/**
	 * 加载私钥
	 * @return boolean
	 */
	public function setupPrivKey($privkeyFile){
		/* if(is_resource($this->_privKey)){
			return true;
		} */
		if(is_file($privkeyFile)){
			$privkey = file_get_contents($privkeyFile);
			$this->_privKey = openssl_pkey_get_private($privkey);
			return true;
		}
		return false;
	}
	
	/**
	 * 加载公钥
	 * @return boolean
	 */
	public function setupPubKey($pubkeyFile){
		/* if(is_resource($this->_pubKey)){
			return true;
		} */
		if(is_file($pubkeyFile)){
			$pubkey = file_get_contents($pubkeyFile);
			$this->_pubKey = openssl_pkey_get_public($pubkey);
			return true;
		}
		return false;
	}
	
	/**
	 * 私钥加密
	 * @param unknown $data
	 * @return NULL|string
	 */
	public function privEncrypt($data){
		if(!is_string($data)){
			return null;
		}
		//$this->setupPrivKey();
		$r = openssl_private_encrypt($data, $crypted, $this->_privKey);
		if($r){
			return base64_encode($crypted);
		}
		return null;
	}
	
	/**
	 * 私钥解密
	 * @param unknown $encrypted
	 * @return NULL|unknown
	 */
	public function privDecrypt($crypted){
		if(!is_string($crypted)){
			return null;
		}
		//$this->setupPrivKey();
		$crypted = base64_decode($crypted);
		$r = openssl_private_decrypt($crypted, $decrypted, $this->_privKey);
		if($r){
			return $decrypted;
		}
		return null;
	}
	
	/**
	 * 公钥加密
	 * @param unknown $data
	 * @return NULL|string
	 */
	public function pubEncrypt($data){
		if(!is_string($data)){
			return null;
		}
		//$this->setupPubKey();
		$r = openssl_public_encrypt($data, $crypted, $this->_pubKey);
		if($r){
			return base64_encode($crypted);
		}
		return null;
	}
	
	/**
	 * 公钥解密
	 * @param unknown $crypted
	 * @return NULL|unknown
	 */
	public function pubDecrypt($crypted){
		if(!is_string($crypted)){
			return null;
		}
		//$this->setupPubKey();
		$crypted = base64_decode($crypted);
		$r = openssl_public_decrypt($crypted, $decrypted, $this->_pubKey);
		if($r){
			return $decrypted;
		}
		return null;
		
	}
	
	/**
	 * 销毁方法
	 */
	public function __destruct(){
		if($this->_pubKey)
			openssl_free_key($this->_pubKey);
		if($this->_privKey)
			openssl_free_key($this->_privKey);
		//@ fclose($this->_privKey);
		//@ fclose($this->_pubKey);
	}
}



<?php
namespace REST\app\controllers;

use REST\Controller;

/**
 * 发言
 * @author yinjie
 */
class Speech_Controller extends Controller{
	
	/*
	 * 初始化方法
	 */
	public function init(array &$param){
		parent::init($param);
	}
	
	/**
	 * 发言
	 * @method POST
	 * @param array $param:
	 * 		保留
	 * @param unknown $posted：
	 * 		mid: 人员Id
	 * 		qid: 圈子ID
	 * 		cont: 发布内容
	 * @return $newConvsId 返回此发言ID
	 */
	public function post_detail(array $param, $posted){
		$postedArr = json_decode($posted, true);
		//$posted不是json串
		if(!$postedArr){
			$postedArr = $_POST;
			//return 'error: 非法参数！';
		}
		//必要参数
		$mid = $param['mid'];	//人员ID
		$qid = $param['qzid'];	//圈子ID
		$content = urldecode($postedArr['cont']);	//内容
		if(!$mid || !$qid || !$content){
			return 'error: 缺少必要参数！';
		}
		$gid = isset($postedArr['gid']) ? $postedArr['gid'] : '0'; 	//群组ID
		$frommodelus = isset($postedArr['fm']) ? $postedArr['fm'] : 'remote'; 	//模块来源
		$fid = isset($postedArr['fid']) ? $postedArr['fid'] : ''; 	//父会话ID
	
		//初始化发布M
		$speechM = new \src\lib\model\Speech($qid);
		$newConvsId = $speechM->speech($mid, $content);
		return $newConvsId;
	}
	
	/**
	 * 获取一个发言的详情
	 * @method GET
	 * @param array $param
	 * 		id: speech id
	 * 		qid: 圈子 id
	 * @return speech obj
	 */
	public function get_detail(array $param){
		$sid = $param['id'];		//speech id
		$qzid = $param['qzid'];
		if(!$sid){
			return 'error: 缺少必要参数！';
		}
		$speechM = new \src\lib\model\Speech($qzid);
		return $speechM->speechInfoById($sid);
	}
	
}

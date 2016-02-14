<?php
namespace REST\app\controllers;

use REST\Controller;
use src\lib\model\Group;
use src\lib\model\Member;
use src\lib\model\Feed;
use src\lib\Common;

class Group_Controller extends Controller{
	
	public function init(array &$param){
		parent::init($param);
	}
	
	
	/**
	 * 获取人员群组列表
	 * @param array $param
	 * @return string
	 */
	public function get_list(array $param){
		if(!isset($param['mid'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		//$groupid = $param['group'];
		$groupM = new Group(0, $param['qzid']);
		$rs = $groupM->myGroupListSort($mid);
		$rsData = array();
		foreach ($rs as $key => $value){
			if($value['gid']){
				$rsData[$key] = $value;
			}
		}
		return $rsData;
	}
	
	/**
	 * 获取群组成员信息
	 * @param array $param
	 * @return string|multitype:
	 */
	public function get_person(array $param){
		if(!isset($param['mid']) || !isset($param['group'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$groupid = $param['group'];
		$groupM = new Group($groupid, $param['qzid']);
		$rs = $groupM->getMemberListByGroupID($groupid);
		$memberM = new Member();
		foreach ($rs as &$member){
			$userinfo = $memberM->getInfo($member['member_id']);
			include_once ROOT.'/lib/yycenterSDK/sdk.php';
			$uname = yycenter_call('User', 'username', $userinfo['user_id']);
			$member['username'] = $uname['data'];
			//echo json_encode($uname);
		}
		return $rs;
	}
	
	/**
	 * 加入群
	 * @param array $param
	 * @return string|boolean
	 */
	public function post_do(array $param){
		if(!isset($param['mid']) || !isset($param['gid'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$gid = $param['gid'];
		$groupM = new Group($gid, $param['qzid']);
		return $groupM->joinGroup($mid);
	}
	
	/**
	 * 退出群
	 * @param array $param
	 * @return string|boolean
	 */
	public function del_do(array $param){
		if(!isset($param['mid']) || !isset($param['gid'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$gid = $param['gid'];
		$groupM = new Group($gid);
		echo $mid;
		echo $gid;
		return $groupM->exitGroup($mid);
	}
	
	/**
	 * 创建群
	 * @param array $param
	 * @param array $post
	 * @return string|Ambigous <boolean, string>
	 */
	public function post_info(array $param, array $post){
		if(!isset($param['mid'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$qzid = $param['qzid'];
		$postedArr = json_decode($post, true);
		//$posted不是json串
		if(!$postedArr){
			return 'error: param invalid!';
		}
		$groupM = new Group(0, $qzid);
		$group_name = $postedArr['gname'];
		$description = $postedArr['description'];
		$pub = 1;
		
		//判断群组名是否已经存在
		if( $groupM->groupExist($group_name, 0) ){
			return 'groupname already used';
		}
		$insert = array(
				'group_name' => $group_name,
				'qz_id'      => $qzid,
				'member_id'	 => $mid,
				'group_logo' => '',
				'pub'	 => 1,
				'membernum'	 => 1,
				'description'=> Common::substr(trim($description), 200, 'utf-8', 0),
				'created'    => time(),
				'grade'		 => 0
		);
		$gid = $groupM->addGroup($insert);
		if($gid){
			//创建群组的系统动态
			$feed = new Feed($qzid);
			$msg = array(
				'author_name' => 'IM',
				'object_value' => $group_name
			);
			$feed->systemAdd($mid, $gid, $feed::OBJECT_TYPE_GROUP, $msg, "GROUP_".$feed::FEED_KEY_ADD);
			$feed->systemAdd($mid, $gid, $feed::OBJECT_TYPE_GROUP, $msg, "GROUP_".$feed::FEED_KEY_JOIN);
			return $gid;
		} else{
			return 'add group fail!';
		}
		
	}
}

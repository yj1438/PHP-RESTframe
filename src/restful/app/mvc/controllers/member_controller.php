<?php
namespace REST\app\controllers;

use REST\Controller;
use src\lib\model\Member;
use src\lib\model\Followed;
use src\lib\model\FollowedGroup;
use src\lib\classes\upload\Image as ImageClass;
/**
 * 人员
 * @author yinjie
 */
class Member_Controller extends Controller{
	
	private $resaultData = array();
	
	/**
	 * 初始化方法
	 * 能过初始方法可以在$param设定用户默认的圈子ID
	 * @param array $param
	 */
	public function init(array &$param){
		$prs = parent::init($param);
		if(isset($prs))
			return $prs;
		/* $this->resaultData['resource'] = 'member';
		$this->resaultData['routParam'] = $param;
		$this->resaultData['uriParam'] = $_GET; */
	}
	
	/**
	 * 获取人员的基本信息
	 * @param array $param:
	 * 		mid : 人员ID(memberId) (必要)
	 * 		qzid : 圈子ID
	 */
	public function get_baseinfo(array $param){
		$mid = $param['mid'];
		$qzid = isset($param['qzid'])? $param['qzid'] : '0';
		$memberM = new \src\lib\model\Member($mid, $qzid);
		$memberInfo = $memberM->getFullInfo($mid);
		//修改头像地址
		if(isset($memberInfo['avatar'])){
			$memberInfo['avatar'] = $this->_esnUrl . 'download/' . $memberInfo['avatar'];
		}
		if(isset($memberInfo['avatar_middle'])){
			$memberInfo['avatar_middle'] = $this->_esnUrl . 'download/' . $memberInfo['avatar_middle'];
		}
		if(isset($memberInfo['avatar_small'])){
			$memberInfo['avatar_small'] = $this->_esnUrl . 'download/' . $memberInfo['avatar_small'];
		}
		return $memberInfo;
	}
	
	
	/**
	 * 获取关注或被关注人列表
	 * @param array $param
	 * 			mid : 人员ID require
	 * 			type :  0:我关注的；1：关注我的
	 * @return Ambigous <multitype:, unknown>
	 */
	public function get_follow(array $param){
		$mid = $param['mid'];
		$type = array_key_exists('type', $param) ? 1 : 0;	//0:我关注的；1：关注我的
		$followM = new \src\lib\model\Followed($mid);
		$followOlist = $followM->getList($type);
		$idlist = array();
		$mkey = $type ? 'member_id' : 'follow_id'; 
		foreach ($followOlist as $follow){
			array_push($idlist, $follow[$mkey]);
		}
		return $this->membersByIds($idlist);
	}
	
	/**
	 * 加关注
	 * @param array $param
	 * 			mid: 人员ID require
	 * 			follow: 关注人的ID，require
	 * 
	 */
	public function post_follow(array $param, $posted){
		if(!isset($param['mid']) || !isset($param['follow'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$follow = $param['follow'];
		$followM = new Followed($mid);
		$idArr = explode('_',$follow);
		foreach($idArr as $followid){
			$isFollowed = $followM->isFollowed($followid);
			if(! $isFollowed){
				$followM->follow($followid, 10);	//加关注
			}
		}
		return true;
	}
	
	/**
	 * 取消关注
	 * @param array $param
	 * @return string|boolean
	 */
	public function del_follow(array $param){
		if(!isset($param['mid']) || !isset($param['follow'])){
			return 'Missing parameter';
		}
		$mid = $param['mid'];
		$qzid = $param['qzid'];
		$follow = $param['follow'];
		$followM = new Followed($mid);
		$followedGroupM = new FollowedGroup();
		$idArr = explode('_',$follow);
		foreach($idArr as $followid){
			$followRes = $followM->cancelFollow($followid, 10);
			$followedGroupM->deleteGroupFollows($qzid,$mid,$followid);
		}
		return true;
	}
	
	
	/**
	 * 获取用户同圈子下的人员通讯录
	 * @param array $param
	 * @return memberlist
	 */
	public function get_addlist(array $param){
		$mid = $param['mid'];
		$memberM = new \src\lib\model\Member($mid);
		$qzid = '';
		if(isset($param['qzid'])){
			$qzid = $param['qzid'];
		}else {
			//$groupM = new \src\lib\model\Group();
			$qzid = array();
			$qzList = $memberM->getMemberQzList($mid);
			foreach ($qzList as $qz){
				array_push($qzid, $qz['qz_id']);
			}
		}
		$num = isset($param['n']) ? $param['n'] : 20;
		return $memberM->getMyDeptMemberList($qzid, 0, $mid, $num);
	}
	
	
	/*
	 * 根据人员IDS获取人员列表
	 * @param array $ids
	 * @return multitype:
	 */
	private function membersByIds(array $ids){
		$memberM  = new \src\lib\model\Member();
		$memberList = $memberM->findByIds($ids);
		return $memberList;
	}
	
	/**
	 * post, put
	 * @param array $param
	 * @param $posted 提交的数据
	 */
	public function post_baseinfo(array $param, $posted){
		$this->resaultData['data'] = '这里是baseinfo的POST方法~~';
		$this->resaultData['posted'] = $posted;
		return $this->resaultData;
	}
	
	/**
	 * DELETE方法
	 * @param array $param
	 */
	public function del_baseinfo(array $param){
		$this->resaultData['data'] = '这里是baseinfo的DELETE方法~~';
		return $this->resaultData;
	}
	
	/**
	 * 获取一个人员加入的圈子
	 * @param array $param
	 * 		mid: 人员ID 必要参数
	 * @return list array
	 */
	public function get_circle(array $param){
		$mid = $param['mid'];
		$memberM = new Member();
		$qzids = $memberM->getMynetList(0,0,array('m.member_id=?',$mid));
		return $qzids;
	}
	
	/**
	 * 查询用户
	 * @param array $param
	 * @return string|multitype:multitype:number unknown string
	 */
	public function get_search(array $param){
		if(!isset($param['mid']) || !isset($param['qzid'])){
			return 'Missing parameter';
		}
		if(!isset($param['key'])){
			return 'No have keyword!';
		}
		$keyword = $param['key'];
		$memberM = new Member();
		$limit = 20;
		$orWhere =  array('name', 'email', 'first_letter', 'pinyin', 'short_pinyin');
		$data = $memberM->getSearchData($this->_defaultqzid, $keyword, '', $orWhere, array(0, $limit));
		$values = array();
		foreach ( $data as $d) {
			$values [] = array (
					'category' => 10,
					'value' => $d ['id'],
					'sign' => $d ['user_type'] > 1 ? $d ['nickname'] : $d ['duty'],
					'avatar' =>  $d ['avatar'] ? ImageClass::getSmall ($d['avatar']) : '/images/default_avatar_middle.png',
					'email' => $d ['email'],
					'name' => ($d ['user_type'] > 1 ? $d ['nickname'] : $d ['name'])
			);
		}
		return $values;
	}
	
}
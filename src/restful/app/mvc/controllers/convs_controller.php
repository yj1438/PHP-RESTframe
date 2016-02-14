<?php
namespace REST\app\controllers;

use REST\Controller;
use src\lib\model as Model;
use src\lib\classes\BadWords;
use src\lib\model\Feed as FeedModel;
use src\lib\model\Reply as ReplyModel;
use src\lib\model\HotDetail as HotDetailModel;
use src\lib\model\DealContent as DealContentModel;
use src\lib\model\Conversation as ConversationModel;

/**
 * 会话
 * @author yinjie
 */
class Convs_Controller extends Controller{
	
	private $perpage = 5;	//默认每次加载多少个会话
	
	
	/*
	 * 初始化方法
	 */
	public function init(array &$param){
		parent::init($param);
	}
	
	/**
	 * 获取人员在某一圈子下的所有会话
	 * @method GET
	 * @param array $param
	 * 		mid: 人员id
	 * 		qzid: 圈子id
	 * @return multitype:
	 */
	public function get_list(array $param){
		$mid = $param['mid'];
		$qzid = $param['qzid'];
		if(!$mid || !$qzid){
			return 'error: 缺少必要参数！';
		}
		//取非必要参数
		$filter = isset($param['for']) ? $param['for'] : 'all';				//查询（范围）类型
		$fid = isset($param['fid']) ? $param['fid'] : 0;					//续点会话的id: 此ID为分页续点，
																			//0：从最新开始取offset个；
																			//非0：从此ID继续往下取会话；
																		
		$created = isset($param['created']) ? $param['created'] : '';		//会话更新时间的止点
		$type = isset($param['type']) ? $param['type'] : 0;					//会话类型（ID）
		$feedkey = isset($param['feedkey']) ? $param['feedkey'] : '';		//会话类型名称
		$this->perpage = isset($param['per']) ? $param['per'] : 5;
		
		//取圈子的基本信息
		$baseM = new \src\lib\model\Base();
		$qzBaseInfo = $baseM->getBaseInfo(array('id = ?' => $qzid));
		
		
		$convsM = new \src\lib\model\Conversation($qzid, $mid, $qzBaseInfo);
		$feedlist = array();
		//针对不同filter的查询方法
		$data = $topList = array();
		switch($filter){
			case 'all': // 全部会话 all
				$feedlist = $convsM->all($fid, $this->perpage, true, $created, $type, $feedkey);
				break;
			case 'att': // 我关注的会话 att
				$feedlist = $convsM->att($fid, $this->perpage, true, $created, $type, $feedkey);
				break;
			case 'me': // 我发布的会话
				$feedlist = $convsM->me(1, $fid, $this->perpage, $created, $type, $feedkey);
				break;
			case 'like': // 我喜欢的会话
				$feedlist = $convsM->like($fid, $this->perpage, $created, $type, $feedkey);
				break;
			case 'favorite': // 我收藏的会话
				$feedlist = $convsM->favorite($fid, $this->perpage, $created, $type, $feedkey);
				break;
			case 'total': //会部动态
				//$startTime = microtime(true);
				if(empty($created) && empty($type)){
					$data = $convsM->getFeedFirstStatic(1, $this->perpage);
				}
				if (!$data){ // all
					$data = $convsM->totalall($fid , $this->perpage, true, $created, $type, $feedkey);
					if (empty($created)) {
						$fs = new \src\lib\model\FeedStatic();
						$fs->addf($qzid, $mid, 1, $data);
					}
				}
				$feedlist = $data[0];
				$feedFold = $data[1];
				if(empty($created)){
					$topList = $convsM->getFeedTopList(0);
				}
				//======================
				foreach ($topList as &$value){
					$value['avatar'] = '';
				}
				//======================
				
				break;
			case 'totalMe': // 我的动态
				if(!$mid){
					//$startTime = microtime(true);
					if(empty($created) && empty($feedkey) && empty($type)){
						$data = $convsM->getFeedFirstStatic(2, $this->perpage);
					}
					if (!$data)	{
						$data = $convsM->totalme(1, $fid, $this->perpage, $created, $type, $feedkey);
						if(empty($created) && empty($feedkey) && empty($type)){
							$fs = new \src\lib\model\FeedStatic();
							$fs->addf($qzid, $mid, 2, $data);
						}
					}
				}else{
					$data = $convsM->ta($mid, 1, $fid, $this->perpage, $created, $type, $feedkey);
				}
				$feedlist = $data[0];
				$feedFold = $data[1];
				break;
			case 'totalAtt': // 我关注的动态 att
				if(empty($created) && empty($feedkey) && empty($type)){
					$data = $convsM->getFeedFirstStatic(3, $this->perpage);
				}
				if (!$data)	{
					$data = $convsM->totalatt($fid, $this->perpage, 1, $created, $type, $feedkey);
					if(empty($created) && empty($feedkey) && empty($type)){
						$fs = new \src\lib\model\FeedStatic();
						$fs->addf($qzid,$mid, 3, $data);
					}
				}
				$feedlist = $data[0];
				$feedFold = $data[1];
				break;
			case 'totalPrm': // 我创建的工作日志	
				$feedlist = $convsM->prm($fid, $this->perpage, $created);
				break;
			case 'totalPrmAt': // @我的工作日志
				$feedlist = $convsM->prmat($fid, $this->perpage, $created);
				break;
			case 'atme': // @我的
				$feedlist = $convsM->atme($fid, $this->perpage, $created);
				break;
		}
		
		/*
		 * 相对路径转换
		 * 可以作为一个公共方法
		 */
		foreach ($feedlist as $key => &$value){
			if(isset($value['avatar'])){
// 				$value['avatar'] = str_replace('/download/', $this->_esnUrl.'/download/', $value['avatar']);
				$value['avatar'] = $this->_esnUrl .'/download/default_avatar.middle.png';
			}
			if (isset($value['reply'])){
				foreach ($value['reply'] as $rk => &$rv ){
					$rv['avatar'] = str_replace('/download/', $this->_esnUrl.'/download/', $rv['avatar']);
				}
			}
		}
		return $feedlist;
	}
	
	/**
	 * 删除会话
	 * @param $param：
	 * 			mid: 人员id
	 * 			qzid: 圈子id
	 * 			id: 会话id(feed-id)
	 * @return parent::$_result
	 */
	public function del_info($param){
		$mid = $param['mid'];
		$qzid = $param['qzid'];
		$id = $param['id'];
		if(!$mid || !$qzid || !$id){
			return 'error: 缺少必要参数！';
		}
		//检测此会话是否存在
		$feedM = new \src\lib\model\Feed($qzid);
		$feedInfo = $feedM->getFeedInfo(array('id=?'=>$id));
		if(!$feedInfo){
			return $this->returnFn(false, '未找到数据');
		}
		
		//根据不同的会话类型做不同的处理
		switch ($feedInfo['type']){
			case FeedModel::OBJECT_TYPE_SPEECH :
				$rs = $feedM->_speechDrop($feedInfo);
				break;
			case FeedModel::OBJECT_TYPE_SCHEDULE:
				//获取日程的详细信息
				$sid = $feedInfo ['feed_id'];
				$scheduleM = new Model\Schedule ( $feedInfo ['qz_id'], $sid );
				$scheduleInfo = $scheduleM->getScheduleListByIds ( array ($sid) );
				if (! empty ( $scheduleInfo [$sid] ) && $scheduleInfo [$sid] ['endtime'] > time ()) {
					// 获取日程参与人
					$scheduleUserM = new Model\ScheduleUsers ( $sid );
					$where = array (
							'role=?' => 0,
							'status<?' => 2
					);
					$userList = $scheduleUserM->getUserList ( $where );
				}
				$rs = $feedM->_scheduleDrop ( $feedInfo );
				// 发送日程删除通知
				if ($rs && ! empty ( $userList ) && is_array ( $userList )) {
					$noticeM = new Model\Notice ( $qzid, $mid );
					$note = serialize ( $scheduleInfo [$sid] );
					foreach ( $userList as $value ) {
						if (! empty ( $value ['member_id'] )) {
							$noticeM->scheduleAdd ( $value ['member_id'], $note, $sid, 'schedule_delete' );
						}
					}
				}
				if ($id) {
					return $this->returnFn($rs, '删除失败：日程尚未过期不能删除');
				} else {
					return $this->returnFn($rs, '删除失败：日程已删除');
				}
				break;
			case FeedModel::OBJECT_TYPE_CALENDAR:
				break;
			case FeedModel::OBJECT_TYPE_FILES:
				break;
			case FeedModel::OBJECT_TYPE_GROUP:
				break;
			case FeedModel::OBJECT_TYPE_DAILY:
				$rs = $feedM->_dailyDrop($feedInfo);
				break;
			case FeedModel::OBJECT_TYPE_VOTE:
				$rs = $feedM->_voteDrop($feedInfo);
				break;
			case FeedModel::OBJECT_TYPE_TASK:
				$rs = $feedM->_taskDrop($feedInfo);
				if ($id) {
					return $this->returnFn($rs, '删除失败：任务处于发布状态或无删除权限');
				}else {
					return $this->returnFn(false, '删除失败：任务已删除');
				}
				break;
			case FeedModel::OBJECT_TYPE_REPLY:
				$rs = $feedM->_replyDrop($feedInfo);
				break;
			case FeedModel::OBJECT_TYPE_EVENT:
				$rs = $feedM->_eventDrop($feedInfo);
				break;
			default:
				break;
		}
		if ($rs) {
			return $this->returnFn($rs, '删除成功');
		}else {
			return $this->returnFn(false, '删除失败');
		}
	}
	
	/**
	 * 会话回复
	 * @param array $param
	 * @param unknown $posted
	 */
	public function post_reply (array $param, $posted){
		$qz_id = $param['qzid'];
		$memberId = $param['mid'];
		if(!$memberId || !$qz_id ){
			return 'error: 缺少必要参数！';
		}
// 		$postedArr = json_decode($posted, true);
// 		if(!$postedArr){
// 			return 'error: 非法参数！';
// 		}		
		//对回复进行回复
		/**
		feed_id:125
		id:53
		feed_type:15							//类型，固定
		feed_reply_to_id:67						//回复的ID
		feed_reply_to_mid:5						//回复对象的ID
		feed_reply_to_name:yinjie				//回复对象的名称
		feed_reply_content:妈的啊~~~~~~
		feed_reply_obj_mid:5					//回复对象的ID
		feed_reply_fileids:
		atperson:
		 */
		$from_id = $_POST['id'];
		$feed_id = $_POST['feed_id'];
		$reply_type = $_POST['feed_type'];
		$content = $_POST['feed_reply_content'];
		$rid = $_POST['feed_reply_to_id'];
		$to_mid = $_POST['feed_reply_to_mid'];
		$obj_mid = $_POST['feed_reply_obj_mid'];
		$reply_fields = $_POST['feed_reply_fileids'];
		$atperson = $_POST['atperson'];
		/* 
		$from_id = !empty($this->_request->feed_id) ? intval($this->_request->feed_id) : 0;	//对象ID
		$qzfeed_id = !empty($this->_request->id) ? intval($this->_request->id) : 0;	//会话ID
		$reply_type = !empty($this->_request->feed_type) ? intval($this->_request->feed_type) : FeedModel::OBJECT_TYPE_SPEECH;	//对象类型
		$content = addslashes($this->_request->feed_reply_content);	//回复内容
		$rid = $this->_request->feed_reply_to_id;	//父回复ID 
		*/
		$data = array();
		
		//判断当前会话是否属于本圈子
		if($feed_id) {
			$convModel = new ConversationModel($qz_id, $memberId);
			$feedInfo = $convModel->getFeedInfo(array('qz_id =?' => $qz_id, 'id =?' => $feed_id));
			if(!$feedInfo){
				return 'error: 当前圈子未找到对应数据！';
			}
		}
		/* if( isset($_COOKIE[md5($from_id."_".$content)]) ){
			$this->_ajaxRs(false, array(), '请稍后再进行回复', 1);
		} else{
			setcookie(md5($from_id."_".$content), md5($memberId), time()+120, '/');
		} */
		// 过滤敏感词
		$content = BadWords::replaceBadword($content);
		$r = new ReplyModel();
		if($rid){
			$data['parent_replyid']  = $rid;//父回复ID
			$data['parent_replymid'] = $to_mid;	//父回复人ID
		}
		
		// 热度添加
		$hotDetailM = new HotDetailModel();
		
		if($to_mid){
			$hotDetailM->addConversationHot($memberId,array(), array($to_mid), $qz_id, $feed_id, $hotDetailM::HOT_CON_REPLY);
		}else{
			$hotDetailM->addConversationHot($memberId,array(), array($obj_mid), $qz_id, $feed_id, $hotDetailM::HOT_CON_REPLY);
		}
		
		$atperson = $atperson; // @的人，需要给他们发通知
		$atperson = $atperson ? explode(',', $atperson) : '';
		$atperson = is_array($atperson) ? $atperson : array();
		//对内容处理，包括网址短链接、邮箱、@、#
		list($topic, $atmember, $content) = DealContentModel::d($qz_id, $memberId, $this->_baseUrl, $content);
		$atmember = is_array($atmember) ? $atmember : array();
		if (isset($ccnotices)) {
			$ccnotices = array_unique(array_merge($ccnotices, $atperson));
		} else {
			$ccnotices = array_unique(array_merge($atperson, $atmember));
		}
		$data['reply_id']   = $from_id;
		$data['reply_type'] = $reply_type;
		$data['member_id']  = $memberId;
		$data['created']    = time();
		$data['content']    = $content;
		$data['reply_obj_owner'] = $obj_mid;
		$rs = $r->addReply($qz_id, $memberId, $data, $reply_fields, $this->_baseUrl, $feed_id, $ccnotices);
		$insertId = $rs ['replyid'];
		$files = $rs ['files'];
		
		//取回复用户的信息
		$userinfo = $this->getMember($memberId);
		$data = array (
				'id' => $insertId,
				'user_id' => $memberId, // 回复人的ID
				'user_name' => $userinfo['name'], // 回复人姓名
				'avatar_url' => $userinfo['avatar'], // 回复人头像 AjaxImg::IMAGE_SIZE_SMALL
				'content' => $content, // 回复内容
				'userspace_url' => $this->_baseUrl . '/space/cons/index',  // 回复人主页地址
				'reply_to_mid' => $_POST['feed_reply_to_mid'],  //被回复人的id
				'reply_to_name' => $_POST['feed_reply_to_name'] //被回复人的名字		
		);
		if ($files) {
			$data ['files'] = $files;
		}
		return $data;
	}
	
	/**
	 * 上传base64编码的图片
	 * @param array $param
	 * @param unknown $posted 上传的编码
	 * @return string|number
	 */
	public function post_image(array $param, $posted){
		$qz_id = $param['qzid'];
		$memberId = $param['mid'];
		if(!$memberId || !$qz_id ){
			return 'error: 缺少必要参数！';
		}
		$imgData = $posted;
		$s = base64_decode(str_replace('data:image/png;base64,', '', $imgData));
		try {
			$filename = microtime(true)*10000;
			//$filename = $this->guid();
			file_put_contents( DIR_ROOT .'/web/uploadfile/' . $filename . '.jpg', $s);
			return $filename;
		} catch (Exception $e) {
			return 0;
		}
		
	}
	
	/*
	 * 生成32位UUID
	 */
	private function guid(){
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}else{
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
			return $uuid;
		}
	}
	
}
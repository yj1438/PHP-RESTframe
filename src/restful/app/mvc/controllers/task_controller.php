<?php


namespace REST\app\controllers;

use REST\Controller;
use src\lib\model\Task;
use src\lib\model\TaskFeed;
use src\lib\model\TaskUser;
use src\lib\model\Followed;
use src\lib\model\Feed;
use src\lib\model\HotDetail;
/**
 * 任务
 * @author yinjie
 *
 */
class Task_Controller extends Controller{
	
	public function init(array &$param){
		parent::init($param);
	}
	
	/**
	 * 
	 * @param unknown $param
	 * @param array $posted:
	 * 		$defaults = array(
	 *			'title'=>'',			//标题：必要参数
				'manageusers'=>array(),	//负责人ID 必要参数
				'joinusers'=>array(),	//参与人ID 必要参数
				'noticeusers'=>array(),	//知会人ID 必要参数
				
				//非必要参数
				'content'=>'',			//任务说明
				'important'=>0,		//重要程度  0：一般 1：重要
				'fastcomplete'=>0,	//是否尽快完成 
					//如果不是尽快完成：====start
				'startdate'=>'',	//
				'starthours'=>'',
				'enddate'=>'',
				'endhours'=>'',
					//如果不是尽快完成：====end
					
			);
	 * @return string
	 */
	public function post_detail($param, $posted){
		$mid = $param['mid'];
		$qzid = $param['qzid'];
		
		$postedArr = json_decode($posted, true);
		//$posted不是json串
		if(!$postedArr){
			return 'error: 非法参数！';
		}
		/*
		 * 主要参数处理
		 */
		// 任务开始时间
		if (!empty($postedArr['startdate'] )) {
			$starttime = Task::getDefaultTaskBeginTime ();
		} else {
			$starttime = Task::formatTaskDateTime ($postedArr['startdate'], $postedArr['starthours'] );
		}
		$endtime = Task::formatTaskDateTime ( $postedArr['enddate'], $postedArr['endhours'] );
		//
		$fastcomplete = $postedArr['fastcomplete'];
		if (!$fastcomplete && $endtime <= $starttime) {
			return "结束时间必须大于开始时间";
		}
		$manageUserArr = $postedArr['manageusers'];
		$joinUserArr = $postedArr['joinusers'];
		$noticeUserArr = $postedArr['noticeusers'];
		
		//主要参数
		$data = array (
				'title' => $postedArr['title'],			//标题：必要参数
				//'manageusers'=>$postedArr['manageusers'],	//负责人ID 必要参数
				//'joinusers'=>$postedArr['joinusers'],	//参与人ID 必要参数
				//'noticeusers'=>$postedArr['noticeusers'],	//知会人ID 必要参数
					
				//非必要参数
				'content' => $postedArr['content'],		//任务说明
				'important' => $postedArr['important'],		//重要程度  0：一般 1：重要
				'fastcomplete' => $postedArr['fastcomplete'],	//是否尽快完成
				
				'member_id' => $mid,
				'qz_id' => $qzid,
				'starttime' => $starttime,	//任务开始时间
				'endtime' => $endtime,		//任务结束时间
				'fastcomplete' => 0,
				'remindtype' => 3,
				'uppertime' => 15,
				'unit_type' => 0,	//0:分钟 1：小时
				'important' => 0,
				'tasksumup' => '',
				'noticetype' => 3,
				'created' => time (),
				'taskstatus' => 0,
				'delay' => 0,
				'type' => 1,
				'type_id' => 1,
				'group_id' => 0,
				'isdeleted' => 0,
				'submittime' => 0,
				'closetime' => 0,
				'completetime' => 0,
				'is_public' => 0,
				'is_draft' => 0,
				'relation_depth' => '',
				'relation_floor' => 0,
				'parent_task_id' => 0,
				'alarm_repetition' => '',
		);
		//
		$taskM = new Task( $qzid, 0, $mid, \Config::get('application.hostname') );
		$rs = $taskM->addTask($data, $mid, $manageUserArr, $joinUserArr, $noticeUserArr, array(), 0, $qzid);
		//
		if($rs){
			// 添加任务相应动态，任务私有动态
			$taskFeedM = new TaskFeed();
			$feedType = TaskFeed::TASK_FEED_ADD;
			$taskFeedM->addFeed ( $qzid, $mid, $rs ['tid'], $feedType );
			// 任务人员自动关注任务
			$followM = new Followed();
			$followUser = array (
				$mid
			);
			$followUser = array_merge ( $followUser, $manageUserArr, $joinUserArr, $noticeUserArr );
			$followUser = array_unique ( $followUser );
			foreach ( $followUser as $followUserId ) {
				$followM->setMemberid ( $followUserId );
				$isFollowed = $followM->isFollowed ( $rs ['tid'], Feed::OBJECT_TYPE_TASK );
				if (! $isFollowed) {
					$followM->follow ( $rs ['tid'], Feed::OBJECT_TYPE_TASK );
				}
			}
			// 加入提醒队列
			$taskM->taskNoticeQueue ( $rs ['tid'], $qzid, 3, 3, array (
					$starttime,
					$endtime
			), 15, 0 );
		}
		
		// 统计用户的热度
		$hotM = new HotDetail ();
		$hotM->addTaskHot ( $mid, $manageUserArr, $joinUserArr, $qzid, $rs ['tid'] );
		
		//return 生成的任务ID
		return $rs['tid'];
	}
	
	/**
	 * 获取一条任务详情
	 * @param string $param
	 * @return array(
	 * 			...
	 * 			...
	 * 			...
	 * 
	 * 			creater=>创建人
	 * 			manager=>负责人
	 * 			joiners=>参加人
	 * 			noticers=>知会人
	 * 				
	 * 			)
	 */
	public function get_detail($param){
		if(!isset($param['id']) || !isset($param['mid'])){
			return 'param error';
		}
		$taskid = $param['id'];
		$mid = $param['mid'];
		$taskM = new Task();
		$rs = $taskM->getTaskSigleInfo($taskid);
		if($rs){
			$taskUserM = new TaskUser();
			$users = $taskUserM->getTaskAllUsers($taskid);
			if($users){
				$rs['creater'] = $users['create'];
				$rs['manager'] = $users['response'];
				$rs['joiners'] = $users['join'];
				$rs['noticers'] = $users['notice'];
			}
		}
		return $rs;
	}
	
	
	/**
	 * 获取人员的所有任务
	 * @param array $param
	 * @return array taskList
	 */
	public function get_list($param){
		$mid = $param['mid'];
		$qzid = isset($param['qzid']) ? $param['qzid'] : 0;
		$taskM = new Task($qzid, 0, $mid);
		$rs = $taskM->TaskAll();
		return $rs;
	}
	
	/**
	 * 删除若干条任务
	 * @param :
	 * 		mid: 人员ID
	 * 		qzid: 圈子ID
	 * 		ids: 要删除的任务IDS
	 * @return 成功删除的记录数
	 */
	public function del_detail($param){
		if(!isset($param['mid']) || !isset($param['ids'])){
			return 'param error';
		}
		$mid = $param['mid'];
		$qzid = isset($param['qzid']) ? $param['qzid'] : $param['qzid'];
		$ids = $param['ids'];
		$idArr = explode('_', $ids);
		$taskM = new Task($qzid, 0, $mid);
		$rs = $taskM->deleteTask($idArr);
		return $rs;
	}
	
}
<?php
namespace REST\app\controllers;

/**
 * 默认类
 * @author yinjie
 *
 */
class Index_Controller{
	
	/**
	 * 初始化方法
	 */
	public function init(){
		
	}
	
	/**
	 * 默认方法
	 * @param unknown $param
	 * @return string
	 */
	public function get_index($param){
		return 'Welcome To ESN WebService';
	}
}
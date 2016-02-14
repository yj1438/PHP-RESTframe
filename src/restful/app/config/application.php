<?php

return array(
    /* 基本信息，放在一级下 */

    'hostname'           => '',
	
	//URL上下文根
    'base_url'           => '/service',

	//esnurl默认为当前HOST，可以自己定义
	'esnurl'			 => '',
		
    // 时区
    'timezone'           => 'Asia/Shanghai',

    // 默认控制器名称
    'default_controller' => 'index',

    // 默认控制器方法名称
    'default_action'     => 'index',

    // 是否开启错误报告，生产环境中请设置为false
    'error_reporting'    => true,

    // 是否加载调试工具
    'debug'              => true,

    // 调试工具（dbug 或 kint）， 是否再包括firephp
    'debug_tool'         => 'dbug',

    // 是否开启php-o对象操纵功能
    'php-o'              => true,

    // 加密KEY
    'key'                => 'esnws520#',

	//放置KEY文件的路径，相对restful项目文件夹
	'keyfilepath'		 => 'keys',
		
    // 这是用来测试的

);

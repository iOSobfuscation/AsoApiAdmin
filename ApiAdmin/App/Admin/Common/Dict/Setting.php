<?php
return array(
	/* 前台设置  */
	'SITE_TITLE' => array(
		'name'    => '站点标题',
		'group'   => '前台设置',
		'editor'  => 'text',
		'default' => '',
	),
	'SITE_KEYWORDS' => array(
		'name'    => '关键字',
		'group'   => '前台设置',
		'editor'  => 'text',
		'default' => '',
	),
	'SITE_DESCRIPTION' => array(
		'name'    => '描述',
		'group'   => '前台设置',
		'editor'  => 'textarea',
		'default' => '',
	),
	'SITE_ICP' => array(
		'name'    => '备案号',
		'group'   => '前台设置',
		'editor'  => 'text',
		'default' => '',
	),
	
	/* 后台设置  */
	'SAVE_LOG_OPEN' => array(
		'name'    => '开启后台日志记录',
		'group'   => '后台设置',
		'editor'  => array('type'=>'checkbox','options'=>array('on'=>'开启','off'=>'关闭')),
		'default' => C('SAVE_LOG_OPEN') ? '开启' : '关闭',
	),
	'MAX_LOGIN_TIMES' => array(
		'name'    => '登录失败后允许最大次数',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('MAX_LOGIN_TIMES'),
	),
	'LOGIN_WAIT_TIME' => array(
		'name'    => '错误等待时间(分钟)',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('LOGIN_WAIT_TIME'),
	),
	'DATAGRID_PAGE_SIZE' => array(
		'name'    => '列表默认分页数',
		'group'   => '后台设置',
		'editor'  => 'numberbox',
		'default' => C('DATAGRID_PAGE_SIZE'),
	),
	
	/* 上传设置  */
	'FILE_UPLOAD_CONFIG.exts' => array(
		'name'    => '允许上传扩展(全局)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_CONFIG.exts.exts'),
	),
	'FILE_UPLOAD_CONFIG.maxSize' => array(
		'name'    => '允许上传大小(全局)',
		'group'   => '上传设置',
		'editor'  => 'numberbox',
		'default' => C('FILE_UPLOAD_CONFIG.maxSize'),
	),
	'FILE_UPLOAD_LINK_CONFIG.exts' => array(
		'name'    => '允许上传扩展(附件)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_LINK_CONFIG.exts'),
	),
	'FILE_UPLOAD_IMG_CONFIG.exts' => array(
		'name'    => '允许上传扩展(图片)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_IMG_CONFIG.exts'),
	),
	'FILE_UPLOAD_FLASH_CONFIG.exts' => array(
		'name'    => '允许上传扩展(动画)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_FLASH_CONFIG.exts'),
	),
	'FILE_UPLOAD_MEDIA_CONFIG.exts' => array(
		'name'    => '允许上传扩展(媒体)',
		'group'   => '上传设置',
		'editor'  => 'text',
		'default' => C('FILE_UPLOAD_MEDIA_CONFIG.exts'),
	),
	
	/* 邮箱设置  */
	'EMAIL_SMTP' => array(
		'name'    => 'SMTP',
		'group'   => '邮箱设置',
		'editor'  => 'text',
		'default' => '',
	),
	'EMAIL_PORT' => array(
		'name'    => '端口',
		'group'   => '邮箱设置',
		'editor'  => 'numberbox',
		'default' => '25',
	),
	'EMAIL_EMAIL' => array(
		'name'    => '邮箱地址',
		'group'   => '邮箱设置',
		'editor'  => 'text',
		'default' => '',
	),
	'EMAIL_USER' => array(
		'name'    => '用户名',
		'group'   => '邮箱设置',
		'editor'  => 'text',
		'default' => '',
	),
	'EMAIL_PWD' => array(
		'name'    => '密码',
		'group'   => '邮箱设置',
		'editor'  => 'text',
		'default' => '',
	),
	
	/* 飞信设置  */
	'FETION_USER' => array(
		'name'    => '用户名',
		'group'   => '飞信设置',
		'editor'  => 'text',
		'default' => '',
	),
	'FETION_PWD' => array(
		'name'    => '密码',
		'group'   => '飞信设置',
		'editor'  => 'text',
		'default' => '',
	),
	
	/* 登录接口设置  */
	'THINK_SDK_SINA.APP_KEY' => array(
		'name'    => 'sina APP ID',
		'group'   => '登录接口设置',
		'editor'  => 'text',
		'default' => '',
	),
	'THINK_SDK_SINA.APP_SECRET' => array(
		'name'    => 'sina KEY',
		'group'   => '登录接口设置',
		'editor'  => 'text',
		'default' => '',
	),

	'THINK_SDK_BAIDU.APP_KEY' => array(
		'name'    => 'baidu APP ID',
		'group'   => '登录接口设置',
		'editor'  => 'text',
		'default' => '',
	),
	'THINK_SDK_BAIDU.APP_SECRET' => array(
		'name'    => 'baidu KEY',
		'group'   => '登录接口设置',
		'editor'  => 'text',
		'default' => '',
	),
);
<?php
defined('THINK_PATH') or exit();
defined('OAUTH_URL_CALLBACK') or define('OAUTH_URL_CALLBACK', SITE_URL . '/oauth/callback?type=');

return array(
	'SHOW_PAGE_TRACE'  => false,

	/* 模板引擎设置 */
//	'TMPL_ACTION_ERROR'     => MODULE_PATH.'View'.DS.'Common'.DS.'dispatch_jump.html',   // 默认错误跳转对应的模板文件
//	'TMPL_ACTION_SUCCESS'   => MODULE_PATH.'View'.DS.'Common'.DS.'dispatch_jump.html',   // 默认成功跳转对应的模板文件
//	'TMPL_EXCEPTION_FILE'   => MODULE_PATH.'View'.DS.'Common'.DS.'exception.html',       // 异常页面的模板文件

	/* 路由设置 */
	'URL_ROUTER_ON'    => true,
	'URL_ROUTE_RULES'  => array(
		'demo' => array('Wap/demo'),
	),


	//新浪微博配置
	'THINK_SDK_SINA' => array(
		'APP_KEY'    => '607110900', //应用注册成功后分配的 APP ID
		'APP_SECRET' => 'bc3befc04c6f8b6b12369721286c033c', //应用注册成功后分配的KEY
		'CALLBACK'   => OAUTH_URL_CALLBACK . 'sina',
	),
	//百度配置
	'THINK_SDK_BAIDU' => array(
		'APP_KEY'    => 'zlTwAaHWYfBrQGoI5Zjl58tZ', //应用注册成功后分配的 APP ID
		'APP_SECRET' => 'jXEc8IKtNWZnCC7zx6gjhUdrU4fOyVUt', //应用注册成功后分配的KEY
		'CALLBACK'   => OAUTH_URL_CALLBACK . 'baidu',
	),
	//腾讯QQ登录配置
	'THINK_SDK_QQ' => array(
		'APP_KEY'    => '101077053', //应用注册成功后分配的 APP ID
		'APP_SECRET' => '04c411f69bd582489b75b96267de6ce3', //应用注册成功后分配的KEY
		'CALLBACK'   => OAUTH_URL_CALLBACK . 'qq',
	),
);
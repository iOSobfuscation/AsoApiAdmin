<?php
namespace Plant\Controller;
use Think\Controller;

/**
 * 公共控制器
 */
class CommonController extends Controller {
	/**
	 * 相当于构造函数，初始化一些公共内容
	 * TODO 这里面建议使用self调用，防止子类中出现重名函数
	 */
	public function _initialize(){
		//self::initNavbar();  //初始化导航，这里使用S缓存
		//self::get_openid(); //获取openid
		$verssion		= time();
		$this->assign('verssion', $verssion);
	}
	
	
	
}

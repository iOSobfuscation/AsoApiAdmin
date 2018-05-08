<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\AdminMenuModel;
use Admin\Model\CommonModel;
/**
 * 后台管理通用模块
 * @author ditser
 */
class IndexController extends CommonController {
	
	private $CommonModel;
	/*初始化 实例化model*/
	
    /**
	 * 后台首页
	 */
	 
	public function index(){

		$menu           = new AdminMenuModel();
		$common         = new CommonModel();
	    $admin_db		= D('Admin');
	    $menu_db		= D('Menu');
	    
		$site_info		= site_info(1);
		
		$menu_list      = $menu->menu_list_data();
		$ExamData       = $common->get_data('exam_cat','','is_del=0','','exam_cat_id,exam_cat_name');
	    $userid = session('userid');
		$userInfo = $admin_db->getUserInfo($userid);    //获取用户基本信息
		$menuList = $menu_db->getMenu();                //头部菜单列表
		if($_SESSION['userid']!=30){
			unset($menu_list[0]);
		}
		
		$this->assign('ExamData',$ExamData);
		$this->assign('menu_list',$menu_list);
		$this->assign('userInfo', $userInfo);
		$this->assign('site_info', $site_info);
		$this->assign('menuList', $menuList);
		$this->assign('user_menu_list', user_menu_list());
		$this->display();
	}
	
	public function sysinfo(){
		
		//统计会员数量
		$member_sum		= member_sum();
		
		//统计订单数量
		$order_sum		= order_sum();
		
		//统计收入总额
		$sales_sum		= sales_sum();
		
		$this->assign('sales_sum', $sales_sum);
		$this->assign('order_sum', $order_sum);
		$this->assign('member_sum', $member_sum);
		$this->display();
	}
	
	/**
	 * 用户登录
	 */
    public function login(){
    	$admin_db = D('Admin');
  //   	$username = I('post.username', '', 'trim');
		// $LoginStatus = $admin_db->where("username = '{$username}'")->getField('status');

		// if($LoginStatus == 1){
		// 	$this->error('该账户已处于登录状态！');
		// 	die;
		// }
    	if (I('get.dosubmit')){
           	$username = I('post.username', '', 'trim') ? I('post.username', '', 'trim') : $this->error('用户名不能为空', HTTP_REFERER);
		   $password = I('post.password', '', 'trim') ? I('post.password', '', 'trim') : $this->error('密码不能为空', HTTP_REFERER);
			if($admin_db->login($username, $password)){
				
				//定义成功跳转URL
				$surl			= "index";	
				//写日志
				$uid					    = get_field('admin', 'ae_', 'userid', "username='$username'");
				$now_time					= date('Y-m-d H:i:s', time());
				$op_desc					= "管理人员：".$username."于".$now_time."登录了后台系统";
				
				loger_add('Index', "登录",$uid,$op_desc);
			   
				//记录session
				$roleid                     = get_field('admin', 'online_', 'roleid', "username='$username'"); 
				$rolename                   = get_field('admin_role', 'online_', 'rolename', "id='$roleid'");
				$thumb                      = get_field('admin', 'ae_', 'thumb', "username='$username'");
				//设置登录状态
				$data['status']  = 1;
				$admin_db->where("userid = $uid")->save($data);
				session('thumb',$thumb);
				session('username',$username);
				session('LoginTime',time());
				session('loginIp',$_SERVER['REMOTE_ADDR']);
				session('rolename',$rolename);
				 $this->success('登录成功', $surl);
			}else{
			    $this->error($admin_db->error, HTTP_REFERER);
			}
    	}else {
			//取出LOGO等
			$site_info		= site_info(1);
			$this->assign('site_info', $site_info);
    		$this->display();
    	}
    }
    
    /**
	 * 退出登录
	 */
    public function logout() {
    	
		session('userid', null);

		session('roleid', null);
		cookie('username', null);
		cookie('userid', null);
		
		$this->success('安全退出！', U('Index/login'));
	}
    
    /**
	 * 验证码
	 */
	public function code(){
        $verify = new \Think\Verify();
        $verify->useCurve = true;
        $verify->useNoise = false;
        $verify->bg = array(255, 255, 255);
        
		if (I('get.code_len')) $verify->length = intval(I('get.code_len'));
		if ($verify->length > 8 || $verify->length < 2) $verify->length = 4;
		
		if (I('get.font_size')) $verify->fontSize = intval(I('get.font_size'));
		
		if (I('get.width')) $verify->imageW = intval(I('get.width'));
		if ($verify->imageW <= 0) $verify->imageW = 130;
		
		if (I('get.height')) $verify->imageH = intval(I('get.height'));
		if ($verify->imageH <= 0) $verify->imageH = 50;

        $verify->entry('admin');
	}
	
    /**
     * 左侧菜单
     */
	public function public_menuLeft($menuid = 0) {
	    $menu_db = D('Menu');
		$datas = array();
		$list = $menu_db->getMenu($menuid);
		foreach ($list as $k=>$v){
			$datas[$k]['name'] = $v['name'];
			$son_datas = $menu_db->getMenu($v['id']);
			foreach ($son_datas as $k2=>$v2){
				$datas[$k]['son'][$k2]['text'] = $v2['name'];
				$datas[$k]['son'][$k2]['id']   = $v2['id'];
				$datas[$k]['son'][$k2]['url'] = U($v2['c'].'/'.$v2['a'].'?menuid='.$v2['id'].'&'.$v2['data']);
			}
		}
		$this->ajaxReturn($datas);
	}
	
	/**
	 * 后台欢迎页
	 */
	public function public_main(){
	    $admin_db      = D('Admin');
	    $userid = session('userid');
		$userInfo = $admin_db->getUserInfo($userid);    //获取用户基本信息
		
	    $sysinfo = \Admin\Plugin\SysinfoPlugin::getinfo();
		$os = explode(' ', php_uname());
		//网络使用状况
		$net_state = null;
		if ($sysinfo['sysReShow'] == 'show' && false !== ($strs = @file("/proc/net/dev"))){
			for ($i = 2; $i < count($strs); $i++ ){
				preg_match_all( "/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/", $strs[$i], $info );
				$net_state.="{$info[1][0]} : 已接收 : <font color=\"#CC0000\"><span id=\"NetInput{$i}\">" . $sysinfo['NetInput'.$i] . "</span></font> GB &nbsp;&nbsp;&nbsp;&nbsp;已发送 : <font color=\"#CC0000\"><span id=\"NetOut{$i}\">" . $sysinfo['NetOut'.$i] . "</span></font> GB <br />";
			}
		}

		$changFile = SITE_DIR . DS . 'change.log';
		$changeList    = array();
		if(file_exists($changFile)){
			$changeList = file($changFile);
		}
		$this->assign('changeList', $changeList);
		
		$this->assign('userInfo', $userInfo);
		$this->assign('sysinfo',$sysinfo);
		$this->assign('os',$os);
		$this->assign('net_state',$net_state);
		$this->display('main');
	}
	
	/**
	 * 更新后台缓存
	 */
	public function public_clearCatche(){
	    $list = dict('', 'Cache');
		if(is_array($list) && !empty($list)){
			foreach ($list as $modelName=>$funcName){
				D($modelName)->$funcName();
			}
		}
		$this->success('操作成功');
	}
	
    /**
     * 防止登录超时
     */
	public function public_sessionLife(){
		$userid = session('userid');
	}
}
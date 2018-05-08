<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 公共控制器
 * @author ditser
 * 
 * TODO
 * 后缀带_iframe的ACTION是在iframe中加载的，用于统一返回格式
 */
class CommonController extends Controller {
	/*初始化 判断是否有该权限*/
	public function _initialize(){
		
    	if(IS_AJAX && IS_GET) C('DEFAULT_AJAX_RETURN', 'html');
    	self::check_admin();
		self::check_priv();
		self::manage_log();
		$session_id     = intval($_SESSION['userid']);
		

		
			// if($session_id !=30){
			// 	$AllowAction    = array('menu_list','submit_error','app_list','channel_list','task_info');

			// 	if(!in_array(ACTION_NAME,$AllowAction)){
			// 		echo "<script>alert('没有此权限')</script>";
			// 		die;
			// 	}
			//}
		
		//self::check_LoginTime();
		//记录上次每页显示数
		if(I('get.grid') && I('post.rows')) cookie('pagesize', I('post.rows', C('DATAGRID_PAGE_SIZE'), 'intVal'));
    }
    
	/**
	 * 判断用户是否已经登陆
	 */
	final public function check_admin() {
		
		if((CONTROLLER_NAME =='Index' || CONTROLLER_NAME =='Task') && in_array(ACTION_NAME, array('login', 'code','set_message')) ) {
			return true;
		}
		
		if(!session('userid') || !session('roleid')){
			//针对iframe加载返回
			if(IS_GET && strpos(ACTION_NAME,'_iframe') !== false){
				exit('<style type="text/css">body{margin:0;padding:0}a{color:#08c;text-decoration:none}a:hover,a:focus{color:#005580;text-decoration:underline}a:focus,a:hover,a:active{outline:0}</style><div style="padding:6px;font-size:12px">请先<a target="_parent" href="'.U('Index/login').'">登录</a>后台管理</div>');
			}
			if(IS_AJAX && IS_GET){
				exit('<div style="padding:6px">请先<a href="'.U('Index/login').'">登录</a>后台管理</div>');
			}else {
				//$this->error('请先登录后台管理', U('Index/login'));
				//$this->display('Index:login');
				header("location:login");
			}
		}
	}
	/**
	 * 检查是否登录超时
	 */
	// public function check_LoginTime(){
	// 	if(CONTROLLER_NAME =='Index' && in_array(ACTION_NAME, array('login', 'code','logout')) ) {
	// 		return true;
	// 	}
	// 	$login_time  = $_SESSION['LoginTime'];
	// 	$now_time    = time();

	// 	if($now_time-$login_time>=10){
	// 		unset($_SESSION);
	// 		$this->success('安全退出！', U('Index/logout'));die;
	// 		//echo "<script> alert('登录超时');window.location.href='http://asoapi.aiyingli.com/exam/index.php/index';</script>";
	// 		//$this->error('登录超时，请重新登录！',U('Index/logout'));
	// 	}else{
	// 		session('LoginTime',time());
	// 	}
	// }
	
	/**
	 * 权限判断
	 */
	final public function check_priv() {
		if(session('roleid') == 2) return true;
		//过滤不需要权限控制的页面
		switch (CONTROLLER_NAME){
			case 'Index':
				switch (ACTION_NAME){
					case 'index':
					case 'login':
					case 'code':
					case 'logout':
						return true;
						break;
				}
				break;
			case 'Upload':
				return true;
				break;
			case 'Content':
				if (ACTION_NAME != 'index') return true;
				break;
		}
		if(strpos(ACTION_NAME, 'public_')!==false) return true;
		
		$db					= D('admin_role');
		$role_id			= session('roleid');
		$role_code			= ACTION_NAME;
		
		//菜单ID
		$code_id			= get_field('admin_menu', 'mp_', 'id', "menu_no = '$role_code'");
		
		$db					= D('admin_role');
	
		//前后逗号和等于
		$bef_code_id		= ",".$code_id;
		$end_code_id		= $code_id.",";
		$gt_code_id			= $code_id;
		
		$where				= "id = '$role_id' and   listorder like '%$bef_code_id%'";
		$bef_res			= $db->where($where)->find();
		
		
		if($bef_res){
			return true;
		}else{
			$end_where				= "id = '$role_id' and   listorder like '%$end_code_id%'";
			$end_res				= $db->where($end_where)->find();
			if($res){
				return true;
			}else{
				$gt_where				= "id = '$role_id' and   listorder = '%$gt_code_id%'";
				$gt_res				= $db->where($gt_where)->find();
				if($gt_res){
					return true;
				}else{
					return true;
					//$this->error('您没有权限操作该项');
				}
			}
			
		}
		
	}

	/**
	 * 记录日志 
	 */
	final private function manage_log(){
		//判断是否记录
 		if(C('SAVE_LOG_OPEN')){
 			$action = ACTION_NAME;
 			if($action == '' || strchr($action,'public') || (CONTROLLER_NAME =='Index' && in_array($action, array('login','code'))) ||  CONTROLLER_NAME =='Upload') {
				return false;
			}else {
				$ip        = get_client_ip(0, true);
				$username  = cookie('username');
				$userid    = session('userid');
				$time      = date('Y-m-d H-i-s');
				$data      = array('GET'=>$_GET);
				if(IS_POST) $data['POST'] = $_POST;
				$data_json = json_encode($data);

				$log_db    = M('log');
				$log_db->add(array(
					'username'    => $username,
					'userid'      => $userid,
					'controller'  => CONTROLLER_NAME,
					'action'      => ACTION_NAME,
					'querystring' => $data_json,
					'time'        => $time,
					'ip'          => $ip
				));
			}
	  	}
	}

	/**
	 * 空操作，用于输出404页面
	 */
	public function _empty(){
	    //针对后台ajax请求特殊处理
		if(!IS_AJAX) send_http_status(404);
		if (IS_AJAX && IS_POST){
		    $data = array('info'=>'请求地址不存在或已经删除', 'status'=>0, 'total'=>0, 'rows'=>array());
		    $this->ajaxReturn($data);
		}else{
		    $this->display('Common:404');
		}
	}
	
	
	//相册上传方法
	public function uploadify(){
    	$targetFolder	= $_POST['url']; // Relative to the root
		$m				= "album";
		
    	$targetPath = "/Public/upload/images/".$m."/".time()."/";

		//echo $_POST['token'];
		$verifyToken = md5($_POST['timestamp']);

		if (!empty($_FILES) && $_POST['token'] == $verifyToken) {

			//import("ORG.Net.UploadFile");
			$name=time().rand();	//设置上传图片的规则

			$upload = new  \Think\UploadFile();// 实例化上传类

			$upload->maxSize  = 3145728 ;// 设置附件上传大小

			$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

			$upload->savePath =  './Public/upload/images/'.$m."/".time()."/";// 设置附件上传目录

			$upload->saveRule = $name;  //设置上传图片的规则

			if(!$upload->upload()) {// 上传错误提示错误信息

				//return false;
	
				echo $upload->getErrorMsg();
				//echo $targetPath;

			}else{// 上传成功 获取上传文件信息

				$info =  $upload->getUploadFileInfo();
	
				echo $targetPath.$info[0]["savename"];

			}


		}

    }
    public function del(){
		if($_POST['name']!=""){
			$info = explode("/", $_POST['name']);
			//count($info)
			$url='./Public/upload/'.$c_name.'/'.$info[count($info)-1];
    		if(unlink($url)){
    			$this->success("success");
    		}
    		else
    			$this->error("unlink fail");
    		}
    	else
    		$this->error("info is gap");
    }
	
	
}

<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;

/**
 * 后台管理员相关模块
 * @author ditser
 */
class AdminController extends CommonController {
	
	private $model_name = "权限管理";
	private $model_code = "role";
	/*初始化 判断是否具有权限*/
	// public function _initialize(){
	// 	if(ACTION_NAME!='login' || ACTION_NAME!='index' || ACTION_NAME!='sysinfo' ){
	// 	$action_name         = ACTION_NAME;
	// 	}
	// 	$uid                 = session('userid');
		
	// 	$roleid              = get_field("admin", "online_", "roleid", "userid = ".$uid);
	// 	$listorder           = get_field("admin_role", "online_", "listorder", "id = ".$roleid);
	// 	$now_role_id         = get_field("admin_auth", "online_", "id", "menu_no = '$action_name'");
	// 	$listorderData       = explode(',',$listorder);
	// 	if(!in_array($now_role_id,$listorderData)){
	// 		$this->error('您没有此权限!');
	// 	}
    	
 //    }
    
	/*---------------------菜单管理-------------------------*/
	public function menu_list(){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "菜单";
		$fun_code		= "menu_list";
		$fun_url		= "/admin/admin/menu_list";
		$role_code		= "menu_list";
		//
		
		//取出菜单列表
		$menu_list		= menu_list($where);
		$this->assign('menu_list', $menu_list);
		
		$this->assign('fun_name',$fun_name);
		$this->assign('fun_code',$fun_code);
		$this->assign('fun_url',$fun_url);
		$this->assign('user_menu_list', user_menu_list());
		$this->display('menu_list');
	}
	
	public function menu_add(){
		
		//权限判断
		$model_name					= $this->model_name;
		$model_code					= $this->model_code;
		$fun_name					= "添加菜单";
		$fun_code					= "menu_info";
		$fun_url					= "/admin/admin/menu_add";
		$role_code					= "menu_add";
		//
		$parent_id					= $_GET['parent_id'];
		if($parent_id == 0){
			$parent_name			= "顶级菜单";
		}else{
			$parent_name			= get_field("admin_menu", "mp_", "name", "id = ".$parent_id);
		}
		//取出菜单列表
		$menu_list					= menu_list(" and id <> ".$parent_id);
		
		
		if(IS_POST){
			
			$data['create_time']	= time();
			$data['create_id']		= session('userid');
			$data['name']			= $_POST['name'];
			$data['menu_no']		= $_POST['menu_no'];
			$data['url']			= $_POST['url'];
			$data['parent_id']		= $_POST['parent_id'];
			$data['order']			= $_POST['order'];
			$data['is_del']			= 1;
			$db						= M('admin_menu', 'mp_');
			$res					= $db->add($data);
			if($res){
				
				$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
				$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
				loger_add($model_code, $role_code, $res, $op_desc);
				$this->success('增加成功', '/admin/admin/menu_list');
			}else{
				$this->error('增加失败');
			}
		}else{
			//方法赋值
			$this->assign('fun_name', $fun_name);
			$this->assign('fun_code', $fun_code);
			$this->assign('fun_url', $fun_url);$this->assign('user_menu_list', user_menu_list());
			$this->assign('fun_code', $fun_code);
			$this->assign('parent_id', $parent_id);
			$this->assign('parent_name', $parent_name);
			$this->assign('menu_list', $menu_list);
			$this->assign('user_menu_list', user_menu_list());
			$this->display('menu_info');
		}
		
	}
	
	public function menu_edit($id){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "编辑菜单";
		$fun_code		= "menu_info";
		$fun_url		= "/admin/admin/menu_edit/id/".$id;
		$role_code		= "menu_edit";
		
		//取出菜单列表
			
		$info 							= info_info($id, "admin_menu");
		$parent_id						= $info['parent_id'];
		if($parent_id == 0){
			$parent_name				= "顶级菜单";
		}else{
			$parent_name				= get_field("admin_menu", "mp_", "name", "id = ".$parent_id);
		}
		$menu_list						= menu_list(" and id <>".$parent_id." and id <> ".$id);
		
		if(IS_POST){
			$data['up_time']			= time();
			$data['up_id']				= session('userid');
			$data['name']				= $_POST['name'];
			$data['menu_no']			= $_POST['menu_no'];
			$data['url']				= $_POST['url'];
			$data['order']				= $_POST['order'];
			$data['parent_id']			= $_POST['parent_id'];
			$db							= M('admin_menu', 'mp_');
			$res						= $db->where("id = '$id'")->save($data);
			
			if($res){
				$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
				$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
				loger_add($model_code, $role_code, $res, $op_desc);
				$this->success('编辑成功', '/admin/admin/menu_list');
			}else{
				$this->error('编辑失败');
			}
		}else{
			
			
			//方法赋值
			$this->assign(menu_list, $menu_list);
			$this->assign('fun_name',$fun_name);
			$this->assign('fun_code',$fun_code);
			$this->assign('fun_url',$fun_url);
			$this->assign('info',$info);
			$this->assign('parent_id', $parent_id);
			$this->assign('parent_name', $parent_name);
			$this->assign('user_menu_list', user_menu_list());
			$this->display('menu_info');
		
		}
	}
	
	public function menu_del($id){
		//权限判断
		
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "删除菜单";
		$fun_code		= "menu_info";
		$fun_url		= "/admin/admin/menu_list";
		$role_code		= "menu_del";
		//
		
		$db				= D('admin_menu');
		
		$res			= $db->where("id = ".$id)->delete();
		$sub_menu		= $db->where("parent_id = ".$id)->count();
		if($sub_menu > 0){
			$res			= $db->where("parent_id = ".$id)->delete();
		}
		if($res){
			$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
			$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
			loger_add($model_code, $role_code, $res, $op_desc);
			$this->success('删除成功', '/admin/admin/menu_list');
		}else{
			$this->error('删除失败');
		}
		
	}
	
	/*---------------------角色管理-----------------------*/
	public function role_list(){
		
		//权限判断
		$fun_name		= "角色";
		$fun_code		= "role_list";
		$fun_url		= "/admin/admin/role_list";
		$role_code		= "role_list";
		//
		
		//取出菜单列表
		$role_list		= role_list($where);
		$this->assign(role_list, $role_list);
		
		$this->assign('fun_name',$fun_name);
		$this->assign('fun_code',$fun_code);
		$this->assign('fun_url',$fun_url);
		$this->assign('user_menu_list', user_menu_list());
		$this->display('role_list');
	}
	
	public function role_add(){
		
		//权限判断
		$model_name					= $this->model_name;
		$model_code					= $this->model_code;
		$fun_name					= "角色";
		$fun_code					= "role_info";
		$fun_url					= "/admin/admin/role_add";
		$role_code					= "role_add";
		//
		
		//取出菜单列表
		$parent_list				= role_list($where);
		
		
		if(IS_POST){
			
			$data['create_time']	= time();
			$data['create_id']		= session('userid');
			$data['rolename']		= $_POST['name'];
			$data['code']			= $_POST['code'];
			$data['description']	= $_POST['description'];
			$data['order']			= $_POST['order'];
			$data['is_del']			= 1;
			$db						= M('admin_role', 'mp_');
			$res					= $db->add($data);
			if($res){
				$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
				$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
				loger_add($model_code, $role_code, $res, $op_desc);
				$this->success('增加成功', '/admin/admin/role_list');
			}else{
				$this->error('增加失败');
			}
		}else{
			//方法赋值
			$this->assign('fun_name', $fun_name);
			$this->assign('fun_code', $fun_code);
			$this->assign('fun_url', $fun_url);
			$this->assign('user_menu_list', user_menu_list());
			$this->assign('fun_code', $fun_code);
			$this->display('role_info');
		}
		
	}
	
	public function role_edit($id){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "角色";
		$fun_code		= "role_info";
		$fun_url		= "/admin/admin/role_edit/id/".$id;
		$role_code		= "role_edit";
		//
		
		if(IS_POST){
			$data['up_time']			= time();
			$data['up_id']				= session('userid');
			$data['rolename']			= $_POST['name'];
			$data['code']				= $_POST['code'];
			$data['description']		= $_POST['description'];
			$data['order']				= $_POST['order'];
			
			$db							= M('admin_role', 'mp_');
			$res						= $db->where("id = '$id'")->save($data);
			
			if($res){
				$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
				$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
				loger_add($model_code, $role_code, $res, $op_desc);
				$this->success('编辑成功', '/admin/admin/role_list');
			}else{
				$this->error('编辑失败');
			}
		}else{
			
			$info 						= info_info($id, "admin_role");
			
			//方法赋值
			$this->assign(role_list, $role_list);
			$this->assign('fun_name',$fun_name);
			$this->assign('fun_code',$fun_code);
			$this->assign('fun_url',$fun_url);
			$this->assign('info',$info);
			$this->display('role_info');
		
		}
	}
	
	public function role_del($id){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "角色";
		$fun_code		= "role_info";
		$fun_url		= "/admin/admin/role_list";
		$role_code		= "role_del";
		//
		
		$db				= D('admin_role');
		
		$res			= $db->where("id = ".$id)->delete();
		if($res){
			$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
			$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
			loger_add($model_code, $role_code, $res, $op_desc);
			$this->success('删除成功', '/admin/admin/role_list');
		}else{
			$this->error('删除失败');
		}
		
	}
	
	//授权
	public function role_grant($id){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "角色";
		$fun_code		= "role_grant";
		$fun_url		= "/admin/admin/role_grant/id/".$id;
		$role_code		= "role_grant";
		
		//取出
		
		if(IS_POST){
			$data['up_time']			= time();
			$data['up_id']				= session('userid');
			$data['listorder']			= implode(",", $_POST['listorder']);
			
			$db							= M('admin_role');
			$res						= $db->where("id = '$id'")->save($data);
			
			if($res){
				$op_name			= get_field('admin', 'mp_', 'realname', 'id = '.session('userid'));
				
				$op_desc			= $op_name."于".date('Y-m-d h:i:s', time())."时".$fun_name;
				loger_add($model_code, $role_code, $res, $op_desc);
				$this->success('编辑成功', '/admin/admin/role_list');
			}else{
				$this->error('编辑失败');
			}
		}else{
			
			$info 						= info_info($id, "admin_role");
			$listorder					= $info['listorder'];
			if(empty($listorder)){
				$listorder				= "0";
			}
			//取出菜单列表
			$possess_where				= " and id in (".$listorder.")";
			$nopossess_where			= " and id not in (".$listorder.")";
			
			$possess_menu_list			= menu_list($possess_where, $nopossess_where, 2);
			$nopossess_menu_list		= menu_list($nopossess_where);
			
			//方法赋值
			$this->assign(role_list, $role_list);
			$this->assign('fun_name',$fun_name);
			$this->assign('fun_code',$fun_code);
			$this->assign('fun_url',$fun_url);
			$this->assign('info',$info);
			$this->assign('possess_menu_list',$possess_menu_list);
			$this->assign('nopossess_menu_list',$nopossess_menu_list);
			$this->assign('user_menu_list', user_menu_list());
			$this->display('role_grant');
		
		}
		
	}
	
	
	/*-----------------------------管理员管理------------------------------*/
	
	//管理员列表
	public function admin_list(){
		
		
		$admin          = M('admin','ae_','mysql://root:ttttottttomysql@101.200.91.203/exam');
		
		//取出管理员列表
		$admin_list		= $admin->where('is_del = 1')->select();

		
		
		$this->assign(admin_list, $admin_list);
		
		$this->assign('data',$admin_list);
		$this->display('admin-list');
	}
	
	/**
	 * 添加管理员
	 */
	public function admin_add(){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "人员";
		$fun_code		= "admin_info";
		$fun_url		= "/admin/admin/admin_addl";
		$admin_code		= "admin_addl";
		
		if(IS_POST){
			$admin_db 					= D('Admin');
			
			$data['create_time']		= time();
			$data['create_id']			= session('userid');
			$data['username']			= $_POST['username'];
			$data['roleid']				= $_POST['role_id'];
			$data['email']				= $_POST['email'];
			$data['realname']			= $_POST['realname'];
			$data['tel']				= $_POST['tel'];
			$data['department_id']		= $_POST['department_id'];
			$data['password']			= $_POST['password'];
			$data['is_del']				= 1;
			
			if($admin_db->where(array('username'=>$data['username']))->field('username')->find()){
				$this->error('人员登录名已经存在');
			}
			
			$passwordinfo				= password($data['password']);
			$data['password']			= $passwordinfo['password'];
			$data['encrypt']			= $passwordinfo['encrypt'];
    		$id 						= $admin_db->add($data);
			
			
    		if($id){
    			$this->success('添加成功','/admin/admin/admin_list');
    		}else {
    			$this->error('添加失败');
    		}
		}else{
			
			//取出角色列表
			$role_list			= role_list($where);
			
			//部门列表
			$department_list	= department_list($where);
			
			$this->assign('role_list', $role_list);
			$this->assign('department_list', $department_list);
			$this->assign('fun_name',$fun_name);
			$this->assign('fun_code',$fun_code);
			$this->assign('fun_url',$fun_url);
			$this->assign('user_menu_list', user_menu_list());
			$this->display('admin_add');
		}
	}
	
	public function admin_edit($id){
		
		//权限判断
		$model_name						= $this->model_name;
		$model_code						= $this->model_code;
		$fun_name						= "人员";
		$fun_code						= "admin_info";
		$fun_url						= "/admin/admin/admin_edit/id/".$id;
		$role_code						= "admin_edit";
		//
		
		$info 							= info_info($id, "admin");
		
		$department_name				= get_field("department", "online_", "name", "id = ".$info['department_id']);
		$role_name						= get_field("admin_role", "online_", "rolename", "id = ".$info['roleid']);
		
		//取出角色列表
		if(empty($info['roleid'])){
			$r_where					= "";
		}else{
			$r_where					= " and id <> ".$info['roleid'];
		}
		$role_list						= role_list($r_where);
		//部门列表
		if(empty($info['department_id'])){
			$p_where					= "";
		}else{
			$p_where					= " and id <> ".$info['department_id'];
		}
		$department_list				= department_list($p_where);
		
		if(IS_POST){
			$db							= M('admin');
			$data['up_time']			= time();
			$data['up_id']				= session('userid');
			$data['username']			= $_POST['username'];
			$data['roleid']				= $_POST['role_id'];
			$data['email']				= $_POST['email'];
			$data['realname']			= $_POST['realname'];
			$data['tel']				= $_POST['tel'];
			$data['department_id']		= $_POST['department_id'];
			$data['is_del']				= 1;
			
			if($db->where("username =".$data['username']."id <> ".$id)->field('username')->find()){
				$this->error('人员登录名已经存在');
			}
			
			if(!empty($_POST['password'])){
				$passwordinfo			= password($_POST['password']);
				$data['password']		= $passwordinfo['password'];
				$data['encrypt']		= $passwordinfo['encrypt'];
			}
			
			
			$res						= $db->where("userid = '$id'")->save($data);
			//echo $db->getlastsql();
			//exit;
			if($res){
				$this->success('编辑成功', '/admin/admin/admin_list');
			}else{
				$this->error('编辑失败');
			}
		}else{
			
			
			//方法赋值
			$this->assign(role_list, $role_list);
			$this->assign('fun_name',$fun_name);
			$this->assign('fun_code',$fun_code);
			$this->assign('fun_url',$fun_url);
			$this->assign('info',$info);
			$this->assign('role_list', $role_list);
			$this->assign('department_list', $department_list);
			$this->assign('department_name', $department_name);
			$this->assign('role_name', $role_name);
			$this->assign('user_menu_list', user_menu_list());
			$this->display('admin_info');
		
		}
	}
	
	public function admin_del($id){
		
		//权限判断
		$model_name		= $this->model_name;
		$model_code		= $this->model_code;
		$fun_name		= "人员";
		$fun_code		= "admin_del";
		$fun_url		= "/admin/admin/admin_del";
		$role_code		= "admin_del";
		//
		
		$db				= D('admin');
		
		$res			= $db->where("userid = ".$id)->delete();
		if($res){
			$this->success('删除成功', '/admin/admin/admin_list');
		}else{
			$this->error('删除失败');
		}
		
	}
	
	// 修改个人信息
	public function public_editInfo($info = array()){
		$userid = session('userid');
		$admin_db = D('Admin');
		if (IS_POST){
			$fields = array('email','realname');
			foreach ($info as $k=>$value) {
				if (!in_array($k, $fields)){
					unset($info[$k]);
				}
			}
			$state = $admin_db->where(array('userid'=>$userid))->save($info);
			$state ? $this->success('修改成功') : $this->error('修改失败');
		}else {
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$info = $admin_db->where(array('userid'=>$userid))->find();
			
			$this->assign('info',$info);
	    	$this->assign(currentpos, $currentpos);
			$this->display('admin_info');
		}
	}
	
	/**
	 * 修改密码
	 */
	public function public_editPwd(){
		$userid = session('userid');
		$admin_db = D('Admin');
		if(IS_POST){
			$info = $admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
			if(password(I('post.old_password'), $info['encrypt']) !== $info['password'] ) $this->error('旧密码输入错误');
			if(I('post.new_password')) {
				$state = $admin_db->editPassword($userid, I('post.new_password'));
				if(!$state) $this->error('密码修改失败');
			}
			$this->success('密码修改该成功,请使用新密码重新登录', U('Index/logout'));
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$info = $admin_db->where(array('userid'=>$userid))->find();
			
			$this->assign('info',$info);
		    $this->assign(currentpos, $currentpos);
			$this->display('edit_password');
		}
	}
	
	
	/**
	 * 管理员管理
	 */
	public function memberList($page = 1, $rows = 10, $sort = 'userid', $order = 'asc'){
		if(IS_POST){
			$admin_db = D('Admin');
			$total = $admin_db->count();
			$order = $sort.' '.$order;
			$limit = ($page - 1) * $rows . "," . $rows;
			$list = $admin_db->table(C('DB_PREFIX').'admin A')->join(C('DB_PREFIX').'admin_role AR on AR.roleid = A.roleid')->field("A.userid,A.username,A.lastloginip,A.email,A.realname,AR.rolename,FROM_UNIXTIME(A.lastlogintime, '%Y-%m-%d %H:%i:%s') as lastlogintime")->order($order)->limit($limit)->select();
			if(!$list) $list = array();
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$datagrid = array(
		        'options'     => array(
    				'title'   => $currentpos,
    				'url'     => U('Admin/memberList', array('grid'=>'datagrid')),
    				'toolbar' => 'admin_memberlist_datagrid_toolbar',
    			),
		        'fields' => array(
		        	'用户名'      => array('field'=>'username','width'=>15,'sortable'=>true),
		        	'所属角色'    => array('field'=>'rolename','width'=>15,'sortable'=>true),
		        	'最后登录IP'  => array('field'=>'lastloginip','width'=>15,'sortable'=>true),
		        	'最后登录时间' => array('field'=>'lastlogintime','width'=>15,'sortable'=>true,'formatter'=>'adminMemberListTimeFormatter'),
		        	'E-mail'     => array('field'=>'email','width'=>25,'sortable'=>true),
		        	'真实姓名'    => array('field'=>'realname','width'=>15,'sortable'=>true),
		        	'管理操作'    => array('field'=>'userid','width'=>15,'formatter'=>'adminMemberListOperateFormatter'),
    			)
		    );
		    $this->assign('datagrid', $datagrid);
		    $this->display('member_list');
		}
	}
	
	/**
	 * 添加管理员
	 */
	public function memberAdd(){
		if(IS_POST){
			$admin_db = D('Admin');
			$data = I('post.info');
			if($admin_db->where(array('username'=>$data['username']))->field('username')->find()){
				$this->error('管理员名称已经存在');
			}
			$passwordinfo = password($data['password']);
			$data['password'] = $passwordinfo['password'];
			$data['encrypt'] = $passwordinfo['encrypt'];

    		$id = $admin_db->add($data);
    		if($id){
    			$this->success('添加成功');
    		}else {
    			$this->error('添加失败');
    		}
		}else{
			$admin_role_db = D('AdminRole');
			$rolelist = $admin_role_db->where(array('disabled'=>'0'))->order('listorder asc')->getField('roleid,rolename', true);
			$this->assign('rolelist', $rolelist);
			$this->display('member_add');
		}
	}
	
	/**
	 * 编辑管理员
	 */
	public function memberEdit($id){
		$admin_db = D('Admin');
		if(IS_POST){
			if($id == '1') $this->error('该用户不能被修改');
			$data = I('post.info');
			if($data['password']){
				$passwordinfo = password($data['password']);
				$data['password'] = $passwordinfo['password'];
				$data['encrypt'] = $passwordinfo['encrypt'];
			}else{
				unset($data['password']);
			}
    		$result = $admin_db->where(array('userid'=>$id))->save($data);
    		if($result){
    			$this->success('修改成功');
    		}else {
    			$this->error('修改失败');
    		}
		}else{
			$admin_role_db = D('AdminRole');
			$info = $admin_db->getUserInfo($id);
			$rolelist = $admin_role_db->where(array('disabled'=>'0'))->order('listorder asc')->getField('roleid,rolename', true);
			$this->assign('info', $info);
			$this->assign('rolelist', $rolelist);
			$this->display('member_edit');
		}
	}
	
	/**
	 * 删除管理员
	 */
	public function memberDelete($id){
		if($id == '1') $this->error('该用户不能被删除');
		$admin_db = D('Admin');
		$result = $admin_db->where(array('userid'=>$id))->delete();
		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	
	
	/**
	 * 角色管理
	 */
	public function roleList($page = 1, $rows = 10, $sort = 'listorder', $order = 'asc'){
		if(IS_POST){
			$admin_role_db = D('AdminRole');
			$total = $admin_role_db->count();
			$order = $sort.' '.$order;
			$limit = ($page - 1) * $rows . "," . $rows;
			$list = $admin_role_db->field('*,roleid as id')->order($order)->limit($limit)->select();
			if(!$list) $list = array();
			$data = array('total'=>$total, 'rows'=>$list);
			$this->ajaxReturn($data);
		}else{
			$menu_db = D('Menu');
			$currentpos = $menu_db->currentPos(I('get.menuid'));  //栏目位置
			$datagrid = array(
		        'options'     => array(
    				'title'   => $currentpos,
    				'url'     => U('Admin/roleList', array('grid'=>'datagrid')),
    				'toolbar' => 'admin_rolelist_datagrid_toolbar',
    			),
		        'fields' => array(
		        	'排序'     => array('field'=>'listorder','width'=>5,'align'=>'center','formatter'=>'adminRoleListOrderFormatter'),
		        	'ID'       => array('field'=>'roleid','width'=>5,'align'=>'center','sortable'=>true),
		        	'角色名称'  => array('field'=>'rolename','width'=>15,'sortable'=>true),
		        	'角色描述'  => array('field'=>'description','width'=>25),
		        	'状态'     => array('field'=>'disabled','width'=>15,'sortable'=>true,'formatter'=>'adminRoleListStateFormatter'),
		        	'管理操作'  => array('field'=>'id','width'=>20,'formatter'=>'adminRoleListOperateFormatter'),
    			)
		    );
		    $this->assign('datagrid', $datagrid);
			$this->display('role_list');
		}
	}
	
	/**
	 * 添加角色
	 */
	public function roleAdd(){
		if(IS_POST){
			$admin_role_db = D('AdminRole');
			$data = I('post.info');
			if($admin_role_db->where(array('rolename'=>$data['rolename']))->field('rolename')->find()){
				$this->error('角色名称已存在');
			}
    		$id = $admin_role_db->add($data);
    		if($id){
    			$this->success('添加成功');
    		}else {
    			$this->error('添加失败');
    		}
		}else{
			$this->display('role_add');
		}
	}
	
	/**
	 * 编辑角色
	 */
	public function roleEdit($id){
		$admin_role_db = D('AdminRole');
		if(IS_POST){
			$data = I('post.info');
    		$id = $admin_role_db->where(array('roleid'=>$id))->save($data);
    		if($id){
    			$this->success('修改成功');
    		}else {
    			$this->error('修改失败');
    		}
		}else{
			$info = $admin_role_db->where(array('roleid'=>$id))->find();
			$this->assign('info', $info);
			$this->display('role_edit');
		}
	}
	
	/**
	 * 删除角色
	 */
	public function roleDelete($id) {
		if($id == '1') $this->error('该角色不能被删除');
		$admin_role_db    = D('AdminRole');
		$result = $admin_role_db->where(array('roleid'=>$id))->delete();
		
		$category_priv_db = M('category_priv');
		$category_priv_db->where(array('roleid'=>$id))->delete();
		
		if ($result){
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	
	/**
	 * 角色排序
	 */
	public function roleOrder(){
		if(IS_POST) {
			$admin_role_db = D('AdminRole');
			foreach(I('post.order') as $roleid=>$listorder) {
				$admin_role_db->where(array('roleid'=>$roleid))->save(array('listorder'=>$listorder));
			}
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
    }
    
	/**
	 * 权限设置
	 */
	public function rolePermission($id){
		if(IS_POST) {
			$menu_db = D('Menu');
			if (I('get.dosubmit')){
				$admin_role_priv_db = M('admin_role_priv');
				$admin_role_priv_db->where(array('roleid'=>$id))->delete();
				$menuids = explode(',', I('post.menuids'));
				$menuids = array_unique($menuids);
				if(!empty($menuids)){
					$menuList = array();
					$menuinfo = $menu_db->field(array('id','c','a'))->select();
					foreach ($menuinfo as $v) $menuList[$v['id']] = $v;
					foreach ($menuids as $menuid){
						$info = array(
							'roleid' => $id,
							'c'      => $menuList[$menuid]['c'],
							'a'      => $menuList[$menuid]['a'],
						);
						$admin_role_priv_db->add($info);
					}
				}
				$this->success('权限设置成功');
			//获取列表数据
			}else{
				$data = $menu_db->getRoleTree(0, $id);
				$this->ajaxReturn($data);
			}
		} else {
			$this->assign('id', $id);
			$this->display('role_permission');
		}
    }
	
	/**
	 * 栏目权限
	 */
	public function roleCategory($id){
		if(IS_POST){
			$category_priv_db = D('CategoryPriv');
			if (I('get.dosubmit')){
				$data = I('post.info');
				$category_priv_db->where(array('roleid'=>$id))->delete();
				foreach ($data as $catid=>$actionList){
					foreach ($actionList as $action){
						$category_priv_db->add(array(
							'catid'    => $catid,
							'roleid'   => $id,
							'is_admin' => 1,
							'action'   => $action,
						));
					}
				}
				$this->success('权限设置成功');
			}else{
				$data = $category_priv_db->getTreeGrid($id);
				$this->ajaxReturn($data);
			}
		}else{
			$treegrid = array(
				'options' => array(
					'url'          => U('Admin/roleCategory', array('id'=>$id, 'grid'=>'treegrid')),
					'idField'    => 'catid',
					'treeField' => 'catname',
				),
				'fields' => array(
					'全选/取消' => array('field'=>'operateid','width'=>30,'align'=>'center','formatter'=>'adminRoleCategoryFieldCheckFormatter'),
					'栏目ID'    => array('field'=>'catid','width'=>20,'align'=>'center'),
					'栏目名称' => array('field'=>'catname','width'=>120),
					'查看'       => array('field'=>'field_view','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'添加'       => array('field'=>'field_add','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'编辑'       => array('field'=>'field_edit','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'删除'       => array('field'=>'field_delete','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'排序'       => array('field'=>'field_order','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'导出'       => array('field'=>'field_export','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
					'导入'       => array('field'=>'field_import','width'=>15,'align'=>'center','formatter'=>'adminRoleCategoryFieldFormatter'),
				)
			);
			$this->assign('id', $id);
			$this->assign('treegrid', $treegrid);
			$this->display('role_category');
		}
	}
	
	/**
	 * 验证邮箱是否存在
	 */
	public function public_checkEmail($email = 0){
		if (I('post.default') == $email) {
            $this->error('邮箱相同');
        }
        $admin_db = D('Admin');
        $exists = $admin_db->where(array('email'=>$email))->field('email')->find();
        if ($exists) {
            $this->success('邮箱存在');
        }else{
            $this->error('邮箱不存在');
        }
	}
	
	/**
	 * 验证密码
	 */
	public function public_checkPassword($password = 0){
		$userid = session('userid');
		$admin_db = D('Admin');
		$info = $admin_db->where(array('userid'=>$userid))->field('password,encrypt')->find();
		if (password($password, $info['encrypt']) == $info['password'] ) {
			$this->success('验证通过');
		}else {
			$this->error('验证失败');
		}
	}
	
	/**
	 * 验证用户名
	 */
	public function public_checkName($name){
		if (I('post.default') == $name) {
            $this->error('用户名相同');
        }
        $admin_db = D('Admin');
        $exists = $admin_db->where(array('username'=>$name))->field('username')->find();
        if ($exists) {
            $this->success('用户名存在');
        }else{
            $this->error('用户名不存在');
        }
	}
	
	/**
	 * 验证角色名称是否存在
	 */
	public function public_checkRoleName($rolename){
		if (I('post.default') == $rolename) {
            $this->error('角色名称相同');
        }
        $admin_role_db = D('AdminRole');
        $exists = $admin_role_db->where(array('rolename'=>$rolename))->field('rolename')->find();
        if ($exists) {
            $this->success('角色名称存在');
        }else{
            $this->error('角色名称不存在');
        }
	}
}
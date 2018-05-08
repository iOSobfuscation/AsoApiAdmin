<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\AuthorityModel;
/**
 * 后台自定义菜单管理模块
 * @author ditser
 */
class AuthorityController extends CommonController {
    /*--------------------- 自定义菜单管理模块-----------------------*/
	private $model_name = "全选模块";
	private $model_code = "Authority";
	 //列表
	public function authority_list(){
		
		//权限判断
		$model_name						= $this->model_name;
		$model_code						= $this->model_code;
		$fun_name						= $model_name."列表";
		$fun_code						= "menu_list";
		$fun_url						= "/admin/menu/menu_list";
		$role_code						= "menu_list";
		//
		$menu                           = new AuthorityModel();
		$menu_list                      = $menu->menu_list_data();
		
		//搜索条件处理
		if(IS_POST){
			
			//处理等于查询
			$bs_id						= $_POST['id'];
			
			//处理模糊查询
			$bs_name					= $_POST['name'];
			$bs_code					= $_POST['code'];
			if(!empty($bs_name)){
				$where					.= " and name like '%".$bs_name."%'";
			}
			if(!empty($bs_code)){
				$where					.= " and code like '%".$bs_code."%'";
			}
			
		}
		
		//取出自定义菜单
		
		
		//取出模块
		$model_list						= model_list();
		
		//取出素材
		$material_list					= material_list();
		
		$cons_menu_total				= cons_menu_total(0);
		
		$this->assign('model_name', $model_name);
		$this->assign('fun_name', $fun_name);
		$this->assign('fun_code', $fun_code);
		$this->assign('fun_url', $fun_url);
		$this->assign('user_menu_list', user_menu_list());
		
		$this->assign('menu_list', $menu_list);
		$this->assign('material_list', $material_list);
		$this->assign('cons_menu_total', $cons_menu_total);
		$this->assign('model_list', $model_list);
		$this->display('index');
	}
	
	//新增
	public function authority_add(){
		
		$menu                               = new AuthorityModel();
		if(IS_POST){
			
			$data['name']					= $_POST['name'];
			$data['parent_id']              = $_POST['parent_id'];
			$data['menu_no']			    = $_POST['menu_no'];
			$data['order']					= $_POST['order'];
			$data['url']                    = $_POST['url'];
			
			
			$name							= $data['name'];
			$order							= $data['order'];
			if(empty($name)){
				$this->error('名称不能为空');
				exit;
			}
			if(empty($order)){
				$this->error('排序不能为空');
				exit;
			}
			
			
			
			
			$data['create_time']			= time();
			$data['create_id']				= session('userid');
			
			$menu_id                        = $menu->insert_data($data);		
			if($menu_id){
				$this->success('增加成功', '/admin/authority/authority_list');
			}else{
				$this->error('增加失败');
			}
		}else{
			$parent_id            = intval($_GET['parent_id']);
			$parent_data          = $menu->get_fields('admin_auth',"id = {$parent_id} and is_del = 0",'name');
			
			$this->assign('parent_data',$parent_data);
			$this->assign('parent_id',$parent_id);
			$this->assign('user_menu_list', user_menu_list());
			$this->assign('parentid', $parentid);
			$this->assign('parent_name', $parent_name);
			$this->assign('material_list', $material_list);
			$this->assign('model_list', $model_list);
			
			$this->assign('model_name', $model_name);
			$this->assign('fun_name', $fun_name);
			$this->assign('fun_code', $fun_code);
			$this->assign('fun_url', $fun_url);
			$this->assign('menu_list', $menu_list);
			$this->display('menu_add');
		}
		
		
	}
	
	//编辑
	public function authority_edit($id){
		
		//权限判断
		$model_name							= $this->model_name;
		$fun_name							= "编辑".$model_name;
		$fun_code							= "menu_info";
		$fun_url							= "/admin/menu/menu_add";
		$role_code							= "menu_edit";
		
		$menu                               = new AuthorityModel();
		if(IS_POST){
			$id                             = intval($_POST['id']);
			$data['name']					= $_POST['name'];
			$data['parent_id']              = $_POST['parent_id']?$_POST['parent_id']:0;
			$data['menu_no']                = $_POST['menu_no'];
			$data['order']					= $_POST['order'];
			$data['url']                    = $_POST['url'];
			$data['up_id']                  = session('userid');
			$data['up_time']                   = time();
			
			
			if(empty($data['name'])){
					
				$this->error('名称不能为空');
				exit;
			}
			
			$res                             = $menu->save_data("id = $id",$data);
			
			if($res){
				$this->success('编辑成功', '/admin/authority/authority_list');
			}else{
				$this->error('编辑失败');
			}
			
		}else{
			/*取出当前菜单信息*/
			$id                  =  intval($_GET['id']);
			$info                =  $menu->get_fields('admin_auth',"id= {$id} and is_del=0",'id,parent_id,name,menu_no,url,order');
			/*取出父级菜单列表*/
			$parent_menu         =  $menu->parent_menu();

		
			$this->assign('info',$info);
			$this->assign('parent_menu',$parent_menu);
			$this->display('menu_add');
			
		}
	}
	
	//发布
	public function menu_release(){
		//权限判断
		$model_name						= $this->model_name;
		$model_code						= $this->model_code;
		$fun_name						= $model_name."列表";
		$fun_code						= "menu_list";
		$fun_url						= "/admin/menu/menu_list";
		$role_code						= "menu_list";
		if(IS_POST){
			if($_POST['release'] == 0){
				$res					= release_consmenu();
				if($res['errcode'] == 0){
					$this->success('发布成功', '/admin/menu/menu_list');
				}else{
					print_r($res);
					echo $res['errcode'];
					exit;
					//$this->error('发布失败');
				}
			}
		}
	}
	
	//删除
	public function authority_del($id){
		
		$db									= M('admin_auth');
		$res								= $db->where("id = '$id'")->delete();
		$sub_res							= $db->where("parentid = '$id'")->delete();
		if($res){
			$this->success('删除成功', '/admin/authority/authority_list');
		}else{
			$this->error('删除失败');
		}
	}
	
	//验证逻辑
	//验证添加子类
	public function check_menu_add(){
		$menu_id							= $_POST['menu_id'];
		
		if($menu_id == NULL){
			//判断菜单id是否为空
			$res['status']					= 1;
			$res['msg']						= "菜单ID不能为空";
		}elseif($menu_id == 0){
			//增加一级菜单
			$cons_menu_total				= cons_menu_total(0);
			if($cons_menu_total >= 3){
				$res['status']				= 2;
				$res['msg']						= "最多只能有三个一级菜单";
			}else{
				$res['status']				= 0;
			}
		}else{
			$cons_menu_total				= cons_menu_total($menu_id);
			if($cons_menu_total >= 5){
				$res['status']				= 3;
				$res['msg']					= "每个一级菜单最多只能有五个二级菜单";
			}else{
				$res['status']				= 0;
			}
		}
		//取出一级菜单名称
		//$parent_id							= get_field('menu', 'mp_', 'parentid', 'id = '.$menu_id);
		$parent_name						= get_field('menu', 'mp_', 'name', 'id = '.$menu_id);
		$res['parent_name']					= $parent_name;
		$res								= json_encode($res, true);
		echo $res;
	}
	
	
	
}
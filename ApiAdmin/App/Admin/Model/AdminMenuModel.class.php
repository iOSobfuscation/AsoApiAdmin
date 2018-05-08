<?php
	namespace Admin\Model;
	use Think\Model;
	
	class AdminMenuModel extends Model{
		/*
			desc 获取一级后台菜单列表
			
		*/
		public function  menu_list_data(){
			$menu           = M('admin_menu');
			
			
			$where          = 'm.is_del = 0 and m.parent_id = 0';
			$subwhere       = 'm.is_del = 0 and m.parent_id !=0';
			
			$fields         = 'm.id,m.create_time,m.parent_id,m.name,m.url,m.order,a.username';
			
			$data           = $menu->alias('m')->where($where)->join('left join ae_admin as a on a.userid=m.create_id')->order('m.order desc')->field($fields)->select();
			$subData        = $menu->alias('m')->where($subwhere)->join('left join ae_admin as a on a.userid=m.create_id')->field($fields)->select();
			
			foreach($data as $key=>$value){
				foreach($subData as $k=>$val){
					if($subData[$k]['parent_id']==$data[$key]['id']){
						$data[$key]['sub'][]=$val;
					}
				}
			}
			
			return $data;
			
		}
		/*
			desc 获取子菜单
			$id  父菜单id
		*/
		public function sub_menu_data($id){
			$menu           = M('admin_menu');
			$where          = "m.parent_id = $id and m.is_del = 0";
			$fields         = 'm.id,m.parent_id,m.name,m.url,m.order,m.create_time,a.username';
			$data           = $menu->alias('m')->where($where)->join("left join ae_admin as a on a.userid=m.create_id")->field($fields)->select();
			
			return $data;
		}
		
		/*
			desc 获取父级菜单
		*/
		public function parent_menu(){
			$model       = M('admin_menu');
			
			$where       = "is_del = 0 and parent_id = 0";
			
			$fields      = 'id,name';
			
			$data        = $model->where($where)->field($fields)->select();
			
			return $data;
		}
		/*
		 desc菜单编辑
		 $where  条件
		 $data   编辑数据
		 */
		public function save_data($where,$data){
			$model          = M('admin_menu');
			
			$data           = $model->where($where)->save($data);
			
			return $data;
		}
		/*desc获取字段
			$table  表名
			$where  条件
			$fields 字段 注若fields为多个则以逗号隔开
		*/
		public function get_fields($table,$where,$fields){
			$model          = M($table);
			
			$data           = $model->where($where)->field($fields)->find();
			
			
			return $data;
		}
		
		//插入菜单操作
		public function insert_data($data){
			$model          = M('admin_menu');
			
			$data           = $model->add($data);
			
			if($data){
				return $data;
			}else{
				return 0;
			}
		}
	}
?>
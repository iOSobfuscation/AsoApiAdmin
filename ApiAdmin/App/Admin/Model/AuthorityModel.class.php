<?php
	namespace Admin\Model;
	use Think\Model;
	
	class AuthorityModel extends Model{
		/*
			desc 获取后台菜单列表
			
		*/
		public function  menu_list_data(){
			$menu           = M('admin_auth');
			
			
			$where          = 'is_del = 0 and parent_id = 0';
			$subWhere       = 'is_del = 0 and parent_id !=0';
			
			$fields         = 'id,parent_id,name,url';
			
			$data           = $menu->where($where)->field($fields)->select();
			
			$subData        = $menu->where($subWhere)->field($fields)->select();
			
			foreach($data as $k=>$val){
				foreach($subData as $key=>$value){
					if($subData[$key]['parent_id']==$data[$k]['id']){
						$data[$k]['sub'][]=$value;
					}
				}
			}
			return $data;
			
		}
		/*
			desc 获取父级菜单
		*/
		public function parent_menu(){
			$model       = M('admin_auth');
			
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
			$model          = M('admin_auth');
			
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
			$model          = M('admin_auth');
			
			$data           = $model->add($data);
			
			if($data){
				return $data;
			}else{
				return 0;
			}
		}
	}
?>
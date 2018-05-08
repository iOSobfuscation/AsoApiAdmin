<?php
	namespace Admin\Model;
	use Think\Model;
	
	
	class CommonModel extends Model{
		/*
			获取指定表的指定字段
			$data  array 获取指定字段
			$table  char  获取字段的表名
			$id    int    条件
			
		*/
		//获取数据
		public function get_data($table,$otherName,$where,$leftJoin,$fields,$order){
			$model          = M($table);
				if($table=='dc_cat'){
					
					$data        = $model->alias('c')->join('left join mp_admin as a on c.create_id = a.userid')->field('c.*,a.username')->where($where)->select();
					
				}else{
				
				$data         = $model->alias($otherName)->join($leftJoin)->field($fields)->where($where)->order($order)->select();
			}
			
			
			//return $model->_sql();
			return $data;
		}
		//获取子分类
		public function get_classdata($parentId){
			$model           = M('dc_cat');
			
			
			$data            = $model->where("parent_id = $parentId and is_del = 1")->select();
			
			return $data;
			
		}
		//获取单个字段
		public function get_OneField($table,$field,$where){
			$model                   = M($table);
			
			$data                    = $model->where($where)->getField($field);
			
			return $data;
		}
		//获取单条数据
		public function find_data($table,$idName,$id){
			$model                   = M($table);
			
			
			$where                   = " $idName = $id";
			
			$res                     =$model->where($where)->find();
			
			return $res;
		}
		//统计数据总数
		public function count_data($table,$where){
			$model                   = M($table);
			
			$total                   = $model->where($where)->count();
			
			return $total;
		}
		public function get_subclass($table,$id){
			$model                   = M($table);
			
			$where                   = " c.parent_id = $id and c.is_del = 1";
			
			$data                    = $model->alias('c')->join('left join mp_admin as a on a.userid=c.create_id')->field('c.*,a.username')->where($where)->select();
			
			return $data;
		}
		//修改数据
		public function save_data($table,$idName,$id,$data){
			$model                   =M($table);
			
			
			$where                   = " $idName = $id ";
			
			$res                     =$model->where($where)->save($data);
			
			return $model->_sql();
		}
		//插入数据
		public function insert_data($table,$data){
			$model          = M($table);
			
			$data           = $model->add($data);
			
			if($data){
				return $data;
			}else{
				return 0;
			}
		}
		//删除数据
		public function del_data($table,$idName,$value){
		
		
			$model      = M($table);
			
			$ids        = rtrim($value,',');
			$where      = " $idName in ($ids) ";
			$data['is_del']   = 0;
			$res        = $model->where($where)->save($data);
			
			return $res;
		}
		//批量发布数据
		public function publish_data($table,$idName,$value){
		
		
			$model      = M($table);
			
			$ids        = rtrim($value,',');
			$where      = " $idName in ($ids) ";
			$data['is_publish']   = 0;
			$res        = $model->where($where)->save($data);
			
			return $res;
		}
		
		//状态更改
		public function status_update($table,$id,$name,$value){
			$model                 = M($table);
			if($table=='dc_cat'){
				$where  = " cat_id = $id ";
			}else if($table=='shop_brand'){
				$where  = " brand_id = $id ";
			}else if($table=='shop_popu'){
				$where  = " popu_id = $id ";
			}else if($table=='shop_tags'){
				$where  = " tags_id = $id ";
			}else{
				$where  = " id = $id ";
			}
			$data[$name]           = $value==1?0:1;
			
			
			
			
			$res=$model->where($where)->save($data);
			
			return $res;
		}	
		//获取数据字段
		public function fields($data,$table,$idName,$id){
			$model          = M($table);
			
			$result         = $model->field('*')->find();
			foreach($result as $key=>$value){
				$names[]=$key;
				
			}
			$flag=1;
			foreach($data as $val){
				if(in_array($val,$names)){
					continue;
				}else{
					$flag=0;
				}
			}
			if($flag){
				$fields =implode(',',$data);
				if(isset($id)){
					
					$where            = " $idName = $id ";
					if(count($data)==1){
						$data         =$model->where($where)->getField($fields);
					}else{
						$data         =$model->where($where)->field(" $fields ")->find();
						
					}
					
				}else{
					    $data         =$model->field(" $fields ")->find();
				}
				return $data;
			}else{
				return false;
			}
			
			
		}
		//图片上传
		public function images_upload($name,$model){
			
			$config=array(
			'rootPath'=>'Public/',
			
			'savePath'=>'upload/images/'.$model.'/',
			'exts'=>array('gif','png','jpg','jpeg','xls'),
			);
			$upload=new \Think\Upload($config);
			$date	= date("Y-mm-dd", time());
	
			//上传文件
		
			$info=$upload->uploadOne($name);
			
			$path	= 'http://'.$_SERVER['HTTP_HOST'].'/Public/'.$info['savepath'].$info['savename'];
			//$path	= $info['savepath'].$info['savename'];
		
			return $path;
		}
		
		
	}
?>

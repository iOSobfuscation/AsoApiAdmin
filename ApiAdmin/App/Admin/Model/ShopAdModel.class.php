<?php
namespace Admin\Model;
use Think\Model;
use Think\Controller;
class ShopAdModel extends model{
	public function get_data($table,$banner_type,$banner_attr_id){
		$model        = M($table);
		if($banner_type==1 && $banner_attr_id ==1){
			
			$where    = 'b.banner_type = 1 and b.banner_attr_id = 1 and b.is_del=0';
			
			$data     = $model->alias('b')->where($where)->select();
		}else if($banner_type==1 && $banner_attr_id ==2){
			$where    = 'b.banner_type = 1 and b.banner_attr_id = 2 and b.is_del=0';
			
			$data     = $model->alias('b')->where($where)->select();
		}else if($banner_type==2 && $banner_attr_id==1){
			$where    = 'b.banner_type = 2 and b.banner_attr_id = 1 and b.is_del=0';
			
			$data     = $model->alias('b')->where($where)->select();
		}else if($banner_type==2 && $banner_attr_id==2){
			$where    = 'b.banner_type = 2 and b.banner_attr_id = 2 and b.is_del=0';
			$data     = $model->alias('b')->where($where)->select();
		}else{
			$where    = "b.is_del = 0";
			$data     = $model->alias('b')->where($where)->select();
		}
		
		return $data;
	}
	//数据添加
	public function insert_data($table,$data){
			$model          = M($table);
			
			$data           = $model->add($data);
			
			if($data){
				return $data;
			}else{
				return 0;
			}
	}
	//修改操作
	public function save_data($table,$idName,$id,$data){
			$model                   =M($table);
			
			
			$where                   = " $idName = $id ";
			
			$res                     = $model->where($where)->save($data);
			
			return $res;
	}
	public function find_data($table,$idName,$id){
			$model                   = M($table);
			
			
			$where                   = " $idName = $id and is_del = 0";
			
			$res                     = $model->where($where)->find();
			
			return $res;
	}
	public function del_data($table,$idName,$value){
		
		
			$model      = M($table);
			
			$ids        = rtrim($value,',');
			$where      = " $idName in ($ids) ";
			$data['is_del']   = 1;
			$res        = $model->where($where)->save($data);
			
			return $res;
	}
	public function images_upload($name,$model){
			
			$config=array(
			'rootPath'=>'Public/',
			
			'savePath'=>'upload/images/'.$model.'/',
			'exts'=>array('gif','png','jpg','jpeg'),
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
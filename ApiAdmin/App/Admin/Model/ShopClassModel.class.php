<?php
namespace Admin\Model;
use Think\Model;

class ShopClassModel extends Model{
	public function del_shopclass($id){
			$class                = M('shop_cat');
			
			$where                = " cat_id in($id) ";
			
			$data['is_del']       = 0;
			
			$info                 = $class->where($where)->save($data);
			
			return $info;
		}
	public function get_subclass($id){
		$class                   = M('shop_cat');
		
		$where                   = " parent_id = $id ";
		
		$data                    = $class->where($where)->select();
		
		return $data;
		
	}
	public function save_data($id,$data){
			$class                =M('shop_cat');
			
			
			$where['cat_id']         =$id;
			
			$res                 =$class->where($where)->save($data);
			
			return $res;
		}
	public function add_class($data){
			$class        = M('shop_cat');
			
			if(empty($data)){
				return false;
			}
			$class_id    = $class->add($data);
			
			return $class_id;
		}
}
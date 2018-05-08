<?php
	namespace Admin\Model;
	use Think\Model;
	
	class ShopModel extends Model{
		
		/*
			获取商品信息
		*/
		public function getShopList(){
			$shop         = M('shop_goods');
			
			$where        = " g.is_del = 1 ";
			
			$data         = $shop->alias('g')->join('left join mp_shop_cat as c on c.cat_id=g.cat_id left join mp_admin as a on a.userid=g.create_id')->field('a.username,c.cat_name,g.id,g.name,g.price,g.thumb,g.create_time,g.create_id,g.desc,g.up_time,g.up_id,g.is_shelves,g.is_hot,g.is_recd,g.wish_num,g.stock')->where($where)->select();
			
			return $data;
		}
		/*type  
			int class：获取分类列表 brand：获取品牌列表 popu：获取人群分类列表 tags：获取标签列表
		*/
		public function getProductList($type,$photo){
				
				$shop          = M('shop_goods');
				$class         = M('shop_cat');
				$brand         = M('shop_brand');
				$popu          = M('shop_popu');
				$tags          = M('shop_tags');
				$album         = M('shop_goods_info');
				if(is_numeric($type) && isset($photo)){
						$where['id']     = $type;
						
						$data            = $album->where($where)->field('info,pic')->select();
				}else{
						$where['id']     = $type;
						
						$data            = $shop->where($where)->find();
				}
					if($type=='class'){
						
						$where     = " is_del = 1 ";
						$data      = $class->where($where)->field('cat_id,cat_name,cat_thumb,cat_desc,cat_sort,is_home')->select();
						
					}else if($type=='brand'){
						$where     = " is_del = 1 ";
						$data      = $brand->where($where)->field('brand_id,brand_name,brand_thumb')->select();
						
					}else if($type=='popu'){
						$where     = " is_del = 1 ";
						$data      = $popu->where($where)->field('popu_id,popu_name,popu_thumb,popu_desc')->select();
						
					}else if($type=='tags'){
						$where     = " is_del = 1 ";
						$data      = $tags->where($where)->field('tags_id,tags_name,tags_thumb')->select();
						
					}
				
				return $data;
		}		
		
		public function add_album($data){
			$album        = M('shop_goods_info');
			
			if(empty($data)){
				return false;
			}
			$album_id    = $album->add($data);
			
			return $album_id;
		}
		
		
		public function add_shop($data){
			$shop        = M('shop_goods');
			
			if(empty($data)){
				return false;
			}
			$shop_id    = $shop->add($data);
			
			return $shop_id;
		}
		
		public function del_shop($id){
			$shop                 = M('shop_goods');
			
			$where                = " id in($id) ";
			
			$data['is_del']       = 0;
			
			$info                 = $shop->where($where)->save($data);
			
			return $info;
		}
		
		public function save_data($id,$data){
			$shop                =M('shop_goods');
			
			
			$where['id']         =$id;
			
			$res                 =$shop->where($where)->save($data);
			
			return $res;
		}
	}
?>
<?php
namespace Admin\Model;
use Think\Model;
use Think\Controller;
class ShopOrderModel extends model{
	/*  $type  int   获取数据方式   
		0 获取未付款订单
		1 获取待发货订单
		2 获取待收货订单
		3 获取已完成订单
		4 获取已作废订单
	*/
	public function get_data($table,$type){
		$model               = M($table);
		$attr                = M('shop_attr_goods_attr');
		if($table=='shop_order'){
			if($type==0){
				//mp_shop_order   mp_member_address mp_member  mp_order_goods mp_shop_goods  mp_shop_goods_attr  mp_shop_attr_goods_attr
				$where           = " o.is_pay = 1 and o.status!=2 and sg.is_del =1 and at.is_del =1 ";
				
				$data            = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id')->field('o.id as oid,o.total,o.status,o.order_model,o.order_id,o.is_pay,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_sum,g.goods_attr_id,sg.id as gid,at.attr_goods_attr_id')->
				where($where)->select();
				//组装商品属性
				
				
				}else{
					if($type==1){
					$where           = " o.is_pay = 0 and o.delivery = 1 and o.status!=2";
					
					
				}else if($type==2){
					$where          = " o.is_pay = 0 and o.delivery = 0 and o.status!=2";
					
					
				}else if($type==3){
					$where          = " o.is_pay = 0 and o.delivery = 2 and o.status!=2";
				}else if($type==4){
					$where          = " o.status = 2 ";
				}
			
				$data            = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id')->field('o.id as oid,o.total,o.pay_time,o.status,o.delivery,o.is_pay,o.order_model,o.order_id,o.pay_type,o.actual_payment,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_sum,sg.name as gname,g.goods_attr_id,sg.id as gid,at.attr_goods_attr_id')->
				where($where)->select();
//组装商品属性
				
			}	
		}else{
			$where         = " is_del = 0";
			$data          = $model->where($where)->select();
		}
		return $data;
	}
	
	public function find_data($id,$type){
		$model        = M('shop_order');
		$attr         = M('shop_attr_goods_attr');
		$goods        = M('shop_goods');
		$AttrValue    = M('shop_goods_attr');
		if($type==1){
			$where        = " o.id = $id and o.status = 0 and o.delivery=1";
			$data         = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id left join mp_shop_coupons as c on c.coupons_id = o.coupons_id left join mp_shop_coupons_type as ct on c.coupons_type_id = ct.coupons_type_id')
			->field('ct.coupons_type_name,c.coupons_name,c.total_price,c.coupons_credit,c.coupons_discount,o.id as oid,o.delivery,o.coupons_id,o.is_pay,o.order_id,o.other_order_id,o.total,o.pay_time,o.status,o.order_model,o.order_id,o.pay_type,o.actual_payment,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_attr_id,g.goods_sum,sg.name as gname,sg.id as gid,at.attr_goods_attr_id')->where($where)->find();
		}else if($type==0){
			$where        = " o.id = $id and o.status = 0";
			$data         = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id left join mp_shop_coupons as c on c.coupons_id = o.coupons_id left join mp_shop_coupons_type as ct on c.coupons_type_id = ct.coupons_type_id left join mp_express as e on e.id= o.exp_id')
			->field('ct.coupons_type_name,c.coupons_name,c.total_price,c.coupons_credit,c.coupons_discount,o.id as oid,o.delivery,o.exp_time,o.exp_sn,o.coupons_id,o.is_pay,o.order_id,o.other_order_id,o.total,o.pay_time,o.status,o.order_model,o.order_id,o.pay_type,o.actual_payment,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_sum,sg.name as gname,sg.id as gid,at.attr_goods_attr_id,e.name,e.code')->where($where)->find();
		}else if($type==2){
			$where        = " o.id = $id and o.status = 0";
			$data         = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id left join mp_shop_coupons as c on c.coupons_id = o.coupons_id left join mp_shop_coupons_type as ct on c.coupons_type_id = ct.coupons_type_id left join mp_express as e on e.id= o.exp_id')
			->field('ct.coupons_type_name,c.coupons_name,c.total_price,c.coupons_credit,c.coupons_discount,o.id as oid,o.complete_type,o.complete_time,o.delivery,o.exp_time,o.exp_sn,o.coupons_id,o.is_pay,o.order_id,o.other_order_id,o.total,o.pay_time,o.status,o.order_model,o.order_id,o.pay_type,o.actual_payment,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_sum,sg.name as gname,sg.id as gid,at.attr_goods_attr_id,e.name,e.code')->where($where)->find();
		}else if($type==3){
			$where        = " o.id = $id and o.status=2";
			$data         = $model->alias('o')->join('left join mp_member as m on o.user_id = m.id left join mp_member_address as a on a.id = o.address left join  mp_shop_order_goods as g on g.order_id = o.order_id left join mp_shop_goods as sg on sg.id=g.goods_id left join mp_shop_goods_attr  as at on at.goods_attr_id = g.goods_attr_id left join mp_shop_coupons as c on c.coupons_id = o.coupons_id left join mp_shop_coupons_type as ct on c.coupons_type_id = ct.coupons_type_id left join mp_express as e on e.id= o.exp_id')
			->field('ct.coupons_type_name,c.coupons_name,c.total_price,c.coupons_credit,c.coupons_discount,o.id as oid,o.complete_type,o.complete_time,o.delivery,o.exp_time,o.exp_sn,o.coupons_id,o.is_pay,o.order_id,o.other_order_id,o.total,o.pay_time,o.status,o.order_model,o.order_id,o.pay_type,o.actual_payment,o.total,o.desc,o.create_time,m.tel as mtel,m.nick,m.signature,a.consignee,a.address,a.tel as atel,g.goods_id,g.goods_sum,sg.name as gname,sg.id as gid,at.attr_goods_attr_id,e.name,e.code')->where($where)->find();
		}
		
		foreach($data as $value){
			$attrData    = $AttrValue->field('attr_goods_attr_id')->where(" goods_attr_id in({$data['attr_goods_id']})")->select();
			$goodsData   = $goods->field('name')->where(" id in({$data['goods_id']})")->select();
		}
		//组装商品名称
		foreach($goodsData as $key=>$value){
			$goodsName[]  =$value['name'];
		}
		// foreach($attrData as $key=>$value){
			// $goodsData[] =$value['attr_goods_attr_value'];
		// }
		$data['gname'] = implode(';',$goodsName);
		
		return $data;
		
	}
	public function save_data($table,$idName,$id,$data){
			$model                   =M($table);
			
			
			$where                   = " $idName = $id ";
			
			$res                     =$model->where($where)->save($data);
			
			return $model->_sql();
	}
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
			
			$path	= 'http://sw.rscne.com/Public/'.$info['savepath'].$info['savename'];
			
			
			
			return $path;
	}
}
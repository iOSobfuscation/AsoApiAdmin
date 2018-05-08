<?php
/** 计划任务
 *  ditser
 *  2016-06-13
 */
namespace Plant\Controller;
use Plant\Controller\CommonController;

class TaskController extends CommonController {
	
	//奖品过期处理
	public function prize_overdue(){
		$actr_db			= D('activite_record');
		$now				= strtotime(date("Y-m-d",time()));//定义过期比照时间
		$actr_where			= "expiration_time <= $now";
		//echo $actr_where;
		$actr_data['status']= 2;
		$res				= $actr_db->where($actr_where)->field('status')->save($actr_data);
		if($res > 0){
			//记录日志
			$db								= D('loger');
			$data['model']					= "prize_overdue";
			$data['action']					= "过期时间处理";
			$data['info_id']				= '';
			$data['op_desc']				= "成功处理 $res 条记录";
			$data['op_id']					= 0;//系统自动执行
			$data['op_time']				= time();
			$data['op_id']					= realIp();
			$db->add($data);
		}
	}
	
	//晒单提醒
	public function sun_remind(){
		$order_db					= D('order');
		$user_db					= D('member');
		$error_db					= D('errorlog');
		$log_db						= D('loger');
		//定义发送比照时间
		//取出今天零点时间
		$nowz						= strtotime(date('Y-m-d', time()));
		//取出昨天天零点时间
		$nowz						= strtotime(date('Y-m-d', strtotime("-1 day")));
		//完成时间小于今天0点，为晒单并且未发送过通知
		//$order_where				= "pay = 0 and delivery = 2 and model = 3 and is_sun = 1 and complete_time<=$nowz and sun_notice = 1 and user_id != 0 and user_id != ''"; 
		$order_where				= "user_id = 199"; 
		$user_ids_arr				= $order_db->where($order_where)->group('user_id')->getField('user_id', true);
		//print_r($user_ids_arr);
		$user_ids_str				= implode(',', $user_ids_arr);
		//取出所有需要发送通知的openid
		$user_where					= "id in (".$user_ids_str.")";
		$open_id_arr				= $user_db->where($user_where)->getField('open_id', true);
		$open_id_len				= count($open_id_arr);
		
		foreach($open_id_arr as $open_id){
			$po_data	= '{"touser":"'.$open_id.'",
						  "template_id":"fZjkl35PCVrR0Iu-rOoie1D8DkryZp7vbziQHEHbEHM",
						  "url":"http://n.xiongdada.com.cn/Mshop/user/prize_index/state/2",            
						  "data":{
							  "first":{"value":"你好！您获得商品已成功签收，请晒单评价拼人品！","color":"#173177"},
							 
							  "remark": {"value":"点击晒单","color":"#173177"}}}';
	  		$po_res					= po_msg($po_data);
			//echo $po_res;
			$po_res_arr				= json_decode($po_res, true);
			if($po_res_arr['errcode'] == 0){
				$data['model']					= "sun_remind";
				$data['action']					= "发送晒单提醒通知";
				$data['info_id']				= '';
				$data['op_desc']				= "成功给 $open_id 发送1条记录，消息ID为:".$po_res_arr['msgid'];
				$data['op_id']					= 0;//系统自动执行
				$data['op_time']				= time();
				$data['op_id']					= realIp();
				$log_db->add($data);
			}else{
				$error_data['user_id']	= $open_id;
				$error_data['type']		= 4;
				$error_data['status']	= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= $po_res;
				$error_db->add($error_data);
			}
		}
	}
	
	//过期提醒
	public function overdue_remind(){
		$actr_db					= D('activite_record');
		$user_db					= D('member');
		$error_db					= D('errorlog');
		$log_db						= D('loger');
		//定义发送比照时间
		//取出明天零点时间
		$tz							= strtotime(date('Y-m-d', strtotime("+1 day")));
		//取出后天零点时间
		$hz							= strtotime(date('Y-m-d', strtotime("+2 day")));
		
		//完成时间小于今天0点，为晒单并且未发送过通知
		$actr_where					= "status = 1 and expiration_time>=$tz and expiration_time<=$hz and userid != 0 and userid != ''"; 
		//$actr_where					= "userid = 199"; 
		$user_ids_arr				= $actr_db->where($actr_where)->group('userid')->getField('userid', true);
		//echo $actr_db->getlastsql();
		//print_r($user_ids_arr);
		//exit;
		$user_ids_str				= implode(',', $user_ids_arr);
		//取出所有需要发送通知的openid
		$user_where					= "id in (".$user_ids_str.")";
		
		$open_id_arr				= $user_db->where($user_where)->getField('open_id', true);
		
		foreach($open_id_arr as $open_id){
			$po_data	= '{"touser":"'.$open_id.'",
						  "template_id":"fZjkl35PCVrR0Iu-rOoie1D8DkryZp7vbziQHEHbEHM",
						  "url":"http://n.xiongdada.com.cn/Mshop/user/prize_index/state/0",            
						  "data":{
							  "first":{"value":"你好！您获得商品还没有领取，即将在一天后失效，请尽快进行领取！","color":"#173177"},
							 
							  "remark": {"value":"点击领取","color":"#173177"}}}';
	  		$po_res					= po_msg($po_data);
			//echo $po_res;
			$po_res_arr				= json_decode($po_res, true);
			
			if($po_res_arr['errcode'] == 0){
				$data['model']					= "overdue_remind";
				$data['action']					= "发送过期提醒通知";
				$data['info_id']				= '';
				$data['op_desc']				= "成功给 $open_id 发送1条记录，消息ID为:".$po_res_arr['msgid'];
				$data['op_id']					= 0;//系统自动执行
				$data['op_time']				= time();
				$data['op_id']					= realIp();
				$log_db->add($data);
			}else{
				$error_data['user_id']	= $open_id;
				$error_data['type']		= 5;
				$error_data['status']	= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= $po_res;
				$error_db->add($error_data);
			}
		}
	}
	
	//发货后15天自动确认收货
	public function auto_receipt(){
		$order_db								= D('order');
		$error_db								= D('errorlog');
		$log_db									= D('loger');
		//确定14天之前的时间
		$reference_time							= strtotime(date('Y-m-d', strtotime("+14 day")));
		$order_where							= "model = 3 and delivery = 0 and exp_time=>$reference_time";
		$order_data['delivery']					= 2;
		$order_data['complete_time']			= time();
		$order_data['complete_type']			= 1;
		$order_res								= $order_db->where($order_where)->save($order_data);
		if($order_res){
			//记录日志
			$data['model']						= "auto_receipt";
			$data['action']						= "自动确认收货";
			$data['info_id']					= '';
			$data['op_desc']					= "成功确认收货 $order_res 个订单";
			$data['op_id']						= 0;//系统自动执行
			$data['op_time']					= time();
			$data['op_id']						= realIp();
			$log_db->add($data);
		}else{
			$error_data['user_id']				= 0;//没有用户
			$error_data['type']					= 6;
			$error_data['status']				= 1;
			$error_data['time']					= time();
			$error_data['ip']					= realIp();
			$error_data['info']					= "确认收货错误";
			$error_data['sql']					= $order_db->getlastsql();
			$error_db->add($error_data);
		}
	}
	
	
}
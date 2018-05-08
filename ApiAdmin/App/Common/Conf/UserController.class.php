<?php
/** 会员中心
 *  ditser
 *  2015-06-13
 */
namespace Mshop\Controller;
use Mshop\Controller\CommonController;

class UserController extends CommonController {
	
	//确认订单信息
	public function index(){
		exit('sss');
		$userid				= session('user_id');//获取$userid

		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		//echo $userid;
		$openid				= session('openid');
		
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		//获取用户信息
		set_wx_user_info($openid, $userid);
		
		//取出用户信息,包括头像、昵称、积分等
		$user_info					= user_info();
		
		if(count($add_info) 		== 0){
			$add_info				= 0;
		}
		$usercount			= usercount($userid);//邀请会员的数量
		//dump($usercount);
		//菜单中的分类
		$menu_cate_list				= gcat_list(0);
		
		//取出当前购物车内商品数量数量
		$cart_sum					= cart_sum();
		
		//取出未付款订单数量
		$duepay_order				= order_sum(1);
		
		//取出待收货订单数量		
		$payments_order				= order_sum(3);
		
		//取出已完成订单数量		
		$finished_order				= order_sum(4);
		
		$this->assign('usercount', intval($usercount));
		
		$this->assign('menu_cate_list', $menu_cate_list);
		$this->assign('duepay_order', $duepay_order);
		$this->assign('payments_order', $payments_order);	
		$this->assign('finished_order', $finished_order);
		
		$this->assign('user_bg', $user_bg);
		$this->assign('userid', $userid);
		$this->assign('user', $user_info);
		$this->assign('cart_sum', $cart_sum);
		$this->display('index');
		
	}
	
	//变更信息
	public function order_list($type){
		
		$order_list				= order_list($type);
		$province				= get_field('member_address', 'mp_', 'province', 'id = 15');
		//echo $province."<br>";
		//print_r($order_list);
		//exit;
		$user_bg				= get_field("set", "mp_", "user_bg", "id = 1");
		
		if($type == 1){
			$header				= "待支付订单";
			$status_name		= "付款";
			$status_url			= "/Mshop/user/order_staus/pay";
		}elseif($type == 3){
			$header				= "待收货订单";
			$status_name		= "确认收货";
			$status_url			= "/Mshop/user/order_staus/query";
		}elseif($type == 4){
			$header				= "已完成订单";
		}
		
		//菜单中的分类
		$menu_cate_list			= gcat_list(0);
		
		//购物车中商品数量
		$cart_sum				= cart_sum();
		
		$this->assign('menu_cate_list', $menu_cate_list);
		$this->assign('user_bg', $user_bg);
		$this->assign('order_list', $order_list);
		
		$this->assign('cart_sum', $cart_sum);
		$this->assign('header', $header);
		$this->display();
		
	}
	
	//订单详情
	public function order_info($oid){
		
		$user_bg				= get_field("set", "mp_", "user_bg", "id = 1");
		//菜单中的分类
		$menu_cate_list			= gcat_list(0);
		$this->assign('menu_cate_list', $menu_cate_list);
		$this->assign('user_bg', $user_bg);
		$this->assign('oid', $oid);
		$this->display();
	}
	
	
	//活动商品列表
	public function prize_index(){
		
		$userid				= session('user_id');//获取$userid
		
		$type = (intval($_GET['type'])>=1 && intval($_GET['type'])<=3) ? intval($_GET['type']) : 1;
		
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		//echo $userid;
		
		//定义模块
		$model					= "user";
		$this->assign('model', $model);
		
		
		if($type<=1){
			//获取未领奖记录
			$st_where				= " and (status = 1 or status = 2)";
			$st_order				= "sign_time desc";
			$st_actrecd_list		= actrecd_list('', $userid, $st_where, $st_order);
			$this->assign('st_actrecd_list', $st_actrecd_list);
		}elseif($type==2){
		//获取已经领奖记录
			$end_where				= " and status = 0 and (deliver = 1 or deliver = 0)";
			$end_order				= "receive_time desc";
			$end_actrecd_list		= actrecd_list('', $userid, $end_where, $end_order);
			$this->assign('end_actrecd_list', $end_actrecd_list);
		
		}else{
			//获取待晒单领奖记录
			$sun_where				= " and status = 0 and deliver = 2";
			$sun_order				= "complete_time desc";
			$sun_actrecd_list		= actrecd_list('', $userid, $sun_where, $sun_order);
			$this->assign('sun_actrecd_list', $sun_actrecd_list);
		}
		$state					= $_GET['state'];
		
		if($state == ''){
			$state				= 1;
		}
		
		$this->assign('index', $state);
		$this->assign('type', $type);
		$this->display();
		
	}
	
	
	
	/**
	 * 晒单分享
	 */
	public function share(){
	
		$userid				= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
		}
		//echo $userid;
	
		//定义模块
		$model				= "share";
		$this->assign('model', $model);
	
		$db 		= D("sun");
		$data 	 	= $db->field("id,user_id,title,content,create_time,pic")->where("status=1 and user_id='$userid'")->order('id desc')->select();
		$user_db 	= D("member");
	
		$zan_db = D("sun_zan");
		$share_db = D("sun_share");
		$comment_db	= D("sun_comment");
	
		$res		= array();
		foreach ($data as $key=>$val){
			$user = $user_db->field("nick")->where("id={$val['user_id']}")->find();
			$res[$key]['nick'] 			= mb_substr($user['nick'], 0,3)."***";
			$res[$key]['title'] 		= $val['title'];
			$res[$key]['content'] 		= $val['content'];
			$res[$key]['create_time'] 	= date("Y-m-d H:i",$val['create_time']);
			$res[$key]['pic'] 			= json_decode($val['pic'],true);
			$res[$key]['id'] 			= $val['id'];
			$res[$key]['zan'] 			= $zan_db->where("sun_id={$val['id']}")->count();
			$res[$key]['share'] 		= $share_db->where("sun_id={$val['id']}")->count();
			$res[$key]['comment'] 		= $comment_db->where("sun_id={$val['id']}")->count();
		}
	
		$this->assign("list",$res);
		$this->display();
	}
	
	
	/**
	 * $cid 也就是期数的id 例 21
	 * $qi 是期数 1期
	 */
	public function act_list($cid=21){
		$userid				= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid				= session('user_id');
		}
		
		$activite_db = D("activite");
		$qi = $activite_db->field("name")->where("id='$cid'")->find();
		
		$activite_record_db = D("activite_record");
		$data 	 	= $activite_record_db->alias("o")->field("o.id,o.activite,o.sign_time,o.goods_id, g.name,g.price,g.mak_price,g.thumb")->join("mp_goods g on g.id=o.goods_id","LEFT")->where("o.userid='$userid' and o.activite='$cid'")->order('o.id desc')->select();
		//echo $activite_record_db->getLastSql();		
		/* echo "<pre>";
		dump($data);
		echo "</pre>"; */
		$this->assign("qi",$qi['name']);
		$this->assign("list",$data);
		$this->display();
	}
	
	
	//我的二维码界面
	public function share_code(){
		
		//定义模块
		$model						= "user";
		$this->assign('model', $model);
		
		$userid						= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid					= session('user_id');
		}
		//$member_code				= D('member')->where(array('id'=>$userid))->getField('code',1);
		//$agency_url					= "http://".C('HOST_NAME')."/Mshop/agency/record/introduce/$userid";
		//生成用户自己的二维码
		//if(empty($member_code)){
			//getcode(1, $userid);
		//}
		//获取用户二维码
		$db							= D('member');
		$where						= array('id'=>$userid);
		$user_code					= $db->where($where)->getField('code', 1);
		
		if(empty($user_code)){getcode(1, $userid);}
		
		//$share_url					= get_url();
		//$signature 					= getsignature_new($share_url);
		
		//$this->assign("signature",$signature);
		//$this->assign('agency_url', $agency_url);
		$this->assign('user_code', $user_code);
		$this->display();
	}
	
	
	//分享赚钱页面
	public function make_money(){
		
		//session('user_id', '127');
		
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		$userid				= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		$usercount			= usercount($userid);//邀请会员的数量
		$user_commission	= user_commission($userid);//当前未提现佣金
		
		$agency_url			= "http://".C('HOST_NAME')."/Mshop/agency/record/introduce/$userid";//分享点击跳转地址
		
		//$share_url			= get_url();
		//$signature 			= getsignature_new($share_url);
		
		$this->assign("signature",$signature);
		
		$this->assign('agency_url', $agency_url);
		$this->assign('userid', $userid);
		$this->assign('usercount', $usercount);
		$this->assign('user_commission', $user_commission);
		$this->display();
		
	}
	//邀请记录
	public function invent_record(){
		
		//session('user_id', '89');
		
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		$userid				= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		
		$now				= time();//当前时间
		
		$tf_time			= $now-2592000;//三月前的时间
		
		$member_list		= rmember_list(" and create_time >= $tf_time and create_time <= $now and recommenders = $userid");
		
		if(empty($member_list)){
			$member_list	= 0;
		}
		
		$usercount			= usercount($userid);//邀请会员的数量
		
		$this->assign('member_list', $member_list);
		$this->assign('usercount', $usercount);
		$this->display();
		
	}
	
	//佣金获得记录
	public function cmor_detail(){
		//session('user_id', '89');
		
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		$userid				= session('user_id');//获取$userid
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		$now				= time();//当前时间
		
		$tf_time			= $now-2592000;//三月前的时间
		
		$commission_record	= user_commission_record("create_time >= $tf_time and create_time <= $now and user_id = $userid");
		
		if(empty($commission_record)){
			$commission_record	= 0;
		}
		
		
		$user_commission	= user_commission($userid);//当前未提现佣金
		
		$this->assign('commission_record', $commission_record);
		$this->assign('user_commission', $user_commission);
		$this->display();
	}
	
	//提现记录
	public function pc_list(){
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		$userid				= session('user_id');//获取$userid
		
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		
		$now				= time();//当前时间
		
		$tf_time			= $now-2592000;//三月前的时间
		
		$pc_record			= pc_record($userid, " and create_time >= $tf_time and create_time <= $now");
		
		if(empty($pc_record)){
			$pc_record	= 0;
		}
		
		//佣金余额
		$user_commission			= user_commission($userid);//邀请会员的数量
		$this->assign('commission_record', $commission_record);
		$this->assign('pc_record', $pc_record);
		$this->assign('user_commission', $user_commission);
		$this->display();
		
		
	}
	//提现处理
	public function past_cath(){
		
		//定义模块
		$model				= "user";
		$this->assign('model', $model);
		
		$userid				= session('user_id');//获取$userid
		
		//判断userid是否存在
		if(empty($userid)){
			get_userid();
			$userid			= session('user_id');
		}
		$user_commission	= user_commission($userid);//当前未提现佣金
		
		
		//判断是否满足提现条件
		if($user_commission < 10){
			$past_cath_staus	= 1;
		}else{
			$past_cath_staus	= 0;
			//取出用户银行卡信息
			$user_account		= user_account($userid);
			if(empty($user_account)){
				$user_account_status	= 1;
			}else{
				$user_account_status	= 0;
			}
		}
		
		if(IS_POST){
			
			$amount			= $_POST['amount'];
			$signature		= $_POST['signature'];
			$bank			= $_POST['bank'];
			$card_number	= $_POST['card_number'];
			$tel			= $_POST['tel'];
			$past_type		= $_POST['past_type'];
			
			if(empty($past_type)){
				$res['status']		= 12;
				$res['msg']			= "请填写提现类型";
				$ck_res				= json_encode($res);
				echo $ck_res;
				exit;
			}
			
				
			if(empty($amount)){
				$amount		= $user_commission;
			}elseif($amount < 10){
				$res['status']		= 11;
				$res['msg']			= "提现金额不能小于10";
				$ck_res				= json_encode($res);
				echo $ck_res;
				exit;
			}elseif($amount > $user_commission){
				$res['status']		= 11;
				$res['msg']			= "提现金额不能超过可提现金额，您的可提现金额为".$user_commission;
				$ck_res				= json_encode($res);
				echo $ck_res;
				exit;
			}
			if($past_type == 2){
				if(empty($signature)){
					$res['status']		= 2;
					$res['msg']			= "请填写真实姓名";
					$ck_res				= json_encode($res);
					echo $ck_res;
					exit;
				}
				if(empty($bank)){
					$res['status']		= 3;
					$res['msg']			= "开户行填写错误";
					$ck_res				= json_encode($res);
					echo $ck_res;
					exit;
				}
				if(empty($card_number)){
					$res['status']		= 4;
					$res['msg']			= "银行卡号填写错误";
					$ck_res				= json_encode($res);
					echo $ck_res;
					exit;
				}
				if(empty($tel) || check_submit($tel, 'phone') == 0){
					$res['status']		= 5;
					$res['msg']			= "请留下真实电话";
					$ck_res				= json_encode($res);
					echo $ck_res;
					exit;
				}
			}
			
			$w_data['user_id']			= $userid;
			$w_data['wamount']			= $amount;
			$w_data['create_time']		= time();
			$w_data['status']			= 1;
			$w_data['wamount_sn']		= build_order_no();//提现编号
			
			
			$a_data['user_signature']	= $signature;
			$a_data['user_bank']		= $bank;
			$a_data['user_card_number']	= $card_number;
			$a_data['user_tel']			= $tel;
			
			if($past_type == 1){
				$w_data['account_time']		= time();
				$w_data['status']			= 5;
			}
			
			$mw_db						= D('member_withdrawals');
			$ma_db						= D('member_account');
			$m_db						= D('member');
			
			
			//插入提现记录表
			$mw_id						= $mw_db->add($w_data);
			//
			
			if($mw_id){
				//更新账户表
				if($user_account_status == 1){
					$a_data['user_id']	= $userid;
					$mw_id				= $ma_db->add($a_data);
				}else{
					$mw_where			= array('user_id'=>$userid);
					$mw_id				= $ma_db->where($mw_where)->save($a_data);
				}
				
				//更新会员可提现金额
				$m_db->where(array('id'=>$userid))->setDec('not_mentioned', $amount);
				if($past_type == 1){
					//更新账户余额
					$m_db->where(array('id'=>$userid))->setInc('balance', $amount);
				}
				
				$res['status']		= 0;
				$res['msg']			= "申请成功";
				$ck_res				= json_encode($res);
				echo $ck_res;
				exit;
			}else{
				$res['status']		= 10;
				$res['msg']			= "数据错误请重新退出重新提交申请".$db->getlastsql();;
				$ck_res				= json_encode($res);
				echo $ck_res;
				exit;
			}
			
		}else{
			$this->assign('ub', $user_account);
			$this->assign('past_cath_staus', $past_cath_staus);
			$this->assign('user_commission', $user_commission);
			$this->display();
		}
	}
	
}
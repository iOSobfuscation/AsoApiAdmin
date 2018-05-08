<?php
/** 微商模块方法文件
 *  ditser
 *  2014-10-20
 */

namespace Mshop\Controller;
use Mshop\Controller\CommonController;
/**
 * 获取数据字典
 * @param $key      //键值，方便查找数据
 * @param $fileName //字典文件名 目录Common/Dict/
 * @return mixed
 */
function dict($key = '', $fileName = 'Setting') {
    static $_dictFileCache  =   array();
    $file = MODULE_PATH . 'Common' . DS . 'Dict' . DS . $fileName . '.php';
    if (!file_exists($file)){
    	unset($_dictFileCache);
    	return null;
    }
    if(!$key && !empty($_dictFileCache)) return $_dictFileCache;
    if ($key && isset($_dictFileCache[$key])) return $_dictFileCache[$key];
    $data = require_once $file;
    $_dictFileCache = $data;
	return $key ? $data[$key] : $data;
}


/** 
 * @desc  im:字符截取 
 * @param (string)$char 字符 
 * return 返回：十进制数 
 */
function sub_str($str, $length = 0, $append = true)
{
    $str = trim($str);
    $strlength = strlen($str);

    if ($length == 0 || $length >= $strlength)
    {
        return $str;  //截取长度等于0或大于等于本字符串的长度，返回字符串本身
    }
    elseif ($length < 0)  //如果截取长度为负数
    {
        $length = $strlength + $length;//那么截取长度就等于字符串长度减去截取长度
        if ($length < 0)
        {
            $length = $strlength;//如果截取长度的绝对值大于字符串本身长度，则截取长度取字符串本身的长度
        }
    }

    if (function_exists('mb_substr'))
    {
        $newstr = mb_substr($str, 0, $length, EC_CHARSET);
    }
    elseif (function_exists('iconv_substr'))
    {
        $newstr = iconv_substr($str, 0, $length, EC_CHARSET);
    }
    else
    {
        //$newstr = trim_right(substr($str, 0, $length));
        $newstr = substr($str, 0, $length);
    }

    if ($append && $str != $newstr)
    {
        $newstr .= '...';
    }
    return $newstr;
}

/**
 * @desc  im:验证验证码是否有效
 * @param (string)$ckcode 三十六进制的验证码 
 * return 返回：成功返回手机号，不成功返回false 
 * */

function ck_ckcode($ckcode){
	if(empty($ckcode) || mb_strlen($ckcode) != 7){
		return false;
	}
	$ck_db = M('invitecode','am_');
	$where = "isused = 1 and code = '$ckcode'";
	$inved_res = $ck_db->where($where)->field('phone')->find();

	$inved_phone = $inved_res['phone'];

	//按用户手机号生成验证码对比看是否一致
	$is_checkcode = c_checkcode_capital($inved_phone);
	$ckcode       = $ckcode;
	$resl 		  = strcmp($is_checkcode,$ckcode);	
	if($resl == 0){
		return $inved_phone;
	}else{
		return false;
	}
}

/**
 * @desc  im:验证活动是否有效
 * @param (string)$act_id 活动ID 
 * return 返回：成功返回活动ID，不成功返回false 
 * */

function ck_activity($act_id){
	if(empty($act_id)){
		return false;
	}
	$now_time = strtotime(date('Y-m-d H:i:s'));
	$table    	  = 'activity';
	$prefix   	  = 'srv_';
	$field    	  = 'id';
	
	//取出活动的有效期类型
	$where_time	  = " id = '$act_id'";
	$Act_timetype = 'Act_timetype';
	$Act_timetype = get_field($table,$prefix,$Act_timetype,$where_time);
	if($Act_timetype == '1'){
		$where    = " id = '$act_id' and Act_starttime < '$now_time' and Act_endtime > '$now_time'";
	}elseif($Act_timetype == '2'){
		$where    = " id = '$act_id'";
	}else{
		return false;
	}
	
	$act_id   	  = get_field($table,$prefix,$field,$where);
	if($act_id){
		return $act_id;
	}else{
		return "1";
	}
}

/**
 * @desc  im:获取单个表单数据
 * @param (string)$table  需要获取的数据的表名，不能为空
 * @param (string)$prefix 需要获取的数据的前缀，不能为空
 * @param (string)$field  需要获取的数据的字段名，不能为空 
 * @param (string)$where  需要获取的数据的条件，不能为空
 * return 返回：成功返回手机号，不成功返回false 
 * */

function get_field($table,$prefix,$field,$where){
	if(empty($where) || empty($field) || empty($table) || empty($prefix)){
		return false;
	}
	$ck_db  = M($table,$prefix);
	$ck_res = $ck_db->where($where)->field($field)->find();
	//echo $ck_db->getlastsql();
	//exit;
	$field  = $ck_res[$field];
	if($ck_res){
		return $field;
	}else{
		return false;
	}
}

/**
 * @desc  im:变更某个表单的数据
 * @param (string)$table  需要变更的数据的表名，不能为空
 * @param (string)$prefix 需要变更的数据的前缀，不能为空
 * @param (string)$data  需要变更的数据的字段名，不能为空 
 * @param (array)$where  需要变更的数据的条件，不能为空
 * return 返回：成功返回手机号，不成功返回false 
 * */

function update_field($table,$prefix,$data,$where){
	if(empty($where) || empty($data) || empty($table) || empty($prefix)){
		return false;
	}
	$db  = M($table,$prefix);
	$res = $db->where($where)->save($data);

	if($res){
		return true;
	}else{
		return false;
	}
}

/**
 * @desc  im:插入某个表单的数据
 * @param (string)$table  需要变更的数据的表名，不能为空
 * @param (string)$prefix 需要变更的数据的前缀，不能为空
 * @param (string)$data  需要变更的数据的字段名，不能为空 
 * @param (array)$where  需要变更的数据的条件，不能为空
 * return 返回：成功返回手机号，不成功返回false 
 * */

function insert_field($table,$prefix,$data,$where){
	if(empty($where) || empty($data) || empty($table) || empty($prefix)){
		return false;
	}
	$db  = M($table,$prefix);
	$res = $db->add($data);

	if($res){
		return true;
	}else{
		return false;
	}
}





/**
 * @desc  im:注册新用户
 * @param (string)$phone  手机号
 * @param (int)   $act_id 活动ID
 * @param (int)	  $staffid   员工ID 
 * return 返回：成功返回用户手机号，不成功返回false 
 * */

function regist_activity($phone,$act_id,$staffid){
	
	if(empty($phone)){
		$msg = 1;//手机号不能为空
		return $msg;
	}elseif(empty($act_id)){
		$msg = 2;//活动ID不能为空
		return $msg;
	}
	
	//判断手机号是否已经注册
	if(get_field('user','am_','id',"loginName = '$phone'")){
		$msg = 6;//手机号已经被注册
		return $msg;
	}
	$user_passwd 		= randomkeys('6','NUMBER');//获得六位随机密码
	$user_pwd	  	    = md5($user_passwd);			
	//声明模型
	$users_db 		    = M('user','am_');
	$invit_db 		    = M('invitecode','am_');
	$activity_user_db   = M('activity_user','srv_');
	$activity_recode_db = M('activity_recode','srv_');
	$activity_db        = M('activity','srv_');
	
	//用户注册数据组装
	$users_data			      = array();
	$users_data['loginName']  = $phone;
	$users_data['pwd']		  = $user_pwd;
	$users_data['createtime'] = date('Y-m-d h:i:s',time());
	//注册
	$user_id 				  = $users_db->add($users_data);
	if($user_id){
		//调用短信接口发送通知
		$notic  	  = '【美吧】'.$phone.'您好,您的美吧密码为:'.$user_passwd.'请不要告诉任何人，登录后及时修改,美吧下载地址:http://api.mei.ba/index.php/Index/d?mid=203';//通知内容
		$notic_reslut = SendMsgBy1065($phone,$notic);//发送短信
		
		//插入活动引入人数表
		$activity_user_data['Actu_uid']     = $user_id;
		$activity_user_data['Actu_actid']   = $act_id;
		$activity_user_data['Actu_staffid'] = $staffid;
		$activity_user_data['Actu_ctime']   = strtotime(date('Y-m-d H:i:s'));
		if(!$activity_user_db->add($activity_user_data)){
			$msg = 3;//插入活动成绩详细表失败
			return $msg;
		}

		//插入活动成绩表
		//取出值
		if(!get_field('activity_recode','srv_','Actr_id',"Actr_actid = '$act_id'")){
			$activity_r_data['Actr_staffid'] = $staffid;
			$activity_r_data['Actr_actid']   = $act_id;
			$activity_r_data['Actr_usernum'] = 1;
			$activity_r_data['Actr_ctime']   = strtotime(date('Y-m-d H:i:s'));
			if(!$activity_recode_db->add($activity_r_data)){
				$msg = 4;//插入活动成绩表失败
				return $msg;
			}
		}
		else{
			$Actr_usernum					 = get_field('activity_recode','srv_','Actr_usernum',"Actr_actid = '$act_id'");
			$activity_r_data['Actr_staffid'] = $staffid;
			$activity_r_data['Actr_actid']   = $act_id;
			$activity_r_data['Actr_usernum'] = $Actr_usernum+1;
			$activity_r_data['Actr_ctime']   = $now_time = strtotime(date('Y-m-d H:i:s'));
			if(!update_field('activity_recode','srv_',$activity_r_data,"Actr_actid = '$act_id'")){
				$msg = 7;//更新活动成绩表失败
				return $msg;
			}
		}
		$msg = 0;
		return $msg;
		
		//赠送给邀请人好处(改成由登录后获取)
		//$gp_uid  	  = get_field('invitecode','am_','uid','phone = '.$inved_phone);//获取邀请人ID
		//give_coupons($gp_uid);

		//赠送给被邀请人优惠券
		give_coupons($user_id,1);
	}else{
		$msg = 5;
		return $msg;
	}
}

/**
 * @desc  赠送优惠券
 * @param $uid 被赠送人的ID
 * @param $act_id 活动ID
 * @param $couponid 优惠券ID 
 * return true false 
 * */
function give_coupons($uid, $act_id, $couponid){
	if(empty($uid)){
		return false;
	}
	
	if(get_field('invitecode','am_','id',"id = '$uid'")){
		if($act_id == 1){
			$coupon_db    			= M('coupons_recode','am_');
			$data_coupon 			= array();
			$data_coupon['uid']   	= $uid;
			$data_coupon['price'] 	= '50';
			$data_coupon['isUsed']  = '0';
			$data_coupon['usesid']  = '';
			$data_coupon['uid']   	= $uid;
			$data_coupon['ctime']   = date('Y-m-d h:i:s',time());
			$uid 					= $coupon_db->add($data_coupon);
			
			$data_coupon['uid']   	= $uid;
			$data_coupon['price'] 	= '198';
			$data_coupon['isUsed']  = '0';
			$data_coupon['usesid']  = '4,5';
			$data_coupon['uid']   	= $uid;
			$data_coupon['ctime']   = date('Y-m-d h:i:s',time());
			$uid 					= $coupon_db->add($data_coupon);
			return true;
		}
	}else{
		return false;
	}
}


/**
 * @desc  短信推送 http://sms.sp1065.cn/login!dologin
 * @param $phone 接收短信的手机号
 * @param $msg 发送的内容
 * return true false 
 * */
function SendMsgBy1065($phone,$msg){
	try {		
		$url="http://dx.sp1065.cn/sendsms?username=1072075&password=654321&order=sendsms";
		$url.="&phone=".$phone."&spnumber=221&msgcont=".$msg;
		
		$ch = curl_init($url) ;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
	    $result = curl_exec($ch) ;
	    $ergstr="<result>\s*0\s*</result>";
	    $smsMsg="url:".$url."<br>返回值：".$result;
	    WriteDebugLog("Home SendMsgBy1065", $smsMsg);
	    return ereg($ergstr,$result);
	} catch (Exception $e) {
		$msg = "<br>common.php->SendMsgBy1065上传时出现异常，异常信息：".$e->getTraceAsString();
		WriteErrorLog($msg);
		return false;
	}
}
/**
 * @access 写错误日志
 *要取得共享锁定（读取的程序），将 lock 设为 LOCK_SH（PHP 4.0.1 以前的版本设置为 1）
 *要取得独占锁定（写入的程序），将 lock 设为 LOCK_EX（PHP 4.0.1 以前的版本中设置为 2）。
 *要释放锁定（无论共享或独占），将 lock 设为 LOCK_UN（PHP 4.0.1 以前的版本中设置为 3）。
 *如果不希望 flock() 在锁定时堵塞，则给 lock 加上 LOCK_NB（PHP 4.0.1 以前的版本中设置为 4）。
 */
function WriteErrorLog($msg){
	$msg=str_replace("<\/", "</", $msg);
	$data="<br>时间：".date('Y-m-d H-i-s', time())."<br>".$msg."<br>";
	$dir=APP_ROOT.DIRECTORY_SEPARATOR."logs";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$dir.=DIRECTORY_SEPARATOR."Home";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$dir.=DIRECTORY_SEPARATOR."Error";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$file=$dir.DIRECTORY_SEPARATOR.date('Y-m-d', time()).".html";
	$file= str_replace("\\", "\\\\", $file);
	if(!file_exists($file))
		$data='<meta http-equiv="Content-Type" content="text/hml; charset=UTF-8">'."<fieldset>".$data."</fieldset>";
	else $data="<fieldset>".$data."</fieldset>";
	$f = fopen($file, 'a');
	if(!flock($f,LOCK_EX))
		return false;
	fwrite($f, $data);
	flock($f,LOCK_UN);//释放锁定
	fclose($f);
	return true;
}
/**
 * @access 写Debug日志
 *要取得共享锁定（读取的程序），将 lock 设为 LOCK_SH（PHP 4.0.1 以前的版本设置为 1）
 *要取得独占锁定（写入的程序），将 lock 设为 LOCK_EX（PHP 4.0.1 以前的版本中设置为 2）。
 *要释放锁定（无论共享或独占），将 lock 设为 LOCK_UN（PHP 4.0.1 以前的版本中设置为 3）。
 *如果不希望 flock() 在锁定时堵塞，则给 lock 加上 LOCK_NB（PHP 4.0.1 以前的版本中设置为 4）。
 */
function WriteDebugLog($msg){
	$msg=str_replace("<\/", "</", $msg);
	$data="<br>时间：".date('Y-m-d H-i-s', time())."<br>".$msg."<br>";
	$dir=APP_ROOT.DIRECTORY_SEPARATOR."logs";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$dir.=DIRECTORY_SEPARATOR."Home";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$dir.=DIRECTORY_SEPARATOR."Debug";
	if (!is_dir($dir)) {
		if(!mkdir($dir))
			return false;
	}
	$file=$dir.DIRECTORY_SEPARATOR.date('Y-m-d', time()).".html";
	$file= str_replace("\\", "\\\\", $file);
	if(!file_exists($file))
		$data='<meta http-equiv="Content-Type" content="text/hml; charset=UTF-8">'."<fieldset>".$data."</fieldset>";
	else $data="<fieldset>".$data."</fieldset>";
	$f = fopen($file, 'a');
	if(!flock($f,LOCK_EX))
		return false;
	fwrite($f, $data);
	flock($f,LOCK_UN);//释放锁定
	fclose($f);
	return true;
}



/**------------------------------------暂时用不到的方法-------------------------------------------------*/

/** 
 * @desc  im:生成一个随机的N位随机数
 * @param (int)$num 十进制数 
 * return 返回：三十六进制数小写 
*/
function get_code_lowercase($num) {  
	$num = intval($num);  
	if ($num <= 0)  
	return false;  
	$charArr = array("0","1","2","3","4","5","6","7","8","9",'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');  
	$char = '';  
	do {  
		$key = ($num - 1) % 36;  
		$char= $charArr[$key] . $char;
		$num = floor(($num - $key) / 36);  
	} 
	while ($num > 0);  
	return $char;  
}
 
/** 
 * @desc  im:十进制数转换成三十六进制数,理论上只要手机号不重复生成的字符就不重复
 * @param (int)$num 十进制数 
 * return 返回：三十六进制数大写 
*/ 
function get_code_capital($num) {  
	$num = intval($num);
	if ($num <= 0)  
	return false;  
	$charArr = array("0","1","2","3","4","5","6","7","8","9",'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');  
	$char = '';  
	do {  
		$key = ($num - 1) % 36;  
		$char= $charArr[$key] . $char;
		$num = floor(($num - $key) / 36);  
	} 
	while ($num > 0);
	return $char;  
}

/** 
 * @desc  im:生成随机验证码
 * @param (varchar)$tels 手机号一个字符串多个手机号用英文状态下的逗号隔开 
 * return 返回：三十六进制数 
*/ 
function c_checkcode_capital($tels){
	if(empty($tels)) return false;
	$tel = explode(',',$tels);
	$checkcode = "";
	$CommonController 		= new CommonController();	
	for($index=0;$index<count($tel);$index++){
		 
		$checkcode .= $CommonController->encodeID($tel[$index],7).",";//组装验证码以英文状态下的逗号隔开
		//$checkcode .= get_code_capital(intval($tel[$index])).",";//组装验证码以英文状态下的逗号隔开
	}
	$checkcode = substr($checkcode,0,strlen($checkcode)-1); //去掉最后一位逗号
	return $checkcode;
}

function c_checkcode_lowercase($tels){
	if(empty($tels)) return false;
	$tel = explode(',',$tels); 
	$checkcode = "";		
	for($index=0;$index<count($tel);$index++){ 
		 $checkcode .= get_code_lowercase((double)$tel[$index]).",";//组装验证码以英文状态下的逗号隔开
	}
	$checkcode = substr($checkcode,0,strlen($checkcode)-1); //去掉最后一位逗号
	return $checkcode;
}


/** 
 * @desc  im:生成随机的六位数 完全随机
 * @param (varchar)$tels 
 * return 返回：固定长度的随机数
*/
function randomkeys($len=6,$format='NUMBER'){ 
	switch($format) { 
		case 'ALL':
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~'; break;
		case 'CHAR':
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~'; break;
		case 'NUMBER':
		$chars='0123456789'; break;
		default :
		$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~'; 
		break;
	}
	mt_srand((double)microtime()*1000000*getmypid()); 
	$password="";
	while(strlen($password)<$len)
	  $password.=substr($chars,(mt_rand()%strlen($chars)),1);
	return $password;
}

/** 
 * @desc  im:生成密码2位的随机数加上六位验证码
 * @param (varchar)$tels 
 * return 返回：固定长度的随机数
*/
function c_passwd($tels){
	if(empty($tels)) return false;
	$tel = explode(',',$tels); 
	$passwd = "";
	for($index=0;$index<count($tel);$index++){ 
		 $passwd .= randomkeys(2).get_code_lowercase((double)$tel[$index]).",";//组装验证码以英文状态下的逗号隔开
	}
	$passwd = substr($passwd,0,strlen($passwd)-1); //去掉最后一位逗号
	return $passwd;
}
/** 
 * @desc  im:三十六进制数转换成十机制数 
 * @param (string)$char 三十六进制数 
 * return 返回：十进制数 
 */  
function get_num($char){  
	$array=array("0","1","2","3","4","5","6","7","8","9","A", "B", "C", "D","E", "F", "G", "H", "I", "J", "K", "L","M", "N", "O","P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y","Z");  
	$len=strlen($char);  
	for($i=0;$i<$len;$i++){  
		$index=array_search($char[$i],$array);  
		$sum+=($index+1)*pow(36,$len-$i-1);  
	}  
	return $sum;  
}
/*--------订单类方法------*/
/**
 * @desc  im:创建订单
 * @param (string)$table  需要变更的数据的表名，不能为空
 * @param (string)$prefix 需要变更的数据的前缀，不能为空
 * @param (string)$data  需要变更的数据的字段名，不能为空 
 * @param (array)$where  需要变更的数据的条件，不能为空
 * return 返回：成功返回手机号，不成功返回false 
 * */

function create_order($goods_id, $user_id, $agency_id, $buy_type){
	if(empty($goods_id) || empty($$user_id) || empty($agency_id) || empty($buy_type)){
		return false;
	}
	
	$pay_total  	      = get_field('goods','ms_','price',"id = '$goods_id'");
	$data['goods_id']  	  = $goods_id;
	$data['user_id'] 	  = $user_id;
	$data['pay_total']	  = $pay_total;
	$data['payed'] 		  = $payed;
	$data['pay_free']	  = $pay_free;
	$data['buy_type']	  = $buy_type;
	$data['agency_id'] 	  = $agency_id;
	$data['pay_total'] 	  = $pay_total;
	
	$data['order_status'] = 0;
	$data['exp_status']   = 0;
	$data['buy_type'] 	  = $buy_type;
	$data['create_time']  = strtotime(date('Y-m-d H:i:s',time()));
	$data['order_id']     = build_order_no();
	if($buy_type == 1){
		$data['pay_status'] = 1;
		$data['pay_type']   = 0;
		$data['pay_free']   = 0;
		$data['payed']   	= $pay_total;
		$data['pay_time']   = strtotime(date('Y-m-d H:i:s',time())) ;
	}
	
	$order_db   = M('order', 'ms_');
	$res  = $order_db->add($data);
	
	if($res){
		return true;
	}else{
		return false;
	}
}


/*--------列表数据----------*/


/*轮播列表方法
 * @function carousel_list
 
 * return 返回：成功返回list，不成功返回false
*/
function carousel_list($type){
	
	$db			= D('carousel');
	$now		= time();
	$where		= " is_del = 1";// and start_time <= '$now' <= end_time ";
	if(!empty($type)){
		$where	.= " and cat_id = ".$type;
	}
	$order		= " `order` desc ";
	$res		= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']			= $val['id'];
		$list[$key]['desc']			= $val['desc'];
		$list[$key]['thumb']		= $val['thumb'];
		$list[$key]['link']			= $val['link'];
		$list[$key]['start_time']	= $val['start_time'];
		$list[$key]['end_time']		= $val['end_time'];
		$list[$key]['create_time']	= $val['create_time'];
		
	}
	return $list;
}



/*---------商品类方法----------*/

/* 首页商品列表方法
 * @function shop_index
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function goods_index(){
	
	$gdb		= M('goods', "mp_");
	$cdb		= M('goods_category', "mp_");
	$order		= "`order`";
	$cwhere		= " is_home = 0 ";
	$list		= $cdb->where($cwhere)->order($order)->select();
	
	foreach($list as $ckey => $cval){
		
		$clist[$ckey]['id']							= $cval['id'];
		$clist[$ckey]['name']						= $cval['name'];
		$clist[$ckey]['thumb']						= $cval['thumb'];
		
		$cat_id										= $cval['id'];
		
		$gwhere										= " cat_id = '$cat_id' and is_shelves = 0 and is_home = 0 ";
		$glist										= $gdb->where($gwhere)->limit(2)->order($order)->select();
	
		foreach($glist as $gkey => $gval){
			
			$list[$ckey]['goods'][$gkey]['id']		= $gval['id'];
			$list[$ckey]['goods'][$gkey]['name']	= $gval['name'];
			$list[$ckey]['goods'][$gkey]['thumb']	= $gval['thumb'];
			
		}
	}
	
	return $list;
}

/* 商品详情
 * @function shop_index
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function goods_info($id){
	
	$db				= M('goods', "mp_");
	
	$where			= "is_del = 1 and id = ".$id;
	$info			= $db->where($where)->find();
	
	if($info['is_recd'] == 0){
		$info['chc']	= "推荐";
	}elseif($info['is_hot'] == 0){
		$info['chc']	= "热卖";
	}
	
	return $info;
}

/* 商品详情
 * @function shop_index
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function pgoods_info($id){
	
	$db			= M('pgoods', "mp_");
	
	$where		= "is_del = 1 and id = ".$id;
	$info		= $db->where($where)->find();
	
	return $info;
}

/* 商品属性
 * @function shop_index
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function attr_info($id){
	
	$db			= M('attr_liquor', "mp_");
	
	$where		= "goods_id = ".$id;
	$info		= $db->where($where)->find();
	
	return $info;
}

/* 商品列表
 * @function goods_list
 * @param String $where 条件
 * @param int $limit 数量
 * return 返回：成功返回list，不成功返回false
*/
function goods_list($where, $limit){
	
	$db								= M('goods', "mp_");
	
	$where							= "is_del = 1".$where;
	$order							= "`order` desc";
	$list							= $db->where($where)->order($order)->field("id, name, price, thumb, is_home, is_recd, is_hot, desc, stock, end_num")->limit($limit)->select();
	
	//echo $db->getlastsql();
	//exit;
	$wishlistdb = D("wishlist");//心愿单 申卫帅添加 2016-06-23	
	foreach($list as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['mak_price']	= $val['mak_price'];
		$list[$key]['desc']			= $val['desc'];
		$list[$key]['thumb']		= $val['thumb'];
		$list[$key]['stock']		= $val['stock'];
		$list[$key]['end_num']		= $val['end_num'];
		$list[$key]['surplus']		= $list[$key]['stock']-$val['end_num'];
		$list[$key]['baifen']		= $val['end_num']/$list[$key]['stock']*100;
		
		//取出当前购物车中数量
		$cart_db					= D('cart');
		$user_id					= session('user_id');
		$cart_where					= "user_id = ".$user_id." and goods_id = ".$val['id'];
		$list[$key]['goods_sum']	= $cart_db->where($cart_where)->sum('sum');
		if(!$list[$key]['goods_sum']){
			$list[$key]['goods_sum']= '0';
		}
		
		if($val['is_recd'] == 1){
			$list[$key]['attribute']= '推荐';
		}elseif($val['is_hot'] == 1){
			$list[$key]['attribute']= '热卖';
		}
		$list[$key]['attribute']= '推荐';
		//取出相册
		$album_info					= album_info($val['id']);
		//print_r($album_info);
		//exit;
		$list[$key]['album']		= $album_info['img_list'];
		
		$list[$key]['wishlist']		= 0; //是否已经加入心愿单 申卫帅 2016-06-23
		$wishlist = $wishlistdb->where("good_id='{$val['id']}' and user_id='{$user_id}'")->find(); //心愿单 申卫帅 2016-06-23
		if(isset($wishlist['id']) && $wishlist['id']){
			$list[$key]['wishlist'] = 1; //是否已经加入心愿单 申卫帅 2016-06-23
		}
	}
	return $list;
}

/* 积分商品列表
 * @function goods_list
 * @param String $where 条件
 * @param int $limit 数量
 * return 返回：成功返回list，不成功返回false
*/
function pgoods_list($where, $limit){
	
	$db								= M('pgoods', "mp_");
	
	$where							= "is_del = 1 and is_shelves = 0".$where;
	$order							= "`order` desc";
	
	$list							= $db->where($where)->order($order)->field("id, name, price, thumb")->limit($limit)->select();
	
	//echo $db->getlastsql();
	//exit;
	foreach($list as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['thumb']		= $val['thumb'];
		
		//取出相册
		
		$album_info					= album_info($val['id']);
		$list[$key]['album']		= $album_info['img_list'];
		
	}
	
	return $list;
}


/* 入场券列表
 * @function goods_list
 * @param String $where 条件
 * @param int $limit 数量
 * return 返回：成功返回list，不成功返回false
*/
function ticket_list($where, $limit){
	
	$db								= M('ticket', "mp_");
	
	$where							= "is_del = 1".$where;
	$order							= "`order` desc";
	
	
	$list							= $db->where($where)->order($order)->field("id, name, price, desc, thumb")->limit($limit)->select();
	
	//echo $db->getlastsql();
	//exit;
	foreach($list as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['thumb']		= $val['thumb'];
		
		//取出相册
		
		$album_info					= album_info($val['id']);
		$list[$key]['album']		= $album_info['img_list'];
		
	}
	
	return $list;
}

/* 会员卡列表
 * @function goods_list
 * @param String $where 条件
 * @param int $limit 数量
 * return 返回：成功返回list，不成功返回false
*/
function membercard_list($where, $limit){
	
	
	$db								= M('member_card', "mp_");
	$where							= "is_del = 1".$where;
	//$order						= "`order` desc";
	$list							= $db->where($where)->order($order)->field("id, name, price, desc, thumb")->limit($limit)->select();
	
	//echo $db->getlastsql();
	//exit;
	foreach($list as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['thumb']		= $val['thumb'];
		//取出相册
		$album_info					= album_info($val['id']);
		$list[$key]['album']		= $album_info['img_list'];
		
	}
	
	return $list;
}

/*获取商品品牌列表
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function brand_list($cate_id, $fun_where, $limit){
	
	$db				= D("goods_brand");
	
	//判断商品分类和品牌是否关联
	$set_info		= set_info(1);
	
	$cate_brand		= $set_info['cate_brand'];
	if($cate_brand == 0){
		//关联逻辑
		$where		= "cate_id = ".$cate_id.$fun_where;
	}else{
		$where		= $fun_where;
	}
	
	$order			= " `order` desc ";
	$res			= $db->where($where)->order($order)->limit($limit)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['thumb']		= $val['thumb'];
		
		//判断该类下是否有子分类
		$where_total				= "parent_id = '$val[id]'" ;
		$subcat_total				= $db->where($where_total)->count();
		$list[$key]['subcat_total']	= $subcat_total;
		
		if($subcat_total > 0){
			$where					= "parent_id = '$val[id]'";
			$sub_res				= $db->where($where)->order($order)->select();
			foreach($sub_res as $sun_key => $sub_val){
				$list[$key]['sub'][$sun_key]['id']			= $sub_val['id'];
				$list[$key]['sub'][$sun_key]['name']		= $sub_val['name'];
				$list[$key]['sub'][$sun_key]['parent_id']	= $sub_val['parent_id'];
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}

/*获取商品分类列表
 * @param (char)$table 分类表名
 * @param (int)$parent_id 分类ID
 * @param (string)$fun_where 条件
 * @param (int)$limit  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function gcat_list($parent_id, $fun_where, $limit){
	
	$db								= D("goods_category");
	
	if(empty($parent_id)){
		$where						= " parent_id = 0 ".$fun_where;
	}else{
		//判断是否有下级分类，如果没有分类则取出该ID的所有兄弟分类
		$tparent_id					= get_field('goods_category', 'mp_', 'parent_id', 'id= '.$parent_id);
		if($tparent_id != 0){
			$where					= " parent_id = ".$tparent_id;
		}else{
			$where					= " parent_id = ".$parent_id.$fun_where;
		}
	}
	
	$order							= " `order` desc ";
	$res							= $db->where($where)->order($order)->limit($limit)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['thumb']		= $val['thumb'];
		
		//判断该类下是否有子分类
		$where_total				= "parent_id = '$val[id]'" ;
		$subcat_total				= $db->where($where_total)->count();
		$list[$key]['subcat_total']	= $subcat_total;
		
		if($subcat_total > 0){
			$where					= "parent_id = '$val[id]'";
			$sub_res				= $db->where($where)->order($order)->select();
			foreach($sub_res as $sun_key => $sub_val){
				$list[$key]['sub'][$sun_key]['id']			= $sub_val['id'];
				$list[$key]['sub'][$sun_key]['name']		= $sub_val['name'];
				$list[$key]['sub'][$sun_key]['parent_id']	= $sub_val['parent_id'];
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}


/*获取积分商品分类列表
 * @param (char)$table 分类表名
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function pcat_list($where, $order){
	$db			= M("pgoods_category", "mp_");
	
	$where		= " parent_id = 0".$where;
	$order		= $order;
	$res		= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		
		
		//判断该类下是否有子分类
		$where_total				= "parent_id = '$val[id]'" ;
		$subcat_total				= $db->where($where_total)->count();
		$list[$key]['subcat_total']	= $subcat_total;
		
		if($subcat_total > 0){
			
			$where		= "parent_id = '$val[id]'";
			$sub_res	= $db->where($where)->order($order)->select();
			foreach($sub_res as $sun_key => $sub_val){
				$list[$key]['sub'][$sun_key]['id']			= $sub_val['id'];
				$list[$key]['sub'][$sun_key]['name']		= $sub_val['name'];
				$list[$key]['sub'][$sun_key]['parent_id']	= $sub_val['parent_id'];
				
			}
		}
	}
	return $list;
}


/*获取针对当前用户的商品单价
 * @param (int) $goods_id 商品ID
 * return 返回：成功返回商品单价，不成功返回false
*/

function user_price($goods_price){
	$user_id	= session('user_id');
	
	$agency		= get_field('member', 'mp_', 'agency', 'id = '.$user_id);
	
	$discount	= get_field('agency', 'mp_', 'discount', 'id = '.$agency);
	
	
	$price		= $discount*$goods_price/10;
	
	return $price;
	
}

/*针对当前用户的购物车内容列表
 * @param (int) $goods_id 商品ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回商品单价，不成功返回false
*/

function cart_list($where, $order){
	
	$db			= M("cart", "mp_");
	
	$where		= " user_id = ".session('user_id');
	
	$order		= $order;
	$res		= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		
		$list[$key]['id']			= $val['id'];
		$list[$key]['goods_id']		= $val['goods_id'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['sum']			= $val['sum'];
		$list[$key]['total']		= $val['total'];
		
		$list[$key]['thumb']		= get_field('goods', 'mp_', 'thumb', 'id = '.$val['goods_id']);
		$list[$key]['name']			= get_field('goods', 'mp_', 'name', 'id = '.$val['goods_id']);
		$list[$key]['desc']			= get_field('goods', 'mp_', 'desc', 'id = '.$val['goods_id']);
		$list[$key]['price']		= get_field('goods', 'mp_', 'price', 'id = '.$val['goods_id']);
		$list[$key]['mak_price']	= get_field('goods', 'mp_', 'mak_price', 'id = '.$val['goods_id']);
		
	}
	return $list;
	
}
/*针对当前用户的购物中商品总数
 * return 返回：成功返回商品总数，不成功返回false
*/

function get_userid($current_url){
	
	//获取当前地址
	$current_url				= get_url();
	
	//获取APPID
	$appid						= M('enterprise',  'mx_')->where(array('id'=>1))->getField('appid', 1);
	
	//echo $current_url."<br>";
	
	//获取code的url
	$code_url					= "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".urlencode ("$current_url")."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
	
	//echo $code_url;
	
	//exit;
	
	header("location: $code_url");
	
	$code 						= $_GET['code'];
	
	//echo $code;
	//exit;
	
	$openid 					= getOpenid($code);
	
	
	if(empty($openid)){
		echo "openid is error!";
		exit;
	}
	
	$user_db					= D('member');
	$user_where					= "open_id = '$openid'";
	$user_res					= $user_db->where($user_where)->find();
	$userid						= $user_res['id'];
	
	if($user_res['id'] == ''){
		header("Content-Type:text/html;   charset=utf-8");
		echo "抱歉，请重新关注";
		exit;
	}
	
	//echo $user_res['id']."<br>";
	//echo $user_db->getlastsql();
	//exit;
	session('openid',$openid);
	session('user_id',$userid);
	return	$userid;
}

/*针对当前用户的购物中商品总数
 * return 返回：成功返回商品总数，不成功返回false
*/

function cart_sum(){
	
	$db			= D("cart");
	$where		= " user_id = ".session('user_id');
	$order		= $order;
	$res		= $db->where($where)->sum('sum');
	if(!$res){
		$res	= '0';
	}
	//echo $db->getlastsql();
	//exit;
	
	return $res;
	
}

/*针对当前用户的购物中商品总数
 * return 返回：成功返回商品总数，不成功返回false
*/

function cart_total(){
	
	$db							= D("cart");
	$where						= " user_id = ".session('user_id');
	$order						= $order;
	$res						= $db->where($where)->sum('total');
	//计算优惠券
	$coupon_id					= session('coupon_id');
	if(!empty($coupon_id)){
		$coupon_type			= get_field('coupon', 'mp_', 'type', 'id = '.$coupon_id);
		if($coupon_type == 1){
			$coupon_discount	= get_field('coupon', 'mp_', 'discount', 'id = '.$coupon_id);
			$res				= $res*$coupon_discount/100;
		}else{
			$coupon_amount		= get_field('coupon', 'mp_', 'amount', 'id = '.$coupon_id);
			$res				= $res-$coupon_amount;
		}
	}
	if(!$res){
		$res	= "0";
	}
	//echo $db->getlastsql();
	//exit;
	
	return $res;
	
}

//原价
function cart_total_p(){
	
	$db							= D("cart");
	$where						= " user_id = ".session('user_id');
	$order						= $order;
	$res						= $db->where($where)->sum('total');
	
	if(!$res){
		$res	= "0";
	}
	//echo $db->getlastsql();
	//exit;
	
	return $res;
	
}

/*针对当前用户的购物车增加数量
 * @param (int) $goods_id 商品ID
 * @param (int)$goods_sum  商品数量
 * return 返回：成功返回商品单价，不成功返回false
*/

function add_cart($goods_id, $goods_sum){
	
	$db			= D("cart");
	$user_id	= session('user_id');
	//取出会员级别，计算享受价格
	$user_level	= get_field('member', 'mp_', 'level', 'id = '.$user_id);
	$user_disc	= get_field('member_level', 'mp_', 'discount', 'id = '.$user_level);
	if(!$user_disc){
		$user_disc	= 100;
	}
	
	//取出商品价格、模块
	$goods_price		= get_field('goods', 'mp_', 'price', 'id = '.$goods_id);
	$goods_model		= get_field('goods', 'mp_', 'model', 'id = '.$goods_id);
	
	//计算成交价格
	$transaction_price	= $goods_price*$user_disc/100;
	
	//计算总价
	$total				= $transaction_price*$goods_sum;
	
	//插入购物车
	//先查询是否存在如果存在就变更数量和总价，不存在就新增
	
	$cart_where	= "goods_id = ".$goods_id." and user_id = ".$user_id." and price = ".$transaction_price;
	$cart_res	= $db->where($cart_where)->find();
	//echo $db->getlastsql();
	//exit;
	if($cart_res){
		$cart_goods_id			= $cart_res['id'];
		$cart_goods_sum			= $cart_res['sum']+$goods_sum;
		$cart_goods_total		= $cart_res['total']+$total;
		
		$data['user_id']		= $user_id;
		$data['sum']			= $cart_goods_sum; //商品数量
		$data['total']			= $cart_goods_total; //商品数量
		$data['create_time']	= time(); //商品时间
		$add_res				= $db->where("id = ".$cart_goods_id)->save($data);
	}else{
		$data['user_id']		= $user_id;
		$data['goods_id']		= $goods_id; //商品id
		$data['model']			= $goods_model; //商品模型
		$data['sum']			= $goods_sum; //商品数量
		$data['price']			= $transaction_price; //商品成交价
		$data['total']			= $total; //商品总价
		$data['create_time']	= time(); //商品时间
		$add_res				= $db->add($data);
	}
	
	if($add_res){
		return	false;
	}else{
		return $res;
	}
	//echo $db->getlastsql();
	//exit;
}

/*针对当前用户的购物车减少
 * @param (int) $goods_id 商品ID
 * @param (int)$goods_sum  商品数量
 * return 返回：成功返回商品单价，不成功返回false
*/

function reduce_cart($goods_id, $goods_sum, $type){
	
	$db			= D("cart");
	$user_id	= session('user_id');
	//取出会员级别，计算享受价格
	$user_level	= get_field('member', 'mp_', 'level', 'id = '.$user_id);
	$user_disc	= get_field('member_level', 'mp_', 'discount', 'id = '.$user_level);
	if(!$user_disc){
		$user_disc	= 100;
	}
	
	//取出商品价格、模块
	$goods_price= get_field('goods', 'mp_', 'price', 'id = '.$goods_id);
	$goods_model= get_field('goods', 'mp_', 'model', 'id = '.$goods_id);
	
	//计算成交价格
	$transaction_price	= $goods_price*$user_disc/100;
	
	//计算总价
	$total				= $transaction_price*$goods_sum;
	
	//插入购物车
	//先查询是否存在如果存在就变更数量和总价，不存在就新增
	
	$cart_where	= "goods_id = ".$goods_id." and user_id = ".$user_id." and price = ".$transaction_price;
	$cart_res	= $db->where($cart_where)->find();
	//echo $db->getlastsql();
	//exit;
	if($cart_res){
		$cart_goods_id			= $cart_res['id'];
		$cart_goods_sum			= $cart_res['sum']-$goods_sum;
		$cart_goods_total		= $cart_res['total']-$total;
		$data['user_id']		= $user_id;
		$data['sum']			= $cart_goods_sum; //商品数量
		$data['total']			= $cart_goods_total; //商品数量
		$data['create_time']	= time(); //商品时间
		$add_res				= $db->where("id = ".$cart_goods_id)->save($data);
		
	}else{
		return	false;
	}
	
	if($add_res){
		return	false;
	}else{
		return $res;
	}
	//echo $db->getlastsql();
	//exit;
	
}


/*获取当前用户的收货地址列表

 * return 返回：成功返回默认地址，不成功返回false
*/

function address_list(){
	
	$db			= D("member_address");
	
	$where		= "user_id = ".session('user_id');
	
	$res		= $db->where($where)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$province_id				= $val['province'];
		$city_id					= $val['city'];
		$area_id					= $val['area'];
		
		$list[$key]['id']			= $val['id'];
		/* 
		$province_id				= $val['province'];
		$city_id					= $val['city'];
		$area_id					= $val['area'];
		$list[$key]['province']		= get_field('area', 'mp_', 'name', 'id = '.$province_id);
		$list[$key]['province']		.= get_field('area', 'mp_', 'name', 'id = '.$city_id);
		$list[$key]['province']		.= get_field('area', 'mp_', 'name', 'id = '.$area_id); */
		
		//获取地区名称
		$list[$key]['province_name']= D('areas')->where(array('area_id'=>$val['province']))->getField('area_name',1); 
		$list[$key]['city_name']	= D('areas')->where(array('area_id'=>$val['city']))->getField('area_name',1); 
		$list[$key]['area_name']	= D('areas')->where(array('area_id'=>$val['area']))->getField('area_name',1); 
		
		$list[$key]['province']		= $val['province'];
		$list[$key]['city']			= $val['city'];
		$list[$key]['area']			= $val['area'];
		$list[$key]['address']		= $val['address'];
		$list[$key]['consignee']	= $val['consignee'];
		$list[$key]['tel']			= $val['tel'];
		$list[$key]['is_default']	= $val['is_default'];
		
	}
	return $list;
}

/*获取当前用户的默认收货地址

 * return 返回：成功返回默认地址，不成功返回false
*/

function default_address($selected_addid){
	
	$db								= D("member_address");
	if(empty($selected_addid)){
		//没有地址则使用默认地址
		$userid						= session('user_id');
		$where						= "user_id = $userid and is_default = 0";
	}else{
		$where						= "id = $selected_addid";
	}
	$res							= $db->where($where)->find();
	
	//获取地区名称
	$res['province_name']			= D('areas')->where(array('area_id'=>$res['province']))->getField('area_name',1); 
	$res['city_name']				= D('areas')->where(array('area_id'=>$res['city']))->getField('area_name',1); 
	$res['area_name']				= D('areas')->where(array('area_id'=>$res['area']))->getField('area_name',1); 
	
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/*插入用户收货地址

 * return 返回：成功返回默认地址，不成功返回false
 * @desc $id 是被编辑的地址id 如果有就是编辑，没有就是添加
*/

function add_address($consignee, $tel, $address, $userid,$province,$city,$area,$id=0,$isDefault=1){
	
	$db							= D("member_address");
	
	$where						= array('user_id'=>$userid, 'is_default'=>0);
	$res						= $db->where($where)->find();
	if($res){
		//判断是否为设置为默认地址
		
		if($isDefault == 0){
			//变更原来的默认地址
			$db->where($where)->setField('is_default', 1);
			$data['is_default']		= 0;
		}else{
			$data['is_default']		= 1;
			
		}
		
	}else{
		$data['is_default']		= 0;
	}
	
	$data['province']		    = $province;
	$data['city']				= $city;
	$data['area']				= $area;
	$data['consignee']			= $consignee;
	$data['tel']				= $tel;
	$data['address']			= $address;
	$data['user_id']			= $userid;
	
	if($id){
		
		if($isDefault==0){
			$db->where(array('user_id'=>$userid, 'is_default'=>0))->save(array('is_default'=>1));
// 			echo $db->getlastsql();
			$data['is_default']			= 0;
		}elseif($isDefault == 1){
			//如果该地址为默认地址，则找个最新的设置为默认地址
			$now_is_default				= $db->where(array('user_id'=>$userid))->getField('is_default',1);
			
			if($now_is_default == 0){
				$now_id					= $db->where(array('user_id'=>$userid))->order('id desc')->getField('id',1);
				$db->where(array('id'=>$now_id))->setField('is_default', 0);
			}
			$data['is_default']			= 1;
		}
		$address_id					= $db->where("id=$id")->save($data);
	}else{
		$address_id					= $db->add($data);
	}
	//echo $db->getlastsql();
	//exit;
	return $address_id;
}


/*获取地区
 * @param (int) $type 读取类型，0一级地区，非一级地区
 * @param (int) $parent_id 非一级地区的父ID
 * @param (string) $fun_where 附加条件
 * return 返回：成功返回默认地址，不成功返回false
*/
function area_list($type, $parent_id, $fun_where){
	$db								= D("area");
	if($type == 0){
		$where						= " parent_id = 0".$fun_where;
	}elseif($type == 1){
		$where						= " parent_id = ".$parent_id.$fun_where;
	}
	$res							= $db->where($where)->order("`order`")->select();
	
	
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
	}
	//echo $db->getlastsql();
	//exit;
	
	return $list;
	
}

/*判断当前用户是否具有成为分销商的资格

 * return 返回：成功返回guid，不成功返回false
*/

function is_agency($openid){
	
	//取出分销商条件
	$condition						= get_field('set', 'mp_', 'agency_condition', 'id = 1');
	
	//判断用户是否为分销商
	$agency_db						= D('agency');
	$agency_res						= $agency_db->where("openid = '$openid'")->find();
	//echo $agency_db->getlastsql();
	//exit;
	if($agency_res){
		$res						= 0;//已是分销商
	}else{
	
		//获取当前用户消费总额
		$db							= D("order");
		$total						= $db->where("status = 0 and delivery = 2 and user_id".session('openid'))->sum('actual_payment');
		//判断并返回
		if($total >= $condition){
			$res					= 1; //满足成为条件
		}else{
			$res					= 2; //不满足
		}
	}
	return	$res;
	
	
}

/*生成guid

 * return 返回：成功返回guid，不成功返回false
*/
function create_guid() {
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45);// "-"
    $uuid = chr(123)// "{"
    .substr($charid, 0, 8).$hyphen
    .substr($charid, 8, 4).$hyphen
    .substr($charid,12, 4).$hyphen
    .substr($charid,16, 4).$hyphen
    .substr($charid,20,12)
    .chr(125);// "}"
    return $uuid;
}
/*取出用户详情

 * return 返回：成功返回详情，不成功返回false
*/
function user_info() {
    $db					= D("member");
	$userid				= session('user_id');
	$where				= "id = $userid";
	$res				= $db->where($where)->field("nick, head_pic, level, sex, balance, not_mentioned, agencylevel, agency_level")->find();
	
	if($res['sex'] == 2){
		$res['sex']		= "女";
	}elseif($res['sex'] == 1){
		$res['sex']		= "男";
	}
	
	//取出用户级别
	$res['level_name']	= get_field('member_level', 'mp_', 'name', 'id = '.$res['level']);
	
	//取出用户分销级别
	$res['agencylevel_name']	= get_field('agency_level', 'mp_', 'name', 'id = '.$res['agencylevel']);
	
    return $res;
}

/*订单数量
 * @param (int)$type 订单类型 1 未付款订单，2 已付款待收货订单， 3 待评价订单, 4 退单
 * @param (string)$where 附加条件
 * return 返回：成功返回列表，不成功返回fals
*/
function order_sum($type, $where_sum){
	if($type == 1){
		//取出未支付订单
		$where		= 'status = 0 and pay = 1';
		$mo_name	= "未支付订单";
	}elseif($type == 2){
		//取出已支付未发货订单
		$where		= 'status = 0 and pay = 0 and delivery = 1';
		$mo_name	= "已支付未发货订单";
	}elseif($type == 3){
		//取出已发货订单
		$where		= 'status = 0 and pay = 0 and delivery = 0';
		$mo_name	= "已发货订单";
	}elseif($type == 4){
		//取出确认收货订单
		$where		= 'status = 0 and pay = 0 and delivery = 2';
		$mo_name	= "已确认收货订单";
	}elseif($type == 5){
		//取出退货订单
		$where		= 'status = 3';
		$mo_name	= "退货订单";
	}
	
	$where		   .= " and user_id = ".session('user_id').$where_sum;//.session('user_id');
	$db				= D('order');
	$res			= $db->where($where)->count();
	return $res;
	
}


/*订单商品
 * @param (int)$order_sn 订单号
 * @param (string)$type 
 * return 返回：成功返回列表，不成功返回false
*/
function ogoods_list($order_sn){
	$db							= D('order_goods');
	$where						= "order_id = ".$order_sn;
	$res						= $db->where($where)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['goods_id']		= $val['goods_id'];
		$list[$key]['price']			= $val['price'];
		$list[$key]['goods_sum']		= $val['goods_sum'];
		$list[$key]['goods_name']		= get_field('goods', 'mp_', 'name', 'id = '.$val['goods_id']);
		$list[$key]['goods_thumb']	= get_field('goods', 'mp_', 'thumb', 'id = '.$val['goods_id']);
	}
	//print_r($list);
	return $list;
}




/*订单列表
 * @param (string)$type 订单类型 1 未付款订单，2 已付款待收货订单， 3 待评价订单, 4 退单
 * return 返回：成功返回列表，不成功返回false
*/

function order_list($type){
	if($type == 1){
		//取出未支付订单
		$where		= 'status = 0 and pay = 1';
		$mo_name	= "未支付订单";
	}elseif($type == 2){
		//取出已支付未发货订单
		$where		= 'status = 0 and pay = 0 and delivery = 1';
		$mo_name	= "已支付未发货订单";
	}elseif($type == 3){
		//取出已发货订单
		$where		= 'status = 0 and pay = 0 and delivery = 0';
		$mo_name	= "已发货订单";
	}elseif($type == 4){
		//取出确认收货订单
		$where		= 'status = 0 and pay = 0 and delivery = 2';
		$mo_name	= "已确认收货订单";
	}elseif($type == 5){
		//取出退货订单
		$where		= 'status = 3';
		$mo_name	= "退货订单";
	}
	$where		   .= " and user_id = ".session('user_id');
	
	$db				= D('order');
	$ogdb			= D('order_goods');
	$order			= "`create_time` desc ";
	$res			= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['order_id']    		= $val['order_id'];
		$list[$key]['total']			= $val['total'];
		$list[$key]['pay']				= $val['pay'];
		$list[$key]['exp_id']    		= $val['exp_id'];
		$list[$key]['exp_sn']			= $val['exp_sn'];
		$list[$key]['delivery']			= $val['delivery'];
		$list[$key]['address_id']		= $val['address'];
		$list[$key]['pay_time']			= date('Y-m-d h:i:s', $val['pay_time']);
		$list[$key]['exp_time']			= date('Y-m-d h:i:s', $val['exp_time']);
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		
		if($val['status'] == 0){
			$list[$key]['status']		= "正常";
		}elseif($val['status'] == 1){
			$list[$key]['status']		= "锁定";
		}elseif($val['status'] == 2){
			$list[$key]['status']		= "退单";
		}
		
		//取出收货人信息
		get_field('member_address', 'mp_', 'name', 'id = '.$res['level']);
		
		//取出收货人地址信息
		$province_id							= get_field('member_address', 'mp_', 'province', 'id = '.$val['address']);
		$city_id								= get_field('member_address', 'mp_', 'city', 'id = '.$val['address']);
		$area_id								= get_field('member_address', 'mp_', 'area', 'id = '.$val['address']);
		$address_id								= get_field('member_address', 'mp_', 'address', 'id = '.$val['address']);
		
		$list[$key]['address']					= get_field('area', 'mp_', 'name', 'id = '.$province_id);
		$list[$key]['address']					.= get_field('area', 'mp_', 'name', 'id = '.$city_id);
		$list[$key]['address']					.= get_field('area', 'mp_', 'name', 'id = '.$area_id);
		$list[$key]['address']					.= get_field('member_address', 'mp_', 'address', 'id = '.$val['address']);
		$list[$key]['consignee']				= get_field('member_address', 'mp_', 'consignee', 'id = '.$val['address']);
		$list[$key]['tel']						= get_field('member_address', 'mp_', 'tel', 'id = '.$val['address']);
		$list[$key]['actual_payment']			= "￥".$val['actual_payment'];
		
		//订单商品列表
		$goods_count							= $ogdb->where("order_id = ".$val['order_id'])->count();
		$list[$key]['goods_sum']				= $goods_count;
		$goods_res								= $ogdb->where("order_id = ".$val['order_id'])->select();
		foreach($goods_res as $goods_key => $goods_val){
			$list[$key]['goods'][$goods_key]['goods_id']	= $goods_val['goods_id'];
			$list[$key]['goods'][$goods_key]['goods_name']	= get_field('goods', 'mp_', 'name', 'id = '.$goods_val['goods_id']);
			$list[$key]['goods'][$goods_key]['thumb']		= get_field('goods', 'mp_', 'thumb', 'id = '.$goods_val['goods_id']);
		}
		
		//echo $ogdb->getlastsql();
		//exit;
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}


/**
 * 验证邮件
 * @param (string)$email 邮箱
 * return e 
 *
*/
function check_reg_email($email){
	
	//定义变量
	$result					= array();
	$status					= '0';
	$db	 					= M('member');
	
	if(empty($email)){
		$status				= '1';
		$msg				= "邮箱不能为空";
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}
	
	if (!eregi("^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)){ 
		$status				= '1';
		$msg				= "邮箱格式不正确";
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}
	
	//检查邮件域所属 DNS 中的 MX 记录 暂时不用
	/*else{
		if($test_mx){
            list($username, $domain) = split("@", $email);
            return getmxrr($domain, $mxrecords);
        }

	}*/
	
	//验证邮件是否存在
	$res 					= $db->where("email='$email'")->select();
	if($res){
		$status				= '1';
		$msg				= "该邮箱已经被注册";
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}
	
	if($status == '0'){
		$msg				= "邮箱可用";
	}
	
	$result['status']		= $status;
	$result['msg']			= $msg;
	return $result;
}


/**
 * 验证手机
 * @param (string)$mobile 手机
 * return 返回 
 *
*/
function fun_check_mobile($mobile){
	
	//定义变量
	$result					= array();
	$status					= '0';
	$db	 					= M('member');
	
	if(empty($mobile)){
		$status				= '1';
		$msg				= "手机号不能为空";
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}
	
	if (!preg_match("/^1[3,5,7,8][0-9]{1}[0-9]{8}$/", $mobile)){ 
		$status				= '1';
		$msg				= "手机号格式不正确";
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}
	
	if($status == '0'){
		$msg				= "手机号可用";
	}
	
	$result['status']		= $status;
	$result['msg']			= $msg;
	return $result;
}


/**
 * 验证值是否为空
 * @param (string)$mobile 手机
 * return 返回 
 *
*/
function fun_check_null($vaule, $type){
	
	//定义变量
	$result					= array();
	$status					= '0';
	
	if(empty($vaule)){
		$status				= '1';
		if($type == 'province'){
			$msg			= "省必须选择";
		}elseif($type == 'city'){
			$msg			= "市必须选择";
		}elseif($type == 'area'){
			$msg			= "区必须选择";
		}elseif($type == 'address'){
			$msg			= "详细地址必须填写";
		}elseif($type == 'consignee'){
			$msg			= "联系人必须填写";
		}
		$result['status']	= $status;
		$result['msg']		= $msg;
		return $result;
		exit;
	}else{
		$result['status']		= $status;
		$result['msg']			= $msg;
		return $result;
	}
}

/**
 * 取出详细地址信息
 * @param (int)$id 手机
 * return 返回 
 *
*/
function address_info($id){
	
	$userid					= session('user_id');
	$db						= D('member_address');
	if(empty($id)){
		$where				= "is_default = 0 and user_id = ".$userid;
		$nodef_where		= "user_id = ".$userid;
		$order				= " `id` desc ";
	}else{
		$where				= "id = $id";
	}
	$info					= $db->where($where)->find();
	
	if(!$info){
		$info				= $db->where($nodef_where)->order($order)->find();
	}
	
	if(!$info){
		$info				= 1;
	}else{
	
		$info['province_id']	= $info['province'];
		$info['province_name']	= get_field('area', 'mp_', 'name', 'id = '.$info['province']);
		
		$info['city_id']		= $info['city'];
		$info['city_name']		= get_field('area', 'mp_', 'name', 'id = '.$info['city']);
		
		$info['area_id']		= $info['area'];
		$info['area_name']		= get_field('area', 'mp_', 'name', 'id = '.$info['area']);
		
		$info['province']		= get_field('area', 'mp_', 'name', 'id = '.$info['province']);
		$info['province']		.= get_field('area', 'mp_', 'name', 'id = '.$info['city']);
		$info['province']		.= get_field('area', 'mp_', 'name', 'id = '.$info['area']);
	}
	return $info;
	
}




/**
 * 取出详细地址信息
 * @param (int)$id 手机
 * return 返回
 *
 */
function address_info_new($id){

	$userid					= session('user_id');
	$db						= D('member_address');
	if(empty($id)){
		$where				= "is_default = 0 and user_id = ".$userid;
		$nodef_where		= "user_id = ".$userid;
		$order				= " `id` desc ";
	}else{
		$where				= "id = ".$id." and user_id = ".$userid;
	}
	$info					= $db->where($where)->find();
	

	if(!$info){
		$info				= $db->where($nodef_where)->order($order)->find();
	}

	return $info;

}







/**
 * 取出用户可用的优惠券
 * @param (int)$id 手机
 * return 返回 
 *
*/
function coupon_info($id){
	
	$userid						= session('user_id');
	$db							= D('coupon_record');
	$res						= '<a href="/Mshop/Flow/coupon_list/"><div class="y-title icon-coupon" style="margin-top:8px; text-indent:26px; font-size:16px; height:30px; line-height:30px; ">';
	if(empty($id)){
		//判断用户是否有可用的优惠券
		$where					= "user_id = ".$userid." and status = 0";
		$info_count				= $db->where($where)->count();
		
		if($info_count > 0){
			$res				.= '<span style="color:#A664A6;">请选择优惠券</span></div></a>';//没有优惠券
		}else{
			$res				.= '<span style="color:#A664A6;">没有可用的优惠券</span></div></a>';//没有优惠券
		}
		
	}else{
		
		//取出优惠券逻辑
		$where					= "coupon_id = ".$id." and user_id = ".$userid." and status = 0";
		$info					= $db->where($where)->find();
		if($info){
			$coupon_name		= get_field('coupon', 'mp_', 'name', 'id = '.$info['coupon_id']);
			$res				.= '优惠券&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#A664A6;">'.$coupon_name.'</span></div></a><input type="hidden" name="coupon_id" value="'.$id.'" />';
		}
		
	}
	
	return $res;
	
}

/*获取当前用户的地址列表
 * return 返回：成功返回默认地址，不成功返回false
*/

function coupon_list(){
	
	$db			= D("coupon_record");
	$ooupon_db	= D("coupon");
	
	$where		= "status = 0 and user_id = ".session('user_id');
	
	$res		= $db->where($where)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		$coupon_id					= $val['coupon_id'];
		//取出优惠券信息
		$coupon_name				= get_field('coupon', 'mp_', 'name', 'id = '.$coupon_id);
		$coupon_thumb				= get_field('coupon', 'mp_', 'thumb', 'id = '.$coupon_id);
		$coupon_desc				= get_field('coupon', 'mp_', 'desc', 'id = '.$coupon_id);
		$list[$key]['id']			= $val['id'];
		$list[$key]['coupon_id']	= $val['coupon_id'];
		$list[$key]['coupon_name']	= $coupon_name;
		$list[$key]['coupon_thumb']	= $coupon_thumb;
		$list[$key]['coupon_desc']	= $coupon_desc;
	}
	return $list;
}


/**
 * @desc  im:处理Openid 并存入session
 * */
function get_openid($openid){
	
	$db				= D('member');
	$where			= "open_id = '$openid'";
	$res			= $db->where($where)->find();
	$user_id		= $res['id'];
	$user_name		= $res['username'];
	$user_agency	= $res['agency'];
	
	//查出默认收货地址
	$address_db		= D('member_address');
	$address_where	= 'user_id = '.$user_id;
	$address_order	= 'id desc';
	$address_res	= $address_db->where($address_where)->find();
	if(empty($address_id)){
		$address_res	= $address_db->order($address_order)->find();
	}
	
	$address_id		= $address_res['id'];
	
	
	//存储session
	session('openid', $openid);
	session('user_id', $user_id);
	session('username', $user_name);
	session('address_id', $address_id);
	session('useragency', $user_agency);
	
}


/*------------------------------摇一摇方法-----------------------------------*/



/*获取当前用户针对某个活动的参与记录
 * return 返回：成功返回默认地址，不成功返回false
*/
function actrecd_list($act_id=false, $userid=false, $where, $order){
	
	$db													= D('activite_record');
	
	//$userid											= session('user_id');
	
	if(empty($order)){
		$order											= "id desc";
	}
	
	if($userid && $act_id){
		$where											= "userid=$userid and activite=$act_id ".$where;
	}elseif(!$userid && $act_id){
		$where											= "activite=$act_id ".$where;
	}elseif(!$act_id && $userid){
		$where											= "userid=$userid" .$where;
	}else{
		$where											= "id != 0 ".$where;
	}
	
	$res												= $db->where($where)->order($order)->select();
	foreach($res as $key => $val){
		$list[$key]['id']								= $val['id'];
		$list[$key]['sign_time']						= $val['sign_time'];
		$list[$key]['receive_time']						= $val['receive_time'];
		$list[$key]['realname']							= $val['realname'];
		$list[$key]['tel']								= $val['tel'];
		$list[$key]['status']							= $val['status'];
		$list[$key]['deliver']							= $val['deliver'];
		$list[$key]['address']							= $val['address'];
		$list[$key]['goods_id']							= $val['goods_id'];
		$list[$key]['pay_type']							= $val['pay_type'];
		$list[$key]['userid']							= $val['userid'];
		$list[$key]['goods_name']						= get_field('goods', 'mp_', 'name', 'id = '.$val['goods_id']);
		$list[$key]['thumb']							= get_field('goods', 'mp_', 'thumb', 'id = '.$val['goods_id']);
		$list[$key]['price']							= get_field('goods', 'mp_', 'price', 'id = '.$val['goods_id']);
		$nick											= get_field('member', 'mp_', 'nick', 'id = '.$val['userid']);
		$list[$key]['nick']								= mb_substr($nick, 0, 3)."***";
		
		$activite										= $val['activite'];
		$list[$key]['act_name']							= get_field('activite', 'mp_', 'name', 'id = '.$activite);
		$list[$key]['userid']							= $val['userid'];
		$list[$key]['sign_time']						= date('Y-m-d H:i', $val['sign_time']);
		$list[$key]['expiration_time']					= date('Y-m-d H:i', $val['expiration_time']);
		$list[$key]['receive_time']						= date('Y-m-d H:i', $val['receive_time']);
		
		//是否有订单
		$order_sn										= $val['order_sn'];
		$status											= $val['status'];
		//已领取输出逻辑
		if($status == 0){
			$pay_type									= $val['pay_type'];//支付方式
			if($pay_type == 1){
				//微信支付
				$order_db								= D('order');
				$order_where							= array('order_id'=>$order_sn);
				$order_res								= $order_db->where($order_where)->field('pay')->find();
				$order_pay								= $order_res['pay'];//判断是否支付
				if($order_pay == 1){
					$list[$key]['order_pay']			= $order_pay;
					$list[$key]['order_sn']				= $order_sn;
					//未支付,调用支付链接
					$list[$key]['p_url']				= "/Mshop/flow/order_pay/order_sn/$order_sn";
					$list[$key]['url_name']				= "立即支付";
				}elseif($order_pay == 0){
					$list[$key]['p_url']				= "/Mshop/Prize/receipt/actr_id/".$val['id'];
					$list[$key]['pay_type']				= $pay_type;
					if($val['deliver'] == 1){
						$list[$key]['url_name']			= "等待发货";
					}elseif($val['deliver'] == 0){
						$list[$key]['url_name']			= "确认收货";
					}elseif($val['deliver'] == 2){
						$list[$key]['url_name']			= "已收货";
					}
				}
			}elseif($pay_type == 5){
				//线下支付
				//已经支付调用确认收货或者等待发货的链接
				$list[$key]['p_url']					= "/Mshop/Prize/receipt/actr_id/".$val['id'];
				if($val['deliver'] == 1){
					$list[$key]['url_name']				= "等待发货";
				}elseif($val['deliver'] == 0){
					$list[$key]['url_name']				= "确认收货";
				}
			}
			$list[$key]['order_sn']						= $val['order_sn'];
			$list[$key]['status_name']					= "已领取";
			
		}elseif($status == 1){
			//未领取
			$list[$key]['p_url']						= "/Mshop/Prize/receive/actr_id/".$val['id'];
			$list[$key]['url_name']						= "领取商品";
			$list[$key]['status_name']					= "未领取";
		}elseif($status == 2){
			//未领取
			$list[$key]['url_name']						= "已过期";
		}
		
		
		if($deliver == 1){
			$list[$key]['deliver_name']					= "未发货";
		}elseif($deliver == 0){
			$list[$key]['deliver_name']					= "已发货";
		}
		
		
	}
	
	return $list;
}


/**
 * 新闻列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function news_list($cat_id, $where, $limit){

	$db    								= D('news');
	
	if(empty($cat_id)){
		$where							= "is_del = 1".$where;
	}else{
		$where							= "cat_id = '$cat_id' and is_del = 1".$where;
	}
	
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']     	 	= $val['title'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['desc']				= $val['desc'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['auth']				= $val['auth'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['link']				= $val['link'];
		
		//处理图片
		//if(empty($val['thumb'])){
			//$list[$key]['thumb']			= "/Public/static/aytc/img/no_img.jpg";
		//}else{
			//$list[$key]['thumb']			= $val['thumb'];
		//}
		
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y年m月d日', $val['create_time']);
			$list[$key]['up_d']			= date('d', $val['create_time']);
			$list[$key]['up_m']			= date('m', $val['create_time']);
			$list[$key]['up_y']			= date('Y', $val['create_time']);
			
		}else{
			$list[$key]['up_time']		= date('Y年m月d日', $val['up_time']);
			$list[$key]['up_d']			= date('d', $val['up_time']);
			$list[$key]['up_m']			= date('m', $val['up_time']);
			$list[$key]['up_y']			= date('Y', $val['up_time']);
		}
		
		//取出新闻分类名称
		
		$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('news_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
	}
	return $list;
}

function news_info($id){
	$db					= D('news');
	$where				= "id = '$id'";
	$res				= $db->where($where)->find();
	
	return $res;
}





/*提现记录
 * @function carousel_list
 
 * return 返回：成功返回list，不成功返回false
*/
function pc_record($userid, $where){
	
	$db			= D('member_withdrawals');
	$where		= " user_id = $userid".$where;// and start_time <= '$now' <= end_time ";
	$res		= $db->where($where)->order($order)->select();
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['user_id']		= $val['user_id'];
		$list[$key]['wamount']		= $val['wamount'];
		$list[$key]['create_time']	= date('Y-m-d H:i:s');
		$status						= $val['status'];
		$list[$key]['wamount_sn']	= $val['wamount_sn'];
		
		
		//1待审核，2已审核，3已打款,4驳回，5已到账
		if($status == 1){
			$list[$key]['status_name']	= "待审核";
		}elseif($status == 2){
			$list[$key]['status_name']	= "已审核";
		}elseif($status == 3){
			$list[$key]['status_name']	= "已打款";
		}elseif($status == 4){
			$list[$key]['status_name']	= "驳回";
		}elseif($status == 5){
			$list[$key]['status_name']	= "已到账";
		}
		
		
	}
	return $list;
}



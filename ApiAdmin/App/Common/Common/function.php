<?php

/*各模块公用方法文件*/

/**
 * 功能：邮件发送函数
 * @param string $to 目标邮箱
 * @param string $subject 邮件主题（标题）
 * @param string $to 邮件内容
 * @return bool true
 */
 function sendMail($to, $subject, $content) {
	 //引入PHPMailer的核心文件 使用require_once包含避免出现PHPMailer类重复定义的警告
   vendor('PHPMailer.class#smtp'); 
 vendor('PHPMailer.class#phpmailer');
    //实例化PHPMailer核心类
    $mail = new PHPMailer();

    //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
    $mail->SMTPDebug = 1;

    //使用smtp鉴权方式发送邮件
    $mail->isSMTP();

    //smtp需要鉴权 这个必须是true
    $mail->SMTPAuth=true;

    //链接qq域名邮箱的服务器地址
    $mail->Host = 'smtp.qq.com';

    //设置使用ssl加密方式登录鉴权
    $mail->SMTPSecure = 'ssl';

    //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
    $mail->Port = 465;

    //设置smtp的helo消息头 这个可有可无 内容任意
    // $mail->Helo = 'Hello smtp.qq.com Server';

    //设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名
    $mail->Hostname = 'http://www.lsgogroup.com';

    //设置发送的邮件的编码 可选GB2312 我喜欢utf-8 据说utf8在某些客户端收信下会乱码
    $mail->CharSet = 'UTF-8';

    //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
    $mail->FromName = 'Mr 超';

    //smtp登录的账号 这里填入字符串格式的qq号即可
    $mail->Username ='765682204@qq.com';

    //smtp登录的密码 使用生成的授权码（就刚才叫你保存的最新的授权码）
    $mail->Password = 'qvljgvywmsfmbfhd';

    //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”
    $mail->From = '765682204@qq.com';

    //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
    $mail->isHTML(true); 

    //设置收件人邮箱地址 该方法有两个参数 第一个参数为收件人邮箱地址 第二参数为给该地址设置的昵称 不同的邮箱系统会自动进行处理变动 这里第二个参数的意义不大
    $mail->addAddress($to);

    //添加多个收件人 则多次调用方法即可
    // $mail->addAddress('xxx@163.com','lsgo在线通知');

    //添加该邮件的主题
    $mail->Subject = '接口异常通知';

    //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
    $mail->Body = $content;

    //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
    // $mail->addAttachment('./d.jpg','mm.jpg');
    //同样该方法可以多次调用 上传多个附件
    // $mail->addAttachment('./Jlib-1.1.0.js','Jlib.js');

    $status = $mail->send();

    //简单的判断与提示信息
    if($status) {
        return true;
    }else{
        return false;
    }
 }
/**
 * utf-8和gb2312自动转化
 * @param unknown $string
 * @param string $outEncoding
 * @return unknown|string
 */
function safeEncoding($string,$outEncoding = 'UTF-8')
{
	$encoding = "UTF-8";
	for($i = 0; $i < strlen ( $string ); $i ++) {
		if (ord ( $string {$i} ) < 128)
			continue;

		if ((ord ( $string {$i} ) & 224) == 224) {
			// 第一个字节判断通过
			$char = $string {++ $i};
			if ((ord ( $char ) & 128) == 128) {
				// 第二个字节判断通过
				$char = $string {++ $i};
				if ((ord ( $char ) & 128) == 128) {
					$encoding = "UTF-8";
					break;
				}
			}
		}
		if ((ord ( $string {$i} ) & 192) == 192) {
			// 第一个字节判断通过
			$char = $string {++ $i};
			if ((ord ( $char ) & 128) == 128) {
				// 第二个字节判断通过
				$encoding = "GB2312";
				break;
			}
		}
	}

	if (strtoupper ( $encoding ) == strtoupper ( $outEncoding ))
		return $string;
	else
		return @iconv ( $encoding, $outEncoding, $string );
}
/**
 * 判断当前服务器系统
 * @return string
 */
function getOS(){
	if(PATH_SEPARATOR == ':'){
		return 'Linux';
	}else{
		return 'Windows';
	}
}
    /**
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str 需要转换的字符串
     * @param string $start 开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param string $suffix 截断显示字符
     * @return string
     */
  function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
        if(function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif(function_exists('iconv_substr')) {
            $slice = iconv_substr($str,$start,$length,$charset);
        }else{
            $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("",array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice.'...' : $slice;
    }

/**
 * 获取url
 * @return boolen
 */
function get_url() {
	$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
	$php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
	$path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
	$relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
	return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/*生成订单编号*/
function build_order_no(){
	return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 检测输入的验证码是否正确
 * @param string $code 为用户输入的验证码字符串
 * @return boolen
 */
function check_verify($code, $id = ''){
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 对用户的密码进行加密
 * @param string $password
 * @param string $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */
function password($password, $encrypt='') {
	$pwd = array();
	$pwd['encrypt'] =  $encrypt ? $encrypt : Org\Util\String::randString(6);
	$pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
	return $encrypt ? $pwd['password'] : $pwd;
}

/**
 * 解析多行sql语句转换成数组
 * @param string $sql
 * @return array
 */
function sql_split($sql) {
	$sql = str_replace("\r", "\n", $sql);
	$ret = array();
	$num = 0;
	$queriesarray = explode(";\n", trim($sql));
	unset($sql);
	foreach($queriesarray as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		$queries = array_filter($queries);
		foreach($queries as $query) {
			$str1 = substr($query, 0, 1);
			if($str1 != '#' && $str1 != '-') $ret[$num] .= $query;
		}
		$num++;
	}
	return($ret);
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 取得文件扩展
 * @param $filename 文件名
 * @return 扩展名
 */
function file_ext($filename) {
	return strtolower(trim(substr(strrchr($filename, '.'), 1, 10)));
}

/**
 * 文件是否存在
 * @param string $filename  文件名
 * @return boolean  
 */
function file_exist($filename ,$type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
	        $filePath = implode('/', $arr);
	        $s = new SaeStorage();
			return $s->fileExists($domain, $filePath);
			break;
		default:
			return \Think\Storage::has($filename ,$type);
	}
}

/**
 * 文件内容读取
 * @param string $filename  文件名
 * @return boolean         
 */
function file_read($filename, $type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
			$filePath = implode('/', $arr);
			$s=new SaeStorage();
			return $s->read($domain, $filePath);
			break;
		default:
			return \Think\Storage::read($filename, $type);
	}
}

/**
 * 文件写入
 * @param string $filename  文件名
 * @param string $content  文件内容
 * @return boolean         
 */
function file_write($filename, $content, $type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$s=new SaeStorage();
			$arr = explode('/',ltrim($filename,'./'));
			$domain = array_shift($arr);
			$save_path = implode('/',$arr);
			return $s->write($domain, $save_path, $content);
			break;
		default:
			return \Think\Storage::put($filename, $content, $type);
	}
}

/**
 * 文件删除
 * @param string $filename  文件名
 * @return boolean     
 */
function file_delete($filename ,$type=''){
	switch (STORAGE_TYPE){
		case 'Sae':
			$arr = explode('/', ltrim($filename, './'));
	        $domain = array_shift($arr);
	        $filePath = implode('/', $arr);
	        $s = new SaeStorage();
			return $s->delete($domain, $filePath);
			break;
		default:
			return \Think\Storage::unlink($filename ,$type);
	}
}

/**
 * 获取文件URL
 * @param string $filename  文件名
 * @return string
 */
function file_path2url($filename){
	$search = array_keys(C('TMPL_PARSE_STRING'));
	$replace = array_values(C('TMPL_PARSE_STRING'));
	return str_ireplace($search, $replace, $filename);
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
 * 商户设置输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function tenant_set($id){
	
	$where								= "id = '$id'";
	$db    								= M('tenant_set', 'to_');
	$res								= $db->where($where)->find();
	
	//echo $db->getlastsql();
	
	return $res;
}



/**
 * @desc  im:获取单个表单数据
 * @param (string)$table  需要获取的数据的表名，不能为空
 * @param (string)$prefix 需要获取的数据的前缀，不能为空
 * @param (string)$field  需要获取的数据的字段名，不能为空 
 * @param (string)$where  需要获取的数据的条件，不能为空
 * @param (string)$type   1为多个字段内容,其他为单个字段
 * return 返回：成功返回field的值，不成功返回false 
 * */

function get_field($table,$prefix,$field,$where,$order,$type){
	if(empty($where) || empty($field) || empty($table) || empty($prefix)){
		return false;
	}
	$ck_db  = M($table,$prefix);
	if(empty($order)){
		$ck_res = $ck_db->where($where)->field($field)->find();
	}else{
		$ck_res = $ck_db->where($where)->field($field)->order($order)->find();
	}
	//echo $ck_db->getlastsql();
	
	$field  = $ck_res[$field];
	
	
	
	if($ck_res){
		return $field;
	}else{
		return false;
	}
}
/**
 * 获得真实IP地址
 * @return string
 */
function realIp() {
	static $realip = NULL;
	if ($realip !== NULL) return $realip;
	if (isset($_SERVER)) {
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			foreach ($arr AS $ip) {
				$ip = trim($ip);
				if ($ip != 'unknown') {
					$realip = $ip;
					break;
				}
			}
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$realip = $_SERVER['HTTP_CLIENT_IP'];
		} else {
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$realip = $_SERVER['REMOTE_ADDR'];
			} else {
				$realip = '0.0.0.0';
			}
		}
	} else {
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			$realip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$realip = getenv('HTTP_CLIENT_IP');
		} else {
			$realip = getenv('REMOTE_ADDR');
		}
	}
	preg_match('/[\d\.]{7,15}/', $realip, $onlineip);
	$realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
	return $realip;
}


/**
 * @desc  im:变更某个表单的数据
 * @param (string)$table  需要变更的数据的表名，不能为空
 * @param (string)$prefix 需要变更的数据的前缀，不能为空
 * @param (string)$data  需要变更的数据的字段名，不能为空 
 * @param (array)$where  需要变更的数据的条件，不能为空
 * return 返回：成功返回true，不成功返回false 
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
 * @desc  生成GUID 
 * return true false 
 * */
function guid(){
    if (function_exists('com_create_guid')){
		$uuid = com_create_guid();
		$uuid = str_replace("-","",$uuid);
		$uuid = substr($uuid,1,32);
		return $uuid;
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $uuid = substr($charid, 0, 8).substr($charid, 8, 4).substr($charid,12, 4).substr($charid,16, 4).substr($charid,20,12);
		return $uuid;
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
/**
     * 用正则表达式验证手机号码(中国大陆区)
     * @param integer $tel    所要验证的手机号
     * @return boolean
     */
function isMobile($tel) {
	if (!$tel) {
		return false;
	}
	return preg_match('#^1[3,8,7][\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', $tel) ? true : false;
}


/**
 * @desc  im:判断手机系统
 * @param (string)$data['os']  输出的数组，data['os']代表系统1为IPHONE系统，2为安卓系统，3为不支持的安卓系统，4为电脑系统
 * @param (string)$data['browser'] 输出的数组，data['browser']代表系统1为微信内部打开，3为其他手机浏览器
 * @param (string)$data['screen_width'] 输出的数组，data['screen_width']当前的屏幕分辨率
 * return 返回：成功返回手机号，不成功返回false
 * */
function get_os(){
	//
	$agent 		= strtolower($_SERVER['HTTP_USER_AGENT']);
	$iphone 	= (strpos($agent, 'iphone')) ? true : false;
	$android    = (strpos($agent, 'android')) ? true : false;
	$androidbig = (strpos($agent, 'android 4')) ? true : false;
	$data = array();
	$data['screen_width'] = $screen_width;
	if($iphone){
		$data['os'] = '1';
		if(strpos($agent, 'micromessenger')){
			$data['browser'] = '1';
		}
		else{
			$data['browser'] = '3';
		}
	}elseif($android){
		$data['os'] = '2';
		if(strpos($agent, 'micromessenger')){
			$data['browser'] = '1';
		}
		else{
			$data['browser'] = '3';
		}
		
	}else{
		$data['os'] = '4';
	}
	return $data;
}

/**
 * @desc  im:获取毫秒数
 * */
function getMillisecond() { 
	list($s1, $s2) = explode(' ', microtime()); 
	return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000); 
}

/**
 * @desc  im:模拟POST提交
 * */
function vpost($url,$data){ // 模拟提交数据函数
	$curl = curl_init(); // 启动一个CURL会话
	curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)'); // 模拟用户使用的浏览器
	// curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	// curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
	curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	$tmpInfo = curl_exec($curl); // 执行操作
	if (curl_errno($curl)) {
		echo 'Errno'.curl_error($curl);//捕抓异常
	}
	curl_close($curl); // 关闭CURL会话
	return $tmpInfo; // 返回数据
}



/**
 * @desc  im:curl提交
 * */
function curl($url, $postFields){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.1)');
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FAILONERROR, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
	if (is_array($postFields) && 0 < count($postFields)){
		$postBodyString = "";
		$postMultipart = false;
		foreach ($postFields as $k => $v){
			//判断是不是文件上传
			if("@" != substr($v, 0, 1)){
				$postBodyString .= "$k=" . urlencode($v) . "&";
			}else{//文件上传用multipart/form-data，否则用www-form-urlencoded
		
				$postMultipart = true;
			}
		}
		unset($k, $v);
		curl_setopt($ch, CURLOPT_POST, 1);
		if ($postMultipart){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		}else{
			//var_dump($postBodyString);
			curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
		}
	}
	$reponse = curl_exec($ch);
	//return curl_getinfo($ch);
	if (curl_errno($ch)){
		throw new Exception(curl_error($ch),0);
	}else{
		$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (200 !== $httpStatusCode){
			throw new Exception($reponse,$httpStatusCode);
		}
	}
	curl_close($ch);
	return $reponse;
}


/**
 * @desc  im:转换字符编码
 * */
function array_iconv($data,  $output = 'utf-8') {  
    $encode_arr = array('UTF-8','ASCII','GBK','GB2312','BIG5','JIS','eucjp-win','sjis-win','EUC-JP');  
    $encoded = mb_detect_encoding($data, $encode_arr);  
  
    if (!is_array($data)) {  
        return mb_convert_encoding($data, $output, $encoded);  
    }  
    else {  
        foreach ($data as $key=>$val) {  
            $key = array_iconv($key, $output);  
            if(is_array($val)) {  
                $data[$key] = array_iconv($val, $output);  
            } else {  
            $data[$key] = mb_convert_encoding($data, $output, $encoded);  
            }  
        }  
    return $data;  
    }  
}


/**
 * @desc  im:截取固定长度的字符，并代替
 * @param (string)$sourcestr	需要截取的字符
 * @param (string)$cutlength	截取长度
 * @param (string)$type			替代样式，1为... 2为空 其他替代的字符直接传  
 * return 返回：成功返回手机号，不成功返回false
 * */

function cut_str($sourcestr, $cutlength, $type){
	
	if($type == 1){
		$sor	= "...";
	}elseif($type == 2){
		$sor	= "";
	}else{
		$sor	= $type;
	}
	
	$returnstr='';
	$i=0;
	$n=0;
	$returnstr = mb_substr($sourcestr, 0, $cutlength, 'utf-8').$sor;//字符串的字节数
	
	return $returnstr;
}

/**
 * 相册详情输出方法
 * @param (int)$cat_id  相册分类ID
 * @param (string)$where 条件
 * return 返回：成功返回楼盘详情，不成功返回false 
 *
*/

function album_info($id){
	
	$db   				= M('album', 'mp_');
	$where				= "is_del = 1 and id = '$id'";
	$info 				= $db->where($where)->find();
	//echo $db->getlastsql();
	
	$info['img_list']	= json_decode($info['info'], true);//化身成数组
	
	foreach($info['img_list'] as $key=> $val){
		$list[$key]['id']	= $val['id'];
		$list[$key]['img']	= $val['name'];
	}
	//print_r($list);
	//exit;
	return $list;
}


/**
 * 判断用户信息是否完整,目前只判断签名和电话
 * @param (string)$info 判断内容
 * return 返回：根据结果返回int型数据
 *
*/

function is_complete($info){
	
	$db   	= M('users', 'mp_');
	$where	= "status = 0 and id = ".session('user_id');
	$info	= "signature, tel";
	$res 	= $db->field($info)->where($where)->find();
	
	
	if(empty($res['signature']) && !empty($res['tel'])){
		$res	= 1;
	}elseif(empty($res['tel']) && !empty($res['signature'])){
		$res	= 2;
	}elseif(empty($res['tel']) && empty($res['signature'])){
		$res	= 3;
	}else{
		$res	= 0;
	}
	
	return $res;
}

/**
 * 取出用户详细信息是否完整,目前只判断签名和电话
 * return 返回返回结果
 *
*/

function userinfo(){
	
	$db   	= M('users', 'mp_');
	$where	= "status = 0 and id = ".session('user_id');
	$info	= "signature, tel";
	$res 	= $db->field($info)->where($where)->find();
	
	return $res;
}



/**
 *
 * 生成二维码
 *
 * @param $content 二维码包含的内容
 * @param $tpgs 图片格式
 * @param bool $qrcode_bas_path 储存路径
 * @param bool $logo 二维码中包含的LOGO图片路径
 * @param string $matrixPointSize 二维码的大小
 * @param string $errorCorrectionLevel 二维码编码纠错级别：L、M、Q、H
 * @param int $matrixMarginSize 二维码边框的间距
 * return string
 */
function qrcode($content,$tpgs,$qrcode_bas_path=false,$logo,$errorCorrectionLevel='L',$matrixPointSize='4',$matrixMarginSize='1'){
	//生成二维码
	
	
		Vendor('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $qrcode_path='';
        $file_tmp_name='';
		
        $errors=array();
		//赋值
		/*$content 					= "http://www.baidu.com"; //二维码内容
		$tpgs						="png";//图片格式
		$qrcode_bas_path			='Public/upload/images/member/'; //储存路径
		$errorCorrectionLevel 		= 4;//容错级别
		$matrixPointSize 			= 6;//生成图片大小
		$matrixMarginSize 			= 1;//边距大小
		*/
		if(isset($_FILES['upimage']['tmp_name']) && $_FILES['upimage']['tmp_name'] && is_uploaded_file($_FILES['upimage']['tmp_name'])){
			if($_FILES['upimage']['size']>512000){
				$errors[]="你上传的文件过大，最大不能超过500K。";
			}
			$file_tmp_name=$_FILES['upimage']['tmp_name'];
			$fileext = array("image/pjpeg","image/jpeg","image/gif","image/x-png","image/png");
			if(!in_array($_FILES['upimage']['type'],$fileext)){
				$errors[]="你上传的文件格式不正确，仅支持 png, jpg, gif格式。";
			}
		}
		
		
		if(!is_dir($qrcode_bas_path)){
			mkdir($qrcode_bas_path, 0777, true);
		}
		$uniqid_rand=date("Ymdhis").uniqid(). rand(1,1000);
		$qrcode_path=$qrcode_bas_path.$uniqid_rand. "_1.".$tpgs;//原始图片路径
		$qrcode_path_new=$qrcode_bas_path.$uniqid_rand."_2.".$tpgs;//二维码图片路径
		if(getOS()=='Linux'){
			$mv = move_uploaded_file($file_tmp_name, $qrcode_path);
		}else{
			//解决windows下中文文件名乱码的问题
			$save_path = safeEncoding($qrcode_path,'GB2312');
			if(!$save_path){
				$errors[]='上传失败，请重试！';
			}
			$mv = move_uploaded_file($file_tmp_name, $qrcode_path);
		}
		if(empty($errors)){
			
			//生成二维码图片
			$object::png($content,$qrcode_path_new, $errorCorrectionLevel, $matrixPointSize, $matrixMarginSize);
			$QR = $qrcode_path_new;//已经生成的原始二维码图
			
			//$logo = $qrcode_path;//准备好的logo图片
			//echo $logo;
			if(!empty($logo)) {
				
				$QR = imagecreatefromstring(file_get_contents($QR));
				$logo = imagecreatefromstring(file_get_contents($logo));
				$QR_width = imagesx($QR);//二维码图片宽度
				$QR_height = imagesy($QR);//二维码图片高度
				$logo_width = imagesx($logo);//logo图片宽度
				$logo_height = imagesy($logo);//logo图片高度
				$logo_qr_width = $QR_width / 8;
				$scale = $logo_width/$logo_qr_width;
				$logo_qr_height = $logo_height/$scale;
				$from_width = ($QR_width - $logo_qr_width) / 2;
				//重新组合图片并调整大小
				imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
				$logo_qr_height, $logo_width, $logo_height);
				//输出图片
				//header("Content-type: image/png");
				imagepng($QR,$qrcode_path);
				imagedestroy($QR);
			}else{
				$qrcode_path=$qrcode_path_new;
			}
		}else{
			$qrcode_path='';
		}
	//$data=array('errors'=>$errors,'qrcode_path'=>$qrcode_path);
	//print_r($data);
	return $qrcode_path;
}

function api_notice_increment($url, $data){
	$ch = curl_init();
	$header = "Accept-Charset: utf-8";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$tmpInfo = curl_exec($ch);
	$errorno=curl_errno($ch);
	/*if ($errorno) {
		return array('rt'=>false,'errorno'=>$errorno);
	}else{
		$js=json_decode($tmpInfo,1);
		if ($js['errcode']=='0'){
			return array('rt'=>true,'errorno'=>0);
		}else {
			$errmsg=GetErrorMsg::wx_error_msg($js['errcode']);
			$this->error('发生错误：错误代码'.$js['errcode'].',微信返回错误信息：'.$errmsg);
		}
	}*/
	return $tmpInfo;
}
function curlGet($url){
	$ch = curl_init();
	$header = "Accept-Charset: utf-8";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$temp = curl_exec($ch);
	return $temp;
}




/*----微信接口类方法----*/
/**
 * @desc  im:获取access_token
 * */
function getAT($eid=0){
		$errbiao = array('-1'=>'系统繁忙','0'=>'请求成功','40001'=>'获取access_token时AppSecret错误，或者access_token无效','40002'=>'不合法的凭证类型','40003'=>'不合法的OpenID','40004'=>'不合法的媒体文件类型','40005'=>'不合法的文件类型','40006'=>'不合法的文件大小','40007'=>'不合法的媒体文件id','40008'=>'不合法的消息类型','40009'=>'不合法的图片文件大小','40010'=>'不合法的语音文件大小','40011'=>'不合法的视频文件大小','40012'=>'不合法的缩略图文件大小','40013'=>'不合法的APPID','40014'=>'不合法的access_token','40015'=>'不合法的菜单类型','40016'=>'不合法的按钮个数','40017'=>'不合法的按钮个数','40018'=>'不合法的按钮名字长度','40019'=>'不合法的按钮KEY长度','40020'=>'不合法的按钮URL长度','40021'=>'不合法的菜单版本号','40022'=>'不合法的子菜单级数','40023'=>'不合法的子菜单按钮个数','40024'=>'不合法的子菜单按钮类型','40025'=>'不合法的子菜单按钮名字长度','40026'=>'不合法的子菜单按钮KEY长度','40027'=>'不合法的子菜单按钮URL长度','40028'=>'不合法的自定义菜单使用用户','40029'=>'不合法的oauth_code','40030'=>'不合法的refresh_token','40031'=>'不合法的openid列表','40032'=>'不合法的openid列表长度','40033'=>'不合法的请求字符，不能包含\uxxxx格式的字符','40035'=>'不合法的参数','40038'=>'不合法的请求格式','40039'=>'不合法的URL长度','40050'=>'不合法的分组id','40051'=>'分组名字不合法','41001'=>'缺少access_token参数','41002'=>'缺少appid参数','41003'=>'缺少refresh_token参数','41004'=>'缺少secret参数','41005'=>'缺少多媒体文件数据','41006'=>'缺少media_id参数','41007'=>'缺少子菜单数据','41008'=>'缺少oauth code','41009'=>'缺少openid','42001'=>'access_token超时','42002'=>'refresh_token超时','42003'=>'oauth_code超时','43001'=>'需要GET请求','43002'=>'需要POST请求','43003'=>'需要HTTPS请求','43004'=>'需要接收者关注','43005'=>'需要好友关系','44001'=>'多媒体文件为空','44002'=>'POST的数据包为空','44003'=>'图文消息内容为空','44004'=>'文本消息内容为空','45001'=>'多媒体文件大小超过限制','45002'=>'消息内容超过限制','45003'=>'标题字段超过限制','45004'=>'描述字段超过限制','45005'=>'链接字段超过限制','45006'=>'图片链接字段超过限制','45007'=>'语音播放时间超过限制','45008'=>'图文消息超过限制','45009'=>'接口调用超过限制','45010'=>'创建菜单个数超过限制','45015'=>'回复时间超过限制','45016'=>'系统分组，不允许修改','45017'=>'分组名字过长','45018'=>'分组数量超过上限','46001'=>'不存在媒体数据','46002'=>'不存在的菜单版本','46003'=>'不存在的菜单数据','46004'=>'不存在的用户','47001'=>'解析JSON/XML内容错误','48001'=>'api功能未授权','50001'=>'用户未授权该api');
		$db  = M('enterprise','mx_');
		$eid = $eid;
		
		//取出过期时间
		$now       = time();
		$regettime = get_field('enterprise', 'mx_', 'acct_gettime', "id = 1");
		$at        = get_field('enterprise', 'mx_', 'access_token', "id = 1");
		
		$where = "id=1";
		//判断是否过期
		if($now >= $regettime || empty($at)){
			$edata = $db->where($where)->find();
			$res = curl('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$edata['appid'].'&secret='.$edata['appsecret']);
			$res = json_decode($res);
			$data['access_token'] 			 = $res->access_token;
			$data['acct_gettime'] 			 = time()+7200;
			$data['acct_regettime']		 	 = time();
			$at 							 = $data['access_token'] ;
			$db->where($where)->save($data);
			
			
		}else{
			$at	= get_field('enterprise', 'mx_', 'access_token', "id = 1");
		}
		return $at;
}



/*设置公众账号所属行业
 * @function set_info
 * @param	 eid 企业ID
 * return 返回：成功返回list，不成功返回false
*/
function set_industry($eid =1, $industry_id1, $industry_id2){
	$at					= getAT($eid);	
	$po_data			= '{
							  "industry_id1":"'.$industry_id1.'",
							  "industry_id2":"'.$industry_id2.'"
						   }';
						   echo $po_data."<br>";
	$res				= api_notice_increment('https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token='.$at, $po_data);
	return $res;
}

/*获取公众账号所属行业
 * @function set_info
 * @param	 eid 企业ID
 * return 返回：成功返回list，不成功返回false
*/
function get_industry($eid =1, $industry_id1, $industry_id2){
	$at					= getAT($eid);
	$res				= api_notice_increment('https://api.weixin.qq.com/cgi-bin/template/get_industry?access_token='.$at);
	return $res;
}

/*获取公众账号模板列表
 * @function set_info
 * @param	 eid 企业ID
 * return 返回：成功返回list，不成功返回false
*/
function get_templetelsit($eid =1){
	$at					= getAT($eid);
	$res				= api_notice_increment('https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token='.$at);
	return $res;
}


/*获取公众账号模板ID
 * @function set_info
 * @param	 eid 企业ID
 * return 返回：成功返回list，不成功返回false
*/
function get_templeteid($eid =1){
	$at					= getAT($eid);
	$po_data			= '{"industry_id1":"1"}';
	$res				= api_notice_increment('https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token='.$at);
	return $res;
}

/*发送消息ID
 * @function set_info
 * @param	 eid 企业ID
 * return 返回：成功返回list，不成功返回false
*/
function po_msg($po_data){
	$at					= getAT($eid);
	$res				= api_notice_increment('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$at, $po_data);
	if($res){
		return $res;
	}else{
		return false;
	}
}


/*判断会员账户余额
 * @function set_info
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function check_member_balance(){
	$userid				= session('user_id');
	$balance			= get_field('member', 'mp_', 'balance', 'id = '.$userid);
	
	$status			= 0; //余额不足 shen
	if($balance < 1){
		$status		= 1;
	}
	return $status;
}

/*---------网站设置数据----------*/

/*网站设置
 * @function set_info
 * @param	 field 取出内容
 * return 返回：成功返回list，不成功返回false
*/
function set_info($field){
	
	$db			= D('set');
	$where		= " id = 1";
	$res		= $db->where($where)->field($field)->find();
	return $res;
}

/**
 * @desc  im:获取固定商品接口，只针对系统内只有一个活动
 * */
function act_fixed_goods($act_id){
	
	$act_db				= D('activite');
	$goods_db			= D('goods');
	
	$act_where			= " is_del = 2  and id = ".$act_id;// id = ".$act_id;
	
	$fixed_goods		= $act_db->where($act_where)->getField('fixed_goods', 1); 
	
	//print_r($fixed_goods);
	
	$gwhere				= "id in (".$fixed_goods.")";
	
	$goods_res			= $goods_db->where($gwhere)->order("position asc")->group('position')->select();
	
	//echo $goods_db->getlastsql();
	//exit;
	foreach($goods_res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['thumb']		= $val['thumb'];
		$list[$key]['position']		= $val['position'];
		$list[$key]['cat_id']		= $val['cat_id'];
	}
	return $list;
}


/**
 * @desc  im:获取数组中重复的值
 * */
function array_repeat($arr){
	if(!is_array($arr)) return $arr;
	
	$arr1 = array_count_values($arr);
	
	$newArr = array();
	
	foreach($arr1 as $k=>$v){
		if($v>1) array_push($newArr,$k); 
	}
	return $newArr;
}

/**
 * @desc  im:获取活动商品接口，只针对系统内只有一个活动
 * */
function act_random_goods($act_id, $position){
	
	$act_db					= D('activite');
	$act_record_db			= D('activite_record');
	$goods_db				= D('goods');
	$userid					= session('user_id');
	
	if($act_id == ''){
		$where				= " is_del = 2 ";// id = ".$act_id;
	}else{
		$where				= " is_del = 2 and id = ".$act_id;// id = ".$act_id;
	}
	$act_res				= $act_db->where($where)->find();
	
	//获取活动所有随机商品ID
	$random_goods_str		= $act_res['random_goods'];
	$random_goods_arr		= explode(',', $random_goods_str);//转数组
	
	//取位置
	$gp_where				= "is_del = 1 and cat_id = 40 and id in($random_goods_str) ";
	$gpo_res				= $goods_db->where($gp_where)->group("position")->getField('position', true);
	
	$position1				= $gpo_res[0];
	$position2				= $gpo_res[1];
	$position3				= $gpo_res[2];
	
	//取出该用户当天的所有获得的随机奖品ID
	//取出今日0点时间戳
	$start_time				= strtotime(date('Y-m-d', time()));
	//定义今日24点时间戳
	$end_time				= $start_time + 24 * 60 * 60;
	
	if($act_id == ''){
		$rec_where			= "userid = ".$userid;
	}else{
		$rec_where			= "activite = $act_id and type = 2 and userid = $userid and sign_time >= $start_time and sign_time <= $end_time";
	}
	//先取所有今天获得的商品ID(随机、固定)
	$rec_res				= $act_record_db->where($rec_where)->group('goods_id')->getField('goods_id', true);
	
	if(empty($rec_res)){
		//无中奖记录
		$goods_ids			= $random_goods_str;
	}else{
		if($random_goods_str === $rgc_id_str){
			//相等则重新计算,相等则代表都中过一次，需要重新来一次
			$goods_ids		= $random_goods_str;
		}else{
			//求差集合,去掉已经中的
			$ragids			= array_diff($random_goods_arr, $rec_res);
			$goods_ids		= implode(',', $ragids);
		}
		
	}
	//取所有的符合条件的商品信息
	//$gp_where				= "id in ($goods_ids) and stock > 0 and (position = $position1)";
	$gp_where				= "id in ($goods_ids) and stock > 0 ";
	$gp_res					= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
	
	foreach($gp_res as $k=>$v){
		$result[$v['position']][]    =   $v;
	}
	$galen						= count($result);
	if($galen == 3){
		//取第一个ID
		$ids_arr1				= $result[$position1];
		$ids_arr2				= $result[$position2];
		$ids_arr3				= $result[$position3];
		
		foreach($ids_arr1 as $k=>$v){
			$gr_res1[$v['id']]   = $v['prize_rate'];
		}
		$goods_id1				= get_rand($gr_res1);
		$goods_info_key1		= arr_search($ids_arr1, "id", $goods_id1);
		$goods_info_arr1		= $ids_arr1[$goods_info_key1];
		//print_r($goods_info_arr1);
		//echo "<br>";
		//取第二个ID
		foreach($ids_arr2 as $k=>$v){
			$gr_res2[$v['id']]   = $v['prize_rate'];
		}
		$goods_id2				= get_rand($gr_res2);
		$goods_info_key2		= arr_search($ids_arr2, "id", $goods_id2);
		$goods_info_arr2		= $ids_arr2[$goods_info_key2];
		
		//取第三个ID
		foreach($ids_arr3 as $k=>$v){
			$gr_res3[$v['id']]   = $v['prize_rate'];
		}
		$goods_id3				= get_rand($gr_res3);
		$goods_info_key3		= arr_search($ids_arr3, "id", $goods_id3);
		$goods_info_arr3		= $ids_arr3[$goods_info_key3];
		
	}elseif($galen == 2){
		//获取键值和位置
		$po_arr					= array_keys($result);
		$position1				= $po_arr[0];
		$position2				= $po_arr[1];
		$gp_arr3				= array_diff($gpo_res, $po_arr);//计算第三个位置
		$position3				= implode(',', $gp_arr3);
		
		$ids_arr1				= $result[$position1];
		$ids_arr2				= $result[$position2];
		$ids_arr3				= array();
		//取第一个
		foreach($ids_arr1 as $k=>$v){
			$gr_res1[$v['id']]   = $v['prize_rate'];
		}
		$goods_id1				= get_rand($gr_res1);
		$goods_info_key1		= arr_search($ids_arr1, "id", $goods_id1);
		$goods_info_arr1		= $ids_arr1[$goods_info_key1];
		
		//取第二个ID
		foreach($ids_arr2 as $k=>$v){
			$gr_res2[$v['id']]   = $v['prize_rate'];
		}
		$goods_id2				= get_rand($gr_res2);
		$goods_info_key2		= arr_search($ids_arr2, "id", $goods_id2);
		$goods_info_arr2		= $ids_arr2[$goods_info_key2];
		
		//取第三个
		$goods_id1_key			= arr_search($ids_arr1, "id", $goods_id1);
		$goods_id2_key			= arr_search($ids_arr2, "id", $goods_id2);
		unset($ids_arr1[$goods_id1_key]);
		unset($ids_arr2[$goods_id2_key]);
		
		$ids_arr3				= array_merge($ids_arr1, $ids_arr2);
		if(empty($ids_arr3)){
			$ids_where3			= "id in ($random_goods_str) and stock > 0 and position = $position3";
			$ids_arr3			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			if(empty($ids_arr3)){
				$ids_where3			= "id in ($random_goods_str) and stock > 0 and id not in ($goods_id1, $goods_id2)";
				$ids_arr3			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			}
		}
		foreach($ids_arr3 as $k=>$v){
			$gr_res3[$v['id']]   = $v['prize_rate'];
		}
		$goods_id3				= get_rand($gr_res3);
		$goods_info_key3		= arr_search($ids_arr3, "id", $goods_id3);
		$goods_info_arr3		= $ids_arr3[$goods_info_key3];
		
		$goods_info_arr3['position']	= $position3;
		
		
		
	}elseif($galen == 1){
		//获取键值和位置
		$po_arr					= array_keys($result);
		$position1				= $po_arr[0];
		$gp_arr					= array_diff($gpo_res, $po_arr);//计算2,3两个位置
		$gp_arr_t				= array_values($gp_arr);//重新排序
		$position2				= $gp_arr_t[0];
		$position3				= $gp_arr_t[1];
		$ids_arr1				= $result[$position1];
		$ids_arr2				= array();
		$ids_arr3				= array();
		//echo $position3;
		//echo "<br>";
		//print_r($ids_arr1);
		//取第一个
		foreach($ids_arr1 as $k=>$v){
			$gr_res1[$v['id']]   = $v['prize_rate'];
		}
		$goods_id1				= get_rand($gr_res1);
		$goods_info_key1		= arr_search($ids_arr1, "id", $goods_id1);
		$goods_info_arr1		= $ids_arr1[$goods_info_key1];
		
		//第二个
		$goods_id1_key			= arr_search($ids_arr1, "id", $goods_id1);
		unset($ids_arr1[$goods_id1_key]);
		
		$ids_arr2				= $ids_arr1;
		if(empty($ids_arr2)){
			$ids_where2			= "id in($random_goods_str) and stock > 0 and position = $position2";
			$ids_arr2			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			if(empty($ids_arr2)){
				$ids_where3			= "id in ($random_goods_str) and stock > 0 and id not in ($goods_id1)";
				$ids_arr3			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			}
		}
		foreach($ids_arr2 as $k=>$v){
			$gr_res2[$v['id']]   = $v['prize_rate'];
		}
		$goods_id32				= get_rand($gr_res2);
		$goods_info_key2		= arr_search($ids_arr2, "id", $goods_id2);
		$goods_info_arr2		= $ids_arr2[$goods_info_key2];
		$goods_info_arr2['position']	= $position2;
		//print_r($ids_arr1);
		//echo "<br>";
		//第三个
		$goods_id1_key			= arr_search($ids_arr1, "id", $goods_id1);
		$goods_id2_key			= arr_search($ids_arr1, "id", $goods_id2);
		unset($ids_arr1[$goods_id1_key]);
		unset($ids_arr1[$goods_id2_key]);
		
		$ids_arr3				= $ids_arr1;
		if(empty($ids_arr3)){
			$ids_where3			= "id in($random_goods_str) and stock > 0 and position = $position3";
			$ids_arr3			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			if(empty($ids_arr3)){
				$ids_where3			= "id in($random_goods_str) and stock > 0 and id not in ($goods_id1, $goods_id2)";
				$ids_arr3			= $goods_db->where($gp_where)->field('id,prize_rate,position,thumb,name, cat_id')->select();
			}
		}
		foreach($ids_arr3 as $k=>$v){
			$gr_res3[$v['id']]   = $v['prize_rate'];
		}
		$goods_id3				= get_rand($gr_res3);
		$goods_info_key3		= arr_search($ids_arr3, "id", $goods_id3);
		$goods_info_arr3		= $ids_arr3[$goods_info_key3];
		$goods_info_arr3['position']	= $position3;
	}
	$goods_list[0]				= $goods_info_arr1;
	$goods_list[1]				= $goods_info_arr2;
	$goods_list[2]				= $goods_info_arr3;
	
	
	
	//print_r($goods_list);
	//exit;
	
	return $goods_list;
}

/**
 * @desc  im:从数组中按某个键值名查找某个值，并返回键值
 * */
function arr_search($stack, $keyname, $keyWord) {
    foreach ($stack as $key => $val) {
		if($val[$keyname] == $keyWord){
            return $key;
        }
    }
    return false;
}

/**
 * @desc  im:处理活动记录
 * */
function handle_act($act_id, $goods_id, $prize_type){
	$act_db							= D('activite');
	$act_record_db					= D('activite_record');
	$member_db						= D('member');
	$activite_number_db				= D('activite_number');
	$goods_db						= D('goods');
	$error_db						= D('errorlog');
	$log_db							= D('loger');
	
	$userid							= session('user_id');
	$act_id							= 21;
	
	//取用户信息
	$user_where						= array('id'=>$userid);
	$user_res						= $member_db->where($user_where)->field('open_id, nick')->find();
	$openid							= $user_res['open_id'];
	$nick							= $user_res['nick'];
	
	if(!$user_res){
		$res['status']				= 31;
		$res['msg']					= "用户信息获取失败";
		return $res;
		exit;
	}
	
	//取商品信息
	$goods_where					= array('id'=>$goods_id);
	$goods_res						= $goods_db->where($goods_where)->field('name, price')->find();
	$goods_name						= $goods_res['name'];
	$goods_price					= $goods_res['price'];
	
	if(!$goods_res){
		$res['status']				= 32;
		$res['msg']					= "商品信息获取失败";
		return $res;
		exit;
	}
	
	//取活动名称
	$act_where						= array('id'=>$act_id);
	$act_res						= $act_db->where($act_where)->field('name')->find();
	$act_name						= $act_res['name'];
	
	if(!$act_res){
		$res['status']				= 33;
		$res['msg']					= "活动名称获取失败";
		return $res;
		exit;
	}
	//申卫帅 首先检查是否足够扣钱 2016-06-10 12:36
	$userBalance 					= $member_db->where(array('id'=>$userid))->find();
	if(!isset($userBalance['balance']) && !$userBalance['balance']>=1){
		$res['status']				= 35;
		$res['msg']					= "用户余额不足";
		return $res;
		exit;
	}
	//存入中奖记录表
	$data['activite']				= $act_id;
	$data['type']					= $prize_type;
	$data['userid']					= $userid;
	$data['sign_time']				= time();
	$data['expiration_time']		= strtotime(date("Y-m-d",strtotime("+1 day")))+604800;//过期时间,从中奖的第二天0点开始算起
	$data['status']					= 1;
	$data['deliver']				= 1;
	$data['goods_id']				= $goods_id;
	$actr_id						= $act_record_db->add($data);
	if(!$actr_id){
		$res['status']				= 34;
		$res['msg']					= "活动记录增加失败";
		return $res;
		exit;
	}else{
		//计算提成
		settlement_money($userid, 1);//错误已经在后台记录
		//扣除会员余额
		session('actr_id', $actr_id);
		 // 用户的余额减1
		if($member_db->where(array('id'=>$userid))->setDec('balance')){
			//存消费记录
			member_consumption($userid, 'yyplay', 1, $actr_id, "用户参与摇摇活动产生消费");
			
			
		}else{
			$error_data['user_id']	= $userid;
			$error_data['obj_id']	= $actr_id;
			$error_data['type']		= 2;
			$error_data['time']		= time();
			$error_data['ip']		= realIp();
			$error_data['info']		= "扣减用户余额失败";
			$error_data['sql']		= $member_db->getlastsql();
			$error_db->add($error_data);
		}
		
		//增加活动已玩次数
		if(!$act_db->where(array('id'=>$act_id))->setInc('sum_play',1)){
			//记录问题日志
			$error_data['user_id']	= $userid;
			$error_data['obj_id']	= $actr_id;
			$error_data['type']		= 2;
			$error_data['time']		= time();
			$error_data['ip']		= realIp();
			$error_data['info']		= "活动次数增加失败";
			$error_data['sql']		= $act_db->getlastsql();
			$error_db->add($error_data);
		}
		//商品中奖数
		if(!$goods_db->where(array('id'=>$goods_id))->setInc('end_num',1)){
			//记录问题日志
			$error_data['user_id']	= $userid;
			$error_data['obj_id']	= $actr_id;
			$error_data['type']		= 2;
			$error_data['time']		= time();
			$error_data['ip']		= realIp();
			$error_data['info']		= "商品中奖数增加失败";
			$error_data['sql']		= $goods_db->getlastsql();
			$error_db->add($error_data);
		}
		
		//处理次数记录
		//取出今日0点时间戳
		$start_time					= strtotime(date('Y-m-d', time()));
		
		//定义今日24点时间戳
		$end_time					= $start_time + 24 * 60 * 60;
		
		//定义当前时间戳
		$now						= time();
		
		$acmn_where					= "activiteid = ".$act_id." and userid = ".$userid." and start_time <= ".$now." and end_time >= ".$now;
		
		$acmn_res					= $activite_number_db->where($acmn_where)->find();
		if($acmn_res){
			// 当日娱乐次数+1
			if(!$activite_number_db->where($acmn_where)->setInc('number',1)){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $actr_id;
				$error_data['type']		= 2;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户当日摇奖数增加失败";
				$error_data['sql']		= $activite_number_db->getlastsql();
				$error_db->add($error_data);
			}
			//扣减商品库存
			if(!$goods_db->where(array('id'=>$goods_id))->setInc('end_num',1)){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $actr_id;
				$error_data['type']		= 2;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "扣减商品库存失败";
				$error_data['sql']		= $goods_db->getlastsql();
				$error_db->add($error_data);
			}
		}else{
			$cn_data['userid']		= $userid;
			$cn_data['activiteid']	= $act_id;
			$cn_data['number']		= 1;
			$cn_data['start_time']	= $start_time;
			$cn_data['end_time']	= $end_time;
			 // 插入记录+1
			if($activite_number_db->add($cn_data)){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $actr_id;
				$error_data['type']		= 2;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户当日摇奖数增加失败";
				$error_data['sql']		= $activite_number_db->getlastsql();
				$error_db->add($error_data);
			}
		}
		//中奖消息推送
		//消息内容
		$po_data						= '{
											   "touser":"'.$openid.'",
											   "template_id":"yolsbz4tDfUtOU48JMGKtCPJLiSXw46VUi5DeVvPkf8",
											   "url":"http://n.xiongdada.com.cn/Mshop/user/prize_index/state/0",            
											   "data":{
													"first": {"value":"亲爱的 '.$nick.',你好！恭喜您获得战利品:'.$goods_name.'","color":"#173177"},
													"tradeDateTime": {"value":"'.date('Y-m-d H:i').'","color":"#173177"},
													"orderType": {"value":"摇摇商品订单","color":"#173177"},
													"customerInfo": {"value":"暂未填写收货地址","color":"#173177"},
													"orderItemName": {"value":"客户热线","color":"#173177"},
													"orderItemData": {"value":"400-6900-590(8：30-21：00)","color":"#173177"},
													"remark": {"value":"感谢您对投币摇摇的支持，祝你好运！点击“详情”查看订单并填写收货地址","color":"#173177"}
													}
										   }';
										   
		$po_res							= po_msg($po_data);
		$po_res_arr						= json_decode($po_res, true);
		if($po_res_arr['errcode'] == 0){
			
			$data['model']					= "prize_remind";
			$data['action']					= "发送中奖提醒通知";
			$data['info_id']				= '';
			$data['op_desc']				= "成功给 $openid 发送1条记录，消息ID为:".$po_res_arr['msgid'];
			$data['op_id']					= 0;//系统自动执行
			$data['op_time']				= time();
			$data['op_id']					= realIp();
			$log_db->add($data);

		}else{
			//记录问题日志
			$error_data['user_id']		= $userid;
			$error_data['obj_id']		= 0;
			$error_data['time']			= time();
			$error_data['ip']			= realIp();
			$error_data['info']			= "发送中奖消息失败,错误信息：$po_res";
			$error_db->add($error_data);
		}
		$res['status']					= 0;
		$res['actr_id']					= $actr_id;
	}
	return $res;
}

/**
 * @desc  im:验证当前用户的次数
 * */
function check_act_mn($act_id){
	$acmn_db			= D('activite_number');
	//echo $act_id;
	$userid				= session('user_id');
	
	$now				= time();
	
	$acmn_where			= "activiteid = ".$act_id." and userid = ".$userid." and start_time <= ".$now." and end_time >=".$now;
	
	$acmn_res			= $acmn_db->field('number')->where($acmn_where)->find();
	
	if($acmn_res){
		$member_number	= $acmn_res['number'];
	}else{
		$member_number	= 0;
	}
	
	$act_number			= get_field('activite', 'mp_', 'more', 'id = '.$act_id);
	
	$number				= $act_number-$member_number;
	
	return $number;
	
}
/**
 * @desc  im:获取微信场景二维码接口
 * */
function getcode($eid=1, $id){
	$at						= getAT($eid);
	
	if(empty($id)){
		$msg				= "用户ID不能为空";
		exit;
	}
	
	$po_data				= '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": "'.$id.'"}}}';
	$res 					= api_notice_increment('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$at, $po_data);
	$res 					= json_decode($res, true);
	
	$ticket					= $res['ticket'];
	$url					= $res['url'];
	
	//自己生成二维码
	$content 				= $url; //二维码内容
	$tpgs					="png";//图片格式
	$qrcode_bas_path		='Public/upload/images/member/'; //储存路径
	$errorCorrectionLevel 	= 'L';//容错级别
	$matrixPointSize 		= 20;//生成图片大小
	//取出会员头像作为LOGO加到二维码上
	$logo					= D('member')->where("id = '$id'")->getField('head_pic', 1);//"http://127.0.0.1/Public/static/img/icon-t.jpg";
	
	$matrixMarginSize 		= 1;//边距大小
	$data_code['code']		= "/".qrcode($content,$tpgs,$qrcode_bas_path,$logo,$errorCorrectionLevel,$matrixPointSize,$matrixMarginSize);
	$res					= D('member')->where("id = '$id'")->save($data_code);
	
	
	//$res = json_decode($res);
	
	return $data_code['code'];
}



/*消息回复接口
 * @param (string)$type 订单类型 1 未付款订单，2 已付款待收货订单， 3 待评价订单 4 退单
 * return 返回：成功返回列表，不成功返回fals
*/
function responseMsg(){
	//get post data, May be due to the different environments
	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

	//extract post data
	if (!empty($postStr)){
			/* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
			   the best way is to check the validity of xml by yourself */
			libxml_disable_entity_loader(true);
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
			$toUsername = $postObj->ToUserName;
			$keyword = trim($postObj->Content);
			$time = time();
			$textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";             
			if(!empty( $keyword ))
			{
				$msgType = "text";
				$contentStr = "Welcome to wechat world!";
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
				echo $resultStr;
			}else{
				echo "Input something...";
			}

	}else {
		echo "";
		exit;
	}
}

//天气接口
function get_weather(){
	/*$res = curl('http://m.weather.com.cn/atad/101241101.html');
	$res = json_decode($res, true);
	$data['access_token'] 			 = $res->access_token;
	$data['acct_gettime'] 			 = time();
	$data['acct_regettime']		 	 = $gettime + 7200;
	$at 							 = $data['access_token'] ;
	*/
	//$city="嘉兴";
	//$content = file_get_contents("http://api.map.baidu.com/telematics/v3/weather?location=%E5%98%89%E5%85%B4&output=json&ak=5slgyqGDENN7Sy7pw29IUvrZ");
	//print_r(json_decode($content));
	
}

//批量过滤
function stripslashes_array(&$array) { 
	while(list($key,$var) = each($array)) { 
		if ($key != 'argc' && $key != 'argv' && (strtoupper($key) != $key || ''.intval($key) == "$key")) { 
			if (is_string($var)) { 
				$array[$key] = stripslashes($var); 
			} 
			if (is_array($var))  { 
				$array[$key] = stripslashes_array($var); 
			} 
		} 
	} 
	return $array; 
} 

// 替换HTML尾标签,为过滤服务 
function lib_replace_end_tag($str) { 
	if (empty($str)) return false;
	$str = htmlspecialchars($str);
	$str = str_replace( '/', "", $str);
	
	$str = str_replace( ')', "", $str);
	$str = str_replace( ')', "", $str);
	$str = str_replace("\\", "", $str);
	$str = str_replace("&gt", "", $str);
	$str = str_replace("&lt", "", $str);
	$str = str_replace("<SCRIPT>", "", $str);
	$str = str_replace("</SCRIPT>", "", $str);
	$str = str_replace("<script>", "", $str);
	$str = str_replace("</script>", "", $str);
	$str = str_replace("select","",$str);
	$str = str_replace("join","",$str);
	$str = str_replace("union","",$str);
	$str = str_replace("where","",$str);
	$str = str_replace("insert","",$str);
	$str = str_replace("delete","",$str);
	$str = str_replace("update","",$str);
	$str = str_replace("like","",$str);
	$str = str_replace("drop","",$str);
	$str = str_replace("create","",$str);
	$str = str_replace("modify","",$str);
	$str = str_replace("rename","",$str);
	$str = str_replace("alter","",$str);
	$str = str_replace("cas","",$str);
	$str = str_replace("&","",$str);
	$str = str_replace("%", "", $str);
	$str = str_replace("$", "", $str);
	$str = str_replace("^", "", $str);
	$str = str_replace("*", "", $str);
	$str = str_replace("(", "", $str);
	$str = str_replace(")", "", $str);
	$str = str_replace("-", "", $str);
	$str = str_replace("+", "", $str);
	$str = str_replace("=", "", $str);
	$str = str_replace(">","",$str);
	$str = str_replace("<","",$str);
	$str = str_replace(" ",chr(32),$str);
	$str = str_replace(" ",chr(9),$str);
	$str = str_replace("    ",chr(9),$str);
	$str = str_replace("&",chr(34),$str);
	$str = str_replace("'",chr(39),$str);
	$str = str_replace("<br />",chr(13),$str);
	$str = str_replace("''","'",$str);
	$str = str_replace("css","",$str);
	$str = str_replace("CSS","",$str);
	 
	return $str;
  
}

function Api_Request($url, $data, $method = "GET"){
		
	$ch = curl_init();	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	//以下两行，忽略https证书
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE) ;
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$method = strtoupper($method);
	if ($method == "POST") {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data) ;
	}
	$content = curl_exec($ch);
	curl_close($ch);
	var_dump($content);
	
	return $content;
}

//高级过滤方式----暂时无用，未完成

/*function int_check($str) {
	 $getfilter = "'|(and|or)\\b.+?(>|<|=|in|like)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	foreach($str as $key=>$value){
		$res				= stopattack($key, $value, $getfilter);
		if($res == '1'){
			return false;
		}else{
			return true;
		}
	}
	
	
}*/
/**
 * 参数检查并写日志
 
function stopattack($StrFiltKey, $StrFiltValue, $ArrFiltReq){
	if(is_array($StrFiltValue))$StrFiltValue = implode($StrFiltValue);
	if (preg_match("/".$ArrFiltReq."/is",$StrFiltValue) == 1){ 
		return '1';
		 
		//$this->writeslog($_SERVER["REMOTE_ADDR"]."    ".strftime("%Y-%m-%d %H:%M:%S")."    ".$_SERVER["PHP_SELF"]."    ".$_SERVER["REQUEST_METHOD"]."    ".$StrFiltKey."    ".$StrFiltValue);
		//$this->error('您提交的参数非法,系统已记录您的本次操作！');
	}else{
		return '0';
	}
}*/
/**
 * SQL注入日志
 
function writeslog($log){
	$log_path = CACHE_PATH.'logs'.DIRECTORY_SEPARATOR.'sql_log.txt';
	echo $log_path;
	exit;
	$ts = fopen($log_path,"a+");
	fputs($ts,$log."\r\n");
	fclose($ts);
}*/

/**
 * @desc  中奖概率  
 * $cat_id 活动id 
 * 1.固定中奖基数
 * 2.随机中奖
 * 3.抽第几次必中奖（首先指定这个）
 * */
function probability_winning($cId1,$cId2,$cId3){
	
	$activite_db		= D('activite');
	$userid				= session('user_id');	
	$activite_res		= $activite_db->field('sum_play,fixed_goods,random_goods,will_goods')->where("id=21")->find();
	
	$activite_number_db = D("activite_number");
	$userid = session('user_id');
	$time = time();
	$played = $activite_number_db->field('number')->where("userid={$userid} and activiteid=21 and start_time<='{$time}' and end_time>='{$time}'")->find();
	$nowNum = $played['number']+1; //正在抽奖的次数
	
	$willGoods = $activite_res['will_goods'] ? json_decode($activite_res['will_goods'],true) : false; //必中奖项
	$wileGoodsId = 0;//必中的id 
	$permanent = 0;//必中的id
	if($willGoods){
		foreach ($willGoods as $val){
			if($nowNum == $val['prize_number']){
				
				$good = getCommodityStocks($val['goods_id']); //必中商品是否有库存 申卫帅2016-06-17
				$res_stock = $good['stock']-$good['end_num']; //最大库存
				if($res_stock>=1){ //有库存  申卫帅2016-06-17
					if($val['permanent']){
						$willGoodsNum = getGoodsPermanent($userid,$val['goods_id']);
						$activite_will = D("activite_will");
						$con = $activite_will->where("user_id={$userid}")->count();
						if($con<=0 && $willGoodsNum<=0){
							$permanent = 1;
							$wileGoodsId = $val['goods_id'] ? $val['goods_id'] : 0;
						}
					}else{
						$wileGoodsId = $val['goods_id'] ? $val['goods_id'] : 0;
					}
				}
			}
		}
	}	
	$goods_db			= D('goods');
	
	$isW = 1; //1必中  2固定   3随机 9永久必中
	$res = 0; //返回的结果 游戏id
	
	if($wileGoodsId && $permanent==1){
		$isW = 9; //永久必中
		$res = $wileGoodsId;
	}
	
	if($wileGoodsId && $permanent==0){
		$res = $wileGoodsId;
	}
	
	if(!$res){
		$isW = 2;
		$fixed_goods = $activite_res['fixed_goods'];
		//$error_db			= D('error');
		/*$e_data['msg']			= $fixed_goods;
		$error_db->add($e_data); */
		//1.固定中奖	
		$goods_res 			= $goods_db->field('id,position,prize_position,stock,end_num')->where("cat_id=39 and id in ($fixed_goods)")->select();
		
		$data				= array(); //结果集
		
		$i					= 0;
		foreach ($goods_res as $val){
			//只要 $activite_res['sum_play']是$val['prize_position']的倍数就取出来
			if( $val['prize_position']>=1 && ($activite_res['sum_play'] % $val['prize_position'] == 0) ){
				$data[$i]['id'] 		= intval($val['id']);
				$data[$i]['stock']  	= intval($val['stock']);
				$data[$i]['end_num']  	= intval($val['end_num']);
				$i++;
			}		
		}
		
		$res_stock = $data[0]['stock']-$data[0]['end_num']; //最大库存
		if(count($data)==1 && $res_stock>=1){		
			$willGoodsNum = getGoodsPermanent($userid,$data[0]['id']);
			if($willGoodsNum<=0){
				$res = $data[0]['id'] ? $data[0]['id'] : 0; //是否有固定中奖的值
			}
			
		}
		//如果比对出来的大于等于2就进行库存比对
		if(count($data)>=2){		
			for ($i=1; $i<count($data); $i++){
				if($data[$i]['stock']-$data[$i]['end_num']>=1 && $res_stock>=1){
					if($res_stock < ($data[$i]['stock']-$data[$i]['end_num']) ){
						$res = $data[$i]['id'];
					}
				}
			}		
		}
	}
	
	//2.随机中奖
	if(!$res){	
		$isW = 3;
		$goods_random_res 	= $goods_db->field('id,position,prize_rate,stock,end_num')->where("id='$cId1' or id='$cId2' or id='$cId3'")->select();
		
		$randData = array();
		foreach ($goods_random_res as $randVal){
			if( ($randVal['stock']-$randVal['end_num'])>=1 ){
				$randData[$randVal['id']] = $randVal['prize_rate'];
			}
		}
		
		$activite_record_db = D("activite_record");
		$userid = session("user_id");
		
		$id = 0;
		if( count($randData)>=1 ){
			$id = get_rand($randData);
			$startTime = strtotime(date("Y-m-d"));
			$endTime = $startTime+86400;
			$gameNum = $activite_record_db->where("userid='$userid' and goods_id='$id' and sign_time>=$startTime and sign_time<=$endTime")->count();
			//if($gameNum>=1){
				$log_db						= D('error');			
				$log_data['msg']			= $activite_record_db->getLastSql().'---'.$id.'---'.$userid;
				$log_db->add($log_data);
				/* unset($randData[$id]); //删除已经重复的
				$id = get_rand($randData); //重新执行
			} */
		}
		
		$res = $id;
	}
	$da = array('isW'=>$isW,'gameId'=>$res);
	return json_encode($da);
}



/**
 * @desc 计算概率
 */
function get_rand($proArr) {
	$result = '';
	//概率数组的总概率精度
	$proSum = array_sum($proArr);

	//概率数组循环
	foreach ($proArr as $key => $proCur) {
		$randNum = mt_rand(1, $proSum);
		if ($randNum <= $proCur) {
			$result = $key;
			break;
		} else {
			$proSum -= $proCur;
		}
	}
	unset ($proArr);
	return $result;
}



/**
 * @desc 获得微信用户的详细信息
 */
function set_wx_user_info($openid=false,$userid=false){
	//获得token
	$accessToken 	= getAT();
	$openid 		= $openid ? $openid : session('openid');
	$userid			= $userid ? $userid : session('user_id');
	$member_db  = D("member");
	$res = 1; //结果值
	$user_data  = $member_db->field('nick,sex,head_pic,city,province,country')->where("id=$userid")->find();
	if($user_data['nick'] && $user_data['sex'] && $user_data['head_pic']  && $user_data['city'] && $user_data['province'] && $user_data['country'] ){
		$res = 0;
	}else{	
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$accessToken&openid=$openid";
		$res = json_decode(curl($url));
		
		$data = array();
		$data['nick'] 			= $res->nickname; 	//微信的昵称
		$data['sex'] 			= $res->sex; 		//微信的性别
		$data['head_pic']		= $res->headimgurl; //微信的头像
		$data['city']			= $res->city; 		//广州
		$data['province']		= $res->province; 	//广东
		$data['country']		= $res->country; 	//中国
		
		
		$where					= "id=$userid";
		if($member_db->where($where)->save($data)){
			$res = 0;
		}
	}

	return $res;
}



// 获取jsticket 两小时有效
function getjsticket(){ // 只允许本类调用，继承的都不可以调用，公开调用就更不可以了
	$access_token 	= getAT();//获得token
// 	echo $access_token;
	$enterprise     = M("mx_enterprise");
	$data			= $enterprise->field('acct_gettime,ticket')->where("id=2")->find();
	$res 			= $data['ticket']; //返回的jsticket
	
	$time = time();
	if($time-$data['acct_gettime'] >= 7200){
		
		$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi"; // 两小时有效
		$rurl = file_get_contents($url);
		$rurl = json_decode($rurl,true);
		if($rurl['errcode'] != 0){
			return false;
		}else{
			$jsticket = $rurl['ticket'];
			$da['ticket'] 		= $jsticket;
			$da['acct_gettime'] = time();
			$enterprise->where("id=2")->save($da);
// 			echo $enterprise->getLastSql();
		}
			
		$res = $jsticket;
	}
	
 return $res;
}


// 获取图片地址
function getmedia($media_id){
	$access_token 	= getAT();//获得token
	$foldername		= date("Y-m-d");
	$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$media_id;
	if (!file_exists("./Public/upload/images/sun/".$foldername)) {
		mkdir("./Public/upload/images/sun/".$foldername, 0777, true);
	}
	$targetName = './Public/upload/images/sun/'.$foldername.'/'.date('YmdHis').rand(100000,999999).'.jpg';
	$ch = curl_init($url); // 初始化
	$fp = fopen($targetName, 'wb'); // 打开写入
	curl_setopt($ch, CURLOPT_FILE, $fp); // 设置输出文件的位置，值是一个资源类型
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_exec($ch);
	curl_close($ch);
	fclose($fp);
	return trim($targetName,".");
}

// 获取 signature
function getsignature($actr_id){
	$noncestr = '0EmjMv8zH22KHxqw';
	$jsapi_ticket = getjsticket();
	$timestamp = 1462279044;
	$url = 'http://n.xiongdada.com.cn/mshop/sun/add/actr_id/'.$actr_id.'/';
	$string1 = 'jsapi_ticket='.$jsapi_ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
	$signature = sha1($string1);
	return $signature;
}









// 获取 signature
function getsignature_new($url){
	$noncestr = '0EmjMv8zH22KHxqw';
	$jsapi_ticket = getjsticket();
	$timestamp = 1462279044;
	$url = "$url";
	$string1 = 'jsapi_ticket='.$jsapi_ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
	$signature = sha1($string1);
	return $signature;
}


/** * 
 * @desc   验证字符串等。
 * @param  $str 所要验证的字符串
 * @param  string $type 所要验证的类型
 * @return bool
 */
function check_submit($str,$type="require")
{
	// 预定义正则验证规则
	$rule = [
			'require'  => '/\S+/', //判断是空
			'email'    => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
			'url'      => '/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(:\d+)?(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/',
			'currency' => '/^\d+(\.\d+)?$/',//货币
			'number'   => '/^\d+$/',
			'zip'      => '/^\d{6}$/',
			'integer'  => '/^[-\+]?\d+$/', //整型
			'double'   => '/^[-\+]?\d+(\.\d+)?$/', //双
			'english'  => '/^[A-Za-z]+$/', //英文
			'phone'	   => '/^1[3|4|5|7|8]\d{9}$/',  //手机验证
			'telephone'=> '/^([0-9]{3,4}-)?[0-9]{7,8}$/',//电话
	];
	
	return preg_match($rule[$type],$str);
}

/**
 * @desc 不管是关注与否获得其openid
 * @param unknown $code
 * @return mixed
 */
function getOpenid($code){
	$db  = M('enterprise','mx_');
	$where	= array('id'=>1);
	$edata = $db->where($where)->find();
	/* $tz = urlencode($lianjie);
	$fasturl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$edata['appid']}&redirect_uri={$tz}&response_type=code&scope=snsapi_base&state=123#wechat_redirect"; */
	$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$edata['appid']}&secret={$edata['appsecret']}&code={$code}&grant_type=authorization_code";
	$wxuser = json_decode(getHttp($url),true);
	//print_r($wxuser);
	return $wxuser['openid'];
}

function getHttp($url) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_URL, $url);

	$res = curl_exec($curl);
	curl_close($curl);

	return $res;
}



/*会员列表
 * @function carousel_list
 * @param String $where 条件
 * @param String $fields 字段英文下的,号分隔
 
 * return 返回：成功返回list，不成功返回false
*/
function rmember_list($where, $fields){
	
	$db									= D('member');
	$odb								= D('order');
	$where								= " is_del = 1 ".$where;// and start_time <= '$now' <= end_time ";
	$order								= " `create_time` desc ";
	$res								= $db->where($where)->field('id, nick, create_time')->order($order)->select();
	
	foreach($res as $key => $val){
		
		$userid							= $val['id'];
		$list[$key]['id']				= $val['id'];
		$list[$key]['nick']				= msubstr($val['nick'], 0, 3)."...";
		$list[$key]['time']				= date('Y-m-d', $val['create_time']);
		
		//获取是否消费
		//按照ID取出订单
		$o_where						= array('user_id'=>$userid, 'pay'=>0, 'model'=>2);
		$o_count						= $odb->where($o_where)->count();
		if($o_count > 0){
			$list[$key]['consumption']	= "已消费";
		}else{
			$list[$key]['consumption']	= "未消费";
		}
		
	}
	
	return $list;
}


/** * 
 * @desc   分销结算方法,以消费者角度，在消费时调用
 * @param  (int)$userid 消费会员ID
 * @param  (int)$amount 消费数量
 * @return bool
 */
function settlement_money($userid, $amount = 1){
	
	//声明
	$member_db						= D('member');
	$comr_db						= D('commission_record');
	$error_db						= D('errorlog');
	$now							= time();
	//$log_db							= D('error');
	//分销商加提成
	
	//取出该用户的分销商级别
	$agencylevel					= $member_db->where("id=$userid")->getField('agencylevel', 1);
	if($agencylevel == 1){
		//特级会员，不计算所有上家提成
		return false;
	}
	$m_where						= array('id'=>$userid);
	$agency							= $member_db->where($m_where)->getField('agency', 1);
	
	if($agency != 0){
		//获取提成比率
		$percentage					= 0.05;
		
		//计算提成金额
		$not_mentioned				= $amount*$percentage;
		$agency_where				= "is_del != 0 and id = $agency";
		//变更分销商的未提现金额
		$mentioned_res				= $member_db->where($agency_where)->setInc('not_mentioned', $not_mentioned);
		if(!$mentioned_res){
			//记录问题日志
			$error_data['user_id']	= $userid;
			$error_data['obj_id']	= $agency;
			$error_data['type']		= 1;
			$error_data['time']		= time();
			$error_data['ip']		= realIp();
			$error_data['info']		= "用户的分销商佣金更新失败";
			$error_data['sql']		= $member_db->getlastsql();
			$error_db->add($error_data);
		}else{
			//佣金记录
			$comra_data['user_id']		= $agency;
			$comra_data['source_id']	= $userid;
			$comra_data['consumption']	= $amount;
			$comra_data['commission']	= $not_mentioned;
			$comra_data['create_time']	= $now;
			$comra_res					= $comr_db->add($comra_data);
			if(!$comra_res){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $agency;
				$error_data['type']		= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户的分销商佣金记录更新失败";
				$error_data['sql']		= $comr_db->getlastsql();
				$error_db->add($error_data);
			}
		}
		
	}
	
	//会员逻辑
	//取父级会员ID
	$fm_where						= array('id'=>$userid);
	$fm_res							= $member_db->where($fm_where)->field('recommenders')->find();
	$fm_id							= $fm_res['recommenders'];//取父级会员ID
	if($fm_id != 0){
		//取父会员级别
		$fm_agencylevel					= $member_db->where(array('id'=>$fm_id))->getField('agencylevel', 1);
		//$log_data['msg']				= $fm_agencylevel;
		//$log_db->add($log_data);
		//判断是否为代理商，是则不加
		if($fm_agencylevel != 1){
		
			//获取提成比率
			$fm_percentage					= 0.15;
			//计算提成金额
			$fm_not_mentioned				= $amount*$fm_percentage;
			
			//变更腹肌会员的未提现金额
			$member_mentioned_where			= "is_del != 0 and id = $fm_id";
			$member_mentioned_res			= $member_db->where($member_mentioned_where)->setInc('not_mentioned', $fm_not_mentioned);
			if(!$member_mentioned_res){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $fm_id;
				$error_data['type']		= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户的父级会员佣金更新失败";
				$error_data['sql']		= $member_db->getlastsql();
				$error_db->add($error_data);
			}else{
				//记录提成记录
				$comrfm_data['user_id']			= $fm_id;
				$comrfm_data['source_id']		= $userid;
				$comrfm_data['consumption']		= $amount;
				$comrfm_data['commission']		= $fm_not_mentioned;
				$comrfm_data['create_time']		= $now;
				$comrfm_res						= $comr_db->add($comrfm_data);
				if(!$comrfm_res){
					//记录问题日志
					$error_data['user_id']	= $userid;
					$error_data['obj_id']	= $fm_id;
					$error_data['type']		= 1;
					$error_data['time']		= time();
					$error_data['ip']		= realIp();
					$error_data['info']		= "用户的父级会员佣金记录更新失败";
					$error_data['sql']		= $comr_db->getlastsql();
					$error_db->add($error_data);
				}
			}
			
			
		}
	}
	
	//取祖父级会员
	$ffm_where						= array('id'=>$fm_id);
	$gm_id							= $member_db->where($ffm_where)->getField('recommenders', 1);
	if($gm_id != 0){
		//$log_data['msg']				= "|||".$member_db->getlastsql();
			//$log_db->add($log_data);
		$gm_where						= array('id'=>$gm_id);
		$gm_res							= $member_db->where($gm_where)->field('agencylevel')->find();
		//$log_data['msg']				= "|||".$member_db->getlastsql();
			//$log_db->add($log_data);
		$agencylevel					= $gm_res['agencylevel'];
		if($agencylevel == 2){
			//高级会员算提成
			$gm_percentage				= 0.05;
			//计算提成金额
			$gm_not_mentioned			= $amount*$gm_percentage;
			$mentioned_where			= "is_del != 0 and id = $gm_id";
			//变更祖父级会员的未提现金额
			$mentioned_res				= $member_db->where($mentioned_where)->setInc('not_mentioned', $gm_not_mentioned);
			if(!$mentioned_res){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $gm_id;
				$error_data['type']		= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户的祖父级会员佣金更新失败";
				$error_data['sql']		= $member_db->getlastsql();
				$error_db->add($error_data);
			}else{
				//记录提成记录
				$comrgm_data['user_id']		= $gm_id;
				$comrgm_data['source_id']	= $userid;
				$comrgm_data['consumption']	= $amount;
				$comrgm_data['commission']	= $gm_not_mentioned;
				$comrgm_data['create_time']	= $now;
				$comrgm_res					= $comr_db->add($comrgm_data);
				if(!$comrgm_res){
					//记录问题日志
					$error_data['user_id']	= $userid;
					$error_data['obj_id']	= $gm_id;
					$error_data['type']		= 1;
					$error_data['time']		= time();
					$error_data['ip']		= realIp();
					$error_data['info']		= "用户的祖父级会员佣金记录更新失败";
					$error_data['sql']		= $comr_db->getlastsql();
					$error_db->add($error_data);
				}
			}
		}
	}
	//曾祖父级会员
	$fgm_where						= array('id'=>$gm_id);
	$fgm_id							= $member_db->where($fgm_where)->getField('recommenders', 1);
	
	if($fgm_id != 0){
		//$log_data['msg']				= "|||".$member_db->getlastsql();
			//$log_db->add($log_data);
		$fgm_where						= array('id'=>$fgm_id);
		$fgm_res						= $member_db->where($fgm_where)->field('agencylevel')->find();
		//$log_data['msg']				= "|||".$member_db->getlastsql();
			//$log_db->add($log_data);
		$agencylevel					= $fgm_res['agencylevel'];
		if($agencylevel == 2){
			//高级会员算提成
			$fgm_percentage				= 0.05;
			//计算提成金额
			$fgm_not_mentioned			= $amount*$fgm_percentage;
			//变更分销商的未提现金额
			$fgm_where					= "is_del != 0 and id = $fgm_id";
			$mentioned_res				= $member_db->where($fgm_where)->setInc('not_mentioned', $fgm_not_mentioned);
			if(!$mentioned_res){
				//记录问题日志
				$error_data['user_id']	= $userid;
				$error_data['obj_id']	= $fgm_id;
				$error_data['type']		= 1;
				$error_data['time']		= time();
				$error_data['ip']		= realIp();
				$error_data['info']		= "用户的曾祖父级会员佣金更新失败";
				$error_data['sql']		= $member_db->getlastsql();
				$error_db->add($error_data);
			}else{
				//记录提成记录
				$comrfgm_data['user_id']	= $fgm_id;
				$comrfgm_data['source_id']	= $userid;
				$comrfgm_data['consumption']= $amount;
				$comrfgm_data['commission']	= $fgm_not_mentioned;
				$comrfgm_data['create_time']= $now;
				$comrfgm_res				= $comr_db->add($comrfgm_data);
				if(!$comrgm_res){
					//记录问题日志
					$error_data['user_id']	= $userid;
					$error_data['obj_id']	= $fgm_id;
					$error_data['type']		= 1;
					$error_data['time']		= time();
					$error_data['ip']		= realIp();
					$error_data['info']		= "用户的曾祖父级会员佣金记录更新失败";
					$error_data['sql']		= $comr_db->getlastsql();
					$error_db->add($error_data);
				}
			}
		}
	}
	
}

/** * 
 * @desc   分销结算方法,以消费者角度，通过订单结算，在支付完成时调用
 * @param  (str)$order_sn 订单ID
 * @return bool
 */
function oagency_settlement($order_sn){
	//声明
	$member_db						= D('member');
	$order_db						= D('order');
	
	//取出订单用应用的数据
	$o_where						= array('order_id'=>$order_sn, 'pay'=>0);
	$o_res							= $order_db->where($o_where)->field('user_id', 'actual_payment')->find();
	$user_id						= $o_res['user_id'];//消费的会员ID
	$actual_payment					= $o_res['actual_payment'];//消费额度
	
	//分销商加提成
	$m_where						= array('id'=>$user_id);
	$agency							= $member_db->where($m_where)->getField('agency', 1);
	if($agency != 0){
		//获取提成比率
		$percentage					= 0.05;
		
		//计算提成金额
		$not_mentioned				= $actual_payment*$percentage;
		
		//变更分销商的未提现金额
		$mentioned_res				= $member_db->where(array('id'=>$agency))->setInc('not_mentioned', $not_mentioned);
	}
	
	//会员逻辑
	//取父级会员ID
	$fm_where						= array('id'=>$userid);
	$fm_res							= $member_db->where($fm_where)->field('recommenders')->find();
	$fm_id							= $fm_res['recommenders'];//取父级会员ID
	
	//获取提成比率
	$fmember_percentage				= 0.1;
	//计算提成金额
	$member_not_mentioned			= $amount*$fmember_percentage;
	
	//变更分销商的未提现金额
	$member_mentioned_res			= $member_db->where(array('id'=>$fm_id))->setInc('not_mentioned', $member_not_mentioned);
		
	//取祖父级会员
	$gm_where						= array('recommenders'=>$fm_id);
	$gm_res							= $member_db->where($gm_where)->field('id', 'agency_level')->find();
	$gm_id							= $gm_res['id'];
	$agency_level					= $gm_res['agency_level'];
	if($agency_level == 2){
		//高级会员算提成
		$gmember_percentage			= 0.05;
		//计算提成金额
		$gmember_not_mentioned		= $amount*$gmember_percentage;
		//变更分销商的未提现金额
		$mentioned_res				= $member_db->where(array('id'=>$gm_id))->setInc('not_mentioned', $not_mentioned);
	}
}



/** * 
 * @desc   分销结算方法,以分销商或者引入人角度
 * @param  (int)$userid 结算的会员ID
 * @return bool
 */
 
function fagency_settlement($userid){
	
	if($userid == ''){
		return "error";
		exit;	
	}
	
	
	
	//声明
	$member_db						= D('member');
	$agency_level_db				= D('agency_level');
	$order_db						= D('order');
	
	
	//取出该用户的分销商级别
	$agencylevel					= $member_db->where("id=$userid")->getField('agencylevel', 1);
	
	
	
	//取出分成方式
	$commission_scheme				= $agency_level_db->where("id=$agencylevel")->getField('condition', 1);
	
	//格式化分成方式
	$arr_commission_scheme			= json_decode($commission_scheme, true);
	
	//开始计算价格
	if($agencylevel == 1){
		//取出所有隶属的会员ID
		$am_where					= array('agency'=>$userid);
		$am_res						= $member_db->where($am_where)->field('id')->select();
		$arr_ids					= array_column($am_res, 'id');
		
		$str_ids					= implode(',', $arr_ids);//需要统计的订单ID
		
		//获取提成比率
		$percentage					= 0.05;
		
		//计算佣金
		$m_where					= "user_id in ($str_ids) and sum_type = 0 and model = 2";
		$sum_res					= $order_db->where($m_where)->sum('actual_payment');
		
		$not_mentioned				= $sum_res*$percentage;
		
		//变更账户余额
		$mentioned_res				= $member_db->where(array('id'=>$userid))->setInc('not_mentioned', $not_mentioned);
		
		if($mentioned_res){
			//变更订单状态
			$order_db->where($m_where)->setField('commissionsum_type', 1);
			
		}
	}elseif($agencylevel == 2){
		//高级会员逻辑，向下两级
		//第一级
		$recommenders_where			= array('recommenders'=>$userid);
		$recommenders_res			= $member_db->where($recommenders_where)->field('id')->select();
		$arr_first_ids				= array_column($recommenders_res, 'id');//需要统计的订单ID
		$str_first_ids				= implode(',', $arr_first_ids);
		
		//echo $str_first_ids."<br>";
		$arr_second_ids				= $member_db->where("recommenders in ($str_first_ids)")->field('id')->select();
		$arr_second_ids				= array_column($arr_second_ids, 'id');//需要统计的订单ID
		
		$arr_ids					= array_merge ($arr_first_ids, $arr_second_ids);
		
		//一级二级拼接
		$str_ids					= implode(',', $arr_ids);
		
	}elseif($agencylevel == 3){
		//普通会员逻辑，向下一级
		//第一级
		$recommenders_where			= array('recommenders'=>$userid);
		$recommenders_res			= $member_db->where($recommenders_where)->field('id')->select();
		$arr_ids					= array_column($recommenders_res, 'id');//需要统计的订单ID
		$str_ids					= implode(',', $arr_ids);
	}
	
	$m_where						= "user_id in ($str_ids) and sum_type = 0 and model = 2";
	$sum_res						= $order_db->where($m_where)->sum('actual_payment');
	echo $sum_res;
}

/** * 
 * @desc   统计引进会员数量
 * @param  (int)$userid 会员ID
 * @return bool
 */
function usercount($userid){
	$db									= D('member');
	if($userid == ''){
		$userCount						= "用户ID为空";//$db->where($where)->count('id');
	}else{
		//$where							= array('recommenders'=>$userid);
		$where							= "recommenders = $userid and is_del != 0";//array('recommenders'=>$userid);
		$userCount						= $db->where($where)->count('id');
	}
	return $userCount;
	
}

/** * 
 * @desc   用户佣金统计
 * @param  (int)$userid 会员ID
 * @param  (int)$userid 结算的会员ID
 * @return bool
 */
function user_commission($userid){
	
	$db									= D('member');
	if($userid == ''){
		return	false;
	}else{
		$where							= array('id'=>$userid);
		$userCount						= $db->where($where)->sum('not_mentioned');
	}
	
	return $userCount;
	
}

/** * 
 * @desc   用户佣金记录
 * @param  (Strint)$wehre 查询条件
 * @return bool
 */
function user_commission_record($where){
	
	$db									= D('commission_record');
	$member_db							= D('member');
	if($where == ''){
		return	false;
		exit;
	}
	$order								= " `create_time` desc ";
	
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key=> $val){
		$m_where						= array('id'=>$val['source_id']);
		$list[$key]['nick']				= msubstr($member_db->where($m_where)->getField('nick', 1), 0, 3)."..."; 
		$list[$key]['commission']		= $val['commission'];
		$list[$key]['time']				= date('Y-m-d', $val['create_time']);
		
	}
	
	return $list;
	
}

/** * 
 * @desc   取出用户帐户信息
 * @param  (int)$userid 查询条件
 * @return bool
 */
function user_account($userid){
	$db									= D('member_account');
	$where								= array('user_id'=>$userid);
	
	$res								= $db->where($where)->find();
	
	return $res;
}

/** * 
 * @desc   导出excel方法
 * @param  (String)$expTitle 文件名称
 * @param  (String)$expCellName 列名称
 * @param  (String)$expTableData 数据
 * @return bool
 */
function exportExcel($expTitle,$expCellName,$expTableData){
	$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
	$fileName = $expTitle;//or $xlsTitle 文件名称可根据自己情况设定
	$cellNum = count($expCellName);
	$dataNum = count($expTableData);
	vendor("PHPExcel.PHPExcel");
	$objPHPExcel = new PHPExcel();
	$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
	
	$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
   // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));  
	for($i=0;$i<$cellNum;$i++){
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]); 
	} 
	  // Miscellaneous glyphs, UTF-8   
	for($i=0;$i<$dataNum;$i++){
	  for($j=0;$j<$cellNum;$j++){
		$objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
	  }             
	}  
	
	header('pragma:public');
	header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
	header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
	$objWriter->save('php://output'); 
	exit;   
}


function getWXinfo()
{

	$enterprise     = M("enterprise", "mx_");
	$data			= $enterprise->field('appid,appsecret')->where("id=1")->find();

	return $data; //返回的jsticket
}

/**
 * @desc 增加永久必中记录 申卫帅 2016-06-16 23:43
 */
function addPermanent($actr_id) {
	$userid		= session('user_id');
	$activite_will = D("activite_will");
	$data = array(
			'user_id'	 	=> $userid,
			'actr_id' 		=> $actr_id,
			'create_time' 	=> time()
	);
	$activite_will->add($data);
}

/**
 * @desc 查看商品的库存 申卫帅 2016-06-17
 */
function getCommodityStocks($goodid) {
	$goods = D("goods");
	return $goods->field('stock,end_num')->where("id=%d",$goodid)->find();
}

/**
 * 用户消费记录
 * @param (int)$userid	用户ID
 * @param (string)$model 模块
 * @param (string)$amount  消费金额
 * @param (string)$obj_id  具体的对象ID，活动对应活动的ID ，订单对应订单的ID
 * @param (string)$info  消费说明
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/
function member_consumption($userid, $model, $amount, $obj_id, $info){
	$db					= D('member_consumption');
	$error_db			= D('errorlog');
	$data['user_id']	= $userid;
	$data['model']		= $model;
	$data['amount']		= $amount;
	$data['info']		= $info;
	$data['obj_id']		= $obj_id;
	$data['create_time']= time();
	$mc_id				= $db->add($data);
	if(!$mc_id){
		$error_data['user_id']	= $userid;
		$error_data['obj_id']	= $obj_id;
		$error_data['type']		= 12;
		$error_data['time']		= time();
		$error_data['ip']		= realIp();
		$error_data['info']		= "消费记录失败--".$model;
		$error_data['sql']		= $db->getlastsql();
		$error_db->add($error_data);
	}
}

/**
 * @desc 查看用户是否已经中过永久必中商品了 申卫帅 2016-06-17 23:38
 */
function getGoodsPermanent($userid,$goodid) {
	$activite_record = D("activite_record");
	return $activite_record->where("userid=%d and goods_id=%d",$userid,$goodid)->count();
}

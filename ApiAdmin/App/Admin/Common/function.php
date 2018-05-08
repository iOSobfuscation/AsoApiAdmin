<?php


function exportExcel_data($expTitle,$expCellName,$expTableData,$file_name){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $file_name;//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));  
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
 * 扫描目录所有文件，并生成treegrid数据
 * @param string $path     目录
 * @param string $filter   过滤文件名
 * @param number $i        辅助用，这个不用传参
 * @return array
 */
function scandir_tree($path, $filter = SITE_DIR, &$i = 1){
	$result = array();
	$path   = realpath($path);

	$path   = str_replace(array('/', '\\'), DS, $path);
	$filter = str_replace(array('/', '\\'), DS, $filter);

	$list = glob($path . DS . '*');

	foreach ($list as $key => $filename){
		$result[$key]['id']    = $i;
		$result[$key]['name']  = str_replace($filter, '', $filename);
		$i++;
		if(is_dir($filename)){
			$result[$key]['type'] = 'dir';
			$result[$key]['size']  = '-';
			$result[$key]['mtime'] = '-';
			
			$result[$key]['state'] = 'closed';
			$result[$key]['children'] = scandir_tree($filename, $filter, $i);
			
			//easyui当children为空时会出现问题，因此在这里过滤
			if(empty($result[$key]['children'])){
				$result[$key]['iconCls'] = 'tree-folder';
				unset($result[$key]['state']);
				unset($result[$key]['children']);
			}
			
		}else{
			$result[$key]['type'] = 'file';
			$result[$key]['size']  = format_bytes(filesize($filename), ' ');
			$result[$key]['mtime'] = date('Y-m-d H:i:s', filemtime($filename));
		}
	}
	return $result;
}



/*---------------------------------------微游戏类方法-------------------------------------------*/
/**
 * 获取奔跑游戏参与者成绩列表
 * @return array
 */
function run_list(){
	$db   = M('user_run', 'mg_');
	$list = $db->table('mg_user_run r')->join('left join mx_users u on u.id = r.user_id')->field("r.distance, u.name, u.tel, FROM_UNIXTIME(r.dist_time, '%Y-%m-%d %H:%i:%s') as r_time")->limit("0,10000")->order("r.distance desc")->select();
	foreach($list as $key => $value){
		$list[$key]['name'] 	= $value['name'];
		$list[$key]['tel']  	= $value['tel'];
		$list[$key]['r_time'] 	= $value['r_time'];
		$list[$key]['distance'] = $value['distance'];
	}
	return $list;
}
/**
 * 处理添加的链接如果有HTTP则返回原有的字符串，如果没有则加上HTTP
 * @param int $str     待处理字符串
 * @return $srt			
 */
function http_act($str){
	if(substr($str, 0, 7) == 'http://'){
		return $str;
	}
	else{
		return 'http://'.$str;
	}
}

/*获取站点设置信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function site_info($id){
	$db			= M("set", "mp_");
	
	$where		= " id = ".$id;
	
	$res			= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/*获取旅游路线信息
 * @param (int)$id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function travel_route($id){
	$db				= D("travel_route");
	
	$where			= " id = ".$id;
	
	$res			= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}


/*会员设置信息
 * @param (int)$id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function member_set($id){
	$db				= D("member_set");
	
	$where			= " id = ".$id;
	
	$res			= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}


/*增加积分
 * @param (int)$type 类型 1 消费积分 2 活动积分 3 评论积分 4 游记积分
 * @param (int)$id 业务id
 * @param (int)$user_id 会员id
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
/*增加积分
 * @param (int)$type 类型 1 消费积分 2 活动积分 3 评论积分 4 游记积分
 * @param (int)$id 业务id
 * @param (int)$user_id 会员id
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function point_increase($type, $id, $user_id){
	
	$member_db					= D('member');
	//取出会员设置
			
	$member_set					= member_set('1');
	
	//取出会员ID
	
	$user_point					= get_field('member', 'mp_', 'point', "id='$user_id'");
	$actual_payment				= get_field('member', 'mp_', 'actual_payment', "id='$user_id'");
	
	//增加积分
	if($type == '1'){
		$consumption			= $member_set['consumption'];
		$point					= $consumption*$actual_payment;
	}elseif($type == '2'){
		
		$point					= get_field('activite', 'mp_', 'point', "id='$id'");
		if(empty($consumption)){
			$point				= $member_set['activite'];
		}
		
	}elseif($type == '3'){
		$point					= $member_set['comment'];
		
	}elseif($type == '4'){
		$point					= $member_set['news'];
	}
	
	$member_where				= "id = '$user_id'";
	
	$data['point']				= $user_point+$point;
	
	$res						= $member_db->where($member_where)->save($data);
	
	
	if($res){
		return true;
	}else{
		return false;
	}
}

/*景区信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function scenicspotset_info($id){
	$db			= M("scenicspotset", "to_");
	
	$where		= " id = ".$id;
	
	$res			= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}


/**
 * 获取奔跑游戏参与者成绩列表
 * @param int $num     统计数量
 * @return array
 */
function run_top($num){
	
	$user_run = M('user_run','mg_');
	$user_db  = M('users','mx_');
	$list     = $user_run->query("SELECT user_id,MAX(distance) as dist, FROM_UNIXTIME(dist_time, '%Y-%m-%d %H:%i:%s') as r_time FROM `mg_user_run` GROUP BY user_id ORDER BY dist DESC LIMIT ".$num);
	foreach($list as $key=>$val){
		$uId 				  = $val['user_id'];
		$user 				  = $user_db->where("id=$uId")->find();
		$list[$key]['name']   = $user['name'];
		$list[$key]['tel']    = $user['tel'];
		$list[$key]['dist']   = $val['dist'];
		$list[$key]['r_time'] = $val['r_time'];
	}
	return $list;
}

/**
 * 获取奔跑特殊数据   
 * @return array
 */
function get_db($num){
	
	$user_run   = M('user_run','mg_');
	$user_db    = M('users','mx_');
	
	$where_run  = "user_id <> ''";
	$where_user = "tel <> ''";
	
	//获取参与人数，参与人次总数
	$run_total  = $user_run->where($where_run)->count();
	$user_total = $user_db->where($where_user)->count();
	
	//获取第一名的姓名，成绩
	
	$dist		= $user_run->where($where_run)->field("MAX(distance) as dist")->find();
	
	$run_db		= array();
	
	$run_db['distance']   = $dist['dist'];
	$run_db['run_total']  = $run_total;
	$run_db['user_total'] = $user_total;
	return $run_db;
}



/*--------------------------------------------------微礼品方法--------------------------------------------------------------*/


/**
 * 获取礼品列表
 * @return array
 */
function mgift_list(){
	$db    = M('gift', 'ms_');
	
	$where = "is_del = 0 and is_nor = 0";
	
	$res   = $db->where($where)->select();
	
	//echo $db->getlastsql();
	
	return $res;
}

/**
 * 获取礼品详细信息
 * @param int $gid     礼品ID
 * @return array
 */
function mgift_info($gid){
	$db    = M('gift', 'ms_');
	
	$where = "id = '$gid' and is_del = 0 and is_nor = 0";
	
	$res   = $db->where($where)->find();
	
	//echo $db->getlastsql();
	
	
	return $res;
}



/**
 * 生成礼品的礼品卡信息 
 * @param int $gid     礼品ID
 * @return array
 */
function c_card($gid){
	
	$res     							= mgift_info($gid);
		$num  							 	= $res['gift_num'];
		$firex   								= $res['gift_firex'];
		$sn_db				 				= M('gift_sn', 'ms_');
		$exlist_db 						= M('gift_exlist', 'ms_');
		
		for($i = 1;  $i <= $num; $i++){

			//生成序列号、密码明文、加密密码
			$sn 		  	  					= $firex.randomkeys('6','NUMBER');//获得六位随机卡号
			$encrypt 		  	            = randomkeys('6','CHAR');;
			$passwd 		        		= randomkeys('6','NUMBER');//获得六位随机密码
			$passwd1      	    		= md5(md5(trim($passwd)).$encrypt);
			
			$data2['gift_id']			= $gid;
			$data2['sn']					= $sn;
			$data2['password']		= $passwd1;
			$data2['create_time']	= time();
			$data2['ex_time']	      	= '';
			$data2['status']			= 0;
			$data2['user_id']			= '';
			$exlist_res  					= $exlist_db->field('gift_id', 'sn', 'password', 'create_time', 'ex_time', 'status', 'user_id')->data($data2)->add();
			
			$data1['gift_id']			= $gid;
			$data1['sn']					= $sn;
			$data1['password']  	  	= $passwd;
			$sn_res	  					= $sn_db->add($data1);
		}
		
		$mgift_db				  			= M('gift', 'ms_');
		$data['is_creat']		  		= 1;
		$res					 			 	= $mgift_db->where("id = '$gid'")->save($data);
	
	return $res;
}


/**
 * @desc  im:插入某个表单的数据
 * @param (string)$table  需要变更的数据的表名，不能为空
 * @param (string)$prefix 需要变更的数据的前缀，不能为空
 * @param (string)$data  需要变更的数据的字段名，不能为空 
 * @param (array)$where  需要变更的数据的条件，不能为空
 * return 返回：成功返回手机号，不成功返回false 
 * */

function insert_field($table,$prefix,$data){
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
 * 获取礼品卡列表
 * @return array
 */
function card_list($gid){
	$db    = M('gift_send', 'ms_');
	
	$where = "gid = '$gid'";
	
	$res   = $db->where($where)->select();
	foreach($res as $key => $val){
		$list[$key]['card_no']     	 = $val['card_no'];
		$list[$key]['card_passwd']   = $val['card_passwd'];
		$list[$key]['encrypt'] 	   	 = $val['encrypt'];
		$list[$key]['creat_time']  	 = $val['creat_time'];
		$list[$key]['is_ex'] 	     = $val['is_ex'];
		
		if($val['user_id'] == 0){
			$list[$key]['user_name'] = '';
			$list[$key]['ex_time']   = '';
		}else{
			$list[$key]['user_name'] = get_field('mx_users', 'ms_', 'name', "id = '$val[user_id]'");
			$list[$key]['ex_time']   = date('Y-m-d h:i:s',$val['ex_time']);
		}
		
		$list[$key]['is_dl'] 	   	 = $val['is_dl'];
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 职位列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function job_list($where){
	
	
	$db    								= D('job');
	$where								= " is_del != 0  ".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['desc']    			= cut_str($val['desc'], 100, 1);
		$list[$key]['link']				= $val['link'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		
		//设置创建人以及修改人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$list[$key]['operation']			= "<a href='/admin/job/job_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/job/job_del/id/".$val['id']."'>删除</a>";
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

/*--------------------首页轮播类方法-----------------------------*/

/**
 * 轮播列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function carousel_list($where){
	
	
	$db    								= M('carousel', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['desc']    			= $val['desc'];
		$list[$key]['link']				= $val['link'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['start_time']		= date('Y-m-d h:i:s', $val['start_time']);
		$list[$key]['end_time']			= date('Y-m-d h:i:s', $val['end_time']);
		
		//设置创建人以及修改人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$list[$key]['operation']			= "<div><a class='btn btn-warning' href='/admin/carousel/carousel_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/carousel/carousel_del/id/".$val['id']."'>删除</a></div>";
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

/**
 * 场馆特色列表输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function characteristic_list($where){
	
	$db    								= M('characteristic', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']			= $val['title'];
		$list[$key]['sub_title']		= $val['sub_title'];
		$list[$key]['bg_color']			= $val['bg_color'];
		$list[$key]['desc']    			= $val['desc'];
		$list[$key]['link']				= $val['link'];
		$list[$key]['title_thumb']		= $val['title_thumb'];
		$list[$key]['sub_title_thumb']	= $val['sub_title_thumb'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		
	}
	
	/*echo $db->getlastsql();
	exit;*/
	return $list;
}

/**
 * 职业体验列表输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function proexp_list($where){
	
	$db    								= M('proexp', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']			= $val['title'];
		$list[$key]['sub_title']		= $val['sub_title'];
		$list[$key]['bg_color']			= $val['bg_color'];
		$list[$key]['desc']    			= $val['desc'];
		$list[$key]['link']				= $val['link'];
		$list[$key]['title_thumb']		= $val['title_thumb'];
		$list[$key]['sub_title_thumb']	= $val['sub_title_thumb'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		
	}
	
	/*echo $db->getlastsql();
	exit;*/
	return $list;
}

/*-----------------------------------------------------------保险单方法----------------------------------------------------------------*/


/**
 * 竟回来源列表输出方法
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function bsource_list($where){
	$db    								= M('bsource', 'mp_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['order']					= $val['order'];
		//创建人
		$create_id							= $val['create_id'];
		if(empty($create_id)){
			$list[$key]['create_id']		= "无";
		}else{
			$list[$key]['create_id']		= get_field("insurance", "mp_", "realname", "id = ".$create_id);
		}
		//修改人人
		$up_id								= $val['up_id'];
		if(empty($up_id)){
			$list[$key]['up_id']			= "无";
		}else{
			$list[$key]['up_id']			= get_field("insurance", "mp_", "realname", "id = ".$up_id);
		}
		
		//创建时间
		if($val['create_time'] == '0'){
			$list[$key]['create_time']		= "无";
		}else{
			$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}
		//编辑时间
		if($val['up_time'] == '0' || empty($val['up_time'])){
			$list[$key]['up_time']			= "无";
		}else{
			$list[$key]['up_time']			= date('Y-m-d h:i:s', $val['up_time']);
		}
	}
	return $list;
}


/**
 * 商险种类列表输出方法
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function btype_list($where){
	$db    								= M('btype', 'mp_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['order']					= $val['order'];
		//创建人
		$create_id							= $val['create_id'];
		if(empty($create_id)){
			$list[$key]['create_id']		= "无";
		}else{
			$list[$key]['create_id']		= get_field("insurance", "mp_", "realname", "id = ".$create_id);
		}
		//修改人人
		$up_id								= $val['up_id'];
		if(empty($up_id)){
			$list[$key]['up_id']			= "无";
		}else{
			$list[$key]['up_id']			= get_field("insurance", "mp_", "realname", "id = ".$up_id);
		}
		
		//创建时间
		if($val['create_time'] == '0'){
			$list[$key]['create_time']		= "无";
		}else{
			$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}
		//编辑时间
		if($val['up_time'] == '0' || empty($val['up_time'])){
			$list[$key]['up_time']			= "无";
		}else{
			$list[$key]['up_time']			= date('Y-m-d h:i:s', $val['up_time']);
		}
	}
	return $list;
}

/**
 * 保险单列表输出方法
 * @param (string)$where 条件
 * @param (string)$ren_where 续保条件条件
 * @param (int)$act 动作状态
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function ins_list($where, $act, $type, $insp_where){
	
	$ins_db    								= D('insurance');
	$insp_db								= D('insurance_personnel');
	
	//取出角色
	$role_id								= session('roleid');
	//echo $act;
	
	//echo $where."<br>";
	
	//echo $act."<br>";
	
	if($act == 'myself'){
		//创建者相同
		$where								.= " and `status` <> 5 and create_id = ".session('userid');
	}elseif($act == 'dep'){
		//部门相同
		
		if(session('depid') == '1'){
			$where							.= " and `status` not in(4,5)";
		}else{
			$where							.= " and `status` not in(4,5) and department_id = ".session('depid');
		}
	}elseif($act == 'com'){
		$where								.= " and `status` not in(4,5)";
	}
	
	if(!empty($insp_where)){
		
		
		//如果查询被保人资料条件不为空则取出被保人资料ID
		
		$insp_where							= " is_del != 0 ".$insp_where;
		$inspid								= $insp_db->field('id')->where($insp_where)->select();
		$pcounts							= $insp_db->where($insp_where)->count();
		$i									= 0;
		
		foreach($inspid as $pkey => $pval){
			$i++;
			$insp_ids						.= $pval['id'];
			if($i < $pcounts){
				$insp_ids					.= ",";
			}
		}
		
		$where								.= " and `insp_id` in (".$insp_ids.")";
	}
	
	$order									= " `id` desc ";
	$res									= $ins_db->where($where)->order($order)->select();
	
	//echo $ins_db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['examine_desc']			= $val['examine_desc'];
		$list[$key]['desc']					= $val['desc'];
		$list[$key]['status']				= $val['status'];
		//保单类
		$list[$key]['te_ins_no']			= $val['te_ins_no'];
		$list[$key]['bus_ins_no']			= $val['bus_ins_no'];
		$list[$key]['te_ins_gc']			= $val['te_ins_gc'];
		$list[$key]['bus_ins_gc']			= $val['bus_ins_gc'];
		
		
		if($val['b_type'] == '0'){
			$list[$key]['b_type']			= "自办";
		}elseif($val['b_type'] == '1'){
			$list[$key]['b_type']			= "公办";
			
		}
		
		if($val['channel'] == '0'){
			$list[$key]['channel']			= "电网销";
		}elseif($val['channel'] == '1'){
			$list[$key]['channel']			= "传统";
			
		}
		
		
		if($val['b_department'] == '0'){
			$list[$key]['b_department']		= "直销部";
		}elseif($val['b_department'] == '1'){
			$list[$key]['b_department']		= "红塔集团业务部";
			
		}
		
		//保险续保单状态
		if($val['handle_type'] == '0'){
			$list[$key]['handle_type']		= "续保";
		}elseif($val['handle_type'] == '1'){
			$list[$key]['handle_type']		= "竟回";
		}elseif($val['handle_type'] == '2'){
			$list[$key]['handle_type']		= "新保";
		}elseif($val['handle_type'] == '3'){
			$list[$key]['handle_type']		= "首次投保";
		}
		
		//保单状态
		if($val['status'] == '0'){
			$list[$key]['ins_status']		= "正常";
		}elseif($val['status'] == '1'){
			$list[$key]['ins_status'] 		= "流失";
		}elseif($val['status'] == '2'){
			$list[$key]['ins_status'] 		= "打回";
		}elseif($val['status'] == '3'){
			$list[$key]['ins_status'] 		= "已提交";
		}elseif($val['status'] == '4'){
			$list[$key]['ins_status'] 		= "草稿";
		}elseif($val['status'] == '4'){
			$list[$key]['ins_status'] 		= "已经续保";
		}
		
		//取出部门
		if(session('roleid') != '2'){
			$list[$key]['dep_name']			= get_field('department' ,'mp_' ,'name' ,'id = '.$val['department_id']);
		}
		
		//定义操作
		if($act == 'myself'){
			if($type == 're'){
				$list[$key]['operation']	= "<a href='/admin/insurance/ins_handle/id/".$val['id']."?act=$act'>处理</a>";
			}elseif($val['status'] == '3'){
				
				$list[$key]['operation']	= "<a href='/admin/insurance/ins_renewal/id/".$val['id']."?act=$act'>续保</a>";
				$list[$key]['operation']	.= " | <a href='/admin/insurance/ins_loss/id/".$val['id']."?act=$act'>流失</a>";
				$list[$key]['operation']	.= " | <a href='/admin/insurance/ins_infoview/id/".$val['id']."?act=$act'>详情</a>";
				//给领导们加上打回
				if($role_id != '2'){
					$list[$key]['operation']	.= " | <a href='/admin/insurance/ins_examine/id/".$val['id']."?act=$act'>打回</a>";
				}
				
			}elseif($val['status'] == '2' || $val['status'] == '4'){
				
				$list[$key]['operation']		= "<a href='/admin/insurance/ins_edit/id/".$val['id']."?act=$act'>编辑</a> | <a href='/admin/insurance/ins_del/id/".$val['id']."?act=$act'>删除</a>";
				
			}elseif($val['status'] == '1'){
				$list[$key]['operation']		= "已流失";
			}elseif($val['status'] == '5'){
				$list[$key]['operation']		= "已续保";
			}
			
		}elseif($act == 'dep'){
			if($val['status'] == '3'){
				
				$list[$key]['operation']		= "<a href='/admin/insurance/ins_infoview/id/".$val['id']."?act=$act'>详情</a> | <a href='/admin/insurance/ins_examine/id/".$val['id']."?act=$act'>打回</a>";
			}elseif($val['status'] == '1'){
				$list[$key]['operation']		= "已流失";
			}elseif($val['status'] == '5'){
				$list[$key]['operation']		= "已续保";
			}elseif($val['status'] == '2'){
				$list[$key]['operation']		= "已打回";
			}
		}elseif($act == 'com'){
			if($val['status'] == '3' || $val['status'] == '1'){
				$list[$key]['operation']		= "<a href='/admin/insurance/ins_infoview/id/".$val['id']."?act=$act'>详情</a> ";
			}elseif($val['status'] == '1'){
				$list[$key]['operation']		= "已流失";
			}elseif($val['status'] == '5'){
				$list[$key]['operation']		= "已续保";
			}
		}
		
		//提交审核时间
		if($val['submit_time'] == '0' || empty($val['submit_time'])){
			$list[$key]['submit_time']		= "无";
		}else{
			$list[$key]['submit_time']		= date('Y-m-d', $val['submit_time']);
		}
		
		//打回时间
		if($val['examine_time'] == '0' || empty($val['examine_time'])){
			$list[$key]['examine_time']		= "无";
		}else{
			$list[$key]['examine_time']		= date('Y-m-d', $val['examine_time']);
		}
		
		//审核人
		$examine_id							= $val['examine_id'];
		if(empty($examine_id)){
			$list[$key]['examine_id']		= "无";
		}else{
			$list[$key]['examine_id']		= get_field("insurance", "mp_", "realname", "id = ".$examine_id);
		}
		
		//创建人
		$create_id							= $val['create_id'];
		if(empty($create_id)){
			$list[$key]['create_id']		= "无";
		}else{
			$list[$key]['create_id']		= get_field("insurance", "mp_", "realname", "id = ".$create_id);
		}
		
		//创建时间
		if($val['create_time'] == '0' || empty($val['create_time'])){
			$list[$key]['create_time']		= "无";
		}else{
			$list[$key]['create_time']		= date('Y-m-d', $val['create_time']);
		}
		
		//取出有效时间 逻辑
		
		if($val['ins_start_time'] == '0' || empty($val['ins_start_time'])){
			$list[$key]['ins_start_time']		= "无";
		}else{
			$list[$key]['ins_start_time']		= date('Y-m-d', $val['ins_start_time']);
		}
		
		if($val['ins_end_time'] == '0' || empty($val['ins_end_time'])){
			$list[$key]['ins_end_time']		= "无";
		}else{
			$list[$key]['ins_end_time']		= date('Y-m-d', $val['ins_end_time']);
		}
		
		if($val['ve_start_time'] == '0' || empty($val['ve_start_time'])){
			$list[$key]['ve_start_time']		= "无";
		}else{
			$list[$key]['ve_start_time']		= date('Y-m-d', $val['ve_start_time']);
		}
		
		if($val['ve_end_time'] == '0' || empty($val['ve_end_time'])){
			$list[$key]['ve_end_time']		= "无";
		}else{
			$list[$key]['ve_end_time']		= date('Y-m-d', $val['ve_end_time']);
		}
		
		$now								= time();
		$insp_where							= "id = ".$val['insp_id'];
		$insp_res							= $insp_db->where($insp_where)->find();
		
		
		
		$list[$key]['cs_tax']				= $insp_res['cs_tax'];
		$list[$key]['insured_name']			= $insp_res['insured_name'];
		$list[$key]['insured_certificates']	= $insp_res['insured_certificates'];
		$list[$key]['plate_number']			= $insp_res['plate_number'];
		//处理电话
		if(session('roleid') == '8'){
			$list[$key]['tel']				= "";
		}else{
			$list[$key]['tel']				= $insp_res['tel'];
		}
		
		
		
		if($insp_res['ins_start_time'] <= $now || $now <= $insp_res['ins_end_time']){
			$list[$key]['is_dq']			= "未到期";
		}else{
			$list[$key]['is_dq']			= "已到期";
			
		}
		
		
	}
	
	//print_r($list);
	//exit;
	return $list;
}



/**
 * 保险单详情输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function ins_info($id){
	
	$db									= M("insurance", "mp_");
	
	$where								= "id = '$id'";
	
	$ins_res							= $db->where($where)->find();
	
	//处理编号
	
	
	if($ins_res['channel'] == 0){
		$pre_te_no						= get_field('set', 'mp_', 'dte_ins_no', 'id = 1');
		$pre_bus_no						= get_field('set', 'mp_', 'dbus_ins_no', 'id = 1');		
	}elseif($ins_res['channel'] == 1){
		$pre_te_no						= get_field('set', 'mp_', 'cte_ins_no', 'id = 1');
		$pre_bus_no						= get_field('set', 'mp_', 'cbus_ins_no', 'id = 1');	
	}
	
	$ins_res['pre_te_no']				= $pre_te_no;
	$ins_res['pre_bus_no']				= $pre_bus_no;
	$ins_res['te_ins_no']				= str_replace($pre_te_no, '',$ins_res['te_ins_no']);
	$ins_res['bus_ins_no']				= str_replace($pre_bus_no, '',$ins_res['bus_ins_no']);
	
	
	$ins_res['ins_res_amount_format']	= substr($ins_res['ins_res_amount'],0,strlen($ins_res['ins_res_amount'])-7)."万";
	
	if($ins_res['ins_start_time'] == '0' || empty($ins_res['ins_start_time'])){
		$ins_res['ins_start_time']	= "";
	}else{
		$ins_res['ins_start_time']	= date('Y-m-d', $ins_res['ins_start_time']);
	}
	
	if($ins_res['ins_end_time'] == '0' || empty($ins_res['ins_end_time'])){
		$ins_res['ins_end_time']		= "";
	}else{
		$ins_res['ins_end_time']		= date('Y-m-d', $ins_res['ins_end_time']);
	}
	
	if($ins_res['ve_start_time'] == '0' || empty($ins_res['ve_start_time'])){
		$ins_res['ve_start_time']		= "";
	}else{
		$ins_res['ve_start_time']		= date('Y-m-d', $ins_res['ve_start_time']);
	}
	
	if($ins_res['ve_end_time'] == '0' || empty($ins_res['ve_end_time'])){
		$ins_res['ve_end_time']			= "";
	}else{
		$ins_res['ve_end_time']			= date('Y-m-d', $ins_res['ve_end_time']);
	}
	
	//处理商业险
	$b_type								= $ins_res['ins_bus_type'];
	if(empty($b_type)){
		$bd_where 						= "" ;
	}else{
		$bd_where 						= "id not in (".$b_type.")";
	}
	$bb_where 							= "id in (".$b_type.")";
	//已选
	$bbs_res							= btype_list($bb_where);
	foreach($bbs_res as $bbs_key => $bbs_val){
		$ins_res['bbs_type'][$bbs_key]['id']		= $bbs_val['id'];
		$ins_res['bbs_type'][$bbs_key]['name']		= $bbs_val['name'];
	}
	
	//未选
	$bds_res							= btype_list($bd_where);
	foreach($bds_res as $bds_key => $bds_val){
		$ins_res['bds_type'][$bds_key]['id']		= $bds_val['id'];
		$ins_res['bds_type'][$bds_key]['name']		= $bds_val['name'];
	}
	
	if($ins_res['examine_time'] == '0' || empty($ins_res['examine_time'])){
		$ins_res['examine_time']		= "无";
	}else{
		$ins_res['examine_time']		= date('Y-m-d', $ins_res['examine_time']);
	}
	
	if($ins_res['examine_id'] == '0' || empty($ins_res['examine_id'])){
		$ins_res['examine_id']			= "无";
	}else{
		$ins_res['examine_id']		= get_field("admin", "mp_", "realname", "id = ".$ins_res['examine_id']);
	}
	
	if($ins_res['create_id'] == '0' || empty($ins_res['create_id'])){
		$ins_res['create_id']		= "无";
	}else{
		$lins_res['create_id']		= get_field("admin", "mp_", "realname", "id = ".$ins_res['create_id']);
	}
	
	if($ins_res['status'] == '0'){
		$ins_res['status_name']		= "正常";
	}elseif($res['status'] == '1'){
		$ins_res['status_name']		= "流失";
	}elseif($res['status'] == '2'){
		$ins_res['status_name']		= "打回";
	}elseif($res['status'] == '3'){
		$ins_res['status_name']		= "已提交";
	}elseif($res['status'] == '4'){
		$ins_res['status_name']		= "草稿";
	}
	
	//处理竟回来源
	$b_source							= $ins_res['b_source'];
	$bb_where 							= "id in (".$b_source.")";
	if(empty($b_source)){
		$bd_where 						= "" ;
	}else{
		$bd_where 						= "id not in (".$b_source.")";
	}
	
	$bd_where 							= "id not in (".$b_source.")";
	
	//已选
	$bbs_res							= bsource_list($bb_where);
	
	foreach($bbs_res as $bbs_key => $bbs_val){
		$ins_res['bbs_source'][$bbs_key]['id']		= $bbs_val['id'];
		$ins_res['bbs_source'][$bbs_key]['name']	= $bbs_val['name'];
	}
	
	//未选
	$bds_res							= bsource_list($bd_where);
	foreach($bds_res as $bds_key => $bds_val){
		$ins_res['bds_source'][$bds_key]['id']		= $bds_val['id'];
		$ins_res['bds_source'][$bds_key]['name']	= $bds_val['name'];
	}
	
	
	return $ins_res;
}


/**
 * 保险单详情输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function insp_info($id){
	
	$db									= M("insurance_personnel", "mp_");
	$insp_where							= "id = ".$id;
	$insp_res							= $db->where($insp_where)->find();
	//echo $db->getlastsql();
	//exit;
	
	//处理电话
	if(session('roleid') == '8'){
		$insp['tel']						= "";
	}
	return $insp_res;
}


/*--------------------新闻管理类方法-----------------------------*/

/**
 * 新闻列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function news_list($cat_id, $where, $order){
	
	$db    								= M('news', 'mp_');
	
	if(empty($cat_id)){
		$where							= "is_del != 0".$where;
	}else{
		$where							= "cat_id = '$cat_id' and is_del != 0".$where;
	}
	
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['cat_id']     	 	= $val['cat_id'];
		$list[$key]['title']     	 	= $val['title'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['album_id']			= $val['album_id'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_nav']			= $val['is_nav'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['is_wx']			= $val['is_wx'];
		$list[$key]['desc']				= $val['desc'];
		$list[$key]['auth']				= $val['auth'];
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<div><a class='btn btn-warning' href='/admin/news/news_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/news/news_rubi/id/".$val['id']."'>删除</a></div>";
		
		//取出新闻分类名称
		
		$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('news_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
	}
	//echo $db->getlastsql();
	
	return $list;
}
/*获取信息分类列表
 * @param (char)$table 分类表名
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function newscat_list($where){
	$db			= D('news_category');
	$where		= "parent_id = 0".$where;
	
	$order		= " `order` desc ";
	$res		= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['order']		= $val['order'];
		$list[$key]['desc']			= cut_str($val['desc'], 60, "...");
		$list[$key]['thumb']		= $val['thumb'];
		$list[$key]['is_nav']		= $val['is_nav'];
		
		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}
		
		//设置创建人以及修改人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['up_user']			.= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$list[$key]['operation']			= "<a href='/admin/News/cat_add/id/".$val['id']."'>添加子分类</a> | ";
		$list[$key]['operation']			.= "<a href='/admin/News/cat_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/News/cat_del/id/".$val['id']."'>删除</a>";
		
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
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];
				$list[$key]['sub'][$sun_key]['is_nav']		= $sub_val['is_nav'];
				
				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}
				
				//设置创建人以及修改人
		
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "id = ".$sub_val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= date('Y-m-d h:i:s', $sub_val['create_time']);
				}
				
				if(empty($sub_val['up_time'])){
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "id = ".$sub_val['create_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= date('Y-m-d h:i:s', $sub_val['create_time']);
				}else{
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "id = ".$sub_val['up_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= date('Y-m-d h:i:s', $sub_val['up_time']);
				}
				
				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			.= "<a href='/admin/News/cat_edit/id/".$sub_val['id']."'>编辑</a>";
				$list[$key]['sub'][$sun_key]['operation']			.= " | <a href='/admin/News/cat_del/id/".$sub_val['id']."'>删除</a>";
				
				
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}

/*--------------------电子商务管理类方法-----------------------------*/
/**
 * 电子商务列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function ebuss_list($cat_id, $where, $order){
	
	$db    								= M('ebuss', 'mp_');
	
	if(empty($cat_id)){
		$where							= "is_del != 0".$where;
	}else{
		$where							= "cat_id = '$cat_id' and is_del != 0".$where;
	}
	
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['description']		= $val['description'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['album']			= $val['album'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['is_wx']			= $val['is_wx'];
		$list[$key]['link']			= $val['link'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/Ebuss/ebuss_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Ebuss/ebuss_rubi/id/".$val['id']."'>删除</a>";
		
		//取出分类名称
		
		$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('ebuss_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
	}
	//echo $db->getlastsql();
	
	return $list;
}

/*获取信息分类列表
 * @param (char)$table 分类表名
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function ebusscat_list($where){
	$db			= D('ebuss_category');
	$where		= "is_del != 0".$where;
	
	$order		= " `order` desc ";
	$res		= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['order']		= $val['order'];
		$list[$key]['desc']			= cut_str($val['desc'], 60, "...");
		$list[$key]['thumb']		= $val['thumb'];
		
		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		//$list[$key]['operation']			= "<a href='/admin/News/cat_add/id/".$val['id']."'>添加子分类</a>";
		$list[$key]['operation']			.= "<a href='/admin/News/cat_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/News/cat_del/id/".$val['id']."'>删除</a>";
		
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
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];
				
				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}
				
				//设置创建人以及修改人
		
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "id = ".$sub_val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $sub_val['create_time']);
				}
				
				if(empty($sub_val['up_time'])){
					$list[$key]['sub'][$sun_key]['up_user']		= get_field("admin", "mp_", "realname", "id = ".$sub_val['create_id']);
					$list[$key]['sub'][$sun_key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $sub_val['create_time']);
				}else{
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "id = ".$sub_val['up_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $sub_val['up_time']);
				}
				
				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			.= "<a href='/admin/News/cat_edit/id/".$sub_val['id']."'>编辑</a>";
				$list[$key]['sub'][$sun_key]['operation']			.= " | <a href='/admin/News/cat_del/id/".$sub_val['id']."'>删除</a>";
				
				
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}


/**
 * 活动列表输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function activite_list($where, $member_id, $type){
	
	$db    								= D('activite');
	$order								= " `order` desc ";
	if(!empty($member_id)){
		
		//取出该用户参与的活动ID
		$actr_db						= D('activite_record');
		$act_ids 						= $actr_db->where(array('userid'=>$member_id))->getField('activite',true);
		$rec_res						= array_flip(array_flip($act_ids));
		$rgc_id_str						= implode(',', $rec_res);//组成id串
		
		$where							= $where."  and id in ($rgc_id_str)";
	}
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['price']			= $val['price'];
		$list[$key]['max_amount']		= $val['max_amount'];
		$list[$key]['desc']				= $val['desc'];
		$list[$key]['more']				= $val['more'];
		$list[$key]['end_sum']			= $val['end_sum'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//处理时间
		
		$list[$key]['activite_time']		= date('Y-m-d h:i:s', $val['start_time'])."至";
		if($val['end_time'] == '0'){
			$list[$key]['activite_time']	.= "不限期";
		}else{
			$list[$key]['activite_time']	.= "<br>".date('Y-m-d h:i:s', $val['end_time']);
		}
		
		//状态、操作
		$list[$key]['tools_operation']		= "<a href='/admin/tools/act_arrangement_data/act_id/".$val['id']."'>整理数据</a>";
		$list[$key]['tools_operation']		.= " | <a href='/admin/tools/act_sundata_clear/act_id/".$val['id']."'>清除晒单数据</a>";
		$list[$key]['tools_operation']		.= " | <a href='/admin/tools/overdue_time/act_id/".$val['id']."'>整理过期时间</a>";
		if($val['is_del'] == '1'){
			$list[$key]['attr']				= "未发布";
			$list[$key]['operation']		= "<a href='/admin/activite/activite_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_push/id/".$val['id']."'>发布</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_rubi/id/".$val['id']."'>删除</a>";
			
		}elseif($val['is_del'] == '2'){
			$list[$key]['attr']				= "已发布<br>".date('Y-m-d h:i:s', $val['push_time'])."<br>".get_field("admin", "mp_", "realname", "userid = ".$val['push_id']);
			if(empty($member_id)){
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."'>参与记录</a>";
			}else{
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."/member_id/$member_id'>参与记录</a>";
			}
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_stop/id/".$val['id']."'>停止</a>";
			
		}elseif($val['is_del'] == '3'){
			$list[$key]['attr']				= "已停止";
			
			if(empty($member_id)){
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."'>参与记录</a>";
			}else{
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."/member_id/$member_id'>参与记录</a>";
			}
			
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_push/id/".$val['id']."'>发布</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_rubi/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '0'){
			$list[$key]['attr']				= "已删除";
			if(empty($member_id)){
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."'>参与记录</a>";
			}else{
				$list[$key]['operation']	= "<a href='/admin/activite/activite_record/id/".$val['id']."/member_id/$member_id'>参与记录</a>";
			}
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_undel/id/".$val['id']."'>恢复</a>";
			$list[$key]['operation']		.= " | <a href='/admin/activite/activite_push/id/".$val['id']."'>发布</a>";
		}
		
		//取出分类名称
		
		/*$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('news_category', 'mp_','name',"id = '$val[cat_id]'", '');*/
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 活动列表输出方法
 * @param (int)$id)  活动ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function activite_record($id, $member_id){
	
	
	$db							= D('activite_record');
	$member_db					= D('member');
	$goods_db					= D('goods');
	$order						= " `sign_time` desc ";
	if(empty($member_id)){
		$where					= "activite = $id";
	}else{
		$where					= "activite = $id and userid = $member_id";
	}
	$res						= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['active']     	 	= $val['active'];
		$list[$key]['userid']			= $val['userid'];
		
		//获取参与者信息
		$userid							= $val['userid'];
		$member_where					= array('id'=>$userid);
		$user_res						= $member_db->where($member_where)->field('nick, id, head_pic')->find();
		
		$list[$key]['nick']				= $user_res['nick'];
		$list[$key]['id']				= $user_res['id'];
		$list[$key]['head_pic']			= $user_res['head_pic'];
		//echo M('member', 'mp_')->getlastsql();
		//exit;$list[$key]['realname']	= $val['realname'];
		$list[$key]['sign_time']		= date('Y-m-d h:i:s', $val['sign_time']);
		$list[$key]['expiration_time']	= date('Y-m-d h:i:s', $val['expiration_time']);
		
		if($list[$key]['sign_time'] == '0'){$list[$key]['sign_time']	= "数据错误";}
		if($list[$key]['expiration_time'] == '0'){$list[$key]['expiration_time']	= "数据错误";}
		//获取商品信息、名称、价值、服务费、缩略图
		$goods_id						= $val['goods_id'];
		$goods_res						= $goods_db->where(array('id'=>$goods_id))->field('name, thumb, price, freight')->find();
		$list[$key]['name']				= $goods_res['name'];
		$list[$key]['thumb']			= $goods_res['thumb'];
		$list[$key]['price']			= $goods_res['price'];
		$list[$key]['freight']			= $goods_res['freight'];
		
		if($val['status'] == '1'){
			$list[$key]['status_name']	= "未领取";
			$list[$key]['receive_time']	= "";
		}elseif($val['status'] == '2'){
			$list[$key]['status_name']	= "已过期";
			$list[$key]['receive_time']	= "";
		}elseif($val['status'] == '0'){
			$list[$key]['status_name']	= "已领取";
			$list[$key]['receive_time']		= date('Y-m-d h:i:s', $val['receive_time']);
		}
		//定义操作
		//$list[$key]['operation']		= "<a href='/admin/activite/record_info/id/".$val['id']."'>操作</a>";
		
	}
	
	return $list;
}

function record_info($id){
	
	$where					= "id = '$id'";
	$db						= D('activite_record');
	$res					= $db->where($where)->find();
	
	$res['sign_time']		= date('Y-m-d h:i:s', $res['sign_time']);
	$res['username']		= get_field('member', 'mp_', 'username', "id='$res[userid]'");
	
	if($res['up_id'] == ''){
		$res['up_user']		= "未处理";
	
	}else{
		$res['up_user']		= get_field('admin', 'mp_', 'realname', "userid='$res[up_id]'");
		$res['up_user']		.= "<br>".date('Y-m-d h:i:s', $res['up_time']);
	}
	if($res['status'] == '1'){
		$res['status_name']	= "未确认";
	}elseif($res['status'] == '2'){
		$res['status_name']	= "已确认";
	}elseif($res['status'] == '3'){
		$res['status_name']	= "已完成";
	}elseif($res['status'] == '4'){
		$res['status_name']	= "取消参与";
	}
	
	return $res;
}

/**
 * 案例列表输出方法
 * @param (int)$cat_id  分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function case_list($cat_id, $where, $order){
	
	$db    								= M('case', 'mp_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']     	 	= $val['title'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['album_id']			= $val['album_id'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['auth']				= $val['auth'];
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//取出分类名称
		
		$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('news_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
	}
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 方案列表输出方法
 * @param (int)$cat_id  分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function plan_list($cat_id, $where, $order){
	
	
	
	$db    								= M('plan', 'mp_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']     	 	= $val['title'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['album_id']			= $val['album_id'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['auth']				= $val['auth'];
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//取出分类名称
		
		$category_id					= $val['category_id'];
		
		$list[$key]['cat_name']			= get_field('news_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 商品列表输出方法
 * @param (int)$cat_id  分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function goods_list($cat_id, $where, $order){
	
	$db    								= M('goods', 'mp_');
	$order								= " `order` desc ";
	if($cat_id == ''){
		$where 							= "is_del = 1 ".$where;
	}else{
		$where							= "cat_id = ".$cat_id." and is_del = 1 ".$where;
	}
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['sn_char']     	 	= $val['sn_char'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['price']			= $val['price'];
		$list[$key]['mak_price']		= $val['mak_price'];
		$list[$key]['discount']			= $val['discount'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['position']			= $val['position'];
		
		if($val['prize_rate'] == 0){
			$list[$key]['prize_attr']	= "中奖位置:".$val['prize_position'];
			$list[$key]['prize_attr']	.= "<br>库存:".$val['stock'];
		}else{
			$list[$key]['prize_attr']	= "中奖率:".$val['prize_rate'];
			$list[$key]['prize_attr']	.= "<br>库存:".$val['stock'];
		}
		//$list[$key]['is_push']			= $val['is_push'];
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//取出分类名称
		$list[$key]['cat_name']				= get_field('goods_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
		//取出品牌名称
		$list[$key]['brand_name']			= get_field('goods_brand', 'mp_','name',"id = '$val[brand_id]'", '');
		
		//定义属性
		
		if($val['is_shelves'] == 0){
			$list[$key]['status']			= " 已上架";
		}elseif($_POST['is_shelves'] == 1){
			$list[$key]['status']			= "未上架";
		}
		
		if($val['is_home'] == 0){
			$list[$key]['status']			.= "/首页";
		}
		
		if($val['is_hot'] == 0){
			$list[$key]['status']			.= "/热点";
		}
		
		if($val['is_recd'] == 0){
			$list[$key]['status']			.= "/推荐";
		}
		
		
		//定义操作
		$list[$key]['operation']			= "<div><a class='btn btn-warning' href='/admin/goods/goods_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/goods/goods_del/id/".$val['id']."'>删除</a></div>";
		
	}
	
	
	//echo $db->getlastsql();
	//exit;
	return $list;
}

/**
 * 商品列表输出方法
 * @param (int)$cat_id  分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function pgoods_list($cat_id, $where, $order){
	
	$db    								= M('pgoods', 'mp_');
	$order								= " `order` desc ";
	$where 								= "is_del != 0 ".$where;
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['sn_char']     	 	= $val['sn_char'];
		$list[$key]['style']			= $val['style'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['price']			= $val['price'];
		$list[$key]['mak_price']		= $val['mak_price'];
		$list[$key]['discount']			= $val['discount'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		//$list[$key]['is_push']			= $val['is_push'];
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//取出分类名称
		$list[$key]['cat_name']				= get_field('pgoods_category', 'mp_','name',"id = '$val[cat_id]'", '');
		
		//取出品牌名称
		$list[$key]['brand_name']			= get_field('pgoods_brand', 'mp_','name',"id = '$val[brand_id]'", '');
		
		//定义属性
		
		if($val['is_shelves'] == 0){
			$list[$key]['status']			= " 已上架";
		}elseif($_POST['is_shelves'] == 1){
			$list[$key]['status']			= "未上架";
		}
		
		if($val['is_home'] == 0){
			$list[$key]['status']			.= "/首页";
		}
		
		if($val['is_hot'] == 0){
			$list[$key]['status']			.= "/热点";
		}
		
		if($val['is_recd'] == 0){
			$list[$key]['status']			.= "/推荐";
		}
		
		//定义操作
		$list[$key]['operation']			= "<a href='/admin/Pgoods/pgoods_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Pgoods/pgoods_del/id/".$val['id']."'>删除</a>";
		
	}
	
	//echo $db->getlastsql();
	//exit;
	return $list;
}



/**
 * 相册列表输出方法
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function album_list($where){
	
	
	
	$db    								= M('album', 'mp_');
	$where								= "is_del != 0".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['thumb']			= $val['thumb'];
		if($val['up_time'] == '0'){
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['create_time']);
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
		}
		
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}


/*获取内容信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function info_info($info_id, $table, $where){
	$db			= M($table);
	
	if($table == 'admin'){
		$where				= " is_del != 0 and userid = '$info_id'".$where;
	}else{
	
		$where				= " is_del != 0 and id = '$info_id'".$where;
	}
	$res					= $db->where($where)->find();
	
	if($res['start_time']){
		$res['start_time']	= date('Y-m-d h:i:s', $res['start_time']);
	}
	
	if($res['end_time']){
		$res['end_time']	= date('Y-m-d h:i:s', $res['end_time']);
	}
	
	if($res['create_time']){
		$res['create_time']	= date('Y-m-d h:i:s', $res['create_time']);
	}
	
	if($res['up_time']){
		$res['up_time']		= date('Y-m-d h:i:s', $res['up_time']);
	}
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}
/*获取属性信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function info_attr($info_id, $table, $where){
	$db			= M('attr_'.$table, "mp_");
	$where		= " goods_id = '$info_id'".$where;
	$res		= $db->where($where)->find();
	/*
	echo $db->getlastsql();
	exit;
	*/
	return $res;
}

/*获取分类列表
 * @param (char)$table 分类表名
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function cat_list($table, $where){
	$db			= D($table);
	$where		= " parent_id = 0".$where;
	
	$order		= " `order` desc ";
	$res		= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['order']		= $val['order'];
		$list[$key]['desc']			= cut_str($val['desc'], 60, "...");
		
		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}
		
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
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];
				
				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}
				
				
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}

/*获取商品分类列表
 * @param (int)$parent_id 父类ID
 * @param (string)$fun_where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function goodscat_list($parent_id, $fun_where){
	
	$db								= D("goods_category");
	if(empty($parent_id)){
		$where						= " parent_id = 0".$fun_where;
	}else{
		$where						= " parent_id = ".$parent_id.$fun_where;
	}
	$order							= " `order` desc ";
	$res							= $db->where($where)->order($order)->select();
	
	//$sql							= $db->getlastsql();
	
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['order']		= $val['order'];
		$list[$key]['desc']			= cut_str($val['desc'], 60, "...");
		$list[$key]['thumb']		= $val['thumb'];
		
		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$parent_id							= $val['parent_id'];
		
		$list[$key]['operation']		= "<div><a class='btn btn-info' href='/admin/goodscate/goodscate_list/parent_id/".$val['id']."'>子类</a></div>";
		$list[$key]['operation']		.= "<div><a class='btn btn-warning' href='/admin/goodscate/goodscate_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']		.= "<div><a class='btn btn-danger' href='/admin/goodscate/goodscate_del/id/".$val['id']."'>删除</a></div>";
		
		
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
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];
				
				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}
				
				//设置创建人以及修改人
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
				}
				
				if(empty($sub_val['up_time'])){
					$list[$key]['sub'][$sun_key]['up_user']			= '';
				}else{
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
				}
				
				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			= "<div><a class='btn btn-warning' href='/admin/goodscate/goodscate_edit/id/".$val['id']."'>修改</a></div>";
				$list[$key]['sub'][$sun_key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/goodscate/goodscate_del/id/".$val['id']."'>删除</a></div>";
				
			}
		}
	}
	
	
	return $list;
}

/*获取某个子分类的所有父分类并组成按钮
 * @param (int)$parent_id 父类ID
 * @param (string)$fun_where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function parent_cate_name($id){
	$db							= D('goods_category');
	//$where						= "id != ".$id;
	$categorys					= $db->where($where)->select();
	//print_r($categorys);
	$result						= getParents($categorys,$id); 
	
	//删除自己（去掉最后一个元素,去掉后需要使用原来的数组名称）
	//$list						= array_pop($result);
	
	foreach($result as $item){
		if($item['id'] == $id){
			$res				.= '<a href="/Admin/Goodscate/goodscate_list/parent_id/'.$item['id'].'"><div class="btn btn-primary label-warning">'.$item['name'].'</div></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		}else{
			$res				.= '<a href="/Admin/Goodscate/goodscate_list/parent_id/'.$item['id'].'"><div class="btn btn-primary">'.$item['name'].'</div></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		
	}
	$res						.= '<a href="/Admin/Goodscate/goodscate_add/parent_id/'.$item['id'].'"><div class="btn btn-primary">添加子分类</div></a>';
	return $res;
	
}


//分类 递归
function getParents($categorys,$catId){  
    $tree=array(); 
	
    foreach($categorys as $item){  
        if($item['id']==$catId){  
            if($item['parent_id']>0)  
                $tree=array_merge($tree,getParents($categorys,$item['parent_id']));  
            $tree[]=$item;
            break;    
        }  
    }
	//print_r($tree);
	//exit;
    return $tree;  
}

/*获取品牌列表
 * @param (char)$table 分类表名
 * @param (int)$cate_id 分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function brand_list($where){
	
	$db								= D('goods_brand');
	$where							= " is_del = 1 and parent_id = 0".$where;
	
	$order							= " `order` desc ";
	$res							= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	foreach($res as $key => $val){
		
		$list[$key]['id']					= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['gcat_id']				= $val['gcat_id'];
		$list[$key]['order']				= $val['order'];
		$list[$key]['desc']					= cut_str($val['desc'], 60, "...");
		$list[$key]['thumb']				= $val['thumb'];
		
		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}
		
		//获取商品分类名称
		$gcat_id							= $val['gcat_id'];
		if(!empty($gcat_id)){
			$list[$key]['gcat_name']		= get_field('goods_category', 'mp_', 'name', 'id = '.$gcat_id);
		}
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$list[$key]['operation']			= "<div><a class='btn btn-warning' href='/admin/goodsbrand/goodsbrand_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/goodsbrand/goodsbrand_del/id/".$val['id']."'>删除</a></div>";
		
		//判断该类下是否有子分类
		$where_total						= "parent_id = '$val[id]'" ;
		$subcat_total						= $db->where($where_total)->count();
		$list[$key]['subcat_total']			= $subcat_total;
		
		if($subcat_total > 0){
			
			$where		= "parent_id = '$val[id]'";
			$sub_res	= $db->where($where)->order($order)->select();
			foreach($sub_res as $sun_key => $sub_val){
				$list[$key]['sub'][$sun_key]['id']			= $sub_val['id'];
				$list[$key]['sub'][$sun_key]['name']		= $sub_val['name'];
				$list[$key]['sub'][$sun_key]['parent_id']	= $sub_val['parent_id'];
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];
				
				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}
				
				//设置创建人以及修改人
		
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
				}
				
				if(empty($sub_val['up_time'])){
					$list[$key]['sub'][$sun_key]['up_user']			= '';
				}else{
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
				}
				
				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			= "<div><a class='btn btn-warning' href='/admin/goodscate/goodscate_edit/id/".$val['id']."'>修改</a></div>";
				$list[$key]['sub'][$sun_key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/goodscate/goodscate_del/id/".$val['id']."'>删除</a></div>";
				
			}
		}
	}
	//print_r($list);
	//exit;
	return $list;
}

/*人群分类列表
 * @param (int)$parent_id 父类ID
 * @param (string)$fun_where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
 */
function popu_list($parent_id, $fun_where){

	$db								= D("popu");
	if(empty($parent_id)){
		$where						= " parent_id = 0".$fun_where;
	}else{
		$where						= " parent_id = ".$parent_id.$fun_where;
	}
	$order							= " `order` desc ";
	$res							= $db->where($where)->order($order)->select();

	//$sql							= $db->getlastsql();

	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['order']		= $val['order'];
		$list[$key]['desc']			= cut_str($val['desc'], 60, "...");
		$list[$key]['thumb']		= $val['thumb'];

		if($val['is_home'] == 0){
			$list[$key]['status']			= "首页";
		}else{
			$list[$key]['status']			= "";
		}

		//设置创建人以及修改人

		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}

		
		//定义操作
		$parent_id							= $val['parent_id'];

		$list[$key]['operation']		= "<div><a class='btn btn-info' href='/admin/popu/popu_list/parent_id/".$val['id']."'>子类</a></div>";
		$list[$key]['operation']		.= "<div><a class='btn btn-warning' href='/admin/popu/popu_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']		.= "<div><a class='btn btn-danger' href='/admin/popu/popu_del/id/".$val['id']."'>删除</a></div>";


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
				$list[$key]['sub'][$sun_key]['desc']		= $sub_val['desc'];
				$list[$key]['sub'][$sun_key]['thumb']		= $sub_val['thumb'];

				if($sub_val['is_home'] == 0){
					$list[$key]['sub'][$sun_key]['status']	= "首页";
				}

				//设置创建人以及修改人
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
				}

				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			= "<div><a class='btn btn-warning' href='/admin/popu/popu_edit/id/".$val['id']."'>修改</a></div>";
				$list[$key]['sub'][$sun_key]['operation']			.= "<div><a class='btn btn-danger' href='/popu/goodscate/popu_del/id/".$val['id']."'>删除</a></div>";

			}
		}
	}


	return $list;
}

/*获取加入我们列表
 * @param (char)$table 分类表名
 * @param (int)$type 收集信息类型，1英才，2商户，3代理商ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function join_list($type, $order){
	$db			= M('collect', "mp_");
	$where		= "type = ".$type;
	$order		= " id desc ";
	$res		= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		$list[$key]['name']			= $val['name'];
		$list[$key]['tel']			= $val['tel'];
		$list[$key]['contact_name']	= $val['contact_name'];
		$list[$key]['specialty']	= $val['specialty'];
		$list[$key]['join_time']	= date('Y-m-d h:i:s', $val['join_time']);
		
	}
	return $list;
}



/**
 * 地区输出方法
 * @param (int)$parent_id   父ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function area_list($parent_id, $fun_where, $limit){
	
	$db									= D("area");
	if(empty($parent_id)){
		$where							= " parent_id = 0".$fun_where;
	}else{
		$where							= " parent_id = ".$parent_id.$fun_where;
	}
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	//$sql								= $db->getlastsql();
	
	foreach($res as $key => $val){
		$list[$key]['id']				= $val['id'];
		$list[$key]['name']				= $val['name'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['parent_id']		= $val['parent_id'];
		$list[$key]['keywords']			= $val['keywords'];
		
		//设置创建人以及修改人
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$parent_id							= $val['parent_id'];
		
		$list[$key]['operation']			= "<a href='/admin/area/area_list/parent_id/".$val['id']."'>子类</a>";
		$list[$key]['operation']			.= " | <a href='/admin/area/area_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/area/area_del/id/".$val['id']."'>删除</a>";
		
		
		//判断该类下是否有子分类
		$where_total						= "parent_id = '$val[id]'" ;
		$subcat_total						= $db->where($where_total)->count();
		$list[$key]['subcat_total']			= $subcat_total;
		
		if($subcat_total > 0){
			
			$where							= "parent_id = '$val[id]'";
			$sub_res						= $db->where($where)->order($order)->select();
			foreach($sub_res as $sun_key => $sub_val){
				$list[$key]['sub'][$sun_key]['id']			= $sub_val['id'];
				$list[$key]['sub'][$sun_key]['name']		= $sub_val['name'];
				$list[$key]['sub'][$sun_key]['parent_id']	= $sub_val['parent_id'];
				$list[$key]['sub'][$sun_key]['keywords']	= $sub_val['keywords'];
				
				//设置创建人以及修改人
				if(empty($sub_val['create_time'])){
					$list[$key]['sub'][$sun_key]['create_user']		= '';
				}else{
					$list[$key]['sub'][$sun_key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
					$list[$key]['sub'][$sun_key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
				}
				
				if(empty($sub_val['up_time'])){
					$list[$key]['sub'][$sun_key]['up_user']			= '';
				}else{
					$list[$key]['sub'][$sun_key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
					$list[$key]['sub'][$sun_key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
				}
				
				//定义操作
				$list[$key]['sub'][$sun_key]['operation']			= "<a href='/admin/goodscate/goodscate_edit/id/".$val['id']."'>修改</a>";
				$list[$key]['sub'][$sun_key]['operation']			.= " | <a href='/admin/goodscate/goodscate_del/id/".$val['id']."'>删除</a>";
				
			}
		}
	}
	
	
	return $list;
}

/*获取某个子分类的所有父分类并组成按钮
 * @param (int)$parent_id 父类ID
 * @param (string)$fun_where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function parent_area_name($id){
	$db							= D('area');
	//$where						= "id != ".$id;
	$categorys					= $db->where($where)->select();
	//print_r($categorys);
	$result						= getParents($categorys,$id); 
	
	//删除自己（去掉最后一个元素,去掉后需要使用原来的数组名称）
	//$list						= array_pop($result);
	
	foreach($result as $item){
		if($item['id'] == $id){
			$res				.= '<a href="/Admin/area/area_list/parent_id/'.$item['id'].'"><div class="btn btn-primary label-warning">'.$item['name'].'</div></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		}else{
			$res				.= '<a href="/Admin/area/area_list/parent_id/'.$item['id'].'"><div class="btn btn-primary">'.$item['name'].'</div></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		}
	}
	$res						.= '<a href="/Admin/area/area_add/parent_id/'.$item['id'].'"><div class="btn btn-primary">添加&nbsp;<b>'.$item['name'].'</b>&nbsp;的子分类</div></a>';
	return $res;
	
}

/*文件上传
 * @param (string)$keys 上传组件名称
 * @param (int)$type 文件类型image,application
 * @param (Array)$postfix  允许上传的文件后缀
 * @param (int)$is_waters 	是否加水印, 1为加，其他不加
 * @param (int)$waters_type 水印类型, 1为文字, 2为图片
 * @param (int)$waters_position 水印位置(1为左下角,2为右下角,3为左上角,4为右上角,5为居中);
 * @param (int)$waters_info 水印内容文字直接就是内容，图片则是图片路径;  
 * @param (string)$path 	 保存路径
 * @param (int)$size  	 文件大小, 单位BYTE
 * @param (int)$imgpreview  	 是否生成预览图(1为生成,其他为不生成)
 * @param (int)$imgpreviewsize  	 缩略图比例
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function uploadfile($keys, $type, $postfix, $is_waters, $waters_type, $waters_position, $path, $size, $imgpreview, $imgpreviewsize){
	
	$uptypes	= $postfix;
	
	
	//是否存在文件 
	if (!is_uploaded_file($_FILES[$keys]['tmp_name'])){
		
		$destination = "图片不存在";
         //echo "图片不存在!"; 
    }  
  
    $file = $_FILES[$keys];
	
	//检查文件大小 
    if($size < $file["size"]){
		$destination = "文件太大";
        echo "文件太大!";  
    }  
  	//检查文件类型
	//处理传过来的文件类型只留下‘/’后的部分
	$file["type"] = msubstr('2', $file["type"], '', '', $charset="utf-8", '/', '', 'false');
	
    if(!in_array($file["type"], $uptypes)){
		
		$destination = "文件类型不符!".$file["type"];
        
    }
  
    if(!file_exists($path)){  
        mkdir($path);  
    }  
  
    $filename		= $file["tmp_name"];  
    $image_size 	= getimagesize($filename);  
    $pinfo			= pathinfo($file["name"]);  
    $ftype			= $pinfo['extension'];  
    $destination	= $path.time().".".$ftype;
	
    if (file_exists($destination) && $overwrite != true){  
		$destination = "同名文件已经存在了!";
        //echo "同名文件已经存在了";  
    }  
  
    if(!move_uploaded_file ($filename, $destination)){
		$destination = "同名文件已经存在了!";
    }  
  
    $pinfo=pathinfo($destination);  
    $fname=$pinfo['basename'];  
  
    if($is_waters==1)  
    {  
        $iinfo=getimagesize($destination,$iinfo);  
        $nimage=imagecreatetruecolor($image_size[0],$image_size[1]);  
        $white=imagecolorallocate($nimage,255,255,255);  
        $black=imagecolorallocate($nimage,0,0,0);  
        $red=imagecolorallocate($nimage,255,0,0);  
        imagefill($nimage,0,0,$white);  
        switch ($iinfo[2])  
        {  
            case 1:  
            $simage =imagecreatefromgif($destination);  
            break;  
            case 2:  
            $simage =imagecreatefromjpeg($destination);  
            break;  
            case 3:  
            $simage =imagecreatefrompng($destination);  
            break;  
            case 6:  
            $simage =imagecreatefromwbmp($destination);  
            break;  
            default:  
            die("不支持的文件类型");  
            exit;  
        }  
  
        imagecopy($nimage,$simage,0,0,0,0,$image_size[0],$image_size[1]);  
        imagefilledrectangle($nimage,1,$image_size[1]-15,80,$image_size[1],$white);  
  
        switch($waters_type)  
        {  
            case 1:   //加水印字符串  
            imagestring($nimage,2,3,$image_size[1]-15,$waters_info,$black);  
            break;  
            case 2:   //加水印图片  
            $simage1 =imagecreatefromgif("xplore.gif");  
            imagecopy($nimage,$simage1,0,0,0,0,85,15);  
            imagedestroy($simage1);  
            break;  
        }  
  
        switch ($iinfo[2])  
        {  
            case 1:  
            //imagegif($nimage, $destination);  
            imagejpeg($nimage, $destination);  
            break;  
            case 2:  
            imagejpeg($nimage, $destination);  
            break;  
            case 3:  
            imagepng($nimage, $destination);  
            break;  
            case 6:  
            imagewbmp($nimage, $destination);  
            //imagejpeg($nimage, $destination);  
            break;  
        }  
  
        //覆盖原上传文件  
        imagedestroy($nimage);  
        imagedestroy($simage);  
    }
	
	return $destination;
   
}


/*文件上传 利用类
 * @param (string)$model 模块名称，用于分开保存每个模块上传的图片
 * @param (int)$size 文件大小限制
 * @param (Array)$allowtype  允许上传的文件后缀
 * @param (int)$israndname 	是否生成随机名称
 * @param (int)$name 	上传控件的名称
 */	
function upfile($model, $size, $allowtype, $israndname, $name){
	
	$date	= date("Y-mm-dd", time());
	$path	= "/Public/upload/images/".$model."/".$date;
	//echo $path;
	//exit;
	
	if(empty($name)){
		$name	= "thumb";
	}
	if(empty($size)){
		$size	= C('FILE_UPLOAD_CONFIG.maxSize');
	}
	
	$up		= new \Think\FileUpload();
	//设置属性(上传的位置， 大小， 类型， 名是是否要随机生成)
	$up -> set("path", ".".$path);
	$up -> set("maxsize", $size);
	$up -> set("allowtype", $allowtype );
	$up -> set("israndname", $israndname);//随机文件名
	
	//使用对象中的upload方法， 就可以上传文件， 方法需要传一个上传表单的名子 pic, 如果成功返回true, 失败返回false
	if($up -> upload($name)){
		//$img_name	= $path."/".$up->getFileName();
		$res	= array('error' => '0', 'path' => $path."/".$up->getFileName());
	} else {
		$res	= array('error' => '1', 'msg' => $up->getErrorMsg());
		
	}
	return $res;
}


/*文件批量上传 利用类
 * @param (string)$model 模块名称，用于分开保存每个模块上传的图片
 * @param (int)$size 文件大小限制
 */	
function b_upfile($model, $size){
	
	$date	= date("Y-mm-dd", time());
	$targetPath	= "/Public/upload/images/".$model."/";
	
	$targetFolder = $_POST['url']; // Relative to the root


	//echo $_POST['token'];
	$verifyToken = md5($_POST['timestamp']);

	if (!empty($_FILES) && $_POST['token'] == $verifyToken) {

		//import("ORG.Net.UploadFile");
		$name=time().rand();	//设置上传图片的规则

		$upload = new  \Think\UploadFile();// 实例化上传类

		$upload->maxSize  = $size ;// 设置附件上传大小

		$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型

		$upload->savePath =  '.'.$targetPath;// 设置附件上传目录

		$upload->saveRule = $name;  //设置上传图片的规则

		if(!$upload->upload()) {// 上传错误提示错误信息

		//return false;

		echo $upload->getErrorMsg();
		//echo $targetPath;

		}else{// 上传成功 获取上传文件信息

		$info =  $upload->getUploadFileInfo();

		return $targetPath.$info[0]["savename"];

		}


	}
}


/*--------代理商类方法----------*/


/*代理商列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function agency_list($where){
	
	$db    								= M('agency', 'mp_');
	$where								= "is_del != 0 ".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    	 		= $val['name'];
		$list[$key]['tel']				= $val['tel'];
		$list[$key]['person']			= $val['person'];
		$list[$key]['person_tel']		= $val['person_tel'];
		$list[$key]['level']			= $val['level'];
		
		$list[$key]['discount']			= $val['discount'];
		$list[$key]['type']				= $val['type'];
		$list[$key]['member_sum']		= $val['member_sum'];
		$list[$key]['fund_flow']		= $val['fund_flow'];
		
		//取出级别名称
		
		
		$list[$key]['level_name']		= get_field('agency_level', 'mp_','name',"id = '$val[level]'", '');
		
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			if(empty($val['create_id'])){
				$list[$key]['create_user']	= "自动升级";
			}else{
				
				$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			}
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']		= '';
		}else{
			$list[$key]['up_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		if(empty($val['login_time'])){
			$list[$key]['login_time']		= "尚未登录";
		}else{
			$list[$key]['login_time']		= date('Y-m-d h:i:s', $val['login_time']);
		}
	}
	
	//echo $db->getlastsql();
	//exit;
	return $list;
}

/*代理商级别列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function level_list($model, $where){
	
	$db    								= M($model.'_level', 'mp_');
	$where								= "is_del != 0 ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	//echo $where;
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    	 		= $val['name'];
		$list[$key]['discount']			= ($val['discount']/10)."折";
		$list[$key]['status']			= $val['status'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']		= '';
		}else{
			$list[$key]['up_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//满足条件
		if(!empty($val['condition'])){
			$list[$key]['condition']		= '累计消费：'.$val['condition'];
		//$condition						= json_decode($val['condition'], true);//化身成数组
		}
		//定义操作
		$list[$key]['operation']		= "<a href='/admin/".$model."level/".$model."level_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']		.= " | <a href='/admin/".$model."level/".$model."level_del/id/".$val['id']."'>删除</a>";
		
		//echo $val['condition'];
		
		/*foreach($condition as $ckey => $cval){
			
			if($cval['item_name'] == 'me_sum'){
				$list[$key]['condition'][$ckey]['item_name'] = "会员数量";
			}elseif($cval['item_name'] == 'cons'){
				$list[$key]['condition'][$ckey]['item_name'] = "消费额度";
			}
			
			$list[$key]['condition'][$ckey]['item_vaule']	 = $cval['item_vaule'];
		}*/
		
	}
	
	//echo $db->getlastsql();
	return $list;
}


/*--------会员类方法----------*/


/*会员列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function member_list($where){
	
	$db    									= M('member', 'mp_');
	$where									= $where;
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['signature']    		= $val['signature'];
		$list[$key]['nick']					= $val['nick'];
		$list[$key]['hpic']					= $val['hpic'];
		$list[$key]['tel']					= $val['tel'];
		$list[$key]['point']				= $val['point'];
		$list[$key]['fund_flow']			= $val['fund_flow'];
		$list[$key]['agency_level']			= $val['agency_level'];
		$list[$key]['agencylevel']			= $val['agencylevel'];
		$list[$key]['head_pic']				= $val['head_pic'];
		$list[$key]['balance']				= $val['balance'];
		$list[$key]['already_mentioned']	= $val['already_mentioned'];
		$list[$key]['not_mentioned']		= $val['not_mentioned'];
		$list[$key]['code']					= $val['code'];
		
		//取出会员级别名称
		$list[$key]['level_name']			= get_field('member_level', 'mp_','name',"id = ".$val['level']);
		//取出分销级别名称
		$list[$key]['alevel_name']			= get_field('agency_level', 'mp_','name',"id = ".$val['agencylevel']);
		
		//取出线下人数
		$dlsum_where						= $where." and recommenders = ".$val['id'];
		$dlsum_res							= $db->where($dlsum_where)->order($order)->count();
		$list[$key]['dlsum']				= $dlsum_res;
		
		//格式化来源
		if($val['source'] == '1'){
			$list[$key]['source_name']		= "官网";
		}elseif($val['source'] == '2'){
			$list[$key]['source_name']		= "微信";
		}elseif($val['source'] == '5'){
			$list[$key]['source_name']		= "鼎游";
		}elseif($val['source'] == '3'){
			$list[$key]['source_name']		= "其他来源";
		}
		
		//格式化状态
		if($val['is_del'] == '0'){
			$list[$key]['status']			= "已删除";
		}elseif($val['is_del'] == '1'){
			$list[$key]['status']			= "正常";
		}elseif($val['is_del'] == '2'){
			$list[$key]['status']			= "已锁定";
		}
		
		//取出卡名称
		if(empty($val['card_id'])){
			//取出代理商
			$list[$key]['card_name']		= "未购卡";
		}else{
			$list[$key]['card_name']		= get_field('member_card', 'mp_','name',"id = '$val[card_id]'");
		}
		
		if(empty($val['agency'])){
			//取出代理商
			$list[$key]['agency_name']		= "无";
		}else{
			//取出代理商
			$list[$key]['agency_name']		= get_field('agency', 'mp_','name',"id = '$val[agency]'");
		}
		
		//取出消费总额
		$order_db							= D('order');
		$order_where						= "pay = 0 and status = 0 and user_id = ".$val['id'];
		
		$list[$key]['total']				= $order_db->where($order_where)->Sum('actual_payment');
		
		if($list[$key]['total'] == '' || $list[$key]['total'] == 0){
			$list[$key]['total']			= 0;
			$list[$key]['operation']		= "";
		}else{
			$list[$key]['operation']		= "<a href='/admin/order/order_list/member_id/".$val['id']."'>消费记录</a> | ";
		}
		
		//取出参与活动记录
		$actr_db							= D('activite_record');
		$actr_where							= "userid = ".$val['id'];
		
		$list[$key]['actr_sum']				= $actr_db->where($actr_where)->count();
		
		if($list[$key]['actr_sum'] == '' || $list[$key]['actr_sum'] == 0){
			$list[$key]['actr_sum']			= 0;
			$list[$key]['operation']		.= "";
		}else{
			$list[$key]['operation']		.= "<a href='/admin/activite/activite_list/member_id/".$val['id']."'>活动记录</a> | ";
		}
		
		//取出提现记录
		$wthd_db							= D('member_withdrawals');
		$wthd_where							= "user_id = ".$val['id'];
		$list[$key]['wthd']					= $wthd_db->where($wthd_where)->count();
		
		if($list[$key]['wthd'] == '' || $list[$key]['wthd'] == 0){
			$list[$key]['wthd']				= 0;
			$list[$key]['operation']		.= "";
		}else{
			$list[$key]['operation']		.= "<a href='/admin/Withdrawals/withdrawals_list/member_id/".$val['id']."'>提现记录</a> | ";
		}
		
		//注册时间
		$list[$key]['create_time']			= date('Y-m-d h:i:s', $val['create_time']);
		
		if($val['sex'] == '0' ){
			$list[$key]['sex']				= "女";
		}else{
			$list[$key]['sex']				= "男";
		}
		
		//定义操作
		
		$list[$key]['operation']			.= "<a href='/admin/member/member_edit/id/".$val['id']."'>查看</a> | ";
		
		if($val['is_del'] == '1'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a> | ";
			$list[$key]['operation']		.= "<a href='/admin/member/member_del/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '2'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_unlock/id/".$val['id']."'>解锁</a> | ";
			$list[$key]['operation']		.= "<a href='/admin/member/member_del/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '0'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_undel/id/".$val['id']."'>恢复</a>";
		}
	}
	
	//echo $db->getlastsql();
	return $list;
}

/*测试会员列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function member_test($where){
	
	$db    									= M('member', 'mp_');
	$where									= "id in (199, 180, 144, 182, 81, 192, 97, 183, 10328, 10130)  ".$where;
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['signature']    		= $val['signature'];
		$list[$key]['nick']					= $val['nick'];
		$list[$key]['hpic']					= $val['hpic'];
		$list[$key]['tel']					= $val['tel'];
		$list[$key]['point']				= $val['point'];
		$list[$key]['fund_flow']			= $val['fund_flow'];
		$list[$key]['agency_level']			= $val['agency_level'];
		$list[$key]['agencylevel']			= $val['agencylevel'];
		$list[$key]['head_pic']				= $val['head_pic'];
		$list[$key]['balance']				= $val['balance'];
		$list[$key]['already_mentioned']	= $val['already_mentioned'];
		$list[$key]['not_mentioned']		= $val['not_mentioned'];
		$list[$key]['code']					= $val['code'];
		
		//取出会员级别名称
		$list[$key]['level_name']			= get_field('member_level', 'mp_','name',"id = ".$val['level']);
		//取出分销级别名称
		$list[$key]['alevel_name']			= get_field('agency_level', 'mp_','name',"id = ".$val['agencylevel']);
		
		//取出线下人数
		$dlsum_where						= $where." and recommenders = ".$val['id'];
		$dlsum_res							= $db->where($dlsum_where)->order($order)->count();
		$list[$key]['dlsum']				= $dlsum_res;
		
		//格式化来源
		if($val['source'] == '1'){
			$list[$key]['source_name']		= "官网";
		}elseif($val['source'] == '2'){
			$list[$key]['source_name']		= "微信";
		}elseif($val['source'] == '5'){
			$list[$key]['source_name']		= "鼎游";
		}elseif($val['source'] == '3'){
			$list[$key]['source_name']		= "其他来源";
		}
		
		//格式化状态
		if($val['is_del'] == '0'){
			$list[$key]['status']			= "已删除";
		}elseif($val['is_del'] == '1'){
			$list[$key]['status']			= "正常";
		}elseif($val['is_del'] == '2'){
			$list[$key]['status']			= "已锁定";
		}
		
		//取出卡名称
		if(empty($val['card_id'])){
			//取出代理商
			$list[$key]['card_name']		= "未购卡";
		}else{
			$list[$key]['card_name']		= get_field('member_card', 'mp_','name',"id = '$val[card_id]'");
		}
		
		if(empty($val['agency'])){
			//取出代理商
			$list[$key]['agency_name']		= "无";
		}else{
			//取出代理商
			$list[$key]['agency_name']		= get_field('agency', 'mp_','name',"id = '$val[agency]'");
		}
		
		//取出消费总额
		$order_db							= D('order');
		$order_where						= "pay = 0 and status = 0 and user_id = ".$val['id'];
		
		$list[$key]['total']				= $order_db->where($order_where)->Sum('actual_payment');
		
		if($list[$key]['total'] == '' || $list[$key]['total'] == 0){
			$list[$key]['total']			= 0;
			$list[$key]['operation']		= "";
		}else{
			$list[$key]['operation']		= "<a href='/admin/order/order_list/member_id/".$val['id']."'>消费记录</a> | ";
		}
		
		//取出参与活动记录
		$actr_db							= D('activite_record');
		$actr_where							= "userid = ".$val['id'];
		
		$list[$key]['actr_sum']				= $actr_db->where($actr_where)->count();
		
		if($list[$key]['actr_sum'] == '' || $list[$key]['actr_sum'] == 0){
			$list[$key]['actr_sum']			= 0;
			$list[$key]['operation']		.= "";
		}else{
			$list[$key]['operation']		.= "<a href='/admin/activite/activite_list/member_id/".$val['id']."'>活动记录</a> | ";
		}
		
		//取出提现记录
		$wthd_db							= D('member_withdrawals');
		$wthd_where							= "user_id = ".$val['id'];
		$list[$key]['wthd']					= $wthd_db->where($wthd_where)->count();
		
		if($list[$key]['wthd'] == '' || $list[$key]['wthd'] == 0){
			$list[$key]['wthd']				= 0;
			$list[$key]['operation']		.= "";
		}else{
			$list[$key]['operation']		.= "<a href='/admin/Withdrawals/withdrawals_list/member_id/".$val['id']."'>提现记录</a> | ";
		}
		
		//注册时间
		$list[$key]['create_time']			= date('Y-m-d h:i:s', $val['create_time']);
		
		if($val['sex'] == '0' ){
			$list[$key]['sex']				= "女";
		}else{
			$list[$key]['sex']				= "男";
		}
		
		//定义操作
		
		$list[$key]['operation']			.= "<a href='/admin/member/member_edit/id/".$val['id']."'>查看</a> | ";
		$list[$key]['operation']			.= "<a href='/admin/tools/clear_data/id/".$val['id']."'>清除数据</a> | ";
		if($val['is_del'] == '1'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a> | ";
			$list[$key]['operation']		.= "<a href='/admin/member/member_del/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '2'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_unlock/id/".$val['id']."'>解锁</a> | ";
			$list[$key]['operation']		.= "<a href='/admin/member/member_del/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '0'){
			$list[$key]['operation']		.= "<a href='/admin/member/member_undel/id/".$val['id']."'>恢复</a>";
		}
	}
	
	//echo $db->getlastsql();
	return $list;
}


/*会员总数
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function member_sum($where){
	
	$db    									= M('member', 'mp_');
	$where									= "is_del = 1 ".$where;
	$res									= $db->where($where)->count();
	
	return $res;
}


/*门票列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function ticket_list($where){
	
	$db    									= M('ticket', 'mp_');
	$where									= " is_del != 0 ".$where;
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['price']				= $val['price'];
		$list[$key]['point']				= $val['point'];
		$list[$key]['thumb']				= $val['thumb'];
		$list[$key]['desc']					= $val['desc'];
		
		//取出购卡总数
		$member_db							= D('member_card');
		$where								= "card_id = ".$val['id'];
		$list[$key]['user_total']			= $member_db->where($where)->count();
		
		//定义操作
		
		$list[$key]['operation']			= "<a href='/admin/ticket/ticket_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/ticket/ticket_del/id/".$val['id']."'>删除</a>";
		//$list[$key]['operation']			.= " | <a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a>";
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}


/*题库列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function questions_list($where){
	
	$db    									= M('questions', 'mp_');
	$where									= " is_del != 0 ".$where;
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['is_home']				= $val['is_home'];
		$list[$key]['is_recd']				= $val['is_recd'];
		
		//
		if($val['is_home'] == 0){
			$list[$key]['attr']				= "首页/";
		}
		
		if($val['is_recd'] == 0){
			$list[$key]['attr']				.= "推荐";
		}
		
		//处理类型
		if($val['type'] == 0){
			$list[$key]['type']				= "问答";
		}elseif($val['type'] == 1){
			$list[$key]['type']				= "单选";
		}elseif($val['type'] == 2){
			$list[$key]['type']				= "多选";
		}
		
		//定义创建时间和人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		
		$list[$key]['operation']			= "<a href='/admin/questions/answer_set/id/".$val['id']."'>设置答案</a>";
		$list[$key]['operation']			.= " | <a href='/admin/questions/questions_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/questions/questions_del/id/".$val['id']."'>删除</a>";
		//$list[$key]['operation']			.= " | <a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a>";
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

/*答案列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function answer_list($where){
	
	$db    									= M('answer', 'mp_');
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['q_id']					= $val['q_id'];
		$list[$key]['item']					= $val['item'];
		$list[$key]['type']					= $val['type'];
		$list[$key]['content']				= $val['content'];
		
		if($val['answer'] == '0'){
			$list[$key]['is_answer']		= "是";
		}else{
			$list[$key]['is_answer']		= "否";
		}
		
		//定义创建时间和人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		
		//定义操作
		
		$list[$key]['operation']			= "<a href='/admin/questions/answer_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/questions/answer_del/id/".$val['id']."'>删除</a>";
		//$list[$key]['operation']			.= " | <a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a>";
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

function answer_info($id){
	$db				= D('answer');
	$where			= "id = '$id'";
	$res			= $db->where($where)->find();
	
	return $res;
	
}

/*试卷列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function testpaper_list($where, $limit){
	
	$db    									= M('testpaper', 'mp_');
	$where									= $where;
	$order									= " `id` desc ";
	$res									= $db->where($where)->order($order)->limit($limit)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['is_home']				= $val['is_home'];
		$list[$key]['is_recd']				= $val['is_recd'];
		
		$list[$key]['time']					= "开始时间：".date('Y-m-d h:i:s', $val['start_time'])."<br>".date('Y-m-d h:i:s', $val['end_time']);
		
		//定义创建时间和人
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		
		//状态、操作
		if($val['is_del'] == '1'){
			$list[$key]['attr']				= "未发布";
			$list[$key]['operation']		= "<a href='/admin/questions/questions_set/id/".$val['id']."'>设置问题</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_push/id/".$val['id']."'>发布</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_del/id/".$val['id']."'>删除</a>";
			
		}elseif($val['is_del'] == '2'){
			$list[$key]['attr']				= "已发布<br>".date('Y-m-d h:i:s', $val['push_time'])."<br>".get_field("admin", "mp_", "realname", "userid = ".$val['push_id']);
			$list[$key]['operation']		= "<a href='/admin/questions/testpaper_answer/id/".$val['id']."'>问卷统计</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_stop/id/".$val['id']."'>停止</a>";
			
		}elseif($val['is_del'] == '3'){
			$list[$key]['attr']				= "已停止";
			$list[$key]['operation']		= "<a href='/admin/questions/testpaper_record/id/".$val['id']."'>参与记录</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_push/id/".$val['id']."'>发布</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_del/id/".$val['id']."'>删除</a>";
		}elseif($val['is_del'] == '0'){
			$list[$key]['attr']				= "已删除";
			$list[$key]['operation']		= "<a href='/admin/questions/testpaper_record/id/".$val['id']."'>问卷统计</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_edit/id/".$val['id']."'>编辑</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_push/id/".$val['id']."'>发布</a>";
			$list[$key]['operation']		.= " | <a href='/admin/questions/testpaper_undel/id/".$val['id']."'>恢复</a>";
		}
		
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

function testpaper_info($id){
	$db				= D('testpaper');
	$where			= "id = '$id'";
	$res			= $db->where($where)->find();
	
	return $res;
}

/*会员卡列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function membercard_list($where){
	
	$db    								= M('member_card', 'mp_');
	$where								= "is_del != 0 ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']					= $val['name'];
		$list[$key]['price']				= $val['price'];
		$list[$key]['point']				= $val['point'];
		$list[$key]['thumb']				= $val['thumb'];
		$list[$key]['desc']					= $val['desc'];
		
		//取出购卡总数
		$member_db							= D('member_card');
		$where								= "card_id = ".$val['id'];
		$list[$key]['user_total']			= $member_db->where($where)->count();
		
		//定义操作
		
		$list[$key]['operation']			= "<a href='/admin/membercard/membercard_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/membercard/membercard_del/id/".$val['id']."'>删除</a>";
		//$list[$key]['operation']			.= " | <a href='/admin/member/member_lock/id/".$val['id']."'>锁定</a>";
	}
	
	//echo $db->getlastsql();
	return $list;
}

/*员工列表
 * @param (string)$where 查询条件
 * @param (string)$Belonging 员工的归属
 * return 返回：成功返回列表，不成功返回fals
*/

function employee_list($Belonging, $where){
	
	if(!empty($Belonging)){
		$Belonging = $Belonging."_";
	}
	
	$db    								= M($Belonging.'employee', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['tel']				= $val['tel'];
		$list[$key]['code']				= $val['code'];
		$list[$key]['member_sum']		= $val['member_sum'];
		$list[$key]['fund_flow']		= $val['fund_flow'];
		$list[$key]['salary']			= $val['salary'];
		$list[$key]['withdrawals']		= $val['withdrawals'];
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

/*订单列表
 * @param (string)$where 查询条件
 * @param (int)$type 订单返回状态,区分未付、已付、已完成订单
 * return 返回：成功返回列表，不成功返回fals
*/

function order_list($where, $type){
	
	$db    								= D('order');
	$g_db								= D("goods");
	$order								= "`create_time` desc ";
	//$where								= "status != 2".$where
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql()."<br>";
	//exit;
	
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['order_id']    		= $val['order_id'];
		$list[$key]['other_oid']    	= $val['other_oid'];
		$list[$key]['total']			= $val['total'];
		$list[$key]['pay']				= $val['pay'];
		$list[$key]['exp_id']    		= $val['exp_id'];
		$list[$key]['type']    			= $val['type'];
		$list[$key]['exp_sn']			= $val['exp_sn'];
		$list[$key]['actual_payment']	= $val['actual_payment'];
		$list[$key]['delivery']			= $val['delivery'];
		$list[$key]['pay_time']			= date('Y-m-d h:i:s', $val['pay_time']);
		$list[$key]['exp_time']			= date('Y-m-d h:i:s', $val['exp_time']);
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		
		//订单状态
		if($val['status'] == 0){
			$list[$key]['status_name']		= "正常";
		}elseif($val['status'] == 1){
			$list[$key]['status_name']		= "锁定";
		}elseif($val['status'] == 2){
			$list[$key]['status_name']		= "退单";
		}elseif($val['status'] == 4){
			$list[$key]['status_name']		= "未确认";
		}elseif($val['status'] == 5){
			$list[$key]['status_name']		= "已确认";
		}
		
		//订单类型
		if($val['model'] == 1){
			$list[$key]['model_name']		= "商品订单";
		}elseif($val['model'] == 2){
			$list[$key]['model_name']		= "充值订单";
		}elseif($val['model'] == 3){
			$list[$key]['model_name']		= "摇一摇领奖订单";
		}
		
		
		
		//付款方式
		if($val['pay_type'] == 1){
			$list[$key]['pay_name']		= "微支付";
		}elseif($val['pay_type'] == 2){
			$list[$key]['pay_name']		= "支付宝";
		}elseif($val['pay_type'] == 3){
			$list[$key]['pay_name']		= "银联";
		}elseif($val['pay_type'] == 4){
			$list[$key]['pay_name']		= "卡券支付";
		}elseif($val['pay_type'] == 5){
			$list[$key]['pay_name']		= "线下支付";
		}elseif($val['pay_type'] == 6){
			$list[$key]['pay_name']		= "不需要支付";
		}
		
		
		//取出购买人姓名
		$list[$key]['user_name']		=  get_field("member", "mp_", "nick", " id = ".$val['user_id']);
		
		
		
		//取出订单商品
		$actr_db						= D('activite_record');
		$actr_res						= $actr_db->where(array('order_sn'=>$val['order_id']))->getField('goods_id',true);
		//echo $actr_db->getlastsql()."<br>";
		$goods_ids						= implode(',', $actr_res);
		//echo $goods_ids;
		//$list_test[$key]['goods_ids']		= $goods_ids;
		if(empty($goods_ids)){
			//$list_test[$key]['type']		= 1;
		}else{
			//$list_test[$key]['type']		= 0;
			$goodsl_where				= "id in ('".$goods_ids."')".$goods_where;
			
			$gres						= $g_db->where($goodsl_where)->field('name, thumb, price')->select();
			//$list_test[$key]['sql'] 	= $g_db->getlastsql()."<br>";
		
			foreach($gres as $gkey => $gval){
				$list[$key]['goods'][$gkey]['id']     	 		= $gval['id'];
				$list[$key]['goods'][$gkey]['name']     	 	= $gval['name'];
				$list[$key]['goods'][$gkey]['thumb']     	 	= $gval['thumb'];
				$list[$key]['goods'][$gkey]['price']			= $gval['price'];
				$list[$key]['goods'][$gkey]['sum']     	 		= 1;
			}
			
		}
		
		//
		//$order_id						= $val['order_id'];
		//$g_where						= "order_id	= ".$val['order_id'];
		
		//print_r($list) ;
		//exit;
		
		//取出收货人信息
		$address											= $val['address'];
		
		$province_id										= get_field("member_address", "mp_", "province", " id = ".$address);
		$city_id											= get_field("member_address", "mp_", "city", " id = ".$address);
		$area_id											= get_field("member_address", "mp_", "area", " id = ".$address);
		
		//省市区 名称
		$list[$key]['address']								= get_field("areas", "mp_", "area_name", " area_id = ".$province_id);
		$list[$key]['address']								.= get_field("areas", "mp_", "area_name", " area_id = ".$city_id);
		$list[$key]['address']								.= get_field("areas", "mp_", "area_name", " area_id = ".$area_id);
		
		//详细地址
		$list[$key]['address']								.= get_field("member_address", "mp_", "address", " id = ".$address);
		//收货人
		$list[$key]['address']								.= "<br>收货人:".get_field("member_address", "mp_", "consignee", " id = ".$address);
		$list[$key]['address']								.= ", 电话:".get_field("member_address", "mp_", "tel", " id = ".$address);//联系电话
		
		
		//物流状态
		//物流状态
		//付款方式
		if($val['delivery'] == 0){
			$list[$key]['delivery_name']					= "已发货";
		}elseif($val['delivery'] == 1){
			$list[$key]['delivery_name']					= "未发货";
		}elseif($val['delivery'] == 1){
			$list[$key]['delivery_name']					= "已确认收货";
		}
		
		$list[$key]['exp_info']								= $list[$key]['delivery_name'];
		if($val['delivery'] != 0){
			$list[$key]['exp_info']							.= ", 由".get_field("express", "mp_", "name", " id = ".$exp_id)."负责配送";
			$list[$key]['exp_info']							.= ", 快递编号为".$val['exp_sn'].", 发货时间：".date('Y-m-d H:i:s', $val['exp_time']);
			if($val['delivery'] == 2){
				$list[$key]['exp_info']						.= "已于".date('Y-m-d H:i:s', $val['complete_time'])."确认收货";
			}
			
		}
		$list[$key]['complete_time']							= $val['complete_time'];
		
		//操作
		$list[$key]['operation']							= '<a href="/admin/order/order_info/id/'.$val['id'].'/model/'.$val['model'].'/type/'.$type.'">查看</a>';
		$list[$key]['operation']							.= '| <a href="/admin/order/order_rubi/nid/'.$val['id'].'">删除</a>';
		
	}
	
	
	
	//print_r($list);
	//exit;
	
	//echo $db->getlastsql();
	//exit;
	return $list;
}

/*订单总数
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function order_sum($where){
	
	$db    								= D('order');
	$where								= "delivery = 2 and pay = 0 ".$where;
	$res								= $db->where($where)->count();
	
	return $res;
	
}

/*销售总额
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/

function sales_sum($where){
	
	$db    								= D('order');
	$where								= "delivery = 2 and pay = 0 ".$where;
	$res								= $db->where($where)->sum('actual_payment');
	return $res;
	
}



/*订单详情
 * @param (int)$id 订单ID
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function order_info($id, $where){
	$db    								= M('order', 'mp_');
	$where								= "id = '$id'".$where;
	$res								= $db->where($where)->find();
	
	if(!empty($res['exp_time'])){
		$res['exp_time']				= date('Y-m-d h:i:s', $res['exp_time']);
	}
	if(!empty($res['pay_time'])){
		$res['pay_time']				= date('Y-m-d h:i:s', $res['pay_time']);
	}
	if(!empty($res['create_time'])){
		$res['create_time']				= date('Y-m-d h:i:s', $res['create_time']);
	}
	if(!empty($res['complete_time'])){
		$res['complete_time']			= date('Y-m-d h:i:s', $res['complete_time']);
	}
	
	if(!empty($res['exp_id'])){
		$res['exp_name']				= get_field("exp", "mp_", "name", " id = ".$res['exp_id']);
	}
	
	if($res['pay'] == 1){
		$res['pay_name'] = "未支付";
	}elseif($res['pay'] == 0){
		$res['pay_name'] = "已支付";
	}else{
		$res['pay_name'] = "异常";
	}
	
	if($res['pay_type'] == 1){
		$res['paytype_name'] = "微支付";
	}elseif($res['pay_type'] == 2){
		$res['paytype_name'] = "支付宝";
	}elseif($res['pay_type'] == 3){
		$res['paytype_name'] = "银联";
	}elseif($res['pay_type'] == 4){
		$res['paytype_name'] = "卡卷支付";
	}elseif($res['pay_type'] == 5){
		$res['paytype_name'] = "线下支付";
	}else{
		$res['paytype_name'] = "异常";
	}
	
	if($res['delivery'] == 1){
		$res['delivery_name'] = "未发货";
	}elseif($res['delivery'] == 0){
		$res['delivery_name'] = "已发货";
	}elseif($res['delivery'] == 2){
		$res['delivery_name'] = "已完成";
	}else{
		$res['delivery_name'] = "异常";
	}
	
	if($res['status'] == 1){
		$res['status_name'] = "锁定";
	}elseif($res['status'] == 0){
		$res['status_name'] = "正常";
	}elseif($res['status'] == 2){
		$res['status_name'] = "删除";
	}elseif($res['status'] == 3){
		$res['status_name'] = "退单";
	}elseif($res['status'] == 5){
		$res['status_name'] = "已完成";
	}else{
		$res['status_name'] = "异常";
	}
	
	//取出收货人信息
	$address			= $res['address'];
	
	$res['province_id']	= get_field("member_address", "mp_", "province", " id = ".$address);
	$res['city_id']		= get_field("member_address", "mp_", "city", " id = ".$address);
	$res['area_id']		= get_field("member_address", "mp_", "area", " id = ".$address);
	
	$res['address']		= get_field("member_address", "mp_", "name", " id = ".$res['province_id']);
	$res['address']		.= get_field("member_address", "mp_", "name", " id = ".$res['city_id']);
	$res['address']		.= get_field("member_address", "mp_", "name", " id = ".$res['area_id']);
	
	$res['address']		.= get_field("member_address", "mp_", "address", " id = ".$address);
	$res['consignee']	= get_field("member_address", "mp_", "consignee", " id = ".$address);
	$res['tel']			= get_field("member_address", "mp_", "tel", " id = ".$address);
	
	//获取商品信息(投币购)
	$goods_db			= D('goods');
	$actr_db			= D('activite_record');
	$artr_res			= $actr_db->where(array('order_sn'=>$res['order_id']))->Field('id, goods_id, sign_time, expiration_time, receive_time, type, activite')->find();
	
	
	
	$goods_id			= $artr_res['goods_id'];
	
	$res['sign_time']	= date('Y-m-d H:i:s', $artr_res['goods_id']);
	$res['expiration_time']	= date('Y-m-d H:i:s', $artr_res['expiration_time']);
	$res['receive_time']= date('Y-m-d H:i:s', $artr_res['receive_time']);
	
	$res['actr_id']		= $artr_res['id'];
	$res['type']		= $artr_res['type'];
	$res['activite']	= $artr_res['activite'];
	
	
	$goods_info			= $goods_db->where(array('id'=>$goods_id))->field('name, thumb, price, freight')->find();
	
	
	$res['goods_name']	= $goods_info['name'];
	$res['goods_thumb']	= $goods_info['thumb'];
	$res['goods_price']	= $goods_info['price'];
	$res['goods_freight']= $goods_info['freight'];
	$res['goods_sum']	= 1;
	return	$res;
}



/*订单商品列表
 * @param (int)$order_id 查询条件
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function og_list($order_id, $actr_where, $goods_where){
	
	$db								= M('goods', 'mp_');
	$actr_db						= M('activite_record', 'mp_');
	$actr_where						= "order_sn = '$order_id'".$actr_where;
	
	//取出订单商品
	$actr_res						= $actr_db->where($actr_where)->getField('goods_id',true);
	//echo $actr_db->getlastsql()."<br>";
	
	//$res['asql']					= $actr_db->getlastsql();
	$goods_ids						= implode(',', $actr_res);
	//echo $goods_ids;
	$goods_where					= "id in ('".$goods_ids."')".$goods_where;
	$res							= $db->where($goods_where)->select();
	//$res['gsql']					= $db->getlastsql();
	//echo $db->getlastsql()."<br>";
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']			= $val['id'];
		//取出商品名称
		//先取出模块
		$model_code					= $val['model'];
		
		if($model_code == 'ticket'){
			$list[$key]['name']		= $val['name'];
			$list[$key]['thumb']	= $val['thumb'];
		}else{
			$list[$key]['name']		= $val['name'];
			$list[$key]['thumb']	= $val['thumb'];
		}
		$list[$key]['freight']		= $val['freight'];
		$list[$key]['price']		= $val['price'];
		$list[$key]['sum']			= 1;
		
	}
	//print_r($list);
	//exit;
	return	$list;
}

/*快递列表
 * @param (string)$where 查询条件
 * @param (string)$Belonging 员工的归属
 * return 返回：成功返回列表，不成功返回fals
*/

function exp_list($Belonging, $where){
	
	
	$db    								= M('exp', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['api']				= $val['api'];
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		if($val['is_del'] == 0){
			$list[$key]['is_del']			= "锁定";
		}elseif($val['is_del'] == 1){
			$list[$key]['is_del']			= "正常";
		}
		
	}
	
	//echo $db->getlastsql();
	return $list;
}


/*---------------------------游戏管理方法--------------------------------*/
/*游戏列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function mgame_list($where, $sub_where, $type){
	$db									= M("mgame", "mg_");
	
	$p_where							= "is_del != 0 ".$where;
	
	$res								= $db->where($p_where)->order("`order` desc")->select();
	
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['game_name']			= $val['game_name'];
		$list[$key]['game_model']			= $val['game_model'];
		
		//设置创建人以及修改人
		$list[$key]['start_time']			= date('Y-m-d h:i:s', $val['start_time']);
		$list[$key]['end_time']				= date('Y-m-d h:i:s', $val['end_time']);
	
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//定义操作
		$list[$key]['operation']			= "<a href='/admin/Mgame/mgame_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Mgame/mgame_del/id/".$val['id']."'>删除</a>";
	}
	return $list;
}
/*---------------------------权限管理模块所属方法--------------------------------*/


/*菜单列表
 * @param (string)$where 查询条件
 * @param (string)$sub_where 子菜单查询条件
 * @param (int)$type 1正常列表，2配合sub_where分别取出选中和未选中
 * return 返回：成功返回列表，不成功返回fals
*/
function menu_list($where, $sub_where, $type){
	$db									= D("admin_auth");
	
	$p_where							= "is_del != 1 and parent_id = 0 ".$where;
	
	//取出一级菜单
	$res								= $db->where($p_where)->order("`order` desc")->select();
	
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['menu_no']			= $val['menu_no'];
		$list[$key]['create_id']		= $val['create_id'];
		$list[$key]['up_id']			= $val['up_id'];
		$list[$key]['order']			= $val['order'];
		
		if($val['parent_id'] == "0"){
			$list[$key]['parent_id']	= "0";
			$list[$key]['parent_name']	= "顶级菜单";
		}else{
			$list[$key]['parent_id']	= $val['parent_id'];
			$list[$key]['parent_name']	= get_field("admin_menu", "online_", "name", "id = ".$val['id']);
		}
		
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		$list[$key]['up_time']			= date('Y-m-d h:i:s', $val['up_time']);
		$list[$key]['create_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
		$list[$key]['up_name']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
		
		
		//取出选中和未选中
		if($type == 2){
			
			$subp_where												= "parent_id = ".$val['id'].$where;
			$subp_res												= $db->where($subp_where)->order("`order` desc")->select();
			
			$subcat_total											= $db->where($subp_where)->count();
			
			$list[$key]['subcat_total']								= $subcat_total;
			//echo $db->getlastsql();
			//exit;
			foreach($subp_res as $subp_key => $subp_val){
				$list[$key]['subp'][$subp_key]['id']     	 		= $subp_val['id'];
				$list[$key]['subp'][$subp_key]['name']    			= $subp_val['name'];
				$list[$key]['subp'][$subp_key]['menu_no']			= $subp_val['menu_no'];
				$list[$key]['subp'][$subp_key]['create_id']			= $subp_val['create_id'];
				$list[$key]['subp'][$subp_key]['create_time']		= date('Y-m-d h:i:s', $subp_val['create_time']);
				$list[$key]['subp'][$subp_key]['up_time']			= date('Y-m-d h:i:s', $subp_val['up_time']);
			}
			
			$subnop_where											= "parent_id = ".$val['id'].$sub_where;
			$subnop_res												= $db->where($subnop_where)->order("`order` desc")->select();
			//echo $db->getlastsql();
			//exit;
			foreach($subnop_res as $subnop_key => $subnop_val){
				$list[$key]['subnop'][$subnop_key]['id']     	 	= $subnop_val['id'];
				$list[$key]['subnop'][$subnop_key]['name']    		= $subnop_val['name'];
				$list[$key]['subnop'][$subnop_key]['menu_no']		= $subnop_val['menu_no'];
				$list[$key]['subnop'][$subnop_key]['create_id']		= $subnop_val['create_id'];
				$list[$key]['subnop'][$subnop_key]['create_time']	= date('Y-m-d h:i:s', $subnop_val['create_time']);
				$list[$key]['subnop'][$subnop_key]['up_time']		= date('Y-m-d h:i:s', $subnop_val['up_time']);
			}
			
		}else{	
			
			$sub_where												= "parent_id = ".$val['id'].$where;
			$sub_res												= $db->where($sub_where)->order("`order` desc")->select();
			
			$subcat_total											= $db->where($sub_where)->count();
			
			$list[$key]['subcat_total']								= $subcat_total;
			
			foreach($sub_res as $sub_key => $sub_val){
				$list[$key]['sub'][$sub_key]['id']     	 		= $sub_val['id'];
				$list[$key]['sub'][$sub_key]['name']    		= $sub_val['name'];
				$list[$key]['sub'][$sub_key]['menu_no']			= $sub_val['menu_no'];
				$list[$key]['sub'][$sub_key]['create_id']		= $sub_val['create_id'];
				$list[$key]['sub'][$sub_key]['create_time']		= date('Y-m-d h:i:s', $sub_val['create_time']);
				$list[$key]['sub'][$sub_key]['up_time']			= date('Y-m-d h:i:s', $sub_val['up_time']);
			}
		}
	}
	
	return $list;
}

/*角色列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function role_list($where){
	$db									= D("admin_role");
	
	$where								= "is_del != 0 ".$where;
	
	$res								= $db->where($where)->order("`order` desc")->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['description']		= $val['description'];
		$list[$key]['rolename']			= $val['rolename'];
		$list[$key]['code']				= $val['code'];
		$list[$key]['create_id']		= $val['create_id'];
		$list[$key]['up_id']			= $val['up_id'];
		$list[$key]['order']			= $val['order'];
		
		if(empty($val['create_time']) || $val['create_time'] == '0'){
			$list[$key]['create_time']	= "";
		}else{
			$list[$key]['create_time']	= date('Y-m-d h:i:s', $val['create_time']);
			$list[$key]['create_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
		}
		if(empty($val['up_time']) || $val['up_time'] == '0'){
			$list[$key]['up_time']		= "";
		}else{
			$list[$key]['up_time']		= date('Y-m-d h:i:s', $val['up_time']);
			$list[$key]['up_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
		}
		
		
		
		
	}
	//echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
	//echo $db->getlastsql();
	//print_r($list);
	//exit;
	return $list;
}

/**
 * 部门列表
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function department_list($where){
	$db    								= M('department', 'mp_');
	$where								= "is_del != 0 and parent_id = 0 ".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['code']				= $val['code'];
		$list[$key]['order']			= $val['order'];
		
		if($val['parent_id'] == "0"){
			$list[$key]['parent_id']	= "0";
			$list[$key]['parent_name']	= "顶级部门";
		}else{
			$list[$key]['parent_id']	= $val['parent_id'];
			$list[$key]['parent_name']	= get_field("admin_menu", "mp_", "name", "id = ".$val['id']);
		}
		
		$list[$key]['create_id']		= $val['create_id'];
		$list[$key]['up_id']			= $val['up_id'];
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		$list[$key]['up_time']			= date('Y-m-d h:i:s', $val['up_time']);
		$list[$key]['create_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
		$list[$key]['up_name']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
		
		$sub_where						= "parent_id = ".$val['id'];
		
		$sub_res						= $db->where($sub_where)->order("`order` desc")->select();
		
		$subcat_total					= $db->where($sub_where)->count();
		
		$list[$key]['subcat_total']		= $subcat_total;
		
		foreach($sub_res as $sub_key => $sub_val){
			$list[$key]['sub'][$sub_key]['id']     	 		= $sub_val['id'];
			$list[$key]['sub'][$sub_key]['name']    		= $sub_val['name'];
			$list[$key]['sub'][$sub_key]['code']			= $sub_val['code'];
			$list[$key]['sub'][$sub_key]['create_id']		= $sub_val['create_id'];
			$list[$key]['sub'][$sub_key]['create_time']		= date('Y-m-d h:i:s', $sub_val['create_time']);
			$list[$key]['sub'][$sub_key]['up_time']			= date('Y-m-d h:i:s', $sub_val['up_time']);
		}
	}
	return $list;
}

/**
 * 自定义菜单
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function cons_menu_list($where){
	
	$db    								= M('menu', 'mp_');
	$where								= "is_del != 0 and parentid = 0 ".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		
		if($val['parent_id'] == "0"){
			$list[$key]['parentid']		= "0";
			$list[$key]['parent_name']	= "顶级菜单";
		}else{
			$list[$key]['parentid']	= $val['parentid'];
			$list[$key]['parent_name']	= get_field("admin_menu", "mp_", "name", "id = ".$val['id']);
		}
		
		$sub_where						= "parentid = ".$val['id'];
		
		$subcat_total					= $db->where($sub_where)->count();
		$list[$key]['subcat_total']		= $subcat_total;
		if($subcat_total>0){
			$sub_res					= $db->where($sub_where)->order("`order` desc")->select();
			$list[$key]['subcat_total']	= $subcat_total;
			
			foreach($sub_res as $sub_key => $sub_val){
				$list[$key]['sub'][$sub_key]['id']     	 		= $sub_val['id'];
				$list[$key]['sub'][$sub_key]['name']    		= $sub_val['name'];
			}
		}
	}
	return $list;
}

/**
 * 自定义菜单数量
 * @param (string)$parent_id 父ID如果为空则判断一级菜单个数
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function cons_menu_total($parent_id){
	
	$db    								= M('menu', 'mp_');
	$where								= "is_del != 0 and parentid = ".$parent_id;
	$res								= $db->where($where)->count();
	
	return $res;
}

/**
 * 优惠券输出方法
 * @param (int)$type 优惠券类型 1打折券 2现金券
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function coupon_list($type, $fun_where){
	
	$db    								= D('coupon');
	if(empty($type)){
		$where							= "is_del != 0".$fun_where;
	}else{
		$where							= "is_del != 0 and type = ".$type.$fun_where;
	}
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['type']				= $val['type'];
		$list[$key]['desc']				= $val['desc'];
		$list[$key]['discount']			= $val['discount'];
		$list[$key]['amount']			= $val['amount'];
		$list[$key]['status']			= $val['status'];
		
		$list[$key]['count']			= $val['count'];
		$list[$key]['order']			= $val['order'];
		
		if($val['status'] == 0){
			$list[$key]['status_name']	= "未发布";
			$list[$key]['operation']	= '<a href="/admin/Coupon/coupon_release/id/'.$val['id'].'/type/'.$val['type'].'/">发布</a>';
		}elseif($val['status'] == 1){
			$list[$key]['status_name']	= "已发布";
			$list[$key]['operation']	= '<a href="/admin/Coupon/coupon_record/id/'.$val['id'].'/type/'.$val['type'].'/">查看记录</a>';
		}
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']		= '';
		}else{
			$list[$key]['up_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		$list[$key]['operation']		.= " | <a href='/admin/Coupon/coupon_edit/id/".$val['id']."/type/".$val['type']."'>修改</a>";
		$list[$key]['operation']		.= " | <a href='/admin/Coupon/coupon_del/id/".$val['id']."/type/".$val['type']."'>删除</a>";
		
	}
	
	//echo $db->getlastsql();
	//print_r($list);
	//exit;
	return $list;
}

/**
 * 优惠券使用记录方法
 * @param (int)$coupon_id  新闻分类ID
 * @param (int)$type 条件
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function coupon_record_list($coupon_id, $fun_where){
	
	$db    								= D('coupon_record');
	$where								= " coupon_id = ".$coupon_id.$fun_where;
	
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['user_id']     	 	= $val['user_id'];
		$list[$key]['coupon_id']     	= $val['coupon_id'];
		$list[$key]['coupon_name']     	= get_field('coupon', 'mp_', 'name', 'id = '.$val['coupon_id']);
		$list[$key]['coupon_desc']     	= get_field('coupon', 'mp_', 'desc', 'id = '.$val['coupon_id']);
		$list[$key]['user_name']     	= get_field('member', 'mp_', 'username', 'id = '.$val['user_id']);
		
		$list[$key]['status']			= $val['status'];
		
		if($val['status'] == 0){
			$list[$key]['status_name']	= "未使用";
		}elseif($val['status'] == 1){
			$list[$key]['status_name']	= "已使用";
		}
		
		if(empty($val['grant_time'])){
			$list[$key]['grant_time']	= '';
		}else{
			$list[$key]['grant_time']	.= date('Y-m-d h:i:s', $val['grant_time']);
		}
		
		if(empty($val['use_time'])){
			$list[$key]['use_time']		= '';
		}else{
			$list[$key]['use_time']		.= date('Y-m-d h:i:s', $val['use_time']);
		}
		
		$order_id						= $val['order_id'];
		if(empty($order_id)){
			$list[$key]['use_order']		= '<a href="/admin/order/order_info/id/'.$order_id.'">订单</a>';
		}
		
	}
	//echo $db->getlastsql();
	//print_r($list);
	//exit;
	return $list;
}

/**
 * 活动列表
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function so_list($where){
	$db    								= M('so', 'mp_');
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['pname']			= $val['code'];
		$list[$key]['pname']			= $val['pname'];
		$list[$key]['tel']     	 		= $val['tel'];
		$list[$key]['age']				= $val['age'];
		$list[$key]['email']			= $val['email'];
		$list[$key]['industry']			= $val['industry'];
		$list[$key]['sn']				= $val['sn'];
		
		if($val['status'] == "0"){
			$list[$key]['status']		= "未出票";
			//定义操作
			$list[$key]['operation']	= "<a href='/Admin/So/so_edit/id/$val[id]'>出票</a> ";
			
		}elseif($val['status'] == "1"){
			$list[$key]['status']	= "已出票";
			//定义操作
			$list[$key]['operation']	= "<a href='/Admin/So/so_view/id/$val[id]'>查看</a> ";
		}
		
		
		$list[$key]['create_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['operator']);
		
		$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
		if(!empty($val['operator_time'])){
			$list[$key]['operator_time']	= date('Y-m-d h:i:s', $val['operator_time']);
		}
		
		
	}
	return $list;
}


/*管理员列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function admin_list($where){
	$db										= D("admin");
	
	$where									= "is_del != 0 ".$where;
	
	$res									= $db->where($where)->order("`userid` desc")->select();
	
	//echo "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
	//echo $db->getlastsql();
	//print_r($list);
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['userid']     	 		= $val['userid'];
		$list[$key]['username']				= $val['username'];
		$list[$key]['roleid']				= $val['roleid'];
		$list[$key]['email']				= $val['email'];
		$list[$key]['realname']				= $val['realname'];
		$list[$key]['tel']					= $val['tel'];
		
		if(empty($val['department_id'])){
			
			$list[$key]['department_id']	= '';
			$list[$key]['department_name']	= '未指定';
			
		}else{
			
			$list[$key]['department_id']	= $val['department_id'];
			$list[$key]['department_name']	= get_field("department", "mp_", "name", "id = ".$val['department_id']);
			
		}
		
		if(empty($val['roleid'])){
			
			$list[$key]['roleid']			= '';
			$list[$key]['rolename']			= '未指定';
			
		}else{
			
			$list[$key]['roleid']			= $val['roleid'];
			$list[$key]['rolename']			= get_field("admin_role", "mp_", "rolename", "id = ".$val['roleid']);
		}
		
		if(empty($val['lastlogintime']) || $val['lastlogintime'] == 0){
			
			$list[$key]['lastlogintime']	= "未登录";
			
		}else{
			
			$list[$key]['lastlogintime']	= date('Y-m-d h:i:s', $val['lastlogintime']);
			$list[$key]['lastloginip']		= $val['lastloginip'];
			
		}
		
		if(empty($val['create_time']) || $val['create_time'] == '0'){
			$list[$key]['create_time']		= "";
		}else{
			$list[$key]['create_time']		= date('Y-m-d h:i:s', $val['create_time']);
			$list[$key]['create_name']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
		}
		if(empty($val['up_time']) || $val['up_time'] == '0'){
			$list[$key]['up_time']			= "";
		}else{
			$list[$key]['up_time']			= date('Y-m-d h:i:s', $val['up_time']);
			$list[$key]['up_name']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
		}
		
		$list[$key]['operation']		= "<a href='/admin/admin/admin_edit/id/".$val['userid']."'>修改</a>";
		$list[$key]['operation']		.= " | <a href='/admin/admin/admin_del/id/".$val['userid']."'>删除</a>";
		
	}
	
	return $list;
}

/*用户菜单列表
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function user_menu_list($where){
	$db									= D("admin_menu");
	
	//取出当前用户的角色
	$user_role_id						= session('roleid');
	
	//取出当前用户权限
	$user_listorder						= get_field("admin_role", "mp_", "listorder", "id = ".$user_role_id);
	
	$m_where							= "is_del != 0 and parent_id = 0 and id in (".$user_listorder.")";
	
	$res								= $db->where($m_where)->order("`order` desc")->select();
	
	
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['menu_no']			= $val['menu_no'];
		$list[$key]['url']				= $val['url'];
		
		$list[$key]['sub_total']  		= $db->where("parent_id = ".$val['id'])->count();
		
		$sub_where						= "parent_id = ".$val['id']." and menu_no not like '%add%' and menu_no not like '%edit%' and menu_no not like '%del%' and menu_no not like '%grant%' and menu_no not like '%renewal%' and id in(".$user_listorder.")";
		
		$sub_res						= $db->where($sub_where)->order("`order` desc")->select();
		
		
		
		foreach($sub_res as $sub_key => $sub_val){
			
			$list[$key]['sub'][$sub_key]['id']     	 		= $sub_val['id'];
			$list[$key]['sub'][$sub_key]['name']    		= $sub_val['name'];
			$list[$key]['sub'][$sub_key]['menu_no']			= $sub_val['menu_no'];
			$list[$key]['sub'][$sub_key]['url']				= $sub_val['url'];
		}
	}
	//print_r($list);
	//exit;
	return $list;
}

/*操作日志记录
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function loger_list($where){
	$db    								= D('loger');
	$order								= "`id` desc ";
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['model']    		= $val['model'];
		$list[$key]['action']			= $val['action'];
		$list[$key]['info_id']			= $val['info_id'];
		$list[$key]['op_desc']			= $val['op_desc'];
		
		
		if(empty($val['op_time']) || $val['op_time'] == '0'){
			$list[$key]['create_time']	= "";
		}else{
			$list[$key]['op_time']		= date('Y-m-d h:i:s', $val['op_time']);
			$list[$key]['op_id']		= get_field("admin", "mp_", "realname", "userid = ".$val['op_id']);
		}
		
		//$list[$key]['operation']		= '<a href="/admin/loger/loger_info/id/'.$val['id'].'>查看</a>';
	
	}
	
	return $list;
	
}

/*插入操作日志记录
 * @param (string)$where 查询条件
 * return 返回：成功返回列表，不成功返回fals
*/
function loger_add($model, $action, $info_id, $op_desc){
	$db								= D('loger');
	$data['model']					= $model;
	$data['action']					= $action;
	$data['info_id']				= $info_id;
	$data['op_desc']				= $op_desc;
	$data['op_id']					= session('userid');
	$data['op_time']				= time();
	$data['op_id']					= realIp();
	
	$db->add($data);
	
}

/**
 * 模块输出方法
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function model_list($where){
	
	$db    								= D('model');
	$where								= "status != 1".$where;
	$order								= " `id` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['code']				= $val['code'];
		$list[$key]['material_id']		= $val['material_id'];
		$list[$key]['status']			= $val['status'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']		= '';
		}else{
			$list[$key]['up_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']		= "<a href='/admin/model/model_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']		.= " | <a href='/admin/model/model_del/id/".$val['id']."'>删除</a>";
		
	}
	//echo $db->getlastsql();
	return $list;
}


/*---------------------------------------------微信接口类方法-------------------------------------------------*/

/**
 * 微信素材输出方法
 * @param (int)$cat_id  新闻分类ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function material_list($where){
	
	$db    								= M('material', 'mx_');
	
	$where								= "is_del != 0".$where;
	
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['title']     	 	= $val['title'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['desc']				= $val['desc'];
		$list[$key]['url']				= $val['url'];
		$list[$key]['attribute']		= $val['attribute'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['is_del']			= $val['is_del'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']		= '';
		}else{
			$list[$key]['up_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']		.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']		= "<div><a class='btn btn-warning' href='/admin/Material/material_edit/id/".$val['id']."'>修改</a></div>";
		$list[$key]['operation']		.= "<div><a class='btn btn-danger' href='/admin/Material/material_del/id/".$val['id']."'>删除</a></div>";
		
	}
	//echo $db->getlastsql();
	return $list;
}

/*获取内容信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function info_info_mx($info_id, $table, $where){
	
	$db						= M($table, "mx_");
	
	if($table == 'admin'){
		$where				= " is_del != 0 and userid = '$info_id'".$where;
	}else{
	
		$where				= " is_del != 0 and id = '$info_id'".$where;
	}
	$res					= $db->where($where)->find();
	
	if($res['start_time']){
		$res['start_time']	= date('Y-m-d h:i:s', $res['start_time']);
	}
	
	if($res['end_time']){
		$res['end_time']	= date('Y-m-d h:i:s', $res['end_time']);
	}
	
	if($res['create_time']){
		$res['create_time']	= date('Y-m-d h:i:s', $res['create_time']);
	}
	
	if($res['up_time']){
		$res['up_time']		= date('Y-m-d h:i:s', $res['up_time']);
	}
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/*获取微信设置信息
 * @param (int)$id ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function wx_info($id){
	$db							= M("enterprise", "mx_");
	
	$where						= " id = ".$id;
	
	$res						= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	
	if(empty($res['url'])){
		$res['url']				= "/Mshop/wtapi/autht/".substr(md5($res['id']),8,16);
	}
	
	if(empty($res['reg_time'])){
		$res['reg_time']		= time();
	}
	
	if(empty($res['token'])){
		$res['token']	= substr(md5(md5($res['id']).md5($res['reg_time'])),8,16);
	}
	
	if($res['status'] == 0){
		$res['status']			= "正常";
	}elseif($res['status'] == 1){
		$res['status']	= "锁定";
	}elseif($res['status'] == 1){
		$res['status']			= "欠费";
	}
	
	return $res;
}



/**
 * @desc  im:自定义菜单发布
 * */

function release_consmenu(){
	$db				    										= D('menu');
	$res														= $db->where("parentid = 0 ")->order("`order` desc")->select();
	$len														= $db->where("parentid = 0 ")->count();
	$i															= 0;
	$info														= '{"button":[';
	
	foreach($res as $key => $val){
		
		$i++;
		$sub_total												= $db->where("parentid = ".$val['id'])->count();
		
		if($sub_total > 0){
			//有子菜单
			$info												.= '{';	
			$info												.= '"name":"'.$val['name'].'",';
			$info												.= '"sub_button":[';
			$sub_res											= $db->where("parentid = ".$val['id'])->order("`order` desc")->select();
			
			$sub_len											= $sub_total;
			$sub_i												= 0;
			foreach($sub_res as $sub_key => $sub_val){
				$sub_i ++;
				
				$info												.= '{';
				
				//菜单类型				
				if($sub_val['type'] == 'count' || $sub_val['type'] == 'model'){
					$info											.= '"type":"click",';
				}else{
					$info											.= '"type":"'.$sub_val['type'].'",';
				}
				
				$info												.= '"name":"'.$sub_val['name'].'",';
				
				if($sub_val['type'] == 'view'){
					$info											.= '"url":"'.$sub_val['url'].'"';
				}elseif($sub_val['type'] == 'click'){
					$info											.= '"key":"'.$sub_val['keywords'].'"';
				}elseif($sub_val['type'] == 'count' || $sub_val['type'] == 'model'){
					$info											.= '"key":"'.$sub_val['view_id'].'"';
				}
				if($sub_i == $sub_len){
					$info											.= '}';
				}else{
					$info											.= '},';
				}
				
			}
			//$info												.= ']}';
			if($i == $len){
				$info											.= ']}';
			}else{
				$info											.= ']},';
			}
		}else{
			//没有二级菜单	
			
			$info												.= '{';
			
			//菜单类型
			if($val['type'] == 'count' || $val['type'] == 'model'){
				$info											.= '"type":"click",';
			}else{
				
				$info											.= '"type":"'.$val['type'].'",';
			}
			
			$info												.= '"name":"'.$val['name'].'",';
			
			if($val['type'] == 'view'){
				$info											.= '"url":"'.$val['url'].'"';
			}elseif($val['type'] == 'count' || $val['type'] == 'model'){
				$info											.= '"key":"'.$val['view_id'].'"';
			}elseif($val['type'] == 'click'){
				$info											.= '"key":"'.$val['keywords'].'"';
			}
			
			if($i == $len){
				$info											.= '}';
			}else{
				$info											.= '},';
			}
			
		}
	}
	$info														.= ']}';
	//获取菜单串
	//$menu_info													= "{\"button\":".json_encode($list)."}";
	
	//echo $info;
	
	//申请at
	$at															= getAT(1);
	//echo $at."<br>";
	
	//echo $info."<br>";
	//exit;
	$res = api_notice_increment('https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$at, $info);
	
	$res = json_decode($res, true);
	
	$res = json_decode($res);
	//echo $res['errcode'];
	//exit;
	return $res;
	
	
}

/**
 * 获取关键词回复列表
 * @return array
 */
function keywords_list($where){
	$db   = M('keywords_msg', 'mx_');
	$list = $db->where($where)->order("`order` desc")->select();
	foreach($list as $key => $value){
		$list[$key]['keywords'] 			= $value['keywords'];
		$list[$key]['type']					= $value['type'];
		$list[$key]['ids'] 					= $value['ids'];
		$list[$key]['news_number'] 			= $value['news_number'];
		$list[$key]['contect'] 				= $value['contect'];
		
		$list[$key]['type']					= $value['type'];
		
		if($value['type'] == 0){
			$list[$key]['type_name']		= "文字";
		}elseif($value['type'] == 1){
			$list[$key]['type_name']		= "图文";
		}elseif($value['type'] == 3){
			$list[$key]['type_name']		= "图片";
		}elseif($value['type'] == 4){
			$list[$key]['type_name']		= "语音";
		}elseif($value['type'] == 5){
			$list[$key]['type_name']		= "视频";
		}elseif($value['type'] == 6){
			$list[$key]['type_name']		= "音乐";
		}
		
		
		//设置创建人以及修改人
		if(empty($value['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$value['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $value['create_time']);
		}
		
		if(empty($value['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$value['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $value['up_time']);
		}
		
		//处理操作
		$list[$key]['operation']			= "<div><a class='btn btn-warning'  href='/admin/keywords/keywords_edit/id/".$value['id']."'>编辑</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger'  href='/admin/keywords/keywords_del/id/".$value['id']."'>删除</a></div>";
		
	}
	return $list;
}
/**
 * 获取关键词回复详情
 * @return array
 */
function keywords_info($id){
	$db   = M('keywords_msg', 'mx_');
	$list = $db->where(array('id'=>$id))->find();
	
	
	//获取已选回复的图文内容
	if($list['ids'] != ''){
		$material_db		= M('material', 'mx_');
		$where				= "id in (".$list['ids'].")";
		$material_res		= $material_db->where($where)->field('id, title, thumb')->select();
		
		foreach($material_res as $key => $val){
			$list['news'][$key]['id']		= $val['id'];
			$list['news'][$key]['title']	= $val['title'];
			$list['news'][$key]['thumb']	= $val['thumb'];
		}
		
		//取未选回复内容
		
		$nwhere				= "id not in (".$list['ids'].")";
		$nmaterial_nres		= $material_db->where($nwhere)->field('id, title, thumb')->select();
		foreach($nmaterial_nres as $key => $val){
			$list['notnews'][$key]['id']	= $val['id'];
			$list['notnews'][$key]['title']	= $val['title'];
			$list['notnews'][$key]['thumb']	= $val['thumb'];
		}
	}
	
	return $list;
}


/**
 * 获取快递列表
 * @return array
 */
function express_list($where){
	$db   									= D('express');
	$list 									= $db->where($where)->order("`order` desc")->select();
	foreach($list as $key => $value){
		$list[$key]['name'] 				= $value['name'];
		$list[$key]['order']				= $value['order'];
		$list[$key]['api'] 					= $value['api'];
		$list[$key]['desc'] 				= $value['desc'];
		
		//设置创建人以及修改人
		if(empty($value['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$value['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $value['create_time']);
		}
		
		if(empty($value['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$value['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $value['up_time']);
		}
		
		//处理操作
		$list[$key]['operation']			= "<div><a class='btn btn-warning' href='/admin/express/express_edit/id/".$value['id']."'>编辑</a></div>";
		$list[$key]['operation']			.= "<div><a class='btn btn-danger' href='/admin/express/express_del/id/".$value['id']."'>删除</a></div>";
		
	}
	return $list;
}


/**
 * 获取快递详情
 * @return array
 */
function express_info($id){
	
	$db   = D('express');
	$list = $db->where(array('id'=>$id))->find();
	
	return $list;
}

/*--------------------旅游管理类方法-----------------------------*/

/**
 * 景点列表输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function scenicspot_list($where, $limit){
	
	$where								= $where;
	$db    								= M('scenicspot', 'to_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->limit($limit)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		$list[$key]['longitude']		= $val['longitude'];
		$list[$key]['dimensions']		= $val['dimensions'];
		$list[$key]['position']			= $val['position'];
		
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		$list[$key]['is_wx']			= $val['is_wx'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/ToScenicspot/scenicspot_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/ToScenicspot/scenicspot_del/id/".$val['id']."'>删除</a>";
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}


/*景点详情
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function scenicspot_info($id, $where){
	
	$db						= M("scenicspot", "to_");
	
	$where					= "is_del != 0 and id = '$id'".$where;
	
	$res					= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/**
 * 线路列表输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function travelline_list($where, $order){
	
	$where								= "is_del != 0".$where;
	$db    								= M('travel_line', 'to_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['thumb_wx']			= $val['thumb_wx'];
		$list[$key]['order']			= $val['order'];
		
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		
		//取出景点名称
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		$list[$key]['operation']			= "<a href='/admin/ToTravelline/travelline_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/ToTravelline/travelline_del/id/".$val['id']."'>删除</a>";
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}


/*路线详情
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function travelline_info($id, $where){
	
	$db						= M("travel_line", "to_");
	
	$where					= "id = '$id'".$where;
	
	$res					= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/**
 * 景区门票列表输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function Toticket_list($where, $order){
	
	$where								= "is_del != 0 ".$where;
	$db    								= M('ticket', 'to_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']     	 	= $val['name'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['keywords']			= $val['keywords'];
		$list[$key]['order']			= $val['order'];
		
		$list[$key]['is_home']			= $val['is_home'];
		$list[$key]['is_hot']			= $val['is_hot'];
		$list[$key]['is_recd']			= $val['is_recd'];
		$list[$key]['is_push']			= $val['is_push'];
		
		//取出当前分类名称
		$list[$key]['cat_name']			= get_field('ticket_category', 'to_', 'name', "id = '$val[cat_id]'");
		
		//定义价格
			
		if($val['discount_price'] == '0.00' || empty($val['discount_price'])){
			$list[$key]['price']		= $val['market_price'];
		}else{
			$list[$key]['price']		= $val['discount_price'];
		}
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']	= '';
		}else{
			$list[$key]['create_user']	= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']	.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/ToTicket/ticket_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/ToTicket/ticket_del/id/".$val['id']."'>删除</a>";
		
	}
	
	
	//echo $db->getlastsql();
	//exit;
	return $list;
}

/*门票详情
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function ticket_info($id, $where){
	
	$db						= M("ticket", "to_");
	
	$where					= "is_del != 0 and id = '$id'".$where;
	
	$res					= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	//处理价格
	
	$res['price']			= json_decode($res['price'], true);
	
	
	return $res;
}


/**
 * 商户列表输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function tenant_list($where, $order){
	
	$where								= "is_del != 0 ".$where;
	$db    								= M('tenant', 'to_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']     	 		= $val['name'];
		$list[$key]['thumb']				= $val['thumb'];
		$list[$key]['tel']					= $val['tel'];
		$list[$key]['keywords']				= $val['keywords'];
		$list[$key]['order']				= $val['order'];
		
		$list[$key]['is_home']				= $val['is_home'];
		$list[$key]['is_hot']				= $val['is_hot'];
		$list[$key]['is_recd']				= $val['is_recd'];
		$list[$key]['is_push']				= $val['is_push'];
		$list[$key]['is_wx']				= $val['is_wx'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/ToTenant/tenant_edit/id/".$val['id']."'>修改</a>";
		
		//处理类型
		if($val['type'] == '1'){
			$list[$key]['type_name']		= "酒店";
			$list[$key]['operation']		.= " | <a href='/admin/Room/room_list/hotel_id/".$val['id']."'>管理房间</a>";
		}elseif($val['type'] == '2'){
			$list[$key]['type_name']		= "餐厅";
			$list[$key]['operation']		.= " | <a href='/admin/Dish/dish_list/rett_id/".$val['id']."'>管理菜品</a>";
		}elseif($val['type'] == '3'){
			$list[$key]['type_name']		= "商店";
			$list[$key]['operation']		.= " | <a href='/admin/Goods/goods_list/shop_id/".$val['id']."'>管理商品</a>";
		}
		$list[$key]['operation']			.= " | <a href='/admin/ToTenant/tenant_del/id/".$val['id']."'>删除</a>";
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 商户分类输出方法
 * @param (int)$area  景区ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function tenant_type_list($tenant, $where){
	
	$where								= "tenant = '$tenant' ".$where;
	$db    								= M('tenant_type', 'to_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']     	 		= $val['name'];
		$list[$key]['order']     	 		= $val['order'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/ToTenant/tenant_type_edit/id/".$val['id']."'>修改</a>";
		
		$list[$key]['operation']			.= " | <a href='/admin/ToTenant/tenant_type_del/id/".$val['id']."'>删除</a>";
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}



/**
 * 获取商户设置信息
 * @param int $id     礼品ID
 * @return array
 */
function tenant_info($id){
	$db    = M('tenant', 'to_');
	
	$where = "id = '$id' and is_del != 0";
	
	$res   = $db->where($where)->find();
	
	//echo $db->getlastsql();
	
	
	return $res;
}



/**
 * 酒店列表输出方法
 * @param (int)$hotel_id  酒店ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function room_list($hotel_id, $where){
	
	$where								= "is_del != 0 and tenant='$hotel_id'";
	$db    								= M('room', 'to_tenant_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']     	 		= $val['name'];
		$list[$key]['type']					= $val['type'];
		$list[$key]['thumb']				= $val['thumb'];
		$list[$key]['price']				= $val['price'];
		$list[$key]['keywords']				= $val['keywords'];
		$list[$key]['order']				= $val['order'];
		
		$list[$key]['is_home']				= $val['is_home'];
		$list[$key]['is_hot']				= $val['is_hot'];
		$list[$key]['is_recd']				= $val['is_recd'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//处理操作
		$list[$key]['operation']			= "<a href='/admin/Room/room_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Room/room_del/id/".$val['id']."'>删除</a>";
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 餐馆列表输出方法
 * @param (int)$hotel_id  酒店ID
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function dish_list($rett_id, $where){
	
	$where								= "is_del != 0 and tenant='$rett_id'";
	$db    								= M('dish', 'to_tenant_');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 			= $val['id'];
		$list[$key]['name']     	 		= $val['name'];
		$list[$key]['type']					= $val['type'];
		$list[$key]['thumb']				= $val['thumb'];
		$list[$key]['price']				= $val['price'];
		$list[$key]['keywords']				= $val['keywords'];
		$list[$key]['order']				= $val['order'];
		
		$list[$key]['is_home']				= $val['is_home'];
		$list[$key]['is_hot']				= $val['is_hot'];
		$list[$key]['is_recd']				= $val['is_recd'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		//处理操作
		$list[$key]['operation']			= "<a href='/admin/Dish/dish_edit/id/".$val['id']."'>编辑</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Dish/dish_del/id/".$val['id']."'>删除</a>";
		
	}
	
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 网站电话列表
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function tel_list($where){
	
	$where								= "is_del != 0 ".$where;
	$db    								= D('tel');
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    	 		= $val['name'];
		$list[$key]['tel']     	 		= $val['tel'];
		$list[$key]['order']     	 	= $val['order'];
		
		$list[$key]['is_home']			= $val['is_home'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		
		$list[$key]['operation']			= "<a href='/admin/Tel/tel_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Tel/tel_del/id/".$val['id']."'>删除</a>";
		
	}
	
	//echo $db->getlastsql();
	
	return $list;
}

/**
 * 友情链接列表输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function friendlink_list($where){
	
	$db    								= M('friendlink', 'mp_');
	$where								= " is_del != 0  ".$where;
	$order								= " `order` desc ";
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['name']    			= $val['name'];
		$list[$key]['link']				= $val['link'];
		$list[$key]['thumb']			= $val['thumb'];
		$list[$key]['order']			= $val['order'];
		
		if(empty($val['create_time'])){
			$list[$key]['create_user']		= '';
		}else{
			$list[$key]['create_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['create_id']);
			$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if(empty($val['up_time'])){
			$list[$key]['up_user']			= '';
		}else{
			$list[$key]['up_user']			= get_field("admin", "mp_", "realname", "userid = ".$val['up_id']);
			$list[$key]['up_user']			.= "<br>".date('Y-m-d h:i:s', $val['up_time']);
		}
		$list[$key]['operation']			= "<a href='/admin/friendlink/friendlink_edit/id/".$val['id']."'>修改</a>";
		$list[$key]['operation']			.= " | <a href='/admin/friendlink/friendlink_del/id/".$val['id']."'>删除</a>";
		
	}
	
	//echo $db->getlastsql();
	return $list;
}

/**
 * 评论列表输出方法
 * @param (string)$where 条件
 * @param (string)$order  排序
 * return 返回：成功返回新闻列表，不成功返回false 
 *
*/

function comment_list($where){
	
	$db    								= D('comment');
	$where								= " is_del != 0  ".$where;
	$order								= " `examine_time` desc ";
	$res								= $db->where($where)->order($order)->select();

	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['reply_id']    		= $val['reply_id'];
		$list[$key]['model']			= $val['model'];
		$list[$key]['content']			= $val['content'];
		
		$list[$key]['create_user']		= $val['user_name'];
		$list[$key]['create_user']		.= "<br>".date('Y-m-d h:i:s', $val['create_time']);
		
		
		if($val['is_examine'] == '0'){
			$list[$key]['examine_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['ecamine_id']);;
			$list[$key]['examine_user']		.= "<br>".date('Y-m-d h:i:s', $val['examine_time']);
		}else{
			$list[$key]['examine_user']		= '';
		}
		
		$list[$key]['operation']			= "<a href='/admin/Comment/comment_info/id/".$val['id']."'>查看</a>";
		$list[$key]['operation']			.= " | <a href='/admin/Comment/comment_del/id/".$val['id']."/is_examine/".$_GET['is_examine']."'>删除</a>";
		
	}
	
	//echo $db->getlastsql();
	//print_r($list);
	//exit;
	return $list;
}

/*评论详细信息
 * @param (int)$id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function comment_info($id){
	$db				= D("comment");
	
	$where			= "id='$id'";
	
	$res			= $db->where($where)->find();
	$res['times']	= date('Y-m-d h:i:s', $res['create_time']);
	
	if($res['is_examine'] == '0'){
		$res['examine_user']		= get_field("admin", "mp_", "realname", "userid = ".$val['ecamine_id']);;
		$res['examine_user']		.= "<br>".date('Y-m-d h:i:s', $val['examine_time']);
	}
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}


/*门票详情
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function tel_info($id, $where){
	
	$db						= D("tel");
	
	$where					= "is_del != 0 and id = '$id'".$where;
	
	$res					= $db->where($where)->find();
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/**
 * 更新后台缓存
 */
function public_clearCatche(){
	$list = dict('', 'Cache');
	if(is_array($list) && !empty($list)){
		foreach ($list as $modelName=>$funcName){
			D($modelName)->$funcName();
		}
	}
}


/** * 
 * @desc   导出订单方法
 * @param  (String)$expTitle 文件名称
 * @param  (String)$expCellName 文件名称
 * @param  (String)$expTableData 数据
 * @return bool
 */
function order_expUser($type_name, $where){//导出Excel
	$xlsName  = $type_name."_".date('Y-m-d H:i:s', time());
	$xlsCell  = array(
		array('order_id','订单号'),
		array('model_name','订单类型'),
		array('user_name','购买人'),
		array('address','收货人'),
		array('goods','商品'),
		array('paytype_name','付款方式'),
		array('actual_payment','订单金额'),
		array('status_name','订单状态'),
		array('pay_name','支付状态'),
		array('exp_info','物流状态'),
		array('create_time','下单时间'),
		array('pay_time','支付时间'),
		array('exp_time','发货时间'),
		array('complete_time','收货时间'),
	);
	$xlsData  = order_list($where);
	exportExcel($xlsName,$xlsCell,$xlsData);
	
}


/** * 
 * @desc   用户提现记录
 * @param  (Int)$userid 查询条件
 * @param  (Strint)$wehre 查询条件
 * @return bool
 */
function withdrawals_record($where, $member_id){
	
	$db									= D('member_withdrawals');
	$member_db							= D('member');
	$order								= " `create_time` desc ";
	
	if(empty($member_id)){
		$where							= "";
	}else{
		$where							= "user_id = $member_id";
	}
	$res								= $db->where($where)->order($order)->select();
	//echo $db->getlastsql();
	//exit;
	
	foreach($res as $key => $val){
		
		$list[$key]['id']     	 		= $val['id'];
		$list[$key]['user_id']     	 	= $val['user_id'];
		$list[$key]['wamount']     	 	= $val['wamount'];
		$list[$key]['wamount_sn']     	= $val['wamount_sn'];
		
		//申请者信息
		$userid							= $val['user_id'];
		$member_where					= array('id'=>$userid);
		$user_res						= $member_db->where($member_where)->field('nick, head_pic')->find();
		
		$list[$key]['nick']				= $user_res['nick'];
		$list[$key]['head_pic']			= $user_res['head_pic'];
		if($val['create_time'] == 0){
			$list[$key]['create_time']	= "数据异常";
		}else{
			$list[$key]['create_time']	= date('Y-m-d h:i:s', $val['create_time']);
		}
		
		if($val['audit_time'] == 0){
			$list[$key]['audit_time']	= "无";
		}else{
			$list[$key]['audit_time']	= date('Y-m-d h:i:s', $val['audit_time']);
		}
		
		if($val['account_time'] == 0){
			$list[$key]['account_time']	= "无";
		}else{
			$list[$key]['account_time']	= date('Y-m-d h:i:s', $val['account_time']);
		}
		
		
		if($val['status'] == '0'){
			$list[$key]['status_name']	= "异常";
		}elseif($val['status'] == '1'){
			$list[$key]['status_name']	= "待审核";
		}elseif($val['status'] == '2'){
			$list[$key]['status_name']	= "已通过";
		}elseif($val['status'] == '3'){
			$list[$key]['status_name']	= "已到账";
		}elseif($val['status'] == '4'){
			$list[$key]['status_name']	= "驳回";
		}
		
		//定义操作
		$list[$key]['operation']		= "<a class='btn btn-warning' href='/admin/Withdrawals/withdrawals_info/id/".$val['id']."'>操作</a>";
		
	}
	
	return $list;
	
}

/*获取内容信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function withdrawals_info($id){
	$db						= D('member_withdrawals');
	$member_db				= D('member');
	$mact_db				= D('member_account');
	
	//获取用户
	$where					= array('id'=>$id);
	$res					= $db->where($where)->find();
	
	if($res['status'] == '0'){
		$res['status_name']		= "异常";
	}elseif($res['status'] == '1'){
		$res['status_name']		= "待审核";
	}elseif($res['status'] == '2'){
		$res['status_name']		= "已通过";
	}elseif($res['status'] == '3'){
		$res['status_name']		= "已打款";
	}elseif($res['status'] == '4'){
		$res['status_name']		= "驳回";
	}elseif($res['status'] == '5'){
		$res['status_name']		= "已到账";
	}
	
	$userid					= $res['user_id'];
	$member_where			= array('id'=>$userid);
	$user_res				= $member_db->where($member_where)->field('nick, head_pic')->find();
	
	$res['nick']			= $user_res['nick'];
	$res['head_pic']		= $user_res['head_pic'];
	
	$meact_where			= array('user_id'=>$userid);
	$user_res				= $mact_db->where($meact_where)->field('user_signature, user_bank, user_card_number, user_tel')->find();
	
	$res['user_signature']	= $user_res['user_signature'];
	$res['user_bank']		= $user_res['user_bank'];
	$res['user_card_number']= $user_res['user_card_number'];
	$res['user_tel']		= $user_res['user_tel'];
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}


/*获取内容信息
 * @param (char)$table 表名
 * @param (int)$info_id 内容ID
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function member_info($id){
	$db						= D('member');
	$ml_db					= D('member_level');
	$al_db					= D('agency_level');
	$mact_db				= D('member_account');
	
	//获取用户
	$where					= array('id'=>$id);
	$res					= $db->where($where)->find();
	
	if($res['is_del'] == '0'){
		$res['status_name']		= "已删除";
	}elseif($res['is_del'] == '1'){
		$res['status_name']		= "正常";
	}elseif($res['is_del'] == '2'){
		$res['status_name']		= "已锁定";
	}
	
	$meact_where			= array('user_id'=>$id);
	$user_res				= $mact_db->where($meact_where)->field('user_signature, user_bank, user_card_number, user_tel')->find();
	
	$ml_where				= array('id'=>$res['level']);
	$res['ml_name']			= $ml_db->where($ml_where)->getField('name');
	
	$al_where				= array('id'=>$res['agencylevel']);
	$res['al_name']			= $al_db->where($al_where)->getField('name');
	
	
	$res['user_signature']	= $user_res['user_signature'];
	$res['user_bank']		= $user_res['user_bank'];
	$res['user_card_number']= $user_res['user_card_number'];
	$res['user_tel']		= $user_res['user_tel'];
	$res['create_time']		= date('Y-m-d H:i:s', $res['create_time']);
	
	//统计下线数量
	
	$res['dcount']			= $db->where(array('recommenders'=>$id))->count();
	
	//分销商
	
	if($res['agency'] == 0){
		$res['agency_name']= "无";
	}else{
		$res['agency_name']= $db->where(array('id'=>$res['agency']))->getField('nick');
	}
	
	//echo $db->getlastsql();
	//exit;
	return $res;
}

/*晒单数据
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function sun_order($where){
	$sun_db												= D('sun');
	$member_db											= D('member');
	$actr_db											= D('activite_record');
	$goods_db											= D('goods');
	$sun_comment_db										= D('sun_comment');
	
	$sun_res											= $sun_db->where($where)->order('create_time desc')->select();
	
	foreach($sun_res as $sun_key => $sun_val){
		$list[$sun_key]['id']							= $sun_val['id'];
		$list[$sun_key]['title']						= $sun_val['title']; 	
		$list[$sun_key]['content']						= $sun_val['content']; 	
		$list[$sun_key]['sun_time']						= date('Y-m-d H:i:s', $sun_val['create_time']);
		
		//处理晒单图片
		$sun_pic										= $sun_val['pic'];
		$sun_pic										= json_decode($sun_pic, true);
		foreach($sun_pic as $pic_key => $pic_val){
			$list[$sun_key]['sun_pic'][$pic_key]['pic']	= $pic_val['pic'];
		}
		
		$sun_userid										= $sun_val['user_id'];
		$sun_actrid										= $sun_val['actr_id'];
		
		//取会员信息
		$member_res										= $member_db->where(array('id'=>$sun_userid))->Field('nick,head_pic')->find();
		$list[$sun_key]['user_nick']					= $member_res['nick'];
		$list[$sun_key]['user_head_pic']				= $member_res['head_pic'];
		
		//取商品信息
		$actr_res										= $actr_db->where(array('id'=>$sun_actrid))->Field('goods_id, sign_time,receive_time')->find(); 
		$goods_res										= $goods_db->where(array('id'=>$actr_res['goods_id']))->Field('name,thumb')->find();
		$list[$sun_key]['sign_time']					= date('Y-m-d H:i:s', $actr_res['sign_time']);
		$list[$sun_key]['receive_time']					= date('Y-m-d H:i:s', $actr_res['receive_time']);
		$list[$sun_key]['goods_name']					= $goods_res['name'];
		$list[$sun_key]['goods_thumb']					= $goods_res['thumb'];
		
		//获取评论信息
		$comment										= $sun_comment_db->where(array('sun_id'=>$sun_val['id']))->count(); 
		$list[$sun_key]['comment']						= '<a href="/Admin/Tools/sun_comment/sun_id/'.$sun_val['id'].'">'.$comment.'</a>';
		
	}
	
	return $list;
	
}

/*晒单数据
 * @param (string)$where 条件
 * return 返回：成功返回新闻列表，不成功返回fals
*/
function sun_comment_list($where){
	$db													= D('sun_comment');
	$member_db											= D('member');
	$where												= "status = 1".$where;
	$res												= $db->where($where)->order('id desc')->select();
	
	foreach($res as $key => $val){
		$list[$key]['id']								= $val['id'];
		$user_id										= $val['user_id']; 	
		$list[$key]['sun_id']							= $val['sun_id']; 	
		$list[$key]['content']							= $val['content']; 
		$list[$key]['time']								= date('Y-m-d H:i:s', $val['time']);
		
		//取会员信息
		$member_res										= $member_db->where(array('id'=>$user_id))->Field('nick,head_pic')->find();
		$list[$key]['user_nick']						= $member_res['nick'];
		$list[$key]['user_head_pic']					= $member_res['head_pic'];
		
	}
	
	return $list;
	
}
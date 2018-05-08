
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
header('Content-type: application/json');
class Api extends CI_Controller {

	/**
	 * 
	 *
	 */
	function  __construct(){
		parent::__construct();
		$this->load->database();

	
	}
	public function index()
	{
		$this->load->view('home');
	}

	public function aso_cesi(){
		echo 11111;
	}
	
	//点击接口
	public function aso_source(){
		$rearr = $_GET;
		$cpid  = $this->uri->segment(4,0);//此cpid为渠道商和我们之间的唯一值
		//判断CP 渠道是否存在
		$sql   = "select * from aso_source_cpid where cpid=$cpid";
		
		$res   = $this->db->query($sql);
		$cp_res= $res->row_array();
		if(empty($cp_res)){
			echo json_encode(array('code'=>99,'message'=>'未知渠道或CP'));die;
		}
		$rearr['cpid']  = $cpid;
		//判断广告是否存在或下线
		$adid  = intval($rearr['adid']);//获取广告id
		$appid = intval($rearr['appid']);//获取appid
		$sql   = "select * from aso_advert where is_disable='0' and appid =$appid and cpid=$adid";
		
		$res   = $this->db->query($sql);
		$list  = $res->row_array();
		if(empty($list)){
			echo json_encode(array('code'=>101,'message'=>'广告未存在或已下线'));die;
		}

		//判断是否是https请求
		$noUrl  = $this->curPageURL();
		if(strstr($list['source_url'],"https://")){
			$reUrl   = str_replace('asoapi.appubang.com/api','47.93.125.108/ce',$noUrl);

			echo $this->request_get($reUrl);die;
		}
		//判断是否是非法ip访问
		// $ServerIps   = explode(',',$cp_res['ip']);
		// if(!in_array($_SERVER['SERVER_ADDR'],$ServerIps)){
			
		// 	echo json_encode(array('code'=>104,'message'=>'illegal access'));die;
		// }
		//判断指定参数是否存在
		if(!isset($rearr['ip'])){
			echo json_encode(array('code'=>102,'message'=>'ip noisset'));die;
		}

		if(!isset($rearr['idfa'])){
			echo json_encode(array('code'=>103,'message'=>'idfa noisset'));die;
		}
		if(!isset($rearr['timestamp'])) $rearr['timestamp']=time();
		if(!isset($rearr['reqtype'])) $rearr['reqtype']=1;
		if(!isset($rearr['device'])){
			$rearr['device']='iphone';
		}else{ 
			$rearr['device']=str_replace(' ','',$rearr['device']);
		}//将设备空格去掉;
		if(!isset($rearr['os'])) $rearr['os']='未知';
		if(!isset($rearr['isbreak'])) $rearr['isbreak']=0;
		if(!isset($rearr['keywords'])) $rearr['keywords']='未知';
		if(!isset($rearr['sign'])) $rearr['sign']='';

		//判断签名
		if($cp_res['key'] !=''){//key不为空的就是带sign,为空的就是渠道不支持sign验证
			if(md5($rearr['timestamp'].$cp_res['key']) != $rearr['sign']){
				echo  json_encode(array('code'=>'104','result'=>'sign error'));die;
			}
		}

		if($rearr['adid']==1){
			$sql = "select count(*) as s from aso_submit where appid = ".$rearr['appid']." and idfa='".$rearr['idfa']."'";
			$res = $this->db->query($sql);
			$result = $res->row_array();

			if(empty($result['s'])){
				$a = array('code'=>'0','result'=>'ok');
				$file_contents = json_encode($a);
				$id = $this->db->insert('aso_source', $rearr); 
				$inid = $this->db->insert_id();
				echo  $file_contents;
				mysql_close();
				die;
			}else{
				$file_contents = array('code'=>'102','result'=>'idfa repeat');
				$data['json'] =json_encode($file_contents);
				$id = $this->db->insert('aso_source_log', $rearr); 
				echo  json_encode($file_contents);
				mysql_close();
				//如果我们与渠道商之间的idfa是重复的就错误返回信息
				die;
			}
		}else{
			if($list['api_cat']==1){
				$s ='source_'.$rearr['adid'];
			
				$this->$s($rearr,$list);
				mysql_close();die;
			}else{
				//获取接口请求方式
				$request_method  = explode('%',$list['source_value'])[2];
				//获取接口响应key值
				$key_value       = explode('%',$list['source_value'])[0];
				//获取接口请求成功响应值
				$TrueValue       = explode('%',$list['source_value'])[1];
				//获取关键词加密方式
				$ktype           = explode('%',$list['source_value'])[3];
				//取出CP点击接口
				$source_url      = $list['source_url'];
				//回调接口
				
			    $callback        = urlencode("http://asoapi.appubang.com/api/aso_advert/?k=".$rearr['timestamp']."&idfa=".$rearr['idfa']."&appid=".$rearr['appid']."&sign=".md5($rearr['timestamp'].md5('callback')));
			    $source_url      = str_replace('{1}',$rearr['appid'],$source_url);
				$source_url      = str_replace('{2}',$rearr['idfa'],$source_url);
				$source_url      = str_replace('{3}',$rearr['ip'],$source_url);
				$source_url      = str_replace('{4}',$rearr['device'],$source_url);
				$source_url      = str_replace('{5}',$ktype==0?$rearr['keywords']:urlencode($rearr['keywords']),$source_url);
				$source_url      = str_replace('{6}',$rearr['os'],$source_url);
				$source_url      = str_replace('{7}',$callback,$source_url);
				$source_url      = str_replace('{8}',md5($rearr['timestamp'].$list['key']),$source_url);
				$source_url      = str_replace('{9}',$rearr['timestamp'],$source_url);
				$keys            = str_replace('idfa',$rearr['idfa'],$key_value);
				$keys            = explode('.',$keys);
				$KCount          = count(explode('.',$key_value));
				if($request_method==1){
					$file_contents = $this->request_get($source_url);

			   		 $json          = json_decode($file_contents,true);
			   
				}else{
					
					 $sourceUrl    = explode('?',$source_url)[0];
					 $sourceParams = explode('&',explode('?',$source_url)[1]);

					 foreach($sourceParams as $k=>$val){
					 	$data[explode('=',$val)[0]] = explode('=',$val)[1];
					 }
					
					$file_contents  = $this->request_post($sourceUrl,$data);
					
					
					$json          = json_decode($file_contents,true);
					
				}
				
				if($KCount==1){
			    	if($json[$keys[0]]==$TrueValue){
			    		$this->db->insert('aso_source', $rearr); 
						echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			    		
			    	}else{

			    		$rearr['json'] =$file_contents;
			    		
						$this->db->insert('aso_source_log', $rearr); 
						echo  json_encode(array('code'=>'103','result'=>'false'));die; 
			    	}
			    }else if($KCount==2){
			    	if($json[$keys[0]][$keys[1]]==$TrueValue){
			    		$this->db->insert('aso_source', $rearr); 
						echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			    	}else{
			    		$rearr['json'] =$file_contents;
			    		
						$this->db->insert('aso_source_log', $rearr); 
						echo  json_encode(array('code'=>'103','result'=>'false'));die;
			    	}
			    }else{
			    	if($json[$keys[0]][$keys[1]][$keys[2]]==$TrueValue){
			    		$this->db->insert('aso_source', $rearr); 
						echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			    	}else{
			    		$rearr['json'] =$file_contents;
			    		
						$this->db->insert('aso_source_log', $rearr); 
						echo  json_encode(array('code'=>'103','result'=>'false'));die;
			    	}
			    }
			}
			
		}
	}
	//回调
	public function aso_advert(){
		if($this->isMobile()){
			$_GET['is_mobile']=1;
		}else{
			$_GET['is_mobile']=0;
		}
		$callarr = $_GET;
		//记录到回调日志
		
		$data_al['appid'] = $callarr['appid'];
		$data_al['idfa']  = $callarr['idfa'];
		$data_al['timestamp']    = time();

		if(isset($callarr['sign'])){
			if($callarr['sign'] != md5($callarr['k'].md5('callback'))){
				$data_al['error']  = '{"resultCode":-1,"errorMsg":"sign error"} ';
				$this->db->insert('aso_advert_log', $data_al);
				echo '{"resultCode":-1,"errorMsg":"sign error"} ';die;
			}else{
				$this->db->insert('aso_advert_log', $data_al);
			}
		}
		

		//$sql = "select * from aso_source where id=".$callarr['back_id']." and appid=".$callarr['appid']." and idfa='".$callarr['idfa']."'";//查找渠道的回调地址
		$sql = "select * from aso_source where appid=".$callarr['appid']." and idfa='".$callarr['idfa']."'" . " ORDER BY id DESC LIMIT 1";//
		// echo $sql;die;
		$res = $this->db->query($sql);
		$list = $res->row_array();
		if(empty($list)){
			echo '{"resultCode":-1,"errorMsg":"callback noExist"} ';die;
		}

		$url = $list['callback'];
		$pos = strrpos($list['callback'], "imoney.one");
		if($pos !== 0) {
			$url = str_replace("imoney.one", "eimoney.com", $list['callback']);
		}
		
		// $callback = urldecode(trim($list['callback']));
		$file_contents = $this->request_get($url);
		// $post_data=array(1=>'1');
        // $file_contents = $this->request_post($callback, $post_data);  
       	// $json = json_decode($file_contents,true );
		
		// $file_contents = file_get_contents($callback);
		$json = json_decode($file_contents,true );
		$result = array('cpid'=>$list['cpid'],'appid'=>$callarr['appid'],'idfa'=>$callarr['idfa'],'timestamp'=>time(),'type'=>2,'is_mobile'=>$callarr['is_mobile']);
		//给你花特殊处理
		if($callarr['appid']==1076275012 && $list['adid']==10042){
			if($list['cpid']!=207){
				$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));

				mysql_close();
				die;
			}
		}
		// 特殊处理
		if($callarr['appid'] == 1051607862) {
			if($json['success']){//回调值成功，完成任务
				$this->db->insert('aso_submit2',$result);
				echo json_encode(array('status'=>1));

				mysql_close();
				die;
			}else{
				$result['json']=$file_contents;
				$this->db->insert('aso_submit_log',$result);
				mysql_close();
				echo  $file_contents;die;
			}
		}
		//创客 回调相应
		if($callarr['appid'] == 1053098785) {
			if($json['success']){//回调值成功，完成任务
				$this->db->insert('aso_submit2',$result);
				echo json_encode(array('errno'=>1,'msg'=>'success'));

				mysql_close();
				die;
			}else{
				$result['json']=$file_contents;
				$this->db->insert('aso_submit_log',$result);
				mysql_close();
				echo json_encode(array('errno'=>0,'msg'=>'false'));
				die;
			}
		}
		if($list['cpid']==217){
			if(!$json['c']){//回调值成功，完成任务
				$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));
				mysql_close();
				die;
			}else{
				$result['json']=$file_contents;
				$this->db->insert('aso_submit_log',$result);
				mysql_close();
				echo  json_encode(array('success'=>false,'message'=>'error'));die;
			}
		}
		if($list['cpid']==512){
			if($json['msg'] == "success"){//回调值成功，完成任务
				$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));
				mysql_close();
				die;
			}else{
				$result['json']=$file_contents;
				$this->db->insert('aso_submit_log',$result);
				mysql_close();
				echo  json_encode(array('success'=>false,'message'=>'error'));die;
			}
		}
		if($list['cpid']==303){
			if($json['status'] == 1){//回调值成功，完成任务
				$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));
				mysql_close();
				die;
			}else{
				$result['json']=$file_contents;
				$this->db->insert('aso_submit_log',$result);
				mysql_close();
				echo  json_encode(array('success'=>false,'message'=>'error'));die;
			}
		}
		if($list['cpid']==909){
			$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));
				mysql_close();
				die;
		}
		if($list['cpid']==7166){
			$this->db->insert('aso_submit2',$result);
				echo  json_encode(array('success'=>true,'message'=>'ok'));
				mysql_close();
				die;
		}
		if($list['cpid']==406 && !$json['c']){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}
		if($list['cpid']==465 && $file_contents == "ok"){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}
		if($list['cpid']==963 && $json['statusCode'] == 200){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}
		if($list['cpid']==517 && $json['code'] == 0){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}
		if($list['cpid']==1356 && $json['status'] == 1){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if($list['cpid']==10013 && $json['status'] == "success"){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if($list['cpid']==10014 && $json['code'] == 0){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if($list['cpid']==10018 && $json['code'] == 200){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if($list['cpid']==333 && $json['code'] == 1){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if($list['cpid']==10016 && $json['statusCode'] == 200){
			$this->db->insert('aso_submit2',$result);
			echo  json_encode(array('success'=>true,'message'=>'ok'));
			mysql_close();
			die;
		}

		if(isset($json['success']) && $json['success']){//回调值成功，完成任务
			$this->db->insert('aso_submit2',$result);
			echo $file_contents;

			mysql_close();
			die;
		}else{
			$result['json']=$file_contents;
			$this->db->insert('aso_submit_log',$result);
			mysql_close();
			echo  $file_contents;die;
		}

		// if( $json['errorMsg']='ok'){//回调值成功，修改任务表中的任务状态，完成任务
		// 	$this->db->insert('aso_submit',$result);
		// 	$result['json']=$file_contents;
		// 	$this->db->insert('aso_submit_log',$result);
		// 	echo $file_contents;
		// 	mysql_close();
		// 	die;
		// }else{
		// 	$result['json']=$file_contents;
		// 	$this->db->insert('aso_submit_log',$result);
		// 	mysql_close();
		// 	echo  $file_contents;die;
		// }
		
	}
	public function aso_IdfaRepeat(){
		$rearr = $_GET;
		$cpid  = $this->uri->segment(4,0);//此cpid为渠道商和我们之间的唯一值
		//判断CP 渠道是否存在
		$sql   = "select * from aso_source_cpid where cpid=$cpid";
		
		$res   = $this->db->query($sql);
		$cp_res= $res->row_array();
		if(empty($cp_res)){
			echo json_encode(array('code'=>99,'message'=>'未知渠道或CP'));die;
		}
		//判断广告是否存在或下线
		$adid  = intval($rearr['adid']);//获取广告id
		$appid = intval($rearr['appid']);//获取appid
		$sql   = "select * from aso_advert where is_disable='0' and appid =$appid and cpid=$adid";
		
		$res   = $this->db->query($sql);
		$list  = $res->row_array();
		if(empty($list)){
			echo json_encode(array('code'=>101,'message'=>'广告未存在或已下线'));die;
		}
		//判断是否是https请求
		$noUrl  = $this->curPageURL();
		if(strstr($list['IdfaRepeat_url'],"https://")){
			$reUrl   = str_replace('asoapi.appubang.com/api','47.95.28.151/ce',$noUrl);

			echo $this->request_get($reUrl);die;
		}
		//判断是否是非法ip访问
		// $ServerIps   = explode(',',$cp_res['ip']);
		// if(!in_array($_SERVER['SERVER_ADDR'],$ServerIps)){
			
		// 	echo json_encode(array('code'=>104,'message'=>'illegal access'));die;
		// }
		$rearr['cpid'] = $cpid;
		//判断指定参数是否存在
		if(!isset($rearr['ip'])){
			$rearr['ip']='';
		}

		if(!isset($rearr['idfa'])){
			echo json_encode(array('code'=>103,'message'=>'idfa noisset'));die;
		}
		if(!isset($rearr['timestamp'])) $rearr['timestamp']=time();
		if($rearr['adid']==1){
			if($rearr['appid']==480079300){
				$sql = "select count(*) as s from aso_submit2 where appid = ".$rearr['appid']." and idfa='".$rearr['idfa']."'";
			}else{
				$sql = "select count(*) as s from aso_submit where appid = ".$rearr['appid']." and idfa='".$rearr['idfa']."'";
			}
			
			$res = $this->db->query($sql);
			$result = $res->row_array();
			if(empty($result['s'])){
				// $a = array('code'=>'0','result'=>'ok');
				// $file_contents = json_encode($a);
				$a = array($rearr['idfa']=>'1');
				$file_contents = json_encode($a);
				$log=array('cpid'=>$rearr['cpid'],'appid'=>$rearr['appid'],'adid'=>$rearr['adid'],'idfa'=>$rearr['idfa'],'json'=>$file_contents ,'date'=>time());
				$this->db->insert('aso_IdfaRepeat_log',$log);
				// $id = $this->db->insert('aso_source', $data); 
				// $inid = $this->db->insert_id();
				// echo  $file_contents;
				
			}else{
				$file_get_contents = array($rearr['idfa']=>'0');
				$file_contents =json_encode($file_get_contents);
				$log=array('cpid'=>$rearr['cpid'],'appid'=>$rearr['appid'],'adid'=>$rearr['adid'],'idfa'=>$rearr['idfa'],'json'=>$file_contents ,'date'=>time());
				$this->db->insert('aso_IdfaRepeat_log',$log);
				// echo  $file_contents;
				//如果我们与渠道商之间的idfa是重复的就错误返回信息
				
			}
			// $log=array('cpid'=>$cpid,'appid'=>$rearr['appid'],'idfa'=>$rearr['idfa'],'json'=> $file_contents ,'date'=>time());
			// $this->db->insert('aso_IdfaRepeat_log',$log);
			echo  $file_contents;
			mysql_close();die;
		}else{
			if($list['api_cat']==1){
				$s ='IdfaRepeat_'.$rearr['adid'];
			
				$this->$s($rearr,$list);
				mysql_close();die;
			}else{
				//获取接口请求方式
				$request_method  = explode('%',$list['repeat_value'])[2];
				//获取接口响应key值
				$key_value       = explode('%',$list['repeat_value'])[0];
				//获取接口请求成功响应值
				$TrueValue       = explode('%',$list['repeat_value'])[1];
				//获取关键词加密方式
				$ktype           = explode('%',$list['repeat_value'])[3];
				//取出CP点击接口
				$IdfaRepeat_url    = $list['IdfaRepeat_url'];
				$IdfaRepeat_url    = str_replace('{1}',$rearr['appid'],$IdfaRepeat_url);
				$IdfaRepeat_url    = str_replace('{2}',$rearr['idfa'],$IdfaRepeat_url);
				$IdfaRepeat_url    = str_replace('{3}',$rearr['ip'],$IdfaRepeat_url);
				
				
				
				$keys              = str_replace('idfa',$rearr['idfa'],$key_value);
			    $keys              = explode('.',$keys);
			    $KCount            = count(explode('.',$key_value));
				if($request_method==1){
					$file_contents = $this->request_get($IdfaRepeat_url);

				    $json          = json_decode($file_contents,true);
				    
				}else{
					
					 $IdfaRepeatUrl    = explode('?',$IdfaRepeat_url)[0];
					 $IdfaRepeatParams = explode('&',explode('?',$IdfaRepeat_url)[1]);

					 foreach($IdfaRepeatParams as $k=>$val){
					 	$data[explode('=',$val)[0]] = explode('=',$val)[1];
					 }
					
					$file_contents  = $this->request_post($IdfaRepeatUrl,$data);

					$json          = json_decode($file_contents,true);
				}

				
				$log=array('cpid'=>$rearr['cpid'],'appid'=>$rearr['appid'],'adid'=>$rearr['adid'],'idfa'=>$rearr['idfa'],'json'=>$file_contents ,'date'=>time());
				$this->db->insert('aso_IdfaRepeat_log',$log);
			 	if($KCount==1){
			    	if($json[$keys[0]]==$TrueValue){
			    		echo json_encode(array($rearr['idfa']=>'1'));die;
			    	}else{
			    		echo json_encode(array($rearr['idfa']=>'0'));die;
			    	}
			    }else if($KCount==2){
			    	if($json[$keys[0]][$keys[1]]==$TrueValue){

			    		echo json_encode(array($rearr['idfa']=>'1'));die;
			    	}else{
			    		echo json_encode(array($rearr['idfa']=>'0'));die;
			    	}
			    }else{
			    	if($json[$keys[0]][$keys[1]][$keys[2]]==$TrueValue){
			    		echo json_encode(array($rearr['idfa']=>'1'));die;
			    	}else{
			    		echo json_encode(array($rearr['idfa']=>'0'));die;
			    	}
			    }
			}
			
		}
	}
	//做完任务上报接口
	public function aso_Submit(){
		$rearr = $_GET;
		$cpid  = $this->uri->segment(4,0);//此cpid为渠道商和我们之间的唯一值
		//判断CP 渠道是否存在
		$sql   = "select * from aso_source_cpid where cpid=$cpid";
		
		$res   = $this->db->query($sql);
		$cp_res= $res->row_array();
		if(empty($cp_res)){
			echo json_encode(array('code'=>99,'message'=>'未知渠道或CP'));die;
		}
		//判断广告是否存在或下线
		$adid  = intval($rearr['adid']);//获取广告id
		$appid = intval($rearr['appid']);//获取appid
		$sql   = "select * from aso_advert where is_disable='0' and appid =$appid and cpid=$adid";
		
		$res   = $this->db->query($sql);
		$list  = $res->row_array();
		if(empty($list)){
			echo json_encode(array('code'=>101,'message'=>'广告未存在或已下线'));die;
		}

		//判断是否是https请求
		$noUrl  = $this->curPageURL();
		if(strstr($list['submit_url'],"https://")){
			$reUrl   = str_replace('asoapi.appubang.com/api','47.95.28.151/ce',$noUrl);

			echo $this->request_get($reUrl);die;


		}
		//判断是否是非法ip访问
		// $ServerIps   = explode(',',$cp_res['ip']);
		// if(!in_array($_SERVER['SERVER_ADDR'],$ServerIps)){
			
		// 	echo json_encode(array('code'=>104,'message'=>'illegal access'));die;
		// }
		$rearr['cpid']  = $cpid;
		//判断指定参数是否存在

		if(!isset($rearr['idfa'])){
			echo json_encode(array('code'=>103,'message'=>'idfa noisset'));die;
		}

		if(!isset($rearr['timestamp'])) $rearr['timestamp']=time();
		if(!isset($rearr['reqtype'])) $rearr['reqtype']=1;
		if(!isset($rearr['device'])) $rearr['device']='iphone';
		if(!isset($rearr['os'])) $rearr['os']='未知';
		if(!isset($rearr['isbreak'])) $rearr['isbreak']=0;
		if(!isset($rearr['keywords'])) $rearr['keywords']='';
		if(!isset($rearr['sign'])) $rearr['sign']='';

		//判断签名
		if($cp_res['key'] !=''){//key不为空的就是带sign,为空的就是渠道不支持sign验证
			if(md5($rearr['timestamp'].$cp_res['key']) != $rearr['sign']){
				echo  json_encode(array('code'=>'104','result'=>'sign error'));die;
			}
		}
		if($rearr['adid']==1){
			$sql = "select cpid,idfa,appid from aso_source where cpid=".$cpid." and appid = ".$rearr['appid']." and idfa='".$rearr['idfa']."'";
			$res = $this->db->query($sql);
			$Soresult = $res->row_array();

			$sql = "select count(*) as s from aso_submit where appid = ".$rearr['appid']." and idfa='".$rearr['idfa']."'";
			$r = $this->db->query($sql);
			$Suresult = $r->row_array();
			unset($rearr['adid'],$rearr['sign'],$rearr['ip'],$rearr['reqtype'],$rearr['isbreak'],$rearr['device'],$rearr['os'],$rearr['callback']);
			if(!empty($Suresult['s'])){//已经做过任务或者已经提交

				$a = array('code'=>'103','result'=>'idfa Exist');
				$file_contents = json_encode($a);
				$rearr['json']=$file_contents;
				$this->db->insert('aso_submit_log',$rearr);
				echo  $file_contents;
				mysql_close();
				die;

			}
		//如果厂商的cpid为1，就是我们自己来做排重只对接渠道，不对接厂商，只做排重点击上报
			if(empty($Soresult)){//等于空就是没有做任务，返回错误值
				$a = array('code'=>'103','result'=>'idfa noExist');
				$file_contents = json_encode($a);
				$rearr['json']=$file_contents;
				$this->db->insert('aso_submit_log',$rearr);
				echo  $file_contents;
				mysql_close();
				die;

			}else{
				//写入submit
				$rearr['timestamp'] = time();
				$rearr['type'] = 1;
				if($rearr['appid']==480079300){
					$this->db->insert('aso_submit2',$rearr);
				}else{
					$this->db->insert('aso_submit',$rearr);
				}
				
				// $sql = "update aso_source set submit=1 where id =".$result['id'];
				// $this->db->query($sql);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				mysql_close();
				die;
				}
			
		}else{
			if($list['api_cat']==1){
				$s ='submit_'.$rearr['adid'];
				unset($rearr['adid']);
				$this->$s($rearr,$list);
				mysql_close();die;
			}else{
				//获取接口请求方式
				$request_method  = explode('%',$list['submit_value'])[2];
				//获取接口响应key值
				$key_value       = explode('%',$list['submit_value'])[0];
				//获取接口请求成功响应值
				$TrueValue       = explode('%',$list['submit_value'])[1];
				//获取关键词加密方式
				$ktype           = explode('%',$list['submit_value'])[3];
				//取出CP上报接口
				$submit_url    = $list['submit_url'];
				$submit_url    = str_replace('{1}',$rearr['appid'],$submit_url);
				$submit_url    = str_replace('{2}',$rearr['idfa'],$submit_url);
				$submit_url    = str_replace('{3}',$rearr['ip'],$submit_url);
				$submit_url    = str_replace('{4}',$rearr['device'],$submit_url);
				$submit_url    = str_replace('{5}',$ktype==0?$rearr['keywords']:urlencode($rearr['keywords']),$submit_url);
				$submit_url    = str_replace('{6}',$rearr['os'],$submit_url);
				$keys          = str_replace('idfa',$rearr['idfa'],$key_value);
				$keys          = explode('.',$keys);
				$KCount        = count(explode('.',$key_value));
				if($request_method==1){
					$file_contents = $this->request_get($submit_url);

				    $json          = json_decode($file_contents,true);
				}else{
					
					 $submitUrl    = explode('?',$submit_url)[0];
					 $submitParams = explode('&',explode('?',$submit_url)[1]);

					 foreach($submitParams as $k=>$val){
					 	$data[explode('=',$val)[0]] = explode('=',$val)[1];
					 }
					
					$file_contents  = $this->request_post($submitUrl,$data);

					$json          = json_decode($file_contents,true);
				}
				unset($rearr['adid'],$rearr['sign'],$rearr['ip'],$rearr['reqtype'],$rearr['isbreak'],$rearr['device'],$rearr['os'],$rearr['callback']);
				if($KCount==1){
			    	if($json[$keys[0]]==$TrueValue){
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$this->db->insert('aso_submit2',$rearr);
						echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			    		
			    	}else{
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$rearr['json'] =$file_contents;
						$this->db->insert('aso_submit_log',$rearr);
			    		echo  json_encode(array('code'=>'103','result'=>'false'));die;
			    	}
			    }else if($KCount==2){
			    	if($json[$keys[0]][$keys[1]]==$TrueValue){
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$this->db->insert('aso_submit2',$rearr);
						echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			    		
			    	}else{
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$rearr['json'] =$file_contents;
						$this->db->insert('aso_submit_log',$rearr);
			    		echo  json_encode(array('code'=>'103','result'=>'false'));die;
			    	}
			    }else{
			    	if($json[$keys[0]][$keys[1]][$keys[2]]==$TrueValue){
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$this->db->insert('aso_submit2',$rearr);
						echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			    	}else{
			    		$rearr['timestamp']=time();
						$rearr['type'] = 1;
						$rearr['json'] =$file_contents;
						$this->db->insert('aso_submit_log',$rearr);
			    		echo  json_encode(array('code'=>'103','result'=>'false'));die;
			    	}
			    }
			}
			
		}
	}
	/**************************************** 接口开始**********************************************/
	//钱咖渠道点击接口
	public function aso_qkClick(){

		if($_SERVER['REQUEST_METHOD']=='GET'){
			echo json_encode(array('code'=>99,'message'=>'request method error'));die;
		}
		if(!isset($_POST['idfa']) || empty($_POST['idfa'])){
			echo json_encode(array('code'=>99,'message'=>'idfa error'));die;
		}

		$data   = $_POST;
		
		$url    = 'http://asoapi.appubang.com/api/aso_source/cpid/622/?appid='.$data['appid'].'&idfa='.$data['idfa'].'&ip='.$data['ip'].'&adid=1';
		
		
		$info   = $this->request_get($url);
		echo $info;
		
		
	}
	//钱咖渠道排重接口
	public function aso_qkIdfaRepeat(){

		if($_SERVER['REQUEST_METHOD']=='GET'){
			echo json_encode(array('code'=>99,'message'=>'request method error'));die;
		}
		if(!isset($_POST['idfa']) || empty($_POST['idfa'])){
			echo json_encode(array('code'=>99,'message'=>'idfa error'));die;
		}
		
		$data          = $_POST;
		
		
		$idfas         = explode(',',$data['idfa']);
		$ReturnIdfa    = array();
		foreach($idfas as $k=>$val){
			

			$info   = $this->request_get("http://asoapi.appubang.com/api/aso_IdfaRepeat/cpid/622?adid=1&appid=".$data['appid'].'&idfa='.$val);
		
			$json   = json_decode($info,true);
			
			if($json[$val]=='1'){
				//echo  json_encode(array($data['idfa']=>'0'));
				$ReturnIdfa[$val]=0;
			}else{
				$ReturnIdfa[$val]=1;
			}

		}
		
		echo json_encode($ReturnIdfa);
		
		
	
	}
	//趣米点击排重
	function source_5($data,$list){
		$file_contents = file_get_contents($list['source_url']."&idfa=".$data['idfa']."&clientip=".$data['ip']);
		$json = json_decode($file_contents,true );
		
		// echo $file_contents;
		if($json[$data['idfa']]==0 && isset($json[$data['idfa']])){
			$this->db->insert('aso_source',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;//这里返回1代表成功
		}else{
			
			
			$data['json']= $file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));
		}	
	}
	//趣米上报
	function submit_5($data,$list){
		if(!isset($data['ip'])){
			echo  json_encode(array('error'=>'ip error'));die;
		}
		$file_contents = file_get_contents($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		// echo $file_contents;
		if($json['code']==0){//上报成功
				// unset($data['sign']);
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				// unset($data['sign']);
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}
	
	//懒猫分包排重
	function IdfaRepeat_3($data,$list){
		$file_contents = file_get_contents($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true );
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		unset($data['sign']);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}
	}
	//懒猫分包上报
	function submit_3($data,$list){
		$file_contents = file_get_contents($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true );
		
		// unset($data['sign']);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status']==1){//上报成功
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	} 
	//懒猫分包点击
	function source_3($data,$list){
		$file_contents = file_get_contents($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&os=".$data['os']);
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//上报成功
				$this->db->insert('aso_source',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败
				$data['json'] =  $file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}	
	}
	//应用雷达排重
	function IdfaRepeat_4($data,$list){
		$url = $list['IdfaRepeat_url'];
        $post_data['appid']       = $data['appid'];
        $post_data['idfa']      = $data['idfa'];
        
        $file_contents = $this->request_post($url, $post_data);  
       	$json = json_decode($file_contents,true );
		// echo $file_contents;die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		unset($data['sign']);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//应用雷达NOW直播注册回调
	function source_57($data,$list){
		$this->source_4($data,$list);
	}
	//应用雷达NOW直播注册回调排重
	function IdfaRepeat_57($data,$list){
		$this->IdfaRepeat_4($data,$list);
	}
	//应用雷达点击请求
	function source_4($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		// echo $callback;die;
		$url = $list['source_url'] ."udid=".$data['idfa'].'&appid='.$data['appid'].'&returnFormat=null&multipleurl='.$callback;
		// $file_contents = file_get_contents("http://integralwall.ann9.com/Interface/ServiceiMoney.ashx?"."&udid=".$data['idfa'].'&appid='.$data['appid'].'&returnFormat=null&multipleurl='.$callback);
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		if($data['appid']==550926736){
			if(isset($json['code'])){//点击成功
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		}
		if($data['appid']==395096736){
			if($json['status']){//点击成功

				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		}
		if(isset($json['success']) && $json['success']){//点击成功

				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}	
	}

	//相册宝 排重
	function IdfaRepeat_55($data,$list){
		$param         = json_encode(array('appid'=>$data['appid'],'idfa'=>$data['idfa']));
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?param=".$param);
		// print_r($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}


	//京盟 点击
	function source_58($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		// $id = $this->db->insert('aso_source', $data);
		// $inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] ."&publicIp=".$data['ip']."&devicetype=".$data['device']."&osversion=".$data['os']."&clicktime=".$data['timestamp']."&callback=".$callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		//var_dump($json);
	if(isset($json["success"]) && $json["success"] == "true"){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//谢总理财分包 赚钱啦 排重
	function IdfaRepeat_6($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&appid=".$data['appid']);
		$json = json_decode($file_contents,true );
		// echo $file_contents;die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'1');//成功
				$contents = json_encode($a);
				echo  $contents;
				exit;
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'0'));//失败
				exit;
			}
	}

	//赚钱啦 点击回调
	function source_6($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$URL_SIGN = MD5($data['timestamp']."168a66c08da0ba918ce1718b594f5e7d");
		$url=$list['source_url']."?appid=".$data['appid']."&timestamp=".$data['timestamp'].'&idfa='.$data['idfa'].'&ip='.$data['ip']."&sign=".$URL_SIGN.'&callback='.$callback;
		// echo $url;die;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		if(isset($json['code']) && !$json['code']){//点击成功
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	
		// echo $file_contents;die;

	}
	//boss直聘
	function IdfaRepeat_10054($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$url='http://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}
	function source_10054($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$arr2 = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='http://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2); 
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		
	}

	//新boss直聘 排重
	function IdfaRepeat_10060($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'c1aa014f0f13c35137555ff0d22cc74a');
		$url='http://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}

	//新boss直聘 点击
	function source_10060($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'c1aa014f0f13c35137555ff0d22cc74a');
		$arr2 = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='http://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2); 
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		
	}

	//Boss直聘(高薪版)
	function IdfaRepeat_10061($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'c1aa014f0f13c35137555ff0d22cc74a');
		$url='http://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}

	//Boss直聘(高薪版)
	function source_10061($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'c1aa014f0f13c35137555ff0d22cc74a');
		$arr2 = array('app_id'=> 4002,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='http://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2); 
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
	}

	//点乐 点击回调
	function source_7($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url=$list['source_url'].'&idfa='.$data['idfa'].'&ip='.$data['ip'].'&callbackurl='.$callback;
		// echo $url;die;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		// echo $file_contents;die;

	}
	//米迪 排重
	function IdfaRepeat_8($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url'] . "&idfa=".$data['idfa']."&appid=".$data['appid']);
		$json = json_decode($file_contents,true );
		// echo $file_contents;die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}
	}
	//米迪点击
	function source_8($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url'].'&appid='.$data['appid'].'&idfa='.$data['idfa'].'&clientIp='.$data['ip'].'&callback='.$callback;
		// echo $url;
		
		
		$file_contents = $this->GetHttpStatusCode($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true );
		if($file_contents==302){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
	}
	
	//今日赚排重
	function IdfaRepeat_9($data,$list){
		if(!isset($data['ip'])){
			echo  json_encode(array('error'=>'ip error'));die;
		}
		// $url = "http://idfa.jinrizhuanqian.com:8080/jinrizhuancooper/downstream_check_idfa?source=aiyingli&check=1"."&idfa=".$data['idfa']."&appleid=".$data['appid']."&ip=".$data['ip'];
		$url = $list['IdfaRepeat_url']."&idfas=".$data['idfa']."&ip=".$data['ip'];
		//echo $url;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		//var_dump($json);
		// echo $file_contents;die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code'])&&isset($json['data'][$data['idfa']]) && $json['data'][$data['idfa']]==0  && $json['code']==0){
				
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
				
		}else{
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
		}
	}
	//今日赚点击回调
	
	function source_9($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		//$HeadUrl  = $list['source_url']!=''?$list['source_url']:'http://click.jinrizhuanqian.com:8080/jinrizhuancooper/downstream_click_notice?source=aiyingli&mac=02:00:00:00:00:00';
		$url=$list['source_url'].'&idfa='.$data['idfa'].'&mac=02:00:00:00:00:00&os='.$data['os'].'&ip='.$data['ip'].'&callbackurl='.$callback;
		//echo $url;
		$file_contents = $this->request_get($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true );
		if(isset($json['code']) && $json['code']==0){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		// echo $file_contents;die;

	}

	//今日赚上报
	function submit_9($data,$list){
		$url = $list['submit_url'] ."&ip=" . $data['ip'] . "&idfa=" . $data['idfa'];
		//echo $url;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
	//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json['code']==0 && isset($json['code'])){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}
	//铜板墙获取广告列表
	function GetAdList(){
			$json   = $this->request_get('http://taskapi.asowind.com/IOS/GetAdList?channelid=4e9fbabb-f18d-4436-b837-b43de3f2c409&idfa=&os=9.3.2&model=ipone&ip=&mac=02:00:00:00:00:00');
			if(isset($_GET['type']) && $_GET['type']==1){

				echo    $json;
				
			}else{
				return  $json;
			}
			
	}
	//铜板墙 排重
	function IdfaRepeat_10($data,$list){

		
		$data_al['channelid']   = '4e9fbabb-f18d-4436-b837-b43de3f2c409';
		$data_al['idfa']        = $data['idfa']; 
		$data_al['appid']       = $data['appid'];
		$data_al['ip']          = $data['ip'];

		$url = $list['IdfaRepeat_url'];
		//var_dump($data_al);
		//echo $url;
		$file_contents = $this->request_post($url,$data_al);
		
		// $file_contents = $this->request_get("http://121.40.57.89:8004/vendor/ioscheck?channelid=61B73D05-57E0-4F13-B7C3-54FB6B0D994F&appid=880988864"."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true );
		
		// echo $file_contents;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['resState']) && $json['resState']==200){
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
				
			}else{
				

				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}
	}
	//铜板墙 点击
	function source_10($data,$list){
		
		$data_al['channelid']   = '4e9fbabb-f18d-4436-b837-b43de3f2c409';
		$data_al['idfa']        = $data['idfa']; 
		$data_al['appid']       = $data['appid'];
		$data_al['ip']          = $data['ip'];
		
		$data_al['model']       = $data['device'];
		$data_al['os']          = $data['os'];
		$data_al['mac']         = '02:00:00:00:00:00';
	    $data_al['keyword']     = $data['keywords'];
		$url = $list['source_url'];
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		// $url="http://121.40.57.89:8004/vendor/iosclick?channelid=61B73D05-57E0-4F13-B7C3-54FB6B0D994F".'&idfa='.$data['idfa'].'&appleid=880988864&ip='.$data['ip'].'&callbackurl='.$callback;
		// echo $url;die;
		
		$file_contents = $this->request_post($url,$data_al);
		// echo $url;
		$json = json_decode($file_contents,true );
		
		if(isset($json['resState']) && $json['resState']==200){//点击成功
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		// echo $file_contents;die;

	}

	//铜板墙上报
	function submit_10($data,$list){
		
		$data_al['channelid']   = '4e9fbabb-f18d-4436-b837-b43de3f2c409';
		$data_al['idfa']        = $data['idfa']; 
		$data_al['appid']       = $data['appid'];
		$data_al['ip']          = $data['ip'];
		
		$data_al['model']       = 'iphone';
		$data_al['os']          = '9.3.2';
		$data_al['mac']         = '02:00:00:00:00:00';
		$data_al['keyword']     = $data['keywords'];
		$url = $list['submit_url'];
		$file_contents = $this->request_post($url,$data_al);
		$json = json_decode($file_contents,true );
		
		// echo $url;
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['resState']) && $json['resState']==200){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}

	// 甘草医生 排重
	function IdfaRepeat_11($data, $list){
		$url =$list['IdfaRepeat_url']."&appid=" . $data['appid'] . "&idfa=" . $data['idfa'] .  "&ip=" . $data['ip'];
		$file_contents = $this -> request_get($url);

		$json = json_decode($file_contents, true);
		//写入log
		$log = array('cpid'=>$data['cpid'], 'appid' => $data['appid'],'adid'=>$data['adid'],'idfa' => $data['idfa'], 'json' => $file_contents , 'date'=>time());
		$this -> db -> insert('aso_IdfaRepeat_log',$log);
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 0){ //排重成功
				$a = array($data['idfa']=>'1'); //这里返回1代表成功
				$contents = json_encode($a);
				echo  $contents;
		}else{ //排重失败
				echo  json_encode(array($data['idfa']=>'0'));//我们这里返回0代表失败不可做任务
		}	
	}

	// 甘草医生  点击
	function source_11($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . "&appid=" . $data['appid'] . "&idfa=" . $data['idfa'] . "&ip=" . $data['ip'] . "&callback=".$callback;
		$file_contents = $this->request_get($url);
		// echo $file_contents;
		$json = json_decode($file_contents,true );
		if($json['status']=="true"){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
	}
	//甘草医生  上报
	function submit_11($data,$list){
		$url = $list['submit_url'] . "&appid=" . $data['appid'] . "&ip=" . $data['ip'] . "&idfa=" . $data['idfa']."&device=iphone"."&os=9.3.1";
		$file_contents = file_get_contents($url);
		$json = json_decode($file_contents,true );		
		// unset($data['sign']);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json['status']=="true"){//上报成功
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
			}else{//失败 
				// unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	} 

	//苏宁易购,美团 排重
	function IdfaRepeat_12($data,$list){
 		// $url = "http://api.v3.9080app.com/RemoveEcho.ashx?adid=3154&appid=3104&idfa=" . $data['idfa'];
		$file_contents = $this->request_get($list['IdfaRepeat_url'] . "&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(!$json[$data['idfa']]){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//苏宁易购,美团 点击
	function source_12($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url'] . "&mac=02:00:00:00:00:00&idfa=".$data['idfa'] . "&ip=" . $data['ip'] . "&callback=" . $callback;

		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );

		//渠道返回：成功返回0  失败返回1
		if($json['success']){
			//成功返回
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//掌上互动 上报
	function submit_12($data,$list){
		$file_contents = $this->request_get($list['submit_url'] . "&mac=02:00:00:00:00:00&osverssion=10.1&ip=".$data['ip']."&idfa=" . $data['idfa']);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status']==1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}
	
	//Hello语音交友 排重
	function IdfaRepeat_88($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url'].'?idfa='.$data['idfa']);
		
		$json = json_decode($file_contents,true );
		// echo $file_contents;die;
		//写入log
	
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['data']) && $json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}
	}
	//Hello语音交友 点击
	function source_88($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		
		$url = $list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback;
		
		$file_contents = file_get_contents($url);
		
		//$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		
		//print_r($file_contents);exit;
		if(isset($json['code']) && $json['code']==200){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//i友钱空空狐,直播吧 排重
	function IdfaRepeat_14($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url'] . "&idfas=".$data['idfa']);
		$json = json_decode($file_contents,true );
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json["code"]==200) {
			if(!$json["result"][$data['idfa']]) {
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	// i友钱空空狐 点击回调
	function source_14($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$file_contents = $this->request_get($list['source_url'] . "&idfa=" . $data['idfa'] . "&ip=" . $data['ip'] . "&callback=" . $callback);
		$json = json_decode($file_contents,true );
		if($json['code'] == 200){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['json'] = $file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	//i友钱空空狐,直播吧 上报
	function submit_14($data,$list){
		$file_contents = $this->request_get($list['submit_url'] . "&idfa=" . $data['idfa']);
		$json = json_decode($file_contents,true );
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json['code']==200){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	//知聊 排重
	function IdfaRepeat_15($data,$list){
		//$url = "http://wap.lingqianapp.com/cpa/outer/iMoney/repeatVerify?idfa=" . $data['idfa'] ."&adId=3540185";
		$file_contents = $this->request_get($list['IdfaRepeat_url'] . "&idfa=" . $data['idfa']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}
	}

	//鼠宝 排重
	function IdfaRepeat_16($data,$list){
		$file_contents = $this->request_get1($list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'].'&ip='.$data['ip']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die;//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//鼠宝 点击
	function source_16($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		
		$url = $list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback;
		
		$file_contents =$this->request_get1($url);
		
		//$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true);
		
		
		//print_r($file_contents);exit;
		if(isset($json['status']) && $json['status']==1){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//鼠宝 上报
	function submit_16($data,$list){
		$file_contents = $this->request_get1($list['submit_url'] . "&idfa=" . $data['idfa']."&ip=".$data['ip']);
		
		$json = json_decode($file_contents,true );
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['success']) && $json['success']){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	//叮当快药 点击接口
	function source_17($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$method = "ddky.promotion.aso.pushidfa";
		$key = "1G42595E43F67D5F2EC79D7G1F4GC1CH9";
		$source = "yunju";
		$t = date("Y-m-d h:i:s");
		$v = "1.0";
		$A = "appid" . $data['appid'] . "idfa" . $data['idfa'] . "method" .$method . "source" . $source . "t" . $t . "v". $v;
		$B = $method . $A . $key;
		$sign = strtoupper(md5($B));

		$file_contents = $this->request_get($list['source_url'] . "?appid=" . $data['appid'] . "&idfa=" . $data['idfa'] ."&source=" . $source . "&t=" . urlencode($t) . "&v=" . $v . "&method=" . $method ."&sign=" . $sign . "&callbackurl=" . $callback . "&callBackOpenUrl=" .$callback);
		$json = json_decode($file_contents,true );
		// unset($data['reqtype'], $data['ip'], $data['isbreak'], $data['device'], $data['os'], $data['sign'], $data['callback']);
		if(!$json['code']){ //点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['json'] = $file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	// 叮当快药 排重
	function IdfaRepeat_17($data,$list){
		$method = "ddky.promotion.aso.check";
		$key = "1G42595E43F67D5F2EC79D7G1F4GC1CH9";
		$t = date("Y-m-d h:i:s");
		$v = "1.0";
		$type = 1;
		$A =  "idfa" . $data['idfa'] . "method" .$method  . "t" . $t . "type" . $type . "v". $v;
		$B = $method . $A . $key;
		$sign = strtoupper(md5($B));
		$url = $list['IdfaRepeat_url'] . "?idfa=". $data['idfa'] . "&method=". $method . "&t=". urlencode($t) . "&type=". $type . "&v=". $v . "&sign=" . $sign;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(!$json['code']){
			//成功返回
			if(!$json['result']['isClick']) {
				$a = array($data['idfa']=>'1');//这里返回1代表成功
				$contents = json_encode($a);
				echo  $contents;
			}else{
				echo  json_encode(array($data['idfa']=>'0'));//我们这里返回0代表失败不可做任务
			}

		}else{

			echo  json_encode(array($data['idfa']=>'0'));//我们这里返回0代表失败不可做任务
		}
	}

	
	//王者之剑2 排重 
	function IdfaRepeat_18($data,$list){
		$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);

		if(!$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{
			echo  json_encode(array($data['idfa']=>'0'));
		}
		
	}

	//王者之剑2 点击
	function source_18($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents = $this->request_get($list['source_url'] .  "&idfa=" . $data['idfa'] ."&ip=" . $data['ip'] . "&callback=" . $callback);
		$json = json_decode($file_contents,true );

		if(!$json['code']){ //点击成功
			$id = $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['json'] = $file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	//唔哩 排重 
	function IdfaRepeat_19($data,$list){
		$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);

		if(!$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	// 唔哩 上报
	function submit_19($data,$list){
		$url= $list['submit_url'] . "&idfa=" . $data['idfa'] . "&callback=aipu";
		$file_contents = $this->request_get($url);
		echo  json_encode(array('code'=>'0','result'=>'ok'));
	}



	//现金贷款王 点击
	
	function source_61($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		}
		 
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url ="http://loan.leying.me/index.php/api/ad/add_user_by_app_store?app=".$data['appid']."&idfa=".$data['idfa']."&os=".$data['os']."&ip=".$data['ip']."&from_channel=xiongmaoshiwan&callback=".$callback;
		

		$file_contents   = $this->request_get($url);

		$info            = explode(':',$file_contents);
		$json  = json_decode($file_contents,true);
	
		
		if(in_array('true}',$info)){
		    $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  $file_contents;
		}
	}


	//纳米盒 点击
	function source_62($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

        $url =$list['source_url']."&imei=".$data['idfa']."&vc=".$data['os']."&ip=".$data['ip'].'&user_id=0&udid=&dev_key=c85904e2ca44abc94c489ae52e059383&mac=02:00:00:00:00:00&callback_url='.$callback;
		

		$file_contents   = $this->request_get($url);

		//$info            = explode(':',$file_contents);
		$json  = json_decode($file_contents,true);
	    //var_dump($json);
		
		if(isset($json['code']) && $json['code']==0){
		    $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  $file_contents;
		}
	}

	//纳米盒  排重 
	function IdfaRepeat_62($data,$list){
		$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'];
		$file_contents = $this->request_get1($url);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 0){ // 1代表成功
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//纳米盒 上报 
	function submit_62($data,$list){
		$url = $list['submit_url'] . "&imei=" . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['code']) && $json['code']==0){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	//秒赚大钱快 排重 
	function IdfaRepeat_21($data,$list){
		$arr = $this->convertUrlQuery($list['IdfaRepeat_url']);
		$adid = $arr['adid'];
		$channel= 34108;
		$key = "9b4506365436fdb0237e69af9979064d";
		$sign = md5($adid."|".$channel."|".$key);
		$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'] . "&channel=" . $channel . "&sign=" . $sign;
		

		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);

		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){ // 1代表成功
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//秒赚大钱快 点击 
	function source_21($data,$list){
		$arr = $this->convertUrlQuery($list['source_url']);
		$adid = $arr['adid'];
		$asign   = $sign = md5($data['timestamp'].md5('callback'));
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$asign;
		$callback = urlencode($callback);
		$channel= 34108;
		$key = "9b4506365436fdb0237e69af9979064d";
		$sign = md5($adid."|".$channel."|".$key);

		$value = '&idfa='. $data['idfa'] .'&keywords='.urlencode($data['keywords'])."&channel=" . $channel . "&ip=" . $data['ip'] . "&sign=" . $sign.'&callbackurl='.$callback;
		

		$file_contents   = $this->request_get($list['source_url'] . $value);
		$json  = json_decode($file_contents,true);

		if(!$json['code'] && $json['result'] == "ok"){
			$this->db->insert('aso_source', $data); 
			echo $file_contents;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  $file_contents;
		}
	}

	//秒赚大钱快 上报 
	function submit_21($data,$list){
		$arr = $this->convertUrlQuery($list['submit_url']);
		$adid = $arr['adid'];
		$channel= 34108;
		$key = "9b4506365436fdb0237e69af9979064d";
		$sign = md5($adid."|".$channel."|".$key);

		$file_contents = $this->request_get($list['submit_url'] . "&idfa=" . $data['idfa'] . "&channel=" . $channel . "&sign=" . $sign);
		$json = json_decode($file_contents,true );
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(!$json['code'] && $json['result'][$data['idfa']]){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	

	//多玩 排重
	function IdfaRepeat_22($data,$list){
		$url = $list['IdfaRepeat_url'] ."&idfa=" . $data['idfa'];
		$file_contents = $this->request_get1($url);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//多玩 上报 
	function submit_22($data,$list){
		$url = $list['submit_url'] . "&idfa=" . $data['idfa'] . "&mac=02:00:00:00:00:00&ip=" . $data['ip'] . "&callback=1" ;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['success']) && $json['success']){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}



	//多玩 排重
	function IdfaRepeat_52($data,$list){
		$url = $list['IdfaRepeat_url'] ."&idfa=" . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//多玩 点击
	function source_52($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . '&idfa=' . $data['idfa'] . "&ip=" . $data['ip'] .'&callback='.$callback;

		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["success"]) && $json["success"] && $json["message"] == "ok"){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}


	//多玩 排重
	function IdfaRepeat_489($data,$list){
		$url = $list['IdfaRepeat_url'] ."&idfa=" . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//多玩 点击
	function source_489($data,$list){
		$url = $list['source_url'] . '&idfa=' . $data['idfa'] . "&ip=" . $data['ip'].'&callback=123';

		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		if(isset($json["success"]) && $json["success"] && $json["message"] == "ok"){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}


	//九域 排重
	function IdfaRepeat_26($data,$list){
		$url = $list['IdfaRepeat_url'] ."&appid=". $data['appid'] ."&idfa=" . $data['idfa'] . "&clientip=" . $data['ip'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json["success"]) && $json["success"] == "true"){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{ // 0代表失败
			echo  json_encode(array($data['idfa']=>'0'));
		}
	}

	//九域 点击
	function source_26($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . "&appid=" . $data['appid'] . '&idfa=' . $data['idfa'] . "&clientip=" . $data['ip'] .'&callback='.$callback;
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["success"]) && $json["success"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//闯奇 点击
	function source_27($data,$list){
		$url = $list['source_url'] . '&idfa=' . $data['idfa'] . "&ip=" . $data['ip'];
		$file_contents   = $this->request_get($url);
		// echo $url;exit;
		// var_dump($file_contents);exit;
		$json  = json_decode($file_contents,true );
		if(isset($json["success"]) && $json["success"]){
			$id = $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}	
	
	
	//闯奇分包排重
	function IdfaRepeat_27($data,$list){
		if(!isset($data['ip'])){
			echo  json_encode(array('error'=>'ip error'));die;
		}
		$file_contents = file_get_contents($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true );
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}
	}

	//闯奇分包上报
	function submit_27($data,$list){
		if(!isset($data['ip'])){
			echo  json_encode(array('error'=>'ip error'));die;
		}

		$file_contents = $this->request_get($list['submit_url']. "&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true );
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json['success']){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
			}else{//失败 
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}


	//柚子众测 排重
	function IdfaRepeat_28($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true );
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//柚子众测 点击
	function source_28($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . '&idfa=' . $data['idfa'] . "&ip=" . $data['ip']."&callback=".$callback;
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["status"]) && $json["status"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//柚子众测 上报
	function submit_28($data,$list){
		$url = $list['submit_url'] .'&idfa='.$data['idfa'];
		//echo $url;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($data['appid'] == 327313778) {
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);

				$data['json'] = $file_contents;
				$this->db->insert('aso_submit_log',$data);

				echo  json_encode(array('code'=>'0','result'=>'ok'));
				exit;
		}
		if($json['status']){//上报成功
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}

	//新柚子众测 排重
	function IdfaRepeat_281($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&ip='.$data['ip']);
		$json = json_decode($file_contents,true );
		//echo $list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&ip='.$data['ip'];
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1

		if(isset($json['data'][$data['idfa']]) && $json['data'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//新柚子众测 点击
	function source_281($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . '&idfa=' . $data['idfa'] . "&ip=" . $data['ip']."&callback=".$callback;
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["status"]) && $json["status"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//新柚子众测 上报
	function submit_281($data,$list){
		$url = $list['submit_url'] .'&idfa='.$data['idfa'];
		//echo $url;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		
		//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($data['appid'] == 327313778) {
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);

				$data['json'] = $file_contents;
				$this->db->insert('aso_submit_log',$data);

				echo  json_encode(array('code'=>'0','result'=>'ok'));
				exit;
		}
		if(isset($json['status']) && $json['status']){//上报成功
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}
	
	
	//快感锁屏 排重
	function IdfaRepeat_29($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			
				$log['ip'] = $data['ip'];
				$this->db->insert('aso_IdfaRepeat',$log);
				echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功	
			
		}else{
			$this->db->insert('aso_IdfaRepeat_log',$log);
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//快感锁屏 点击
	function source_29($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] .'&idfa=' . $data['idfa'] . "&ip=" . $data['ip']."&mac=02:00:00:00:00:00"."&callbackurl=".$callback;
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["success"]) && $json["success"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//快感锁屏 上报
	function submit_29($data,$list){
		$url = $list['submit_url'] .'&idfa='.$data['idfa']."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['success']) && $json['success']){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				unset($list['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}

	//钱夹 排重
	function IdfaRepeat_31($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&user_ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		$r = (string)$json["success"];
		//渠道返回：成功返回0  失败返回1
		if(isset($json["success"]) && $r === "true"){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//钱夹 点击
	function source_31($data,$list){
		if($data['appid'] == 1010521665) {
			// echo 1;exit;
			$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&user_ip=".$data['ip']."&iosver=" . $data['os']);
			$json = json_decode($file_contents,true);
			$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
			$this->db->insert('aso_IdfaRepeat_log',$log);
			$r = (string)$json["success"];
			//渠道返回：成功返回0  失败返回1
			if(isset($json["success"]) && $r != "true"){
				echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
			}
		}
		$url = $list['source_url'] .'&idfa=' . $data['idfa'] ."&user_ip=" . $data['ip'];
		$file_contents = $this->request_get($url);
		// $json  = json_decode($file_contents,true );
		$this->db->insert('aso_source', $data); 
		echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		// if(!empty($file_contents)){
		// 	$this->db->insert('aso_source', $data); 
		// 	echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		// }else{
		// 	$data['json'] =$file_contents;
		// 	$this->db->insert('aso_source_log', $data); 
		// 	echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		// }
	}

	//钱夹 上报
	function submit_31($data,$list){
		$url = $list['submit_url'] .'&idfa='.$data['idfa']."&user_ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		// $json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		$data['timestamp']=time();
		$data['type'] = 1;
		$this->db->insert('aso_submit2',$data);
		echo  json_encode(array('code'=>'0','result'=>'ok'));
		// if($json['result'] == 1){//上报成功
		// 		$data['timestamp']=time();
		// 		$data['type'] = 1;
		// 		$this->db->insert('aso_submit2',$data);
		// 		echo  json_encode(array('code'=>'0','result'=>'ok'));
				
		// 	}else{//失败 
		// 		unset($list['ip']);
		// 		$data['timestamp']=time();
		// 		$data['type'] = 1;
		// 		$data['json'] =$file_contents;
		// 		$this->db->insert('aso_submit_log',$data);
		// 		echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		// 	}
	}

	//磨盘 排重
	function IdfaRepeat_32($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//磨盘 点击
	function source_32($data,$list){
		$url = $list['source_url'] .'&idfa=' . $data['idfa'] ."&clientIp=" . $data['ip'];
		$file_contents = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		$this->db->insert('aso_source', $data); 
		echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		// if(!empty($file_contents)){
		// 	$this->db->insert('aso_source', $data); 
		// 	echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		// }else{
		// 	$data['json'] =$file_contents;
		// 	$this->db->insert('aso_source_log', $data); 
		// 	echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		// }
	}

	//姑婆 排重
	function IdfaRepeat_33($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json['msg']) && $json['msg']==0){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//姑婆 点击
	function source_33($data,$list){

		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;

		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&os_version=".$data['os']."&mac=02:00:00:00:00:00&callback_url=".$callback;

		$file_contents = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		
		if($json['status']==1){
			
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}


	// 动信通 排重
	function IdfaRepeat_34($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code']) && $json['code']==1000 && $json['data'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//动信通 排重
	function IdfaRepeat_341($data,$list){
		$url = $list['IdfaRepeat_url']. "&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($data['appid']==1159576400){
			if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			     echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			}
		}
		
		if(isset($json['code']) && $json['code'] == 0 && $json['data']['status'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//动信通点击
	function source_341($data,$list){
		
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);


		$url = $list['source_url'] ."&idfa=".$data['idfa']."&callback=".$callback;

		// $file_contents = file_get_contents($url);

		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		if(isset($json['code']) && $json['code'] == 0){
			 $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}



	// 有鱼记账 排重
	function IdfaRepeat_35($data,$list){
		$res['asoAppid']  = $data['appid'];
		$res['idfa']      = $data['idfa'];
		$res['timestamp'] = time();
		$res['signMsg']   = strtoupper(md5('appid='.$data['appid'].'&idfa='.$data['idfa'].'&timestamp='.time().'&key=youyuidfa?!'));
		$res['key']       = 'youyuidfa?!';
		$file_contents = $this->request_post($list['IdfaRepeat_url'],$res);
		$json = json_decode($file_contents,true);
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code']) && $json['code']==1 && $json['results'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	// 有鱼记账 点击
	function source_35($data,$list){

		// $id = $this->db->insert('aso_source', $data); 
		// $inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;

		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$res['pburl']            = $callback;
		$res['idfa']             = $data['idfa'];
		$res['asoAppid']         = $data['appid'];
		$res['download_source']  = 'apyb';
		
		
		$file_contents = $this->request_post($list['source_url'],$res);
		
		$json  = json_decode($file_contents,true );
		
		if(isset($json['code']) && $json['code']==1){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}


	//兼客兼职 点击回调
	function source_42($data,$list){
		$this->source_982754793($data,$list);
	}

	//一伴婚恋 排重
	
	function IdfaRepeat_20($data,$list){
		if($data['appid']==1335846722){
			list($t1, $t2) = explode(' ', microtime()); 
			
			$timestamp= (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);  
			$key_title='相亲';
			$idfa_channel="ayl";
			$channel='2';
            $platform=2;
			$str="channel".$channel."idfa".$data['idfa']."idfachannel".$idfa_channel.'keytitle'.$key_title.'platform'.$platform."timestamp".$timestamp;
			$str1=preg_replace("/[^a-zA-Z0-9]+/","", $str);
			$s1 = MD5("dgfdg43spi".$str1."y712efggr");
			$signstr=MD5("fdf3ng".$s1."ojjky");

			$url = $list['IdfaRepeat_url']."?idfa=".$data['idfa']."&sign=".$signstr."&timestamp=".$timestamp."&key_title=相亲"."&idfa_channel=ayl&channel=".$channel."&platform=".$platform;
			
			$file_contents = $this->request_get($url);
			$json = json_decode($file_contents,true);
			
			$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
			$this->db->insert('aso_IdfaRepeat_log',$log);

			if(isset($json['code']) && $json['code']==0){
				echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}
			
			//echo $signstr;
		}else{
			$time = time();
			if(explode('&',$list['IdfaRepeat_url'])[1]=='idfa_channel=ayl'){
			
				$key = 'idfachannelaylkeytitletimestamp';
			}else{
				$key = 'idfachannelkuchuankeytitletimestamp';
			}
			$joint = "idfa" . str_replace("-","",$data['idfa']) . $key . $time;
			// echo $joint;
		    $a = md5("fidfas" . $joint ."9id8jo90");
			$sign = md5("3i45" . $a . "bffffak");
			$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'] . "&sign=".$sign."&timestamp=".$time;
			$file_contents = $this->request_get($url);
			
			$json = json_decode($file_contents,true);
			//var_dump($json);
			//写入log
			$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>$time);
			$this->db->insert('aso_IdfaRepeat_log',$log);
			//渠道返回：成功返回0  失败返回1
			if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
				echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}
		}
		
	}
	//一伴婚恋 排重
	function submit_20($data,$list){
		if($data['appid']==1335846722){
			list($t1, $t2) = explode(' ', microtime()); 
			
			$timestamp= (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);  
			$key_title='相亲';
			$idfa_channel="ayl";
			$channel='2';
            $platform=2;
			$str="channel".$channel."idfa".$data['idfa']."idfachannel".$idfa_channel.'keytitle'.$key_title.'platform'.$platform."timestamp".$timestamp;
			$str1=preg_replace("/[^a-zA-Z0-9]+/","", $str);
			
			$s1 = MD5("dgfdg43spi".$str1."y712efggr");
			$signstr=MD5("fdf3ng".$s1."ojjky");

			$url = $list['submit_url']."?idfa=".$data['idfa']."&sign=".$signstr."&timestamp=".$timestamp."&key_title=相亲"."&idfa_channel=ayl&channel=".$channel."&platform=".$platform;
			$file_contents = $this->request_get($url);
			$json = json_decode($file_contents,true);
			
			unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
			
			if(isset($json['code']) && $json['code'] == 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			//echo $signstr;
			}	
		}else{
			$time = time();
			if(explode('&',$list['submit_url'])[1]=='idfa_channel=ayl'){
			
				$key = 'idfachannelaylkeytitletimestamp';
			}else{
				$key = 'idfachannelkuchuankeytitletimestamp';
			}
			
			$joint = "idfa" . str_replace("-","",$data['idfa']) . $key . $time;
		    $a = md5("fidfas" . $joint ."9id8jo90");
			$sign = md5("3i45" . $a . "bffffak");
			$url = $list['submit_url'] .'&idfa='.$data['idfa']."&timestamp=" . $time."&sign=".$sign;
			;
			$file_contents = $this->request_get($url);
			$json = json_decode($file_contents,true);
			unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
			
			if(isset($json['state']) && $json['state'] == 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
		}
	}
	//一伴婚恋 点击
	function source_20($data,$list){
		list($t1, $t2) = explode(' ', microtime()); 
			
		$timestamp= (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);  
		$key_title='相亲';
		$idfa_channel="ayl";
		$channel='2';
        $platform=2;
		$str="channel".$channel."idfa".$data['idfa']."idfachannel".$idfa_channel.'keytitle'.$key_title.'platform'.$platform."timestamp".$timestamp;
		$str1=preg_replace("/[^a-zA-Z0-9]+/","", $str);
		
		$s1 = MD5("dgfdg43spi".$str1."y712efggr");
		$signstr=MD5("fdf3ng".$s1."ojjky");

		$url = $list['source_url']."?idfa=".$data['idfa']."&sign=".$signstr."&timestamp=".$timestamp."&key_title=相亲"."&idfa_channel=ayl&channel=".$channel."&platform=".$platform;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		
		
		if(isset($json['code']) && $json['code'] == 1){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}



	//樱花婚恋 排重
	function IdfaRepeat_970($data,$list){
		$time = time();
		$joint = "idfa" . str_replace("-","",$data['idfa']) . "idfachannelaylkeytitletimestamp" . $time;
		// echo $joint;
	    $a = md5("fidfas" . $joint ."9id8jo90");
		$sign = md5("3i45" . $a . "bffffak");
		$url = $list['IdfaRepeat_url'] . "&idfa=" . $data['idfa'] . "&sign=".$sign."&timestamp=".$time;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>$time);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//樱花婚恋 排重
	function submit_970($data,$list){
		$time = time();
		$joint = "idfa" . str_replace("-","",$data['idfa']) . "idfachannelaylkeytitletimestamp" . $time;
	    $a = md5("fidfas" . $joint ."9id8jo90");
		$sign = md5("3i45" . $a . "bffffak");
		$url = $list['submit_url'] .'&idfa='.$data['idfa']."&idfa_channel=ayl". "&timestamp=" . $time."&sign=".$sign;
		
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['state']) && $json['state'] == 1){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			unset($data['ip']);
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}
	
	
	//集聘 排重
	function IdfaRepeat_10062($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$url='https://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}

	//集聘 点击
	function source_10062($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$arr2 = array('app_id'=> 4050,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='https://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2);
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	//茄客 排重
	function IdfaRepeat_40($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		// print_r($file_contents);exit;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']] && !$json['errno']){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//茄客 点击
	function source_40($data,$list){
		$id = $this->db->insert('aso_source', $data); 
		// $inid = $this->db->insert_id();
		// $sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		// $callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		// $callback = urlencode($callback);

		$url = $list['source_url'] . "&appid=".$data['appid'].'&idfa=' . $data['idfa'] . "&ip=" . $data['ip']; //"&callback=".$callback
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["errno"]) && !$json["errno"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//茄客 上报
	function submit_40($data,$list){
		$url = $list['submit_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] . "&ip=" . $data['ip'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["errno"]) && !$json["errno"]){//上报成功
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			}
	}

	//今日影视大全 排重
	function IdfaRepeat_41($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);
		// print_r($file_contents);exit;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 1){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//今日影视大全 点击
	function source_41($data,$list){
		$url = $list['source_url'] . "&appid=".$data['appid'].'&idfa=' . $data['idfa'] . "&ip=" . $data['ip']; 
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		if(isset($json["code"]) && !$json["code"]){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//今日影视大全 激活
	function submit_41($data,$list){
		$url = $list['submit_url'] ."appid=".$data['appid'].'&idfa=' . $data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["active"]) && $json["active"] == 1){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	//boss直聘极速版 排重
	function IdfaRepeat_10063($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4053,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$url='https://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}

	//boss直聘极速版 点击
	function source_10063($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4053,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$arr2 = array('app_id'=> 4053,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='https://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2);
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		
	}


	//boss名企直聘 排重
	function IdfaRepeat_10064($data,$list){
		$str='';
		$surl ='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4060,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0');
		foreach ($arr as $key => $value) {
			$surl .=$key.'='.$value.'&';
		}
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/idfaExist'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$url='https://api.bosszhipin.com/api/integralWall/idfaExist?'.$surl.'sig='.$sig;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($json[$data['idfa']]==1){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
		}else{
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}

	}

	//boss名企直聘 点击
	function source_10064($data,$list){
		if(empty($data['callback'])){
			echo'{"resultCode":-1,"errorMsg":"callback error"}'; die;  
		}
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$str='';
		list($t1, $t2) = explode(' ', microtime());
		$time= $t2 .  ceil( ($t1 * 1000) );
		$arr = array('app_id'=> 4060,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none');
		ksort($arr);
		foreach ($arr as $key => $value) {
			$str.=$key.'='.$value;
		}
		$sig='V2.0' . MD5('/api/integralWall/save8090'.$str.'b4c64402a3e858c6d725f05c5a1ca097');
		$arr2 = array('app_id'=> 4060,'req_time'=>$time,'uniqid'=>'9080app','idfa'=>$data['idfa'],'v'=>'2.0','ip'=>$data['ip'],'source'=>'aiyingli1','callback'=>$callback,'mac'=>'none','openUdid'=>'none','sig'=>$sig);
		$url='https://api.bosszhipin.com/api/integralWall/save8090';
		$file_contents = $this->request_post($url,$arr2);
		$json = json_decode($file_contents,true );
		if(isset($json['status']) && $json['status']==1){//点击成功
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
		
		
	}
	

	//中海金融交易 排重
	function IdfaRepeat_42($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appid=".$data['appid']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//禅大师 排重
	function IdfaRepeat_43($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//禅大师 激活
	function submit_43($data,$list){
		$url = $list['submit_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["success"]) && $json["success"]){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}
	//快友 点击回调
	function source_44($data,$list){
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&mac=02:00:00:00:00:00&ip=".$data['ip']."&callback=".$callback.'&os='.$data['os']."&useragent=&skip=0&openudid=");
		//echo $list['source_url']."&idfa=".$data['idfa']."&mac=02:00:00:00:00:00&ip=".$data['ip']."&callback=".$callback.'&os='.$data['os']."&useragent=&skip=0&openudid=";
		 
		$json  = json_decode($file_contents,true );

		if(isset($json['res']) && $json['res'] == 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//快友 排重
	function IdfaRepeat_44($data,$list){
		$res['idfa']   = $data['idfa'];
		$res['appid']  = $data['appid'];
		$file_contents = $this->request_post($list['IdfaRepeat_url'],$res);
		
		$json = json_decode($file_contents,true);

		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//触金 排重
	function IdfaRepeat_45($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//触金 点击
	function source_45($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] .'&idfa=' . $data['idfa'] ."&ip=".$data['ip']."&returnUrl=" . $callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		if(isset($json["Error"]) && !$json["Error"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//触金 激活
	function submit_45($data,$list){
		$url = $list['submit_url'] .'&idfa=' . $data['idfa'] ."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["Error"]) && !$json["Error"]){ //上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}
	//点鑫 排重
	function IdfaRepeat_46($data,$list){
		$arr = $this->convertUrlQuery($list['IdfaRepeat_url']);
		$param = array();
		$param['adid'] = $arr['adid'];
		$param['idfas'] = $data['idfa'];
		
		$file_contents = $this->request_post("http://api.23dx.cn/ios/echo", $param);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//点鑫 点击
	function source_46($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url'] .'&idfa=' . $data['idfa'] ."&ip=".$data['ip']."&callback=" . $callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );

		if(isset($json["Status"]) && $json["Status"] && !$json["Code"]){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//点鑫 激活
	function submit_46($data,$list){
		$arr = $this->convertUrlQuery($list['submit_url']);
		$param = array();
		$param['adid'] = $arr['adid'];
		$param['idfa'] = $data['idfa'];
		$param['appid'] = $arr['appid'];
		$param['ip'] = $data['ip'];

		$sign = md5(self::buildParamStr($param));
		$url = $list['submit_url'] .'&idfa=' . $data['idfa'] ."&ip=".$data['ip']."&key=".$sign;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["Status"]) && $json["Status"] && !$json["Code"]){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}

	//脚印网络 排重
	function IdfaRepeat_47($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//脚印网络 点击
	function source_47($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] ."&ip=".$data['ip']."&callback=".$callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );

	if(isset($json["success"]) && $json["success"] == "true"){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//脚印网络 激活
	function submit_47($data,$list){
		$url = $list['submit_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] ."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["message"]) && $json["message"] == "success" && $json["success"]){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}



	//热云 排重
	function IdfaRepeat_56($data,$list){
		$url = $list['IdfaRepeat_url'];

		$data['idfa']  = $data['idfa'];
		$data['appid'] = $data['appid'];
		
		$file_contents = $this->request_post($url,$data);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//热云 点击
	function source_56($data,$list){
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url = $list['source_url']."?idfa=".$data['idfa'].'&ip='.$data['ip'].'&noredirect=true'.'&callback='.$callback;
	
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		
		if(isset($json['status']) && $json['status']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//酷传 排重
	function IdfaRepeat_23($data,$list){
		
		$sign              = md5(time().'d15d5fa40c274ba089c40bfa239a0127');
		//$file_contents = $this->request_post($list['IdfaRepeat_url'], $req);
		
		$url               = $list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&sign=".$sign."&timestamp=".time()."&name=aiyingli";
	
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json['status']==200 && $json['data']==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//酷传 点击
	function source_23($data,$list){
		// if(empty($data['callback'])){
		//  	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		//  }
		 $id = $this->db->insert('aso_source', $data); 
		 $inid = $this->db->insert_id();
		 $sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		 $callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		 $callback = urlencode($callback);
		$url = $list['source_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&name=aiyingli&ip=".$data['ip']."&systemVersion=".$data['os']."&timestamp=".time()."&sign=".md5(time().'d15d5fa40c274ba089c40bfa239a0127')."&callback=".$callback;

		
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		if($json["status"]==200 && $json["data"] == 0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//应用猎人 排重
	function IdfaRepeat_24($data,$list){
		
	
		//$file_contents = $this->request_post($list['IdfaRepeat_url'], $req);
		
		$url               = $list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa'];
		
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//应用猎人 点击
	function source_24($data,$list){
		// if(empty($data['callback'])){
		//  	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		//  }
		 $id = $this->db->insert('aso_source', $data); 
		 $inid = $this->db->insert_id();
		 $sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		 $callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		 $callback = urlencode($callback);
		$url = $list['source_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&ip=".$data['ip']."&callback=".$callback;

	
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );

		if(isset($json["status"]) && $json["status"] == 1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}


	//应用猎人 完成上报
		function submit_24($data,$list){
		$url = $list['submit_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] ."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json["status"]) && $json["status"] == 1){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}
	
	//熊猫排重
	function IdfaRepeat_25($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		 $list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		$json = json_decode($file_contents,true);

		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//熊猫上报
	function submit_25($data,$list){
		$url = $list['submit_url'] ."&appid=".$data['appid'].'&idfa=' . $data['idfa'] ."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["status"]) && $json["status"] == 1){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}


	//熊猫回调
	function source_25($data,$list){
		// if(empty($data['callback'])){
		//  	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		//  }
		 $id = $this->db->insert('aso_source', $data); 
		 $inid = $this->db->insert_id();
		 $sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		 $callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		 $callback = urlencode($callback);
		$url = $list['source_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&ip=".$data['ip']."&callBackUrl=".$callback.'&os='.$data['os'];

	
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );

		if(isset($json["status"]) && $json["status"] == 1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	
	
	//创榜 排重
	function IdfaRepeat_900($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appleid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//echo $list['IdfaRepeat_url']."&appleid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	

	//创榜 上报
	function submit_900($data,$list){

		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&appleid=".$data['appid']);
		//echo $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&appleid=".$data['appid'];
		$json = json_decode($file_contents,true);
		//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['status']) && $json['status'] == 'true' && $json['message']=='success'){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}
	//战鼓之魂 排重
	function IdfaRepeat_49($data,$list){
		$url = "http://staging.smartcrows.com:4099/validateIDFA?idfa=".$data['idfa']."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['Code']) && $json['Code'] == 200){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//战鼓之魂 激活
	function submit_49($data,$list){
		$url = "http://staging.smartcrows.com:3005/checkIDFAActivalion?idfa=" . $data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['code']) && $json['code'] == 200){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}

	//凡卓 排重
	function IdfaRepeat_50($data,$list){
		if($data['appid']==847334708){
			$data['appid']=8473347080;
		}else if($data['appid']==1147426399){
			$data['appid'] = '1147426399-fz';
		}else if($data['appid']==1149186410){
			$data['appid'] ='1149186410-fz';
		}
		if(strstr($list['IdfaRepeat_url'],'&apple_id=')!==false){
			$url           = $list['IdfaRepeat_url']."&idfas=".$data['idfa'];
			
		}else{
			$url           = $list['IdfaRepeat_url']."&apple_id=".$data['appid']."&idfas=".$data['idfa'];
			
		}
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		// print_r($list['IdfaRepeat_url']."&apple_id=".$data['appid']."&idfas=".$data['idfa']);exit;

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json["errno"]) && !$json["errno"] && !$json['data']['idfas'][$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//凡卓 点击
	function source_50($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		// $id = $this->db->insert('aso_source', $data); 
		// $inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] .'&idfa=' . $data['idfa'] ."&ip=".$data['ip']."&click_time=".$data['timestamp']."&notify_url=".$callback;
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true);
		if(isset($json["errno"]) && !$json["errno"]){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//CN 排重
	function IdfaRepeat_51($data,$list){
		$this->IdfaRepeat_53($data,$list);
	}

	
	//CN 点击
	function source_51($data,$list){
		$this->source_53($data,$list);
	}

	//CN 回调系统排重
	function IdfaRepeat_70($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//试玩大师 点击
	function IdfaRepeat_71($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&appid='.$data['appid']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//试玩大师 点击
	function source_71($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url'] ."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback;
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json["code"]==0){
			 $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//试用宝 排重
	function IdfaRepeat_73($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&app_id='.$data['appid'].'&ip='.$data['ip']);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['status']) && $json['status']==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//试用宝 点击
	function source_73($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url'] ."&idfa=".$data['idfa'].'&app_id='.$data['appid'].'&ip='.$data['ip']."&callback_url=".$callback;
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['status']) && $json['status']==0){
			 $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//试用宝 上报
	function submit_73($data,$list){
		$url = $list['submit_url']."&app_id=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status']==0){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//新CN排重对接接口
	function IdfaRepeat_53($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appid=".$data['appid']."&idfa=".$data['idfa'].'&ip='.$data['ip']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//新CN点击对接接口
	function source_53($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url'] ."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback;
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if($json["code"]==1){
			// $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//新CN激活对接接口 激活
	function submit_53($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['code']) && $json['code'] == 1){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//行者天下 京东金融 appid=jingdongjinrong
	function IdfaRepeat_105($data,$list){
		$this -> IdfaRepeat_359($data, $list);
	}

	function source_105($data,$list){
		$this -> source_359($data, $list);
	}

	//新CN
	function IdfaRepeat_969($data,$list){
		$this -> IdfaRepeat_53($data, $list);
	}

	function source_969($data,$list){
		$this -> source_53($data, $list);
	}

	function submit_969($data,$list){
		$this -> submit_53($data, $list);
	}

	//猎豆点击
	function source_54($data,$list){
		$nsign          = md5("appBaseId=".$data['appid']."&channelId=20015&idfa=".$data['idfa']."slims267(envied");
		$cparam		   = "{'source':'liedou','appBaseId':{$data['appid']},'sign':{$nsign}}";
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url'] ."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&userid=waifang&osver=".$data['os']."&cparam=".$cparam."&ctype=1&mac=02:00:00:00:00:00&callback=".$callback;
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}


	//猎豆排重
	function IdfaRepeat_54($data,$list){
		$sign          = md5("appBaseId=".$data['appid']."&channelId=20015&idfa=".$data['idfa']."slims267(envied");
		$cparam		   = "{'source':'liedou','appBaseId':{$data['appid']},'sign':{$sign}}";
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appBaseId=".$data['appid']."&idfa=".$data['idfa'].'&sign='.$sign.'&cparam='.$cparam);
		
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		

		
		//渠道返回：成功返回0  失败返回1
		if($json['code']==0 && $json['data']['result']==1){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//猎豆 激活
	function submit_54($data,$list){
		
		$sign          = md5("appBaseId=".$data['appid']."&channelId=20015&idfa=".$data['idfa']."slims267(envied");

		$cparam		   = "{'source':'liedou','appBaseId':{$data['appid']},'sign':{$sign}}";	
		$file_contents = $this->request_get($list['submit_url']."&appBaseId=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&sign=".$sign.'&cparam='.$cparam.'&mac=02:00:00:00:00:00');

		//echo $list['submit_url']."&appBaseId=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&sign=".$sign.'&cparam='.$cparam.'&mac=02:00:00:00:00:00';
	    $json = json_decode($file_contents,true);
	
		
		//echo $list['submit_url']."?uuid=".$data['idfa']."&appid=".$data['appid']."&deviceName=ipone4&deviceVersion=4.0.0&idfa=".$data['idfa']."&ip=".$data['ip']."&deviceMac=02:00:00:00:00:00&network=wifi&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f";
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($json['message']== 'success' && $json['code']==0){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//巨掌排重 
	function IdfaRepeat_246($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&ip='.$data['ip'].'&mac=02:00:00:00:00:00');
		
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		

		
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//巨掌点击 
	function source_246($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		
		if($data['device']=='iPhone 5s'){
			$data['device']=str_replace(' ','',$data['device']);
		}
		$url = $list['source_url'] ."&idfa=".$data['idfa']."&ip=".$data['ip'].'&devicemodel='.$data['device'].'&systemversion='.$data['os'];
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if($json["State"]==100){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//巨掌上报 
	function submit_246($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['State']) && $json['State'] == 100){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	
	
	
	//36氪 排重
	function IdfaRepeat_593394038($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa'].'&appid='.$data['appid']);
		
		$json = json_decode($file_contents,true);
		//var_dump($json);
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		

		
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==1){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//36氪 点击回调
	function source_593394038($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		$url = $list['source_url'] ."?idfa=".$data['idfa']."&callback=".$callback.'&appid='.$data['appid'].'&ip='.$data['ip'].'&timestamp='.$data['timestamp'].'&sign='.md5($data['timestamp'].'21e6022915a46283741cdb686693d83ab40d9692');
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	

	//每日优鲜排重
	function IdfaRepeat_36($data,$list){
		
		$file_contents = $this->request_get("https://as-vip.missfresh.cn/web20/statistic/center/idfa/filter/aiyingli?appid=".$data['appid']."&idfa=".$data['idfa']);
		

		$json = json_decode($file_contents,true);
		//print_r($json);die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//每日优鲜点击回调
	function source_36($data,$list){
	     $id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = "https://as-vip.missfresh.cn/web20/statistic/center/idfa/report/aiyingli?appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback."&os=".$data['os']."&mac=02:00:00:00:00:00";
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if($json["code"]==00){
			// $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//拍拍贷 排重
	function IdfaRepeat_37($data,$list){
		
		date_default_timezone_set('Asia/Shanghai');
		$appid                  = explode('=',explode('&',$list['source_url'])[0])[1];
		//echo $appid;
		$res['AppId']           = explode('=',explode('&',$list['source_url'])[1])[1];
		//echo $res['AppId'];
		$res['Idfas'][0]['Idfa'] = $data['idfa'];
		$res['Source']          = explode('=',explode('&',$list['source_url'])[2])[1];
		$json_string            = json_encode($res);
		
        $url       = 'https://openapi.ppdai.com/marketing/AdvertiseService/CheckAdvertise';
        
		$file_contents = $this->SendRequest($url,$json_string,$appid,$data['appid']);
		

		$json = json_decode($file_contents,true);
		//var_dump($json);
		//print_r($json);die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['Result']) && $json['Result']==0 && $json['Content']['CheckIdfaResults'][0]['IsActive']==1){
			echo  json_encode(array($data['idfa']=>'1')); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	
	//拍拍贷 点击
	function source_37($data,$list){
	    date_default_timezone_set('Asia/Shanghai');
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		//$callback = urlencode($callback);
		$appid                  = explode('=',explode('&',$list['source_url'])[0])[1];
		$res['AppId']           = explode('=',explode('&',$list['source_url'])[1])[1];
		$res['Idfa']            = $data['idfa'];
		$res['CallBackUrl']     = $callback;
		$res['DeviceId']        = '';
		$res['Source']          = explode('=',explode('&',$list['source_url'])[2])[1];
		$res['Mac']             = '';
		$json_string            = json_encode($res);
		//echo $json_string;
		$url                    = 'https://openapi.ppdai.com/marketing/AdvertiseService/SaveAdvertise';
		
		$file_contents = $this->SendRequest($url,$json_string,$appid,$data['appid']);
		
		//$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		//var_dump($json);
		if(isset($json['Result']) && $json['Result']==0 && $json['Content']['IsActive']==1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//向上金服 排重
	function IdfaRepeat_38($data,$list){
		$data_al['allidfa']  = $data['idfa'];
		$url = $list['IdfaRepeat_url'];
		$file_contents = $this->request_post($url,$data_al);

		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == '0'){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//向上金服 上报
	function submit_38($data,$list){
		$url = $list['submit_url'] .'&idfa=' . $data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json["status"]) && $json["status"]){ //上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}


	//隆驰兴润 排重
	function IdfaRepeat_39($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url'].'&idfa='.$data['idfa']);

		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == '0'){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//隆驰兴润 上报
	function submit_39($data,$list){
		$file_contents = $this->request_get($list['submit_url'].'&idfa='.$data['idfa']);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]=='0'){ //上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;//这里返回1代表成功
		}
	}

	//CN 回调系统点击
	function source_70($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] ."&mac=02:00:00:00:00:00".'&idfa=' . $data['idfa']."&ip=".$data['ip']."&callback=" . $callback;
		$file_contents   = $this->request_get($url);
		// $json  = json_decode($file_contents,true);

		if(isset($file_contents) && $file_contents == 1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//谢总对面 排重
	function IdfaRepeat_99($data,$list){
		$url = $list['IdfaRepeat_url'];
		$timestamp = time();
		$re = array();
		if($data['appid']==1228060341){
			$re['source'] = 'yyykuchuan';
		}else if($data['appid']==574152539){
			$re['source'] = 'kuchuan';
		}
		$re['appid'] = $data['appid'];
		$re['time'] = $timestamp;
		$re['sign'] = MD5($re['source'].$re['time']);
		$re['idfa'] = $data['idfa'];
		$file_contents = $this->request_post($url, $re);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>$timestamp);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] ==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//谢总对面 点击
	function source_99($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		if($data['appid']==1228060341){
			$source = 'yyykuchuan';
		}else if($data['appid']==574152539){
			$source = 'kuchuan';
		}
		$url = $list['source_url']."idfa=".$data['idfa']."&source=".$source."&mac="."&callback=".$callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		// print_r($json);exit;
		if(isset($json["success"]) && $json["success"] == "true"){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	
	
	//钱大师 点击
	function source_100($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		
		$url = $list['source_url']."&idfa=".$data['idfa'].'&ip='.$data['ip'].'&mac=02:00:00:00:00:00';
		//echo $url;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		
		//print_r($json);exit;
		if($json['data']==1 && $json['status']==1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	
	
	//钱大师 排重
	function IdfaRepeat_100($data,$list){
		$url = $list['IdfaRepeat_url'].'&idfa='.$data['idfa'];
		
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	
	function submit_100($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa'].'&ip='.$data['ip'];
		
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status'] == 1){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//战鼓之魂 排重
	function IdfaRepeat_69($data,$list){
		$url = "http://staging.smartcrows.com:3005/checkIDFAActivalion?channel=vd10101&idfa=".$data['idfa']."&ip=".$data['ip'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code']) && $json['code'] == 500){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//战鼓之魂 激活
	function submit_69($data,$list){
		$url = "http://staging.smartcrows.com:3005/checkIDFAActivalion?channel=vd10101&idfa=" . $data['idfa']."&ip=".$data['ip'];;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['code']) && $json['code'] == 200){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//钱时代理财尊享版 排重对接接口
	function IdfaRepeat_200($data,$list){
		$file_contents = $this->request_get("https://www.qsdjf.com/api/generalize/idfaRepeat?appid=".$data['appid']."&idfa=".$data['idfa']);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//钱时代理财尊享版 点击对接接口
	function source_200($data,$list){
		

		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = "http://www.qsdjf.com/api/generalize/click?appid=".$data['appid']."&channel=172&idfa=".$data['idfa']."&ip=".$data['ip']."&timestamp=".$data['timestamp']."&sign=".md5($data['timestamp'].'c5824bb722c24b11bd3ab09ca3398a95')."&callback=".$callback;
		$file_contents   = $this->request_get($url);
		
		
		$json  = json_decode($file_contents,true);
		
		if($json["code"]==0){
			 $this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	
	/*览益股市  上报*/
	function submit_697($data,$list){
		$data_al['appid']    = $data['appid'];
		$data_al['idfa']      = $data['idfa'];
		$file_contents = $this->request_post($list['submit_url'],$data_al);

	    $json = json_decode($file_contents,true);
	
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
	
		if(isset($json['code']) && ($json['code']==200 || $json['code']==400)){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//艺龙 排重
	function IdfaRepeat_96($data,$list){
		$url = $list['IdfaRepeat_url'] . "?appid=".$data['appid']."&idfa=".$data['idfa']."&channel=ttlxasoiphone";
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	

	//有惠手机购物   排重
	function IdfaRepeat_10059($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url'].'?idfa='.$data['idfa'].'&appid='.$data['appid']);
		
		$json = json_decode($file_contents,true );
		// echo $file_contents;die;
		//写入log
		
	
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
				
			}else{
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}
	}
	//有惠手机购物   点击
	function source_10059($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$nsign    = md5($data['timestamp'].'2ihijVARY3QAGfT8');
		
		$url = $list['source_url']."?appid=".$data['appid']."&timestamp=".$data['timestamp']."&sign=".$nsign."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback;
		
		$file_contents = file_get_contents($url);
		
		//$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		
		
		//print_r($file_contents);exit;
		if(isset($json['code']) && $json['code']=='0'){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//云端金融理财 点击
	function source_788($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url']."?appid=". $data['appid'] ."&idfa=".$data['idfa']."&callback_url=".$callback."&source=imoney";
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true );
		
		// print_r($file_contents);exit;
		if(isset($json["code"]) && $json["code"]==0){

			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//云端金融理财 排重
	function IdfaRepeat_788($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url'].'?idfa='.$data['idfa']);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}


	//艺龙 点击
	function source_96($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$id = $this->db->insert('aso_source', $data); 
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url = $list['source_url']."&appid=". $data['appid'] ."&idfa=".$data['idfa']."&callbackurl=".$callback;
		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		// print_r($file_contents);exit;
		if(isset($json["success"]) && $json["success"]){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//悦美 排重
	function IdfaRepeat_94($data,$list){
		$url = "http://www.yuemei.com/api/distinct.php";
		$data['bundleid'] = "com.yuemei.kw";
		$data['idfa_list'] = $data['idfa'];
		$file_contents = $this->request_post($url, $data);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	
	//钱时代金服 点击
	function source_104($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$key = "c5824bb722c24b11bd3ab09ca3398a95";
		$csign = md5($data['timestamp'] . $key);//回调地址的sign;
		$url = $list['source_url']."&appid=" .  $data['appid'] . "&idfa=" . $data['idfa']."&ip=".$data['ip'] . "&timestamp=" . $data['timestamp'] . "&sign=" . $csign . "&callback=" . $callback;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		//var_dump($json);
		//写入log

		if(!$json['code'] && isset($json['code'])){//点击成功
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
		
	}

	//钱时代金服 排重 
	function IdfaRepeat_104($data,$list){
		$url = $list['IdfaRepeat_url']."?appid=". $data['appid'] . "&idfa=" . $data['idfa'];
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true );
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);

		if($json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{
			echo  json_encode(array($data['idfa']=>'0'));
		}
		
	}


	//触金 点击
	function source_106($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		
		$url = $list['source_url']."&idfa=" . $data['idfa']."&osVersion=".$data['os'] . "&ip=" . $data['ip'] . "&returnUrl=" . $callback;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );

		
		
		//写入log

		if($json['Error']==0 && isset($json['Error'])){//点击成功
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败 
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
		
	}

	//触金 排重 
	function IdfaRepeat_106($data,$list){
		$url = $list['IdfaRepeat_url']."&idfa=" . $data['idfa']."&ip=". $data['ip'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);

		if(!$json[$data['idfa']]){
				$a = array($data['idfa']=>'1');
				$contents = json_encode($a);
				echo  $contents;
		}else{
			echo  json_encode(array($data['idfa']=>'0'));
		}
		
	}

	//财视 点击
	function source_98($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }

		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$udid="02:00:00:00:00:00";
		$isskip=0;
		$url="http://api.adsofts.cn/ios/api/notice?s=gE0LD3eZhw3LY4/rCHjDTZOQW3z++RUNmi/4EkOCujFBLzX9Ne9m3w=="."&isSkip=".$isskip."&os=".$data['os']."&udid=".$udid."&idfa=".$data['idfa']."&openudid=".null."&callbackurl=".$callback."&clientip=".$data['ip'];

		//$file_contents = file_get_contents($url);

		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );//print_r($json);exit;
		//print_r($file_contents);exit;
		if(isset($json['success']) && $json['message']=="成功"){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//财视 排重
	function IdfaRepeat_98($data,$list){
		$source="wanpu";
		$mac="02:00:00:00:00:00";
		//$url = "https://tgcw.feifeipark.com/index.php/Api/AppStore/check"."?appid=".$data['appid']."&idfa=".$data['idfa'];
		$url="http://chai.api.3g.cnfol.com/index.php?r=Version/RepeatActivate"."&mac=".$mac."&idfa=".$data['idfa']."&source=".$source;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 1){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	// 友邦金储宝理财 点击
	function source_103($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		// $id = $this->db->insert('aso_source', $data);
		// $inid = $this->db->insert_id();
		if(!isset($data['os'])){
			$data['os']='';
		}
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		//$url="http://106.15.36.23:8080/YouBang/SyncServer?type=ClickSync&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback."&adid=".$addid."&channel=".$channelid."&clickKeyword=".$clickKeyword;
		//$file_contents = file_get_contents($url);
		$url=$list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback."&os=".$data['os'];
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );
		//print_r($url);die;
		//print_r($json);exit;
		//print_r($file_contents);exit;
		//var_dump($json);
		if(isset($json['resultCode']) && $json['resultCode']==1000){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	// 友邦 排重
	function IdfaRepeat_103($data,$list){
		$url=$list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&os=&channel=10010";
		$file_contents = $this->request_get($url);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		// print_r($file_contents);die;
		//渠道返回：成功返回0  失败返回1
		//idfa 已存在标识为 1，不存在标识为 0。

		if(isset($json['data']) && $json['data'] == 1) {
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}elseif(isset($json["msg"]) && $json["msg"] == 0) {
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}elseif(isset($json["success"]) && $json["success"] && $json["exist"] == 0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}elseif(isset($json["data"]) && !$json["error_code"] && !$json["data"][$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}elseif(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	
	//友邦 上报
	function submit_103($data,$list){
		$url = $list['submit_url'] . "&idfa=" . $data['idfa'] . "&ip=" . $data['ip'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['resultCode']) && $json['resultCode']==1000){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}else{//失败
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}

	// JC 排重
	function IdfaRepeat_48($data,$list){

		$url = $list['IdfaRepeat_url']."&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		//idfa 已存在标识为 1，不存在标识为 0。
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){

			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{

			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	// JCpopstar 点击
	function source_48($data,$list)
	{
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'] . md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=" . $inid . "&k=" . $data['timestamp'] . "&idfa=" . $data['idfa'] . "&appid=" . $data['appid'] . "&sign=" . $sign;
		$callback = urlencode($callback);

		$url = $list['source_url'] . "&idfa=" . $data['idfa'] . "&callback=" . $callback;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents, true);
		//print_r($file_contents);exit;
		if (isset($json['success']) && $json['success']) {
			$this->db->insert('aso_source', $data);
			echo json_encode(array('code' => '0', 'result' => 'ok'));
			die; //这里返回0代表成功
		} else {
			$data['json'] = $file_contents;
			$this->db->insert('aso_source_log', $data);
			echo json_encode(array('code' => '103', 'result' => 'false'));
			die;
		}
	}
	//有米友商 点击
	function source_101($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$goto=0;
		$mac=null;
		$url = $list['source_url']."&ifa=".$data['idfa']."&mac=".$mac."&ip=".$data['ip']."&goto=".$goto."&callback_url=".$callback;
		//print_r($url);
		$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true);
		//print_r($file_contents);exit;
		if(isset($json['c']) && !$json['c']){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	///有米友商 排重
	function IdfaRepeat_101($data,$list){
		$req = array("appid"=>$data['appid'], "idfa"=>$data['idfa']);
		$file_contents = $this->request_post($list['IdfaRepeat_url'], $req);
		$json = json_decode($file_contents,true);
		//print_r($json);die;
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();

		}
	}
	//有米友商 上报
	function submit_101($data,$list){
		$s="bd6b335329HYycCU2Ti8Nt6Ex_q5Hy99_7f";
		$timestamp=time();
		$url=$list['submit_url'] . "?ifa=" . $data['idfa']."&s=".$s."&timestamp=".$timestamp;
		//print_r($url);die;
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true );
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json['c']==0){//上报成功
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));

		}else{//失败
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
		}
	}
	
	//优信二手车排重
	function IdfaRepeat_102($data,$list){
		$url = $list['IdfaRepeat_url']."?appid=".$data['appid']."&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);

		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
    //优信二手车点击
	function source_102($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$channel = "ayl";
		$url = $list['source_url']."?appid=".$data['appid']."&idfa=".$data['idfa']."&callback=".$callback."&channel=".$channel;
	                                     	$file_contents   = $this->request_get($url);
		$json  = json_decode($file_contents,true );

		if(isset($json['code']) && $json['code']){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//百度地图 排重
	function IdfaRepeat_156($data,$list){
		$url = $list['IdfaRepeat_url']. "&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['msg']) && $json['msg'] == 0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//快乐购 点击
	function source_156($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }

		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);


		$url = $list['source_url'] ."&idfa=".$data['idfa']."&ip=".$data['ip']."&os_version=" .$data['os']. "&mac=020000000000&callback_url=".$callback;

		// $file_contents = file_get_contents($url);

		$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );

		if(isset($json['status']) && $json['status'] == 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//奇客 排重
	function IdfaRepeat_157($data,$list){
		$data['timestamp'] = time();
		$params['user_id'] = "Q0001";
		$params['atime'] = $data['timestamp'];
		$params['appid'] = $data['appid'];
		$params['idfas'] = $data['idfa'];
		// PHP 版本 生成方法
		// 合作方 partner_key， 注意不是 partner
		$partner_key = "XKBP1Oqut0r2LiGV";
		// UNIX TIMESTAMP 最小单位为秒
		$atime = $data['timestamp'];
		// 第三方用户唯一标识，可以为字母与数字组合的字符串。
		$user_id = "Q0001";
		// 生成签名 5afda19c5d65a7a7
		$sign = substr(md5($partner_key.$atime.$user_id), 8, 16);
		$params['sign'] = $sign;
		$file_contents = $this->request_post($list['IdfaRepeat_url'], $params);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['success']) && $json['success'] && !$json['success']['data'][$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//奇客 点击
	function source_157($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		// $callback = urlencode($callback);

		$data['timestamp'] = time();
		$params['user_id'] = "Q0001";
		$params['atime'] = $data['timestamp'];
		$params['appid'] = $data['appid'];
		$params['idfa'] = $data['idfa'];
		$params['ip'] = $data['ip'];
		$params['click_time'] = $data['timestamp'];
		$params['callback'] = $callback;
		// PHP 版本 生成方法
		// 合作方 partner_key， 注意不是 partner
		$partner_key = "XKBP1Oqut0r2LiGV";
		// UNIX TIMESTAMP 最小单位为秒
		$atime = $data['timestamp'];
		// 第三方用户唯一标识，可以为字母与数字组合的字符串。
		$user_id = "Q0001";
		// 生成签名 5afda19c5d65a7a7
		$sign = substr(md5($partner_key.$atime.$user_id), 8, 16);
		$params['sign'] = $sign;
		$file_contents   = $this->request_post($list['source_url'], $params);
		$json  = json_decode($file_contents,true );

		if(isset($json['success']) && $json['success'] == true){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//奇客 上报
	function submit_157($data,$list){
		$data['timestamp'] = time();
		$params['appid'] = $data['appid'];
		$params['idfa'] = $data['idfa'];
		$params['active_time'] = $data['timestamp'];

		$file_contents = $this->request_post($list['submit_url'], $params);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['success']) && $json['success']  == true){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//聚点 排重
	function IdfaRepeat_158($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?adid=217&idfa=".$data['idfa']);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//聚点 点击
	function source_158($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }

		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&mac=02:00:00:00:00:00&ip=".$data['ip']."&callback=".$callback);
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );

		if(isset($json['status']) && $json['status'] == 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//聚点 上报
	function submit_158($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&mac=02:00:00:00:00:00");

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['status']) && $json['status'] == 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	
	//肥牛试玩 排重
	function IdfaRepeat_159($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&IP=".$data["ip"]."&MAC=02:00:00:00:00:00");
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json["status"]) && !$json["status"]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//肥牛试玩 点击
	function source_159($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&tag=".$data['keywords']."&idfa=".$data['idfa']."&MAC=02:00:00:00:00:00&IP=".$data['ip']."&callback_url=".$callback);
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
		
		if(isset($json['status']) && $json['status'] == 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else if(isset($json['code']) && $json['code'] == 1){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//肥牛试玩 上报
	function submit_159($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa'].'&uuid='.$data['idfa'].'&ip='.$data['ip'].'&deviceName=&deviceVersionString=&deviceMac=');

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['status']) && $json['status'] == 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
		 }else if(isset($json['code']) && $json['code'] == 1){
		 		$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();

		 }else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}
	//大树互娱 点击
	function source_59($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		//$callback = urlencode($callback);
		$data_al['source']  = 'apyb';
		$data_al['cip']     = $data['ip'];
		$data_al['ifa']     = $data['idfa'];
		if($data['appid']==1180004118){
			$data_al['media']   = 9737;
			$data_al['adname']  = '最强NBA';
			$data_al['adid']    = 10;
			$data_al['adgameid'] = 107424;
		}else if($data['appid']==1208410827){
			$data_al['media']   = 9721;
			$data_al['adname']  = '三国群英传';
			$data_al['adid']    = 11;
			$data_al['adgameid'] = 107421;
		}
		$data_al['callback'] = $callback;
		
		$url = $list['source_url'];
		
		$file_contents   = $this->request_post($url,$data_al);
		$json  = json_decode($file_contents,true );
		
		if(isset($json['IsSuccess']) && $json['IsSuccess']){
			$this->db->insert('aso_source',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//来赚 排重
	function IdfaRepeat_191($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json["status"]) && !$json["status"]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//来赚 点击
	function source_191($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }

		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback);
		// print_r($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback);exit;
		$json  = json_decode($file_contents,true );

		if(isset($json['success']) && $json['success'] == 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//来赚 上报
	function submit_191($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
	
		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['errorMsg']) && $json['errorMsg'] == "{\"success\":true}"){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	/*久韵积分墙 及贷  点击*/
	function source_160($data,$list){

		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callbackurl=".$callback);
		
		$json = json_decode($file_contents,true);
		
		if($data['appid']==1156410247){
			if(isset($json['code']) && $json['code']==0){
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			}else{
				$data['json'] =$file_contents;
				$this->db->insert('aso_source_log', $data);
				echo  json_encode(array('code'=>'103','result'=>'false'));die;
			}
		}
		if($data['appid']==1298557871){
			if(isset($json['code']) && $json['code']==200){
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			}else{
				$data['json'] =$file_contents;
				$this->db->insert('aso_source_log', $data);
				echo  json_encode(array('code'=>'103','result'=>'false'));die;
			}
		}
		if($file_contents== 1){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	/*久韵积分墙 及贷  排重*/
	function IdfaRepeat_160($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);
		//print_r($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);exit;
		$json = json_decode($file_contents,true);
		//写入log
		
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		if($data['appid']==871095743){
			//渠道返回：成功返回0  失败返回1
			if(isset($json['data'][$data['idfa']]) && $json['data'][$data['idfa']]==0){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			} 


		}
		
		if($data['appid']==1156410247){
			//渠道返回：成功返回0  失败返回1
			
			if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			} 


		} 
		if($data['appid']==1298557871){
			//渠道返回：成功返回0  失败返回1
			if(isset($json['data'][0]['isUse']) && !$json['data'][0]['isUse']){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			} 


		}
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	/*久韵积分墙  及贷 上报*/
	function submit_160($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&mac='02:00:00:00:00:00'");

		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if( $file_contents== 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}
	/*久韵积分墙  点击*/
	function source_161($data,$list){
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callbackurl=".$callback);
		$json     = json_decode($file_contents,true);
		// print_r($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback);exit;
		
//echo $callback;
		if($json['code']==0000){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	/*久韵房多多  排重*/
	function IdfaRepeat_161($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']."&appid=".$data["appid"]."&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f");
		
		// print_r($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json["code"]) && isset($json["data"][$data['idfa']])){
			if(($json["code"]==0000) && $json["data"][$data['idfa']]==0){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			}
		}else{
			if($json[$data['idfa']]==0){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			}
		}
	}
	/*久韵房多多  上报*/
	function submit_161($data,$list){
		$file_contents = $this->request_get($list['submit_url']."?uuid=".$data['idfa']."&appid=".$data['appid']."&deviceName=&deviceVersion=&idfa=".$data['idfa']."&ip=".$data['ip']."&deviceMac=02:00:00:00:00:00&network=&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f");
	    $json = json_decode($file_contents,true);
	
		
		//echo $list['submit_url']."?uuid=".$data['idfa']."&appid=".$data['appid']."&deviceName=ipone4&deviceVersion=4.0.0&idfa=".$data['idfa']."&ip=".$data['ip']."&deviceMac=02:00:00:00:00:00&network=wifi&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f";
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($json['message']== 0000){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//创客 点击
	function source_162($data,$list){
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."?source=aiyingli&idfa=".$data['idfa']."&callback=".$callback."&appid=".$data['appid']);
		
		// print_r($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callback=".$callback);exit;
		$json          = json_decode($file_contents,true);
		
//echo $callback;
		if($json['code']== 0){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//创客 排重
	function IdfaRepeat_162($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']."&appid=".$data["appid"]);
		// print_r($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data["ip"]);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//猎豆 九秀直播 排重
	 function IdfaRepeat_10065($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&cparam=%7B%22source%22%3A%22liedou%22%2C%22appid%22%3A%22717804271%22%2C%22sign%22%3A%22sign%22%7D%20");
		
		//echo $file_contents;die;
		//写入log
		$json   = json_decode($file_contents,true);
		
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json['uniq']==1 && $json['msg']=='success'){
				
				//成功返回
				echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;
			}
	}
	//猎豆 九秀直播 激活
	function submit_10065($data,$list){
		
		$sign          = md5("appBaseId=".$data['appid']."&channelId=20015&idfa=".$data['idfa']."slims267(envied");
		$file_contents = $this->request_get("http://m.liedou.com/batman/external/v2/activeClick?channelId=20015&cparam=%7B%22source%22%3A%22liedou%22%2C%22appid%22%3A%22717804271%22%2C%22sign%22%3A%22sign%22%7D&cpCid=30011&mac=02:00:00:00:00:00&appBaseId=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']."&sign=".$sign);
	    $json = json_decode($file_contents,true);
	
		
		//echo $list['submit_url']."?uuid=".$data['idfa']."&appid=".$data['appid']."&deviceName=ipone4&deviceVersion=4.0.0&idfa=".$data['idfa']."&ip=".$data['ip']."&deviceMac=02:00:00:00:00:00&network=wifi&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f";
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($json['message']== 'success' && $json['code']==0){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}
	

	function source_368($data,$list){
		// $id = $this->db->insert('aso_source', $data); 
		// $inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		$url="http://ejie.iparky.com/api/common/aylcallback".'?appid='.$data['appid'].'&idfa='.$data['idfa'].'&ip='.$data['ip'].'&timestamp='.$data['timestamp'].'&callback='.$callback;
		
        $file_contents = $this->request_get($url);
        // $file_contents = $this->StatusCode($url);
		// echo $file_contents;die;
		$json = json_decode($file_contents,true);

		if($json['code']==0){//点击成功
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
	}
	//钱客 排重
	function IdfaRepeat_248($data,$list){

		$url = $list['IdfaRepeat_url']."&idfas=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['data'][$data['idfa']]) && !$json['data'][$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//钱客 点击
	function source_248($data,$list){
		
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$url=$list['source_url'].'&idfa='.$data['idfa'].'&mac=02:00:00:00:00:00'.'&ip='.$data['ip'].'&os='.$data['os'].'&callbackurl='.$callback;
		//echo $url;
        $file_contents = $this->request_get($url);
        // $file_contents = $this->StatusCode($url);
		
		$json = json_decode($file_contents,true);
		//var_dump($json);
		if(isset($json['code']) && $json['code']==0){//点击成功
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die;
				
			}else{//失败 
				$data['json']=$file_contents;
				$this->db->insert('aso_source_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
				die;
			}
	}


	//钱客 激活
	function submit_248($data,$list){
		$url = $list['submit_url'] . "&appleid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip'];;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status'] == "true"){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	/*行者天下  点击*/
	function source_359($data,$list){
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
	
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&client_ip=".$data['ip']."&callback=".$callback);
			//echo $list['source_url']."&idfa=".$data['idfa']."&client_ip=".$data['ip']."&callback=".$callback;
		$json     = json_decode($file_contents,true);
		
		if($json['success']){
			// $this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	/*行者天下  排重*/
	function IdfaRepeat_359($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	/*行者天下  上报*/
	function submit_359($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&client_ip=".$data['ip']);

	    $json = json_decode($file_contents,true);
	
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($json['result']== 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//唔哩 点击
	function source_876($data,$list){
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		$callback = urlencode($callback);

		$url=$list['source_url'].'&idfa='.$data['idfa'].'&callback='.$callback;
		
        $file_contents = $this->request_get($url);
        // $file_contents = $this->StatusCode($url);
		
		$json = json_decode($file_contents,true);
	
		if($file_contents=="success"){//点击成功
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			// $data['json']=$file_contents;
			// $this->db->insert('aso_source_log',$data);
		}else{//失败 
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	/*唔哩  排重*/
	function IdfaRepeat_876($data,$list){
		$file_contents = $this->request_get($list['IdfaRepeat_url']."idfa=".$data['idfa']);
		//echo $list['IdfaRepeat_url']."&idfa=".$data['idfa'];
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		//var_dump($json);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}


	
	

	//卓泰点击
	function source_60($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url'] ."?mid=20031&appid=".$data['appid']."&idfa=".$data['idfa']."&clkip=".$data['ip']."&idfamd5=".md5($data['idfa'])."&clktime=".$data['timestamp']."&callbackurl=".$callback;
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if($json["code"]==200){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//卓泰排重
	function IdfaRepeat_60($data,$list){
		//$data['timestamp'] = time();
		$res['idfa']       = $data['idfa'];
		$res['appid']      = $data['appid'];
		$file_contents = $this->request_post($list['IdfaRepeat_url'],$res);
		//echo $list['IdfaRepeat_url']."&idfa=".$data['idfa'];
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		//var_dump($json);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}
	//卓泰激活上报
	function submit_60($data,$list){
		$file_contents = $this->request_get($list['submit_url']."?appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);

	    $json = json_decode($file_contents,true);
	
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($json['status']== 1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//有为互动 排重
	function IdfaRepeat_66($data,$list){
		if($data['appid']==486744917){
			$data['appid']=200;
		}
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&appid='.$data['appid']);
		
		$json = json_decode($file_contents,true);
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		

		
		//渠道返回：成功返回0  失败返回1
		if(isset($json['status']) && $json['status']==20000 && $json['result']['allowed']){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//有为互动 点击
	function source_66($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		if($data['appid']==486744917){
			$data['appid']=200;
		}
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		 $url = $list['source_url']."&appid=".$data['appid']."&model=".$data['device']."&idfa=".$data['idfa']."&ip=".$data['ip']."&sysVer=".$data['os']."&word=".urlencode('新闻')."&callbackUrl=".$callback;
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['status']) && $json['status']==20000 && $json['result']['success']){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//有为互动 上报
	function submit_66($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&appid=".$data['appid'].'&mac=02:00:00:00:00:00&ip='.$data['ip'].'&word='.urlencode($data['keywords']);
		//echo $url;
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//var_dump($json);
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['status']) && $json['status']==20000 && $json['result']['success']){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	

	

	//指还王 排重
	function IdfaRepeat_74($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']);
		
		$json = json_decode($file_contents,true);
		
		//var_dump($json);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		

		
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//指还王 注册回调
	function source_74($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);

		$url = $list['source_url'] ."&idfa=".$data['idfa']."&callbackUrl=".$callback;
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['returnCode']) && $json['returnCode']=='success'){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}


	/*掌贝网易新闻  排重*/
	function IdfaRepeat_597($data,$list){
		$file_contents = file_get_contents($list['IdfaRepeat_url'].'&idfa='.$data['idfa']."&ip=".$data['ip']);
		// print_r($file_contents);exit;
		// $json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		//var_dump($json);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
 		$pos = strpos($file_contents, "0");
        if ($pos !== false){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//掌贝网易新闻 点击
	function source_597($data,$list){
		$data['timestamp'] = time();
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		$callback = urlencode($callback);

		$url=$list['source_url'].'&clientip='.$data['ip'].'&IDFA='.$data['idfa'].'&callback_url='.$callback;
		
        $file_contents = file_get_contents($url);
        // $file_contents = $this->StatusCode($url);
		// $json = json_decode($file_contents,true);

 		$pos = strpos($file_contents, "1");
        if ($pos !== false){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			// $data['json']=$file_contents;
			// $this->db->insert('aso_source_log',$data);
		}else{//失败 
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	//掌贝网易新闻 激活上报
	function submit_597($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);
		
		$json =explode('|',$file_contents);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if($json[0]=='1'){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}



	
	/*聚鹏  排重*/
	function IdfaRepeat_9656($data,$list){
		$url = $list['IdfaRepeat_url']."&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		//var_dump($json);
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1

        if (isset($json[$data['idfa']]) && $json[$data['idfa']] == 0){
			//成功返回
			echo  json_encode(array($data['idfa']=>'1'));//这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0');//我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;
		}
	}

	//聚鹏 点击
	function source_9656($data,$list){
		$data['timestamp'] = time();
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;

		$callback = urlencode($callback);

		$url = $list['source_url']."&clientip=".$data['ip']."&idfa=".$data['idfa'];

        $file_contents = $this->request_get($url);
// print_r($file_contents);exit;
        // $file_contents = $this->StatusCode($url);
		$json = json_decode($file_contents,true);

        if (isset($json['success']) && $json['success']){
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
			// $data['json']=$file_contents;
			// $this->db->insert('aso_source_log',$data);
		}else{//失败 
			$data['json']=$file_contents;
			$this->db->insert('aso_source_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));//这里返回1代表成功
			die;
		}
	}

	/*聚鹏  上报*/
	function submit_9656($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&clientIp=".$data['ip']);

	    $json = json_decode($file_contents,true);
	
		
		//echo $list['submit_url']."?uuid=".$data['idfa']."&appid=".$data['appid']."&deviceName=ipone4&deviceVersion=4.0.0&idfa=".$data['idfa']."&ip=".$data['ip']."&deviceMac=02:00:00:00:00:00&network=wifi&secretKey=NQiMpTaKdMp6cFSdWHoqvY7tPzU0t58f";
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['success']) &&  $json['success']){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//米连科技 排重
	function IdfaRepeat_10010($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appid=".$data['appid']."&idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//米连科技 点击
	function source_10010($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."?idfa=".$data['idfa']."&appid=".$data['appid']."&timestamp=".$data['timestamp']."ip=".$data['ip']."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
		
		if(isset($json['code']) && $json['code'] == 0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//中华万年历  排重
	function IdfaRepeat_10011($data,$list){

		$data_al['app_key']   = 99817882;
		$data_al['devices'][] = $data['idfa'];

		$fields               = json_encode($data_al);
	

		$file_contents = $this->request_post2($list['IdfaRepeat_url'],$fields);
		$json = json_decode($file_contents,true );
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['data'][0]) && $json['data'][0] == $data['idfa']){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//钱庄理财 
	function source_10012($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

	     $this->request_get($list['source_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&source=apyb&devicename=".$data['device']."&sysversion=".$data['os']."&clickip=".$data['ip']."&callback=".$callback);

	
		$this->db->insert('aso_source', $data);
		echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		
	}

	//nice  排重
	function IdfaRepeat_10013($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url'].'?appid='.$data['appid'].'&idfa='.$data['idfa']);
		$json = json_decode($file_contents,true );
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 1){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//nice  点击
	function source_10013($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."?idfa=".$data['idfa']."&appid=".$data['appid']."&timestamp=".$data['timestamp']."&ip=".$data['ip']."&callback=".$callback);
		
		
		$json  = json_decode($file_contents,true );
		
		if(isset($json['code']) && $json['code'] == 0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//nice  上报
	function submit_10013($data,$list){
		$file_contents = $this->request_get($list['submit_url']."?idfa=".$data['idfa']."&appid=".$data['appid']);

	    $json = json_decode($file_contents,true);
	
		
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['result']) &&  $json['result']){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//龙域映客 排重
	function IdfaRepeat_10014($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['data'][$data['idfa']]) && !$json['data'][$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//龙域映客 点击
	function source_10014($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['code']) && $json['code'] == 0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//车位 排重
	function IdfaRepeat_10015($data,$list){
		$url = "https://tgcw.feifeipark.com/index.php/Api/AppStore/check"."?appid=".$data['appid']."&idfa=".$data['idfa'];
		$file_contents = $this->request_get($url);

		$json = json_decode($file_contents,true);
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());

		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']] == 1){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//车位 点击
	function source_10015($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;
		// }
		// echo 1;exit;
		$id = $this->db->insert('aso_source', $data);
		$inid = $this->db->insert_id();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=".$inid."&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$timestamp = time();
		$sign=md5($timestamp."f18500b0e62a32e491c59ccd053c546d");
		$url ="https://tgcw.feifeipark.com/index.php/Api/AppStore/click"."?idfa=".$data['idfa']."&callback=".$callback."&sign=".$sign."&appid=".$data['appid']."&timestamp=".$timestamp."&ip=".$data['ip'];
		$file_contents = file_get_contents($url);
		//$file_contents   = $this->request_get($url);

		$json  = json_decode($file_contents,true );
		//print_r($file_contents);exit;
		if(isset($json['code']) && !$json['code']){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//真龙霸业  点击跳转接口
	function source_10071($data,$list){
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		//http://c.aidgo.cn/aidgo/third/click?channel=8211feed20f43cf8&advid=152395488242303&appname={5}&clickid={9}&idfa={2}&mac=&osVersion={6}&ip={3}&callback={7}
		$callback_url    = 'http://asoapi.appubang.com/api/MycAdvert?k='.$data['timestamp'].'&idfa='.$data['idfa'].'&appid='.$data['appid'].'&sign='.$sign;
		$callback        = urlencode($callback_url);
		$file_contents   = $this->request_get($list['source_url']."?channel=8211feed20f43cf8&advid=152395488242303&idfa=".$data['idfa']."&ip=".$data['ip'].'&clickid='.$data['timestamp'].'&osVersion='.$data['os'].'&appname='.urlencode('聚告').'&callback='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
		
		
		if(isset($json['status']) && $json['status']==200){
			//$data['session_id']   = $json['data']['token'];
			$this->db->insert('aso_source_myc', $data);
			header("location:https://itunes.apple.com/cn/app/id1119087595?mt=8");
			//echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}

		
	}
	//真龙霸业 回调接口
	function MycAdvert(){
		
	   $rearr = $_GET;

       $sql   = "select callback,appid,cpid,idfa from aso_source_myc where appid = {$rearr['appid']} and idfa = '{$rearr['idfa']}'";

	   $res   = $this->db->query($sql);

	   $list  = $res->row_array();//查出来的是厂商app的信息

	   if(empty($list)){
	   		echo json_encode(array('code'=>99,'message'=>'source not exist'));die;
	   }

	   if($rearr['sign'] != md5($rearr['k'].md5('callback'))){
	   		echo json_encode(array('code'=>101,'message'=>'sign error'));die;
	   }
	   $result['appid']       = $list['appid'];
	   $result['idfa']        = $list['idfa'];
	   $result['cpid']        = $list['cpid'];
	   $result['timestamp']   = time();
	   $result['type']        = 2;

	   $url = $list['callback'];
	   $pos = strrpos($list['callback'], "imoney.one");
		if($pos !== 0) {
			$url = str_replace("imoney.one", "eimoney.com", $list['callback']);
		}
		
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true );


		
	    $this->db->insert('aso_submit2',$result);
			
		mysql_close();
		echo json_encode(array('code'=>100,'message'=>'success'));die;
              
			
	}

	
	//对面 排重
	function IdfaRepeat_10017($data,$list){

		$data_al['appid'] = $data['appid'];
		$data_al['source'] = 'imoney';
		$data_al['time']   = time();
		$data_al['idfa']   = $data['idfa'];

		

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//对面 点击
	function source_10017($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&app=".$data['appid']."&mac=02:00:00:00:00:00&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['success']) && $json['success']=="true"){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//塔防 排重
	function IdfaRepeat_10018($data,$list){
		$url           = $list['IdfaRepeat_url']==''?'http://111.230.78.86:8080/interface/distinct':$list['IdfaRepeat_url'];
		
		$file_contents = $this->request_get($url."?idfa=".$data['idfa'].'&appid='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else if(isset($json['result'][$data['idfa']]) && $json['result'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//钱小咖点击
	function source_10018($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&muid=".$data['idfa']."&sign=".md5($data['idfa'].'727c0248e68c597c')."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['status']) && $json['status']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//塔防 上报
	function submit_10018($data,$list){

		$data_al['appid']  = $data['appid'];
		$data_al['idfa']   = $data['idfa'];
		$data_al['source'] = 'aipuyoubang';
		$data_al['sign']   = md5('aipuyoubang|'.$data['appid'].'|'.$data['idfa'].'|'.'m5f5nohv4mk5gz2pa7h9hr5qrd9mv14b');

		$url           = $list['submit_url']==''?'http://111.230.78.86:8080/interface/active':$list['submit_url'];
		$json_data         = json_encode($data_al);
		$file_contents = $this->request_post2($url,$json_data);

	    $json = json_decode($file_contents,true);
	
		
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if(isset($json['err_code']) &&  $json['err_code']==0){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}
	//健康猫
	function IdfaRepeat_10019($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code']) && $json['code']==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//猎聘 排重
	function IdfaRepeat_10020($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&appid=".$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
	
		//渠道返回：成功返回0  失败返回1
		
		if(isset($json['data'][$data['idfa']]) && $json['data'][$data['idfa']]=='0'){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//猎聘 点击
	function source_10020($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = file_get_contents($list['source_url']."&idfa=".$data['idfa']."&appid=".$data['appid']."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
	
		if(isset($json['success']) && $json['success']=='true'){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//应用猿 排重
	function IdfaRepeat_10021($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&ip='.$data['ip']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//应用猿 点击
	function source_10021($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&callbackurl=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
	
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//应用猿 上报
	function submit_10021($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&appid=".$data['appid'].'&ip='.$data['ip'].'&device=iphone');

	    $json = json_decode($file_contents,true);
	
	
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['code']) &&  $json['code']==0){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//点点时代 排重
	function IdfaRepeat_10022($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&ip='.$data['ip']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//点点时代 排重 点击
	function source_10022($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&mac=&os=".$data['os']."&devicemodel=".$data['device']."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['success']) && $json['success']==1){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//点点时代 排重 上报
	function submit_10022($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&mac=&ip=".$data['ip'].'&os=iphone');

	    $json = json_decode($file_contents,true);
	
	
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['success']) && $json['success']==1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//易服数创 排重
	function IdfaRepeat_10023($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//易服数创 排重 点击
	function source_10023($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&kword=".$data['keywords']."&ip=".$data['ip'].'&callback='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
	
		if(isset($json['success']) && $json['success']==1){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//易服数创 排重 上报
	function submit_10023($data,$list){
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);

	    $json = json_decode($file_contents,true);
	
	
	
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['success']) && $json['success']==1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}


	//你我贷理财 点击回调
	function source_10024($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&appid=".$data['appid']."&client_ip=".$data['ip'].'&idfa='.$data['idfa'].'&mac=02:00:00:00:00:00&callback='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['code']) && $json['code']==200){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//掘金股票 排重
	function IdfaRepeat_10025($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appId=".$data['appid']."&idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==1){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//掘金股票
	function source_10025($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."?appId=".$data['appid']."&ip=".$data['ip'].'&idfa='.$data['idfa'].'&callback='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
	
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//天天快报 排重
	function IdfaRepeat_10026($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appId=".$data['appid']."&idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	
	//天天快报 点击
	function source_10026($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&appid=".$data['appid']."&ip=".$data['ip'].'&idfa='.$data['idfa']."&report_type=1&click_time=".$data['timestamp'].'&callback='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
	
		if(isset($json['ret']) && $json['ret']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//人人贷借款  排重
	function IdfaRepeat_10027($data,$list){

		$file_contents = $this->request_get($list['IdfaRepeat_url']."&apple_id=".$data['appid']."&idfas=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['data']['idfas']) && $json['data']['idfas'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	
	//人人贷借款  点击
	function source_10027($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&ip=".$data['ip'].'&idfa='.$data['idfa']."&click_time=".$data['timestamp'].'&notify_url='.$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['errno']) && $json['errno']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//最美应用 排重
	function IdfaRepeat_10028($data,$list){


		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&app_id='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if( $json['result'] && $json['data'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//最美应用 点击
	function source_10028($data,$list){
		
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']."&app_id=".$data['appid']."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['result']) && $json['result']==1){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//百福金榜我爱卡 排重
	function IdfaRepeat_10029($data,$list){


		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&appid='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==1){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//百福金榜我爱卡 点击
	function source_10029($data,$list){
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$nsign     = md5($data['timestamp'].'59n5w5h2ky26i5avbb7f2mopjy9x1thi');
		$file_contents   = $this->request_get($list['source_url']."?idfa=".$data['idfa']."&ip=".$data['ip']."&appid=".$data['appid']."&timestamp=".$data['timestamp']."&sign=".$nsign."&callback=".$callback);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//58新接口
	function source_10030($data,$list){
		
		// $id = $this->db->insert('aso_source', $data); 
		// $inid = $this->db->insert_id();
		$callback = "http://asoapi.appubang.com/api/aso_advert/?back_id=&k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".md5($data['timestamp'].md5('callback'));
		$res= "[{'ost':3,'uuid':'{$data['idfa']}','osn':'{$data['device']}','osv':'{$data['os']}','ip':'{$data['ip']}','callback':'{$callback}'}]";
		// $res[]['ost']      = 3;
		// $res[]['uuid']     = $data['idfa'];
		// $res[]['osn']      = $data['device'];
		// $res[]['osv']      = $data['os'];
		// $res[]['ip']       = $data['ip'];
		// $res[]['callback'] = $callback;
		
	   list($t1, $t2) = explode(' ', microtime());
       $datetime      = explode('.',$t2 . '.' .  ceil( ($t1 * 1000) ))[0].explode('.',$t2 . '.' .  ceil( ($t1 * 1000) ))[1];

		$data_al['params']  = urlencode($res);
		//var_dump(json_encode($res));
       //$data_al['params']  = json_encode($res);
		if($data['appid']==1147166510){
			$data_al['app']     = '58citynet_aipuyoubang';
		}else if($data['appid']==1169404447){
			$data_al['app']     = 'citynet_aipuyoubang';
		}else{
			$data_al['app']     = 'aipuyoubang';
		}
		
		$data_al['ts']		= $datetime;
		//模拟get
		
		$file_contents      = $this->request_post('http://appces.58.com/notice',$data_al);

		//var_dump($data_al);
		// echo $data_al['ts'];
		// var_dump($res);
		$json               = json_decode($file_contents,true);
		
		
		if($json['code']==0 && !empty($json['data']) && $json['data'][0]['status']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; //这里返回1代表成功
		}
	}

	//卓因 腾讯新闻  channel=dongxintonga
	function source_10031($data,$list){
		
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		$file_contents   = $this->request_get($list['source_url']."&appid=".$data['appid']."&idfa=".$data['idfa'].'&callback='.$callback);
		$json            = json_decode($file_contents,true);
		
		
		if($data['appid']==494520120){
			if($json['result']=='ok'){
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			}else{
				$data['json'] =$file_contents;
				$this->db->insert('aso_source_log', $data);
				echo  json_encode(array('code'=>'103','result'=>'false'));die;
			}
		}

		if($file_contents==01){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//卓因 排重
	function IdfaRepeat_10031($data,$list){


		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa'].'&appid='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//卓因 腾讯新闻 channel=dongxintong
	function source_10032($data,$list){
		$data['timestamp']  = time();
		
		$file_contents   = $this->request_get($list['source_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);

		if($file_contents==01){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//尚邻 排重
	function IdfaRepeat_10033($data,$list){
		$data_al['adid']   = explode('=',$list['IdfaRepeat_url'])[1];
		$data_al['appid']  = $data['appid'];
		$data_al['idfas']  = $data['idfa'];
		$data_al['sign']   = md5('adid='.explode('=',$list['IdfaRepeat_url'])[1].'&appid='.$data['appid'].'&idfas='.$data['idfa'].'&d41d8cd98f00b204e9800998ecf8427e');
		

		$file_contents = $this->request_post(explode('?',$list['IdfaRepeat_url'])[0],$data_al);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if($json['code']==0 && $json['data'][$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//尚邻 点击
	function source_10033($data,$list){
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		$data_al['adid']       = explode('=',$list['IdfaRepeat_url'])[1];
		$data_al['appid']      = $data['appid'];
		$data_al['idfa']      = $data['idfa'];
		$data_al['ip']         = $data['ip'];
		$data_al['notify_url'] = $callback;
		$data_al['sign']       = md5('adid='.explode('=',$list['source_url'])[1].'&appid='.$data['appid'].'&idfa='.$data['idfa'].'&ip='.$data['ip'].'&notify_url='.$callback.'&d41d8cd98f00b204e9800998ecf8427e');

		

		
		$file_contents   = $this->request_post(explode('?',$list['source_url'])[0],$data_al);
		
		// print_r($file_contents);exit;
		$json  = json_decode($file_contents,true );
	
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//赚客 排重
	function IdfaRepeat_10034($data,$list){


		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['repeat']) && $json['repeat']==1){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//赚客 激活上报
	function submit_10034($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&taskip=".$data['ip'];
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	//坦克大作战联盟  channel=dongxintonga
	function source_10035($data,$list){
		
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		$file_contents   = $this->request_get($list['source_url']."?appid=".$data['appid'].'&timestamp='.$data['timestamp'].'&sign='.md5($data['timestamp'].'93dfbc6f1e2645ab99dd7a686d9e3183')."&idfa=".$data['idfa'].'&callback='.$callback);
		$json            = json_decode($file_contents,true);
		
		

		
		if($json['success']=='true' && $json['message']=='ok'){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//坦克大作战联盟  排重
	function IdfaRepeat_10035($data,$list){


		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa'].'&appid='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($data['idfa']) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//雪球 点击 
	function source_10037($data,$list){
		
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		$file_contents   = $this->request_get($list['source_url'].'?idfa='.$data['idfa'].'&channel=xianhou&callback='.$callback);
		
		$json            = json_decode($file_contents,true);
		
		
		
		if(isset($json['success']) && $json['success']){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}
	//雪球   排重
	function IdfaRepeat_10037($data,$list){

		$data_al['idfa']    = $data['idfa'];
		$data_al['appid']   = $data['appid'];

		$file_contents = $this->request_post($list['IdfaRepeat_url'],$data_al);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]=='0'){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//艺龙 点击 
	function source_10038($data,$list){
		
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa'];

		//echo $callback = urlencode($callback);
		
		$file_contents   = $this->request_get($list['source_url'].'?idfa='.$data['idfa'].'&appname=com.elongjd2.travel&channel=imoneyios1&appid='.$data['appid'].'&callback='.$callback);
		

		
		$json            = json_decode($file_contents,true);
		
		
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//艺龙 排重
	function IdfaRepeat_10038($data,$list){

	
		$file_contents = $this->request_get($list['IdfaRepeat_url'].'&udids='.$data['idfa']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]=='0'){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}

	//谢总 排重

	function IdfaRepeat_10039($data,$list){
		if($data['appid']==463150061){
			$params['idfa']        = $data['idfa'];
			$params['app_id']      = $data['appid'];
			$params['time_stamp']  = time();
			$params['app_secret']  = 'xt3Z4rHLOyjqp2';
			$params['source']      = 'fenmei';
			ksort($params);

			$data_al  = implode('',$params);
			$sign     = md5($data_al);

			$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa'].'&app_id='.$data['appid'].'&source=fenmei&time_stamp='.time().'&sign='.$sign);
			
			// print_r($file_contents);exit;
			$json = json_decode($file_contents,true);
			
			
			//写入log
			$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
			
			$this->db->insert('aso_IdfaRepeat_log',$log);
			//渠道返回：成功返回0  失败返回1
			if(isset($json['body'][$data['idfa']]) && $json['body'][$data['idfa']]==1){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			}

		 }else{

		 	$params['idfa']        = $data['idfa'];
			$params['appid']       = $data['appid'];
			$params['time']        = time();
			
			$params['sign']        = md5(explode('?',$list['IdfaRepeat_url'])[1].time());
			$params['source']      = explode('?',$list['IdfaRepeat_url'])[1];

			$file_contents = $this->request_post($list['IdfaRepeat_url'],$params);
			
			// print_r($file_contents);exit;
			$json = json_decode($file_contents,true);
			
			
			//写入log
			$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
			
			$this->db->insert('aso_IdfaRepeat_log',$log);
			//渠道返回：成功返回0  失败返回1
			if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
				echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
			}else{
				$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
				$contents = json_encode($a);
				echo  $contents;exit();
			}


		 }
	}
	//谢总 点击
	function source_10039($data,$list){
		if($data['appid']==1308869647){
			$data['timestamp']  = time();
			$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
			$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
			$callback = urlencode($callback);
			
			$file_contents   = $this->request_get($list['source_url'].'&idfa='.$data['idfa'].'&app='.$data['appid'].'&callback='.$callback);
			
			$json            = json_decode($file_contents,true);
			
			
			
			if(isset($json['success']) && $json['success']){
				$this->db->insert('aso_source', $data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
			}else{
				$data['json'] =$file_contents;
				$this->db->insert('aso_source_log', $data);
				echo  json_encode(array('code'=>'103','result'=>'false'));die;
			}

		 }
	}
	//掌阅 激活上报
	function submit_10039($data,$list){
		$params['idfa']        = $data['idfa'];
		$params['app_id']      = $data['appid'];
		$params['time_stamp']  = time();
		$params['app_secret']  = 'xt3Z4rHLOyjqp2';
		$params['source']      = 'fenmei';
		ksort($params);

		$data_al  = implode('',$params);
		$sign     = md5($data_al);
		$file_contents = $this->request_get($list['submit_url']."&idfa=".$data['idfa']."&app_id=".$data['appid'].'&source=fenmei&time_stamp='.time().'&sign='.$sign);

	    $json = json_decode($file_contents,true);
	
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['body']['success']) && $json['body']['success']==1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	

	//应用喵 排重
	function IdfaRepeat_10040($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//应用喵 点击 
	function source_10040($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		$url = $list['source_url'] ."&idfa=".$data['idfa']."&keyword=".$data['keywords']."&ip=".$data['ip'].'&devicemodel='.$data['device'].'&callbackurl='.$callback;
		
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['success']) && $json["success"]){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//应用喵 上报 
	function submit_10040($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip'];
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		if(isset($json['success']) && $json['success']){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//代理 	侯红亮 排重

	function IdfaRepeat_10041($data,$list){
		
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa'].'&appid='.$data['appid']);
	
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['status']) && $json['status']==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//代理 	侯红亮 激活上报
	function submit_10041($data,$list){
		
		$file_contents = $this->request_get($list['submit_url']."?idfa=".$data['idfa']."&appid=".$data['appid']);

	    $json = json_decode($file_contents,true);
	
		
	
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['status']) && $json['status']==1){//上报成功
				$data['timestamp']=time();
				$data['type'] = 1;
				$this->db->insert('aso_submit2',$data);
				echo  json_encode(array('code'=>'0','result'=>'ok'));exit();
				
			}else{//失败 
				unset($data['ip']);
				$data['timestamp']=time();
				$data['type'] = 1;
				$data['json'] =$file_contents;
				$this->db->insert('aso_submit_log',$data);
				echo  json_encode(array('code'=>'103','result'=>'false'));exit();//这里返回1代表成功
			}
	}

	//直客 袁艳萍 排重 
	function IdfaRepeat_10042($data,$list){
		
		if($data['appid']==1076275012){
			$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa'].'&appid='.$data['appid']);
		}else{

			$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfas=".$data['idfa'].'&appid='.$data['appid'].'&client_ip='.$data['ip']);
		}
		// print_r($file_contents);exit;
		$json = json_decode($file_contents,true);
		
		//写入log
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		
		$this->db->insert('aso_IdfaRepeat_log',$log);
		//渠道返回：成功返回0  失败返回1
		if(isset($json['code']) && $json['code']==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));exit(); //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;exit();
		}
	}
	//直客 袁艳萍 点击 
	function source_10042($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);

		if($data['appid']==1076275012){
			$url = $list['source_url'] ."&idfa=".$data['idfa']."&appid=".$data['appid']."&ip=".$data['ip']."&timestamp=".$data['timestamp'].'&sign='.md5($data['timestamp'].'522aa4e0eff61267d3921a8881a29e92').'&callback='.$callback;
		}else{

			$url = $list['source_url'] ."&idfa=".$data['idfa']."&appid=".$data['appid']."&client_ip=".$data['ip'].'&callback='.$callback;
		}
		
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json["code"]==1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//直客 袁艳萍 激活上报
	function submit_10042($data,$list){
		$idfa          = $data['idfa'];

		$Tlast         = substr($idfa,strlen($idfa)- 1,1);

		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);

		if($Tlast==1 || $Tlast==2){
			$data['timestamp']=time();
			$data['type'] = 0;
			$this->db->insert('aso_gnh_submit',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));
		}else{
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_gnh_submit',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));
		}
		
	}

	//易赚 排重
	function IdfaRepeat_10043($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if($json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//易赚 点击 
	function source_10043($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		$callback = urlencode($callback);
		
		$url = $list['source_url'] ."&idfa=".$data['idfa']."&kid=".$data['keywords']."&ip=".$data['ip'].'&mac='.$data['device'].'&callback='.$callback;
		$file_contents   = $this->request_get($url);
		
		$json  = json_decode($file_contents,true);
		
		if(isset($json['success']) && $json["success"]){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	//易赚 上报 
	function submit_10043($data,$list){
		$url = $list['submit_url']."&idfa=".$data['idfa']."&ip=".$data['ip'].'&mac=';
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['success']) && $json['success']){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}


	//果推 排重
	function IdfaRepeat_10044($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?idfa=".$data['idfa']."&appid=".$data['appid']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]==0){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	///果推 点击 
	function source_10044($data,$list){
		
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		
		$url = $list['source_url'] ."&idfa=".$data['idfa']."&appid=".$data['appid']."&ip=".$data['ip'].'&k='.$data['timestamp'].'&callback='.$sign;
		
		$file_contents   = $this->request_get($url);
		
		// $json  = json_decode($file_contents,true);
		
		if(is_numeric($file_contents) && $file_contents==1){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//qq炫舞
	function source_10045($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);
		$url = $list['source_url'] ."&idfa=".$data['idfa']."&time=".$data['timestamp']."&cip=".$data['ip'].'&callback='.$callback;
		
		$file_contents   = $this->request_get($url);
		
		// $json  = json_decode($file_contents,true);
		
		if($file_contents=='yes'){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//蚂蚁小咖 排重
	function IdfaRepeat_10046($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json['status']) && $json['status']==1){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//蚂蚁小咖 激活上报
	function submit_10046($data,$list){
		$url = $list['submit_url']."&appid=".$data['appid']."&idfa=".$data['idfa']."&ip=".$data['ip'].'&os='.'&keyword='.urlencode($data['keywords']);
		
		$file_contents = $this->request_get($url);
		
		$json = json_decode($file_contents,true);
		
		unset($data['sign'],$data['ip'],$data['reqtype'],$data['isbreak'],$data['device'],$data['os'],$data['callback']);
		
		if(isset($json['status']) && $json['status']){
			$data['timestamp']=time();
			$data['type'] = 1;
			$this->db->insert('aso_submit2',$data);
			echo  json_encode(array('code'=>'0','result'=>'ok'));die;
		}else{//失败 
			$data['timestamp']=time();
			$data['type'] = 1;
			$data['json'] =$file_contents;
			$this->db->insert('aso_submit_log',$data);
			echo  json_encode(array('code'=>'103','result'=>'false'));die;
		}
	}

	// 17k 点击
	function source_10047($data,$list){
		// if(empty($data['callback'])){
		// 	echo'{"resultCode":-1,"errorMsg":"callback empty"}'; die;  
		// }
		$data['timestamp']  = time();
		$sign = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		
		$callback = urlencode($callback);
		$url = $list['source_url'] ."?appid=".$data['appid']."&idfa=".$data['idfa']."&sign=".md5($data['timestamp'].'b598d2fc01db077c8cf6e8c6752c818a')."&timestamp=".$data['timestamp']."&ip=".$data['ip'].'&callback='.$callback;
		
		$file_contents   = $this->request_get($url);
		
		 $json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//17k 排重
	function IdfaRepeat_10047($data,$list){
		
		$file_contents = $this->request_get($list['IdfaRepeat_url']."?appid=".$data['appid']."&idfa=".$data['idfa']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && $json[$data['idfa']]=='1'){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//啪啪彩票 排重
	function IdfaRepeat_10048($data,$list){
		$data_al['idfa']   = $data['idfa'];
		$file_contents     = $this->request_post($list['IdfaRepeat_url'],$data_al);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	//U盟  排重
	function IdfaRepeat_10073($data,$list){
		
		$file_contents     = $this->request_get($list['IdfaRepeat_url'].'?appid='.$data['appid'].'&idfa='.$data['idfa']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}

	function source_10073($data,$list){
		$data['timestamp']  = time();
		$sign     = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		

		$nsign    = md5('appid='.$data['appid'].'&ch=abby.sy@AIYILI&idfa='.$data['idfa'].'&pburl='.$callback.'&time='.$data['timestamp'].'344e95cdff');
		//echo 'appid='.$data['appid'].'&ch=abby.sy@AIYILI&idfa='.$data['idfa'].'&phurl='.$callback.'&time='.$data['timestamp'].'344e95cdff';

		$callback = urlencode($callback);
		$url      = $list['source_url'] ."?ch=abby.sy@AIYILI&idfa=".$data['idfa']."&sign=".$nsign."&time=".$data['timestamp']."&appid=".$data['appid'].'&pburl='.$callback;

		
		//echo $nsign;
		$file_contents   = $this->request_get($url);
		
		 $json  = json_decode($file_contents,true);
		
		if(isset($json['status']) && $json['status']==200){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}

	//小白 排重
	function IdfaRepeat_10100($data,$list){
		
		$file_contents     = $this->request_get($list['IdfaRepeat_url'].'?appid='.$data['appid'].'&idfa='.$data['idfa'].'&timestamp='.$data['timestamp']);
		
		$json = json_decode($file_contents,true);
		
		//写入logecho 
		$log=array('cpid'=>$data['cpid'],'appid'=>$data['appid'],'adid'=>$data['adid'],'idfa'=>$data['idfa'],'json'=>$file_contents ,'date'=>time());
		$this->db->insert('aso_IdfaRepeat_log',$log);
		
		
		//渠道返回：成功返回0  失败返回1
		if(isset($json[$data['idfa']]) && !$json[$data['idfa']]){
			echo  json_encode(array($data['idfa']=>'1'));die; //这里返回1代表成功
		}else{
			$a = array($data['idfa']=>'0'); //我们这里返回0代表失败不可做任务
			$contents = json_encode($a);
			echo  $contents;die;
		}
	}
	//小白  点击
	function source_10100($data,$list){
		$data['timestamp']  = time();
		$sign     = md5($data['timestamp'].md5('callback'));//回调地址的sign;
		$callback = "http://asoapi.appubang.com/api/aso_advert/?k=".$data['timestamp']."&idfa=".$data['idfa']."&appid=".$data['appid']."&sign=".$sign;
		

		$nsign    = md5('appid='.$data['appid'].'&callback='.$callback.'&idfa='.$data['idfa'].'&source=AYLKJ&timestamp='.$data['timestamp'].'5AEFC74B3336952B84FB82BD');
		//echo 'appid='.$data['appid'].'&ch=abby.sy@AIYILI&idfa='.$data['idfa'].'&phurl='.$callback.'&time='.$data['timestamp'].'344e95cdff';

		$callback = urlencode($callback);
		$url      = $list['source_url'] .'?appid='.$data['appid'].'&idfa='.$data['idfa']."&sign=".$nsign."&timestamp=".$data['timestamp'].'&source=AYLKJ'.'&callback='.$callback;

		 
		//echo $nsign;
		$file_contents   = $this->request_get($url);
		
		 $json  = json_decode($file_contents,true);
		
		if(isset($json['code']) && $json['code']==0){
			$this->db->insert('aso_source', $data); 
			echo  json_encode(array('code'=>'0','result'=>'ok'));die; //这里返回0代表成功
		}else{
			$data['json'] =$file_contents;
			$this->db->insert('aso_source_log', $data); 
			echo  json_encode(array('code'=>'103','result'=>'false'));die; 
		}
	}
	
	

	/**************************************** 接口结束**********************************************/
	/********************************** 自定义函数开始*********************************************/
	/**
     * 赶集构成加密串
     * @param  array $params 参数
     * @return string
     */
    static function buildParamStr( $params ) {
        ksort( $params );
        $paramStr = '';
        foreach ( $params as $key => $value ) {
            if ( $key == 'signature' ) {
                continue;
            }
            $paramStr .= sprintf( '%s=%s&', $key, $value );
        }

        return rtrim( $paramStr, '&' );
	}




	/**
     * 模拟post进行url请求
     * @param string $url
     * @param array $post_data
     */
    function request_post($url = '', $post_data = array()) {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        
        $o = "";
        foreach ( $post_data as $k => $v ) 
        { 
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

     /**
     * 模拟post进行url请求
     * @param string $url
     * @param 以json流传递
     */
    function request_post2($url = '', $post_data = '') {
       
        $ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS,$post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    'Content-Type: application/json;charset=utf-8',
		    'Content-Length: ' . strlen($post_data))
		);
 
		$result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    function request_get($url,$info=array()){

    	// 创建一个cURL资源
		$ch  =  curl_init ();

		// 设置URL和相应的选项
		curl_setopt ( $ch ,  CURLOPT_URL ,$url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ( $ch ,  CURLOPT_HEADER ,  0 );
		curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS,5000);
		//curl_setopt($ch, CURLOPT_TIMEOUT,1); 
		
		// 抓取URL并把它传递给浏览器
		$data = curl_exec($ch);//运行curl

		$curl_errno = curl_errno($ch);  
        $curl_error = curl_error($ch);


	    curl_close($ch);
	 
	   //判断是否请求超时 允许最大请求时间2秒
	    if($curl_errno >0){  
	    		//记录超时日志 并输出错误
	    		if($url!=''){
    					$info['url']      = $url;
	    		        $info['message']  = $curl_error;
	    		        $info['date']     = gmdate('Y-m-d H:i:s',time());
    					$this->db->insert('aso_timeout_log',$info);
    			}
	    		
               echo $data = json_encode(array("code"=>99,"message"=>$curl_error)); die;
        }
	     return $data;
		// 关闭cURL资源，并且释放系统资源
		// curl_close ( $ch );

    }
    function request_get1($url){
    	// 创建一个cURL资源
		$ch  =  curl_init ();

		// 设置URL和相应的选项
		curl_setopt ( $ch ,  CURLOPT_URL ,$url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ( $ch ,  CURLOPT_HEADER ,  0 );
		curl_setopt( $ch ,CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']); // 模拟浏览器请求
		// 抓取URL并把它传递给浏览器
		$data = curl_exec($ch);//运行curl
	    curl_close($ch);
	     return $data;

		// 关闭cURL资源，并且释放系统资源
		// curl_close ( $ch );

    }

    function request_get2($url, $headers){
    	// 创建一个cURL资源
		$ch  =  curl_init ();
		$headerArr = array(); 
		foreach( $headers as $n => $v ) { 
		    $headerArr[] = $n .':' . $v;  
		}
		// 设置URL和相应的选项
		curl_setopt ( $ch ,  CURLOPT_URL ,$url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
		// 抓取URL并把它传递给浏览器
		$data = curl_exec($ch);//运行curl
	    curl_close($ch);
	     return $data;

		// 关闭cURL资源，并且释放系统资源
		// curl_close ( $ch );

    }

    	/**
	 * RSA私钥签名
	 * 
	 * @param $signdata: 待签名字符串      	     	
	 */
	function sign($signdata,$AppStoreId){
	if($AppStoreId==1176078944){
	  $appPrivateKey="-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAOJmBoc5nidFw2rH
Gxljo5bCsKMA5yksj2lb9pykxwXgy7Jgeow8yIKF9ZO7TvDWdvYWYYqejZRPmFcD
siYtC+aSGmDuXjimIEYAuaXRpg/eySqVkB7e/bCGwRqXI5jen2vsXSZIQsfOgIYw
A2IIbLNEluMlicse1ClbcDaiq30zAgMBAAECgYBIad24ruM5KIVCyACQ9F/Evu0E
litZ7hjI2FNe8w19gdNlcJqB9IclyHcuE4FCYzaVq77zOZeLUpIlctcugsYFFb63
17/KbUrqKIdI3P++0b4kzeOGu8B7F4/hpS/9Y43aXFQzHpn+/7XBHRCScs5mpmAQ
CLjQn6AAdkJE5gCfUQJBAPMQoHyBGMyyUyl77/CPhs0w1mVTk/Lr5FaIKTHzFmcT
Rm5887GSLu16IiwQQFTUQEhIXtRjmw3qUZH02gRfXN8CQQDuclhjT5QZjbU/Usv7
BSWdoxf6409bpzhqamnfGZE/8M20mw6G/sl21vJtsKb/R1cCYT7240uWfKlTxKXE
sxYtAkBczzx4TdLqVizq6ifz8tnF/5/dkMwtNWU6pUMVj3w+X13FUnC6nNbOVpQ1
vv7RZTomX3vWHTJXXeFHmfalNMSBAkEA5Z65nVE59m2vd7587ktjkO1JH3Kcrk9X
FatKLu0JIgD7pwuWrstXKRkPNjBicPy7PnB1WP1DgjSkPyXk2In5NQJBANIuQKAX
N+P8I2P9usBgQzwGubaxcZFNuXoi0xWTp20/2FT35g8U9ziCIcmw2tq08/B3dmZT
RiZIVKcD0VZknZc=
-----END PRIVATE KEY-----";
	}else if($AppStoreId==1104239394){
		  $appPrivateKey="-----BEGIN PRIVATE KEY----- 
MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAIeBa1z6EeL3dkTC6Y
81VaiNK59Rqq7vJQLAKlzVBRKNWNX0OsP4fnU9Qukg9LNdfSejfz6iSIFbsRJfs3HO
Rds153+/fQmoDelaCkjbj0jiwdyFQE03tfUWXZMIJToSBBr8U5uzMD/s1Ef3gdUL9Y
EPUHo+k6kf2+qB9pyMTbRjAgMBAAECgYBTohQiuZFKlVNgkzBWHCP3ONJArcX73Evq
i7JZw3wy/BxlSSzwATIDqEDg5F9DSSNS0L1bagv4EyCR55E4X4iLIO0APfceVCGtLP
V2PfCHTflgXN7JXEYWEYhhrT9FyEL2D4frBu1thezCPQ1kGspFiwPXcgjvs0lJO0Tk
YOfbMQJBANKrX6eDNCfciQ/CCZ/2I25sjXJi5CBpuYb9do2zEaxnpShf6uuDpwSo01
VN65JI6pYqHzHrDSeI4Xm5AuI4ZrsCQQCkqa2160nGV89YwKQ4sDhbGGp1IJjmSjLq
yB64mlpSm2A/2R44pkjTCro/O56j+xpMGq0CnlUsQoatbpUQhBJ5AkB7pj6UkXvRUa
3Y4+jGTK/rJie3VbfUFnngc3BcJxhees8DbZjy9ujW4Uh5LyzvRYD69mos4GtuIvdE
fITmxnf1AkBSC9Xpcm6VLMW9FGf/cxbxlQ3ehLqK7OfIAqUEGKzuwkrIJZggZAKfXZ
YF0eAvFvw4dYZFar1Hy3It0o7l5tkJAkADcbrKca2QyJlUq/q7oIgzM1bR/dZvOfj/
P2r71Gzx9fMr06xK92FhKR5mQe511C7IezP5IUoB4b0y0xQmOZQ/
-----END PRIVATE KEY-----";
	}else if($AppStoreId==983488107){
		  $appPrivateKey="-----BEGIN PRIVATE KEY----- 
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAM7denwTRPdolkuCZL/g
4zo6lcdzCsDxc9zgNSh7yJapkxMrAiqkw25ij0KJvJDBXCTMui11xqTkJpga2I078NqY
IDMXe3p2X+dEXEmQaPD35c2CLjss5UygG0CNGNfRyg4uIACPqXuhaeTvrjBAM8N7E7rg
9oYE5LZxaAyVaSZDAgMBAAECgYAKJeVPVuaoOHI/DAuDOjYLcjpMyYD6jB3B9SHGdaQW
eAUmCJMXonOP47fhbL5aX5H0oDJ17nQrPKIEDjUXYJxlFjSGX9GabXMcgtEx7C07gFg+
SnRfjqM794EzjKXSVxe9BTEoohH1NoMrO3QM22AanpIAxDBfEFX7uEAkgbyTCQJBAPcu
0Q1HUFxXJky6uHi97jnrydYvXs58gyegrY+c8NMRbgN0Rp6Pg8faR1PwZCHIWmuAtn2t
cdQBi30npW+hLpcCQQDWPn7B++2pKQF4fZignRoG2v0qoPrdbjutGUQXknAKGgAK9xEx
ynpmeE4rw4F5hAP19uwBu1K4pbEG5SuESqc1AkAHS1xj9ezLLM82iHQVLBWxo+Gq7m7v
zQDZ1IYKrOj2cZc7htzmpPmQlkJwmbF6xbzVW1EHWGz5gqopIVhiePE1AkA/6c7o0d45
i7kbl+RTbeqYxvWlpPaR3lPBNPtiSNZRvSXsH36qquvO6+7uEVnrxV1lIC+R6K8p1Iw2
MWHFCnxNAkEA1ZTD/bBq0Ri+SSLFkviKmaRdsR4iiuWzvXAhhaoupeUeJnXPzW1M1K3/
3hviRzZeLMZnr+Mio+cw8prXhpPs5w==
-----END PRIVATE KEY-----";
	}
	  // $aaaa=preg_replace("/\s/", ' ', $appPrivateKey);
	    if(openssl_sign($signdata,$sign,$appPrivateKey));
	        $sign = base64_encode($sign);
	        //echo $appPrivateKey;
	    return $sign;
	}
		/**
	 * 排序Request至待签名字符串
	 *
	 * @param $request: json格式Request
	 */
	function sortToSign($request){
	    $obj = json_decode($request);
	    $arr = array();
	    foreach ($obj as $key=>$value){
	        if(is_array($value)){
	            continue;
	        }else{
	            $arr[$key] = $value;
	        }
	    }
	    ksort($arr);
	    $str = "";
	    foreach ($arr as $key => $value){
	        $str = $str.strtolower($key).$value;
	    }
	    //$str = strtolower($str);
	    return $str;
	}

	
	// 包装好的发送请求函数
	function SendRequest($url,$request,$appId,$AppStoreId){
		$curl = curl_init ($url);
		
		$timestamp = gmdate ( "Y-m-d H:i:s", time ()); // UTC format
		$timestap_sign = $this->sign($appId. $timestamp,$AppStoreId);
		$requestSignStr = $this->sortToSign($request);
		$request_sign = $this->sign($requestSignStr,$AppStoreId);
		//echo $requestSignStr;
		$header = array ();
		$header [] = 'Content-Type:application/json;charset=UTF-8';
		$header [] = 'X-PPD-TIMESTAMP:' . $timestamp;
		$header [] = 'X-PPD-TIMESTAMP-SIGN:' . $timestap_sign;
		$header [] = 'X-PPD-APPID:' . $appId;
		$header [] = 'X-PPD-SIGN:' . $request_sign;
		
		curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header );
		curl_setopt ( $curl, CURLOPT_POST, 1 );
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, $request );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec ( $curl );
		curl_close ( $curl );
	//         $j = json_decode ( $result, true );
		//var_dump($result);
		return $result;
	}

	function getSignature($str, $key) {  
	    $signature = "";  
	    if (function_exists('hash_hmac')) {  
	        $signature = base64_encode(hash_hmac("sha1", $str, $key, true));  
	    } else {  
	        $blocksize = 64;  
	        $hashfunc = 'sha1';  
	        if (strlen($key) > $blocksize) {  
	            $key = pack('H*', $hashfunc($key));  
	        }  
	        $key = str_pad($key, $blocksize, chr(0x00));  
	        $ipad = str_repeat(chr(0x36), $blocksize);  
	        $opad = str_repeat(chr(0x5c), $blocksize);  
	        $hmac = pack(  
	                'H*', $hashfunc(  
	                        ($key ^ $opad) . pack(  
	                                'H*', $hashfunc(  
	                                        ($key ^ $ipad) . $str  
	                                )  
	                        )  
	                )  
	        );  
	        $signature = base64_encode($hmac);  
	    }  
	    return $signature;  
   }
   
    function GetHttpStatusCode($url){   
        $curl = curl_init();  
        curl_setopt($curl,CURLOPT_URL,$url);//获取内容url  
        curl_setopt($curl,CURLOPT_HEADER,1);//获取http头信息  
        curl_setopt($curl,CURLOPT_NOBODY,1);//不返回html的body信息  
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);//返回数据流，不直接输出  
        curl_setopt($curl,CURLOPT_TIMEOUT,30); //超时时长，单位秒 
        curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36"); 
        curl_exec($curl);  
        $rtn= curl_getinfo($curl,CURLINFO_HTTP_CODE);  
        curl_close($curl);  
        return  $rtn;  
    }
    function StatusCode($url){   
        $curl = curl_init();  
        curl_setopt($curl,CURLOPT_URL,$url);//获取内容url  
        curl_setopt($curl,CURLOPT_HEADER,1);//获取http头信息 
        // curl_setopt($curl,CURLOPT_FOLLOWLOCATION,1); 
      
        curl_setopt($curl,CURLOPT_NOBODY,1);//不返回html的body信息  
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);//返回数据流，不直接输出  
        curl_setopt($curl,CURLOPT_TIMEOUT,30); //超时时长，单位秒  
        curl_setopt($curl,CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.134 Safari/537.36");
        // curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
        // curl_setopt($curl,CURLOPT_FOLLOWLOCATION,1); 
        $a=curl_exec($curl); 
        echo $a;die; 
        // $rtn= curl_getinfo($curl,CURLINFO_HTTP_CODE);  //获取http状态码
        $rtn= curl_getinfo($curl,CURLINFO_EFFECTIVE_URL);  //获取http状态码
        curl_close($curl);  
        return  $rtn;  
    }
    //获取当前请求url
    function curPageURL() 
	{
	    $pageURL = 'http';

	    // if ($_SERVER["HTTPS"] == "on") 
	    // {
	    //     $pageURL .= "s";
	    // }
	    $pageURL .= "://";

	    if ($_SERVER["SERVER_PORT"] != "80") 
	    {
	        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
	    } 
	    else 
	    {
	        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	    }
	    return $pageURL;
	}
	

   

	/** 
	 *  将URL中的参数取出来放到数组里
	 * 
	 * @param    string    query 
	 * @return    array    params 
	 */ 
	function convertUrlQuery($url)
	{ 
		$arr = parse_url($url);
		$query = $arr['query'];
	    $queryParts = explode('&', $query); 
	    
	    $params = array(); 
	    foreach ($queryParts as $param) 
		{ 
	        $item = explode('=', $param); 
	        $params[$item[0]] = $item[1]; 
	    } 
	    
	    return $params; 
	}

	//判断是否是移动端请求
	private function isMobile()  
	{   
	    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备  
	    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))  
	    {  
	        return true;  
	    }   
	    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息  
	    if (isset ($_SERVER['HTTP_VIA']))  
	    {   
	        // 找不到为flase,否则为true  
	        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;  
	    }   
	    // 脑残法，判断手机发送的客户端标志,兼容性有待提高  
	    if (isset ($_SERVER['HTTP_USER_AGENT']))  
	    {  
	        $clientkeywords = array ('nokia',  
	            'sony',  
	            'ericsson',  
	            'mot',  
	            'samsung',  
	            'htc',  
	            'sgh',  
	            'lg',  
	            'sharp',  
	            'sie-',  
	            'philips',  
	            'panasonic',  
	            'alcatel',  
	            'lenovo',  
	            'iphone',  
	            'ipod',  
	            'blackberry',  
	            'meizu',  
	            'android',  
	            'netfront',  
	            'symbian',  
	            'ucweb',  
	            'windowsce',  
	            'palm',  
	            'operamini',  
	            'operamobi',  
	            'openwave',  
	            'nexusone',  
	            'cldc',  
	            'midp',  
	            'wap',  
	            'mobile'  
	        );   

	        // 从HTTP_USER_AGENT中查找手机浏览器的关键字  
	        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))  
	        {  
	            return true;  
	        }   
	    }   
	    // 协议法，因为有可能不准确，放到最后判断  
	    if (isset ($_SERVER['HTTP_ACCEPT']))  
	    {   
	        // 如果只支持wml并且不支持html那一定是移动设备  
	        // 如果支持wml和html但是wml在html之前则是移动设备  
	        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))  
	        {  
	            return true;  
	        }   
	    }   

	    return false;  
	}   

}


/********************************** 自定义函数结束*********************************************/
Class Crypt{
	const CIPHER = MCRYPT_RIJNDAEL_128;//算法
	const MODE = MCRYPT_MODE_CBC;//模式
	//俄罗斯方块
	/**
	* 加密 默认为 AES 128 CBC
	* @param string $key 密钥
	* @param string $plaintext 需加密的字符串
	* @param string $cipher 算法
	* @param string $mode 模式
	* @return binary
	*/
	static  public  function encrypt($key, $plaintext, $cipher = self::CIPHER, $mode = self::MODE ) {
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER, self::MODE),MCRYPT_RAND);
		$padding = 16 - (strlen($plaintext) % 16);
		$plaintext .= str_repeat(chr($padding), $padding);
		$ciphertext = mcrypt_encrypt(self::CIPHER, $key, $plaintext, self::MODE, $iv);
		$ciphertext = $iv . $ciphertext;
		return $ciphertext;
	}
}

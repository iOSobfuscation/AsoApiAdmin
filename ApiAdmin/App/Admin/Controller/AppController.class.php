<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;

class AppController extends CommonController{
		private $connect    = 'mysql://root:ttttottttomysql@101.200.91.203/aso_db';

		private $db_prefix  = 'aso_';

		public function   app_list(){

			
			$app_id        = intval($_REQUEST['appid']);
			$appid         = intval($_REQUEST['appid'])!=null?"and a.appid = $app_id":'';
			$cpid          = intval($_REQUEST['cpid'])!=null?"and a.cpid = {$_REQUEST['cpid']}":'';
			$channels       = intval($_REQUEST['channel'])!=null?"and a.channel = {$_REQUEST['channel']}":'';
			$salesman      = intval($_REQUEST['salesman'])!=null?"and a.salesman = {$_REQUEST['salesman']}":'';
			$AppName       = trim($_REQUEST['app_name'])!=null?"and a.app_name like '%{$_REQUEST['app_name']}%'":'';

			if($_REQUEST['start_time'] !=null && $_REQUEST['end_time']!=null){
				   $start_time    = intval(strtotime($_REQUEST['start_time']));
					// $start_time    = 1506700800;
					// $end_time      = 1507564800;
				    $end_time      = intval(strtotime($_REQUEST['end_time'])+3600);
				    $stimes        = $_REQUEST['start_time'];
				    $etimes        = $_REQUEST['end_time'];

				    $time          = "and a.create_time between $start_time and $end_time";
				} 
			$where         = "a.app_name != ''";
			
			


			$app       = M('advert',$this->db_prefix,$this->connect);
			$channel   = M('source_cpid',$this->db_prefix,$this->connect);
			$sales     = M('sales',$this->db_prefix,$this->connect);
			$count     = $app->alias('a')->where("$where $appid $AppName $cpid $channels $salesman $time")->count();
			$NowPage   = isset($_GET['page'])?intval($_GET['page']):1;

			$EveryPage = 10;
			$MaxPage   = ceil($count/$EveryPage);
			$MinPage   = 1;
			if($NowPage>=$MaxPage){
				$NowPage = $MaxPage;
			}
			if($NowPage<=$MinPage){
				$NowPage  =1;
			}

			$data           = $app->alias('a')->where("$where $appid $AppName $cpid $channels $salesman $time")->join('left join aso_sales as s on s.sales_id = a.salesman left join aso_source_cpid as ac on ac.cpid=a.channel ')->field('a.id,a.appid,a.cpid,a.app_name,a.IdfaRepeat_url,a.submit_url,a.source_url,a.is_disable,a.channel,a.salesman,a.create_time,a.is_repeat,a.is_source,a.is_submit,a.is_advert,s.sales_name,ac.name')->limit($EveryPage*($NowPage-1),10)->select();
			
			$channel_list     = $channel->order('id asc')->select();
			$sales_list       = $sales->order('sales_id asc')->select();
			$this->assign('channel_id',$_REQUEST['channel']);
			$this->assign('salesman_id',$_REQUEST['salesman']);
			$this->assign('channel',$channel_list);
			$this->assign('salesman',$sales_list);
			$this->assign('count',$count);
			$this->assign('MaxPage',$MaxPage);
			$this->assign('NowPage',$NowPage);
			$this->assign('cpid',$_REQUEST['cpid']);
			$this->assign('start_time',$_REQUEST['start_time']);
			$this->assign('end_time',$_REQUEST['end_time']);
			$this->assign('appid',$_REQUEST['appid']);
			$this->assign('app_name',trim($_REQUEST['app_name']));
			$this->assign('data',$data);
			$this->display('app_list');

		}



		/*单选题修改操作*/
	public function edit(){
			$app                   = M('advert',$this->db_prefix,$this->connect);
			$sales                 = M('sales',$this->db_prefix,$this->connect);
			$so_cp                 = M('source_cpid',$this->db_prefix,$this->connect);
			$res                   = $_POST['data'];
		if(IS_POST){
			$id                    = intval($res['id']);
			$data['appid']         = intval($res['appid']);
			$data['cpid']          = intval($res['cpid']);
			
			$data['salesman']      = intval($res['salesman']);
			$data['channel']       = intval($res['channel']);
			
			
			
			$data['app_name']      = $res['app_name'];
			$data['api_cat']       = intval($res['api_cat']);
			$data['key']           = trim($res['key']);
			
			if(isset($res['is_repeat'])){
					$data['is_repeat']=1;
			}else{
				$data['is_repeat']=0;
			}
			if(isset($res['is_source'])){
				$data['is_source']=1;
			}else{
				$data['is_source']=0;
			}
			if(isset($res['is_submit'])){
				$data['is_submit']=1;
			}else{
				$data['is_submit']=0;
			}
			if(isset($res['is_advert'])){
				$data['is_advert']=1;
			}else{
				$data['is_advert']=0;
			}
			// $data['app_name']      = trim($res['app_name']);
			$info                  = $app->where("id = $id")->save($data);
			
			if($info){
				echo 0;
			}else{
				echo 99;
			}
		}else{
			
			$id               = intval($_GET['id']);
			$where['id']      = $id;

			$data             = $app->where($where)->field('id,appid,app_name,cpid,is_repeat,is_source,is_submit,is_advert,api_cat,key,channel,salesman')->find();
			$sales_list     = $sales->order('sales_id asc')->select();
			$channel_list   = $so_cp->order('id asc')->select();
			$this->assign('channel',$channel_list);
			$this->assign('sales',$sales_list);
			$this->assign('data',$data);
			$this->display('app_info');
			
		}
	}
	/*接口复制*/
	public function app_copy(){
		$app                   = M('advert',$this->db_prefix,$this->connect);
		$appid          = intval($_GET['appid']);

		$adid           = intval($_GET['adid']);

		$data           = $app->where(array('appid'=>$appid,'cpid'=>$adid))->find();

		unset($data['id']);
		unset($data['is_disable']);
		$data['is_disable']  = '1';
		$info          = $app->add($data);

		if($info){
			echo json_encode(array('code'=>100,'data'=>$data));
		}else{
			echo json_encode(array('code'=>99,'data'=>''));
		}
		

	}

	public function app_edit(){
			$app                   = M('advert',$this->db_prefix,$this->connect);

			$res                   = $_POST['data'];
		if(IS_POST){
			
			$appid                 = intval($res['id']);
			if($res['type']=='repeat'){
				$data['IdfaRepeat_url']  = trim($res['url']);

				$data['repeat_value']    = $res['params'].'%'.$res['code'].'%'.$res['method'].'%'.$res['ktype'];
			}else if($res['type']=='source'){
				$data['source_url']      = trim($res['url']);

				$data['source_value']    = $res['params'].'%'.$res['code'].'%'.$res['method'].'%'.$res['ktype'];
			}else{
				$data['submit_url']      = trim($res['url']);

				$data['submit_value']    = $res['params'].'%'.$res['code'].'%'.$res['method'].'%'.$res['ktype'];
			}
			// $data['app_name']      = trim($res['app_name']);
			$info                  = $app->where("id = $appid")->save($data);
			
			if($info){
				echo 0;
			}else{
				echo 99;
			}
		}else{
			
			$id               = intval($_GET['id']);
			$where['id']      = $id;
			$type             = $_GET['type'];

			if($type=='repeat'){
				$data             = $app->where($where)->field('id,IdfaRepeat_url,repeat_value,api_cat')->find();
				$data['url']      = trim($data['IdfaRepeat_url']);
				$data['value']    = $data['repeat_value'];
			}else if($type=='source'){
				$data             = $app->where($where)->field('id,source_url,source_value,api_cat')->find();
				$data['url']      = trim($data['source_url']);
				$data['value']    = $data['source_value'];
			}else{
				$data             = $app->where($where)->field('id,submit_url,submit_value,api_cat')->find();
				$data['url']      = trim($data['submit_url']);
				$data['value']    = $data['submit_value'];
			}
			$data['type']         = $type;
			$data['method']       = explode('%',$data['value'])[2]==1?'get':'post';
			$data['params']       = explode('%',$data['value'])[0];
			$data['code']         = explode('%',$data['value'])[1];
			$data['ktype']        = explode('%',$data['value'])[3];
			$this->assign('data',$data);
			$this->display('api_info');
			
		}
	}

	public function app_ceshi(){

		$appid          = intval($_GET['appid']);

		$adid           = intval($_GET['adid']);
		$time           = time();
		$idfa           = $this->randomkeys(8).'-'.$this->randomkeys(4).'-'.$this->randomkeys(4).'-'.$this->randomkeys(4).'-'.$this->randomkeys(12);
		$repeat_status  = file_get_contents("http://asoapi.appubang.com/api/aso_IdfaRepeat/cpid/666/?adid=$adid&appid=$appid&idfa=$idfa&ip=183.206.164.114");

		$source_status  = file_get_contents("http://asoapi.appubang.com/api/aso_source/cpid/666/?adid={$adid}&appid={$appid}&idfa={$idfa}&ip=183.196.168.138&timestamp=$time&reqtype=0&device=iphone&os=9.3.2&isbreak=0&callback=http%3A%2F%2Fwww.imoney.one%2Fdiamonds%2Fcallback%2FintegralWall%2Fios%2Faipu%3Fsnuid%3DHU11BkmaxtMExwNu3zjiQCFwl2YxxMXAa4Gjs1JHNPaIS3fYJd5cCF0dht5rRpzuZsLd_icOLg64MJofbCsX3YrUgiLADKH654gpDCZXhyV7UxEkZgChuYL0oJDyGUDhyKHHGuwf00d0uOkwC5toGg&sign=539327b3d8452ff9639c4b03cb09be27");

		$submit_status     = file_get_contents("http://asoapi.appubang.com/api/aso_Submit/cpid/666/?adid={$adid}&appid={$appid}&idfa={$idfa}&timestamp=$time&ip=58.214.177.114&sign=e3662ccb8d8220588b660094e891e953");
		
		echo json_encode(array('repeat'=>$repeat_status,'sourcet'=>$source_status,'submit'=>$submit_status));
	}

	public function check_cpid(){
		$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$cpid = intval($_GET['cpid']);

		$data = $advert->where("cpid = $cpid")->find();

		if($cpid != 1 && $data){
			echo 1;
		}else{
			echo 0;
		}
	}

	public function app_add(){
			$app                   = M('advert',$this->db_prefix,$this->connect);
			$sales                 = M('sales',$this->db_prefix,$this->connect);
			$so_cp                 = M('source_cpid',$this->db_prefix,$this->connect);
			$res                   = $_POST['data'];
			//$res                   = $_POST;
		if(IS_POST){
			
			$App_Id                = $res['appid'];
			
			$AppIds                = explode(',',$App_Id);
			
			foreach($AppIds as $k=>$val){
				$data['appid']         = intval($val);
				$data['cpid']          = intval($res['cpid']);
				$data['source_url']    = trim($res['source_url']);
				$data['salesman']      = $res['salesman'];
				$data['channel']       = $res['channel'];
				$data['IdfaRepeat_url']= trim($res['IdfaRepeat_url']);
				$data['create_time']   = time();
				$data['submit_url']    = trim($res['submit_url']);
				$data['app_name']      = $res['app_name'];
				$data['api_cat']       = intval($res['api_cat']);
				$data['key']           = trim($res['key']);

				if(isset($res['is_repeat'])){
					$data['is_repeat']=1;
				}
				if(isset($res['is_source'])){
					$data['is_source']=1;
				}
				if(isset($res['is_submit'])){
					$data['is_submit']=1;
				}
				if(isset($res['is_advert'])){
					$data['is_advert']=1;
				}
				$info                  = $app->add($data);

			}
			
			
			if($info){
				//echo 0;
				echo $app->_sql();
			}else{
				echo 99;
			}
		}else{
			$sales_list     = $sales->order('sales_id asc')->select();
			$channel_list   = $so_cp->order('id asc')->select();
			$this->assign('channel',$channel_list);
			$this->assign('sales',$sales_list);
			
			$this->assign('type','add');
			$this->display('app_info');
			
		}
	}

	public function idfa_export(){

		$channel    = M('source_cpid','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$data       = $channel->field('name,cpid')->select();
		$appid      = intval($_GET['appid']);
		$adid       = intval($_GET['adid']);

		$this->assign('appid',$appid);
		$this->assign('adid',$adid);

		$this->assign('channel',$data);
		$this->display('idfa_export');
	}

	public function ad_idfa_export(){
		$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$source    = M('source','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$submit    = M('submit2','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$nsubmit   = M('submit','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$model     = M('','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$appid     = intval($_POST['appid']);
		$adid      = intval($_POST['adid']);
		$cpid      = intval($_POST['source']);
		$start_time    = intval(strtotime($_POST['start_time']));
	    $end_time      = intval(strtotime($_POST['end_time'])+3600);

	    $app_name  = $advert->alias('a')->where("a.appid = $appid and a.cpid=$adid")->getField('a.app_name');
	   
	  	if($adid==1){
	  		if($cpid==1){
	  		$data = $model->table('aso_submit e,aso_source s')->field("s.appid,s.idfa,s.keywords,s.ip,FROM_UNIXTIME(max(s.timestamp)) as stime,FROM_UNIXTIME(max(e.timestamp)) as etime")->where("s.idfa=e.idfa and s.appid=$appid and s.timestamp between $start_time and $end_time and e.timestamp between $start_time and $end_time and e.appid=$appid")->group('s.idfa')->select();

	  		

	  		}else{
	  			$data = $model->table('aso_submit e,aso_source s')->field("s.appid,s.idfa,s.keywords,s.ip,FROM_UNIXTIME(max(s.timestamp)) as stime,FROM_UNIXTIME(max(e.timestamp)) as etime")->where("s.idfa=e.idfa and s.cpid=$cpid and e.cpid=$cpid and s.appid=$appid and s.timestamp between $start_time and $end_time and e.timestamp between $start_time and $end_time and e.appid=$appid")->group('s.idfa')->select();

	  		}
	  		
	  	}else{
	  		if($cpid==1){
	  		// $data = $model->table('aso_submit2 e,aso_source s')->field("s.appid,s.idfa,s.keywords,s.ip,FROM_UNIXTIME(max(s.timestamp)) as stime,FROM_UNIXTIME(max(e.timestamp)) as etime")->where("s.idfa=e.idfa and s.appid=$appid and s.timestamp between $start_time and $end_time and e.timestamp between $start_time and $end_time and e.appid=$appid")->group('s.idfa')->select();

	  		$data=$source->alias('s')->join('left join aso_submit2 as e on s.idfa=e.idfa')->field("s.appid,s.idfa,s.keywords,s.ip,FROM_UNIXTIME(max(s.timestamp)) as stime,FROM_UNIXTIME(max(e.timestamp)) as etime")->where("s.appid=$appid and s.timestamp between $start_time and $end_time and e.timestamp between $start_time and $end_time and e.appid=$appid")->group('s.idfa')->select();
	  		}else{
	  			$data = $model->table('aso_submit2 e,aso_source s')->field("s.appid,s.idfa,s.keywords,s.ip,FROM_UNIXTIME(max(s.timestamp)) as stime,FROM_UNIXTIME(max(e.timestamp)) as etime")->where("s.idfa=e.idfa and s.cpid=$cpid and e.cpid=$cpid and s.appid=$appid and s.timestamp between $start_time and $end_time and e.timestamp between $start_time and $end_time and e.appid=$appid")->group('s.idfa')->select();
	  		}
	  	}


	  	   $xlsName  = $_POST['start_time'].'到'.$_POST['end_time'].$app_name.'idfa任务信息';
                  $xlsCell  = array(
                array('appid','AppID'),

                
                array('idfa','设备唯一标识'),
                 array('keywords','关键词'),
                 array('ip','用户ip'),
				array('stime','点击时间(领取任务)'),
				array('etime','完成时间（回调时间）'),
				
            );
            $file_name    = date('m-d',$start_time).'到'.date('m-d',$end_time).$app_name.'数据';
			
            
            exportExcel_data($xlsName,$xlsCell,$data,$file_name);
	}

	public function app_del(){
		$app              = M('advert',$this->db_prefix,$this->connect);
		$id               = intval($_GET['id']);
		$where['id']      = $id;

		$data             = $app->where($where)->delete();

		
	}

	public function app_disable(){
		$app                 = M('advert',$this->db_prefix,$this->connect);
		$id                  = intval($_GET['id']);
		$where['id']         = $id;
		$data['is_disable']  = intval($_GET['type'])  ==1?'1':'0';
		$app->where($where)->save($data);

		
	}

	public function check(){
		if($_POST['app_name']==null){
				echo 2;
				exit;
			}else{
				echo 0;
				exit;
			}
	}

	public  function randomkeys($length)   
	{   
	   $pattern = '1234567890ABCDEFGHIJKLOMNOPQRSTUVWXYZ';  
	    for($i=0;$i<$length;$i++)   
	    {   
	        $key .= $pattern{mt_rand(0,35)};    //生成php随机数   
	    }   
	    return $key;   
	}   
 

}
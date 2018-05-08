<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\SubjectModel;
use Admin\Model\CommonModel;
use Admin\Model\IdfaRepeatModel;
class TaskController extends CommonController{
	private $CommonModel;
	private $SubjectModel;
	
	
	public function task_info(){
			set_time_limit(0);
			if(IS_POST){
				
				$appid         = intval($_POST['appid']);
				
				if($_POST['start_time'] !=null && $_POST['end_time']!=null){
				   $start_time    = intval(strtotime($_POST['start_time']));
					// $start_time    = 1506700800;
					// $end_time      = 1507564800;
				    $end_time      = intval(strtotime($_POST['end_time'])+3600);
				    $stimes        = $_POST['start_time'];
				    $etimes        = $_POST['end_time'];

				    $time          = "and a.timestamp between $start_time and $end_time";
				}
				if(isset($_POST['adid']) && $_POST['adid']!=null){
					$ad            = intval($_POST['adid']);
					$adi           = "and a.adid = $ad";
					$GetAppName    = "and a.cpid = $ad";
				}

				if(isset($_POST['cpid']) && $_POST['cpid']!=null){
					$cp            = intval($_POST['cpid']);
					$cpi           = "and a.cpid = $cp";
				} 
				if($_POST['idfa']!=null){
					$idf           =  trim($_POST['idfa']);

					$idfa          = "and a.idfa = '$idf'";

				}
				
			}else{
				
				$appid             = intval($_GET['appid']);
				
				if($_GET['start_time'] !=null && $_GET['end_time']!=null){
					$start_time    = intval(strtotime($_GET['start_time']));
				    $end_time      = intval(strtotime($_GET['end_time'])+3600);
				    $stimes        = $_GET['start_time'];
				    $etimes        = $_GET['end_time'];
				    $time          = "and a.timestamp between $start_time and $end_time";
				}
				if($_GET['adid'] !=null){
					$ad            = intval($_GET['adid']);
					$adi           = "and a.adid = $ad";
					$GetAppName    = "and a.cpid = $ad";
				}
				if($_GET['cpid'] !=null){
					$cp            = intval($_GET['cpid']);
					$cpi           = "and a.cpid = $cp";
					
				}
			}
			
		
			
			$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$source    = M('source','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$channel   = M('source_cpid','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$IdRe      = D('IdfaRepeat');
			$submit     = M('submit2','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$nsubmit   = M('submit','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		    $app_name  = $advert->alias('a')->where("a.appid = $appid $GetAppName")->getField('a.app_name');
		    $app_name  =json_decode(file_get_contents("http://itunes.apple.com/lookup?id=$appid"),true)['results'][0]['trackName']==''?$advert->alias('a')->where("a.appid = $appid $GetAppName")->getField('a.app_name'):json_decode(file_get_contents("http://itunes.apple.com/lookup?id=$appid"),true)['results'][0]['trackName'];
		    //获取渠道数据
		    $CData     = $channel->field('cpid,name')->select();
			$adid      = $advert->where("appid = $appid")->getField('cpid');
		    $count     = count($source->alias('a')->where("a.appid = $appid $time $idfa $adi $cpi")->group('a.idfa')->select());
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
			$SourceData=array();
			
			if(isset($_GET['type']) && $_GET['type']==1){

				$SourceData= $source->alias('a')->where("a.appid = $appid $time $idfa $adi $cpi")->field("a.ip,a.idfa,FROM_UNIXTIME(max(a.timestamp)) as stime,a.appid,keywords")->order('stime asc')->group('a.idfa')->select();
			
			
				 /* 51*/
		
			/*51*/
				 // $data    = $source->alias('a')->field('a.appid,a.idfa,FROM_UNIXTIME(max(a.timestamp)) as stime,FROM_UNIXTIME(s.timestamp) as etime,a.keywords')->table('aso_submit2 s,aso_source')->where("a.appid = 564713751 and a.idfa=s.idfa and a.timestamp between 1524672000 and 1524758400 and s.appid=564713751 and s.timestamp between 1524672000 and 1524844800")->group('a.idfa')->limit(0,10100)->select();

				  
			}else{
				// $IdRetime          = "and a.date between $start_time and $end_time";
				// 	$SourceData= $IdRe->alias('a')->join('left join aso_source_cpid as c on c.cpid=a.cpid')->where("a.appid = $appid $IdRetime $idfa $adi $cpi")->field("c.name,a.idfa,FROM_UNIXTIME(max(a.date)) as stime,a.appid")->group('a.idfa')->limit($EveryPage*($NowPage-1),10)->select();

				
				  $SourceData= $source->alias('a')->join('left join aso_source_cpid as c on c.cpid=a.cpid')->where("a.appid = $appid $time $idfa $adi $cpi")->field("c.name,a.ip,a.idfa,FROM_UNIXTIME(max(a.timestamp)) as stime,a.appid,keywords")->order('a.timestamp desc')->group('a.idfa')->limit($EveryPage*($NowPage-1),10)->select();


				
			}


			if(empty($SourceData)){

				$count     = count($submit->alias('a')->where("a.appid = $appid $time $idfa $cpi")->group('a.idfa')->select());
				
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
				$data   = $submit ->alias('a')->join('left join aso_source_cpid as c on a.cpid=c.cpid')->where("a.appid = $appid $time $idfa $cpi")->field("c.name,a.idfa,FROM_UNIXTIME(max(a.timestamp)) as sutime,a.appid,a.keywords")->order('a.timestamp desc')->group('a.idfa')->limit($EveryPage*($NowPage-1),10)->select();
				foreach($data as $i=>$j){
					$data[$i]['app_name']=$app_name;
					$data[$i]['ip']      ='未统计';
					$data[$i]['stime']   = '未统计';
					if($data[$i]['keywords']=='') $data[$i]['keywords']='未统计';
				}
				
				if(isset($_GET['type']) && $_GET['type']==1){
					$data   = $submit ->alias('a')->join('left join aso_source_cpid as c on a.cpid=c.cpid')->where("a.appid = $appid $time $idfa $cpi")->field("c.name,a.idfa,FROM_UNIXTIME(max(a.timestamp)) as sutime,a.appid,a.keywords")->group('a.idfa')->select();
					foreach($data as $i=>$j){
					$data[$i]['app_name']=$app_name;
					
					if($data[$i]['keywords']=='') $data[$i]['keywords']='未统计';
				    }
					 $xlsName  = $stimes.'到'.$etimes.$app_name.'idfa任务信息';
	                 $xlsCell  = array(
	                array('appid','AppID'),

	                array('app_name','App名称'),
	                array('name','渠道'),
	                array('idfa','设备唯一标识'),
	                 array('keywords','关键词'),
					array('sutime','激活时间'),
					
					
		            );
		            $file_name    = date('m-d',$start_time).'到'.date('m-d',$end_time).$app_name.'数据';
					
		            
		            exportExcel_data($xlsName,$xlsCell,$data,$file_name);
		            exit;
				}
				$this->assign('channel',$CData);
				$this->assign('count',$count);
				$this->assign('MaxPage',$MaxPage);
				$this->assign('adid',$_REQUEST['adid']);
				$this->assign('cpid',$cp);
				$this->assign('appid',$_REQUEST['appid']);
				$this->assign('start_time',str_replace(' ','',$stimes));
				$this->assign('end_time',str_replace(' ','',$etimes));
				$this->assign('NowPage',$NowPage);
				$this->assign('data',$data);
				$this->display('task_info');

				die;
			}
			
			//echo $source->_sql();
			if(isset($_REQUEST['adid']) && $_REQUEST['adid']!=null){
				if(intval($_REQUEST['adid'])!=1){
					$SubmitData= $submit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid,keywords")->select();
				}else{
					if(intval($_REQUEST['appid'])==480079300){
						$SubmitData= $submit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid,keywords")->select();
					}else{
						$SubmitData= $nsubmit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid,keywords")->select();
					}
					
				}
			}else{
				$SubmitData= $submit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid,keywords")->select();
			}
			// if($ad!=1){
			// 	$SubmitData= $submit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid")->select();
			// }else{
			// 	$SubmitData= $nsubmit->alias('a')->where("a.appid = $appid $time $idfa")->field("a.timestamp,a.idfa,a.appid")->select();
			// }
			// echo $nsubmit->_sql();
			//var_dump($SubmitData);

			foreach($SourceData as $k=>$val){
				$SourceData[$k]['app_name'] = $app_name;
				foreach($SubmitData as $i=>$j){
					if($val['keywords']=='') $val['keywords']='未统计';
					if($val['idfa']==$j['idfa']){
						$SourceData[$k]['sutime']=$j['timestamp']==''?'未完成':date('Y-m-d H:i:s',$j['timestamp']);
						$SourceData[$k]['keywords'] = $val['keywords'];
					}
				}
			}


			$data      = $SourceData;
			
			 //var_dump(array_count_values($data));

			if(isset($_GET['type']) && $_GET['type']==1){
				   $xlsName  = $stimes.'到'.$etimes.$app_name.'idfa任务信息';
                  $xlsCell  = array(
                array('appid','AppID'),

                array('app_name','App名称'),
                array('idfa','设备唯一标识'),
                array('keywords','关键词'),
                 array('ip','用户ip'),
                 array('name','渠道'),
				array('stime','点击时间(领取任务)'),
				array('sutime','完成时间（回调时间）'),
				
            );
            $file_name    = date('m-d',$start_time).'到'.date('m-d',$end_time).$app_name.'数据';
			
            
            exportExcel_data($xlsName,$xlsCell,$data,$file_name);
            exit;
			}
		
			//var_dump($data);
			$this->assign('channel',$CData);
			$this->assign('count',$count);
			$this->assign('MaxPage',$MaxPage);
			$this->assign('adid',$_REQUEST['adid']);
			$this->assign('cpid',$cp);
			$this->assign('appid',$_REQUEST['appid']);
			$this->assign('start_time',str_replace(' ','',$stimes));
			$this->assign('end_time',str_replace(' ','',$etimes));
			$this->assign('NowPage',$NowPage);
			$this->assign('data',$data);
			$this->display('task_info');

		
	}

	public function demo(){
		$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');

		$data      = $advert->field('create_time,app_name,appid')->where("create_time between 1518192000 and 1519228800")->order('create_time asc')->select();

		foreach($data as $k=>$val){
			$data[$k]['info'] = '对接'.$val['app_name'].'广告(AppId:'.$val['appid'].')';
 			$data[$k]['date'] = date('Y-m-d H:i:s',$val['create_time']);
		}
		foreach($data as $a=>$b){
			unset($data[$a]['appid']);
			unset($data[$a]['app_name']);
			unset($data[$a]['create_time']);
		}
		
		
		$xlsName  = '技术部刘超春节假期工作记录';
                  $xlsCell  = array(
                array('info','工作内容'),

                array('date','时间'),
                );
        $file_name = '工作记录';

		exportExcel_data($xlsName,$xlsCell,$data,$file_name);
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
}

	
	

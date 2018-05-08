<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\SubjectModel;
use Admin\Model\CommonModel;

class LogController extends CommonController{
	public function submit_error(){
		if(IS_POST){
				
				$appid         = intval($_POST['appid']);
				
				if($_POST['start_time'] !=null && $_POST['end_time']!=null){
					$start_time    = intval(strtotime($_POST['start_time']));
				    $end_time      = intval(strtotime($_POST['end_time'])+3600);
				    $stimes        = $_POST['start_time'];
				    $etimes        = $_POST['end_time'];

				    $time          = "and timestamp between $start_time and $end_time";
				}
				if($_POST['idfa']!=null){
					$idf           =  trim($_POST['idfa']);

					$idfa          = "and idfa = '$idf'";

				}
				
			}else{
				$appid             = intval($_GET['appid']);
				
				if($_GET['start_time'] !=null && $_GET['end_time']!=null){
					$start_time    = intval(strtotime($_GET['start_time']));
				    $end_time      = intval(strtotime($_GET['end_time'])+3600);
				    $stimes        = $_GET['start_time'];
				    $etimes        = $_GET['end_time'];
				    $time          = "and timestamp between $start_time and $end_time";
				}
			}
			
		
			
			$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			
			$submit   = M('submit_log','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$app_name  = $advert->where("appid = $appid")->getField('app_name');
			//$adid      = $advert->where("appid = $appid")->getField('cpid');
		    $count     = count($submit->where("appid = $appid $time $idfa")->group('idfa')->select());
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
			if(isset($_GET['type']) && $_GET['type']==1){
				$SourceData= $submit->where("appid = $appid $time $idfa")->field("idfa,FROM_UNIXTIME(max(timestamp)) as stime,appid,json")->order('stime asc')->group('idfa')->select();
			}else{
				$SourceData= $submit->where("appid = $appid $time $idfa")->field("idfa,FROM_UNIXTIME(max(timestamp)) as stime,appid,json")->group('idfa')->limit($EveryPage*($NowPage-1),10)->select();
			}
			
			
			
			foreach($SourceData as $k=>$v){
				$SourceData[$k]['app_name'] = $app_name;
			}
			
			
			

			$data      = $SourceData;
			//var_dump($data);
			 //var_dump(array_count_values($data));

			if(isset($_GET['type']) && $_GET['type']==1){
				   $xlsName  = $stimes.'到'.$etimes.$app_name.'idfa出错记录';
                  $xlsCell  = array(
                array('appid','AppID'),
                array('app_name','App名称'),
                array('idfa','设备唯一标识'),
				array('stime','报错时间'),
				array('json','报错json'),
				
            );
			
            
            exportExcel_data($xlsName,$xlsCell,$data);
            exit;
			}
			//var_dump($data);
			$this->assign('count',$count);
			$this->assign('MaxPage',$MaxPage);
			$this->assign('appid',$_REQUEST['appid']);
			$this->assign('start_time',str_replace(' ','',$stimes));
			$this->assign('end_time',str_replace(' ','',$etimes));
			$this->assign('NowPage',$NowPage);
			$this->assign('data',$data);
			$this->display('submit_error');
	}
}
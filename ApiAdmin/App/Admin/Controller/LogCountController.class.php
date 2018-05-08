<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\SubjectModel;
use Admin\Model\CommonModel;
class LogCountController extends CommonController{
	private $CommonModel;
	private $SubjectModel;
	
	
	public function log_info(){
			set_time_limit(0);
			
		
			$SourceLog = M('source_log','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$SubmitLog = M('submit_log','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$source    = M('source','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$submit    = M('submit2','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$nsubmit   = M('submit','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		    $app_name  = $advert->alias('a')->where("a.appid = $appid $GetAppName")->getField('a.app_name');

		    $t = time();
            $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
            $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
            $type = intval($_GET['type']);
            if($type==1){
            $data      = $SourceLog->alias('sl')->field('count(sl.appid) as sccount,sl.appid,sl.json,sl.adid')->where("sl.timestamp between $start and $end")->group('sl.appid')->select();
            foreach($data as $k=>$val){
             	$where['appid']       = $val['appid'];
             	// $where['cpid']        = $val['adid'];
             	$data[$k]['app_name'] = $advert->where($where)->getField('app_name');
             }

        	}else if($type==3){
             $data      = $SubmitLog->alias('sl')->field('count(sl.appid) as sucount,sl.appid,sl.json')->where("sl.timestamp between $start and $end")->group('sl.appid')->select();
             foreach($data as $k=>$val){
             	$where['appid']       = $val['appid'];
             	$data[$k]['app_name'] = $advert->where($where)->getField('app_name');
             }
        	}
        	
        	$this->assign('type',$type);
           $this->assign('data',$data);
			$this->display('log_info');


		
	}

	public function LogC(){
 		$SourceLog = M('source_log','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$SubmitLog = M('submit_log','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$advert    = M('advert','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$source    = M('source','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$submit    = M('submit2','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		$nsubmit   = M('submit','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
	    $app_name  = $advert->alias('a')->where("a.appid = $appid $GetAppName")->getField('a.app_name');

	    $t = time();
        $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
        $type = intval($_GET['type']);
        
        $source_log_data      = $SourceLog->alias('sl')->field('count(sl.appid) as sccount,sl.appid,sl.json,sl.adid')->where("sl.timestamp between $start and $end")->group('sl.appid')->select();
            foreach($source_log_data as $k=>$val){
             	$where['appid']       = $val['appid'];
             	// $where['cpid']        = $val['adid'];
             	$source_log_data[$k]['app_name'] = $advert->where($where)->getField('app_name');
             }	
    
         $submit_log_data      = $SubmitLog->alias('sl')->field('count(sl.appid) as sucount,sl.appid,sl.json')->where("sl.timestamp between $start and $end")->group('sl.appid')->select();
             foreach($submit_log_data as $k=>$val){
             	$where['appid']       = $val['appid'];
             	$submit_log_data[$k]['app_name'] = $advert->where($where)->getField('app_name');
             }
         $scstr='';
         $sustr='';
         foreach($source_log_data as $k=>$val){
         	if($val['sccount']>1000){
         		// sendMail('zilong.wang@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')点击接口异常记录超过100次,当前报错记录为'.$val['sccount'].'次.请及时处理!');
         		// sendMail('liuchao@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')点击接口异常记录超过100次,当前报错记录为'.$val['sccount'].'次.请及时处理!');
         		// sendMail('weilinye@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')点击接口异常记录超过100次,当前报错记录为'.$val['sccount'].'次.请及时处理!');
         		$scstr=$scstr.$val['app_name'].'(AppId: '.$val['appid']. ' , Error Times: '.$val['sccount'].') ';
         	}
         }
         
         foreach($submit_log_data as $k=>$val){
         	if($val['sucount']>1000){
         		// sendMail('zilong.wang@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')激活上报接口异常记录超过100次,当前报错记录为'.$val['sucount'].'次.请及时处理!');
         		// sendMail('liuchao@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')点击接口异常记录超过100次,当前报错记录为'.$val['sucount'].'次.请及时处理!');
         		// sendMail('weilinye@aiyingli.com','','广告'.$val['app_name'].'('.$val['appid'].')点击接口异常记录超过100次,当前报错记录为'.$val['sucount'].'次.请及时处理!');
         		$sustr=$sustr.$val['app_name'].'(AppId: '.$val['appid']. ' , Error Times: '.$val['sucount'].') ';
         	}
         }
         /*发送点击上报接口通知邮箱*/
         if(strlen($scstr)>5){
	         sendMail('liuchao@aiyingli.com','','广告 '.$scstr.'点击接口异常记录超过1000次,请及时处理!');
	         sendMail('zilong.wang@aiyingli.com','','广告 '.$scstr.'点击接口异常记录超过1000次,请及时处理!');
	         //sendMail('weilinye@aiyingli.com','','广告 '.$scstr.'点击接口异常记录超过100次,请及时处理!');
         }
         /*发送上报激活接口通知邮箱*/
         if(strlen($sustr)>5){


         sendMail('liuchao@aiyingli.com','','广告 '.$sustr.'激活上报接口异常记录超过1000次,请及时处理!');
         sendMail('zilong.wang@aiyingli.com','','广告 '.$sustr.'激活上报接口异常记录超过1000次,请及时处理!');
        // sendMail('weilinye@aiyingli.com','','广告 '.$sustr.'激活上报接口异常记录超过100次,请及时处理!');
     	}
    	
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

	
	

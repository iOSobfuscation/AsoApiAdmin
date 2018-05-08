<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;

class ChannelController extends CommonController{
		private $connect    = 'mysql://root:ttttottttomysql@101.200.91.203/aso_db';

		private $db_prefix  = 'aso_';

		public function   channel_list(){
			$cpi           = $_REQUEST['cpid'];
			
			$cpid          = $cpi!=null?"and cpid = $cpi":'';
			$CpName        = trim($_REQUEST['cp_name'])!=null?"and name like '%{$_REQUEST['cp_name']}%'":'';
			$where         = "name != ''";
			
			


			$app            = M('source_cpid',$this->db_prefix,$this->connect);

			$count     = $app->where("$where $cpid $CpName")->count();
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

			$data           = $app->where("$where $cpid $CpName")->field('id,cpid,name,key,note')->limit($EveryPage*($NowPage-1),10)->select();
			//echo $app->_sql();
			$this->assign('count',$count);
			$this->assign('MaxPage',$MaxPage);
			$this->assign('NowPage',$NowPage);
			$this->assign('cpid',$_REQUEST['cpid']);
			
			$this->assign('name',trim($_REQUEST['cp_name']));
			$this->assign('data',$data);
			//dump($data);
			$this->display('channel_list');

		}


		public function channel_SetKey(){
			$app   = M('source_cpid',$this->db_prefix,$this->connect);
			$name  = trim($_GET['name']);
			$cpid  = trim($_GET['cpid']);
			$data['cpid']   = $cpid;
			$data_al['key'] = md5($cpid.$name);

			$info  =  $app->where($data)->save($data_al);

			if($info){
				echo json_encode(array('code'=>0,'key'=>$data_al['key']));
			}else{
				echo json_encode(array('code'=>99));
			}


		}



		/*单选题修改操作*/
	public function channel_edit(){
			$app                   = M('source_cpid',$this->db_prefix,$this->connect);

			$res                   = $_POST['data'];
		if(IS_POST){
			
			$appid                 = intval($res['id']);
			$data['cpid']          = intval($res['cpid']);
			
			
			$data['name']          = trim($res['name']);
			$data['note']          = trim($res['note']);
			
			$info                  = $app->where("id = $appid")->save($data);
			
			if($info){
				echo 0;
			}else{
				echo 99;
			}
		}else{
			
			$id               = intval($_GET['id']);
			$where['id']      = $id;

			$data             = $app->where($where)->field('id,cpid,name,key,note')->find();

			$this->assign('data',$data);
			$this->display('channel_info');
			
		}
	}

	public function channel_add(){
			$app                   = M('source_cpid',$this->db_prefix,$this->connect);

			$res                   = $_POST['data'];
		if(IS_POST){
			
			
			$data['cpid']          = intval($res['cpid']);
			$data['name']          = trim($res['name']);
			$data['note']          = trim($res['note']);
			if(isset($res['key'])){
				$data['key'] = md5($data['cpid'].$data['name']);
			}
			
			$info                  = $app->add($data);
			
			if($info){
				echo 0;
			}else{
				echo 99;
			}
		}else{
			
			$this->assign('type','add');
			$this->display('channel_info');
			
		}
	}

	public function channel_del(){
		$app              = M('source_cpid',$this->db_prefix,$this->connect);
		$id               = intval($_GET['id']);
		$where['id']      = $id;

		$data             = $app->where($where)->delete();

		
	}

	public function check(){
		if($_POST['name']==null){
				echo 2;
				exit;
			}else{
				echo 0;
				exit;
			}
	}
}
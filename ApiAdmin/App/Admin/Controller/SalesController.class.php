<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;

class SalesController extends CommonController{
		private $connect    = 'mysql://root:ttttottttomysql@101.200.91.203/aso_db';

		private $db_prefix  = 'aso_';

		public function   sales_list(){
			
			$where         = "sales_name != ''";
			
			$app           = M('sales',$this->db_prefix,$this->connect);

			$count     = $app->where($where)->count();

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

			$data           = $app->where($where)->field('sales_id,sales_name,create_time')->limit($EveryPage*($NowPage-1),10)->select();
			//echo $app->_sql();
			$this->assign('count',$count);
			$this->assign('MaxPage',$MaxPage);
			$this->assign('NowPage',$NowPage);
			$this->assign('cpid',$_REQUEST['cpid']);
			
			$this->assign('name',trim($_REQUEST['cp_name']));
			$this->assign('data',$data);
			//dump($data);
			$this->display('sales_list');

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

	public function sales_add(){
			$app                   = M('sales',$this->db_prefix,$this->connect);

			$name                   = $_GET['name'];
		
			$is_exist              = $app->where(array('sales_name'=>$name))->find();

			if($is_exist){
				echo 99;die;
			}

			$data['sales_name']    = $name;
			$data['create_time']   = time();
			
			
			$info                  = $app->add($data);
			
			if($info){
				echo 100;
			}else{
				echo 101;
			}
		
			
			// $this->assign('type','add');
			// $this->display('sales_info');
			
		
	}

	public function sales_del(){
		$app              = M('sales',$this->db_prefix,$this->connect);
		$id               = intval($_GET['id']);
		$where['sales_id']      = $id;

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
<?php

namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\SubjectModel;
use Admin\Model\CommonModel;
class RadioController extends CommonController{
	private $CommonModel;
	private $SubjectModel;
	
	
	public function radio_list(){
		
		
		$this->display('radio_list');
	}
	
	
	public function radio_add(){
		
		
		if(IS_POST){
			$data['score']         = $_POST['score'];
			$data['radio_topic']   = $_POST['radio_topic'];
			$data['subject_id']    = intval($_POST['subject_id']);
			$data['radio_answer']  = trim($_POST['radio_answer']);
			$data['radio_option']  = json_encode($_POST['radio_option']);
			$data['create_time']   = time();
			$data['admin_id']      = session('userid')?session('userid'):1;
			$data['acc_cat_id']    = intval($_POST['acc_cat_id']);
			$data['exam_cat_id']   = intval($_POST['exam_cat_id']);
			
			$info                  = $this->CommonModel->insert_data('radio',$data);
			
			if($info){
				if($_POST['subjectId']!=null){
				    $this->success('添加成功',"radio_list?id={$data['subject_id']}");
				
				}else{
					$this->success('添加成功','radio_list');
				}
			}else{
				$this->error('添加失败');
			}
		}else{
			$subject_list    = $this->SubjectModel->subject_list();
			$exam_cat_list   = $this->CommonModel->get_data('exam_cat','','is_del = 0','','exam_cat_id,exam_cat_name');
			if(isset($_GET['subject_id']) && isset($_GET['acc_id']) && isset($_GET['exam_id'])){
				$subject_id     = intval($_GET['subject_id']);
				$acc_id         = intval($_GET['acc_id']);
				$exam_id        = intval($_GET['exam_id']);
				$this->assign('subject_id',$subject_id);
				$this->assign('acc_id',$acc_id);
				$this->assign('exam_id',$exam_id);
			}
			$furl            = '/Admin/radio/radio_add';
			$this->assign('furl',$furl);
			$this->assign('exam_data',$exam_cat_list);
			
			$this->assign('subject_data',$subject_list);
			$this->display('radio_info');
		}
	}
	public function get_acc(){
		$exam_cat_id   = intval($_GET['exam_cat_id']);
		echo json_encode($this->CommonModel->get_data('acc_cat','',"exam_cat_id = $exam_cat_id and is_del = 0",'','acc_cat_name,acc_cat_id'));
		
	}
	public function get_subject(){
		$acc_cat_id   = intval($_GET['acc_cat_id']);
		echo json_encode($this->CommonModel->get_data('subject','',"acc_cat_id = $acc_cat_id and is_del = 0",'','subject_name,subject_id'));
	}
	/*单选题修改操作*/
	public function radio_edit(){
			
		if(IS_POST){
			
			$radio_id              = intval($_POST['radio_id']);
			$data['radio_topic']   = $_POST['radio_topic'];
			$data['subject_id']    = intval($_POST['subject_id']);
			$data['radio_answer']  = trim($_POST['radio_answer']);
			$data['radio_option']  = json_encode($_POST['radio_option']);
			$data['score']         = $_POST['score'];
			$data['acc_cat_id']    = intval($_POST['acc_cat_id']);
			$data['exam_cat_id']   = intval($_POST['exam_cat_id']);
			$info                  = $this->CommonModel->save_data('radio','radio_id',$radio_id,$data);
			
			if(info){
				$this->success('修改成功','radio_list');
			}else{
				$this->error('修改失败');
			}
		}else{
			
			$radio_id                     = intval($_GET['id']);
			$radio_data                   = $this->CommonModel->find_data('radio','radio_id',$radio_id);
			
			$radio_data['radio_option']   = json_decode($radio_data['radio_option'],true);
			$furl                         = '/Admin/Radio/radio_edit';
			$exam_cat_id                  = $this->CommonModel->fields(array('exam_cat_id'),'radio','radio_id',$radio_id);
			$acc_cat_id                  = $this->CommonModel->fields(array('acc_cat_id'),'radio','radio_id',$radio_id);
			$TotalOption                  = count($radio_data['radio_option']);
			$exam_cat_list   = $this->CommonModel->get_data('exam_cat','',"is_del = 0",'','exam_cat_id,exam_cat_name');
			$subject_data    = $this->CommonModel->get_data('subject','',"acc_cat_id=$acc_cat_id and is_del = 0",'','subject_id,subject_name');
			$acc_cat_list    = $this->CommonModel->get_data('acc_cat','',"exam_cat_id = $exam_cat_id and is_del = 0",'','acc_cat_id,acc_cat_name');
			$this->assign('exam_data',$exam_cat_list);
			$this->assign('acc_data',$acc_cat_list);
			$this->assign('Total',$TotalOption);
			$this->assign('data',$radio_data);
			$this->assign('furl',$furl);
			$this->assign('subject_data',$subject_data);
			$this->display('radio_info');
			
		}
	}
	
	public function radio_del(){
		$radio_id          = intval($_GET['radio_id']);
		
		$data['is_del']    = 1;
		
		$info              = $this->CommonModel->save_data('radio','radio_id',$radio_id,$data);
		
		if($info){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
		
	}
	
	public function radio_check(){
		if($_POST['radio_topic']==null){
			echo 2;
		}else if($_POST['score']<0){
			echo 5;
		}else if($_POST['subject_id']==null){
			echo 3;
		}else if(count($_POST['radio_option'])<2){
			echo 4;
		}
	}
	
	
	/**
	 * @desc 单选批量添加
	 * @param unknown $file_name
	 */
	public function radio(){
		
		$config=array(
			'rootPath'=>'Public/',
			'maxSize' =>'0',
			'savePath'=>'upload/images/',
			'exts'=>array('gif','png','jpg','jpeg','xls','xlsx'),
			);
			$upload=new \Think\Upload($config);
			$date	= date("Y-mm-dd", time());
	
			//上传文件
		
			$info=$upload->uploadOne($_FILES['radio']);
			
			//$path	= 'http://'.$_SERVER['HTTP_HOST'].'/Public/'.$info['savepath'].$info['savename'];
			$file_name	= './Public/'.$info['savepath'].$info['savename'];
		
		Vendor("PHPExcel.PHPExcel");   // 这里不能漏掉
		Vendor("PHPExcel.PHPExcel.IOFactory");
		$ExtName    = pathinfo($file_name)['extension'];//文件后缀名

		$objReader  = $ExtName=='xlsx' ?\PHPExcel_IOFactory::createReader('Excel2007'):\PHPExcel_IOFactory::createReader('Excel5');
		
		$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
		
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumn = $sheet->getHighestColumn(); // 取得总列数
		$arr = array();
		ini_set('max_execution_time', '0');
		$radioM = M('cesubmit','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
		for($i=1;$i<$highestRow+1;$i++){
			$arr2 = array();
			foreach (range('A',$highestColumn) as $va){
				$key = $objPHPExcel->getActiveSheet()->getCell($va."1")->getValue();				
				
					$jsonStr = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();
					if(is_object($jsonStr))  $jsonStr= $jsonStr->__toString();
					$arr2['idfa']      = $this->sth($jsonStr);
					$arr2['cpid']      = $_POST['cpid']?intval($_POST['cpid']):1;
					$arr2['appid']     = $_POST['appid']?intval($_POST['appid']):1;

					$arr2['timestamp'] = $_POST['timestamp']?$_POST['timestamp']:1;
					$arr2['type']      = $_POST['type']==1?1:2;

					// $fp = fopen('a.txt','w+');
					
					// fwrite($fp,"{$arr2['cpid']}\r\n");
				
					

					// fclose($fp);
					$radioM->add($arr2);
			}

			
			
		}	
		
		// foreach($arr as $k=>$val){
		// 	$res  = $radioM->add($arr[$k]);
		// }
		
		// 
		
			$this->success('导入成功');
		
	}
	
	
	/**
	 * @desc 分割成数组
	 * @param unknown $str
	 * @return unknown
	 */
	private function th($str){
		$pstr = "\s";
		$isHave = strpos($str,"　");
		if($isHave) $pstr .= "|　";
		$isHave1 = strpos($str,"\r");
		if($isHave1) $pstr .= "|\r";
		$isHave2 = strpos($str,"\t");
		if($isHave2) $pstr .= "|\t";
		$isHave3 = strpos($str,"\n");
		if($isHave3) $pstr .= "|\n";
		
		$s1 = preg_replace('/['.$pstr.']+/', "", $str);
		
		$arr =explode("|",preg_replace("/︱/", "|", $s1));
		foreach ($arr as $key=>$val){
			$arr[$key] = trim($val,"\xc2\xa0");
		}
		return $arr;
	}
	
	/**
	 * @desc 清楚空格
	 * @param unknown $str
	 */
	private function sth($str){
		$pstr = "\s";
		$isHave = strpos($str,"　");
		if($isHave) $pstr .= "|　";
		$isHave1 = strpos($str,"\r");
		if($isHave1) $pstr .= "|\r";
		$isHave2 = strpos($str,"\t");
		if($isHave2) $pstr .= "|\t";
		$isHave3 = strpos($str,"\n");
		if($isHave3) $pstr .= "|\n";
		$b = preg_replace('/['.$pstr.']+/', "", $str);
		$c = trim($b,"\xc2\xa0");
		return $c;
		
	}
	
}
<?php
	namespace Home\Controller;
use Home\Controller\CommonController;

use Admin\Model\CommonModel;
class IndexController extends CommonController{
	
	private $CommonModel;
	
	public function _initialize(){
		$this->CommonModel    = new CommonModel();
		
	}
	public function siteinfo(){
		$SiteData             = $this->CommonModel->find_data('set','id',1);
		$ExamData       = $this->CommonModel->get_data('exam_cat','','is_del = 0','','exam_cat_id,exam_cat_name,desc');
		 $NewsData       = $this->CommonModel->get_data('news','','is_del = 0','','news_id,title,is_link,link,thumb');
		 $this->assign('NewsData',$NewsData);
		 $this->assign('ExamData',$ExamData);
		$this->assign('SiteData',$SiteData);
	}
	public function index(){
		
		$ExamData       = $this->CommonModel->get_data('exam_cat','','is_del = 0','','exam_cat_id,exam_cat_name,desc');
		$NewsData       = $this->CommonModel->get_data('news','','is_del = 0','','news_id,title,is_link,link,thumb');
		$Banner         = $this->CommonModel->get_data('shop_banner','','is_del = 0','','banner_id,banner_title,banner_pic,banner_url');
		$SiteData             = $this->CommonModel->find_data('set','id',1);
		$this->assign('BannerData',$Banner);
		$this->assign('NewsData',$NewsData);
		$this->assign('ExamData',$ExamData);
		
		$this->siteinfo();
		$this->display('index');
	}
	
	/*注册页面*/
	public function register(){
		if(IS_POST){
			$data['member_name']    = trim($_POST['username']);
			$data['phone']          = trim($_POST['phone']);
			$data['member_pwd']     = md5($_POST['password']);
			$data['registration_time'] = time();
			$info                   = $this->CommonModel->insert_data('member',$data);
			
			$_SESSION['member']['member_id']      = $info;
		
		    $_SESSION['member']['member_name']    = $data['member_name'];
			 $_SESSION['member']['phone']         = $data['phone'];
			$_SESSION['record'][1]=array();
			$_SESSION['record'][2]=array();
			$_SESSION['record'][3]=array();
			$_SESSION['record'][4]=array();
			$this->display('registerInfo');
		}else{
			$this->siteinfo();
			$this->display('register');
		}
	}
	/*验证数据合法性*/
	public function check(){
		$username    = trim($_GET['username']);
		$member      = M('member');
		$phone       = trim($_GET['phone']);
		$info1       = $member->where("member_name = '{$username}'")->find();
		$info2       = $member->where("phone = '{$phone}'")->find();
		if($info1){
			echo 2;
		}else if($info2){
			echo 3;
		
		}else if(time() - session('code.time') > 900){
			echo 4;
		
		}else if(I('get.code') != session('code.code')){
			echo 5;
		}else{
			echo 0;
		}
	}
	
	/*验证数据合法性*/
	public function PhoneCheck(){
		
		$member      = M('member');
		if(isset($_GET['phone'])){
		$phone       = trim($_GET['phone']);
		
		$info2       = $member->where("phone = '{$phone}'")->find();
		
			if($info2){
				echo 3;
			}
		}else if(time() - session('code.time') > 900){
			echo 4;
		
		}else if(I('get.code') != session('code.code')){
			echo 5;
		}else{
			echo 0;
		}
	}
	public function EditPwd(){
			$member = M('member');
			
			
		if(IS_POST){
			$member_id             = intval($_SESSION['member']['member_id']);
			$data['member_pwd']    = md5($_POST['repassword']);
			
			$info        = $member->where("member_id = $member_id")->save($data);
			
			if($info){
				echo 1;
			}else{
				echo 0;
			}
			
		}else{
			$member_id     = intval($_GET['id']);
			$newpassword   = md5($_GET['password']);
			
			if($member->where("member_id = $member_id and member_pwd = '$newpassword'")->find()){
				echo 'yes';
			}else{
				echo 'no';
			}
		}
	}
	/*修改手机号*/
	public function EditPhone(){
			$member = M('member');
			
			
		if(IS_POST){
			$member_id             = intval($_SESSION['member']['member_id']);
			$data['phone']         = $_POST['phone'];
			
			$info        = $member->where("member_id = $member_id")->save($data);
			
			if($info){
				echo 1;
			}else{
				echo 0;
			}
			
		}
		
	}
	/*参加考试页面*/
	public function join_exam(){
		if($_SESSION['member']['member_id']==null){
			echo 1;
			exit;
		}
		
		if(isset($_GET['papers_id'])){
			if(isset($_GET['score'])){
				$this->assign('score',intval($_GET['score']));
			}
			//单选题列表
			$paper        = M('papers_info');
			$EveryPaper   = M('papers');
			$radio        = M('radio');
			$check        = M('check');
			$judgment     = M('judgment');
			$acc          = M('acc_cat');
			$analysis     = M('calculation_analysis');
			$analysisA    = M('calculation_analysis_answers_questions');
			$papers_id    = $_GET['papers_id']?$_GET['papers_id']:1;
			$papers_name  = $EveryPaper->where("papers_id = $papers_id")->getField('papers_name');
			$acc_cat_id   = $EveryPaper->where("papers_id = $papers_id")->getField('acc_cat_id');
			$acc_cat_name = $acc->where("acc_cat_id = $acc_cat_id")->getField('acc_cat_name');
			
			//单选题
			$radio_info   = $paper->where("papers_id = $papers_id and que_type_id = 1")->getField('que_info');
			$radio_data   = $radio->where("radio_id in($radio_info) and is_del = 0")->field('radio_option,radio_id,radio_answer,radio_topic,score')->select();
			foreach($radio_data as $k=>$val){
				$radio_data[$k]['radio_option']  = json_decode($val['radio_option']);
				$radio_score                     += $val['score'];
			}
			//多选题
			$check_info   = $paper->where("papers_id = $papers_id and que_type_id = 2")->getField('que_info');
			$check_data   = $check->where("check_id in($check_info) and is_del = 0")->field('check_option,check_id,check_answer,check_topic,score')->select();
			foreach($check_data as $k=>$val){
				$check_data[$k]['check_option']  = json_decode($val['check_option']);
				$check_score                     += $val['score'];
			}
			//判断题
			$judgment_info   = $paper->where("papers_id = $papers_id and que_type_id = 3")->getField('que_info');
			$judgment_data   = $judgment->where("judgment_id in($judgment_info) and is_del = 0")->field('judgment_id,judgment_answer,judgment_topic,score')->select();
			foreach($judgment_data as $k=>$val){
				$judgment_score                   += $val['score'];
			}
			//计算分析题
			
			$analysis_info   = $paper->where("papers_id = $papers_id and que_type_id = 4")->getField('que_info');
			$analysis_data   = $analysis->where("calculation_analysis_id in($analysis_info) and is_del = 0")->field('calculation_analysis_id,calculation_analysis_content')->select();
			$analysis_score  = $analysisA->where("calculation_analysis_id in($analysis_info) and is_del = 0")->getField('sum(score)');

			$this->siteinfo();
			$this->assign('papers_id',$papers_id);
			$this->assign('papers_name',$papers_name);
			$this->assign('check_score',$check_score);
			$this->assign('radio_score',$radio_score);
			$this->assign('judgment_score',$judgment_score);
			$this->assign('analysis_score',$analysis_score);
			$this->assign('TotalScore',$radio_score+$check_score+$judgment_score+$analysis_score);
		}else{
			if(isset($_GET['score'])){
				$this->assign('score',intval($_GET['score']));
			}
			$subject      = M('subject');
			$radio        = M('radio');
			$check        = M('check');
			$judgment     = M('judgment');
			$acc          = M('acc_cat');
			$analysis     = M('calculation_analysis');
			$analysisA    = M('calculation_analysis_answers_questions');
			$subject_id   = $_GET['subject_id']?$_GET['subject_id']:1;
			$subject_name = $subject->where("subject_id = $subject_id")->getField('subject_name');
			$acc_cat_id   = $subject->where("subject_id = $subject_id")->getField('acc_cat_id');
			$acc_cat_name = $acc->where("acc_cat_id = $acc_cat_id")->getField('acc_cat_name');

			//单选题
			
			$radio_data   = $radio->where("subject_id = $subject_id and is_del = 0")->field('radio_option,radio_id,radio_answer,radio_topic,score')->select();
			foreach($radio_data as $k=>$val){
				$radio_data[$k]['radio_option']  = json_decode($val['radio_option']);
				$radio_score                     += $val['score'];
			}
			//多选题
			
			$check_data   = $check->where("subject_id = $subject_id and is_del = 0")->field('check_option,check_id,check_answer,check_topic,score')->select();
			foreach($check_data as $k=>$val){
				$check_data[$k]['check_option']  = json_decode($val['check_option']);
				$check_score                     += $val['score'];
			}
			//判断题
			
			$judgment_data   = $judgment->where("subject_id = $subject_id and is_del = 0")->field('judgment_id,judgment_answer,judgment_topic,score')->select();
			//计算分析题
			foreach($judgment_data as $k=>$val){
				
				$judgment_score                     += $val['score'];
			}
			
			$analysis_data   = $analysis->where("subject_id = $subject_id and is_del = 0")->field('calculation_analysis_id,calculation_analysis_content')->select();
			$calculation_analysis_ids='';
			foreach($analysis_data as $k=>$val){
				$calculation_analysis_ids    .=$val['calculation_analysis_id'].',';
			}
			
			$calculation_analysis_ids= rtrim($calculation_analysis_ids,',');
			
			$analysis_score  = $analysisA->where("calculation_analysis_id in($calculation_analysis_ids) and is_del = 0")->getField('sum(score)');
			$this->siteinfo();
			
			
			$this->assign('subject_id',$subject_id);
			$this->assign('subject_name',$subject_name);
			$this->assign('check_score',$check_score);
			$this->assign('radio_score',$radio_score);
			$this->assign('judgment_score',$judgment_score);
			$this->assign('analysis_score',$analysis_score);
			$this->assign('TotalScore',$radio_score+$check_score+$judgment_score+$analysis_score);
		}
		$this->assign('acc_cat_name',$acc_cat_name);
		
		$this->assign('analysis_data',$analysis_data);
		$this->assign('judgment_data',$judgment_data);
		$this->assign('check_data',$check_data);
		$this->assign('radio_data',$radio_data);
		
		$this->display('exam');
	}
	/*验证用户登录*/
	public function login_check(){
		$member        = M('member');
		$username      = trim($_POST['username']);
		$password      = md5($_POST['password']);
		
		$info1          = $member->where("member_name = '$username'")->find();
		$info2          = $member->where("phone = '$username'")->find();
		
		$info3          = $member->where("member_name = '$username'  or  phone = '$username'")->find();
		
		$nowPassword    = $info3['member_pwd'];
		
		if($_POST['username']==null){
			echo 2;
		}else if($_POST['password']==null){
			echo 3;
		}else if(!$info1 && !$info2){
			echo 4;
		}else if(!$info3){
			echo 5;
		}else if($password != $nowPassword){
			echo 6;
		}
	}
	public function login(){
		if(IS_POST){
		$member              = M('member');
		$username            = trim($_POST['username']);
		$info                = $member->where("phone = '$username' or member_name='$username'")->find();
		$_SESSION['member']['member_id']      = $info['member_id'];
		$_SESSION['member']['member_name']    = $info['member_name'];
		$_SESSION['member']['phone']          = $info['phone'];
		$_SESSION['member']['member_nick']    = $info['member_nick'];
		$_SESSION['member']['member_thumb']   = $info['thumb'];
		$_SESSION['member']['registration_time']   = $info['registration_time'];
		$_SESSION['record'][1]=array();
		$_SESSION['record'][2]=array();
		$_SESSION['record'][3]=array();
		$_SESSION['record'][4]=array();
		$this->display('loginInfo');
		}else{
			$ExamData       = $this->CommonModel->get_data('exam_cat','','is_del = 0','','exam_cat_id,exam_cat_name,desc');
		 $NewsData       = $this->CommonModel->get_data('news','','is_del = 0','','news_id,title,is_link,link,thumb');
		 $Banner         = $this->CommonModel->get_data('shop_banner','','is_del = 0','','banner_id,banner_title,banner_pic,banner_url');
		 $this->siteinfo();
		 
		 $this->assign('BannerData',$Banner);
		 $this->assign('NewsData',$NewsData);
		 $this->assign('ExamData',$ExamData);
			$this->display('index');
		}
		
	}
	/*统计考生得分*/
	
	public function CountScore(){
		//所答单选题
		$radio_log    = $_SESSION['record'][1];
		$check_log    = $_SESSION['record'][2];
		$judgment_log = $_SESSION['record'][3];
		$analysis_log = $_SESSION['record'][4];
		$papers_id    = intval($_GET['papers_id']);
		$subject_id   = intval($_GET['subject_id']);
		$radio        = M('radio');
		$check        = M('check');
		$judgment     = M('judgment');
		$papers       = M('papers');
		$subject      = M('subject');
		$exam         = M('exam');
		$analysis     = M('calculation_analysis_answers_questions');
		if(isset($_GET['papers_id'])){
		//统计单选题得分
			foreach($radio_log as $k=>$val){
				foreach($radio_log[$k]['answer'] as $i=>$j){
					if(strpos($j,'<br/>')!==false){
					$radio_log[$k]['answer'][$i]  = str_replace('<br/>','(@)',$j);
					}
				}
			}
			foreach($radio_log as $k=>$val){
				if(strpos($val,'<br/>')!==false){
					$val  = str_replace('<br/>','(@)',$val);
				}
				if($radio->where("radio_id = $k")->getField('radio_answer')==$val['answer']){
					$radio_log[$k]['score']  = intval($radio->where("radio_id = $k")->getField('score'));
					$data[$k]['is_correct']   = 1;
					$data[$k]['score']        = intval($radio->where("radio_id = $k")->getField('score'));
					
				}else{
					$radio_log[$k]['score']  = 0;
					$data[$k]['is_correct']   = 0;
					$data[$k]['score']        = 0;
				}
				$data[$k]['member_id']    = $_SESSION['member']['member_id'];
				$data[$k]['exam_cat_id']  = $papers->where("papers_id = $papers_id")->getField('exam_cat_id');
				$data[$k]['acc_cat_id']   = $papers->where("papers_id = $papers_id")->getField('acc_cat_id');
				$data[$k]['papers_id']    = $papers_id;
				$data[$k]['que_type_id']  = 1;
				$data[$k]['que_id']       = $k;
				$data[$k]['member_answer'] = $val['answer'];
				$data[$k]['create_time']   = time();
				$exam->add($data[$k]);
				$radio_score  +=  $radio_log[$k]['score']; 
			}
		//统计多选题得分
		
			foreach($check_log as $k=>$val){
				foreach($check_log[$k]['answer'] as $i=>$j){
					if(strpos($j,'<br/>')!==false){
					$check_log[$k]['answer'][$i]  = str_replace('<br/>','(@)',$j);
					}
				}
			}
			foreach($check_log as $key=>$value){
				$check_log[$key]['answer']   = json_encode(array_values($value['answer']),true);
	              //echo $check->where("check_id = $key")->getField('check_answer').'---'.$check_log[$key]['answer'].'<br/>';
				if($check->where("check_id = $key")->getField('check_answer')==$check_log[$key]['answer']){
					$check_log[$key]['score']  = intval($check->where("check_id = $key")->getField('score'));
					$data[$key]['is_correct']  =1;
					$data[$key]['score']       = intval($check->where("check_id = $key")->getField('score'));
				}else{
					$check_log[$key]['score']  = 0;
					$data[$key]['is_correct']   = 0;
					$data[$key]['score']        = 0;
				}
				
				$data[$key]['member_id']    = $_SESSION['member']['member_id'];
				$data[$key]['exam_cat_id']  = $papers->where("papers_id = $papers_id")->getField('exam_cat_id');
				$data[$key]['acc_cat_id']   = $papers->where("papers_id = $papers_id")->getField('acc_cat_id');
				$data[$key]['papers_id']    = $papers_id;
				$data[$key]['que_type_id']  = 2;
				$data[$key]['que_id']       = $key;
				$data[$key]['member_answer'] = $check_log[$key]['answer'];
				$data[$key]['create_time']   = time();
				$exam->add($data[$key]);
				$check_score  +=  $check_log[$key]['score']; 
			}
		//统计判断题得分
			foreach($judgment_log as $k=>$val){
				if($judgment->where("judgment_id = $k")->getField('judgment_answer')==$judgment_log[$k]['answer']){
					$judgment_log[$k]['score']  = intval($judgment->where("judgment_id = $k")->getField('score'));
					
					$data[$k]['is_correct']     = 1;
					$data[$k]['score']          = intval($judgment->where("judgment_id = $k")->getField('score'));
				}else{
					$judgment_log[$k]['score']  = 0;
					$data[$k]['is_correct']     = 0;
					$data[$k]['score']          = 0;
				}
				$data[$k]['member_id']    = $_SESSION['member']['member_id'];
				$data[$k]['exam_cat_id']  = $papers->where("papers_id = $papers_id")->getField('exam_cat_id');
				$data[$k]['acc_cat_id']   = $papers->where("papers_id = $papers_id")->getField('acc_cat_id');
				$data[$k]['papers_id']    = $papers_id;
				$data[$k]['que_type_id']  = 3;
				$data[$k]['que_id']       = $k;
				$data[$k]['member_answer'] = $val['answer'];
				$data[$k]['create_time']   = time();
				$exam->add($data[$k]);
				
				$judgment_score  +=  $judgment_log[$k]['score']; 
			}
		//统计计算分析题得分
			foreach($analysis_log as $k=>$val){
				foreach($val as $i=>$j){
					if($analysis->where("calculation_analysis_id = $k and question_num = $i")->getField('answers')==$analysis_log[$k][$i]['answer']){
						$analysis_log[$k][$i]['score']  = $analysis->where("calculation_analysis_id = $k and question_num = $i")->getField('score');
						
					}else{
						$analysis_log[$k][$i]['score']  = 0;
						
					}
					$analysis_log[$k]['every_score']  += $analysis_log[$k][$i]['score'];
				}
				$data[$k]['member_id']    = $_SESSION['member']['member_id'];
				$data[$k]['exam_cat_id']  = $papers->where("papers_id = $papers_id")->getField('exam_cat_id');
				$data[$k]['acc_cat_id']   = $papers->where("papers_id = $papers_id")->getField('acc_cat_id');
				$data[$k]['papers_id']    = $papers_id;
				$data[$k]['que_type_id']  = 4;
				$data[$k]['que_id']       = $k;
				
				$data[$k]['create_time']   = time();
				$exam->add($data[$k]);
				$analysis_score  += $analysis_log[$k]['every_score'];
			}
			 $this->assign('papers_id',$papers_id);
		}else{
			//统计单选题得分
			foreach($radio_log as $k=>$val){
				foreach($radio_log[$k]['answer'] as $i=>$j){
					if(strpos($j,'<br/>')!==false){
					$radio_log[$k]['answer'][$i]  = str_replace('<br/>','(@)',$j);
					}
				}
			}
			foreach($radio_log as $k=>$val){
				if(strpos($val,'<br/>')!==false){
					$val  = str_replace('<br/>','(@)',$val);
				}
				if($radio->where("radio_id = $k")->getField('radio_answer')==$val['answer']){
					$radio_log[$k]['score']  = intval($radio->where("radio_id = $k")->getField('score'));
					
					
				}else{
					$radio_log[$k]['score']  = 0;
					
				}
				
				$radio_score  +=  $radio_log[$k]['score']; 
			}
		//统计多选题得分
		
			foreach($check_log as $k=>$val){
				foreach($check_log[$k]['answer'] as $i=>$j){
					if(strpos($j,'<br/>')!==false){
					$check_log[$k]['answer'][$i]  = str_replace('<br/>','(@)',$j);
					}
				}
			}
			foreach($check_log as $key=>$value){
				$check_log[$key]['answer']   = json_encode(array_values($value['answer']),true);
	              //echo $check->where("check_id = $key")->getField('check_answer').'---'.$check_log[$key]['answer'].'<br/>';
				if($check->where("check_id = $key")->getField('check_answer')==$check_log[$key]['answer']){
					$check_log[$key]['score']  = intval($check->where("check_id = $key")->getField('score'));
					
				}else{
					$check_log[$key]['score']  = 0;
					
				}
				
				
				$check_score  +=  $check_log[$key]['score']; 
			}
		//统计判断题得分
			foreach($judgment_log as $k=>$val){
				if($judgment->where("judgment_id = $k")->getField('judgment_answer')==$judgment_log[$k]['answer']){
					$judgment_log[$k]['score']  = intval($judgment->where("judgment_id = $k")->getField('score'));
					
					
				}else{
					$judgment_log[$k]['score']  = 0;
					
				}
				
				
				$judgment_score  +=  $judgment_log[$k]['score']; 
			}
		//统计计算分析题得分
			foreach($analysis_log as $k=>$val){
				foreach($val as $i=>$j){
					if($analysis->where("calculation_analysis_id = $k and question_num = $i")->getField('answers')==$analysis_log[$k][$i]['answer']){
						$analysis_log[$k][$i]['score']  = $analysis->where("calculation_analysis_id = $k and question_num = $i")->getField('score');
						
					}else{
						$analysis_log[$k][$i]['score']  = 0;
						
					}
					$analysis_log[$k]['every_score']  += $analysis_log[$k][$i]['score'];
				}
				
				$analysis_score  += $analysis_log[$k]['every_score'];
			}	

			 $this->assign('subject_id',$subject_id);
		

		}
		//echo $radio_score.'--'.$check_score.'--'.$judgment_score.'--'.$analysis_score;
		 $total_score = $radio_score+$check_score+$judgment_score+$analysis_score;
		 $this->assign('total_score',$total_score);
		 $this->assign('radio_score',$radio_score);
		 $this->assign('check_score',$check_score);
		 $this->assign('judgment_score',$judgment_score);
		 $this->assign('analysis_score',$analysis_score);

		$this->display('demo');
	}
	/*订单支付*/
	public function order_pay(){
		$order_id  = intval($_GET['order_id']);
		$order     = M('order');
		$data['is_pay']  = 0;
		$info     = $order->where("order_id = $order_id")->save($data);
		
		if($info){
			echo 1;
		}else{
			echo 0;
		}	
	}
	/*
		@desc 获取章节列表
	*/
	public function SubjectList(){
		$acc_cat_id     =  intval($_GET['acc_id']);
		
		$subject        = M('subject');
		$leftJoin       = 'left join ae_acc_cat as a on a.acc_cat_id = s.acc_cat_id';
		$SubjectData    = $subject->alias('s')->join($leftJoin)->where("s.is_del = 0 and s.acc_cat_id= $acc_cat_id")->field('s.subject_id,s.subject_name,a.acc_cat_name')->select();
		$this->siteinfo();
		
		$this->assign('SubjectData',$SubjectData);
		$this->display('cy');
	}
	public function BuyExam(){
		$acc_cat_id   =  intval($_GET['acc_id']);
		
		$paper        = M('papers');
		$leftJoin     = 'left join ae_acc_cat as a on a.acc_cat_id = p.acc_cat_id';
		$PaperData    = $paper->alias('p')->join($leftJoin)->where("p.is_del = 0 and p.acc_cat_id= $acc_cat_id")->field('p.papers_id,p.papers_name,p.cur_price,a.acc_cat_name')->select();
		$this->siteinfo();
		
		$this->assign('PaperData',$PaperData);
		$this->display('cy');
	}
	public function getContent(){
		$radio         = M('radio');
		$check         = M('check');
		
		$judgment      = M('judgment');
		$analysis      = M('calculation_analysis');
		$calculate     = M('calculation_analysis_answers_questions');
		$que_type_id   = intval($_GET['q_type']);
		$test_id       = intval($_GET['exam_id']);
		
		if($que_type_id==1){
			$data['topic']   = $radio->where("radio_id = $test_id")->getField('radio_topic');
			$data['score']   = $radio->where("radio_id = $test_id")->getField('score');
			$data['option']  = json_decode($radio->where("radio_id = $test_id")->getField('radio_option'));
			foreach($data['option'] as $k=>$val){
				if(strpos($data['option'][$k],'(@)')!==false){
					$data['option'][$k]=str_replace('(@)','<br/>',$data['option'][$k]);
				}
			}
			
		}else if($que_type_id==2){
			$data['topic']   = $check->where("check_id = $test_id")->getField('check_topic');
			$data['score']   = $check->where("check_id = $test_id")->getField('score');
			$data['option']  = json_decode($check->where("check_id = $test_id")->getField('check_option'));

			foreach($data['option'] as $k=>$val){
				if(strpos($data['option'][$k],'(@)')!==false){
					$data['option'][$k]=str_replace('(@)','<br/>',$data['option'][$k]);
				}
			}
		}else if($que_type_id==3){
			$data['topic']   = $judgment->where("judgment_id = $test_id")->getField('judgment_topic');
			$data['score']   = $judgment->where("judgment_id = $test_id")->getField('score');
			
		}else{
			$data['topic']   = $analysis->where("calculation_analysis_id = $test_id")->getField('calculation_analysis_content');
			$data['option']  = $calculate->where("calculation_analysis_id = $test_id and is_del = 0")->field('question_num,calculation_analysis_type_id,question_content,score')->order('question_num asc')->select();
			
			$borrow          = $this->CommonModel->get_data('borrowing','','is_del = 0','','borrowing_id,borrowing_name');
			$BorrowTypeList  = $this->CommonModel->get_data('borrowing_type_list','','is_del=0','','borrowing_type_list_id,borrowing_type_list_name,borrowing_type_id');
			$BorrowType      = $this->CommonModel->get_data('borrowing_type','','is_del=0','','borrowing_type_id,borrowing_type_name');
			foreach($BorrowTypeList as $key=>$value){
			foreach($BorrowType as $k=>$val){
				
					if($BorrowTypeList[$key]['borrowing_type_id']==$BorrowType[$k]['borrowing_type_id']){
						$result['----'.$val['borrowing_type_name'].'----'][] =$value; 
					}
				}
			}
			$data['borrow']      = $borrow;
			$data['borrow_list'] = $BorrowTypeList;
		}
			
			$data['session']  = $_SESSION['record'];
		echo json_encode($data);
	}
	/*记录考生答题记录*/
	public function record_exam(){
		
		$info        = explode('|',$_GET['value']);
		
		$que_type_id = intval($info[0]);
		$test_id     = intval($info[1]);
		$answer      = $info[2];
		$key         = $info[3];
		$status      = intval($_GET['status']);
		if($que_type_id==2){
			
			if($status==1){
				$_SESSION['record'][$que_type_id][$test_id]['answer'][$key] = $answer;
				ksort($_SESSION['record'][$que_type_id][$test_id]['answer']);
				
			}else{
				
				unset($_SESSION['record'][$que_type_id][$test_id]['answer'][$key]);
			}
		}else if($que_type_id==1 || $que_type_id==3){
			if($status==1){
				unset($_SESSION['record'][$que_type_id][$test_id]['answer']);
				$_SESSION['record'][$que_type_id][$test_id]['answer'] = $answer;
			}
		}else if($que_type_id==4){
			$question_number   = $info[2];
			$c_type            = $info[3];
			$answer            = $info[4];
			if($c_type==1){

				if($answer!=null){
					$_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']=$answer;
				}else{
					unset($_SESSION['record'][$que_type_id][$test_id][$question_number]);
				}
			}else if($c_type==2){
					if($_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']!=null){
					$NowV =  str_split($_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']);
					dump($NowV);
					if(in_array($answer,$NowV)){
							$k       =  array_search($answer,$NowV);
							unset($NowV[$k]);
							sort($NowV);
					}else{
						 array_push($NowV,$answer);
						 sort($NowV);
					}

					foreach($NowV as $i=>$j){
						$checkV         .= $j;
					}
					//$_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']= implode(' ',sort($NowV));
					$_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']=$checkV;
				}else{
					$_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']=$answer;
				}
			}else{
				
				$a = json_decode($answer,true);
				
				$newAnswer=json_encode($a);
				
			$_SESSION['record'][$que_type_id][$test_id][$question_number]['answer']=$newAnswer;
			}
		}
		dump($_SESSION);
		//dump($_GET['value']);
		
	}
	/*判断session是否存在*/
	public function buy_check(){
		$price    = intval($_GET['price']);
		if($_SESSION['member']['member_id']==null){
			echo 1;
		}else if($price==null){
            echo 2;
		}else{
			echo 0;
		}
	}
	/*退出登录*/
	public function offlogin(){

		$_SESSION['record'][1]=array();
		$_SESSION['record'][2]=array();
		$_SESSION['record'][3]=array();
		$_SESSION['record'][4]=array();
		
		$this->index();
	}
	public function Acclist(){
		
		$exam_id           = intval($_GET['id']);
		$leftJoin          = 'left join ae_exam_cat as ec on ec.exam_cat_id= a.exam_cat_id';
		$fields            = 'ec.exam_cat_name,a.acc_cat_id,a.acc_cat_name,a.cur_price';
		$AccData           = $this->CommonModel->get_data('acc_cat','a',"a.exam_cat_id =$exam_id and a.is_del=0",$leftJoin,$fields);
		$this->siteinfo();
		
		$this->assign('AccData',$AccData);
		$this->display('cy');
	}
	/*对科目生成订单*/
	public function acc_nopay_order(){
		$goods_ids      = explode(',',$_GET['acc_ids']);
		$price          = explode(',',$_GET['price']);
		foreach($goods_ids as $key=>$value){
				$result[$key]['goods_id'] = $value;
			foreach($price as $key=>$val){
				$result[$key]['price'] = $val;
				
			}
		}
		
		$type          = intval($_GET['type']);
		$order         = M('order');
		/*1 未付款订单  0已付款订单*/
		if($type==1){
			foreach($result as $k=>$val){
				$data[$k]['order_id']          = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
				$data[$k]['other_order_id']    = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
				$data[$k]['total']             = $val['price'];
				$data[$k]['actual_payment']    = $val['price'];
				$data[$k]['member_id']         = intval($_SESSION['member']['member_id']);
				$data[$k]['is_pay']            = 1;
				$data[$k]['create_time']       = time();
				$data[$k]['is_exam']           = 1;
				$data[$k]['goods_id']          = $val['goods_id'];
				$data[$k]['num']               = 1;
				
				$info         = $order->add($data[$k]);
			}
		
		
		}else{
			foreach($result as $k=>$val){
				$data[$k]['order_id']          = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
				$data[$k]['other_order_id']    = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
				$data[$k]['total']             = $val['price'];
				$data[$k]['actual_payment']    = $val['price'];
				$data[$k]['member_id']         = intval($_SESSION['member']['member_id']);
				$data[$k]['is_pay']            = 0;
				$data[$k]['create_time']       = time();
				$data[$k]['is_exam']           = 1;
				$data[$k]['pay_time']          = time();
				$data[$k]['goods_id']          = $val['goods_id'];
				$data[$k]['num']               = 1;
				
				$info         = $order->add($data[$k]);
			}
		}
	}
	/*对试卷生成订单*/
	public function papers_nopay_order(){
		$goods_ids      = explode(',',$_GET['papers_ids']);
		$price          = explode(',',$_GET['price']);
		foreach($goods_ids as $key=>$value){
				$result[$key]['goods_id'] = $value;
			foreach($price as $key=>$val){
				$result[$key]['price'] = $val;
				
			}
		}
		//$total_price   = intval($_GET['price']);
		$type          = intval($_GET['type']);
		$order         = M('order');
		/*1 未付款订单  0已付款订单*/
		if($type==1){
			foreach($result as $k=>$val){
				$data[$k]['order_id']          = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
				$data[$k]['other_order_id']    = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
				$data[$k]['total']             = $val['price'];
				$data[$k]['actual_payment']    = $val['price'];
				$data[$k]['member_id']         = intval($_SESSION['member']['member_id']);
				$data[$k]['is_pay']            = 1;
				$data[$k]['create_time']       = time();
				$data[$k]['is_exam']           = 0;
				$data[$k]['goods_id']          = $val['goods_id'];
				$data[$k]['num']               = 1;
				
				$info         = $order->add($data[$k]);
			}
		
		
		}else{
			foreach($result as $k=>$val){
				$data[$k]['order_id']          = time().str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
				$data[$k]['other_order_id']    = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
				$data[$k]['total']             = $val['price'];
				$data[$k]['actual_payment']    = $val['price'];
				$data[$k]['member_id']         = intval($_SESSION['member']['member_id']);
				$data[$k]['is_pay']            = 0;
				$data[$k]['create_time']       = time();
				$data[$k]['pay_time']          = time();
				$data[$k]['is_exam']           = 0;
				$data[$k]['goods_id']          = $val['goods_id'];
				$data[$k]['num']               = 1;
				
				$info         = $order->add($data[$k]);
			}
		}
	}
	/*
		
		desc 获取订单列表
	*/
	public function order_list(){
		if(isset($_GET['type']) && isset($_GET['pay'])){
			
		
		$type           = intval($_GET['type']);
		$pay            = intval($_GET['pay']);
		}else{
			$type=1;
			$pay=1;
		}
		$order          = M('order');
		
		$member_id      = $_SESSION['member']['member_id'];
		if($type==1 && $pay==1){
			$leftJoin   = 'left join ae_acc_cat as ac on ac.acc_cat_id = o.goods_id left join ae_exam_cat as ec on ac.exam_cat_id= ec.exam_cat_id';
			$where      = "o.is_del = 0 and o.is_exam= 1 and o.is_pay = 1 and o.member_id = $member_id";
			
			$fields     = 'o.order_id,o.total,o.pay_time,o.num,o.create_time,ac.acc_cat_name,ec.exam_cat_name';
			$count      = $order->alias('o')->join($leftJoin)->where($where)->count();// 查询满足要求的总记录数
			
			$Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$this->assign('model','AccNopay');
			//重新定义展示方法
			$Page->setConfig('prev','上一页');
            $Page->setConfig('next','下一页');
            $Page->setConfig('first','首页');
            $Page->setConfig('last','尾页');
            $Page->setConfig('theme',"共 %TOTAL_ROW% 条记录 %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%");
			
			$data       = $order->alias('o')->join($leftJoin)->where($where)->field($fields)->limit($Page->firstRow.','.$Page->listRows)->select();
			
			foreach($data as $k=>$val){
				$data[$k]['goods_name'] = $data[$k]['exam_cat_name'].'  '.$data[$k]['acc_cat_name'];
				$data[$k]['create_time'] = date('Y-m-d H:i:s',$data[$k]['create_time']);
			}
			
			
		}else if($type==1 && $pay==0){
			$leftJoin   = 'left join ae_acc_cat as ac on ac.acc_cat_id = o.goods_id left join ae_exam_cat as ec on ac.exam_cat_id= ec.exam_cat_id';
			$where      = "o.is_del = 0 and o.is_exam= 1 and o.is_pay = 0 and o.member_id = $member_id";
			
			$fields     = 'o.order_id,o.total,o.pay_time,o.num,o.create_time,ac.acc_cat_name,ec.exam_cat_name';
			
			$count      = $order->alias('o')->join($leftJoin)->where($where)->count();// 查询满足要求的总记录数// 查询满足要求的总记录数
			$Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$this->assign('model','AccPay');
			//重新定义展示方法
			$Page->setConfig('prev','上一页');
            $Page->setConfig('next','下一页');
            $Page->setConfig('first','首页');
            $Page->setConfig('last','尾页');
            $Page->setConfig('theme',"共 %TOTAL_ROW% 条记录 %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%");
			
			$data       = $order->alias('o')->join($leftJoin)->where($where)->field($fields)->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($data as $k=>$val){
				$data[$k]['goods_name'] = $data[$k]['exam_cat_name'].'  '.$data[$k]['acc_cat_name'];
				$data[$k]['create_time'] = date('Y-m-d H:i:s',$data[$k]['create_time']);
				$data[$k]['pay_time'] = date('Y-m-d H:i:s',$data[$k]['pay_time']);
			}
			
		}else if($type==0 && $pay==1){
			$leftJoin   = 'left join ae_papers as p on p.papers_id = o.goods_id left join ae_exam_cat as ec on p.exam_cat_id= ec.exam_cat_id left join ae_acc_cat as ac on ac.acc_cat_id = p.acc_cat_id';
			$where      = "o.is_del = 0 and o.is_exam= 0 and o.is_pay = 1 and o.member_id = $member_id";
			
			$fields     = 'p.papers_id,o.order_id,o.total,o.num,o.create_time,p.papers_name,ac.acc_cat_name,ec.exam_cat_name';
			
			$count      = $order->alias('o')->join($leftJoin)->where($where)->count();// 查询满足要求的总记录数// 查询满足要求的总记录数
			$Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$this->assign('model','PapersNopay');
			//重新定义展示方法
			$Page->setConfig('prev','上一页');
            $Page->setConfig('next','下一页');
            $Page->setConfig('first','首页');
            $Page->setConfig('last','尾页');
            $Page->setConfig('theme',"共 %TOTAL_ROW% 条记录 %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%");
			
			$data       = $order->alias('o')->join($leftJoin)->where($where)->field($fields)->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($data as $k=>$val){
				$data[$k]['goods_name'] = $data[$k]['exam_cat_name'].' '.$data[$k]['acc_cat_name'].' '.$data[$k]['papers_name'];
				$data[$k]['create_time'] = date('Y-m-d H:i:s',$data[$k]['create_time']);
			}
		
			
			
		}else{
			$leftJoin   = 'left join ae_papers as p on p.papers_id = o.goods_id left join ae_exam_cat as ec on p.exam_cat_id= ec.exam_cat_id left join ae_acc_cat as ac on ac.acc_cat_id = p.acc_cat_id';
			$where      = "o.is_del = 0 and o.is_exam= 0 and o.is_pay = 0 and o.member_id = $member_id";
			
			$fields     = 'p.papers_id,o.order_id,o.pay_time,o.total,p.papers_name,o.num,o.create_time,ac.acc_cat_name,ec.exam_cat_name';
			
			$count      = $order->alias('o')->join($leftJoin)->where($where)->count();// 查询满足要求的总记录数// 查询满足要求的总记录数
			$Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
			$this->assign('model','papersPay');
			//重新定义展示方法
			$Page->setConfig('prev','上一页');
            $Page->setConfig('next','下一页');
            $Page->setConfig('first','首页');
            $Page->setConfig('last','尾页');
            $Page->setConfig('theme',"共 %TOTAL_ROW% 条记录 %FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%");
			
			$data       = $order->alias('o')->join($leftJoin)->where($where)->field($fields)->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($data as $k=>$val){
				$data[$k]['goods_name'] = $data[$k]['exam_cat_name'].' '.$data[$k]['acc_cat_name'].' '.$data[$k]['papers_name'];
				$data[$k]['create_time'] = date('Y-m-d H:i:s',$data[$k]['create_time']);
				$data[$k]['pay_time'] = date('Y-m-d H:i:s',$data[$k]['pay_time']);
			}
			
			// $result  = array(
			// 'data'  =>$data,
			// 'page'  =>$Page->show()// 分页显示输出
			// );
		}
		$this->siteinfo();
		//echo json_encode($result);
		$this->assign('page',$Page->show());
		$this->assign('data',$data);
		$this->display('personal');
	}
	
	public function demo2()
	{
		// ***********************必须先将extension=php_curl扩展打开
		// 接收电话号并且执行发送短信的功能
		// 1.接收电话号码
		// if(isset($_GET)){
			// $this->error('非法操作',U("Register/register"));
		// exit;
		// }
		$phone = I('post.phone');
		// mt_rand()
		$rand = mt_rand(100000,999999);
		$content = "欢迎注册会计网校！您的验证码是$rand,验证码在15分钟内有!";

		// 2.必须保存生成的验证码
		$code = array('code'=>$rand,'time'=>time());
		session('code',$code);

		// 3.执行发送
		$sms = new \Org\Sms\SmsBao('LC765682204','765682204ppdh');
		$data = $sms->sendSms($phone, $content);
	
		// 4.返回数据
		$this->ajaxReturn($data);
	}
}
?>
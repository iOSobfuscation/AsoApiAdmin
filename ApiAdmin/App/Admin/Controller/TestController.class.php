<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
/**
 * 后台网站设置模块
 * @author ditser
 */
class TestController extends CommonController {
    
	//单选
	public function radio($file_name){
		$file_name= './Public/static/test_excel/test-radio.xls';		
		Vendor("PHPExcel.PHPExcel");   // 这里不能漏掉
		Vendor("PHPExcel.PHPExcel.IOFactory");		
		$objReader =  \PHPExcel_IOFactory::createReader('Excel5');
		
		$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumn = $sheet->getHighestColumn(); // 取得总列数
		$arr = array();		
		for($i=2;$i<$highestRow+1;$i++){
			$arr2 = array();
			foreach (range('A',$highestColumn) as $va){
				$key = $objPHPExcel->getActiveSheet()->getCell($va."1")->getValue();
				if($key=='radio_option'){
					$jsonStr = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();					
					$arr2[$key] = json_encode(explode(",",preg_replace("/，/" ,',' ,$jsonStr)));
				}else{
					$arr2[$key] = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();
				}
				$arr2['create_time'] = time();
			}
			$arr[] = $arr2;
		}
		$radioM = M("radio");
		$radioM->addAll($arr);
	}
	
	
	//多选
	public function check($file_name){
		$file_name= './Public/static/test_excel/test-check.xls';
		Vendor("PHPExcel.PHPExcel");   // 这里不能漏掉
		Vendor("PHPExcel.PHPExcel.IOFactory");
		$objReader =  \PHPExcel_IOFactory::createReader('Excel5');
	
		$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumn = $sheet->getHighestColumn(); // 取得总列数
		$arr = array();
		for($i=2;$i<$highestRow+1;$i++){
			$arr2 = array();
			foreach (range('A',$highestColumn) as $va){
				$key = $objPHPExcel->getActiveSheet()->getCell($va."1")->getValue();
				if($key=='check_option' || $key=='check_answer'){
					$jsonStr = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();
					$arr2[$key] = json_encode(explode(",",preg_replace("/，/" ,',' ,$jsonStr)));
				}else{
					$arr2[$key] = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();
				}
				$arr2['create_time'] = time();
			}
			$arr[] = $arr2;
		}
		$checkM = M("check");
		$checkM->addAll($arr);
	}
	
	
	
	//判断题
	public function judgment($file_name){
		$file_name= './Public/static/test_excel/test-judgment.xls';
		Vendor("PHPExcel.PHPExcel");   // 这里不能漏掉
		Vendor("PHPExcel.PHPExcel.IOFactory");
		$objReader =  \PHPExcel_IOFactory::createReader('Excel5');
	
		$objPHPExcel = $objReader->load($file_name,$encode='utf-8');
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestRow(); // 取得总行数
		$highestColumn = $sheet->getHighestColumn(); // 取得总列数
		$arr = array();
		for($i=2;$i<$highestRow+1;$i++){
			$arr2 = array();
			foreach (range('A',$highestColumn) as $va){
				$key = $objPHPExcel->getActiveSheet()->getCell($va."1")->getValue();
				$arr2[$key] = $objPHPExcel->getActiveSheet()->getCell($va.$i)->getValue();
				$arr2['create_time'] = time();	
			}
			$arr[] = $arr2;
		}
		$judgmentM = M("judgment");
		$judgmentM->addAll($arr);
	}
	
	
	
	
	
	/**
	 * 安全URL编码
	 * @param type $data
	 * @return type
	 */
	public function encode($data) {
		$str = str_replace(array('+', '/', '=','i', 'z','b','A','t','o','p','q','j','e','B'), array('-', '_', '','!','@','#','$','%','^','~','&','*','(',')'), base64_encode(serialize($data)));
		return $str;
	}
	
	/**
	 * 安全URL解码
	 * @param type $string
	 * @return type
	 */
	public function decode($string) {
		$data = str_replace(array('-', '_','!','@','#','$','%','^','~','&','*','(',')'), array('+', '/','i', 'z','b','A','t','o','p','q','j','e','B'), $string);
		$mod4 = strlen($data) % 4;
		($mod4) && $data .= substr('====', $mod4);
		return unserialize(base64_decode($data));
	}
	
	public function replace($str){
		return str_replace(array('i', 'z','b','A','t','o','p'), array('!','@','#','$','%','^','~'), $str);
	}
	
	public function re_replace($str){
		return str_replace(array('!','@','#','$','%','^','~'),array('i', 'z','b','A','t','o','p'), $str);
	}
	
	
	public function ed(){
		$data = '嚼吸要=有的采集器=丧权=辱=国';
		$str = $this->encode($data);
		dump($data)."<br>";
		dump($str)."<br>";
		dump($this->decode($str));
	}
	
	
}
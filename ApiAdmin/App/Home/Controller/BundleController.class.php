<?php
	namespace Home\Controller;
use Home\Controller\CommonController;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:GET');
// 响应头设置
header('Access-Control-Allow-Headers:x-requested-with,content-type');
class BundleController extends CommonController{
		public function bundle_list(){
			$bundle   =  M('bundle','aso_','mysql://root:ttttottttomysql@101.200.91.203/aso_db');
			$data     = $bundle->select();
			$this->assign('data',$data);
			$this->display('bundle_list');
		}
}
<?php
namespace Plant\Model;
use Think\Model;

class CategoryModel extends Model{
	protected $tableName = 'category';
	protected $pk        = 'catid';
	
	/**
	 * 获取导航
	 */
	public function getNavigation($parentid = 0){
		$field = array('catid', 'catname', 'type', 'url');
		$order = '`listorder` ASC,`catid` DESC';
		$data  = $this->field($field)->where(array('parentid'=>$parentid, 'ismenu'=>'1'))->order($order)->select();
		if (is_array($data)){
			foreach ($data as &$arr){
				$arr['children'] = $this->getNavigation($arr['catid']);
			}
		}else{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 当前位置
	 * @param $id 菜单id
	 */
	public function currentPos($catid) {
		$field = array('catid', 'catname', 'type', 'parentid', 'url');
		$info  = $this->field($field)->where(array('catid'=>$catid))->find();
		$str   = '';
		if($info['parentid']) {
			$str = $this->currentPos($info['parentid']);
		}
		return $str . $info['catname'] . ' &gt; ';
	}
	
	/**
	 * 获取二级菜单
	 */
	public function getSideList($parentid){
		if(!$parentid) return array();
		$field = array('catid', 'catname', 'type', 'parentid', 'url');
		$order = '`listorder` ASC,`catid` DESC';
		$list  = $this->field($field)->where(array('parentid'=>$parentid, 'ismenu'=>'1'))->order($order)->select();
		
		return $list ? $list : array();
	}

}

<?php
namespace Admin\Model;
use Think\Model;

class AdminModel extends Model{
    protected $tableName = 'admin';
	protected $pk        = 'userid';
	public    $error;
	
	/**
	 * 登录验证
	 */
	public function login($username, $password){
	    $times_db = M('times');
	    
        //查询帐号
        $r = $this->where(array('username'=>$username))->find();
        if(!$r){
            $this->error = '管理员不存在';
            return false;
        }
		
	    //密码错误剩余重试次数
        $rtime = $times_db->where(array('username'=>$username, 'isadmin'=>'1'))->find();
        if($rtime['times'] >= C('MAX_LOGIN_TIMES')) {
            $minute = C('LOGIN_WAIT_TIME') - floor((time()-$rtime['logintime'])/60);
            if ($minute > 0) {
                $this->error = "密码重试次数太多，请过{$minute}分钟后重新登录！";
                return false;
            }else {
                $times_db->where(array('username'=>$username))->delete();
            }
        }

		$password = md5(md5($password).$r['encrypt']);
        $ip       = get_client_ip(0, true);

        if($r['password'] != $password) {
            if($rtime && $rtime['times'] < C('MAX_LOGIN_TIMES')) {
                $times = C('MAX_LOGIN_TIMES') - intval($rtime['times']);
                $times_db->where(array('username'=>$username))->save(array('ip'=>$ip,'isadmin'=>1));
                $times_db->where(array('username'=>$username))->setInc('times');
            } else {
                $times_db->where(array('username'=>$username,'isadmin'=>1))->delete();
                $times_db->add(array('username'=>$username,'ip'=>$ip,'isadmin'=>1,'logintime'=>time(),'times'=>1));
                $times = C('MAX_LOGIN_TIMES');
            }
            $this->error = "密码错误，您还有{$times}次尝试机会！";
            return false;
        }
        
        $times_db->where(array('username'=>$username))->delete();
        $this->where(array('userid'=>$r['userid']))->save(array('lastloginip'=>$ip,'lastlogintime'=>time()));
        
		$set_db					= ('set');
		$role_db				= ('admin_role');
		$dep_db					= ('department');
		
		//取出公司名称
		$site_name				= get_field("set", "mp_", "site_name", "id = 1");
		
		//取出当前用户部门，角色
		$user_dep_name			= get_field("department", "mp_", "name", "id = ".$r['department_id']);
		$user_role_name			= get_field("admin_role", "mp_", "rolename", "id = ".$r['roleid']);
		
        session('userid', $r['userid']);
		session('depname', $user_dep_name);//部门名称
		session('depid', $r['department_id']);//部门id
		session('eid', $r['e_id']);
        session('rolename', $user_role_name);//角色名称
        session('roleid', $r['roleid']);//角色id
        session('realname', $r['realname']);//用户姓名
		
        session('site_name', $site_name);//用户姓名
		
        cookie('userid', $r['userid']);
		cookie('depname', $user_dep_name);//部门名称
		cookie('depid', $r['department_id']);//部门id
		cookie('eid', $r['e_id']);
        cookie('rolename', $user_role_name);//角色名称
        cookie('roleid', $r['roleid']);//角色id
        cookie('realname', $r['realname']);//用户姓名
		
        cookie('site_name', $site_name);//用户姓名
        
        return true;
	}
	
	/**
	 * 获取用户信息
	 */
	public function getUserInfo($userid){
	    $admin_role_db = D('AdminRole');
	    $info = $this->field('password, encrypt', true)->where(array('userid'=>$userid))->find();
		if($info) $info['rolename'] = $admin_role_db->getRoleName($info['roleid']);    //获取角色名称
	    return $info;
	}
    
	/**
	 * 修改密码
	 */
	public function editPassword($userid, $password){
		$userid = intval($userid);
		if($userid < 1) return false;
		$passwordinfo = password($password);
		return $this->where(array('userid'=>$userid))->save($passwordinfo);
	}
}
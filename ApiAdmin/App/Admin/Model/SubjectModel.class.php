<?php
	namespace Admin\Model;
	use Think\Model;
	class SubjectModel extends Model{
		public function subject_list(){
			$subject       = M('subject');
			
			return $subject->where('is_del = 0')->field('subject_id,subject_name,create_time')->order('create desc')->select();
			
		}
		
		public function subject_save($id,$data){
			$subject       = M('subject');

			return         $subject->where("subject_id = $id")->save($data);	
		}
		
		public function subject_add($data){
			$subject       = M('subject');
			
			return          $subject->add($data);
		}
	}
	
<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Admin\Model\AdminMenuModel;
/**
 * 后台自定义菜单管理模块
 * @author ditser
 */
class MenuController extends CommonController {
    /*--------------------- 自定义菜单管理模块-----------------------*/
	private $model_name = "自定义菜单";
	private $model_code = "menu";
	/*初始化 判断是否具有权限*/
	// public function _initialize(){
		// if(ACTION_NAME!='login' || ACTION_NAME!='index' || ACTION_NAME!='sysinfo' ){
		// $action_name         = ACTION_NAME;
		// }
		// $uid                 = session('userid');
		
		// $roleid              = get_field("admin", "online_", "roleid", "userid = ".$uid);
		// $listorder           = get_field("admin_role", "online_", "listorder", "id = ".$roleid);
		// $now_role_id         = get_field("admin_auth", "online_", "id", "menu_no = '$action_name'");
		// $listorderData       = explode(',',$listorder);
		// if(!in_array($now_role_id,$listorderData)){
			// $this->error('您没有此权限!');
		// }
    	
    // }
    
	 //列表
	public function menu_list(){
		
		$menu                           = new AdminMenuModel();
		
		if(isset($_GET['id'])){
			$id         = intval($_GET['id']);
			$data       = $menu->sub_menu_data($id);
			$this->assign('subdata',$data);
			$this->assign('parent_id',$id);
			
		}else{
			
			$data       = $menu->menu_list_data();
			
			$this->assign('data',$data);
		}
		//dump($data);
		$this->display('index');
	}
	
	//新增
	public function menu_add(){
		
		$menu                               = new AdminMenuModel();
		if(IS_POST){
			
			$data['name']					= $_POST['name'];
			$data['parent_id']              = $_POST['parent_id'];
			$data['menu_no']			    = $_POST['menu_no'];
			$data['order']					= $_POST['order'];
			$data['url']                    = trim($_POST['url']);
			
			
			$name							= $data['name'];
			$order							= $data['order'];
			if(empty($name)){
				$this->error('名称不能为空');
				exit;
			}
			if(empty($order)){
				$this->error('排序不能为空');
				exit;
			}
			
			
			
			
			
			$data['create_time']			= time();
			$data['create_id']				= session('userid');
			
			$menu_id                        = $menu->insert_data($data);		
			if($menu_id){
				$this->success('增加成功', 'menu_list');
			}else{
				$this->error('增加失败');
			}
		}else{
			$parent_id            = intval($_GET['parent_id']);
			$parent_data          = $menu->get_fields('admin_menu',"id = {$parent_id} and is_del = 0",'name');
			
			$this->assign('parent_data',$parent_data);
			$this->assign('parent_id',$parent_id);
			$this->assign('user_menu_list', user_menu_list());
			$this->assign('parentid', $parentid);
			$this->assign('parent_name', $parent_name);
			$this->assign('material_list', $material_list);
			$this->assign('model_list', $model_list);
			
			$this->assign('model_name', $model_name);
			$this->assign('fun_name', $fun_name);
			$this->assign('fun_code', $fun_code);
			$this->assign('fun_url', $fun_url);
			$this->assign('menu_list', $menu_list);
			$this->display('menu_add');
		}
		
		
	}
	
	//编辑
	public function menu_edit($id){
		
		//权限判断
		$model_name							= $this->model_name;
		$fun_name							= "编辑".$model_name;
		$fun_code							= "menu_info";
		$fun_url							= "/admin/menu/menu_add";
		$role_code							= "menu_edit";
		
		$menu                               = new AdminMenuModel();
		if(IS_POST){
			$id                             = intval($_POST['id']);
			$data['name']					= $_POST['name'];
			$data['parent_id']              = $_POST['parent_id']?$_POST['parent_id']:0;
			$data['menu_no']                = $_POST['menu_no'];
			$data['order']					= $_POST['order'];
			$data['url']                    = trim($_POST['url']);
			$data['up_id']                  = session('userid');
			$data['up_time']                = time();
			
			
			if(empty($data['name'])){
					
				$this->error('名称不能为空');
				exit;
			}
			
			$res                             = $menu->save_data("id = $id",$data);
			
			if($res){
				$this->success('编辑成功', 'menu_list');
			}else{
				$this->error('编辑失败');
			}
			
		}else{
			/*取出当前菜单信息*/
			$id                  =  intval($_GET['id']);
			$info                =  $menu->get_fields('admin_menu',"id= {$id} and is_del=0",'id,parent_id,name,menu_no,url,order');
			/*取出父级菜单列表*/
			$parent_menu         =  $menu->parent_menu();

		
			$this->assign('info',$info);
			$this->assign('parent_menu',$parent_menu);
			$this->display('menu_add');
			
		}
	}
	
	//发布
	public function menu_release(){
		//权限判断
		$model_name						= $this->model_name;
		$model_code						= $this->model_code;
		$fun_name						= $model_name."列表";
		$fun_code						= "menu_list";
		$fun_url						= "/admin/menu/menu_list";
		$role_code						= "menu_list";
		if(IS_POST){
			if($_POST['release'] == 0){
				$res					= release_consmenu();
				if($res['errcode'] == 0){
					$this->success('发布成功', '/admin/menu/menu_list');
				}else{
					print_r($res);
					echo $res['errcode'];
					exit;
					//$this->error('发布失败');
				}
			}
		}
	}
	
	//删除
	public function menu_del($id){
		
		$db									= M('admin_menu');
		
		$sub_res							= $db->where("parent_id = '$id'")->select();
		if($sub_res!=null){
			$this->error('此菜单下有子菜单！');
		}else{
			$res= $db->where("id = '$id'")->delete();
			if($res){
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}
		}
	}
	
	//验证逻辑
	//验证添加子类
	public function check_menu_add(){
		$menu_id							= $_POST['menu_id'];
		
		if($menu_id == NULL){
			//判断菜单id是否为空
			$res['status']					= 1;
			$res['msg']						= "菜单ID不能为空";
		}elseif($menu_id == 0){
			//增加一级菜单
			$cons_menu_total				= cons_menu_total(0);
			if($cons_menu_total >= 3){
				$res['status']				= 2;
				$res['msg']						= "最多只能有三个一级菜单";
			}else{
				$res['status']				= 0;
			}
		}else{
			$cons_menu_total				= cons_menu_total($menu_id);
			if($cons_menu_total >= 5){
				$res['status']				= 3;
				$res['msg']					= "每个一级菜单最多只能有五个二级菜单";
			}else{
				$res['status']				= 0;
			}
		}
		//取出一级菜单名称
		//$parent_id							= get_field('menu', 'mp_', 'parentid', 'id = '.$menu_id);
		$parent_name						= get_field('menu', 'mp_', 'name', 'id = '.$menu_id);
		$res['parent_name']					= $parent_name;
		$res								= json_encode($res, true);
		echo $res;
	}
	
	
	
}
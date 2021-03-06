<?php

require_once APPLICATION_PATH.'/utils/ImageUtils.php';
require_once 'BaseController.php';
require_once APPLICATION_PATH.'/utils/DatabaseUtils.php';
require_once APPLICATION_PATH.'/utils/LogUtils.php';
require_once APPLICATION_PATH.'/utils/CookieUtils.php';

class IndexController extends BaseController
{
    public function init()
    {
        /* Initialize action controller here */
    	parent::init();
//     	echo $this->db;
    }

    public function indexAction()
    {
    	$check = $this->getRequest()->getParam('login');
        $this->image_generate();
        if($check === 'nologin'){
        	$this->redirect("index.html#login=nologin");
        } else{
        	$this->render();
        }
    }

    public function codeAction(){
    	$this->image_generate();
    	echo $this->view->ImgDir.$this->view->captchaId;
        $this->_helper->viewRenderer->setNoRender(true);
    	
    }

    // 验证模块
    public function loginAction(){
        $request = $this->getRequest();
        $check = $request->getParam("ckeckCode","default");
        $name = $request->getParam('name','default');
        $password = $request->getParam("password",'default');
        $user = array();
        $roleNames = '';
    	$codeSession = new Zend_Session_Namespace('checkcode');
        $checkCode = $codeSession->code;
        
        if(strtolower($check) !== strtolower($checkCode)){
        	echo '验证码输入错误,请重新输入';
        	$this->_helper->viewRenderer->setNoRender(true);
        	return;
        }
        // database
        if($this->checkDb($name, $password,$user,$roleNames)){
           if($roleNames === ''){
           	   echo '用户没有登录权限,请先到邮箱验证';
           	   $this->_helper->viewRenderer->setNoRender(true);
           	   return;
           }
           // put the role name and result in session
           $userSession = new Zend_Session_Namespace('user');
           $userSession->user = $user;
           $userSession->roleNames = $roleNames;

           // 将记录保存到cookie
           $hascookie = $request->getParam("check");
           $hascookie = $hascookie[0];
           $cookie = null;
           if($hascookie === 'on'){
           	    if(!isset($cookie)){
           	        $cookie = new CookieUtils();
           	    }
           	    $cookie->setName('user[name]');
           	    $cookie->setValue($name);
           	    $cookie->setExpire(time() + 60*60*24*7);
           	    $cookieState = $cookie->setCookie();
           	    $cookie->setName("user[password]");
           	    $cookie->setValue($password);
           	    $cookie->setCookie();
           } else{
           	   // 清除cookie
           	   if(isset($_COOKIE['user']) && isset($_COOKIE['user']['name'])){
           	   	   if(!isset($cookie)){
           	   	   	   $cookie = new CookieUtils();
           	   	   }
           	   	   $cookie->setName('user[name]');
           	   	   $cookie->setValue('');
           	   	   $cookie->setExpire(-3600);
           	   	   $cookie->setCookie();
           	   	   $cookie->setName('user[password]');
           	   	   $cookie->setValue('');
           	   	   $cookie->setExpire(-3600);
           	   	   $cookie->setCookie();
           	   }
           }
           
           echo 'success';
           $this->_helper->viewRenderer->setNoRender(true);
           return;
        } else{
           echo '密码错误,请重新输入';
           $this->_helper->viewRenderer->setNoRender(true);
           return;
        }
        
    }
    
    private function image_generate(){
    	$im = new ImageUtils(APPLICATION_PATH.'/utils/simsun.ttf', 'checkcode', './image/code/');
    	/**
    	 codes: 0 session
    	 1 captcha
    	*/
    	$codes = $im->generateCheckCode();
    	
    	$this->codeSession = $codes['session'];
    	$captcha = $codes['captcha'];
    	
    	$this->view->ImgDir = $captcha->getImgDir();
    	$this->view->captchaId = $captcha->getId(); //获取文件名，md5编码
    	$this->codeSession->code=$captcha->getWord(); //获取当前生成的验证字符串
    }
    
    /**
     * Check the user and the role
     * @param string $name the user nickname or number
     * @param string $password
     * @param array $user  the user attribute
     * @param string $roleName
     * @return boolean
     */
    private function checkDb($name,$password,&$userMessage,&$roleName){
    	if(!isset($name) || !isset($password)){
    		die('The name or password is not set');
    	}
    	require_once APPLICATION_PATH.'/models/User.php';
    	// 匹配全部数字
    	$pattern = '/^\d+$/i';
    	$datautils = new DatabaseUtils;
    	$user = new User();
    	$datas = null;
    	$flag = false;
    	$password = md5($password);
    	if(preg_match($pattern, $name)){
    		// number
    		$datas = array('number' => $name);
    	} else{
    		$datas = array('nickname' => $name);
    	}
    	
    	$resultset = $datautils->fetchData($user, $this->db, $datas);
    	
        if($password !== $resultset->password){
            return false;
        }
        
        foreach ($resultset as $key => $value){
        	if(!isset($value) || $value === ""){
        		$userMessage[$key] = '暂无';
        	}else{
        	    $userMessage[$key] = $value;
        	}
        }
        
        $roles = $datautils->findManyToManyRow($resultset, 'Role', 'RoleUser', 'User', 'Role');
        for ($i = 0; $i < count($roles); $i++){
        	$t = $roles[$i];
        	
        	foreach ($t as $key => $value){
        		if($key == 'name'){
        			if($value == 'login') $flag = true;
        			$roleName .= $value;
        		}
        	}
        }
        if($flag === false){
        	$roleName = '';
        }
        return true;
    }
    
}


<?php error_reporting(E_ALL);
session_start();
include 'database_connection.php';

//$con = mysqli_connect('localhost','acecare7_newace','Aceace88.com','acecare7_newace');
//$con = mysqli_connect('127.0.0.1','root','root','live_chat','3307');

date_default_timezone_set('America/Vancouver');

$action=$_REQUEST['action'];
 



if($action=='showchat_in_admin')showchat_in_admin();
if($action=='add_user')add_user();
if($action=='savechat')savechat();
if($action=='showchat')showchat();
if($action=='getonline_users')getonline_users();
if($action=='show_admin_chat')show_admin_chat();
if($action=='total_all_unread_msg')total_all_unread_msg();
if($action=='delete_user')delete_user();
if($action=='insert_update_admin')insert_update_admin();
if($action=='admin_logout')admin_logout();
if($action=='get_recent_msg')get_recent_msg();
if($action=='check_admin_online_onchat')check_admin_online_onchat();




function add_user(){
    global $con;
    $name=$_REQUEST['name'];
    $email=$_REQUEST['email'];
    $sql="INSERT INTO users(name,email,datetime) VALUES ('".$name."','".$email."','".date('Y-m-d H:i:s')."')";
    mysqli_query($con,$sql);
    $_SESSION['user_id']=mysqli_insert_id($con);
    $_SESSION['user_name']=$name;
    echo mysqli_insert_id($con);
    
}
/***************************************************/
function savechat(){ 
    global $con;
    $user_id=$_REQUEST['user_id'];
    $admin_id=$_REQUEST['admin_id'];
    $session_id=$_REQUEST['session_id'];
    $message=$_REQUEST['message'];
    $sql="INSERT INTO chat(user_id,session_id,message,admin_id,datetime) VALUES ('".$user_id."','".$session_id."','".$message."','".$admin_id."','".date('Y-m-d H:i:s')."')";
    mysqli_query($con,$sql);
    
    if($user_id !=0){
        //update_user($user_id);
        showchat();
    }
     else {
         update_user_byadmin($session_id,$admin_id);
         //insert_update_admin($admin_id);
         insert_update_admin();
         show_admin_chat();
        }
}
/***************************************************/
function showchat(){
    //global $con;
    $dbObj = new DatabaseConnection();
    $connectionObj = $dbObj->dbConnection();
    $session_id=$_REQUEST['session_id'];
        
    update_msg_read_status('user',$session_id); 
        
    $sql = "SELECT * FROM chat where  session_id='".$session_id."'";
    $result=mysqli_query($connectionObj,$sql);

$text='';
    while($row = mysqli_fetch_array($result)) {
         if($row['user_id'] != 0  ){
             $class="pull-left";
             $src="http://placehold.it/50/55C1E7/fff&text=U";
             }else {
                 $class="pull-right";
                 $src="http://placehold.it/50/FA6F57/fff&text=ME";
             }
         
    $text.='<li class="left clearfix"><span class="chat-img '.$class.'">
                            <img src="'.$src.'" alt="User Avatar" class="img-circle" />
                        </span>
                            <div class="chat-body clearfix">
                                <div class="header">
                                    <strong class="primary-font">'.$_SESSION['user_name'].'</strong> <small class="pull-right text-muted">
                                        <span class="glyphicon glyphicon-time"></span>12 mins ago</small>
                                </div>
                                <p>'.$row['message'].'
                                </p>
                            </div>
                        </li>';
    }
echo $text;
}
/***************************************************/
function show_admin_chat(){
    global $con;
    $session_id=$_REQUEST['session_id'];
        update_msg_read_status('admin',$session_id);
        
        
    $sql = "SELECT * FROM chat where  session_id='".$session_id."'";
    $result=mysqli_query($con,$sql);

$text='';
    while($row = mysqli_fetch_array($result)) {
        
          if($row['user_id'] != 0  ){
             $class="pull-left";
             //$src="http://placehold.it/50/55C1E7/fff&text=U";
                         $src="/acesys/adminchat/you.png";
             }else {
                 $class="pull-right";
                  //$src="http://placehold.it/50/FA6F57/fff&text=A";
                                  $src="/acesys/adminchat/a.png";
             }
         
    $text.='<li class="left clearfix">
                     <span class="chat-img1 '.$class.'">
                     <img src="'.$src.'" alt="User Avatar" class="img-circle">
                     </span>
                     <div class="chat-body1 clearfix">
                        <p>'.$row['message'].'</p>
                        <div class="chat_time pull-right">'.date("d-m-Y h:i A",strtotime($row['datetime'])).'</div>
                     </div>
                  </li>';
  }

//echo $text;

    echo json_encode(array('chat'=>$text,'online_users'=>getonline_users(), 'admin_online'=>check_admin_online_onchat()  ));
}
/***************************************************/
function getonline_users(){
    global $con;
    $admin_id=$_REQUEST['admin_id'];
       $sql= "SELECT * from users where chatting=0 or chat_with_admin_id='".$admin_id."'";
   // echo $datetime=strtotime(date('Y-m-d H:i:s',strtotime('-15 minutes')));
    //echo $sql= "SELECT * from users where id in(select user_id from chat where datetime >='".$datetime."')";
    $result = mysqli_query($con,$sql);
    $text='';$unread_msg=0;
     if(mysqli_num_rows($result) > 0){ 
    $i=1;
        while($row = mysqli_fetch_array($result)) { 
            $unread=count_unread_msg("admin",$row['id']);
            $unread_msg = $unread_msg + $unread;
            
    /*$text.='<li class="left clearfix"  rowid="'.$i.'"  onclick=showchat('.$row['id'].','.$row['id'].',"'.$row['name'].'",'.$i.')>*/
    
    $text.='<li class="left clearfix"  rowid="'.$i.'"  username="'.$row['name'].'" onclick=showchat('.$row['id'].','.$row['id'].','.$i.')>
                <a href="javascript:void(0)"   userid="'.$row['id'].'">
                     <span class="chat-img pull-left">
                     <!--<img src="http://placehold.it/50/55C1E7/fff&text=U" alt="User Avatar" class="img-circle">-->
                     <img src="/acesys/adminchat/you.png" alt="User Avatar" class="img-circle">
                     </span>
                     <div class="chat-body clearfix">
                        <div class="header_sec">
                           <strong class="primary-font username">'.$row['name'].'</strong> <strong class="pull-right small_date">
                           '.date('d-m-Y h:i A',strtotime($row['datetime'])).'</strong>
                        </div>
                        <div class="contact_sec">
                           <strong class="primary-font"></strong>'; 
                     if($unread !=0)      
                            $text.='<span class="badge pull-right">'.$unread.'</span>';
                        
           $text.='</div>
                     </div>
                  </a></li>';
//<a class="pull-right" href="javascript:void(0)" onclick=delete_user('.$row['id'].')><i class="fa fa-close"></i></a>


    
    $i++;
     } 
 }
    //echo $text.' <input type="hidden" id="unread_msg" value="'.$unread_msg.'">';
     $text.= ' <input type="hidden" id="unread_msg" value="'.$unread_msg.'">';
    
    
    //when called by wordpress_cake_chat page then use "echo"  if called by function on custom_functions page then use "return"
    if($_REQUEST['action'] == 'getonline_users')
       echo json_encode(array('online_users' => $text));
    else  return $text;
     //echo json_encode(array('online_users' => $text));
}

/***********************************************/
function insert_update_admin(){
    global $con; 
    $admin_id=$_REQUEST['admin_id'];
        
         $sql="select * from admin where admin_id ='".$admin_id."'";
        $result=mysqli_query($con,$sql);
         if(mysqli_num_rows($result) > 0){
               $sql2="update admin set last_activity='".date('Y-m-d H:i:s')."'  where admin_id=".$admin_id;
              mysqli_query($con,$sql2);
          }
         else{       $admin_name=$_REQUEST['admin_name'];
             $sql2="insert into admin(admin_id,name,last_activity) values ('".$admin_id."','".$admin_name."','".date('Y-m-d H:i:s')."')";  
              mysqli_query($con,$sql2);
         }
        
}
/***********************************************/
function update_user_byadmin($user_id,$admin_id){
              global $con;
               $sql="update users set chatting='1' ,chat_with_admin_id='".$admin_id."' where id='".$user_id."'";
              mysqli_query($con,$sql);
    
}
/*
function update_user($user_id){
              global $con;
               $sql="update users set last_activity='".date('Y-m-d H:i:s')."' where id='".$user_id."'";
              mysqli_query($con,$sql);
    
}*/
/***********************************************/
function update_msg_read_status($admin_or_user,$session_id){
     global $con;
     //msg read admin  will be seen/updated by user
     if($admin_or_user =='user')$sql="update chat set msg_read='1' where session_id='".$session_id."' and user_id=0";
     //msg read  user will be seen/updated by admin
     if($admin_or_user =='admin')$sql="update chat set msg_read='1' where session_id='".$session_id."' and admin_id=0";
              mysqli_query($con,$sql);
    
}
/**********************count unread by user id *************************/
function count_unread_msg($admin_or_user,$session_id){
     global $con;
     if($admin_or_user =='user')$sql="select count(*) as count from chat where user_id='0' and msg_read='0' and session_id='".$session_id."'";
     
     if($admin_or_user =='admin')$sql="select count(*) as count from chat where admin_id='0' and msg_read='0' and session_id='".$session_id."'";
             $result= mysqli_query($con,$sql);
    if(mysqli_num_rows($result) >0)$row=mysqli_fetch_array($result);
    return $row['count'];
}
/***********************************************/
function total_all_unread_msg(){
     //global $con;
      $dbObj = new DatabaseConnection();
      $connectionObj = $dbObj->dbConnection();
        //$admin_id=$_REQUEST['admin_id'];
      //$sql="select count(*) as count from chat where (admin_id=".$admin_id." or admin_id='0') and msg_read='0' ";
    $sql="select count(*) as count from lh_chat where unanswered_chat=1";
     
      $result= mysqli_query($connectionObj,$sql);
      
    if(mysqli_num_rows($result) >0)$row=mysqli_fetch_array($result);
    //echo $row['count'];

    $unread_msg=$row['count'];
    
    $data=array('unread'=>$unread_msg);
    echo json_encode($data);
    
}
/***********************************************/
function delete_user(){
     global $con;
     $user_id=$_REQUEST['user_id'];
       $sql= "delete  from users where id=".$user_id;
         //$sql= "update  users set status=0 where  id=".$user_id;
       mysqli_query($con,$sql);
       $sql= "delete  from chat where session_id=".$user_id;
         //  $sql= "update  chat status=0 where session_id=".$user_id;
       mysqli_query($con,$sql);
    
}
function admin_logout(){
     global $con; 
      $admin_id=$_REQUEST['admin_id'];
        
         $sql2="update admin set last_activity='".date('Y-m-d H:i:s')."' , status=0 where admin_id=".$admin_id;
              mysqli_query($con,$sql2);

}
/***********************************************/
function get_recent_msg(){
     global $con;
        $admin_id=$_REQUEST['admin_id'];
       // $sql="select a.*,b.name as user_name from chat as a left join users as b  on a.user_id=b.id where a.msg_read=0 and a.datetime >= '".date('Y-m-d')."' and a.admin_id=0 order by a.chat_id desc limit 3";
        
        $sql1= "SELECT max(chat_id) as chat_id from chat where msg_read=0 and admin_id=0 group by user_id order by chat_id desc";
        $result1= mysqli_query($con,$sql1);
        $chat_id=array();
        while($row=mysqli_fetch_array($result1)){
            $chat_id[]=$row['chat_id'];
        }
         $chat_id=implode(',',$chat_id);
        
         $sql="select a.*,b.name as user_name from chat as a left join users as b  on a.user_id=b.id  where chat_id in('".$chat_id."')   order by a.chat_id desc ";
     
      $result= mysqli_query($con,$sql);
      
      $content='';
    if(mysqli_num_rows($result) >0){
        while($row=mysqli_fetch_array($result)){
            $content[]='<li><div onclick=showChat('.$row['user_id'].') >
                   <div ><span class="pull-left"><strong>'.$row['user_name'].'</strong></span> <span class="pull-right small_date">'.date('d-m-Y h:i A',strtotime($row['datetime'])).'</span></div>
                   <div class="full-width">'.$row['message'].'</div></div></li>';
        }
        $content='<ul>'.implode(' ',$content).'</ul>';
    }
    
    return $content;
    
}
/***********************************************/
function check_admin_online_onchat(){
     global $con;
      $current_datetime=date('Y-m-d H:i:s',strtotime('2 minutes ago'));
      $sql="select * from admin where last_activity >=  '".$current_datetime."'";
      $result= mysqli_query($con,$sql);
      
       //if(mysqli_num_rows($result) >0) echo 'yes'; else echo 'no';
       if(mysqli_num_rows($result) >0) return 'yes'; else return 'no';


}

?>

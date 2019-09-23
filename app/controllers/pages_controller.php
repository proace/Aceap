<?php
/* SVN FILE: $Id: pages_controller.php 4786 2007-04-05 17:57:00Z phpnut $ */

/**
 * Short description for file.
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *                              1785 E. Sahara Avenue, Suite 490-204
 *                              Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright       Copyright 2005-2007, Cake Software Foundation, Inc.
 * @link                http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package         cake
 * @subpackage      cake.cake.libs.controller
 * @since           CakePHP(tm) v 0.2.9
 * @version         $Revision: 4786 $
 * @modifiedby      $LastChangedBy: phpnut $
 * @lastmodified    $Date: 2007-04-05 12:57:00 -0500 (Thu, 05 Apr 2007) $
 * @license         http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Short description for class.
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package     cake
 * @subpackage  cake.cake.libs.controller
 */
class PagesController extends AppController{

/**
 * Enter description here...
 *
 * @var unknown_type
 */
     var $name = 'Pages';

/**
 * Enter description here...
 *
 * @var unknown_type
 */             
    var $helpers = array('Html');
    var $components = array('HtmlAssist','Common','Lists');

/**
 * This controller does not use a model
 *
 * @var $uses
 */
     var $uses = array('Order','OrderType','CallResult','User');
/**
 * Displays a view
 *
 */
     function display() {

          if (!func_num_args()) {
                $this->redirect('/');
          }

          $path=func_get_args();

          if (!count($path)) {
                $this->redirect('/');
          }

          $count  =count($path);
          $page   =null;
          $subpage=null;
          $title  =null;

          if (!empty($path[0])) {
                $page = $path[0];
          }

          if (!empty($path[1])) {
                $subpage = $path[1];
          }

          if (!empty($path[$count - 1])) {
                $title = ucfirst($path[$count - 1]);
          }

          $this->set('page', $page);
          $this->set('subpage', $subpage);
          $this->set('title', $title);

          // add this snippet before the last line
          if (method_exists($this, $page)) {
            $this->$page();
          } 

          $this->render(join('/', $path));
     }

    function main() {
        $this->layout = 'plain';
        $this->set('common', $this->Common);
    }

    function topbar() {
        $this->layout = 'inline';
        $this->set('common', $this->Common);
    }

    function search()
    {
        $agent_id = $_SESSION['user']['id'];
        $this->layout = 'inline';
        $this->set('booking_sources', $this->HtmlAssist->table2array($this->Order->Source->execute('SELECT ace_rp_users.id, ace_rp_users.first_name, ace_rp_users.last_name FROM ace_rp_users, ace_rp_users_roles WHERE ace_rp_users.is_active = 1 AND ace_rp_users.id=ace_rp_users_roles.user_id AND (ace_rp_users_roles.role_id=3 OR ace_rp_users_roles.role_id=7)'), 'id', 'first_name'));
        $this->set('job_types', $this->HtmlAssist->table2array($this->OrderType->findAllByFlagactive(1), 'id', 'name'));
        $this->set('call_results', $this->HtmlAssist->table2array($this->CallResult->findAll(), 'id', 'name'));
        $this->set('common', $this->Common);
        if($_SESSION['user']['role_id'] == 6){
            $this->set("camp_list", $this->Lists->CampaingList());
        }
        else{
            $this->set("camp_list", $this->Lists->AgentCampaingList($agent_id));
        }
        
    }

    function browser()
    {
        $this->layout = 'inline';
        $this->set('common', $this->Common);
    }
    
    function desktop() {
        $this->layout = 'blank';
    }
    
    function bookOnline() {
        $this->layout = 'blank';
        $this->set('allCities',$this->Lists->ActiveCitiesWithId());
        
        $db =& ConnectionManager::getDataSource("default");
        
        $query = "
            SELECT * 
            FROM ace_iv_items
            WHERE id IN (18, 8, 14, 1031)";
        $result = $db->_execute($query);
        while($row = mysql_fetch_array($result)) {
            $items[$row['id']]['name'] = $row['name'];
            $items[$row['id']]['selling_price'] = $row['selling_price'];
        }
        $this->set('items',$items);
    }
    
    function successful() {
        $this->layout = 'blank';
    }
    
    function historyOnline() {
        $this->layout = 'blank';
    }
    
    function saveOnlineBooking() {
        $this->layout = 'blank';
        $db =& ConnectionManager::getDataSource("default");
        
        $city_id = $this->data['Customer']['city'];             
        
        $ip_address = empty($_SERVER['HTTP_CLIENT_IP'])?(empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['REMOTE_ADDR']:$_SERVER['HTTP_X_FORWARDED_FOR']):$_SERVER['HTTP_CLIENT_IP'];
        
        $query = "
            SELECT id 
            FROM ace_rp_cities
            WHERE internal_id = $city_id
        ";
        $result = $db->_execute($query);
        $row = mysql_fetch_array($result); 
        $this->data['Customer']['city'] = $row['id'];
        
        $this->User->save($this->data['Customer']);     
        $this->data['Order']['customer_id'] = $this->User->getLastInsertID();       
        
        //print_r($this->data['Item']);
        //exit;
        
        //preset online booking values  
        $query = "
            SELECT MAX(order_number) num 
            FROM ace_rp_orders";
        $result = $db->_execute($query);
        $row = mysql_fetch_array($result); 
        $this->data['Order']['order_number'] = 1 + $row['num'];
        
        $this->data['Order']['job_date'] = date("Y-m-d", strtotime($this->data['Order']['job_date']));
        $this->data['Order']['booking_date'] = $this->data['Order']['job_date'];
        $this->data['Order']['created_by'] = 96043; //ACE Web Site
        $this->data['Order']['created_date'] = date('Y-m-d H:i:s');
        $this->data['Order']['booking_telemarketer_id'] = 96043; 
        $this->data['Order']['booking_source_id'] = 96043;
        $this->data['Order']['order_status_id'] = 1;
        $this->data['Order']['order_type_id'] = 34;
        $this->data['Order']['customer_phone'] = $this->data['Customer']['phone'];
        $this->data['Order']['created_by_ip'] = $ip_address; //origin
        
        //set beginning and ending time
        if($this->data['Order']['job_time_beg_hour'] != '')
            $this->data['Order']['job_time_beg'] = $this->data['Order']['job_time_beg_hour'].':'.($this->data['Order']['job_time_beg_min'] ? $this->data['Order']['job_time_beg_min'] : '00');
        if($this->data['Order']['job_time_end_hour'] != '')
            $this->data['Order']['job_time_end'] = $this->data['Order']['job_time_end_hour'].':'.($this->data['Order']['job_time_end_min'] ? $this->data['Order']['job_time_end_min'] : '00');
        //set techs' time
        if($this->data['Order']['fact_job_beg_hour'] != '')
            $this->data['Order']['fact_job_beg'] = $this->data['Order']['fact_job_beg_hour'].':'.($this->data['Order']['fact_job_beg_min'] ? $this->data['Order']['fact_job_beg_min'] : '00');
        if($this->data['Order']['fact_job_end_hour'] != '')
            $this->data['Order']['fact_job_end'] = $this->data['Order']['fact_job_end_hour'].':'.($this->data['Order']['fact_job_end_min'] ? $this->data['Order']['fact_job_end_min'] : '00');
        
        $this->Order->save($this->data['Order']);
        
        $order_id = $this->Order->getLastInsertID();
        
        $total = 0;     

        // Save booked items
        
        
        if($this->data['Item']['Furnace'] == 1) {
            $query = "
                SELECT * 
                FROM ace_iv_items
                WHERE id = 18";
            $result = $db->_execute($query);
            $row = mysql_fetch_array($result);
        
            $temp[0]['class'] = '0';
            $temp[0]['item_id'] = $row['id'];
            $temp[0]['item_category_id'] = $row['iv_category_id'];
            $temp[0]['name'] = $row['name'];
            $temp[0]['quantity'] = '1';
            $temp[0]['price'] = $row['selling_price'];
            $temp[0]['discount'] = '0';
            $temp[0]['addition'] = '0'; 
            $temp[0]['order_id'] = $order_id;
                        
            $this->Order->BookingItem->create();
            $this->Order->BookingItem->save($temp[0]);
        }
        
        if($this->data['Item']['Airduct'] == 1) {
            $query = "
                SELECT * 
                FROM ace_iv_items
                WHERE id = 8";
            $result = $db->_execute($query);
            $row = mysql_fetch_array($result);
            
            $temp[1]['class'] = '0';
            $temp[1]['item_id'] = $row['id'];
            $temp[1]['item_category_id'] = $row['iv_category_id'];
            $temp[1]['name'] = $row['name'];
            $temp[1]['quantity'] = '1';
            $temp[1]['price'] = $row['selling_price'];
            $temp[1]['discount'] = '0';
            $temp[1]['addition'] = '0'; 
            $temp[1]['order_id'] = $order_id;
                        
            $this->Order->BookingItem->create();
            $this->Order->BookingItem->save($temp[1]);
        }
        
        if($this->data['Item']['Boiler'] == 1) {
            $query = "
                SELECT * 
                FROM ace_iv_items
                WHERE id = 14";
            $result = $db->_execute($query);
            $row = mysql_fetch_array($result);
            
            $temp[2]['class'] = '0';
            $temp[2]['item_id'] = $row['id'];
            $temp[2]['item_category_id'] = $row['iv_category_id'];
            $temp[2]['name'] = $row['name'];
            $temp[2]['quantity'] = '1';
            $temp[2]['price'] = $row['selling_price'];
            $temp[2]['discount'] = '0';
            $temp[2]['addition'] = '0';
            $temp[2]['order_id'] = $order_id;
                        
            $this->Order->BookingItem->create();
            $this->Order->BookingItem->save($temp[2]);
        }
        
        if($this->data['Item']['Other'] == 1) {
            $temp[3]['class'] = '0';
            $temp[3]['item_id'] = '1031';
            $temp[3]['item_category_id'] = '1';
            $temp[3]['name'] = 'Others - Online Inquiry';
            $temp[3]['quantity'] = '1';
            $temp[3]['price'] = '0';
            $temp[3]['discount'] = '0';
            $temp[3]['addition'] = '0'; 
            $temp[3]['order_id'] = $order_id;
                        
            $this->Order->BookingItem->create();
            $this->Order->BookingItem->save($temp[3]);
        }
        
        $query = "SELECT * 
            FROM ace_iv_items
            WHERE iv_category_id = 17
            AND model = '".$this->data['Item']['Coupon']."'
            AND active = 1
            LIMIT 1
        ";
        
        $result = $db->_execute($query);
        if($row = mysql_fetch_array($result)) {
        
            if($this->data['Item']['Coupon'] != '') {
                $temp[4]['class'] = '0';
                $temp[4]['item_id'] = $row['id'];
                $temp[4]['item_category_id'] = '17';
                $temp[4]['name'] = $row['name'];
                $temp[4]['quantity'] = '1';
                $temp[4]['price'] = intval($row['selling_price']);
                $temp[4]['discount'] = '0';
                $temp[4]['addition'] = '0'; 
                $temp[4]['order_id'] = $order_id;
                            
                $this->Order->BookingItem->create();
                $this->Order->BookingItem->save($temp[4]);
            }
        } else {
            if($this->data['Item']['Coupon'] != '') {
                $temp[4]['class'] = '0';
                $temp[4]['item_id'] = '7';
                $temp[4]['item_category_id'] = '1';
                $temp[4]['name'] = $this->data['Item']['Coupon']." - Invalid Coupon";
                $temp[4]['quantity'] = '1';
                $temp[4]['price'] = 0;
                $temp[4]['discount'] = '0';
                $temp[4]['addition'] = '0'; 
                $temp[4]['order_id'] = $order_id;
                            
                $this->Order->BookingItem->create();
                $this->Order->BookingItem->save($temp[4]);
            }   
        }
        
        
        /*for ($i = 0; $i < count($this->data['Order']['BookingItem']); $i++) {
            // Set ID of parent order
            $this->data['Order']['BookingItem'][$i]['order_id'] = $order_id;            
            if (0+$this->data['Order']['BookingItem'][$i]['quantity']!=0) {
                $this->Order->BookingItem->create();
                $this->Order->BookingItem->save($this->data['Order']['BookingItem'][$i]);
                $total += $this->data['Order']['BookingItem'][$i]['quantity']*
                          $this->data['Order']['BookingItem'][$i]['price'] -
                          $this->data['Order']['BookingItem'][$i]['discount'] +
                          $this->data['Order']['BookingItem'][$i]['addition'];
            }
        }*/     
        
        //send message to dispatcher
        //to beatriz
        /*$query = "INSERT INTO ace_rp_messages (txt, state, from_user, from_date, to_user, to_date, to_time, file_link)
             VALUES ('A new job has been booked', 0, 96043, current_date(),
                     57499, current_date(), '00:00', ".$order_id.")";
        $db->_execute($query);*/
        //to nick
        /*$query = "INSERT INTO ace_rp_messages (txt, state, from_user, from_date, to_user, to_date, to_time, file_link)
             VALUES ('A new job has been booked', 0, 96043, current_date(),
                     223190, current_date(), '00:00', ".$order_id.")";
        $db->_execute($query);*/
        //to hennesy    
        $query = "INSERT INTO ace_rp_messages (txt, state, from_user, from_date, to_user, to_date, to_time, file_link)
             VALUES ('A new job has been booked', 0, 96043, current_date(),
                     206767, current_date(), '00:00', ".$order_id.")";
        $db->_execute($query);  
        //to roy
        $query = "INSERT INTO ace_rp_messages (txt, state, from_user, from_date, to_user, to_date, to_time, file_link)
             VALUES ('A new job has been booked', 0, 96043, current_date(),
                     226792, current_date(), '00:00', ".$order_id.")";
        $db->_execute($query);
        
        $query = "
                    INSERT INTO ace_rp_notes(message, note_type_id, order_id, user_id, urgency_id, note_date)
                    VALUES ('From ACE WEBSITE: ".$this->data['Order']['job_notes_office']." .', 1, $order_id, 96043, 2, NOW())
                ";  
        $db->_execute($query);
    }
    
    function mobileMenu() {
        $this->layout = 'blank';
    }
    
    function verticalTimeSlots() {
        $this->layout = "blank";
        
        $days_ahead = 16;
        
        $city_id = $_POST['city_id'];
        $temp_date = $_POST['job_date'];
        $job_type = $_POST['job_type'];
        //$city_id = 283;
        //$temp_date = '13 Feb 2012';
        $job_date = date("Y-m-d", strtotime($temp_date));
        //$user_id = $this->Common->getLoggedUserID();
        if(!isset($city_id)) $city_id = 0;      
        
        $db =& ConnectionManager::getDataSource("default");     
        
        $query = "
            SELECT * 
            FROM ace_rp_cities      
        ";
        
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {            
            $cities[$row['internal_id']]['name']= $row['name'];
        }       
        
        $query = "
                SELECT ts.id, ts.name, ts.from, ts.to,
                (SELECT 
                    SUM(
                        IF(
                            (SELECT IF(COUNT(*) = 0,1,0)
                            FROM ace_rp_orders 
                            WHERE job_date = '$job_date'
                            AND order_status_id NOT IN(3,2)
                            AND (
                                (HOUR(ts.from) >= HOUR(job_time_beg) AND HOUR(ts.from) < HOUR(job_time_end))
                                OR
                                (HOUR(ts.to) > HOUR(job_time_beg) AND HOUR(ts.to) <= HOUR(job_time_end))
                            )
                            AND job_truck = il.id) 
                        AND 
                            (SELECT COUNT(route_id) 
                            FROM ace_rp_route_cities
                            WHERE route_date = '$job_date'
                            AND city_id = $city_id
                            AND route_id = il.id) 
                            +
                            (SELECT IF(COUNT(route_id) = 0, 1, 0)
                            FROM ace_rp_route_cities
                            WHERE route_date = '$job_date'
                            AND route_id = il.id)                       
                            ,
                        1,0)
                    ) 
                FROM ace_rp_inventory_locations il
                WHERE il.route_type = $job_type
                AND il.id NOT IN(19,20)) 'slots'
                FROM ace_rp_timeslots ts
                WHERE ts.id != 6
        ";
        
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            if($row['slots'] > 0) {         
            $furnaceslots[$row['id']]['name']= $row['name'];    
            $furnaceslots[$row['id']]['from']= $row['from'];
            $furnaceslots[$row['id']]['to']= $row['to'];
            $furnaceslots[$row['id']]['slots']= $row['slots'];          
            }
            //for($i=0;$i < $days_ahead; $i++) $furnaceslots[$row['id']][$i]= $row[$i];         
        }
        
        
        
        $this->set('cities', $cities);
        $this->set('city_id', $city_id);
        $this->set('job_type', $job_type);
        $this->set('furnaceslots', $furnaceslots);
            
        //$this->set('dates', $dates);
        //$this->set('days_ahead', $days_ahead);
    }
    
    function reserveTimeslot() {
        $this->layout = "blank";
                
        //$user_id = $_POST['user_id'];
        
        //if(!isset($user_id)) $user_id = $this->Common->getLoggedUserID();
        
        $job_date = date("Y-m-d", strtotime($_POST['job_date']));       
        $week_number = $_POST['week_number'];
        $job_time_beg = $_POST['job_time_beg'];
        $job_time_end = $_POST['job_time_end'];
        $job_time_name = $_POST['job_time_name'];
        $city_id = $_POST['city_id'];
        $route_type = $_POST['route_type'];
        
        $db =& ConnectionManager::getDataSource("default");     
        
        $query = "
            SELECT *
            FROM (
                SELECT il.id, il.name, il.tech1_id tech1, il.tech2_id tech2,        
                      IF(
                          (SELECT IF(COUNT(*) = 0,1,0)
                          FROM ace_rp_orders 
                          WHERE job_date = '$job_date'
                          AND order_status_id NOT IN(3,2)
                          AND (
                              (HOUR('$job_time_beg') >= HOUR(job_time_beg) AND HOUR('$job_time_beg') < HOUR(job_time_end))
                              OR
                              (HOUR('$job_time_end') > HOUR(job_time_beg) AND HOUR('$job_time_end') <= HOUR(job_time_end))
                          )
                          AND job_truck = il.id) 
                      AND 
                          (SELECT COUNT(route_id) 
                          FROM ace_rp_route_cities
                          WHERE route_date = '$job_date'
                          AND city_id = $city_id
                          AND route_id = il.id) 
                          +
                          (SELECT IF(COUNT(route_id) = 0, 1, 0)
                          FROM ace_rp_route_cities
                          WHERE route_date = '$job_date'
                          AND route_id = il.id)                  
                          ,
                      1,0) slots,
                      (SELECT name FROM ace_rp_cities WHERE internal_id = $city_id) city_name,
                      RIGHT(CONCAT('0', HOUR('$job_time_beg')), 2) job_time_beg,
                      RIGHT(CONCAT('0', HOUR('$job_time_end')), 2) job_time_end
                FROM ace_rp_inventory_locations il
                WHERE il.route_type = $route_type
                AND il.id NOT IN(19,20)) available
            WHERE slots > 0             
            LIMIT 1
        ";
        
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $slot['id'] = $row['id'];               
            $slot['name'] = $row['name'];
            $slot['slot'] = $row['slots'];
            $slot['job_time_beg'] = $row['job_time_beg'];
            $slot['job_time_end'] = $row['job_time_end'];
            $slot['city_name'] = $row['city_name'];
            $slot['tech1'] = $row['tech1'];
            $slot['tech2'] = $row['tech2'];
        }
        
        if(count($slot) > 0) {
            $this->set('availability', "Available");
            $this->set('availability_class', "available");
            $this->set('city_area', $slot['city_name']);
            $this->set('job_date', $job_date);
            $this->set('job_date_name', date("j M Y", strtotime($job_date)));
            $this->set('job_truck', $slot['id']);
            $this->set('job_truck_name', $slot['name']);
            $this->set('job_time_beg', $slot['job_time_beg']);
            $this->set('job_time_end', $slot['job_time_end']);
            $this->set('job_time_name', $job_time_name);
            $this->set('tech1', $slot['tech1']);
            $this->set('tech2', $slot['tech2']);
            $this->set('slot', $slot);
        } else {
            $this->set('availability', "Unavailable");
            $this->set('availability_class', "unavailable");
            $this->set('city_area', "");
            $this->set('job_date', $job_date);
            $this->set('job_date_name', date("j M Y", strtotime($job_date)));
            $this->set('job_truck', 0);
            $this->set('job_truck_name', "");
            $this->set('job_time_beg', "");
            $this->set('job_time_end', "");
            $this->set('job_time_name', $job_time_name);            
        }
        
        
    }
    
    function onlineItemsAjax($n) {
        $this->layout = "blank";
        
        $temp[0]['class'] = '0';
        $temp[0]['item_id'] = '18';
        $temp[0]['item_category_id'] = '1';
        $temp[0]['name'] = 'Furnace Service (Basic)';
        $temp[0]['quantity'] = '1';
        $temp[0]['price'] = '109';
        $temp[0]['discount'] = '20';
        $temp[0]['addition'] = '0';
        
        $temp[1]['class'] = '0';
        $temp[1]['item_id'] = '8';
        $temp[1]['item_category_id'] = '1';
        $temp[1]['name'] = 'Air Duct Cleaning(15 Vents) - SILVER Package #2';
        $temp[1]['quantity'] = '1';
        $temp[1]['price'] = '229';
        $temp[1]['discount'] = '0';
        $temp[1]['addition'] = '0';
        
        $temp[2]['class'] = '0';
        $temp[2]['item_id'] = '14';
        $temp[2]['item_category_id'] = '1';
        $temp[2]['name'] = 'Boiler Service';
        $temp[2]['quantity'] = '1';
        $temp[2]['price'] = '139';
        $temp[2]['discount'] = '0';
        $temp[2]['addition'] = '0';
        
        $temp[3]['class'] = '0';
        $temp[3]['item_id'] = '1031';
        $temp[3]['item_category_id'] = '1';
        $temp[3]['name'] = 'Others - Online Inquiry';
        $temp[3]['quantity'] = '1';
        $temp[3]['price'] = '0';
        $temp[3]['discount'] = '0';
        $temp[3]['addition'] = '0';
        
        $i = 0;
        foreach(explode("-",$n) as $temp_index) {
            $items[$i++] = $temp[$temp_index];
        }
        
        $this->set('items', $items);
    }
    
    function chatCustomerLogin() {
        $this->layout = "blank";
    }
    
    function chatAdminLogin() {
        $this->layout = "blank";
    }
    
    function chat() {
        $this->layout = "blank";
    }
    
    function checkCoupon() {
        $db =& ConnectionManager::getDataSource("default"); 
        $this->layout = "blank";
        $query = "SELECT * 
            FROM ace_iv_items
            WHERE iv_category_id = 17
            AND model = '".$_POST['code']."'
            AND active = 1
            LIMIT 1
        ";
        
        $result = $db->_execute($query);
        if($row = mysql_fetch_array($result)) echo "OK";
        exit;
    }
    
    function getIpAddress() {
        return (empty($_SERVER['HTTP_CLIENT_IP'])?(empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['REMOTE_ADDR']:$_SERVER['HTTP_X_FORWARDED_FOR']):$_SERVER['HTTP_CLIENT_IP']);
    }
    
    function publicFeedbacks() {
        $this->layout = "blank";
        $db =& ConnectionManager::getDataSource("default");     
        
        $query = "
            SELECT CONCAT(UPPER(SUBSTRING(u.last_name, 1, 1)), LOWER(SUBSTRING(u.last_name FROM 2))) AS last_name, 
            o.order_number, o.job_date, o.feedback_comment 
            FROM ace_rp_orders o
            LEFT JOIN ace_rp_customers u
            ON o.customer_id = u.id
            WHERE job_date BETWEEN '2012-01-01' AND NOW() 
            AND o.order_status_id = 5
            AND feedback_comment IS NOT NULL
            AND feedback_comment != ''
            AND feedback_comment NOT LIKE '%#HIDE%'
            ORDER BY o.job_date DESC
        ";
        $counter = 0;
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $results[$row['order_number']]['last_name'] = $row['last_name'];    
            $results[$row['order_number']]['job_date'] = $row['job_date'];  
            $results[$row['order_number']]['feedback_comment'] = $row['feedback_comment'];  
            $counter++; 
        }
        
        $this->set('results', $results);
        $this->set('counter', $counter);
    }
    
    function feedbackCounter() {
        $this->layout = "blank";
        $db =& ConnectionManager::getDataSource("default");     
        
        $query = "
            SELECT COUNT(o.order_number) cnt
            FROM ace_rp_orders o
            LEFT JOIN ace_rp_customers u
            ON o.customer_id = u.id
            WHERE job_date BETWEEN '2012-01-01' AND NOW() 
            AND o.order_status_id = 5
            AND feedback_comment IS NOT NULL
            AND feedback_comment != ''
            AND feedback_comment NOT LIKE '%#HIDE%'
        ";
        $counter = 0;
        $result = $db->_execute($query);
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            $counter = $row['cnt'];
        }
        
        $this->set('counter', str_split($counter));
    }
    // Loki: Show the booking info page.    
    function showReminderBookingPage()
    {   
        $oid = $_GET['oid'];
        $cusId = $_GET['cid'];
        $jobType = $_GET['otype']; 
        $reminderDate = $_GET['rdate'];
        $orderNum = $_GET['onum'];

        $this->set("orderId",$oid);
        $this->set("customerId",$cusId);
        $this->set("orderNum",$orderNum);
    }
    
    function thankYouPage()
    {
        
    }

    function showMembershipReminder()
    {       
        $cusId = $_GET['cid'];
        $userData = $this->Common->getUserDetails($cusId);
        $this->set('userData', $userData);

    }

    // Loki: Set message frame
    function message() 
    {   
        if(!empty($_POST))
        {
            $search_str     = isset($_POST['search_str']) ? trim($_POST['search_str']) : '';
            $db             =& ConnectionManager::getDataSource($this->User->useDbConfig);
            $currentDate    = date("Y-m-d");
            $limit          = 20;
            $where          = '';
            $groupBy        = '';
            $pageNo         = 0;
            if(isset($_POST["offset"]))
            {
                $off        = (int)$_POST["offset"] * $limit;
                $pageNo     = $_POST["offset"]+1;
            }       

            if(!empty($search_str)) 
            { 
                $limit = 1;
                $off   = 0;
                $where = "where sl.phone_number = '".$search_str."' ";
            } else {
                $where   = "where sl.id in (select max(id) from ace_rp_sms_log where sms_type = 2  group by phone_number order by id desc )";
            }

          /*  $textData = "SELECT concat(cu.first_name, ' ', cu.last_name) from_name, sl.message, sl.phone_number, sl.sms_type, sl.id from ace_rp_sms_log sl INNER JOIN ace_rp_customers cu ON ((sl.phone_number = cu.cell_phone) OR (sl.phone_number = cu.phone)) ".$where." order by sms_date desc limit ".$off.", ".$limit;*/
           
            $textData = "SELECT sl.message, sl.phone_number, sl.sms_type, sl.id from ace_rp_sms_log sl ".$where." order by sms_date desc limit ".$off.", ".$limit;
                $result = $db->_execute($textData);
                $res = '';
                while($row = mysql_fetch_array($result, MYSQL_ASSOC))
                {
                    $res .= '<div class=" textus-ConversationListItem-link textus-ConversationListItem-preview" onclick="showMessageHistory('.$row["phone_number"].')" page_num="'.$pageNo.'">
                        <input type="hidden" id="message_id" value="'.$row['id'].'">
                        <h4 class="textus-ConversationListItem-contactName">'.$row["phone_number"].'</h4>
                        <div class="textus-ConversationListItem-previewDetails"><span class="textus-ConversationListItem-previewMessage">'. $row["message"].'
                        </div>
                     </div>';
                    
                }
                echo $res;
                exit();      
        }
    }

    //Loki: Show page to user for service review
    function showUserReview()
    {
        $this->set("email", $_GET['email']);
        $this->set("phone_number", $_GET['phone_number']);
    }
}
?>

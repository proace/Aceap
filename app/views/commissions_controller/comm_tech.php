<?php

  $html = "<link type='text/css' href='ROOT_URL/app/webroot/css/overcast/jquery-ui-1.8.4.custom.css' rel='stylesheet' /> 
<table class='results' cellpadding='3' cellspacing='0' style='border-style: solid;border-width:1px;'>
    <tr style='height:25px;'>
        <th rowspan=2 width='60' style='background-color:#ffffff;border-left:0px'>Date</th>        
        <th rowspan=2 width='60' style='background-color:#ffffff;'>Job</th> 
             
        <th rowspan=2 width='100' style='background-color:#ffffff;'>Tech / Source</th>
        <th rowspan=2 style='background-color:#ffffff;'>Commission type</th>

        <th rowspan=2 width='60' style='background-color:#caffca;'>Booking</th>

        <th rowspan=2 width='60' style='background-color:#caffff;'>Extra Sales</th>
        <th colspan=2 width='60' style='background-color:#53ffff;'>Sales Comm</th>

   <!--     <th rowspan=2 width='60' style='background-color:#C5BE97;'>Time Payable</th>-->
        <th rowspan=2 width='60' style='background-color:#80ff80;'>Booking Paid</th>
        <th rowspan=2 width='60' style='background-color:#c0c0c0;'>Driving</th>

                <th colspan=2 width='60' style='background-color:#ff7d7d;'>Deductions</th>
        
        <th rowspan=2 colspan=2 width='100' style='background-color:#ffff80;'>Adjustment</th>
        <th rowspan=2 width='60' style='background-color:#ffff80;'>Total Comm</th>
        
        <th rowspan=2 width='16' style='background-color:#ffff80;'><?='<img src=''.ROOT_URL.'/app/webroot/img/icon-vsm-check.png'>'?></th>
        <th rowspan=2 colspan=2 width='100' style='background-color:#ffff80;'>Payments</th>
        <th rowspan=2 colspan=2 width='100' style='background-color:#ffff80;'>Purchase</th>
        <th rowspan=2 width='100' style='background-color:#ffff80;'>Notes</th>
        <th rowspan=2 width='16' style='background-color:#ffff80;'>Confirmed<?='<img src=''.ROOT_URL.'/app/webroot/img/icon-vsm-check.png'>'?></th>
        <th rowspan=2 width='16' style='background-color:#ffff80;'>Not Confirmed<?='<img src=''.ROOT_URL.'/app/webroot/img/cross-icon.jpeg' heignt='16 px' width='16 px' >'?></th>

    </tr>
    <tr style='height:25px;'>
        <th width='60' style='background-color:#53ffff;'>jobs</th>
        <th width='60' style='background-color:#53ffff;'>appli ances</th>
                <th width='50' style='background-color:#ff7d7d;'>Redo</th>
                <th width='50' style='background-color:#ff7d7d;'>Helper</th>
    </tr>'".
    $r = 1;
    $total_sum = 0;
    $approved_sum = 0;
    $techApprovedSum = 0;
    $techNotApprovedSum = 0;
    foreach ($orders as $obj):
        $class = 'cell'.(++$r%2);
        $comm_calculated_class='color:red;';
        if($obj['user_commissions_saved'] == 1) $comm_calculated_class='color:green;';
        $rows_persons = $obj['comm'];
        $local_class = '';
        $name_class = '';
        if ($obj['order_status_id']!='5') $name_class = 'notready';
        $disable_inputs = '';
        $disable_tech = array();
        $disable_tech[1] = false;
        $disable_tech[2] = false;
        $disable_tech[3] = false;
        $disable_tech[4] = false;
        $message_to = 0;
        
        if (($_SESSION['user']['id'] != 44851)&&($_SESSION['user']['id'] != 52249)&&($_SESSION['user']['id'] != 231307)
          &&($_SESSION['user']['id'] != 58613)&&($_SESSION['user']['id'] != 68476))
        {
            if ($obj['job_technician1_id']!=$_SESSION['user']['id']) $disable_tech[1] = true;
            if ($obj['job_technician2_id']!=$_SESSION['user']['id']) $disable_tech[2] = true;
            if ($obj['booking_source_id']!=$_SESSION['user']['id']) $disable_tech[3] = true;
            if ($obj['booking_source2_id']!=$_SESSION['user']['id']) $disable_tech[4] = true;
            $disable_inputs = 'disabled';
            $message_to = 11;
        }."
        <tr class= $class>
        <td rowspan=4 style='cursor:pointer;border-left:0px'>
        echo date('Y-m-d',strtotime($obj['job_date']))
        </td>        
        <td rowspan=2 align='center' title='Booking date: date('Y-m-d',strtotime($obj['job_date'])) Job date: date('Y-m-d',strtotime($obj['job_date']))>&nbsp;
            <br/><font color='($obj['order_status_id']==5) ? 'green' : 'red''><b>$obj['order_status']?></b></font>
        </td>';".
            if ($techid)
                if ($techid==$obj['job_technician1_id'])
                {
                    $local_class = 'active';
                    //Add total only for the done jobs
                    $total_sum = $total_sum + $rows_persons[1]['total_comm'];
                    if ($rows_persons[1]['verified'])
                        $approved_sum = $approved_sum + $rows_persons[1]['total_comm'];
                }
            else $local_class = 'inactive';
        ."
        <td class='$local_class onclick='javascript:SelectTech($obj['job_technician1_id'];);' style='cursor:pointer;'>
            &nbsp; $obj['tech1_name'];
        </td>
        <td>".
         
            if ($disable_inputs) echo '&nbsp;'.$comm_roles[$rows_persons[1]['commission_type']];
            else echo $html->selectTag('commtype['.$obj['id'].'][1]', $comm_roles, $rows_persons[1]['commission_type'], array('onchange'=>'ChangeTechMethod('.$obj['id'].',this,1)'));
            ."
        </td>
        
        <td rowspan=4 align='right' onclick='ShowDetails($obj['id'], 0)'>
            <a href='#'>$HtmlAssist->prPrice($obj['total'][0])</a>
        </td>

        <td rowspan=4 align='right' onclick='ShowDetails($obj['id'], 1)'>
                                <a href='#'>$HtmlAssist->prPrice($obj['total'][1])</a>
                </td>
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['sales_job_comm']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['sales_job_comm'])?>&nbsp;</td>
                <td class='$name_class $local_class?' align='right'>($rows_persons[1]['sales_appl_comm']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['sales_appl_comm'])&nbsp;</td>
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['booking_comm']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['booking_comm'])?>&nbsp;</td>
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['driving_comm']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['driving_comm']) &nbsp;</td>
        
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['redo_penalty']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['redo_penalty'])?>&nbsp;</td>
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['helper_ded']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['helper_ded'])&nbsp;</td>

                <td>".
                    if ($rows_persons[1]['verified']&&!$disable_tech[1]) {
                    if ($disable_inputs) echo '&nbsp;<b>'.$rows_persons[1]['adjustment'].'</b>';
                    else echo '<input type="text" style="width:40" value="'.$rows_persons[1]['adjustment'].'" onchange=\'Adjust('.$obj['id'].','.json_encode($rows_persons[1]).',this);\'/>';
                     }else{ 
                      &nbsp;
                     } ."
                </td>".
                
                    if(!empty($rows_persons[1]['tech_verified']) || $rows_persons[1]['tech_verified'] != '')
                    {
                        $techApprovedSum = $techApprovedSum + $rows_persons[1]['total_comm'];
                    } 
                    if(!empty($rows_persons[1]['tech_unverified']) || $rows_persons[1]['tech_unverified'] != '')
                    {
                        $techNotApprovedSum = $techNotApprovedSum + $rows_persons[1]['total_comm'];
                    } 
                ."
        <td if ($message_to) 'rowspan=4'; align='center' valign='top'>
                                 if ($obj['tech1_name']) {
                                <img src='ROOT_URL/app/webroot/img/icon-vsm-edit.png' onclick='ShowMessages($obj['id'],($message_to)?$message_to:$rows_persons[1]['id'])'/>
                                 } else 
                                &nbsp;
                                }
                </td>
                <td class='$name_class $local_class' align='right'>($rows_persons[1]['total_comm']==0||$disable_tech[1])?'':$HtmlAssist->prPrice($rows_persons[1]['total_comm'])&nbsp;</td>
                
                <td>
                  <input type='checkbox' $disable_inputs $rows_persons[1]['verified'] onclick='MarkVerified($obj['id'], json_encode($rows_persons[1]),this);'/>
                </td>
                <td rowspan='4' colspan='2' width='100'>".
                if(!empty($obj['orderNumber_image_path']))
                    {

                        $imgPath =  '/acesys/app/webroot/payment-images/'.$obj['orderNumber_image_path']; 
                        ."
                         <img class='openImg1' src='.$imgPath.' style='max-height: 100px; max-width: 100%; height: 50px; width: 50px;'>".           
                     } else {."
                        <img id='pre-image_photo3' class='pre-image_".$obj['id']."_photo3 invoice-openImg' src='#' alt='your image' />
                                <div class='acecare-td-adjust'>
                                    <label for='Fileinput' >Upload</label>
                                    <input type='file' name='techCommArr[$obj['id']][uploadFile]' id='Fileinput' class='disply_preview' data-ct='_".$obj['id']."_photo3'>
                                </div>  
                     }
                </td>";
                <td rowspan="4" colspan="2" width="100">
                    <?php 
                        if(!empty($obj['order_purchase_image1']))
                        {
                            $imgPath = ROOT_URL.'/upload_photos/'.$obj['order_purchase_image1'];
                            echo '<img class="openImg1" src="'.$imgPath.'" style="max-height: 100px; max-width: 100%; height: 50px; width: 50px;">' ;   
                        } else { ?>
                            <img id="pre-image_photo1" class="pre-image_<?php echo $obj['id'] ?>_photo1 invoice-openImg" src="#" alt="your image" />
                            <div class="acecare-td-adjust">
                                <label for="sortpicture1" >Upload</label>
                                <input type="file" name="techCommArr[<?php echo $obj['id'] ?>][sortpic1]" id="sortpicture1" class="disply_preview" data-ct="_<?php echo $obj['id'] ?>_photo1">
                            </div>
                    <?php } ?>
                <br>
                <?php
                    if(!empty($obj['order_purchase_image2']))
                    {
                        $imgPath =  ROOT_URL.'/upload_photos/'.$obj['order_purchase_image2'];
                        echo '<img class="openImg1" src="'.$imgPath.'" style="max-height: 100px; max-width: 100%; height: 50px; width: 50px;">' ;   
                    } else { ?>
                            <img id="pre-image_photo2" class="pre-image_<?php echo $obj['id'] ?>_photo2 invoice-openImg" src="#" alt="your image" />
                            <div class="acecare-td-adjust">
                                <label for="sortpicture2" >Upload</label>
                                <input type="file" name="techCommArr[<?php echo $obj['id'] ?>][sortpic2]" id="sortpicture2" class="disply_preview" data-ct="_<?php echo $obj['id'] ?>_photo2">
                            </div>
                    <?php } ?>
                </td>
                <td><input type="text" name="techCommArr[<?php echo $obj['id'] ?>][tech-notes]" placeholder="Notes"></td>
                <!-- <td width="100">
                  <input type="checkbox" name= "techCommArr[<?php echo $obj['id'] ?>]tech-confirm1"onclick='MarkVerified(<?=$obj['id']?>,<? echo json_encode($rows_persons[1]);?>,this);'/>
                </td>
 -->            <td width="100">
                  <input type="checkbox" <?=$rows_persons[1]['tech_verified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,1,this, 1, <?=($rows_persons[1]['total_comm']==0||$disable_tech[1])?'':$HtmlAssist->totalPrPrice($rows_persons[1]['total_comm']) ?>);' />
                </td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[1]['tech_unverified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-not-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,1,this, 0, <?php echo ($rows_persons[1]['total_comm']==0||$disable_tech[1])?'':$HtmlAssist->totalPrPrice($rows_persons[1]['total_comm']) ?>);'/>
                </td>
    </tr>
    <tr class="<?=$class?>">
                <?php
                                if ($techid)
                                                if ($techid==$obj['job_technician2_id'])
                                                {
                                                                $local_class = ' active';
                                                                $total_sum = $total_sum + $rows_persons[2]['total_comm'];
                                                                if ($rows_persons[2]['verified'])
                                                                                $approved_sum = $approved_sum + $rows_persons[2]['total_comm'];
                                                }
                                                else $local_class = 'inactive';
                ?> 
                <td class="<?=$local_class?>" onclick="javascript:SelectTech(<?php echo $obj['job_technician2_id']; ?>);" style="cursor:pointer;">
                  &nbsp;<?php echo $obj['tech2_name']; ?>
                </td>
                <td>
                  <?php 
                    if ($disable_inputs) echo '&nbsp;'.$comm_roles[$rows_persons[2]['commission_type']];
                    else echo $html->selectTag('commtype['.$obj['id'].'][2]', $comm_roles, $rows_persons[2]['commission_type'], array('onchange'=>'ChangeTechMethod('.$obj['id'].',this,2)'));
                    ?>
                </td>
                
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['sales_job_comm']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['sales_job_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['sales_appl_comm']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['sales_appl_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['booking_comm']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['booking_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['driving_comm']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['driving_comm']) ?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['redo_penalty']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['redo_penalty'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['helper_ded']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['helper_ded'])?>&nbsp;</td>

                <td>
                  <?php
                    if ($rows_persons[2]['verified']&&!$disable_tech[2]) {
                    if ($disable_inputs) echo '&nbsp;<b>'.$rows_persons[2]['adjustment'].'</b>';
                    else echo '<input type="text" style="width:40" value="'.$rows_persons[2]['adjustment'].'" onchange=\'Adjust('.$obj['id'].','.json_encode($rows_persons[2]).',this);\'/>';
                    }else{ ?>
                      &nbsp;
                    <?php } ?>
                </td>
                <?php
                    if(!empty($rows_persons[2]['tech_verified']) || $rows_persons[2]['tech_verified'] != '')
                    {
                        $techApprovedSum = $techApprovedSum + $rows_persons[2]['total_comm'];
                    } 
                    if(!empty($rows_persons[2]['tech_unverified']) || $rows_persons[2]['tech_unverified'] != '')
                    {
                        $techNotApprovedSum = $techNotApprovedSum + $rows_persons[2]['total_comm'];
                    } 
                ?>
        <? if (!$message_to) {?>
                <td align="center" valign="top">
                                <? if ($obj['tech2_name']) { ?>
                                <img src="<?=ROOT_URL;?>/app/webroot/img/icon-vsm-edit.png" onclick="ShowMessages(<?=$obj['id']?>,<?=$rows_persons[2]['id']?>)"/>
                                <? } else {?>
                                &nbsp;
                                <? }?>
                </td>
                <? }?>
        <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[2]['total_comm']==0||$disable_tech[2])?'':$HtmlAssist->prPrice($rows_persons[2]['total_comm']) ?>&nbsp;</td>
                
                <td>
                  <input type="checkbox" <?=$disable_inputs?> <?=$rows_persons[2]['verified']?> onclick='MarkVerified(<?=$obj['id']?>,<? echo json_encode($rows_persons[2]);?>,this);'/>
                </td>
                <td></td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[2]['tech_verified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,2,this, 1, <?=($rows_persons[2]['total_comm']==0||$disable_tech[2])?'':$HtmlAssist->totalPrPrice($rows_persons[2]['total_comm']) ?>);' />
                </td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[2]['tech_unverified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-not-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,2,this, 0, <?php echo ($rows_persons[2]['total_comm']==0||$disable_tech[2])?'':$HtmlAssist->totalPrPrice($rows_persons[2]['total_comm']) ?>);'/>
                </td>
                <!-- <td >
                  <input type="checkbox" name= "techCommArr[<?php echo $obj['id'] ?>][tech-confirm2]" />
                </td>
                <td width="100">
                  <input type="checkbox" name= "techCommArr[<?php echo $obj['id'] ?>][tech-not-confirm2]"/>
                </td> -->
    </tr>
    <tr class="<?= $class ?>">
        <td rowspan=2 align="center"><b><?php echo $obj['order_type']; ?></b></td>
                
                <?php
                                if ($techid)
                                                if ($techid==$obj['booking_source_id'])
                                                {
                                                                $local_class = ' active';
                                                                $total_sum = $total_sum + $rows_persons[3]['total_comm'];
                                                                if ($rows_persons[3]['verified'])
                                                                                $approved_sum = $approved_sum + $rows_persons[3]['total_comm'];
                                                }
                                                else  $local_class = 'inactive';
                ?> 
            <td class="<?=$local_class?>" onclick="javascript:SelectTech(<?php echo $obj['booking_source_id']; ?>);" style="cursor:pointer;">
                  &nbsp;<?php echo $obj['source_name']; ?>
                </td>
                <td>&nbsp;</td>
                
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['sales_job_comm']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['sales_job_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['sales_appl_comm']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['sales_appl_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['booking_comm']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['booking_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['driving_comm']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['driving_comm']) ?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['redo_penalty']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['redo_penalty'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['helper_ded']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['helper_ded'])?>&nbsp;</td>
                
                <td>
                <?php
                    if(!empty($rows_persons[3]['tech_verified']) || $rows_persons[2]['tech_verified'] != '')
                    {
                        $techApprovedSum = $techApprovedSum + $rows_persons[3]['total_comm'];
                    } 
                    if(!empty($rows_persons[3]['tech_unverified']) || $rows_persons[3]['tech_unverified'] != '')
                    {
                        $techNotApprovedSum = $techNotApprovedSum + $rows_persons[3]['total_comm'];
                    } 
                ?>
                  <?php
                    if ($rows_persons[3]['verified']&&!$disable_tech[3]) {
                    if ($disable_inputs) echo '&nbsp;<b>'.$rows_persons[3]['adjustment'].'</b>';
                    else echo '<input type="text" style="width:40" value="'.$rows_persons[3]['adjustment'].'" onchange=\'Adjust('.$obj['id'].','.json_encode($rows_persons[3]).',this);\'/>';
                    }else{ ?>
                      &nbsp;
                    <?php } ?>
                </td>
        <? if (!$message_to) {?>
                <td align="center" valign="top">
                                <? if ($obj['source_name']) { ?>
                                <img src="<?=ROOT_URL;?>/app/webroot/img/icon-vsm-edit.png" onclick="ShowMessages(<?=$obj['id']?>,<?=$rows_persons[3]['id']?>)"/>
                                <? } else {?>
                                &nbsp;
                                <? }?>
                </td>
                <? }?>
        <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[3]['total_comm']==0||$disable_tech[3])?'':$HtmlAssist->prPrice($rows_persons[3]['total_comm']) ?>&nbsp;</td>
                
                <td>
                  <input type="checkbox" <?=$disable_inputs?> <?=$rows_persons[3]['verified']?> onclick='MarkVerified(<?=$obj['id']?>,<? echo json_encode($rows_persons[3]);?>,this);'/>
                </td>
                <td></td>
            <td width="100">
                  <input type="checkbox" <?=$rows_persons[3]['tech_verified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,3,this, 1, <?=($rows_persons[3]['total_comm']==0||$disable_tech[3])?'':$HtmlAssist->totalPrPrice($rows_persons[3]['total_comm']) ?>);' />
                </td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[3]['tech_unverified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-not-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,3,this, 0, <?php echo ($rows_persons[3]['total_comm']==0||$disable_tech[1])?'':$HtmlAssist->totalPrPrice($rows_persons[3]['total_comm']) ?>);'/>
                </td>
    </tr>
    <tr class="<?= $class ?>">
                <?php
                                if ($techid)
                                                if ($techid==$obj['booking_source2_id'])
                                                {
                                                                $local_class = ' active';
                                                                $total_sum = $total_sum + $rows_persons[4]['total_comm'];
                                                                if ($rows_persons[4]['verified'])
                                                                                $approved_sum = $approved_sum + $rows_persons[4]['total_comm'];
                                                }
                                                else $local_class = 'inactive';
                ?> 
            <td class="<?=$local_class?>" onclick="javascript:SelectTech(<?php echo $obj['booking_source2_id']; ?>);" style="cursor:pointer;">
                  &nbsp;<?php echo $obj['source2_name']; ?>
                </td>
                <td>&nbsp;</td>
                
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['sales_job_comm']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['sales_job_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['sales_appl_comm']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['sales_appl_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['booking_comm']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['booking_comm'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['driving_comm']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['driving_comm']) ?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['redo_penalty']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['redo_penalty'])?>&nbsp;</td>
                <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['helper_ded']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['helper_ded'])?>&nbsp;</td>
                
                <td>
                <?php
                    if(!empty($rows_persons[4]['tech_verified']) || $rows_persons[4]['tech_verified'] != '')
                    {
                        $techApprovedSum = $techApprovedSum + $rows_persons[4]['total_comm'];
                    } 
                    if(!empty($rows_persons[4]['tech_unverified']) || $rows_persons[4]['tech_unverified'] != '')
                    {
                        $techNotApprovedSum = $techNotApprovedSum + $rows_persons[4]['total_comm'];
                    } 
                ?>
                  <?php
                    if ($rows_persons[4]['verified']&&!$disable_tech[4]) {
                    if ($disable_inputs) echo '&nbsp;<b>'.$rows_persons[4]['adjustment'].'</b>';
                    else echo '<input type="text" style="width:40" value="'.$rows_persons[4]['adjustment'].'" onchange=\'Adjust('.$obj['id'].','.json_encode($rows_persons[4]).',this);\'/>';
                    }else{ ?>
                      &nbsp;
                    <?php } ?>
                </td>
        <? if (!$message_to) {?>
                <td align="center" valign="top">
                                <? if ($obj['source2_name']) { ?>
                                <img src="<?=ROOT_URL;?>/app/webroot/img/icon-vsm-edit.png" onclick="ShowMessages(<?=$obj['id']?>,<?=$rows_persons[4]['id']?>)"/>
                                <? } else {?>
                                &nbsp;
                                <? }?>
                </td>
                <? }?>
        <td class="<?=$name_class?> <?=$local_class?>" align="right"><?=($rows_persons[4]['total_comm']==0||$disable_tech[4])?'':$HtmlAssist->prPrice($rows_persons[4]['total_comm']) ?>&nbsp;</td>
                
                <td>
                  <input type="checkbox" <?=$disable_inputs?> <?=$rows_persons[4]['verified']?> onclick='MarkVerified(<?=$obj['id']?>,<? echo json_encode($rows_persons[4]);?>,this);'/>
                </td>
                <td></td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[4]['tech_verified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,4,this, 1, <?=($rows_persons[1]['total_comm']==0||$disable_tech[1])?'':$HtmlAssist->totalPrPrice($rows_persons[4]['total_comm']) ?>);' />
                </td>
                <td width="100">
                  <input type="checkbox" <?=$rows_persons[4]['tech_unverified']?> name= "techCommArr[<?php echo $obj['id'] ?>][tech-not-confirm1]" onclick='techMarkVerified(<?=$obj['id']?>,4,this, 0, <?php echo ($rows_persons[4]['total_comm']==0||$disable_tech[4])?'':$HtmlAssist->totalPrPrice($rows_persons[4]['total_comm']) ?>);'/>
                </td>       
    </tr>
<?php endforeach; ?>
<?php if ($techid) {?>
    <tr>
                <td colspan=10 style="border-top:1px solid #9f9f9f;">&nbsp;</td>
                <td align="right" style="border-top:1px solid #9f9f9f;"><b>Total:</b></td>
                <td align="right" style="border-top:1px solid #9f9f9f;"><b><?php echo $HtmlAssist->prPrice($total_sum);?></b></td>
                <td colspan=1 align="right" style="border-top:1px solid #9f9f9f;"><b>Approved by office:</b></td>
                <td align="right" style="border-top:1px solid #9f9f9f;"><b><?php echo $HtmlAssist->prPrice($approved_sum);?></b></td>
                </td>
                <!-- <td></td> -->
                
                <td colspan=5 style="border-top:1px solid #9f9f9f;">&nbsp;</td>
                <td  align="right" style="border-top:1px solid #9f9f9f;"><b> Tech confirmed by office:</b></td>
                <td align="right" style="border-top:1px solid #9f9f9f;"><b><?php echo $HtmlAssist->prPrice($techApprovedSum);?></b></td>
                </td>
                <td  align="right" style="border-top:1px solid #9f9f9f;"><b>Not confirmed by office:</b></td>
                <td align="right" style="border-top:1px solid #9f9f9f;"><b><?php echo $HtmlAssist->prPrice($techNotApprovedSum);?></b></td>
                </td>
    </tr>
<?php }?>

</table>
<input type="hidden" id="confirm-total" name="confirm-total" value="<?= $techApprovedSum?>">
<input type="hidden" id="not-confirm-total" name="not-confirm-total" value="<?=$techNotApprovedSum?>">
<!-- <input class="submitBtn" type="submit" name="submitForm">
<table width="100%"> -->
<!-- <tr>
    <td width="100%" align="center">
        <?php echo $html->link('<<','/commissions/calculateCommissions?page='.$previousPage.'&ffromdate='.$fdate.'&ftodate='.$tdate.'&ftechid='.$techid.'&fjoboption='.$joboption) ?>
        &nbsp;
        <?php echo $html->link('>>','/commissions/calculateCommissions?page='.$nextPage.'&ffromdate='.$fdate.'&ftodate='.$tdate.'&ftechid='.$techid.'&fjoboption='.$joboption) ?>
    </td>
</tr> -->
</table>
?>
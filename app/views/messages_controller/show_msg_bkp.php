<script>

 function markAsRead(id){
    $.get("<?=BASE_URL;?>/messages/markAsRead",
        // {message_id:id}, function(data){document.forms['viewform'].submit();});
        {message_id:id}, function(data){location.reload();});
 }
function respond(to_user_id)
{
  var answer = window.open("<?=BASE_URL?>/messages/EditMessage?order_id=<?=$_GET['job_id']?>&to_user_id="+to_user_id,'', 
  "width=450, height=350, left=300, top=200");
}
function forward(text)
{
  var answer = window.open("<?=BASE_URL?>/messages/EditMessage?order_id=<?=$_GET['job_id']?>&text="+text,'', 
  "width=450, height=350, left=300, top=200");
}
function DeleteMsg(id){
    $.get("<?=BASE_URL;?>/messages/DeleteMessage",
        {message_id:id}, function(data){document.forms['viewform'].submit();});
}
function eraseAll(){
    $.get("<?=BASE_URL;?>/messages/DeleteAll", function(data){document.forms['viewform'].submit();});
}
function createMessage()
{
  var answer = window.open("<?=BASE_URL?>/messages/EditMessage?order_id=<?=$_GET['job_id']?>&to_user_id=<?=$_GET['to_user_id']?>",'', 
  "width=450, height=350, left=300, top=200");
    document.forms['viewform'].submit();
}
</script>
<style type="text/css">
 .incoming {
        border:1px solid #4a6901;
        width:100%;
        background-color:#eafeba;
 }
 .sent {
        border:1px solid #6b5101;
        width:100%;
        background-color:#feedba;
 }
</style>
<form method="GET" action="<?= $PHP_SELF?>" name="viewform">
    <input type="hidden" name="action" value="view" >
    <input type="hidden" name="order" value="<?=$_GET['order']?>">
    <input type="hidden" name="sort" value="<?=$_GET['sort']?>">
    <input type="hidden" name="currentPage" value="<?=$_GET['currentPage']?>">
    <input type="hidden" name="job_id" value="<?=$_GET['job_id']?>">
    <input type="hidden" name="to_user_id" value="<?=$_GET['to_user_id']?>">
</form>
</script>
<table>
    <tr>
        <td onclick="createMessage()">
            <img src="<?=ROOT_URL?>/app/webroot/img/icon-sm-plus.png">
        </td>
        <td style="text-align:left;" onclick="createMessage()">
            <b>Send message</b>
        </td>
        <td onclick="eraseAll()">
            <img src="<?=ROOT_URL?>/app/webroot/img/icon-vsm-delete.png">
        </td>
        <td style="text-align:left;" onclick="eraseAll()">
            <b>Erase All</b>
        </td>
    </tr>
</table>
<table class="results" cellpadding="3" cellspacing="0">
        <?php
            $r=0;
            foreach ($items as $obj) {
                $r++;
                if ($common->getLoggedUserID()==$obj['from_user'])
                {
                    $class = 'incoming';
                    $corr_title = 'To: ';
                    $corr_name = $obj['to_name'];
                    $corr = $obj['to_user'];
                }
                else
                {
                    $class = 'sent';
                    $corr_title = 'From: ';
                    $corr_name = $obj['from_name'];
                    $corr = $obj['from_user'];
                }
                $open_file = '';
                if ($obj['customer_link'])
                    $open_file = "<a target='main_view' href='".BASE_URL."/orders/editBooking?customer_id=".$obj['customer_link']."'>Open file</a>";
                elseif ($obj['file_link'])
                    $open_file = "<a target='main_view' href='".BASE_URL."/orders/editBooking?order_id=".$obj['file_link']."'>Open file</a>";
        ?>
    <tr id="r<?=$r?>">
            <table class="<?=$class?>">
                <tr>
                    <td><?=$obj['from_date']?></td>
                    <td rowspan=4 style="border-left:1px solid #CCCCCC;text-align:left;width:400px">
                        <div style="width:400px"><?=$obj['txt']?></div>
                    </td>
                    <td rowspan="3" style="text-align:center;vertical-align:middle;">Read:</td><td rowspan=4 style="border-left:1px solid #CCCCCC;width:16px">
                        <input type="checkbox" <?php if($obj['state']){echo 'checked';}?> name="MarkRead" onclick="markAsRead(<?=$obj['id']?>)">
                    </td>
                    <td rowspan=4 style="border-left:1px solid #CCCCCC;width:16px" onclick="DeleteMsg(<?=$obj['id']?>)">
                        <img src="<?=ROOT_URL?>/app/webroot/img/icon-vsm-delete.png" />
                    </td>
                </tr>
                <tr>
                    <td><?=$corr_title?><b><?=$corr_name?></b><a href='#' onclick='respond("<?=$corr?>")'>(Reply)</a></td>
                </tr>
                <tr>
                    <td><a href='#' onclick='forward("<?=$obj['txt']?>")'>(Forward)</a></td>
                </tr>
                <tr>
                    <td><?=$open_file?></td>
                </tr>
            </table>
    </tr>
    <? } ?>
</table>

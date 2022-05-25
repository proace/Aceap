<!-- This tag creates our form tag -->
<script language="JavaScript" src="<?=ROOT_URL;?>/app/webroot/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript">
    
    tinyMCE.init({
            mode: 'textareas',
            theme : "modern",
            browser_spellcheck: true,
            plugins : "code, responsivefilemanager, table, link,  anchor, image, media",
        // plugins : "code,safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,imagemanager,filemanager, nanospell, link unlink, anchor, image, media, textcolor",
         toolbar: "forecolor backcolor link image code bold italic formatselect numlist bullist indent outdent underline alignleft aligncenter alignright table ",
        external_plugins: {"nanospell": "plugins/nanospell/plugin.js"},
            nanospell_server: "php", // choose "php" "asp" "asp.net" or "java"
        // Theme options
        theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect,|nanospell",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
        theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,spellchecker,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,|,insertfile,insertimage",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        theme_advanced_resizing : true,
        
        // Example content CSS (should be your site CSS)
        content_css : "css/example.css",
        
        convert_urls: true,
        width: 800,
        height: 500,
        template_external_list_url : "js/template_list.js",
        external_link_list_url : "js/link_list.js",
        external_image_list_url : "js/image_list.js",
        media_external_list_url : "js/media_list.js",
        gecko_spellcheck : true, browser_spellcheck : true
        });

    
</script> 
<?php echo $html->formTag('/settings/edit/' . $html->tagValue('Setting/title')); ?>
<?php echo $html->hidden ('Setting/id', array("value",$html->tagValue('Setting/id'))); ?>
<?php echo $html->hidden ('Setting/title', array("value",$html->tagValue('Setting/title'))); ?>
<? echo $html->hiddenTag('rurl', $this->params['url']['rurl']); ?>

<table width="600" align="left" border="0" cellspacing="1" cellpadding="0"> 
    <tr>
        <td valign=top><b>Subject:</b></td><td><?php echo $html->input('Setting/subject', array("style" => "width: 100%;"))?></td>
    </tr>
    <tr>
        <td valign=top><label><b>Message:</b></label></td><td><?php echo $html->textarea('Setting/valuetxt', array("style" => "width: 600px; height: 400px;")); ?>
          <?php echo $html->tagErrorMsg('Setting/valuetxt', 'Please enter a value for this setting.') ?>
        <br/>
        </td>
    </tr>
    <tr><td><input type="submit" name="submit" value="Save" class="buttons"></td></tr>
</table>
</form>

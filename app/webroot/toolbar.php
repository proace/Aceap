<link rel="stylesheet" type="text/css" href="css/style.css" title="Default" />
<div class="toolbar">
<? foreach ($tb as $tbi) { ?>
	<div class="icon">
	<a href="<?=$tbi['url'];?>">
		<img src="img/<?=$tbi['img'];?>"><br/>
		<?=$tbi['title'];?>
	</a>
	</div>
<? } ?>
</div>
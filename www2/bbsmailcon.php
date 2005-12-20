<?php
	require("www2-funcs.php");
	login_init();
	assert_login();
	
	if (isset($_GET["num"]))
		$num = $_GET["num"];
	else {
		html_error_quit("����Ĳ���");
	}

	if (isset($_GET["dir"]))
		$dirname = $_GET["dir"];
	else
		$dirname = ".DIR";

	if (isset($_GET["title"]) )
		$title=$_GET["title"];
	else
		$title="�ռ���";
		
	$title_encode = rawurlencode($title);

	if (strstr($dirname, "..") || strstr($dirname, "/")){
		html_error_quit("����Ĳ���");
	}
	$dir = "mail/".strtoupper($currentuser["userid"]{0})."/".$currentuser["userid"]."/".$dirname ;

	$total = filesize( $dir ) / 140 ;  /* TODO: BUG */
	if( $total <= 0 ){
		html_error_quit("�ż㲻����");
	}

	$articles = array ();
	if( bbs_get_records_from_num($dir, $num, $articles) ) {
		$file = $articles[0]["FILENAME"];
	}else{
		html_error_quit("����Ĳ���");
	}

	$filename = "mail/".strtoupper($currentuser["userid"]{0})."/".$currentuser["userid"]."/".$file ;
	if(! file_exists($filename)){
		html_error_quit("�ż�������...");
	}

	@$attachpos=$_GET["ap"];//pointer to the size after ATTACHMENT PAD
	if ($attachpos!=0) {
		require_once("attachment.php");
		output_attachment($filename, $attachpos);
		exit;
	}
	mailbox_header("�ż��Ķ�");
?>
<div class="large">
<div class="article">
<?php
				bbs_print_article($filename,1,$_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']);
?>
</div></div>
<div class="oper">
[<a onclick='return confirm("�����Ҫɾ���������")' href="bbsmailact.php?act=del&dir=<?php echo $dirname;?>&file=<?php echo $file;?>&title=<?php echo $title_encode;?>">ɾ��</a>]
[<a href="javascript:history.go(-1)">������һҳ</a>]
<?php
				if($num > 0){
?>
[<a href="bbsmailcon.php?dir=<?php echo $dirname;?>&num=<?php echo $num-1;?>&title=<?php echo $title_encode;?>">��һƪ</a>]
<?php
				}
?>
[<a href="bbsmailbox.php?path=<?php echo $dirname;?>&title=<?php echo $title_encode;?>&start=<?php echo $num-10;?>">�����ż��б�</a>]
<?php
				if($num < $total-1){
?>
[<a href="bbsmailcon.php?dir=<?php echo $dirname;?>&num=<?php echo $num+1;?>&title=<?php echo $title_encode;?>">��һƪ</a>]
<?php
				}
?>
[<a href="bbspstmail.php?dir=<?php echo $dirname ?>&userid=<?php echo $articles[0]["OWNER"]; ?>&num=<?php echo $num; ?>&file=<?php echo $articles[0]["FILENAME"]; ?>&title=<?php if(strncmp($articles[0]["TITLE"],"Re:",3)) echo "Re: "; ?><?php echo urlencode($articles[0]["TITLE"]); ?>">����</a>]
</div>
<form action="bbsmailfwd.php" method="post" class="medium">
<input type="hidden" name="dir" value="<?php echo $dirname;?>"/>
<input type="hidden" name="id" value="<?php echo $num;?>"/>
	<fieldset><legend>ת���ż�</legend>
		<div class="inputs">
		<label>�Է���id��email:</label><input type="text" name="target" size="20" maxlength="69" value="<?php echo $currentuser["userid"];?>"/><br/>
		<input type="checkbox" name="big5" id="big5" value="1"/><label for="big5" class="clickable">BIG5</label>
		<input type="checkbox" name="noansi" id="noansi" value="1"/><label for="noansi" class="clickable">����ANSI</label>
		<input type="submit" value="ת��"/>
	</div></fieldset>
</form>
<?php
	bbs_setmailreaded($dir,$num);
	page_footer();
?>
<?php
require("www2-funcs.php");
require("www2-board.php");
login_init();
define('ARTCNT', 20);

$bbsman_modes = array(
	"DEL"   => 1,
	"MARK"  => 2,
	"DIGEST"=> 3,
	"NOREPLY" => 4,
	"ZHIDING" => 5,
	"UNDEL" => 6
);

function do_manage_function($board) {
	global $bbsman_modes;
    $mode = intval($_POST['act']);
    if ($mode > 0 && $mode <= sizeof($bbsman_modes)) {
        for ($i = 0 ; $i < ARTCNT ; $i ++) {
            if (isset($_POST['art'.$i])) {
                if (intval($_POST['art'.$i])) {
                    $id = intval($_POST['art'.$i]);
                    $zhiding = 0;
                }
            }
            elseif (isset($_POST['ding'.$i])) {
                if (intval($_POST['ding'.$i])) {
                    $id = intval($_POST['ding'.$i]);
                    $zhiding = 1;
                }
            }
            else
                continue;
                      
            if ($zhiding && ($mode != 6)) {
                 if ($mode !=  $bbsman_modes['DEL'] && $mode != $bbsman_modes['ZHIDING'])
                    continue;   
                 $mode = $bbsman_modes['DEL'];
            }
            
            if (!$id)   continue;
            
            $ret = bbs_bmmanage($board,$id,$mode,$zhiding);
            switch ($ret) {
                case -1:
                case -2:
                case -3:
                case -9:
                    html_error_quit('ϵͳ����'.$ret);
                    break;
                case -4:
                    html_error_quit('����ID����');
                    break;
                case -5:
                    html_error_quit('û������ļ������ܸ��ļ��Ѿ����ָ������档');
                    break;
                default:    
            }
        }
    }
}


function bbs_board_foot($brdarr, $managemode, $ftype, $isnormalboard) {
	global $currentuser, $dir_modes;
	$brd_encode = urlencode($brdarr["NAME"]);
	$usernum = $currentuser["index"];
	$brdnum  = $brdarr["NUM"];
?>
<div class="oper smaller">
<?php
	if ($ftype != $dir_modes["ORIGIN"]) {
?>
[<a href="bbsdoc.php?board=<?php echo $brd_encode; ?>&ftype=<?php echo $dir_modes["ORIGIN"]; ?>">ͬ����ģʽ</a>]
<?php		
    } else if ($ftype) {
?>
[<a href="bbsdoc.php?board=<?php echo $brd_encode; ?>">��ͨģʽ</a>]
<?php
	}
?>
[<a href="bbsnot.php?board=<?php echo $brd_encode; ?>">���滭��</a>]
[<a href="bbsbfind.php?board=<?php echo $brd_encode; ?>" onclick="return showFindBox('<?php echo $brd_encode; ?>')">���ڲ�ѯ</a>]
[<a href="bbsshowvote.php?board=<?php echo $brd_encode; ?>">����ͶƱ</a>]
[<a href="bbsshowtmpl.php?board=<?php echo $brd_encode; ?>">����ģ��</a>]
[<?php bbs_add_super_fav ($brdarr['DESC'], 'bbsdoc.php?board='.$brdarr['NAME']); ?>]
<?php
	$rsslink = $isnormalboard ? bbs_rss_link($brd_encode, $ftype) : "";
	if ($rsslink) {
?>
<a href='<?php echo $rsslink; ?>' title='RSS'><img src='images/xml.gif'/></a>
<?php
	}
	$relatefile = $_SERVER["DOCUMENT_ROOT"]."/brelated/".$brdarr["NAME"].".html";
	if( file_exists( $relatefile ) )
	{
?>
<br/>���������˳�ȥ���������棺
<?php
		include($relatefile);
	}
	if (bbs_is_bm($brdnum, $usernum)) {
?>
<br/>�������ӣ�
[<a href="bbsdeny.php?board=<?php echo $brd_encode; ?>">�������</a>] 
[<a href="bbsmnote.php?board=<?php echo $brd_encode; ?>">���滭��</a>]
[<a href="bbsmvote.php?board=<?php echo $brd_encode;?>">����ͶƱ</a>]
<?php
		if (!$managemode) {
?>
[<a href="bbsdoc.php?manage=1&board=<?php echo $brd_encode; ?>">����ģʽ</a>]
<?php
		} else {
?>
[<a href="bbsdoc.php?&board=<?php echo $brd_encode; ?>">��ͨģʽ</a>]
[<a href="bbsclear.php?board=<?php echo $brd_encode; ?>">���δ��</a>]
<?php
		}
	}
?>
</div>
<?php
}


function display_articles($brdarr,$articles,$start,$ftype,$managemode,$page,$total,$showHot)
{
	global $brdnum, $usernum, $dir_modes;
	$ann_path = bbs_getannpath($brdarr["NAME"]);
	if ($ann_path != FALSE)	{
		if (!strncmp($ann_path,"0Announce/",10))
			$ann_path = substr($ann_path,9);
	}
	if(bbs_is_bm($brdnum, $usernum))
	{
		$isbm = 1;
	}
	else 
	{
		$isbm = 0;
	}
	if($ftype == $dir_modes["DELETED"])
	{
		$mancode = "2";
	}
	else
	{
		$mancode = $managemode?"1":"0";
	}
?>
<script>
var c = new docWriter('<?php echo addslashes($brdarr["NAME"]); ?>',<?php echo $start;
?>,<?php echo $mancode; ?>,<?php echo $ftype; ?>,<?php echo $page; ?>,<?php echo $total;
?>,'<?php echo addslashes($ann_path); ?>',<?php echo $showHot?"1":"0"; ?>,<?php echo $isbm; ?>);
<?php
	foreach ($articles as $article)
	{
?>
c.o(<?php echo $article["ID"]; ?>,<?php echo $article["GROUPID"]; ?>,'<?php echo $article["OWNER"]; ?>',<?php
		$flags = $article["FLAGS"];
		if (!strncmp($flags,"D",1)||!strncmp($flags,"d",1)) {
			echo "false";
		} else {
			$str = "'";
			if ($flags[1] == 'y') {
				if ($flags[0] == ' ')
					$str .= " ";
				else
					$str .= $flags[0];
			} elseif ($flags[0] == 'N' || $flags[0] == '*'){
				if ($flags[0] == ' ') 
					$str .= " "; 
				else if ($managemode)
					$str .= $flags[0];
				else
					$str .= ""; //$flags[0];  //��Ҫδ����� windinsn
			} else{
				if ($flags[0] == ' ')
					$str .=  " "; 
				else
					$str .= $flags[0];
			}
			$str .= $flags[3] . "'";
			echo $str;
		}
?>,<?php echo $article["POSTTIME"]; ?>,'<?php echo addslashes($article["TITLE"]); ?> ',<?php echo $article["EFFSIZE"]; ?>);
<?php
	}
?>
c.t();
</script>
<?php
}

if (isset($_GET["board"]))
	$board = $_GET["board"];
else{
	html_error_quit("�����������");
}
// ����û��ܷ��Ķ��ð�
$brdarr = array();
$brdnum = bbs_getboard($board, $brdarr);
if ($brdnum == 0){
	html_error_quit("�����������");
}
$board = $brdarr["NAME"];
$usernum = $currentuser["index"];
if (bbs_checkreadperm($usernum, $brdnum) == 0){
	html_error_quit("�����������");
}
if ($brdarr["FLAG"]&BBS_BOARD_GROUP) {
	for ($i=0;$i<sizeof($section_nums);$i++) {
		if (!strcmp($section_nums[$i],$brdarr["SECNUM"])) {
			 Header("Location: bbsboa.php?group=" . $i . "&group2=" . $brdnum);
			 return;
		}
	}
	html_error_quit("�����������");
}

$isbm=bbs_is_bm($brdnum, $usernum);

$managemode = isset($_GET["manage"]);
if ($managemode) {
	if (!$isbm)
		html_error_quit("�㲻�ǰ���");
}

$brd_encode = urlencode($brdarr["NAME"]);

if($managemode)
{
	if(isset($_GET["ftype"]))
	{
		$ftype = intval($_GET["ftype"]);
		if(($ftype != $dir_modes["NORMAL"]) && ($ftype != $dir_modes["DELETED"]))
		{
			$ftype = $dir_modes["NORMAL"];
		}
	}
	else 
	{
		$ftype = $dir_modes["NORMAL"];
	}
}
else if(isset($_GET["ftype"])) {
	$ftype = intval($_GET["ftype"]);
	if (!bbs_is_permit_mode($ftype, 0)) {
		html_error_quit("�����ģʽ");
	}
}
else
{
	$ftype = $dir_modes["NORMAL"];
}

if(($ftype == $dir_modes["DELETED"]) && !$managemode)  //�ǹ���ģʽ���ÿ�����վ��ͬʱҲ��֤���ᱻcache
{
	html_error_quit("�㲻�ܿ��������Ŷ��");
}

$isnormalboard = bbs_normalboard($board);

bbs_set_onboard($brdnum,1);
if ($ftype == $dir_modes["ORIGIN"]) {
	bbs_checkorigin($board);
} else if ($ftype == $dir_modes["MARK"]) {
	bbs_checkmark($board);
}
if (!$managemode && $isnormalboard && (isset($_GET["page"]) || $ftype) ) {
	$dotdirname = bbs_get_board_index($board, $ftype);
	if (cache_header("public",@filemtime($dotdirname),($ftype == $dir_modes["NORMAL"]) ? 10 : 300))
		return;
}

if (isset($_POST['act'])) {
	do_manage_function($board);
}

$total = bbs_countarticles($brdnum, $ftype);
if ($total <= 0) {
	if ($ftype) {
		html_error_quit("��������".$dir_name[$ftype]."Ŀǰû������");
	} else {
		if (strcmp($currentuser["userid"], "guest") != 0){
			html_error_quit("��������Ŀǰû������<br /><a href=\"bbspst.php?board=" . $board . "\">��������</a>");
		} else{
			html_error_quit("��������Ŀǰû������");
		}
	}
}

if (isset($_GET["page"]))
	$page = $_GET["page"];
elseif (isset($_POST["page"]))
	$page = $_POST["page"];
else
{
	if (isset($_GET["start"]))
	{ /* TODO: ȥ������������ݽ�֮ǰ�� javascript ��� page �� */
		$start = $_GET["start"];
		settype($start, "integer");
		$page = ($start + ARTCNT - 1) / ARTCNT;
	}
	else
		$page = 0;
}
settype($page, "integer");
if ($page > 0)
	$start = ($page - 1) * ARTCNT + 1;
else
	$start = 0;
/*
 * �������һ��ʱ�������⣬���ܻᵼ����ű��ҡ�
 * ԭ���������ε��� bbs_countarticles() �� bbs_getarticles()��
 */
if ($start == 0 || $start > ($total - ARTCNT + 1))
{
	if ($total <= ARTCNT)
	{
		$start = 1;
		$page = 1;
	}
	else
	{
		$start = ($total - ARTCNT + 1);
		$page = ($start + ARTCNT - 1) / ARTCNT + 1;
	}
}
else
	$page = ($start + ARTCNT - 1) / ARTCNT;
settype($page, "integer");
$articles = bbs_getarticles($brdarr["NAME"], $start, ARTCNT, $ftype);
if ($articles == FALSE){
	html_error_quit("��ȡ�����б�ʧ��");
}
		
bbs_board_header($brdarr,$ftype,$managemode,$isnormalboard);
display_articles($brdarr, $articles, $start, $ftype, $managemode, $page, $total,
	(defined('BBS_NEWPOSTSTAT') && !$managemode && $isnormalboard && !$ftype) );

if (defined("SITE_SMTH")) { @include("tshirtlink.php"); }

bbs_board_foot($brdarr, $managemode, $ftype, $isnormalboard);
page_footer(/*$managemode ? FALSE : TRUE */);
?>
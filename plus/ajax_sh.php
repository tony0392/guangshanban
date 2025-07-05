<?php
/**
 *
 * dede文章自动审核插件,dede自动审核插件2.2,岑溪网站开发 QQ 22599377 tel 18278019929 http://www.12345t.com/!
 *
 */
require_once(dirname(__FILE__)."/../include/common.inc.php");
require_once(DEDEINC.'/archives.func.php');
$sql = "SELECT msg,utime,tonum,info FROM `#@__auto`";
$rows = $dsql->GetOne($sql);
if(!is_array($rows)) tips("配置失败");
/*数据*/
$row=unserialize($rows['msg']);;
if($row['wnum']==""||$row['otime']=="") tips("时间间隔，最大编数未填写!");
if($row['start']=="0") tips("自动审核被关闭");
/*时间*/
$utime=$rows['utime'];
$tonum=$rows['tonum'];
$info=unserialize($rows['info']);
/*现在时间*/
$newtime=time();
$newhour=date('G');
/*时段是否允许*/

if (!array_key_exists($newhour,$row['ktime'])){
tips("审核时段不在范围内",$row['tip']);
}
/*时间间隔是否允许*/
if(($utime-$newtime)>0){
tips("允许".$row['wnum']."编已审".$tonum."编还需".($utime-$newtime)."秒才能运行审核!",$row['tip']);
}else{
/*抗拒第二天重置*/
$newday=strtotime(date("Y-m-d",time()));
$sqlday=strtotime(date("Y-m-d",$utime));
if($newday!=$sqlday){$query = "UPDATE `#@__auto` SET utime='$newtime',tonum='0',info=''";
$dsql->ExecuteNoneQuery($query);
tips("时间已重新配置!",$row['tip']);
}
/*更新时间间隔*/
$utime=$newtime+$row['otime'];
$query = "UPDATE `#@__auto` SET utime='$utime'";
$dsql->ExecuteNoneQuery($query);
}
/*编数是否允许*/
if($tonum>=$row['wnum']){
tips("允许".$row['wnum']."编已审".$tonum."编",$row['tip']);
}


/*这里可以检测文章了*/
$istype=join(",",$row['type']);
$arc = $dsql->GetOne("Select * From #@__arctiny  where arcrank='-1' and typeid in($istype) order by id");
if(!is_array($arc)) tips("没有可审核的文章了");
$upquery = "Update `#@__archives` set ismake=1,arcrank =0,pubdate ='$newtime',senddate='$newtime',sortrank='$newtime' where id='$arc[id]';"; 
$upquery1 = "Update `#@__arctiny` set arcrank =0,senddate='$newtime',sortrank='$newtime' where id='$arc[id]';"; 
$rs = $dsql->ExecuteNoneQuery($upquery);
$rs1 = $dsql->ExecuteNoneQuery($upquery1);
if($rs==''&&$rs1=='') {tips("审核中遇到未知错误");}else{
$tonum=$tonum+1;
$utime=$newtime+$row['otime'];
$query = "UPDATE `#@__auto` SET utime='$utime',tonum='$tonum'";
$dsql->ExecuteNoneQuery($query);
}
/*生成文章*/
$artUrl = MakeArt($arc[id], TRUE);
/*生成栏目*/
$typediarr = array();
array_push($typediarr,$arc['typeid']);
$row3 = $dsql->GetOne("Select reid,topid From `#@__arctype` where id=".$arc['typeid']);
if(!in_array($row3['reid'],$typediarr) and $row3['reid']!=0) array_push($typediarr,$row3['reid']);
if(!in_array($row3['topid'],$typediarr) and $row3['topid']!=0) array_push($typediarr,$row3['topid']);
  require_once(DEDEDATA."/cache/inc_catalog_base.inc");
  require_once(DEDEINC."/channelunit.func.php");
  require_once(DEDEINC."/arc.listview.class.php");
$shlisthtml=$row['htmlnum']?$$row['htmlnum']:2;
  foreach($typediarr as $typeid)
	{
	    $lv = new ListView($typeid);
		$reurl = $lv->MakeHtml(1,$shlisthtml);
	}
/*更新首页*/
$index=index_do();
if($index){
if($row['log']=="1"){
$info[]=$artUrl;
$infod=serialize($info);
$query = "UPDATE `#@__auto` SET info='$infod'";
$dsql->ExecuteNoneQuery($query);
}
tips("审核成功:".$artUrl,$row['tip']);
}

function MakeArt($aid, $ismakesign=FALSE)
{
    global $cfg_makeindex,$cfg_basedir,$cfg_templets_dir,$cfg_df_style;
    include_once(DEDEINC.'/arc.archives.class.php');
    if($ismakesign)
    {
        $envs['makesign'] = 'yes';
    }
    $arc = new Archives($aid);
    $reurl = $arc->MakeHtml();
    if(isset($typeid))
    {
        $preRow =  $arc->dsql->GetOne("SELECT id FROM `#@__arctiny` WHERE id<$aid AND arcrank>-1 AND typeid='$typeid' order by id desc");
        $nextRow = $arc->dsql->GetOne("SELECT id FROM `#@__arctiny` WHERE id>$aid AND arcrank>-1 AND typeid='$typeid' order by id asc");
        if(is_array($preRow))
        {
            $arc = new Archives($preRow['id']);
            $arc->MakeHtml();
        }
        if(is_array($nextRow))
        {
            $arc = new Archives($nextRow['id']);
            $arc->MakeHtml();
        }
    }
    return $reurl;
}
function index_do(){
require_once(DEDEINC."/arc.partview.class.php");
global $dsql,$cfg_basedir,$cfg_templets_dir;
$envs = $_sys_globals = array(); 
$envs['aid'] = 0; 
$indexs = $dsql->GetOne("select * from`#@__homepageset`");
$templet = str_replace("{style}", $cfg_df_style, $indexs['templet']); 
$homeFile = str_replace("../", "",$indexs['position']);
$homeFile=$cfg_basedir.'/'.$homeFile;
$fp = fopen($homeFile, 'w') or die("无法更新网站主页到：$homeFile 位置"); 
fclose($fp); 
$tpl = $cfg_basedir.$cfg_templets_dir.'/'.$templet; 
if(!file_exists($tpl)) exit("无法找到主页模板：$tpl "); 
$GLOBALS['_arclistEnv'] = 'index'; 
$pv = new PartView();
$pv->SetTemplet($tpl); 
$pv->SaveToHtml($homeFile); 
$pv->Close();
return true;
}
function tips($msg,$tips=1){
if($tips){
exit($msg);
}else{
exit;
}
}
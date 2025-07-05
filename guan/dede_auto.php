<?php
/**
 * 自动审核 V2.0
 *
 * @version        开发 QQ 22599377
 * @link           http://dedeauto.12345t.com
 */
require(dirname(__FILE__)."/config.php");
CheckPurview('plus_审核模块');
if($dopost == "autosave")
{
unset($_POST['dopost']);
unset($_POST['Submit']);
$msg=serialize($_POST);
    $query = "UPDATE `#@__auto`
     SET
     msg='$msg'
     ";
    $dsql->ExecuteNoneQuery($query);
    ShowMsg("修改成功！","dede_auto.php");
    exit;
}
$sql = "SELECT msg,info FROM `#@__auto`";
$rows = $dsql->GetOne($sql);
if(!is_array($rows)){
$query = "INSERT INTO #@__auto(msg) VALUES('1');";
$dsql->ExecuteNoneQuery($query);
}
$row=unserialize($rows['msg']);
$info=unserialize($rows['info']);
$sql = "SELECT typename,id FROM `#@__arctype` where isdefault=1 and ispart=0";
$dsql->SetQuery($sql);//将SQL查询语句格式化
$dsql->Execute();//执行SQL操作
//通过循环输出执行查询中的结果
while($t = $dsql->GetArray()){
$type[]=$t;
}
include DedeInclude('templets/dede_auto.htm');
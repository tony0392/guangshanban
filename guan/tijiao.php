<?php
require_once(dirname(__FILE__)."/config.php");
file_put_contents('../url.txt','');
//file_put_contents('',"../url.txt");
if(empty($do))
{
    include DEDEADMIN.'/templets/tijiao.htm';
} else { 
$limit = $cfg_tijiao_limit;
if($limit > 50 || $limit < 1)
	{
	 $limit = 50;
	}
$query = "SELECT maintable.*, addtable.body, arctype.typename
FROM #@__archives maintable
LEFT JOIN #@__addonarticle addtable ON addtable.aid=maintable.id
LEFT JOIN #@__arctype arctype ON arctype.ID=maintable.typeid
WHERE maintable.channel=1 and maintable.arcrank!=-1 ORDER BY maintable.pubdate DESC LIMIT $limit
";
$dsql->SetQuery($query);
$dsql->Execute();
while($row = $dsql->GetArray())
	{
		$row1 = GetOneArchive($row['id']);
		if(strpos($row1['arcurl'],'http://') === false)
		{
			$link = ($cfg_basehost=='' ? 'http://'.$_SERVER["HTTP_HOST"].$cfg_cmspath : $cfg_basehost).$row1['arcurl'];
		}else
		{
			$link = $row1['arcurl'];
		}
		$link = htmlspecialchars($link);
		$k=fopen("../url.txt","a+");
		fwrite($k,$link."\r\n");		
	}
fclose($k);	
$file = '../url.txt';
$content = file_get_contents($file);
//echo $content;
$array = explode("\r\n", $content);
//print_r($array);
$urls = $array;
$api = $cfg_jiekou;
$ch = curl_init();
$options =  array(
    CURLOPT_URL => $api,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => implode("\n", $urls),
    CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
);
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
echo $result;
}
?>
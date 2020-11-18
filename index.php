<?php
/**
 * @version        $Id: index.php 2020-11-18 tianya $
 * @copyright      Copyright (c) 2020, DedeBIZ.COM
 * @license        https://www.dedebiz.com/license
 * @link           https://www.dedebiz.com
 */
// 把大象装冰箱，总共分几步？
// 1.数据库转码
// 2.文件选择性覆盖
$cfg_NotPrintHead = "Y";
require_once (dirname(__FILE__) . "/../include/common.inc.php");
require_once (dirname(__FILE__) . "/common.func.php");

$bkdir = DEDEDATA.'/'.$cfg_backup_dir."/tov6";
MkdirAll($bkdir,$GLOBALS['cfg_dir_purview']);

$step = isset($step)? intval($step) : 0;

//跳转到一下页的JS
$gotojs = "function GotoNextPage(){
    document.gonext."."submit();
}"."\r\nset"."Timeout('GotoNextPage()',500);";

$dojs = "<script language='javascript'>$gotojs</script>";

if ( empty($tablearr) )
{
		$dsql->SetQuery("SHOW TABLES");
		$dsql->Execute('t');
		while($row = $dsql->GetArray('t',MYSQL_BOTH))
		{
		    if(preg_match("#^{$cfg_dbprefix}#", $row[0])||in_array($row[0],$channelTables))
		    {
		        $dedeSysTables[] = $row[0];
		    }
		}
		$tablearr = implode(",", $dedeSysTables);
}

if ( empty($bakfiles) )
{
	$dh = dir($bkdir);
	$structfile = "";
	while(($filename=$dh->read()) !== false)
	{
	    if(!preg_match("#txt$#", $filename))
	    {
	        continue;
	    }
	    if(preg_match("#tables_struct#", $filename))
	    {
	        $structfile = $filename;
	    }
	    else if( filesize("$bkdir/$filename") >0 )
	    {
	        $filelists[] = $filename;
	    }
	}
	$dh->close();
	$bakfiles = implode(",", $filelists);
}

if ($step == 0) {
	ShowMsgV6("执行升级程序之前，您需要<font color=red>备份站点数据库及文件</font>，如果已经完成备份，可以点击下面“开始升级到V6”进行升级！<br><a target='_blank' class='btn btn-outline-success' href='https://www.dedebiz.com/help/tov6.md'>升级文档</a> <a class='btn btn-success' href='index.php?step=1'>开始升级到V6</a>","javascript:;");
}

// 对数据库进行编码转换
else if ( $step == 1 )
{
	if ( $cfg_soft_lang === "gb2312" )
	{
	    //初始化使用到的变量
	    $tables = explode(',', $tablearr);
	    $isstruct = 1;
	    if(!isset($startpos))
	    {
	        $startpos = 0;
	    }
	    if(!isset($iszip))
	    {
	        $iszip = 0;
	    }
	    if(empty($nowtable))
	    {
	        $nowtable = '';
	    }
	    if(empty($fsize))
	    {
	        $fsize = 2048;
	    }
	    $fsizeb = $fsize * 1024;

	    //第一页的操作
	    if($nowtable=='')
	    {
	        $tmsg = '';
	        $dh = dir($bkdir);
	        while($filename = $dh->read())
	        {
	            if(!preg_match("#txt$#", $filename))
	            {
	                continue;
	            }
	            $filename = $bkdir."/$filename";
	            if(!is_dir($filename))
	            {
	                unlink($filename);
	            }
	        }
	        $dh->close();
	        $tmsg .= "发现是GBK编码系统，需要进行数据库转码...<br />";

	        if($isstruct==1)
	        {
	            $bkfile = $bkdir."/tables_struct_".substr(md5(time().mt_rand(1000,5000).$cfg_cookie_encode),0,16).".txt";
	            $mysql_version = $dsql->GetVersion();
	            $fp = fopen($bkfile, "w");
	            foreach($tables as $t)
	            {
		            $ttt = str_replace($cfg_dbprefix, $cfg_dbprefix."v6_", $t);
	                fwrite($fp, "DROP TABLE IF EXISTS `$ttt`;\r\n\r\n");
	                $dsql->SetQuery("SHOW CREATE TABLE ". $dsql->dbName .".".$t);
	                $dsql->Execute('me');
	                $row = $dsql->GetArray('me', MYSQL_BOTH);

	                //去除AUTO_INCREMENT
	                $row[1] = preg_replace("#AUTO_INCREMENT=([0-9]{1,})[ \r\n\t]{1,}#i", "", $row[1]);
                    $eng1 = "#ENGINE=MyISAM[ \r\n\t]{1,}DEFAULT[ \r\n\t]{1,}CHARSET=gbk#i";
                    $row[1] = preg_replace($eng1, "ENGINE=MyISAM CHARSET=utf8", $row[1]);
                    
                    $row[1] = str_replace("CREATE TABLE `".$cfg_dbprefix, "CREATE TABLE `".$cfg_dbprefix."v6_", $row[1]);

	                $tableStruct = $row[1];

	                $tableStruct = gb2utf8($tableStruct);
	                
	                fwrite($fp,''.$tableStruct.";\r\n\r\n");
	            }
	            fclose($fp);
	            $tmsg .= "转化数据表结构信息完成...<br />";
	        }
	        $tmsg .= "<font color='red'>正在进行数据转化的初始化工作，请稍后...</font>";
	        $doneForm = "<form name='gonext' method='post' action='index.php'>
	           <input type='hidden' name='isstruct' value='$isstruct' />
	           <input type='hidden' name='step' value='1' />
	           <input type='hidden' name='fsize' value='$fsize' />
	           <input type='hidden' name='tablearr' value='$tablearr' />
	           <input type='hidden' name='nowtable' value='{$tables[0]}' />
	           <input type='hidden' name='startpos' value='0' />
	           <input type='hidden' name='iszip' value='$iszip' />\r\n</form>\r\n{$dojs}\r\n";
	        PutInfo($tmsg, $doneForm);
	        exit();
	    }
	    //执行分页备份
	    else
	    {
	        $j = 0;
	        $fs = array();
	        $bakStr = '';

	        //分析表里的字段信息
	        $dsql->GetTableFields($nowtable);
	        $tmpNowtable = str_replace($cfg_dbprefix, $cfg_dbprefix."v6_", $nowtable);
	        $intable = "INSERT INTO `$tmpNowtable` VALUES(";
	        while($r = $dsql->GetFieldObject())
	        {
	            $fs[$j] = trim($r->name);
	            $j++;
	        }

	        $fsd = $j-1;

	        //读取表的内容
	        $dsql->SetQuery("SELECT * FROM `$nowtable` ");
	        $dsql->Execute();
	        $m = 0;
	        $bakfilename = "$bkdir/{$nowtable}_{$startpos}_".substr(md5(time().mt_rand(1000,5000).$cfg_cookie_encode),0,16).".txt";
	        
	        while($row2 = $dsql->GetArray())
	        {
		        
	            if($m < $startpos)
	            {
	                $m++;
	                continue;
	            }

	            //检测数据是否达到规定大小
	            if(strlen($bakStr) > $fsizeb)
	            {
	                $fp = fopen($bakfilename,"w");
	                $bakStr = gb2utf8($bakStr);
	                fwrite($fp,$bakStr);
	                fclose($fp);
	                $tmsg = "<font color='red'>完成到{$m}条记录的转化，继续转化{$nowtable}...</font>";
	                $doneForm = "<form name='gonext' method='post' action='index.php'>
	                <input type='hidden' name='step' value='1' />
	                <input type='hidden' name='isstruct' value='$isstruct' />
	                <input type='hidden' name='dopost' value='bak' />
	                <input type='hidden' name='fsize' value='$fsize' />
	                <input type='hidden' name='tablearr' value='$tablearr' />
	                <input type='hidden' name='nowtable' value='$nowtable' />
	                <input type='hidden' name='startpos' value='$m' />
	                <input type='hidden' name='iszip' value='$iszip' />\r\n</form>\r\n{$dojs}\r\n";
	                PutInfo($tmsg,$doneForm);
	                exit();
	            }

	            //正常情况
	            $line = $intable;
	            for($j=0; $j<=$fsd; $j++)
	            {
	                if($j < $fsd)
	                {
	                    $line .= "'".RpLine(addslashes($row2[$fs[$j]]))."',";
	                }
	                else
	                {
	                    $line .= "'".RpLine(addslashes($row2[$fs[$j]]))."');\r\n";
	                }
	            }
	            $m++;
	            $bakStr .= $line;
	        }

	        //如果数据比卷设置值小
	        if($bakStr!='')
	        {
	            $fp = fopen($bakfilename,"w");
	            $bakStr = gb2utf8($bakStr);
	            fwrite($fp,$bakStr);
	            fclose($fp);
	        }
	        for($i=0; $i<count($tables); $i++)
	        {
	            if($tables[$i] == $nowtable)
	            {
	                if(isset($tables[$i+1]))
	                {
	                    $nowtable = $tables[$i+1];
	                    $startpos = 0;
	                    break;
	                }else
	                {
				        ShowMsgV6('完成数据备份转码，下面进行还原……', 'index?step=2');
				        exit();
	                }
	            }
	        }
	        $tmsg = "<font color='red'>完成到{$m}条记录的转码，继续转码{$nowtable}...</font>";
	        $doneForm = "<form name='gonext' method='post' action='index.php'>
	          <input type='hidden' name='isstruct' value='$isstruct' />
	          <input type='hidden' name='step' value='1' />
	          <input type='hidden' name='fsize' value='$fsize' />
	          <input type='hidden' name='tablearr' value='$tablearr' />
	          <input type='hidden' name='nowtable' value='$nowtable' />
	          <input type='hidden' name='startpos' value='$startpos'>\r\n</form>\r\n{$dojs}\r\n";
	        PutInfo($tmsg,$doneForm);
	        exit();
	    }
	    //分页备份代码结束
	} else {
        ShowMsgV6('当前系统就是UTF8程序，无需数据库编码转换，进行DedeCMSV6升级……', 'index?step=3');
        exit();
	}
} 
// 还原为UTF8编码的文件
else if ( $step == 2 )
{
	if ( $cfg_soft_lang === "gb2312" )
	{
		@list($dbhost, $dbport) = explode(':', $GLOBALS['cfg_dbhost']);
		!$dbport && $dbport = 3306;
		$conn = mysqli_init();
		mysqli_real_connect($conn, $dbhost, $GLOBALS['cfg_dbuser'], $GLOBALS['cfg_dbpwd'], false, $dbport) or die("无法连接数据库");
		mysqli_query($conn, "SET character_set_connection=utf8,character_set_results=utf8,character_set_client=binary");
		mysqli_select_db($conn, $GLOBALS['cfg_dbname']);
	    $bakfilesTmp = $bakfiles;
	    $bakfiles = explode(',', $bakfiles);
	    if(empty($structfile))
	    {
	        $structfile = "";
	    }
	    if(empty($delfile))
	    {
	        $delfile = 1;
	    }
	    if(empty($startgo))
	    {
	        $startgo = 0;
	    }
	    if($startgo==0 && $structfile!='')
	    {
	        $tbdata = '';
	        $fp = fopen("$bkdir/$structfile", 'r');
	        while(!feof($fp))
	        {
	            $tbdata .= fgets($fp, 1024);
	        }
	        fclose($fp);
	        $querys = explode(';', $tbdata);

	        foreach($querys as $q)
	        {
		        $rs = mysqli_query($conn, trim($q).';');
		        if ( !$rs )
		        {
		        	//$str = mysqli_error($conn);
		        	//var_dump($str);
		        }
		        //$str = mysqli_error($conn);
		        //var_dump($str);
	            //$dsql->ExecuteNoneQuery(trim($q).';');
	        }
	        if($delfile==1)
	        {
	            @unlink("$bkdir/$structfile");
	        }
	        $tmsg = "<font color='red'>完成数据表编码，准备转化数据...</font>";
	        $doneForm = "<form name='gonext' method='post' action='index.php'>
	        <input type='hidden' name='step' value='2' />
	        <input type='hidden' name='startgo' value='1' />
	        <input type='hidden' name='delfile' value='$delfile' />
	        <input type='hidden' name='bakfiles' value='$bakfilesTmp' />
	        </form>\r\n{$dojs}\r\n";
	        PutInfo($tmsg, $doneForm);
	        exit();
	    }
	    else
	    {
	        $nowfile = $bakfiles[0];
	        $bakfilesTmp = preg_replace("#".$nowfile."[,]{0,1}#", "", $bakfilesTmp);
	        $oknum=0;
	        if( filesize("$bkdir/$nowfile") > 0 )
	        {
	            $fp = fopen("$bkdir/$nowfile", 'r');
	            while(!feof($fp))
	            {
	                $line = trim(fgets($fp, 512*1024));
	                if($line=="") continue;
	                //$rs = $dsql->ExecuteNoneQuery($line);
	                //var_dump($line);
	                $rs = mysqli_query($conn, $line);
	                if($rs) $oknum++;
	            }
	            fclose($fp);
	        }
	        if($delfile==1)
	        {
	            @unlink("$bkdir/$nowfile");
	        }
	        if($bakfilesTmp=="")
	        {
	            ShowMsgV6('成功转化所有数据，下面进行DedeCMSV6升级……', 'index.php?step=3');
	            exit();
	        }
	        $tmsg = "成功转化{$nowfile}的{$oknum}条记录<br/><br/>正在准备转化其它数据...";
	        $doneForm = "<form name='gonext' method='post' action='index.php'>
	        <input type='hidden' name='step' value='2' />
	        <input type='hidden' name='startgo' value='1' />
	        <input type='hidden' name='delfile' value='$delfile' />
	        <input type='hidden' name='bakfiles' value='$bakfilesTmp' />
	        </form>\r\n{$dojs}\r\n";
	        PutInfo($tmsg, $doneForm);
	        exit();
	    }
	} else {
        ShowMsgV6('当前系统就是UTF8程序，无需数据库编码转换，继续DedeCMSV6升级……', 'index?step=3');
        exit();
	}
} 
// 执行DedeCMSV6增量SQL
else if ( $step == 3 )
{
	@list($dbhost, $dbport) = explode(':', $GLOBALS['cfg_dbhost']);
	!$dbport && $dbport = 3306;
	$conn = mysqli_init();
	mysqli_real_connect($conn, $dbhost, $GLOBALS['cfg_dbuser'], $GLOBALS['cfg_dbpwd'], false, $dbport) or die("无法连接数据库");
	mysqli_query($conn, "SET character_set_connection=utf8,character_set_results=utf8,character_set_client=binary");
	mysqli_select_db($conn, $GLOBALS['cfg_dbname']);
		
	$querys = explode(';', $v6sql);

	foreach( $querys as $key => $q )
	{
        $prefix="#@__";
		$q = str_replace($prefix, $GLOBALS['cfg_dbprefix']."v6_", $q);
		$q = str_replace("TYPE=MyISAM", "ENGINE=MyISAM", $q);
        $rs = mysqli_query($conn, trim($q).';');
        if ( !$rs )
        {
        	// $str = mysqli_error($conn);
        	// var_dump($str);
        }
	}

	// 重写配置
	ReWriteConfig($conn);

	// 数据库连接文件
	$sqlconn = file_get_contents(dirname(__FILE__)."/common.inc.txt");
    $sqlconn = str_replace("~dbtype~","mysql",$sqlconn);
    $sqlconn = str_replace("~dbhost~",$GLOBALS['cfg_dbhost'],$sqlconn);
    $sqlconn = str_replace("~dbname~",$GLOBALS['cfg_dbname'],$sqlconn);
    $sqlconn = str_replace("~dbuser~",$GLOBALS['cfg_dbuser'],$sqlconn);
    $sqlconn = str_replace("~dbpwd~",$GLOBALS['cfg_dbpwd'],$sqlconn);
    $sqlconn = str_replace("~dbprefix~",$GLOBALS['cfg_dbprefix']."v6_",$sqlconn);
    $sqlconn = str_replace("~dblang~","utf8",$sqlconn);

	file_put_contents(DEDEDATA."/common.inc.php", $sqlconn);

	rename(dirname(__FILE__)."/index.php",dirname(__FILE__)."/index.php.bak");
	
	ShowMsgV6('完成系统数据库转换升级，下载DedeCMSV6系统，解压后将<code>./src</code>目录下的文件选择性替换即可完成升级。<br><a class=\'btn btn-success\' target=\'_blank\' href=\'https://www.dedebiz.com/download\'>下载DedeCMSV6</a> ', 'javascript:;');
	exit();
}


	

?>
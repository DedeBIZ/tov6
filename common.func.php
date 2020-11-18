<?php
/**
 * @version        $Id: common.func.php 2020-11-18 tianya $
 * @copyright      Copyright (c) 2020, DedeBIZ.COM
 * @license        https://www.dedebiz.com/license
 * @link           https://www.dedebiz.com
 */
$v6sql = <<<EOT
ALTER TABLE `#@__tagindex`
	ADD COLUMN `uptime` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `addtime`,
	ADD COLUMN `mktime` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `uptime`;

ALTER TABLE `#@__feedback`
	ADD COLUMN `fid` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `aid`;

ALTER TABLE `#@__feedback`
	ADD COLUMN `replycount` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `dtime`;

INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_feedback_msglen', '评论内容字数限定', 5, 'number', '200');
INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_auth_code', '授权码(登录www.dedebiz.com进行商业授权)', 1, 'bstring', '');
INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_bizcore_hostname', 'DedeBIZ Core地址', 1, 'string', '127.0.0.1');
INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_bizcore_port', 'DedeBIZ Core端口', 1, 'number', '8181');
INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_bizcore_appid', 'DedeBIZ Core应用ID', 1, 'string', '');
INSERT INTO `#@__sysconfig` (`varname`, `info`, `groupid`, `type`, `value`) VALUES ( 'cfg_bizcore_key', 'DedeBIZ Core通信密钥', 1, 'string', '');

CREATE TABLE `#@__feedback_goodbad` (
	`fgid` INT(11) NOT NULL AUTO_INCREMENT,
	`mid` INT(11) NOT NULL DEFAULT '0',
	`fid` INT(11) NOT NULL DEFAULT '0',
	`fgtype` TINYINT(4) NOT NULL DEFAULT '0' COMMENT '0:good 1:bad',
	PRIMARY KEY (`fgid`)
) TYPE=MyISAM CHARSET=utf8;

CREATE TABLE `#@__search_limits` (
	`ip` VARCHAR(200) NOT NULL,
	`searchtime` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`ip`)
) TYPE=MyISAM CHARSET=utf8;
EOT;

function RpLine($str)
{
    $str = str_replace("\r", "\\r", $str);
    $str = str_replace("\n", "\\n", $str);
    return $str;
}

function PutInfo($msg1,$msg2)
{
    $msginfo = "<html>\n<head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
        <title>DedeCMSV6 提示信息</title>
        <link rel=\"stylesheet\" href=\"static/css/bootstrap.min.css\"><style>.modal {position: static;}</style><link href=\"static/font-awesome/css/font-awesome.min.css\" rel=\"stylesheet\">
        <base target='_self'/>\n</head>\n<body leftmargin='0' topmargin='0'>\n<center>
        <br/>
        <main class='container'><div class='modal' tabindex='-1' role='dialog' style='display:block'><div class='modal-dialog'><div class='modal-content'><div class='modal-header'><h6 class='modal-title'>DedeCMSV6 提示信息</h6></div><div class='modal-body'>
        {$msg1}</div></div></div></div></main>\r\n{$msg2}";
    echo $msginfo."</center>\n</body>\n</html>";
}

function ShowMsgV6($msg, $gourl, $onlymsg=0, $limittime=0)
{
    if(empty($GLOBALS['cfg_plus_dir'])) $GLOBALS['cfg_plus_dir'] = '..';

    $htmlhead  = "<html>\r\n<head>\r\n<title>DedeCMSV6 提示信息</title>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">";
    $htmlhead .= "<link rel=\"stylesheet\" href=\"static/css/bootstrap.min.css\"><style>.modal {position: static;}</style><link href=\"static/font-awesome/css/font-awesome.min.css\" rel=\"stylesheet\">";
    $htmlhead .= "<base target='_self'/></head>\r\n<body leftmargin='0' topmargin='0' bgcolor='#FFFFFF'>".(isset($GLOBALS['ucsynlogin']) ? $GLOBALS['ucsynlogin'] : '')."\r\n<center>\r\n<script>\r\n";
    $htmlfoot  = "</script>\r\n</center>\r\n</body>\r\n</html>\r\n";

    $litime = ($limittime==0 ? 1000 : $limittime);
    $func = '';

    if($gourl=='-1')
    {
        if($limittime==0) $litime = 5000;
        $gourl = "javascript:history.go(-1);";
    }

    if($gourl=='' || $onlymsg==1)
    {
        $msg = "<script>alert(\"".str_replace("\"","“",$msg)."\");</script>";
    }
    else
    {
        //当网址为:close::objname 时, 关闭父框架的id=objname元素
        if(preg_match('/close::/',$gourl))
        {
            $tgobj = trim(preg_replace('/close::/', '', $gourl));
            $gourl = 'javascript:;';
            $func .= "window.parent.document.getElementById('{$tgobj}').style.display='none';\r\n";
        }

        $func .= "      var pgo=0;
      function JumpUrl(){
        if(pgo==0){ location='$gourl'; pgo=1; }
      }\r\n";
        $rmsg = $func;
        $rmsg .= "document.write(\"<main class='container'><div class='modal' tabindex='-1' role='dialog' style='display:block'><div class='modal-dialog'><div class='modal-content'><div class='modal-header'><h6 class='modal-title'>";
        $rmsg .= "DedeCMSV6 提示信息！</h6></div><div class='modal-body'>\");\r\n";
        $rmsg .= "document.write(\"".str_replace("\"","“",$msg)."\");\r\n";
        $rmsg .= "document.write(\"";

        if($onlymsg==0)
        {
            if( $gourl != 'javascript:;' && $gourl != '')
            {
                $rmsg .= "<br /><a href='{$gourl}'>如果你的浏览器没反应，请点击这里...</a>";
                $rmsg .= "</div></div></div></div></main>\");\r\n";
                $rmsg .= "setTimeout('JumpUrl()',$litime);";
            }
            else
            {
                $rmsg .= "</div></div></div></div></main>\");\r\n";
            }
        }
        else
        {
            $rmsg .= "</div></div></div></div></main>\");\r\n";
        }
        $msg  = $htmlhead.$rmsg.$htmlfoot;
    }
    echo $msg;
}

$configfile = DEDEDATA.'/config.cache.inc.php';

//更新配置函数
function ReWriteConfig($conn)
{
    global $configfile;
    if(!is_writeable($configfile))
    {
        echo "配置文件'{$configfile}'不支持写入，无法修改系统配置参数！";
        exit();
    }
    $fp = fopen($configfile,'w');
    flock($fp,3);
    fwrite($fp,"<"."?php\r\n");

    $sql = "SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC ";
    $prefix="#@__";
    $sql = str_replace($prefix, $GLOBALS['cfg_dbprefix']."v6_", $sql);

    $result=mysqli_query($conn,$sql);


    while($row = mysqli_fetch_array($result,MYSQLI_ASSOC))
    {
        if($row['type']=='number')
        {
            if($row['value']=='') $row['value'] = 0;
            fwrite($fp,"\${$row['varname']} = ".$row['value'].";\r\n");
        }
        else
        {
            fwrite($fp,"\${$row['varname']} = '".str_replace("'",'',$row['value'])."';\r\n");
        }
    }
    fwrite($fp,"?".">");
    fclose($fp);
}

?>
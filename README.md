Old mysql methods using PDO
===============================
The package provides global mysql_* functions old model (PHP older 5.5) that can be used when the original MySQL extension is not available.

The librarie duplicates all popular of the functionality of mysql_* functions. Very high speed efficiency, almost 100% compared to direct use of PDO or mysqli.

It is important to note that there is support for encryption SSL certificate.

Examples:
---------------
Classic connection:

	//Connect and print date
	$link = mysql_connect($host, $user, $password);
	if ($link == false) { exit(); };
	mysql_select_db($db,$link);
	mysql_query('SET NAMES "utf8"');
	echo (mysql_result(mysql_query('SELECT NOW();'),0,0));

SSL connection:

	//SSL connect and print date
	$link = mysql_connect($host, $user, $password, 'yandex.crt');
	if ($link == false) { exit(); };
	mysql_select_db($db,$link);
	mysql_query('SET NAMES "utf8"');
	echo (mysql_result(mysql_query('SELECT NOW();'),0,0));

Examples:

	$result = mysql_query('SELECT `PAYMENTS`.`ID`, `PAYMENTS`.`DATE`, `PAYMENTS`.`CONTRACT`, `PAYMENTS`.`SUM`, `PAYMENTS`.`OPERATOR`, `PAYMENTS`.`COMMENT`, `PAYMENTS`.`DELETED`, `USERS`.`GROUP` FROM `PAYMENTS`,`USERS` WHERE `USERS`.`CONTRACT`=`PAYMENTS`.`CONTRACT` AND `PAYMENTS`.`ID_MANAGER`="'.$id_manager.'"'.$payments_search.' ORDER BY `DATE` DESC LIMIT '.$payments_count.';');
	for ($i=0; $i<mysql_num_rows($result); $i++)
	  {
	    echo '<td><u>'.mysql_result($result, $i, 0).'</u></td>';
	    echo '<td><u>'.mysql_result($result, $i, "PAYMENTS.CONTRACT").'</u></td>';
	  }


Who is this for?
----------------
This package is for site owners/developers who want to upgrade their PHP version to a version that has the mysql_connect/mysql_* functions removed without having to re-write their entire codebase to replace those functions to PDO or MySQLI.

About:
=========================
If you run into any issues, bugs, features or make things better please send them to me and I will get them in as soon as I can.

    @authors   Slevistrovich Leonid <crack-it@yandex.com>, Jaroslav Herber
    @copyright GPL license
    @license   http://www.gnu.org/copyleft/gpl.html
    @link      https://github.com/Leonid-SLV/

Versions:
=========

Current Version
---------------
Version 1.1
* Support SSL
* 100% efficiency compared to direct
* Proven library, works in billing systems for high-load objects


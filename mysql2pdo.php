<?php
/*
php-mysql2pdo
ver 1.1

authors: Leonid Selvistrovich, Jaroslav Herber
*/

/*
	Database-Object
*/
class jhDb {

	private static $_oInstance = null;

	private static $_aCachedDbs = array();
	private static $_oLastException = false;

	private static $_sHost = 'localhost';
	private static $_sUser = 'user';	// default-user
	private static $_sPass = 'password';	// default-password
	private static $_sCharset = 'utf8';
	private static $_sSSL = '';


	public static function getInstance() {

		if( !self::$_oInstance instanceof jhDb ) {
			self::$_oInstance = new jhDb();
		}

		return self::$_oInstance;

	}


	public static function getDb( $sDbName ) {
		if( $sDbName )
		  {
		    return self::createConnection($sDbName);
		  }
//		if( $sDbName ) {
//
//			if( !isset(self::$_aCachedDbs[$sDbName]) ) {
//				self::$_aCachedDbs[$sDbName] = self::createConnection($sDbName);
//			}
//
//			return self::$_aCachedDbs[$sDbName];
//
//		}

	}


	public static function setConnection( $sHost, $sUser, $sPass, $sSSL) {

		self::$_sHost = $sHost;
		self::$_sUser = $sUser;
		self::$_sPass = $sPass;
        self::$_sSSL = $sSSL;
	}


	protected static function createConnection( $sDbName ) {

		try {
			$aOptions = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true);
            if (self::$_sSSL != '') {$aOptions=$aOptions+array(PDO::MYSQL_ATTR_SSL_CA => self::$_sSSL);}
			return new PDO('mysql:host='.self::$_sHost.';dbname='.$sDbName.';charset='.self::$_sCharset, self::$_sUser, self::$_sPass, $aOptions);

		} catch( PDOException $oEx ) {

			self::setLastException($oEx);

		}

		return false;

	}


	public static function setLastException( $sError ) {
		self::$_oLastException = $sError;
	}


	public static function getLastException() {
		return self::$_oLastException;
	}


}


if( PHP_MAJOR_VERSION >= 7 ) {

	$GLOBALS['mysql_connections'] = array();

	define('MYSQL_BOTH', PDO::FETCH_BOTH);
	define('MYSQL_NUM', PDO::FETCH_NUM);
	define('MYSQL_ASSOC', PDO::FETCH_ASSOC);


	function mysql_connect($sHost, $sUser, $sPassword, $sSSL='') {
		jhDb::setConnection($sHost, $sUser, $sPassword, $sSSL);
		return $sUser;
	}

	function mysql_select_db( $sDbName, $sUser ) {
		$GLOBALS['mysql_connections'][$sUser] = jhDb::getDb($sDbName);
		return true;
	}

	function mysql_real_escape_string( $sString ) {
		return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $sString);
	}

	function mysql_query( $sQuery, $sUser = false ) {

		$oDb = false;

		//23.04.2020 / Virtual lower_case_table=1
		$sQueryTemp = $sQuery;
		$sQueryTempLower = false;
		for ($i=0; $i<=strlen($sQueryTemp)-1; $i++)
		  {
		    if ($sQueryTemp[$i]=='`')
		      {
		        if ($sQueryTempLower==false)
		          {
		            $sQueryTempLower=true;
		          }
		          else
		          {
		            $sQueryTempLower=false;
		          }
		      }
		      else
		      {
		        if ($sQueryTempLower==true)
		          {
		            $sQueryTemp[$i]=mb_strtolower($sQueryTemp[$i]);
		          }
		      }
		  }
	    $sQuery = $sQueryTemp;

		if( $sUser && isset($GLOBALS['mysql_connections'][$sUser]) ) {
			$oDb = $GLOBALS['mysql_connections'][$sUser];
		} elseif( count($GLOBALS['mysql_connections']) ) {
			$oDb = end($GLOBALS['mysql_connections']);
		}

		if( !$oDb ) {
			debug('No database-object!');
			return false;
		}

		try {
		    //echo $sQuery.'<br>'; // PATCH 24.04.2020 Debug
			$rRes = $oDb->query($sQuery);
			$GLOBALS['mysql_inser_id'] = $oDb->lastInsertId();  //PATCH 24.01.2020
		} catch( PDOException $oEx ) {
			jhDb::setLastException($oEx);
		}

        //$rRes->execute();
        //$rRes->fetchAll();
		return $rRes;

	}

//	function mysql_result( &$rRes, $iRow, $mField = 0 ) {
//
//		$iCountRow = 0;
//
//		$sFetchType = PDO::FETCH_NUM;
//		if( !is_numeric($mField) ) {
//			$sFetchType = PDO::FETCH_ASSOC;
//		}
//
//		while( $aRow = $rRes->fetch($sFetchType) ) {
//
//			if( $iRow === $iCountRow && isset($aRow[$mField]) ) {
//				return $aRow[$mField];
//			}
//
//			$iCountRow++;
//
//		}
//
//		return false;
//
//	}

	function mysql_result($rRes, $iRow, $mField = 0 )
	  {
	    //$rRes->execute();

	    $array_rRes = json_decode(json_encode($rRes),true); //Сохраняем данные подключения
		$query_rRes = mb_strtolower($array_rRes['queryString']); //Извлекаем запрос
	    if ($GLOBALS['mysql_cache_query']==$query_rRes)
	      {
	        //echo '!Запрос кеширован!';
	        $aRow = $GLOBALS['mysql_cache_aRow'];
	      }
	      else
	      {
	        $rRes->execute();
	        $aRow = $rRes->fetchAll(PDO::FETCH_NUM);
	        //Патч от 23.05.2020. Идея заключается в том, что есть смысл кешировать запросы с большим кол-вом строк на выходе
	        //и держать такой кеш. Пройдут более маленькие запросы, а в кеше тот большой запрос останется. При вызове его, он будет.
	        //Такой способ позволил ускорить работу на 25%, в отличии от кеширования предыдущего запроса.
	        if (($rRes->rowCount())>1)
	          {
	            $GLOBALS['mysql_cache_aRow'] = $aRow;
	            $GLOBALS['mysql_cache_query']=$query_rRes;
	          }
	      }


	    if( 1==1 ) {

			//$array_rRes = json_decode(json_encode($rRes),true); //Сохраняем данные подключения
			//$query_rRes = mb_strtolower($array_rRes['queryString']); //Извлекаем запрос
			$select_rRes = substr($query_rRes, strpos($query_rRes,'select')+6, strpos($query_rRes,'from')-6-strpos($query_rRes,'select')); //Выделяем запрашиваеммые поля
			$select_rRes = str_replace('`','',$select_rRes);
			$select_rRes = str_replace(' ','',$select_rRes);
			$array_select_rRes = explode(',',$select_rRes);

		    if ( !is_numeric($mField) )
		      {
			    $i_selRes = 0;
			    while ($i_selRes <= count($array_select_rRes)-1)
			      {
			        //PATCH от 17.01.2020 | Корректировка значений, в случае если в запросе указано ТАБЛИЦА.СТОЛБЕЦ, а значение просто СТОЛБЕЦ.
			        if ((strpos($array_select_rRes[$i_selRes],'.')===FALSE & strpos($mField,'.')!==FALSE) OR (strpos($array_select_rRes[$i_selRes],'.')!==FALSE & strpos($mField,'.')===FALSE))
			          {
			            if (strpos($array_select_rRes[$i_selRes],'.')!==FALSE)
			              {
			                $array_select_rRes[$i_selRes]=substr($array_select_rRes[$i_selRes],strpos($array_select_rRes[$i_selRes],'.')+1,strlen($array_select_rRes[$i_selRes])-strpos($array_select_rRes[$i_selRes],'.'));
			              }

                        if (strpos($mField,'.')!==FALSE)
			              {
			                $mField=substr($mField,strpos($mField,'.')+1,strlen($mField)-strpos($mField,'.'));
			              }
			          }
			        //
			        if ($array_select_rRes[$i_selRes]==mb_strtolower($mField))
			          {
			      	    return $aRow[$iRow][$i_selRes];
			        	$i_selRes = count($array_select_rRes);
			          }
			        $i_selRes = $i_selRes+1;
			      }
		      }
		      else
		      {
		        return $aRow[$iRow][$mField];
		      }


		}

		return false;

	  }

	function mysql_insert_id() {

		return $GLOBALS['mysql_inser_id'];

	}

	function mysql_fetch_array( &$rRes, $sFetchType = PDO::FETCH_NUM ) {

		if( $aRow = $rRes->fetch($sFetchType) ) {
			return $aRow;
		}

		return false;

	}

	function mysql_fetch_assoc( &$rRes ) {

		if( $aRow = $rRes->fetch(PDO::FETCH_ASSOC) ) {
			return $aRow;
		}

		return false;

	}

	function mysql_fetch_row( $rRes ) {

		if( $aRow = $rRes->fetch(PDO::FETCH_NUM) ) {
			return $aRow;
		}

		return false;

	}

	function mysql_num_rows( $rRes ) {
		return $rRes->rowCount();
	}

	function mysql_set_charset( $sCharSet, $oConnection ) {

		if( $sCharSet !== 'utf8' ) {
			debug('Only utf8 is supported');
			return false;
		}

		return true;

	}

	function mysql_error( $rRes ) {

		if( $oException = jhDb::getLastException() ) {
			return $oException->getMessage();
		}

		return false;

	}

	function mysql_errno( $rRes ) {

		if( $oException = jhDb::getLastException() ) {
			return $oException->getCode();
		}

		return 0;

	}

	function mysql_free_result( $rRes ) {
		// No need to delete RAM in year 2019
		return true;
	}

	function mysql_close($temp='')
	  {
	    $GLOBALS['mysql_connections'] = null;
	    return true;
	  }

}


if( !function_exists('debug') ) {
	function debug( $mVar, $sLogFile = '/tmp/debug.log' ) {

		$rLogFile = fopen($sLogFile, 'a');
		$sString = var_export($mVar, true);

		$sExecutionFile = '';
		$aBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
		$sOriginFunction = array_shift($aBacktrace);
		if( $sOriginFunction['file'] ) {
			$sExecutionFile = basename($sOriginFunction['file']);
		}

		fputs($rLogFile, "\n---- " . date('Y-m-d H:i:s') . ' / ' . $sExecutionFile . " ----\n" . $sString . "\n");
		fclose($rLogFile);

	}
}

?>
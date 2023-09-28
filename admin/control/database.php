<?php

if (!defined('IN_SYS')) exit('Access Denied');

set_time_limit(600);

class database_controller extends admincp
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$tables = db::select("SHOW TABLE STATUS");
		self::json(array('success' => $tables));
	}

	public function backup()
	{
		$tables = db::select("SHOW TABLE STATUS");
		$totalRow = array_sum(array_map(function ($val) {
			return $val['Rows'];
		}, $tables));

		$param = gp('param', false);
		if (empty($param)) {
			$config['datapath'] = DATA . "/backup/" . date("YmdHis") . '_' . 'V' . $GLOBALS['G']['softversion'] . '_' . md5(time() . mt_rand(1000, 5000) . uniqid(microtime(true), true));
			$config['begintime'] = time();
			$config['table'] = 0;
			$config['page'] = 0;
			$config['curRow'] = 0;
			$config['sumRow'] = 0;
			$config['totalRow'] = $totalRow;
		} else {
			$config = $param;
		}

		$table = $tables[$config['table']]['Name'];

		$bakStr = '';
		$fields = db::select("SHOW COLUMNS FROM `$table`");
		$queryId = db::query("Select * From `$table` ");

		$intable = "INSERT INTO `$table` VALUES(";
		$bakfilename = $config['datapath'] . "/{$table}__{$config['page']}.txt";

		if (mysqli_data_seek($queryId, $config['curRow'])) {
			while ($row = db::fetch($queryId)) {
				$lines = array();
				foreach ($fields as $f) {
					$field = $f['Field'];
					$row[$field] = addslashes($row[$field]);
					$row[$field] = str_replace("\r", "\\r", $row[$field]);
					$row[$field] = str_replace("\n", "\\n", $row[$field]);
					$lines[] = "'" . $row[$field] . "'";
				}
				$config['curRow']++;
				$config['sumRow']++;
				$bakStr .= $intable . join(",", $lines) . ");\r\n";
				if (strlen($bakStr) > 2097152) {
					$config['page']++;
					$this->writefile($bakfilename, $bakStr);
					self::json(array('continue' => "完成到$config[page]条记录的备份，继续备份{$table}...", 'param' => $config));
				}
			}
		}

		if ($bakStr != '') {
			$this->writefile($bakfilename, $bakStr);
		}
		if (isset($tables[$config['table'] + 1])) {
			$config['table'] = $config['table'] + 1;
			$config['curRow'] = 0;
			$config['page'] = 0;
			self::json(array('continue' => "{$table}表共$config[page]条记录备份完成", 'param' => $config));
		} else {
			$this->backuptableStruct($config['datapath']);
			$time = time() - $config['begintime'];
			self::json(array('success' => "完成所有数据备份！总共用时：$time 秒"));
		}
	}

	public function backuptableStruct($datapath)
	{
		$bkfile = $datapath . "/__tables_struct.txt";
		$tables = db::select("SHOW TABLE STATUS");
		foreach ($tables as $t) {
			$table = $t['Name'];
			$this->writefile($bkfile, "DROP TABLE IF EXISTS `$table`;\r\n");
			$row = db::select("SHOW CREATE TABLE $table");
			$tableStruct = preg_replace("/AUTO_INCREMENT=([0-9]{1,})[ \r\n\t]{1,}/i", "", $row[0]['Create Table']);
			$this->writefile($bkfile, $tableStruct . ";\r\n\r\n");
		}
	}

	public function backupDataList()
	{
		$list = glob(DATA . '/backup/*');
		$bk = array();
		foreach ($list as $k => $v) {
			if (is_dir($v)) {
				$basename = basename($v);
				$bk[$k]['name'] = $basename;
				list($time, $version) = explode('_', $basename);
				$bk[$k]['time'] = strtotime($time);
				$bk[$k]['version'] = $version[0] == 'V' ? substr($version, 1) : '';
				$bk[$k]['status'] = is_file($v . '/__tables_struct.txt');
				$bk[$k]['size'] = $this->dirSize($v);
			}
		}
		self::json(array('success' => $bk));
	}

	public function backupDataDel()
	{
		$name = gp('name');
		$dir = DATA . '/backup/' . $name;
		helper::delDir($dir);
	}

	public function restore()
	{
		if (empty($_SESSION['bkfiles'])) {
			$tables = db::select("SHOW TABLE STATUS");
			array_map(function ($val) {
				if (preg_match('#content__(\d*)$#i', $val['Name'])) {
					db::query("DROP TABLE IF EXISTS `$val[Name]`;");
				}
			}, $tables);

			$name = gp('name');
			$path = DATA . '/backup/' . $name;
			$files = glob($path . '/*');
			$bkfiles = array();
			$adminfiles = array();
			$configfiles = array();
			$yumingfiles = array();
			foreach ($files as $f) {
				if (!preg_match('#__tables_struct.txt$#i', $f)) {
					if (preg_match('#admin__(\d*).txt$#i', $f)) {
						$adminfiles[] = $f;
					} elseif (preg_match('#config__(\d*).txt$#i', $f)) {
						$configfiles[] = $f;
					} elseif (preg_match('#yuming__(\d*).txt$#i', $f)) {
						$yumingfiles[] = $f;
					} else {
						$bkfiles[] = $f;
					}
				}
			}
			$_SESSION['bkfiles'] = $bkfiles;
			$_SESSION['bkfiles_num'] = count($bkfiles) + 1;

			$tables_struct = $path . '/__tables_struct.txt';
			$tbdata = file_get_contents($tables_struct);
			$querys = explode(';', $tbdata);
			foreach ($querys as $q) {
				$q = trim($q);
				if ($q != '') db::query($q . ';');
			}

			while ($file = array_pop($configfiles)) {
				$this->restoreFile($file);
			}
			while ($file = array_pop($adminfiles)) {
				$this->restoreFile($file);
			}
			while ($file = array_pop($yumingfiles)) {
				$this->restoreFile($file);
			}
			self::json(array('continue' => '数据表已重建，准备还原数据', 'percentage' => 1));
		} else {
			$file = array_pop($_SESSION['bkfiles']);
			$this->restoreFile($file);
			if (empty($_SESSION['bkfiles'])) {
				self::json(array('success' => '数据还原成功', 'percentage' => 100));
			} else {
				$percentage = intval((($_SESSION['bkfiles_num'] - count($_SESSION['bkfiles'])) / $_SESSION['bkfiles_num']) * 100);
				self::json(array('continue' => '正在还原，请耐心等待不要关闭页面', 'percentage' => $percentage));
			}
		}
	}

	public function restoreFile($file)
	{
		$fp = fopen($file, 'r');
		while (!feof($fp)) {
			$line = trim(fgets($fp, 512 * 1024));
			if ($line == "") {
				continue;
			}
			db::query($line);
		}
		fclose($fp);
	}

	public function command()
	{
		if (IS_POST) {
			$command  = gp("command", false);
			$command = stripslashes($command);
			$command = str_replace("\r", "", $command);
			$temp_sql = preg_split("#;[ \t]{0,}#", $command);
			$resp = array();
			foreach ($temp_sql as $sql) {
				$sql = trim($sql);
				if (empty($sql)) continue;
				if (preg_match("#^select #i", $sql)) {
					if (strpos($sql, 'limit') === false) {
						$sql = $sql . " limit 10";
					}
					$columns = array();
					try {
						$list = db::select($sql);
						if ($list) {
							$resp[] = array(
								'success' => '执行成功',
								'sql' => $sql,
								'data' => $list,
							);
						}
					} catch (Exception $e) {
						$resp[] = array(
							'error' => 'SQL错误，执行失败',
							'sql' => $sql,
						);
					}
				} else {
					try {
						$result = db::query($sql);
						if ($result) {
							$resp[] = array(
								'success' => '执行成功',
								'sql' => $sql,
							);
						} else {
							$resp[] = array(
								'error' => '执行失败',
								'sql' => $sql,
							);
						}
					} catch (Exception $e) {
						$resp[] = array(
							'error' => 'SQL错误，执行失败',
							'sql' => $sql,
						);
					}
				}
			}
			self::json($resp);
		}
	}

	public function optimize()
	{
		$table = gp('table');
		db::query("OPTIMIZE TABLE $table ");
		self::json(array('success' => true));
	}

	public function repair()
	{
		$table = gp('table');
		db::query("REPAIR TABLE $table ");
		self::json(array('success' => true, 'tip' => $table . ' 执行成功'));
	}

	protected function dirSize($dir)
	{
		$dh = opendir($dir);
		$size = 0;
		while (false !== ($file = @readdir($dh))) {
			if ($file != '.' and $file != '..') {
				$path = $dir . '/' . $file;
				if (is_dir($path)) {
					$size += $this->dirSize($path);
				} elseif (is_file($path)) {
					$size += filesize($path);
				}
			}
		}
		closedir($dh);
		return $size;
	}

	protected function writefile($file, $content)
	{
		$filedir = dirname($file);
		if (!is_dir($filedir)) {
			if (@mkdir($filedir, 0777, TRUE) === FALSE) {
				return FALSE;
			}
		}
		return @file_put_contents($file, $content, FILE_APPEND);
	}
}

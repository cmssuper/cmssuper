<?php

class sysInit
{

	public  static function start()
	{
		$filename = DATA . "/config.php";
		if (file_exists($filename)) {
			header("Location: /");
			exit();
		}
		$action = gp('action');
		if (empty($action)) {
			self::show();
		} else if ($action == "ajax") {
			self::ajax();
		} else if ($action == "dbcheck") {
			self::dbcheck();
		} else if ($action == "dbinit") {
			self::dbinit();
		}
	}

	public static function ajax()
	{
		$G['sp_os'] = PHP_OS;
		$G['sp_server'] = $_SERVER['SERVER_SOFTWARE'];
		$G['sp_name'] = $_SERVER['SERVER_NAME'];
		$G['upload'] =  is_writable(ROOT . '/uploads');
		$G['data'] =  is_writable(ROOT . '/data');
		$G['sysdir'] = ROOT;
		$G['phpv_check'] = !version_compare(PHP_VERSION, '7.4.0', '<');
		$G['curl_check'] = function_exists('curl_init');
		$G['mb_check'] = function_exists('mb_substr');
		$G['mysqli_check'] = function_exists('mysqli_connect');
		$G['phpv'] = $G['phpv_check'] ? PHP_VERSION : "<font color=red>" . PHP_VERSION . " （必须7.4或以上版本）</font>";
		echo json_encode($G);
	}

	public static function dbcheck()
	{
		$dblink = gp('dblink', -1);
		$dbuser = gp('dbuser', -1);
		$dbpassword =  gp('dbpassword', -1);
		$dbname = gp('dbname', -1);
		$hostinfo = explode(":", $dblink);
		$dbhost = $hostinfo[0];
		$port = isset($hostinfo[1]) ? $hostinfo[1] : 3306;

		if (empty($dbname)) {
			echo json_encode(array('error' => '请先填写数据库配置信息'));
			exit;
		}

		mysqli_report(MYSQLI_REPORT_ALL);
		$conn = false;
		try {
			$conn = mysqli_connect($dbhost, $dbuser, $dbpassword, null, $port);
		} catch (Exception $e) {
		}
		if ($conn) {
			$result = false;
			try {
				$result = mysqli_select_db($conn, $dbname);
			} catch (Exception $e) {
			}
			if (!$result) {
				echo json_encode(array('success' => '数据库连接成功，正在为你创建数据库！', 'dbexist' => false));
			} else {
				echo json_encode(array('success' => '你的数据库已经存在，重新安装将覆盖原数据库数据，是否要安装', 'dbexist' => true));
			}
		} else {
			echo json_encode(array('error' => '数据库连接失败，请检查数据库配置信息'));
		}
	}

	public static function dbinit()
	{
		$dblink = gp('dblink', -1);
		$dbuser = gp('dbuser', -1);
		$dbpassword =  gp('dbpassword', -1);
		$dbname = gp('dbname', -1);
		$hostinfo = explode(":", $dblink);
		$dbhost = $hostinfo[0];
		$port = isset($hostinfo[1]) ? $hostinfo[1] : 3306;
		$username = gp('username');
		$password = gp('password');
		$conn = mysqli_connect($dbhost, $dbuser, $dbpassword, null, $port);
		$result = mysqli_select_db($conn, $dbname);
		mysqli_query($conn, "SET character_set_connection='utf8',character_set_results='utf8',character_set_client=binary,sql_mode=''");
		if (!$result) {
			mysqli_query($conn, "create database  `$dbname`");
			if (!mysqli_select_db($conn, $dbname)) {
				echo json_encode(array('error' => mysqli_error($conn)));
				exit();
			}
		}
		// initSql
		$sqls = <<<EOF
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(32) DEFAULT NULL,
  `salt` varchar(10) DEFAULT '',
  `siteids` text,
  `status` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员';

DROP TABLE IF EXISTS `ads`;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yuming_id` int(11) NOT NULL DEFAULT '0' COMMENT '域名ID',
  `abc` varchar(30) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `allowSpider` tinyint(1) NOT NULL DEFAULT '1',
  `content` text,
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `abc` (`abc`,`yuming_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `attachment`;
CREATE TABLE `attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `classlist`;
CREATE TABLE `classlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yuming_id` int(11) DEFAULT NULL,
  `ename` varchar(30) DEFAULT NULL,
  `title` varchar(30) DEFAULT NULL,
  `weight` smallint(6) DEFAULT '50',
  `status` tinyint(4) DEFAULT '1',
  `crawlWords` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `yuming_id` (`yuming_id`,`ename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `varName` varchar(255) NOT NULL,
  `varValue` text,
  PRIMARY KEY (`varName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `crawler`;
CREATE TABLE `crawler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yuming_id` int(11) DEFAULT NULL,
  `class` varchar(30) DEFAULT NULL,
  `listurl` varchar(255) DEFAULT NULL,
  `articlerule` text,
  `titlerule` text,
  `contentrule` text,
  `norule` text,
  `updatetime` varchar(255) DEFAULT NULL,
  `page` int(11) DEFAULT '0',
  `autoStart` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `crawllinks`;
CREATE TABLE `crawllinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `yuming_id` int(11) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT '',
  `ruleid` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `typeof_ruleid_link` (`ruleid`,`link`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `datalist`;
CREATE TABLE `datalist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `flink`;
CREATE TABLE `flink` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sitename` varchar(50) DEFAULT NULL,
  `url` varchar(180) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT '1',
  `yuming_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `reword`;
CREATE TABLE `reword` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oldword` varchar(255) DEFAULT NULL,
  `newword` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `yuming_id` varchar(50) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `seoconfig`;
CREATE TABLE `seoconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seoWord` text,
  `seoTitle` text,
  `seoTitlex` text,
  `seoWordNum` text,
  `seoWordScale` text,
  `yuming_id` int(11) DEFAULT NULL,
  `autoseo` int(11) DEFAULT '0',
  `autonum` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `spiderlog`;
CREATE TABLE `spiderlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siteId` int(11) NOT NULL,
  `spider` varchar(20) NOT NULL,
  `url` varchar(100) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `ua` varchar(255) NOT NULL,
  `addtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `spiderstats`;
CREATE TABLE `spiderstats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siteId` int(11) NOT NULL,
  `spider` varchar(20) NOT NULL,
  `daytime` varchar(10) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  `addtime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `siteId_spider_daytime` (`siteId`,`spider`,`daytime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `tagindex`;
CREATE TABLE `tagindex` (
  `aid` int(11) NOT NULL DEFAULT '0',
  `siteId` int(11) DEFAULT NULL,
  `tagid` int(11) NOT NULL DEFAULT '0',
  `click` int(11) DEFAULT '0',
  PRIMARY KEY (`aid`,`tagid`),
  KEY `tagid_siteId` (`tagid`,`siteId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `tagslist`;
CREATE TABLE `tagslist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagsname` varchar(255) DEFAULT NULL,
  `addtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addtime` (`addtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `yuming`;
CREATE TABLE `yuming` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sitename` varchar(20) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `keywords` varchar(150) DEFAULT NULL,
  `template` varchar(20) DEFAULT NULL,
  `siteTitle` varchar(50) DEFAULT NULL,
  `mobileSwitch` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `config` (`varName`, `varValue`) VALUES ('downPicture', '1'), ('site_code', ''), ('appkey', ''), ('softversion', '4.3.0'), ('sitemap',	'1'), ('visitCount', ''), ('speed',	'50'), ('baidu_tui_token', ''), ('spider', '1'), ('visitTime', ''), ('crawlNewsClass',	''), ('arcTimer', '[]'), ('autoNewsClass', '');
EOF;
		// End
		$sqls = str_replace('USING BTREE', '', $sqls);
		$sqls = preg_replace("/[\r\n]{1,}/", "\n", $sqls);
		$sql_arr = preg_split("#;[ \t]{0,}\n#", $sqls);
		foreach ($sql_arr as $sql) {
			$sql = trim($sql);
			if ($sql != '') {
				mysqli_query($conn, $sql);
			}
		}
		$rand = rand(1000, 9999);
		$password = md5($username . md5($password) . $rand);
		mysqli_query($conn, "insert into admin (username,password,salt) values ('$username','$password',$rand)");
		$myfile = fopen(ROOT . "/data/config.php", "w") or die("无法写入配置文件!");
		$txt = "<?php\n//数据库服务器连接地址\n\$dbhost = '{$dblink}';\n//数据库名\n\$dbname = '{$dbname}';\n//数据库用户名\n\$dbuser = '{$dbuser}';\n//数据库密码\n\$dbpassword = '{$dbpassword}';\n";
		fwrite($myfile, $txt);
		fclose($myfile);
		echo json_encode(array('success' => true));
	}

	public static function show()
	{
?>
		<!DOCTYPE html>
		<html>

		<head>
			<meta charset='utf-8' />
			<title>cmsSuper站群系统安装程序</title>
			<link rel="stylesheet" type="text/css" href="https://cdn.staticfile.org/element-ui/2.13.0/theme-chalk/index.css" />
			<script src="https://cdn.staticfile.org/vue/2.6.11/vue.min.js"></script>
			<script src="https://cdn.staticfile.org/element-ui/2.13.0/index.js"></script>
			<script src="https://cdn.staticfile.org/axios/0.19.0/axios.min.js"></script>
			<script src="https://cdn.staticfile.org/qs/6.9.1/qs.min.js"></script>
		</head>

		<body style="padding: 0; margin: 0;">
			<div id="mainApp" style="width: 950px; margin: 0 auto; font-size:14px;">
				<div style="background: #0092DD;display:flex;justify-content: space-between;line-height:48px;height:48px;padding:0 10px;">
					<div style="color: #0288cd;">
						<img src="/static/common/images/cmssuper.png" style="height:48px;" />
					</div>
					<div>
						<el-link class="el-icon-link" style="color: #FFF;padding-right:10px;" href="http://www.cmssuper.com" target="_blank">网站官网</el-link>
						<el-link class="el-icon-link" style="color: #FFF;padding-right:10px;" href="https://github.com/thinkincloud/cmssuper" target="_blank">Github</el-link>
					</div>
				</div>

				<div style="margin-top: 50px;display:flex;justify-content: space-between;">
					<div style="width:140px; height: 300px; font-size: 14px; text-align: right;color:#666; line-height:30px;">
						<el-steps direction="vertical" :active="step" finish-status="success">
							<el-step title="环境检测"></el-step>
							<el-step title="参数配置"></el-step>
							<el-step title="完成安装"></el-step>
						</el-steps>
					</div>
					<div style="width:780px;">
						<div v-if="step==0" style="color:#666; line-height: 30px;">
							<el-card>
								<div slot="header">
									<span>服务器信息</span>
								</div>
								<div>
									<div>服务器域名：{{G.sp_name}}</div>
									<div>服务器操作系统：{{G.sp_os}}</div>
									<div>服务器解译引擎：{{G.sp_server}}</div>
									<div>PHP版本：<span v-html="G.phpv"></span></div>
									<div>系统安装目录：{{G.sysdir}}</div>
								</div>
							</el-card>

							<el-card style="margin-top:10px;">
								<div slot="header">
									<span>环境检测</span>
								</div>
								<div>
									<div>curl扩展：<font color="green" v-if="G.curl_check" class="el-icon-success">已开启</font>
										<font v-else color="red" class="el-icon-error">未开启</font>
									</div>
									<div>mb_string扩展：<font color="green" v-if="G.mb_check" class="el-icon-success">已开启</font>
										<font v-else color="red" class="el-icon-error">未开启</font>
									</div>
									<div>mysqli扩展：<font color="green" v-if="G.mysqli_check" class="el-icon-success">已开启</font>
										<font v-else color="red" class="el-icon-error">未开启</font>
									</div>
								</div>
							</el-card>

							<el-card style="margin-top:10px;">
								<div slot="header">
									<span>目录权限检测</span>
								</div>
								<div>
									<div>系统要求必须满足下列所有的目录权限全部可读写的需求才能使用，其它应用目录可安装后在管理后台检测。</div>
									<table class="tab" cellpadding="0" cellspacing="0" width="500" border="1" bgcolor="#fafafa" style="border-collapse: collapse;">
										<tr>
											<th>目录名</th>
											<th>读取权限</th>
											<th>写入权限</th>
										</tr>
										<tr align="center">
											<td>/uploads</td>
											<td>
												<font class="el-icon-success" color="green"></font>
											</td>
											<td>
												<font v-if="G.upload==true" class="el-icon-success" color="green"></font>
												<font v-else color="red" class="el-icon-error"></font>
											</td>
										</tr>
										<tr align="center">
											<td>/data</td>
											<td>
												<font class="el-icon-success" color="green"></font>
											</td>
											<td>
												<font v-if="G.data==true" class="el-icon-success" color="green"></font>
												<font v-else color="red" class="el-icon-error"></font>
											</td>
										</tr>
									</table>
								</div>
							</el-card>

							<div style="padding-top:10px;">
								<el-button type="primary" @click="step=0">返回上一步</el-button>
								<span v-if="G.upload==false || G.data==false" style='color:red;'>没有写入权限，请修改权限后安装！</span>
								<span v-else-if="!G.phpv_check || !G.curl_check || !G.mb_check || !G.mysqli_check " style='color:red;'>环境不满足安装条件！</span>
								<el-button v-else type="primary" @click="step=1">下一步</el-button>
							</div>
						</div>
						<div v-if="step==1" style="color:#888; line-height: 30px;">
							<el-card>
								<div slot="header">
									<span>数据库配置信息</span>
								</div>
								<el-form label-width="120px" style="width:400px;">
									<el-form-item label="数据库主机">
										<el-input v-model="form.dblink" type="text" placeholder="一般为localhost或127.0.0.1" />
									</el-form-item>
									<el-form-item label="数据库用户">
										<el-input v-model="form.dbuser" type="text" />
									</el-form-item>
									<el-form-item label="数据库密码">
										<el-input v-model="form.dbpassword" type="text" />
									</el-form-item>
									<el-form-item label="数据库名称">
										<el-input v-model="form.dbname" type="text" />
									</el-form-item>
								</el-form>
							</el-card>

							<el-card style="margin-top:10px;">
								<div slot="header">
									<span>管理员初始密码</span>
								</div>
								<el-form label-width="120px" style="width:400px;">
									<el-form-item label="用户名">
										<el-input v-model="form.username" type="text" />
									</el-form-item>
									<el-form-item label="密 码">
										<el-input v-model="form.password" type="text" />
									</el-form-item>
								</el-form>
							</el-card>

							<div style="padding-top:10px;">
								<el-button type="primary" @click="step=0">返回上一步</el-button>
								<el-button type="primary" @click="checkTest">下一步</el-button>
							</div>
						</div>
						<div v-if="step==2" style="height: 150px; line-height:30px; padding:10px;">
							<div>恭喜您，网站安装成功！</div>
							<div>
								<el-button type="primary" @click="window.location.href='/admin';">立即登陆</el-button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<script>
				axios.defaults.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
				axios.defaults.headers['X-REQUESTED-WITH'] = 'xmlhttprequest';
				var mainApp = new Vue({
					el: '#mainApp',
					data: function() {
						return {
							step: 0,
							G: {},
							form: {
								dblink: "127.0.0.1",
								username: "admin",
								password: "123456"
							}
						}
					},
					created() {
						axios.get('?m=sysInit&action=ajax').then((res) => {
							this.G = res.data;
						});
					},
					methods: {
						checkTest() {
							axios.post('?m=sysInit&action=dbcheck', Qs.stringify(this.form)).then((res) => {
								if (res.data.error) {
									this.$message.error(res.data.error);
									return;
								}
								if (res.data.dbexist) {
									this.$confirm(res.data.success, '提示', {
										confirmButtonText: '确定',
										cancelButtonText: '取消',
										type: 'warning'
									}).then(() => {
										this.sysInit();
									})
								} else {
									this.$message.success(res.data.success);
									setTimeout(() => {
										this.sysInit();
									}, 1000);
								}
							});
						},
						sysInit() {
							axios.post('?m=sysInit&action=dbinit', Qs.stringify(this.form)).then((res) => {
								if (!res.data.success) {
									this.$message.error(res.data.error);
								} else {
									this.step = 2;
								}
							});
						}
					}
				});
			</script>
		</body>

		</html>
<?php
	}
}

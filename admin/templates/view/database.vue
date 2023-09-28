<template>
	<div class="cp-wrap">
		<div class="crumb" style="border-bottom: none;">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/database">数据库管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<el-tabs type="border-card" v-model="tabName">
			<el-tab-pane label="数据库备份" name="backup">
				<template>
					<el-table show-summary :summary-method="getSummaries" :data="tables" style="width: 100%">
						<el-table-column prop="Name" label="表" width="180"></el-table-column>
						<el-table-column prop="Engine" label="引擎" width="180"></el-table-column>
						<el-table-column prop="Data_length" label="数据长度" :formatter="sizeFormatter"></el-table-column>
						<el-table-column prop="Index_length" label="索引长度" :formatter="sizeFormatter"></el-table-column>
						<el-table-column prop="Data_free" label="数据空闲" :formatter="sizeFormatter"></el-table-column>
						<el-table-column prop="Auto_increment" label="自动增量"></el-table-column>
						<el-table-column prop="Rows" label="行数"></el-table-column>
						<el-table-column label="操作" width="120">
							<template slot-scope="scope">
								<div class="list-actions">
									<span v-if="scope.row.Data_free > 0"><el-link type="primary" @click="optimizeTable(scope.row.Name)">优化</el-link></span>
									<span><el-link type="primary" @click="repairTable(scope.row.Name)">修复</el-link></span>
								</div>
							</template>
						</el-table-column>
					</el-table>
					<div class="cp-table-top">
						<el-button size="medium" type="primary" @click="createBackup">创建备份</el-button>
					</div>
				</template>
			</el-tab-pane>
			<el-tab-pane label="数据库还原" name="restore">
				<el-card shadow="hover" style="color: #ff6600;">
					<p>1、与程序版本不一致的数据无法还原，请升级前备份好程序，升级后备份好数据库</p>
					<p>2、数据库备份保存目录为 /data/backup，请您及时下载到本地电脑保存，然后删除服务器上备份文件</p>
					<p>3、文章正文数据未包含在数据库中，您还需要另行备份<b>/data/content</b>文件夹中的文件</p>
					<p>4、为了确保安全，请不要将各种压缩包文件放置在网站目录中，包括但不限于整站数据、数据库备份等</p>
				</el-card>
				<el-table :data="backupDataList" style="width: 100%">
					<el-table-column
						prop="time"
						label="备份时间"
						width="180"
						:formatter="
							(row) => {
								return this.formatTime(row.time);
							}
						"
					></el-table-column>
					<el-table-column prop="version" width="180" label="程序版本"></el-table-column>
					<el-table-column prop="size" label="备份大小" :formatter="sizeFormatter"></el-table-column>
					<el-table-column prop="status" label="状态">
						<template slot-scope="scope">
							<i v-if="scope.row.status" class="el-icon-circle-check" style="color: #67c23a;">已完成</i>
							<i v-if="!scope.row.status" class="el-icon-circle-close" style="color: #f56c6c;">未完成</i>
						</template>
					</el-table-column>
					<el-table-column label="操作" width="160">
						<template slot-scope="scope">
							<el-button v-if="scope.row.status" :disabled="scope.row.version!=common.conf.softversion" slot="reference" type="warning" size="mini" plain @click="restore(scope.row.name)">还原</el-button>
							<el-button slot="reference" type="danger" size="mini" plain @click="backupDataDel(scope.row.name)">删除</el-button>
						</template>
					</el-table-column>
				</el-table>
			</el-tab-pane>
			<el-tab-pane label="SQL命令行工具" name="command">
				<h3 style="padding: 10px 0;">SQL命令执行工具</h3>
				<codeEditor v-if="tabName=='command'" class="sqlEditor" v-model="sqlString" style="width: 100%;height:300px;border:solid 1px #cccccc;" fileType="sql"></codeEditor>
				<div style="padding: 10px 0;">
					<el-button type="primary" @click="runCommand">执行命令</el-button>
				</div>
			</el-tab-pane>
		</el-tabs>
		<el-dialog title="进度" :visible.sync="progressDialogVisible" :close-on-click-modal="false">
			<el-progress :text-inside="true" :stroke-width="22" :percentage="percentage" :status="progressStatus"></el-progress>
			<p>{{ message }}</p>
		</el-dialog>

		<el-dialog title="执行结果" width="80%" :visible.sync="commandDialogVisible" :close-on-click-modal="false">
			<div v-for="(item, index) in execResult" style="margin-bottom: 5px;" :key="index">
				<el-alert :title="item.success ? '执行成功' : '执行失败'" :description="item.sql" :type="item.success ? 'success' : 'warning'" :closable="false"></el-alert>
				<el-table v-if="item.data" :data="item.data" style="width: 100%">
					<el-table-column v-for="(val, field) in item.data[0]" :prop="field" :label="field" :key="field"></el-table-column>
				</el-table>
			</div>
		</el-dialog>
	</div>
</template>
<script>
import mixin from "./mixin";
import codeEditor from "./components/codeEditor.vue";
export default {
	mixins: [mixin],
	components: {
		codeEditor,
	},
	data() {
		return {
			tables: [],
			progressDialogVisible: false,
			message: "备份即将开始",
			param: {},
			percentage: 0,
			progressStatus: "warning",
			backupDataList: [],
			execResult: [],
			commandDialogVisible: false,
			sqlString: "",
		};
	},
	computed: {
		tabName: {
			get: function() {
				return this.$route.query.tab ? this.$route.query.tab : "backup";
			},
			set: function(newValue) {
				this.routerUpdate({ tab: newValue });
			},
		},
	},
	methods: {
		pageInit() {
			if (this.tabName == "backup") {
				return new Promise((resolve) => {
					this.$axios.post("?m=database").then((res) => {
						this.tables = res.data.success;
						resolve();
					});
				});
			} else if (this.tabName == "restore") {
				this.loadBKList();
			}
		},
		sizeFormatter(row, column, cellValue) {
			return this.formatSize(cellValue);
		},
		getSummaries(param) {
			const { columns, data } = param;
			const sums = [];
			columns.forEach((column, index) => {
				if (index === 0) {
					sums[index] = "总计";
					return;
				}
				if (index === 1 || index === 5 || index === 6 || index === 7) {
					return;
				}
				const values = data.map((item) => Number(item[column.property]));
				if (!values.every((value) => isNaN(value))) {
					sums[index] = values.reduce((prev, curr) => {
						const value = Number(curr);
						if (!isNaN(value)) {
							return prev + curr;
						} else {
							return prev;
						}
					}, 0);
					sums[index] = this.formatSize(sums[index]);
				} else {
					sums[index] = "N/A";
				}
			});
			return sums;
		},
		optimizeTable(table) {
			this.$axios.post("?m=database&a=optimize", { table: table }).then((res) => {
				if (res.data.success) {
					this.pageInit();
				}
			});
		},
		repairTable(table) {
			this.$axios.post("?m=database&a=repair", { table: table }).then((res) => {
				if (res.data.success) {
					this.pageInit();
				}
			});
		},
		createBackup() {
			this.message = "备份即将开始";
			this.param = {};
			this.percentage = 0;
			this.progressStatus = "warning";
			this.progressDialogVisible = true;
			this.runBackup();
		},
		runBackup() {
			this.$axios.post("?m=database&a=backup", { param: this.param }).then((res) => {
				if (res.data.success) {
					this.message = res.data.success;
					this.progressStatus = "success";
					this.percentage = 100;
					setTimeout(() => {
						this.progressDialogVisible = false;
						this.tabName = "restore";
					}, 1000);
				} else {
					this.param = res.data.param;
					this.message = res.data.continue;
					this.percentage = parseInt((res.data.param.sumRow / res.data.param.totalRow) * 100);
					if (this.progressDialogVisible) {
						this.runBackup();
					}
				}
			});
		},
		restore(name) {
			this.$confirm("您确定要将此次备份还原到数据库吗？", "提示", {
				confirmButtonText: "确定",
				cancelButtonText: "取消",
				type: "warning",
			}).then(() => {
				this.message = "即将开始数据还原";
				this.percentage = 0;
				this.progressStatus = "success";
				this.progressDialogVisible = true;
				this.runRestore(name);
			});
		},
		runRestore(name) {
			this.$axios.post("?m=database&a=restore", { name: name, siteId: null }).then((res) => {
				this.percentage = res.data.percentage;
				if (res.data.success) {
					this.message = res.data.success;
					this.loadCommon(true);
					setTimeout(() => {
						this.progressDialogVisible = false;
					}, 100);
				} else if (res.data.continue) {
					this.message = res.data.continue;
					if (this.progressDialogVisible) {
						setTimeout(() => {
							this.runRestore(name);
						}, 100);
					}
				}
			});
		},
		loadBKList() {
			this.$axios.get("?m=database&a=backupDataList").then((res) => {
				this.backupDataList = res.data.success;
			});
		},
		backupDataDel(name) {
			this.$confirm("您确定要删除此次备份数据吗？", "提示", {
				confirmButtonText: "确定",
				cancelButtonText: "取消",
				type: "warning",
			}).then(() => {
				this.$axios
					.get("?m=database&a=backupDataDel", {
						params: {
							name: name,
						},
					})
					.then(() => {
						this.loadBKList();
					});
			});
		},
		runCommand() {
			this.$axios.post("?m=database&a=command", { command: this.sqlString }).then((res) => {
				this.commandDialogVisible = true;
				this.execResult = res.data;
			});
		},
	},
};
</script>

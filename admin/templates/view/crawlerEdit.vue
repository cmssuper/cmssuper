<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/crawler">采集管理</el-breadcrumb-item>
				<el-breadcrumb-item>规则编辑</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" :rules="rules" label-position="top" size="mini" label-width="80px">
					<el-form-item class="skeleton" label="规则类型" prop="yuming_id">
						<el-radio-group v-model="form.yuming_id" size="small">
							<el-radio :label="siteId" border>单站规则</el-radio>
							<el-radio label="0" border>全局规则</el-radio>
						</el-radio-group>
					</el-form-item>
					<el-form-item class="skeleton" label="指定到栏目" v-if="form.yuming_id > 0">
						<el-select v-model="form.class" clearable placeholder="请选择">
							<el-option v-for="item in classlist" :key="item.ename" :label="item.title" :value="item.ename"> </el-option>
						</el-select>
					</el-form-item>
					<el-form-item class="skeleton" label="列表网址生成">
						<el-alert type="info" :closable="false">
							要采集的栏目列表，通配符[开始数-结束数]，如：http://example.com/list_[1-10].html
						</el-alert>
						<el-input v-model="form.listurl" size="medium" type="textarea" :autosize="{ minRows: 4 }" placeholder="如：http://example.com/list_[1-10].html"> </el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="文章网址区域">
						<el-row :gutter="20">
							<el-col :span="16">
								<div class="grid-content">
									<el-alert type="info" :closable="false">
										在列表页面找出要采集的文章网址区域，"文章网址前html片段[内容]文章网址后html片段"，"[内容]"为固定占位符
									</el-alert>
									<el-input v-model="form.articlerule" size="medium" type="textarea" :autosize="{ minRows: 4 }" placeholder="文章网址前html片段[内容]文章网址后html片段"> </el-input>
								</div>
							</el-col>
							<el-col :span="8">
								<div class="grid-content">
									<el-alert type="info" :closable="false">
										网址过滤
									</el-alert>
									<el-input v-model="form.norule" size="medium" type="textarea" :autosize="{ minRows: 4 }" placeholder="多个规则用换行隔开"> </el-input>
								</div>
							</el-col>
						</el-row>
					</el-form-item>
					<el-form-item class="skeleton" label="标题规则">
						<el-alert type="info" :closable="false">
							在正文页面提取标题，"标题前html片段[内容]标题后html片段"，"[内容]"为固定占位符
						</el-alert>
						<el-input v-model="form.titlerule" size="medium" type="textarea" :autosize="{ minRows: 4 }" placeholder="标题前html片段[内容]标题后html片段"> </el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="正文规则">
						<el-alert type="info" :closable="false">
							在正文页面提取文章内容，"正文前html片段[内容]正文后html片段"，"[内容]"为固定占位符
						</el-alert>
						<el-input v-model="form.contentrule" size="medium" type="textarea" :autosize="{ minRows: 4 }" placeholder="正文前html片段[内容]正文后html片段"> </el-input>
					</el-form-item>

					<el-form-item class="skeleton">
						<el-button type="primary" size="medium" @click="submitForm()">保存</el-button>
						<el-button type="primary" size="medium" @click="testRule()">测试</el-button>
					</el-form-item>
				</el-form>
			</div>
		</div>
		<el-dialog id="testRuleDialog" title="测试规则" width="80%" :visible.sync="testRuleVisible" :destroy-on-close="true">
			<div v-html="testRuleResult"></div>
		</el-dialog>
	</div>
</template>
<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			form: {},
			classlist: {},
			rules: {
				yuming_id: [{ required: true, message: "请选择规则类型" }],
			},
			testRuleVisible: false,
			testRuleResult: "",
		};
	},
	methods: {
		pageInit() {
			this.$axios.get("?m=crawler&a=get_classlist").then((res) => {
				if (res.data.success) {
					this.classlist = res.data.success;
				}
			});
			if (this.$route.query.id) {
				return new Promise((resolve) => {
					this.$axios.get("?m=crawler&a=crawlRuleEdit&id=" + this.$route.query.id).then((res) => {
						if (res.data.success) {
							this.form = res.data.success;
							resolve();
						}
					});
				});
			}
		},
		submitForm() {
			this.$refs["form"].validate((valid) => {
				if (valid) {
					this.$axios.post("?m=crawler&a=crawlRuleEdit", this.form);
				}
			});
		},
		testRule(testIdx) {
			this.testRuleResult = "";
			this.testRuleVisible = true;
			const loading = this.$loading({
				target: "#testRuleDialog .el-dialog",
				lock: true,
				text: "Loading",
				spinner: "el-icon-loading",
			});
			this.form.testIdx = testIdx ? testIdx : 0;
			this.$axios.post("?m=crawltest", this.form).then((res) => {
				loading.close();
				this.testRuleResult = res.data;
			});
		},
	},
};
</script>

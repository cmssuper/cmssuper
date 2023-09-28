<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/flink">友情链接</el-breadcrumb-item>
				<el-breadcrumb-item>链接编辑</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" :rules="rules" label-position="top" label-width="80px">
					<el-form-item class="skeleton" label="作用范围" prop="yuming_id">
						<el-radio v-model="form.yuming_id" :label="siteId">当前站点</el-radio>
						<el-radio v-model="form.yuming_id" label="0">所有站点</el-radio>
					</el-form-item>
					<el-form-item class="skeleton" label="网站名称" prop="sitename">
						<el-input v-model="form.sitename" style="width: 300px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="网站地址" prop="url">
						<el-input v-model="form.url" style="width: 400px;" placeholder="http://"></el-input>
						<span class="tip">请输入http://开头的网址</span>
					</el-form-item>
					<el-form-item class="skeleton" label="状态">
						<el-switch v-model="form.status" active-value="1" inactive-value="0"></el-switch>
					</el-form-item>
					<el-form-item class="skeleton" label="备注">
						<el-input v-model="form.note" type="textarea" :autosize="{ minRows: 2, maxRows: 4 }" style="width: 400px;"></el-input>
					</el-form-item>
					<el-form-item>
						<el-button class="skeleton" type="primary" size="medium" @click="submitForm(true)">保存</el-button>
					</el-form-item>
				</el-form>
			</div>
		</div>
	</div>
</template>
<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			form: {},
			rules: {
				yuming_id: [{ required: true, message: "请选择左右范围", trigger: "change" }],
				sitename: [{ required: true, message: "请填写网站名称", trigger: "change" }],
				url: [{ required: true, message: "请填写网站地址", trigger: "change" }],
			},
		};
	},
	methods: {
		pageInit() {
			if (this.$route.query.id) {
				return new Promise((resolve) => {
					this.$axios.get("?m=flink&a=edit&id=" + this.$route.query.id).then((res) => {
						if (res.data.success) {
							this.form = res.data.success;
							resolve();
						}
					});
				});
			}
		},
		submitForm() {
			this.$axios.post("?m=flink&a=edit", this.form);
		},
	},
};
</script>

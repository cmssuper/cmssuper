<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/classlist">栏目管理</el-breadcrumb-item>
				<el-breadcrumb-item>编辑栏目</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" :rules="rules" label-position="top" size="mini" label-width="80px">
					<el-form-item class="skeleton" label="栏目名称" prop="title">
						<el-input v-model="form.title" style="width: 300px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="栏目目录" prop="ename">
						<el-input v-model="form.ename" placeHolder="栏目目录由字母和数字构成，且必须字母开头" style="width: 300px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton">
						<el-button type="primary" :loading="axiosLoading" size="medium" @click="submitForm(true)">保存</el-button>
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
				title: [{ required: true, message: "请输入栏目名称", trigger: "change" }],
				ename: [{ required: true, message: "栏目目录由字母和数字构成，且必须字母开头", trigger: "change" }],
			},
		};
	},
	methods: {
		pageInit() {
			if (this.$route.query.id) {
				return new Promise((resolve) => {
					this.$axios.get("?m=classlist&a=edit&id=" + this.$route.query.id).then((res) => {
						if (res.data.success) {
							this.form = res.data.success;
							resolve();
						}
					});
				});
			}
		},
		submitForm() {
			this.$axios.post("?m=classlist&a=edit", this.form);
		},
	},
};
</script>

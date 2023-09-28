<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/seo/reword">词语替换</el-breadcrumb-item>
				<el-breadcrumb-item>词语添加</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" label-position="top" size="mini" label-width="80px">
					<el-form-item label="作用范围" prop="yuming_id">
						<el-radio v-model="form.yuming_id" label="0">所有站点</el-radio>
						<el-radio v-model="form.yuming_id" :label="siteId">当前站点</el-radio>
					</el-form-item>
					<el-form-item label="替换词语">
						<div>
							<el-input v-model="form.oldword" placeHolder="替换词" style="width: 300px;"></el-input>
						</div>
						<div style="width: 300px;text-align: center; line-height: 50px;">
							<el-button type="primary" round :plain="form.type == 1" class="el-icon-sort" @click="typeChange(2)"></el-button>
							<el-button type="primary" round :plain="form.type == 2" class="el-icon-bottom" @click="typeChange(1)"></el-button>
						</div>
						<div>
							<el-input v-model="form.newword" placeHolder="替换词" style="width: 300px;"></el-input>
						</div>
					</el-form-item>
					<el-form-item>
						<el-button type="primary" size="medium" @click="submitForm(true)">保存</el-button>
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
			form: {
				yuming_id: "0",
				type: 2,
			},
		};
	},
	methods: {
		typeChange(e) {
			this.$set(this.form, "type", e);
		},
		submitForm() {
			this.$axios.post("?m=seo&a=reword_add", this.form);
		},
	},
};
</script>

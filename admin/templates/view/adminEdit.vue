<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator-class="el-icon-arrow-right">
				<el-breadcrumb-item to="/admin">管理员列表</el-breadcrumb-item>
				<el-breadcrumb-item>
					<div v-if="form.id">修改管理员</div>
					<div v-else>添加管理员</div>
				</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" label-position="top" size="mini" label-width="80px">
					<el-form-item class="skeleton" label="用户名">
						<el-input :disabled="!!form.id" v-model="form.username" style="width: 200px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="登陆密码(不修改可留空)">
						<el-input v-model="form.password" style="width: 200px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="站点权限【测试功能，尚未开放】">
						<el-transfer :titles="['站点列表', '已授权']" filterable v-model="form.siteids" :filter-method="filterMethod" filter-placeholder="站点搜索" :data="sites"> </el-transfer>
					</el-form-item>
					<el-form-item class="skeleton" label="允许登陆">
						<el-switch :disabled="form.id == 1 || adminId != 1" v-model="form.status" active-value="1" inactive-value="0"> </el-switch>
					</el-form-item>
					<el-form-item class="skeleton">
						<el-button type="primary" size="medium" @click="submitForm()">保存</el-button>
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
		};
	},
	computed: {
		adminId: function() {
			return this.common.user ? this.common.user.id : 0;
		},
		sites: function() {
			let tmp = [];
			for (var key in this.common.sites) {
				tmp.push({
					key: this.common.sites[key].id,
					label: this.common.sites[key].name,
					name: this.common.sites[key].sitename,
					disabled: this.$route.query.id == 1 || this.adminId != 1,
				});
			}
			return tmp;
		},
	},
	methods: {
		pageInit() {
			if (this.$route.query.id) {
				return new Promise((resolve) => {
					this.$axios.get("?m=admin&a=edit&id=" + this.$route.query.id).then((res) => {
						this.form = res.data.success;
						resolve();
					});
				});
			}
		},
		filterMethod(query, item) {
			var str = item.label + item.name;
			return str.indexOf(query) > -1;
		},
		submitForm() {
			this.$axios.post("?m=admin&a=edit", this.form);
		},
	},
};
</script>

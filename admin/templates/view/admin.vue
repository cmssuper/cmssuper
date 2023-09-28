<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator-class="el-icon-arrow-right">
				<el-breadcrumb-item>管理员列表</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top">
			<el-button size="medium" type="primary" plain @click="routerTo('/admin/edit')">添加管理员 </el-button>
		</div>
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%">
				<el-table-column prop="username" label="账号"></el-table-column>
				<el-table-column prop="siteNum" label="权限">
					<template slot-scope="scope">
						<div v-if="scope.row.id == 1">超级管理员</div>
						<div v-else>
							<span>管理</span>
							<b>{{ scope.row.siteNum }}</b>
							<span>站点</span>
						</div>
					</template>
				</el-table-column>
				<el-table-column prop="status" label="状态">
					<template slot-scope="scope">
						<el-tag v-if="scope.row.status == 1" type="success" size="small">允许登陆</el-tag>
						<el-tag v-if="scope.row.status == 0" type="info" size="small">禁止登陆</el-tag>
					</template>
				</el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope" v-if="common.user && common.user.id == 1">
						<div class="list-actions">
							<span><el-link type="primary" @click="edit(scope.row.id)">修改</el-link></span>
							<el-popconfirm v-if="scope.row.id != 1" title="确定删除吗？" @confirm="ajaxGet('?m=admin&a=del&id=' + scope.row.id)"><el-link slot="reference" type="danger">删除</el-link></el-popconfirm>
						</div>
					</template>
				</el-table-column>
			</el-table>
		</div>
		<div class="cp-pages">
			<el-pagination
				:current-page="page"
				:page-size="pagesize"
				@current-change="
					(page) => {
						routerUpdate({ page: page });
					}
				"
				:hide-on-single-page="true"
				background
				layout="prev, pager, next"
				:total="total"
			>
			</el-pagination>
		</div>
	</div>
</template>
<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			pagesize: 20,
			total: 0,
			listData: [],
		};
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.$axios.post("?m=admin", { pagesize: this.pagesize, page: this.page }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
						resolve();
					}
				});
			});
		},
		edit(id) {
			this.$router.push({ path: "/admin/edit", query: { id: id } });
		},
	},
};
</script>

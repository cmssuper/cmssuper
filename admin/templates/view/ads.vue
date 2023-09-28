<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator-class="el-icon-arrow-right">
				<el-breadcrumb-item>广告管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top">
			<el-button size="medium" type="primary" plain @click="routerTo('/ads/edit')">添加广告位 </el-button>
		</div>
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%">
				<el-table-column prop="id" label="ID" width="100"> </el-table-column>
				<el-table-column prop="title" label="广告位名称"></el-table-column>
				<el-table-column prop="abc" label="调用代码">
					<template slot-scope="scope">
						<input :value="'&lt;!-- tag::ad(\'' + scope.row.abc + '\') --&gt;'" style="border:0px;padding:8px;background:#f1f3f6;width:90%;max-width:250px;" onfocus="this.select();" />
					</template>
				</el-table-column>
				<el-table-column prop="status" label="状态">
					<template slot-scope="scope">
						<el-tag v-if="scope.row.status == 1" type="success" size="small">已开启</el-tag>
						<el-tag v-if="scope.row.status == 0" type="info" size="small">已关闭</el-tag>
					</template>
				</el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<div class="list-actions">
							<span><el-link type="primary" @click="routerTo('/ads/edit?id=' + scope.row.id)">修改</el-link></span>
							<el-popconfirm title="确定删除吗？" @confirm="ajaxGet('?m=ads&a=del&id=' + scope.row.id)"><el-link slot="reference" type="danger">删除</el-link></el-popconfirm>
						</div>
					</template>
				</el-table-column>
			</el-table>
		</div>
		<div class="cp-pages skeleton">
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
				this.$axios.post("?m=ads", { pagesize: this.pagesize, page: this.page }).then((res) => {
					this.listData = res.data.success.list;
					this.total = parseInt(res.data.success.total);
					resolve();
				});
			});
		},
	},
};
</script>

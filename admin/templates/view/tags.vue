<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/tags">标签管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top cl">
			<div class="fr skeleton">
				<form method="POST" @submit="routerUpdate({ page: null, kw: kw })" onsubmit="return false;">
					<el-input clearable name="kw" size="mini" placeholder="搜索关键词" v-model="kw" @clear="routerUpdate({ page: null, kw: null })">
						<el-button native-type="submit" slot="append" icon="el-icon-search"></el-button>
					</el-input>
				</form>
			</div>
		</div>
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%">
				<el-table-column prop="tagsname" width="150" label="标签"></el-table-column>
				<el-table-column prop="num" label="被引用数"></el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<el-popconfirm title="确定删除吗？" @confirm="ajaxGet('?m=tags&a=del&id=' + scope.row.id)"><el-link slot="reference" type="danger">删除</el-link></el-popconfirm>
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
			kw: "",
			pagesize: 20,
			total: 0,
			listData: [],
		};
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.kw = this.$route.query.kw;
				this.$axios.post("?m=tags", { pagesize: this.pagesize, page: this.page, kw: this.kw }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
						resolve();
					}
				});
			});
		},
	},
};
</script>

<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/flink">友情链接</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<!--搜索框-->
		<div class="cp-table-top cl">
			<div class="fl">
				<el-button size="medium" type="primary" plain @click="routerTo('/flink/edit')">添加链接</el-button>
			</div>
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
				<el-table-column prop="id" width="50" label="ID"></el-table-column>
				<el-table-column prop="sitename" label="网站名称">
					<template slot-scope="scope">
						<el-tooltip v-if="scope.row.note != ''" :content="scope.row.note" placement="top">
							<span class="el-icon-info"> {{ scope.row.sitename }}</span>
						</el-tooltip>
						<span v-else>{{ scope.row.sitename }}</span>
					</template>
				</el-table-column>
				<el-table-column prop="url" label="网站地址">
					<template slot-scope="scope">
						{{ scope.row.url }}
					</template>
				</el-table-column>
				<el-table-column prop="yuming_id" label="作用范围">
					<template slot-scope="scope">
						<span v-if="scope.row.yuming_id > 0">当前站点</span>
						<span v-else style="color:#ccc">所有站点</span>
					</template>
				</el-table-column>
				<el-table-column prop="status" label="status">
					<template slot-scope="scope">
						<el-tag v-if="scope.row.status == 1" type="success" size="small">启用</el-tag>
						<el-tag v-if="scope.row.status == 0" type="info" size="small">禁用</el-tag>
					</template>
				</el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<div class="list-actions">
							<span><el-link type="primary" @click="routerTo('/flink/edit?id=' + scope.row.id)">修改</el-link></span>
							<el-popconfirm title="确定要删除吗？" @confirm="ajaxGet('?m=flink&a=del&id=' + scope.row.id)">
								<el-link slot="reference" type="danger">删除</el-link>
							</el-popconfirm>
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
				this.$axios.post("?m=flink", { pagesize: this.pagesize, page: this.page, kw: this.kw }).then((res) => {
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

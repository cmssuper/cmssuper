<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/sites">网站管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top cl">
			<div class="fl">
				<el-button size="medium" type="primary" plain @click="routerTo('/sites/edit')">添加站点</el-button>
			</div>
			<div class="fr skeleton">
				<form method="POST" @submit="routerUpdate({ page: null, kw: kw })" onsubmit="return false;">
					<el-input clearable name="kw" size="mini" placeholder="搜索关键词" v-model="kw" @clear="routerUpdate({ page: null, kw: null })">
						<el-button native-type="submit" slot="append" icon="el-icon-search"></el-button>
					</el-input>
				</form>
			</div>
		</div>
		<!-- demoTip -->
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%">
				<el-table-column prop="id" width="50" label="ID"></el-table-column>
				<el-table-column prop="sitename" label="网站名称"></el-table-column>
				<el-table-column prop="name" label="域名">
					<template slot-scope="scope">
						<a class="el-icon-link" :href="'http://' + scope.row.name" target="_blank"> {{ scope.row.name }} </a>
					</template>
				</el-table-column>
				<el-table-column prop="mobileSwitch" label="手机版">
					<template slot-scope="scope">
						<el-tooltip :content="mobileSwitchTip(scope.row)" placement="top">
							<el-switch v-model="scope.row.mobileSwitch" inactive-value="0" active-value="1" @change="mobileSwitch(scope.row, scope.$index)"></el-switch>
						</el-tooltip>
					</template>
				</el-table-column>
				<el-table-column prop="template" label="网站风格"></el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<div class="list-actions">
							<span><el-link type="primary" @click="routerTo('/sites/edit?id=' + scope.row.id)">修改</el-link></span>
							<el-popconfirm title="确定删除吗？" @confirm="siteDel(scope.row.id)"><el-link slot="reference" type="danger">删除</el-link></el-popconfirm>
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
				this.$axios.post("?m=sites", { pagesize: this.pagesize, page: this.page, kw: this.kw }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
						resolve();
					}
				});
			});
		},
		mobileSwitch(row) {
			this.$axios.post("?m=sites&a=mobileSwitch", row);
		},
		mobileSwitchTip: function(row) {
			var wapName = "m." + row.name.replace(/^www\./g, "");
			return "开启后需将域名 " + wapName + " 解析到当前服务器IP";
		},
		siteDel(id) {
			this.$axios.get("?m=sites&a=del&id=" + id).then(async () => {
				await this.loadCommon(true);
				this.pageInit();
			});
		},
	},
};
</script>

<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item v-if="common.sites && common.sites[siteId]" to="/home">
					<span class="siteTag el-icon-s-home">
						{{ common.sites[siteId].sitename }}
					</span>
				</el-breadcrumb-item>
				<el-breadcrumb-item>文章管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top cl">
			<div class="fl">
				<el-dropdown split-button size="medium" type="primary" plain @click="articleAdd('single')" @command="articleAdd">
					发布文章
					<el-dropdown-menu slot="dropdown">
						<el-dropdown-item command="single">单站点发布</el-dropdown-item>
						<el-dropdown-item command="multi">多站点发布</el-dropdown-item>
					</el-dropdown-menu>
				</el-dropdown>
				<el-button size="mini" type="danger" plain v-if="showDelete" @click="deleteArticles">删除选中</el-button>
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
			<el-table :data="listData" style="width: 100%" @selection-change="handleSelectionChange">
				<el-table-column type="selection" width="45"></el-table-column>
				<el-table-column prop="id" label="ID" width="70"></el-table-column>
				<el-table-column min-width="300" label="标题" :show-overflow-tooltip="true">
					<template slot-scope="scope">
						<a :href="scope.row.url" target="_blank">
							<font v-if="scope.row.thumb != ''" color="#409EFF" class="el-icon-picture"></font>
							<span>{{ scope.row.title }}</span>
							<font v-if="scope.row.flag == 'h'" color="red" size="-2">[头条]</font>
							<font v-if="scope.row.flag == 'c'" color="red" size="-2">[推荐]</font>
							<font v-if="scope.row.flag == 'f'" color="red" size="-2">[幻灯片]</font>
							<font v-if="scope.row.flag == 'a'" color="red" size="-2">[特荐]</font>
							<font v-if="scope.row.flag == 's'" color="red" size="-2">[滚动]</font>
							<font v-if="scope.row.flag == 'b'" color="red" size="-2">[加粗]</font>
						</a>
					</template>
				</el-table-column>
				<el-table-column prop="classname" label="栏目"></el-table-column>
				<el-table-column
					prop="addtime"
					:formatter="
						(row, column, cellValue) => {
							return formatTime(cellValue);
						}
					"
					label="时间"
					width="150"
				></el-table-column>
				<el-table-column prop="status" label="状态">
					<template slot-scope="scope">
						<el-tag v-if="scope.row.status == 0" type="info" size="small">待审核</el-tag>
						<el-tag v-if="scope.row.status == 1" type="success" size="small">已发表</el-tag>
						<el-tag v-if="scope.row.status == 2" type="primary" size="small">定时发布</el-tag>
					</template>
				</el-table-column>
				<el-table-column v-if="this.common.conf && this.common.conf.baidu_tui_token != ''" label="推送" :formatter="pingStatus" prop="ping_status"></el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<div class="list-actions">
							<span>
								<el-link type="primary" @click="edit(scope.row.id)">修改</el-link>
							</span>
							<el-popconfirm title="确定要删除吗？" @confirm="del(scope.row.id)">
								<el-link type="danger" slot="reference">删除</el-link>
							</el-popconfirm>
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
			kw: "",
			pagesize: 20,
			total: 0,
			listData: [],
			showDelete: false,
			selectIds: [],
		};
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.kw = this.$route.query.kw;
				this.$axios.post("?m=article", { pagesize: this.pagesize, page: this.page, kw: this.kw }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
						resolve();
					}
				});
			});
		},
		handleSelectionChange(val) {
			this.selectIds = [];
			for (var index in val) {
				this.selectIds.push(val[index].id);
			}
			this.showDelete = this.selectIds.length > 0;
		},
		deleteArticles() {
			this.$axios.post("?m=article&a=del", { id: this.selectIds }).then((res) => {
				if (res.data.success) {
					this.pageInit();
				}
			});
		},
		articleAdd(type) {
			this.routerTo("/article/edit?type=" + type);
		},
		edit(id) {
			this.routerTo("/article/edit?id=" + id);
		},
		del(id) {
			this.selectIds = [id];
			this.deleteArticles();
		},
		pingStatus: function(row, column, cellValue) {
			var ps = {
				"0": "待推送",
				"1": "已推送",
			};
			var pe = {
				"100": "未授权",
				"101": "百度接口异常",
				"102": "token错误",
				"103": "not_same_site",
				"104": "not_valid",
			};
			if (ps[cellValue]) {
				return ps[cellValue];
			} else if (pe[row.ping_errMsg]) {
				return pe[row.ping_errMsg];
			} else {
				return "Status:" + cellValue + " errMsg:" + row.ping_errMsg;
			}
		},
	},
};
</script>

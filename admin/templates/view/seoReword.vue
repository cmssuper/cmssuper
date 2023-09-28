<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/seo/reword">词语替换</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top cl">
			<div>
				<el-dropdown split-button size="medium" type="primary" plain @click="routerTo('/seo/reword/add')" @command="rewordAdd">
					添加词语
					<el-dropdown-menu slot="dropdown">
						<el-dropdown-item command="single">单个添加</el-dropdown-item>
						<el-dropdown-item command="multi">批量上传</el-dropdown-item>
					</el-dropdown-menu>
				</el-dropdown>
			</div>
			<div class="fr">
				<el-popconfirm v-if="listData.length > 0" title="确定要清空所有吗？" @confirm="ajaxGet('?m=seo&a=reword_delall')">
					<el-button slot="reference" type="info" size="mini">清空所有</el-button>
				</el-popconfirm>
			</div>
		</div>
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%;">
				<el-table-column prop="oldword" label="替换词"></el-table-column>
				<el-table-column prop="type" label="替换方向">
					<template slot-scope="scope">
						<el-tag v-if="scope.row.type == 1" type="success" size="small" class="el-icon-right"></el-tag>
						<el-tag v-if="scope.row.type == 2" type="info" size="small"><span class="el-icon-sort" style="transform: rotate(90deg);"></span></el-tag>
					</template>
				</el-table-column>
				<el-table-column prop="newword" label="替换词"></el-table-column>
				<el-table-column prop="yuming_id" label="作用范围">
					<template slot-scope="scope">
						<span v-if="scope.row.yuming_id > 0">当前站点</span>
						<span v-else style="color: #ccc;">所有站点</span>
					</template>
				</el-table-column>
				<el-table-column label="操作" width="120">
					<template slot-scope="scope">
						<el-popconfirm title="确定要删除吗？" @confirm="ajaxGet('?m=seo&a=reword_del&id=' + scope.row.id)">
							<el-link slot="reference" type="danger">删除</el-link>
						</el-popconfirm>
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
		<el-dialog title="上传词语" :visible.sync="uploadDialogVisible" width="600px">
			<p>1、请上传txt后缀<b>UTF-8编码</b>文件</p>
			<p>2、替换词必须包含中文，英文字母或者域名可能导致采集异常</p>
			<p>3、每行一条替换规则，中间用“,”或“->”分隔</p>
			<p>文件格式下图所示:</p>
			<div style="padding: 8px; border: dashed 1px #ccc; margin: 5px 0;">
				高兴,开心<br />
				伤心,悲伤<br />
				友谊->友情<br />
				预见->预料<br />
			</div>
			<el-form label-position="left" size="mini" label-width="80px">
				<el-form-item label="作用范围">
					<el-radio v-model="upSiteId" label="0">所有站点</el-radio>
					<el-radio v-model="upSiteId" label="{$site[id]}">当前站点</el-radio>
				</el-form-item>
			</el-form>
			<span slot="footer" class="dialog-footer">
				<el-button @click="uploadDialogVisible = false">取 消</el-button>
				<el-upload action="/null" :show-file-list="false" style="display: inline;" :http-request="uploadFile">
					<el-button type="primary">选择文件</el-button>
				</el-upload>
			</span>
		</el-dialog>
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
			uploadDialogVisible: false,
			upSiteId: "0",
		};
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.kw = this.$route.query.kw;
				this.$axios.post("?m=seo&a=reword", { pagesize: this.pagesize, page: this.page }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
						resolve();
					}
				});
			});
		},
		rewordAdd(type) {
			if (type == "single") {
				this.routerTo("/seo/reword/add");
			} else {
				this.uploadDialogVisible = true;
			}
		},
		uploadFile(params) {
			let formdata = new FormData();
			formdata.append("file", params.file);
			this.$axios.post('?m=seo&a=reword_upload&siteId=' + this.upSiteId, formdata, { headers: { "Content-Type": "multipart/form-data;charset=UTF-8" } }).then(() => {
				this.pageInit();
				this.uploadDialogVisible = false;
			});
		},
	},
};
</script>

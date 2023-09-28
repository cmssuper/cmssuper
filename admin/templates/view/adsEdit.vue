<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/ads">广告管理</el-breadcrumb-item>
				<el-breadcrumb-item>广告详情</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" size="mini" label-width="120px">
					<el-form-item class="skeleton" label="广告位标记">
						<el-input v-model="form.abc" :disabled="form.id > 0" placeholder="任意字母和数字，不能和其他广告位重复" style="width: 300px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="广告位名称">
						<el-input v-model="form.title" placeholder="任意方便您记忆的名称" style="width: 300px;"></el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="开启广告">
						<el-switch v-model="form.status" active-value="1" inactive-value="0"> </el-switch>
					</el-form-item>
					<el-form-item class="skeleton" label="爬虫识别">
						<el-radio-group v-model="form.allowSpider" size="small">
							<el-radio label="1" border>不区分爬虫</el-radio>
							<el-radio label="0" border>爬虫不显示广告</el-radio>
						</el-radio-group>
					</el-form-item>
					<el-form-item class="skeleton" label="默认广告内容">
						<el-input type="textarea" placeholder="没有指定特定广告的站点显示该广告内容" :autosize="{ minRows: 3, maxRows: 10 }" v-model="form.content" style="width:500px;"> </el-input>
					</el-form-item>
					<el-form-item class="skeleton" label="指定站点广告">
						<el-button type="primary" class="el-icon-circle-plus" @click="siteAdAdd()">添加</el-button>
						<div class="siteAdBox">
							<div v-if="subAds.length">
								<div class="row" v-for="(item, index) in subAds" :key="index">
									<span>{{ item.name }}</span>
									<div>
										<el-link class="el-icon-edit" @click="subAdEdit(item)">修改</el-link>
										<el-link class="el-icon-delete" @click="subAdDel(item.id)">删除</el-link>
									</div>
								</div>
							</div>
							<div v-if="!subAds.length">暂无</div>
						</div>
					</el-form-item>
					<el-form-item>
						<el-button class="skeleton" type="primary" size="medium" @click="submitForm()">保存</el-button>
					</el-form-item>
				</el-form>
			</div>
		</div>
		<el-dialog title="指定站点广告设置" :visible.sync="siteAdAddVisible">
			<el-form :model="adAddForm">
				<el-form-item label="广告站点" label-width="120">
					<el-select v-model="siteSelectIds" multiple filterable reserve-keyword placeholder="选择站点" style="width:300px;">
						<el-option v-for="(item, index) in sitesList" :key="index" :label="item.sitename + '(' + item.name + ')'" :value="item.id"> </el-option>
					</el-select>
				</el-form-item>
				<el-form-item label="广告内容" label-width="120">
					<el-input style="width:500px;" type="textarea" placeholder="指定站点显示的广告内容" :autosize="{ minRows: 3, maxRows: 10 }" v-model="adAddForm.content"></el-input>
				</el-form-item>
			</el-form>
			<div slot="footer" class="dialog-footer">
				<el-button @click="siteAdAddVisible = false">取 消</el-button>
				<el-button type="primary" @click="siteAdAddSubmit">确 定</el-button>
			</div>
		</el-dialog>
	</div>
</template>
<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			form: {},
			adAddForm: {},
			siteAdAddVisible: false,
			siteSelectIds: [],
			subAds: [],
		};
	},
	methods: {
		pageInit() {
			if (this.$route.query.id) {
				return new Promise((resolve) => {
					this.$axios.get("?m=ads&a=edit&id=" + this.$route.query.id).then((res) => {
						this.form = res.data.success;
						this.subAdsLoad();
						resolve();
					});
				});
			}
		},
		subAdsLoad() {
			this.$axios.get("?m=ads&a=subedit&abc=" + this.form.abc).then((res) => {
				this.subAds = res.data.success;
			});
		},
		submitForm() {
			this.$axios.post("?m=ads&a=edit", this.form);
		},
		siteAdAdd() {
			if (this.form.id) {
				this.siteAdAddVisible = true;
				this.siteSelectIds = [];
				this.adAddForm = {};
			} else {
				this.$message({
					message: "请先保存广告位再设置指定站点",
					type: "error",
				});
			}
		},
		subAdEdit(item) {
			this.siteAdAddVisible = true;
			this.siteSelectIds = [item.yuming_id];
			this.adAddForm = {};
			this.$set(this.adAddForm, "content", item.content);
		},
		subAdDel(id) {
			this.$axios.get("?m=ads&a=subdel&id=" + id).then((res) => {
				if (res.data.success) {
					this.subAdsLoad();
				}
			});
		},
		siteAdAddSubmit() {
			this.adAddForm.yuming_ids = this.siteSelectIds;
			this.adAddForm.abc = this.form.abc;
			this.adAddForm.status = this.form.status;
			this.$axios.post("?m=ads&a=subedit", this.adAddForm).then((res) => {
				if (res.data.success) {
					this.siteAdAddVisible = false;
					this.subAdsLoad();
				}
			});
		},
	},
};
</script>
<style scoped>
.siteAdBox {
	border: 1px solid #dcdfe6;
	border-radius: 4px;
	margin-top: 10px;
	padding: 10px;
	width: 500px;
	color: #777;
}

.siteAdBox .row {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	border-bottom: solid 1px #efefef;
}
</style>

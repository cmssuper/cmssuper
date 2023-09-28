<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/article">文章管理</el-breadcrumb-item>
				<el-breadcrumb-item>{{ action == "edit" ? "修改文章" : "添加文章" }}</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form postbox cl">
			<div class="box ml">
				<el-form ref="formMain" :model="form" :rules="rules" size="mini" label-width="80px">
					<el-form-item label="批量发布" v-if="type == 'multi'">
						<el-transfer :titles="['站点列表', '已选择']" filterable v-model="selectSiteids" :filter-method="filterMethod" filter-placeholder="站点搜索" :data="sitesTransfer"> </el-transfer>
					</el-form-item>
					<el-form-item label="文章标题" prop="title">
						<el-input v-model="form.title" size="medium" style="width: 100%;" maxlength="50" show-word-limit> </el-input>
					</el-form-item>
					<el-form-item label="缩略图">
						<ul v-if="!!form.thumb" class="el-upload-list el-upload-list--picture-card">
							<li class="el-upload-list__item" style="height: auto">
								<img class="el-upload-list__item-thumbnail" :src="$baseHost + form.thumb" />
								<span class="el-upload-list__item-actions">
									<span class="el-upload-list__item-preview" @click="thumbPreview = true">
										<i class="el-icon-zoom-in"></i>
									</span>
									<span class="el-upload-list__item-delete" @click="removeThumb">
										<i class="el-icon-delete"></i>
									</span>
								</span>
							</li>
						</ul>
						<el-upload v-else list-type="picture-card" action="/null" :http-request="uploadFile" :show-file-list="false">
							<i class="el-icon-plus"></i>
						</el-upload>
						<el-dialog :visible.sync="thumbPreview">
							<img width="100%" :src="$baseHost + form.thumb" alt="" />
						</el-dialog>
					</el-form-item>
					<el-form-item label="文章正文" prop="body">
						<editor :value="form.body" @input="editorInput"></editor>
					</el-form-item>
					<el-form-item label="关键词">
						<el-input v-model="form.keyword" size="medium" style="width: 100%;"> </el-input>
					</el-form-item>
					<el-form-item label="文章摘要">
						<el-input v-model="form.description" size="medium" style="width: 100%;" type="textarea" :autosize="{ minRows: 4 }"> </el-input>
					</el-form-item>
					<el-form-item v-if="action == 'add'">
						<el-alert title="编辑内容自动保存，关闭后下次进入此页面可重新加载" type="info"> </el-alert>
					</el-form-item>
				</el-form>
			</div>
			<div class="box mr">
				<el-form ref="formSide" label-position="top" :model="form" label-width="40px">
					<div style="padding-bottom:20px;padding-top:10px;">
						<el-button type="primary" @click="submitForm(1)">{{ action == "add" ? "发布文章" : "更新文章" }}</el-button>
						<el-button v-if="action == 'add'" type="primary" plain @click="savedraft(1)">保存草稿</el-button>
						<el-button v-if="action == 'edit'" type="primary" plain @click="submitForm(0)">设为待审</el-button>
					</div>
					<el-form-item label="栏目">
						<el-select v-model="form.class" placeholder="请选择" clearable>
							<el-option v-for="item in classlist" :key="item.ename" :label="item.title" :value="item.ename"> </el-option>
						</el-select>
					</el-form-item>
					<el-form-item label="属性">
						<el-checkbox-group v-model="flag" :min="0" :max="1">
							<el-checkbox label="h">头条[h]</el-checkbox>
							<el-checkbox label="c">推荐[c]</el-checkbox>
							<el-checkbox label="f">幻灯[f]</el-checkbox>
							<el-checkbox label="a">特荐[a]</el-checkbox>
							<el-checkbox label="s">滚动[s]</el-checkbox>
							<el-checkbox label="b">加粗[b]</el-checkbox>
						</el-checkbox-group>
					</el-form-item>
					<el-form-item label="标签">
						<el-select v-model="form.tags" multiple filterable remote allow-create default-first-option placeholder="请选择文章标签" :remote-method="remoteTags">
							<el-option v-for="item in tags" :key="item.tagsname" :label="item.tagsname" :value="item.tagsname"> </el-option>
						</el-select>
						<el-button type="info" plain @click="tagManage">管理</el-button>
					</el-form-item>
					<el-form-item label="定时发布">
						<el-date-picker :editable="false" :clearable="false" v-model="timestamp" value-format="timestamp" type="datetime" placeholder="选择发布时间"> </el-date-picker>
					</el-form-item>
				</el-form>
			</div>
		</div>
		<el-dialog title="文章发布结果" width="700px" :close-on-click-modal="false" :close-on-press-escape="false" lock-scroll :visible.sync="dialogTableVisible" @closed="$router.go(-1)">
			<el-table :data="publishResult" :row-class-name="rowColor">
				<el-table-column property="sitename" label="站点" width="150"></el-table-column>
				<el-table-column property="status" :formatter="formatStatus" label="状态" width="100"></el-table-column>
				<el-table-column property="url" label="文章网址"></el-table-column>
			</el-table>
		</el-dialog>
	</div>
</template>

<script>
import editor from "./components/editor.vue";
import mixin from "./mixin";
export default {
	mixins: [mixin],
	components: {
		editor,
	},
	data() {
		return {
			form: {},
			sitesTransfer: [],
			selectSiteids: [],
			classlist: {},
			tags: [],
			rules: {
				title: [{ required: true, message: "请输入文章标题", trigger: "change" }],
			},
			thumbPreview: false,
			publishResult: [],
			dialogTableVisible: false,
		};
	},
	computed: {
		action() {
			return this.$route.query.id ? "edit" : "add";
		},
		type() {
			if (this.$route.query.type) {
				return this.$route.query.type;
			} else {
				return "single";
			}
		},
		flag: {
			get: function() {
				return this.form.flag ? [this.form.flag] : [];
			},
			set: function(newVal) {
				this.$set(this.form, "flag", newVal.length > 0 ? newVal[0] : "");
			},
		},
		timestamp: {
			get: function() {
				return this.form.addtime * 1000;
			},
			set: function(newVal) {
				this.$set(this.form, "addtime", newVal / 1000);
			},
		},
	},
	watch: {
		form: {
			handler() {
				this.savedraft();
			},
			deep: true,
		},
	},
	methods: {
		pageInit() {
			this.selectSiteids.push(this.siteId);
			this.sitesTransfer = [];
			for (let index in this.sites) {
				this.sitesTransfer.push({
					key: this.sites[index].id,
					label: this.sites[index].sitename,
				});
			}
			//加载栏目
			this.$axios.get("?m=article&a=classlist").then((res) => {
				this.classlist = res.data.success;
			});
			//加载默认下拉tags
			this.remoteTags();
			//加载草稿箱
			if (this.action == "add") {
				let draftData = window.localStorage.getItem("draftData");
				if (draftData) {
					this.$confirm("是否加载草稿箱内容", "提示", {
						confirmButtonText: "载入",
						cancelButtonText: "取消",
						type: "warning",
					}).then(() => {
						this.form = JSON.parse(draftData);
					});
				}
			} else {
				if (this.$route.query.id) {
					return new Promise((resolve) => {
						this.$axios.get("?m=article&a=edit&id=" + this.$route.query.id).then((res) => {
							if (res.data.success) {
								this.form = res.data.success;
								resolve();
							}
						});
					});
				}
			}
		},
		uploadFile(params) {
			let formdata = new FormData();
			formdata.append("file", params.file);
			this.$axios.post("?m=upload", formdata, { headers: { "Content-Type": "multipart/form-data;charset=UTF-8" } }).then((res) => {
				this.$set(this.form, "thumb", res.data.success.location);
			});
		},
		removeThumb() {
			this.$set(this.form, "thumb", "");
		},
		filterMethod(query, item) {
			var str = item.label + item.name;
			return str.indexOf(query) > -1;
		},
		editorInput(value) {
			this.$set(this.form, "body", value);
		},
		remoteTags(queryString) {
			this.$axios
				.get("?m=article&a=tags", {
					params: { kw: queryString ? queryString : "" },
				})
				.then((res) => {
					this.tags = res.data;
				});
		},
		formatStatus(row, column, cellValue) {
			var statusArr = { padding: "准备发布", running: "发布中", success: "成功", error: "错误" };
			return statusArr[cellValue];
		},
		rowColor({ row }) {
			var rowColors = { padding: "", running: "running-row", success: "success-row", error: "error-row" };
			return rowColors[row.status];
		},
		submitForm(status) {
			this.form.status = status;
			this.$refs["formMain"].validate((valid) => {
				if (valid) {
					if (!this.form.body) {
						this.$message.error("请先填写文章内容");
						return false;
					}
					if (this.selectSiteids.length == 0) {
						this.$message.error("请选择发布站点");
						return false;
					}
					this.publishResult = [];
					for (let index in this.selectSiteids) {
						this.publishResult.push({
							id: this.selectSiteids[index],
							sitename: this.sites[this.selectSiteids[index]].sitename,
							status: "padding",
							url: "",
						});
					}
					//多站点发布，弹出dialog
					if (this.selectSiteids.length > 1) {
						this.dialogTableVisible = true;
					}
					this.postArt(0);
				}
			});
		},
		postArt(index) {
			if (this.publishResult.length > index) {
				this.publishResult[index].status = "running";
				this.form.siteId = this.publishResult[index].id;
				this.$axios
					.post("?m=article&a=edit", this.form)
					.then((res) => {
						if (res.data.success) {
							this.publishResult[index].status = "success";
							this.publishResult[index].url = res.data.url;
						} else if (res.data.error) {
							this.publishResult[index].status = "error";
							this.publishResult[index].url = res.data.tip;
						}
						this.postArt(++index);
					})
					.catch(() => {
						this.publishResult[index].status = "error";
						this.publishResult[index].url = "网络错误";
						this.postArt(++index);
					});
			} else {
				//发布完成，清空草稿
				window.localStorage.removeItem("draftData");
				//单站点发布结果处理
				if (this.selectSiteids.length == 1) {
					if (this.publishResult[0].status == "success") {
						this.$message({
							message: "保存成功",
							type: "success",
						});
						this.$router.go(-1);
					}
				}
			}
		},
		savedraft(notify = 0) {
			if (this.action != "add" || (!this.form.title && !this.form.body)) return;
			window.localStorage.setItem("draftData", JSON.stringify(this.form));
			if (notify) {
				this.$notify({
					title: "成功",
					duration: 1000,
					message: "草稿保存成功",
					type: "success",
				});
			}
		},
		tagManage() {
			this.$confirm("即将离开此页面，未保存内容将丢失", "提示", {
				confirmButtonText: "离开",
				cancelButtonText: "取消",
				type: "warning",
			}).then(() => {
				this.routerTo("/tags");
			});
		},
	},
};
</script>

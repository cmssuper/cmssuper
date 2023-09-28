<template>
	<div>
		<el-popover placement="right" width="600" trigger="click" title="设置栏目采集关键词">
			<div class="infinite-list-item" slot="reference">
				<div class="infinite-list-name">{{ site.sitename }}({{ site.name }})</div>
				<span class="infinite-list-num" v-if="site.kwnum">{{ site.kwnum }}</span>
				<i class="el-icon-arrow-right"></i>
			</div>
			<div style="display:flex;flex-direction:row;">
				<div style="display:flex;flex-direction:column;max-height:500px;overflow:scroll;padding-top:10px;">
					<el-badge v-for="(item, i) in site.class" :key="i" :hidden="!getCrawlWordsNum(item.crawlWords)" :value="getCrawlWordsNum(item.crawlWords)" class="item" type="primary" style="margin-right:30px;width:200px;margin-bottom:15px;">
						<el-button type="primary" :plain="item.id != curClass.id" style="width:200px;" @click="showDialog(item)">{{ item.title }}<i class="el-icon-upload el-icon--right"></i></el-button>
					</el-badge>
				</div>
				<div v-if="curClass.id" style="flex:1;">
					<el-input type="textarea" :rows="20" width="100%;" placeHolder="填写要采集的关键词，每行一个" v-model="curClass.crawlWords"></el-input>
					<div style="display:flex; flex-direction:row; justify-content: flex-end; padding:10px;">
						<el-button @click="cancel">取 消</el-button>
						<el-button type="primary" @click="save">保 存</el-button>
					</div>
				</div>
			</div>
		</el-popover>
	</div>
</template>
<style scoped>

.infinite-list .infinite-list-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	height: 50px;
	padding: 0 10px;
	border-bottom: 1px solid #ebeef5;
	flex-direction: row;
}

.infinite-list .infinite-list-item .infinite-list-name {
	flex: 1;
}

.infinite-list .infinite-list-item .infinite-list-num {
	background-color: #409eff;
	color: #ffffff;
	border-radius: 8px;
	padding: 2px 6px;
	font-size: 12px;
	margin-right: 10px;
}
</style>
<script>
export default {
	props: ["site"],
	data() {
		return {
			curClass: [],
		};
	},
	methods: {
		getCrawlWordsNum(crawlWords) {
			if (crawlWords == "") return 0;
			return crawlWords.split("\n").length;
		},
		showDialog(item) {
			this.curClass = item;
			this.dialogFormVisible = true;
		},
		cancel() {
			this.curClass = [];
			this.$emit("change", true);
		},
		save() {
			this.$axios.post("?m=crawler&a=crawlKeywordSave", this.curClass).then((res) => {
				if (res.data.success) {
					this.curClass = [];
					this.$emit("change", true);
				}
			});
		},
	},
};
</script>

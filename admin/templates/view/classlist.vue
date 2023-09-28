<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/classlist">栏目管理</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table-top">
			<el-button size="medium" type="primary" plain @click="routerTo('/classlist/edit')">新建栏目</el-button>
		</div>
		<div class="cp-table skeleton">
			<el-table :data="listData" style="width: 100%" ref="listTable" v-if="reshow">
				<el-table-column prop="id" width="36">
					<template slot-scope="scope">
						<el-popover placement="right" trigger="hover" v-if="scope.row.status == 1">
							<div>
								<el-link slot="reference" class="el-icon-top" type="primary" @click="sort(scope.row.id, 'up')">上移</el-link>
								<el-link slot="reference" class="el-icon-bottom" type="primary" @click="sort(scope.row.id, 'down')">下移</el-link>
							</div>
							<el-link slot="reference" class="el-icon-d-caret" type="primary"></el-link>
						</el-popover>
					</template>
				</el-table-column>
				<el-table-column prop="title" label="栏目名称">
					<template slot-scope="scope">
						<span>{{ scope.row.title }}</span>
					</template>
				</el-table-column>
				<el-table-column prop="ename" label="栏目目录">
					<template slot-scope="scope">
						<span>{{ scope.row.ename }}</span>
						<i style="color:#ccc;font-size:12px;">(id:{{ scope.row.id }})</i>
					</template>
				</el-table-column>
				<el-table-column
					prop="status"
					label="状态"
					:filters="[
						{ text: '启用的栏目', value: 'open' },
						{ text: '禁用的栏目', value: 'close' },
					]"
					:filter-multiple="false"
					:filter-method="filterStatus"
					:filtered-value="filteredValue"
				>
					<template slot-scope="scope">
						<el-tag v-if="scope.row.status == 1" type="success" size="small">启用</el-tag>
						<el-tag v-if="scope.row.status == 0" type="info" size="small">禁用</el-tag>
					</template>
				</el-table-column>
				<el-table-column label="操作" width="160">
					<template slot-scope="scope">
						<div class="list-actions">
							<span><el-link type="primary" @click="routerTo('/classlist/edit?id=' + scope.row.id)">修改</el-link></span>
							<el-popconfirm title="确定要删除吗?" @confirm="ajaxGet('?m=classlist&a=del&id=' + scope.row.id)"><el-link slot="reference" type="danger">删除</el-link></el-popconfirm>
							<span v-if="scope.row.status == 0"> <el-link type="success" @click="ajaxGet('?m=classlist&a=open&id=' + scope.row.id)">启用</el-link> </span>
							<el-popconfirm v-if="scope.row.status == 1" title="确定要禁用默认栏目吗?" @confirm="ajaxGet('?m=classlist&a=stop&id=' + scope.row.id)">
								<el-link slot="reference" type="danger">禁用</el-link>
							</el-popconfirm>
						</div>
					</template>
				</el-table-column>
			</el-table>
			<div v-if="filteredValue[0] == 'open' && disableClassNum > 0" class="box-column" style="color: #ccc;font-size: 12px; text-align: center; cursor: pointer;margin-bottom:40px;">
				<p @click="showAllClass">展开其它{{ disableClassNum }}个未开启栏目</p>
			</div>
		</div>
	</div>
</template>

<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			listData: [],
			filteredValue: ["open"],
			reshow: true,
		};
	},
	computed: {
		disableClassNum() {
			var n = 0;
			for (let index in this.listData) {
				if (this.listData[index].status == 0) {
					n++;
				}
			}
			return n;
		},
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.$axios.post("?m=classlist").then((res) => {
					this.listData = res.data.success;
					resolve();
				});
			});
		},
		showAllClass() {
			this.filteredValue = [""];
			this.reshow = false;
			this.$nextTick(() => {
				this.reshow = true;
			});
		},
		filterStatus(value, row) {
			if (value == "open") {
				return row["status"] == 1;
			}
			if (value == "close") {
				return row["status"] == 0;
			}
			return true;
		},
		sort(v, direction) {
			var listData = [];
			for (let index in this.listData) {
				if (this.listData[index].status == 1) {
					listData.push({
						id: this.listData[index].id,
						weight: listData.length,
					});
					if (this.listData[index].id == v) {
						var curIndex = listData.length - 1;
					}
				}
			}
			if (direction == "up" && curIndex > 0) {
				listData[parseInt(curIndex) - 1].weight++;
				listData[curIndex].weight--;
			}
			if (direction == "down" && curIndex < listData.length - 1) {
				listData[parseInt(curIndex) + 1].weight--;
				listData[curIndex].weight++;
			}
			this.$axios.post("?m=classlist&a=weight", { data: listData }).then((res) => {
				if (res.data.success) {
					this.pageInit();
				}
			});
		},
	},
};
</script>

<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/spider">爬虫统计</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-table">
			<el-tabs v-model="tabName" type="card">
				<el-tab-pane label="爬虫趋势" name="index">
					<div id="charts" style="height:400px;"></div>
				</el-tab-pane>
				<el-tab-pane label="详细记录" name="log">
					<el-table :data="listData" style="width: 100%">
						<el-table-column prop="spider" label="爬虫" width="200"></el-table-column>
						<el-table-column
							prop="addtime"
							label="抓取时间"
							width="200"
							:formatter="
								(row) => {
									return formatTime(row.addtime);
								}
							"
						></el-table-column>
						<el-table-column prop="url" label="抓取链接"></el-table-column>
						<el-table-column prop="ip" label="IP地址" width="150"></el-table-column>
						<el-table-column prop="ua" label="爬虫UA"></el-table-column>
					</el-table>
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
				</el-tab-pane>
			</el-tabs>
		</div>
	</div>
</template>

<script>
import mixin from "./mixin";
export default {
	mixins: [mixin],
	data() {
		return {
			echartsTimer: null,
			xAxis: [],
			series: [],
			pagesize: 20,
			total: 0,
			listData: [],
		};
	},
	computed: {
		tabName: {
			get: function() {
				return this.$route.query.tab ? this.$route.query.tab : "index";
			},
			set: function(newValue) {
				this.routerUpdate({ tab: newValue });
			},
		},
	},
	methods: {
		pageInit() {
			if (this.tabName == "index") {
				this.$axios.post("?m=spider").then((res) => {
					if (res.data.success) {
						this.xAxis = res.data.success.xAxis;
						this.series = res.data.success.series;
						clearInterval(this.echartsTimer);
						this.echartsTimer = setInterval(() => {
							if (window.echarts) {
								this.echartsLoad();
								clearInterval(this.echartsTimer);
							}
						}, 100);
					}
				});
			} else if (this.tabName == "log") {
				this.$axios.post("?m=spider&a=log", { pagesize: this.pagesize, page: this.page }).then((res) => {
					if (res.data.success) {
						this.listData = res.data.success.list;
						this.total = parseInt(res.data.success.total);
					}
				});
			}
		},
		echartsLoad() {
			var option = {
				tooltip: {
					trigger: "axis",
				},
				legend: {},
				grid: {
					left: "3%",
					right: "4%",
					bottom: "3%",
					containLabel: true,
				},
				xAxis: {
					type: "category",
					boundaryGap: false,
					data: this.xAxis,
				},
				yAxis: {
					type: "value",
				},
				series: this.series,
			};
			window.echarts.init(document.getElementById("charts")).setOption(option);
		},
	},
};
</script>

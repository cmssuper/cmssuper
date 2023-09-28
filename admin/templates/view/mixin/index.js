import { mapState, mapGetters } from "vuex";

export default {
	data: function() {
		return {};
	},
	computed: {
		...mapState(["common", "axiosLoading"]),
		...mapGetters(["token", "sites", "sitesList", "siteId"]),
		page() {
			return this.$route.query.page ? parseInt(this.$route.query.page) : 1;
		},
	},
	async mounted() {
		if (this.$route.path != "/") {
			await this.loadCommon();
		}
		let allowPaths = ["/", "/home", "/sites/edit"];
		if (this.sitesList.length == 0 && !allowPaths.includes(this.$route.path)) {
			this.$message.error("站群还没有添加网站，请先创建网站");
			this.$router.push("/sites/edit");
			return;
		}
		if (this.pageInit) {
			this.$store.commit("setPageLoading", true);
			await this.pageInit();
			this.$store.commit("setPageLoading", false);
		}
	},
	watch: {
		$route: {
			handler: function() {
				this.pageInit();
			},
			deep: true,
		},
		siteId(n, o) {
			if (o != 0) {
				this.pageInit();
			}
		},
	},
	methods: {
		loadCommon(force) {
			return new Promise((resolve) => {
				if (!force && this.common.user) {
					resolve();
				} else {
					this.$axios.post("?m=getCommon", { siteId: null }).then((res) => {
						if (res.data.success) {
							this.$store.commit("setCommon", res.data.success);
							resolve();
						}
					});
				}
			});
		},
		routerTo(url, query) {
			this.$router.push({ path: url, query: query });
		},
		routerUpdate(query) {
			let flag = false;
			let newquery = { ...this.$route.query, ...query };
			for (var key in query) {
				if (query[key] == null) {
					delete newquery[key];
				}
				if (query[key] != this.$route.query[key]) {
					flag = true;
					break;
				}
			}
			if (flag) {
				this.$router.push({ query: newquery });
			}
		},
		formatTime(value) {
			var date = new Date(value * 1000);
			var year = date.getFullYear();
			var month = date.getMonth() + 1;
			var day = date.getDate();
			var hour = date.getHours();
			var minute = date.getMinutes();
			var second = date.getSeconds();
			return year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;
		},
		formatSize(value) {
			if (!-value) {
				return "0B";
			}
			var unitArr = new Array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
			var index = 0;
			var srcsize = parseFloat(value);
			index = Math.floor(Math.log(srcsize) / Math.log(1024));
			var size = srcsize / Math.pow(1024, index);
			size = parseFloat(size.toFixed(2));
			return size + unitArr[index];
		},
		submitValidate() {
			return new Promise((resolve) => {
				this.$refs["form"].validate((valid) => {
					resolve(valid);
				});
			});
		},
		ajaxGet(url) {
			this.$axios.get(url).then((res) => {
				if (res.data.success) {
					this.pageInit();
				}
			});
		},
	},
};

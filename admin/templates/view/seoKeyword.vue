<template>
	<div class="cp-wrap">
		<div class="crumb">
			<el-breadcrumb separator="/">
				<el-breadcrumb-item to="/seo/keyword">关键词优化</el-breadcrumb-item>
			</el-breadcrumb>
		</div>
		<div class="cp-form">
			<div class="box">
				<el-form ref="form" :model="form" size="mini" label-width="130px">
					<el-form-item class="skeleton" label="文章关键词">
						<el-input v-model="form.seoWord" type="textarea" :autosize="{ minRows: 6, maxRows: 10 }" style="width: 400px;"></el-input>
						<div class="tip">多个关键词用逗号或换行隔开</div>
					</el-form-item>
					<el-form-item class="skeleton" label="同时插入标题">
						<el-switch v-model="form.seoTitle" active-value="1" inactive-value="0"> </el-switch>
					</el-form-item>
					<el-form-item class="skeleton" label="插入标题位置">
						<el-radio v-model="form.seoTitlex" label="1">标题前面</el-radio>
						<el-radio v-model="form.seoTitlex" label="2">标题后面</el-radio>
						<el-radio v-model="form.seoTitlex" label="3">自由插入</el-radio>
					</el-form-item>
					<el-form-item class="skeleton" label="插入数量">
						<el-input-number v-model="form.seoWordNum" controls-position="right" :min="1" :max="10"></el-input-number>
						<div class="tip">每篇文章插入词语的数量</div>
					</el-form-item>
					<el-form-item class="skeleton" label="文章优化比例">
						<el-slider v-model="form.seoWordScale" :format-tooltip="formatTooltip" :step="10" style="width: 400px;"></el-slider>
						<div class="tip">优化文章的比例，可填0-100，0关闭该功能，100全部文章都插入</div>
					</el-form-item>
					<el-form-item>
						<el-button class="skeleton" type="primary" size="medium" @click="submitForm()">保存</el-button>
					</el-form-item>
				</el-form>
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
			form: {},
		};
	},
	methods: {
		pageInit() {
			return new Promise((resolve) => {
				this.$axios.get("?m=seo&a=keyword&id=" + this.$route.query.id).then((res) => {
					if (res.data.success) {
						this.form = res.data.success;
						if (!this.form.seoTitlex) {
							this.$set(this.form, "seoTitlex", "1");
						}
						this.$set(this.form, "seoWordScale", parseInt(this.form.seoWordScale));
						resolve();
					}
				});
			});
		},
		formatTooltip(e) {
			if (e == 0) {
				return "关闭";
			} else if (e == 100) {
				return "全部文章都插入关键词";
			} else {
				return "每 100 篇文章中处理插入其中 " + e + " 篇";
			}
		},
		submitForm() {
			this.$axios.post("?m=seo&a=keyword", this.form);
		},
	},
};
</script>

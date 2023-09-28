<template>
	<div>
		<div id="monacoEditor">
			<div v-if="!isText" class="imageViewer">
				<el-image v-if="language == 'image'" :src="$baseHost + value"></el-image>
				<span v-if="language == 'unknow'">无法显示此文件</span>
			</div>
		</div>
	</div>
</template>
<style scoped>
#monacoEditor {
	height: 100%;
}
</style>
<script>
export default {
	name: "codeEditor",
	props: {
		value: {
			type: String,
			default: "",
		},
		fileType: {
			type: String,
			default: "",
		},
	},
	data() {
		return {
			createTimer: null,
			resizeTimer: null,
			editor: null,
			body: "",
		};
	},
	computed: {
		language() {
			let language = "";
			switch (this.fileType) {
				case "htm":
				case "html":
				case "xml":
					language = "html";
					break;
				case "css":
					language = "css";
					break;
				case "js":
					language = "javascript";
					break;
				case "sql":
					language = "sql";
					break;
				case "gif":
				case "jpg":
				case "jpeg":
				case "png":
					language = "image";
					break;
				default:
					language = "unknow";
			}
			return language;
		},
		isText() {
			let textLanguage = ["html", "css", "javascript", "sql"];
			return textLanguage.indexOf(this.language) > -1;
		},
	},
	watch: {
		language() {
			if (!this.isText) {
				this.unloadEditor();
			} else {
				this.createEditor();
			}
		},
		value(n) {
			if (n != this.body) {
				this.body = n;
				if (this.isText) {
					this.editor.setValue(n);
				}
			}
		},
	},
	mounted() {
		window.onresize = () => {
			if (this.editor) {
				this.resizeTimer = setTimeout(() => {
					this.createEditor();
					clearTimeout(this.resizeTimer);
				}, 1000);
			}
		};
		this.createEditor();
	},
	methods: {
		createEditor() {
			if (!window.monaco) {
				this.createTimer = setTimeout(() => {
					this.createEditor();
				}, 100);
				return;
			}
			clearTimeout(this.createTimer);
			this.unloadEditor();
			this.editor = window.monaco.editor.create(document.getElementById("monacoEditor"), {
				value: this.body,
				language: this.language,
				automaticLayout: true,
				autoIndent: true,
				minimap: { enabled: false },
				lineNumbers: false,
			});
			this.editor.onDidChangeModelContent(() => {
				this.body = this.editor.getValue();
				this.$emit("input", this.body);
			});
		},
		unloadEditor() {
			if (this.editor) {
				this.editor.dispose();
				this.editor = null;
			}
		},
	},
};
</script>

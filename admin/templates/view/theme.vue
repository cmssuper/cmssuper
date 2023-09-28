<template>
    <div class="cp-wrap">
        <div class="crumb">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item to="/theme">模版风格</el-breadcrumb-item>
            </el-breadcrumb>
        </div>
        <div class="themes">
            <el-tabs tab-position="left" value="list" :before-leave="beforeLeave">
                <el-tab-pane label="我的模版" name="list">
                    <div class="wswrap">
                        <div class="ws" v-for="(item, index) in listData" :key="index">
                            <div class="preview">
                                <img v-if="item.preview" :src="$baseHost + item.preview" alt="" />
                                <i v-else class="nopic el-icon-picture-outline"></i>
                                <div class="tplname">{{ item.name }}({{ index }})</div>
                                <div class="actions">
                                    <el-button class="el-icon-edit" size="mini" @click="showEditorDialog(index, item)">编辑模版</el-button>
                                    <el-popconfirm v-if="index != 'default'" title="确定删除吗？" @confirm="themeDel(index, item)">
                                        <el-button slot="reference" class="el-icon-delete" size="mini">删除模版</el-button>
                                    </el-popconfirm>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-tab-pane>
                <el-tab-pane label="更多模版" name="more"> </el-tab-pane>
            </el-tabs>
        </div>
        <el-dialog
            :lock-scroll="true"
            custom-class="editorDialog"
            :title="theme.name + ' (/templates/' + themeFolder + ')'"
            width="94%"
            top="5vh"
            :visible.sync="editorDialogVisible"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :destroy-on-close="true">
            <div class="editorWrap" v-if="editorDialogVisible">
                <div class="editorSide">
                    <div class="estitle">模版列表</div>
                    <div class="treewrap">
                        <el-tree
                            :props="{
                                label: 'label',
                                children: 'children',
                                isLeaf: 'isfile',
                            }"
                            ref="tplTree"
                            :load="loadtplTree"
                            @current-change="loadTplBody"
                            :highlight-current="true"
                            node-key="dir"
                            lazy></el-tree>
                    </div>
                </div>
                <div class="codeEditorWrap">
                    <el-input size="small" v-model="tplDir">
                        <template slot="prepend">/templates</template>
                    </el-input>
                    <codeEditor class="codeEditor" v-model="body" :fileType="fileType"></codeEditor>
                </div>
            </div>
            <div slot="footer">
                <el-button @click="editorDialogVisible = false">关 闭</el-button>
                <el-button type="primary" @click="saveTpl" v-if="isText" :loading="loading">保 存</el-button>
            </div>
        </el-dialog>
        <el-dialog
            title="模版下载中"
            :show-close="false"
            :close-on-click-modal="false"
            :close-on-press-escape="false"
            :visible.sync="downDialogVisible"
            width="30%">
            <div><el-progress :percentage="downprogress"></el-progress></div>
            <span slot="footer" class="dialog-footer">
                <el-button type="primary" @click="downDialogVisible = false" size="mini">关闭</el-button>
            </span>
        </el-dialog>
    </div>
</template>
<script>
import mixin from "./mixin";
import codeEditor from "./components/codeEditor.vue";
export default {
    mixins: [mixin],
    components: {
        codeEditor,
    },
    data() {
        return {
            editorDialogVisible: false,
            pagesize: 20,
            total: 0,
            listData: {},
            liveData: [],
            themeFolder: "",
            theme: {},
            tplDir: "",
            body: "",
            fileType: "",
            loading: false,
            downDialogVisible: false,
            downprogress: 0,
        };
    },
    computed: {
        isText() {
            var textArr = ["htm", "html", "xml", "css", "js", "txt"];
            return textArr.indexOf(this.fileType) != -1;
        },
    },
    methods: {
        pageInit() {
            this.$axios.get("?m=theme").then(res => {
                this.listData = res.data.success;
            });
        },
        showEditorDialog(themeFolder, theme) {
            this.themeFolder = themeFolder;
            this.theme = theme;
            this.editorDialogVisible = true;
        },
        loadtplTree(node, resolve) {
            var dir = node.level > 0 ? node.data.dir : "/" + this.themeFolder;
            this.$axios.post("?m=theme&a=loadDir", { dir: dir }).then(res => {
                resolve(res.data);
                if (node.level === 0) {
                    this.$refs.tplTree.setCurrentKey(dir + "/index.htm");
                    var note = this.$refs.tplTree.getCurrentNode();
                    this.loadTplBody(note);
                }
            });
        },
        loadTplBody(data) {
            if (data.isfile) {
                this.tplDir = data.dir;
                this.fileType = data.dir.substring(data.dir.lastIndexOf(".") + 1);
                if (!this.isText) {
                    this.body = "/templates" + this.tplDir;
                } else {
                    var loading = this.$loading({
                        target: ".codeEditor",
                    });
                    this.$axios.post("?m=theme&a=loadTplBody", { file: data.dir }).then(res => {
                        this.body = res.data.success;
                        loading.close();
                    });
                }
            }
        },
        saveTpl() {
            this.loading = true;
            this.$axios.post("?m=theme&a=tplSave", { theme: this.themeFolder, file: this.tplDir, body: this.body }).then(() => {
                this.loading = false;
            });
        },
        themeDel(themeName) {
            this.$axios.post("?m=theme&a=del", { themeName: themeName }).then(() => {
                this.pageInit();
            });
        },
        beforeLeave(activeName) {
            if (activeName == "more") {
                window.open("https://support.qq.com/products/417911");
            }
            return false;
        },
    },
};
</script>

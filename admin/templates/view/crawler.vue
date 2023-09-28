<template>
    <div class="cp-wrap">
        <div class="crumb">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item to="/crawler">采集管理</el-breadcrumb-item>
            </el-breadcrumb>
        </div>
        <el-tabs type="border-card" v-model="tabName">
            <el-tab-pane label="规则采集" name="crawlRule" :lazy="true">
                <div class="cp-table-top">
                    <el-button size="medium" type="primary" plain @click="routerTo('/crawler/edit')">添加规则 </el-button>
                </div>
                <div class="cp-table">
                    <el-table :data="crawlRule" style="width: 100%">
                        <el-table-column prop="id" label="ID"></el-table-column>
                        <el-table-column prop="listurl" label="采集地址"></el-table-column>
                        <el-table-column prop="yuming_id" label="站点">
                            <template slot-scope="scope">
                                <el-tag v-if="scope.row.yuming_id > 1" type="success" size="small">单站规则</el-tag>
                                <el-tag v-if="scope.row.yuming_id == 0" type="info" size="small">全局规则</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column prop="crawlRule" label="自动采集">
                            <template slot="header">
                                <el-dropdown trigger="click" placement="bottom" @command="crawlRuleMultiSet">
                                    <span class="el-dropdown-link"> 自动采集<i class="el-icon-arrow-down el-icon--right"></i> </span>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-dropdown-item command="1">全部开启</el-dropdown-item>
                                        <el-dropdown-item command="0">全部关闭</el-dropdown-item>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </template>
                            <template slot-scope="scope">
                                <el-switch
                                    v-model="scope.row.autoStart"
                                    active-value="1"
                                    inactive-value="0"
                                    @change="crawlRuleChange(scope.row)"></el-switch>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="170">
                            <template slot-scope="scope">
                                <div class="list-actions">
                                    <span><el-link type="primary" @click="start_caiji(scope.row.id)">采集</el-link></span>
                                    <span><el-link type="primary" @click="routerTo('/crawler/edit?id=' + scope.row.id)">修改</el-link></span>
                                    <el-popconfirm title="确定要删除吗?" @confirm="ajaxGet('?m=crawler&a=crawlRuleDel&id=' + scope.row.id)"
                                        ><el-link slot="reference" type="danger">删除</el-link></el-popconfirm
                                    >
                                </div>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </el-tab-pane>

            <el-tab-pane label="关键词采集" name="crawlKeyword" :lazy="true">
                <div class="infinite-list">
                    <clawlerListItem v-for="(item, i) in crawlKeyword.sites" :site="item" @change="loadCrawlKeywordData" :key="i"></clawlerListItem>
                </div>
            </el-tab-pane>

            <el-tab-pane label="AI生成文章" name="AiNews" :lazy="true">
                <el-alert type="warning" :closable="false"> 本功能仅用于新站填充数据测试模版，不适用于线上运行网站，请删除数据后在上线 </el-alert>
                <el-form ref="form" size="mini" label-width="120px" style="margin-top: 20px">
                    <el-form-item label="标题词汇">
                        <el-input type="textarea" :rows="8" v-model="AiNewsArgs.words" style="width: 500px" placeholder="每个词生成一篇文章">
                        </el-input>
                    </el-form-item>
                    <el-form-item label="标题优化">
                        <el-switch v-model="AiNewsArgs.titleBetter" active-value="1" inactive-value="0"> </el-switch>
                    </el-form-item>
                    <el-form-item label="文章长度">
                        <el-input-number v-model="AiNewsArgs.bodyLength" :controls="false" style="width: 80px"></el-input-number>
                    </el-form-item>
                    <el-form-item label="高级处理">
                        <el-switch v-model="AiNewsArgs.bodyBetter" active-value="1" inactive-value="0"> </el-switch>
                    </el-form-item>
                    <el-form-item label="站点">
                        <el-table
                            v-if="AiNews"
                            :data="AiNews.sites"
                            tooltip-effect="dark"
                            style="width: 100%"
                            height="350"
                            @selection-change="AiNewsClassSelectionChange">
                            <el-table-column type="selection" width="55"> </el-table-column>
                            <el-table-column label="站点">
                                <template slot-scope="scope">{{ scope.row.sitename }}({{ scope.row.name }})</template>
                            </el-table-column>
                            <el-table-column label="发布栏目">
                                <template slot-scope="scope">
                                    <el-select
                                        v-model="scope.row.classSeled"
                                        multiple
                                        collapse-tags
                                        style="margin-left: 20px"
                                        placeholder="默认全部栏目">
                                        <el-option v-for="item in scope.row.class" :key="item.ename" :label="item.title" :value="item.ename">
                                        </el-option>
                                    </el-select>
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" size="medium" @click="AiNewsSubmitForm()">开始生成并发布</el-button>
                    </el-form-item>
                </el-form>
            </el-tab-pane>
        </el-tabs>
        <el-dialog title="采集预览" :visible.sync="caiji.dialogVisible" @close="close_caiji">
            <div style="position: absolute; right: 80px; top: 22px">
                <el-link class="el-icon-delete" type="danger" @click="clearRuleLog(caiji.ruleid)">清理采集缓存</el-link>
            </div>
            <div v-for="(item, index) in caiji.result" v-html="item" :key="index"></div>
        </el-dialog>

        <el-dialog title="生成预览" :visible.sync="AiCaiji.dialogVisible" @close="close_AiCaiji">
            <div v-for="(item, index) in AiCaiji.result" v-html="item" :key="index"></div>
        </el-dialog>
    </div>
</template>
<style scoped>
.h3 {
    font-size: 16px;
    padding: 20px 0;
}
.infinite-list {
    width: 400px;
    padding: 0;
    margin: 0;
    list-style: none;
    border: 1px solid #ebeef5;
    border-bottom: none;
}
</style>
<script>
import clawlerListItem from "./components/clawlerListItem.vue";
import mixin from "./mixin";
export default {
    mixins: [mixin],
    components: {
        clawlerListItem,
    },
    data() {
        return {
            visible: false,
            crawlRule: [],
            crawlKeyword: [],
            AiNews: {},
            AiNewsArgs: {
                titleBetter: "1",
                bodyLength: 500,
            },
            caiji: {
                ruleid: "",
                dialogVisible: false,
                source: null,
                result: [],
            },
            AiCaiji: {
                dialogVisible: false,
                result: [],
            },
        };
    },
    computed: {
        tabName: {
            get: function () {
                return this.$route.query.tab ? this.$route.query.tab : "crawlRule";
            },
            set: function (newValue) {
                this.routerUpdate({ tab: newValue });
            },
        },
    },
    methods: {
        pageInit() {
            var n = this.$route.query.tab;
            if (n == "crawlKeyword") {
                this.loadCrawlKeywordData();
            } else if (n == "crawlRule") {
                this.loadCrawlRuleData();
            } else if (n == "AiNews") {
                this.loadAiNewsData();
            }
        },
        loadCrawlKeywordData() {
            this.$axios.get("?m=crawler&a=crawlKeywordData").then(res => {
                this.crawlKeyword = res.data;
            });
        },
        loadCrawlRuleData() {
            this.$axios.get("?m=crawler&a=crawlRuleData").then(res => {
                this.crawlRule = res.data;
            });
        },
        crawlRuleChange(data) {
            this.$axios.post("?m=crawler&a=crawlRuleChange", data).then(() => {
                this.loadCrawlRuleData();
            });
        },
        crawlRuleMultiSet(data) {
            this.$axios.post("?m=crawler&a=crawlRuleChange", { autoStart: data }).then(() => {
                this.loadCrawlRuleData();
            });
        },
        start_caiji(ruleid) {
            this.caiji.ruleid = ruleid;
            this.caiji.dialogVisible = true;
            this.caiji.source = this.$axios.CancelToken.source();
            this.start_auto();
        },
        close_caiji() {
            this.caiji.source.cancel();
            this.caiji.result = [];
        },
        start_auto() {
            if (this.caiji.dialogVisible == true) {
                this.$axios
                    .get("../crawler/crawlRule?ruleid=" + this.caiji.ruleid, { cancelToken: this.caiji.source.token })
                    .then(res => {
                        if (res.data.length > 0 && this.caiji.result[this.caiji.result.length - 1] != res.data) {
                            this.caiji.result.push(res.data);
                            this.caiji.result.splice(0, this.caiji.result.length - 18);
                            if (res.data.indexOf("Error:") == -1) {
                                setTimeout(() => {
                                    this.start_auto();
                                }, 200);
                            }
                        } else {
                            setTimeout(() => {
                                this.start_auto();
                            }, 2000);
                        }
                    })
                    .catch(res => {
                        if (res.data.length > 0 && this.caiji.result[this.caiji.result.length - 1] != res.data) {
                            this.caiji.result.push(res.data);
                        }
                        if (res.data.indexOf("Error:") == -1) {
                            setTimeout(() => {
                                this.start_auto();
                            }, 500);
                        }
                    });
            }
        },
        clearRuleLog() {
            this.$confirm("系统自动对采集进度进行缓存，避免重复采集，只有规则未成功采集，修改规则后才需要清理缓存", "提示", {
                confirmButtonText: "确定",
                cancelButtonText: "取消",
                type: "warning",
            }).then(() => {
                this.$axios.get("?m=crawler&a=clearRuleLog&id=" + this.caiji.ruleid).then(() => {
                    this.$notify({
                        title: "成功",
                        duration: 1000,
                        message: "缓存已清理",
                        type: "success",
                    });
                });
            });
        },
        loadAiNewsData() {
            this.$axios.get("?m=crawler&a=AiNewsData").then(res => {
                this.AiNews = res.data;
            });
        },
        AiNewsSubmitForm() {
            if (!this.AiNewsArgs.words) {
                this.$message.error("请填写标题词汇");
                return;
            }
            if (!this.AiNewsArgs.siteIds || this.AiNewsArgs.siteIds.length == 0) {
                this.$message.error("请选择要发布的站点");
                return;
            }
            this.$confirm(
                "<b>系统将根据标题词汇随机组成的文本插入系统中，文本内容可能存在歧义，您需要自行承担相应后果</b>",
                "使用本功能，表明您已明确了解",
                {
                    dangerouslyUseHTMLString: true,
                    confirmButtonText: "开始生成",
                    cancelButtonText: "我不能接受",
                }
            )
                .then(() => {
                    this.AiCaiji.dialogVisible = true;
                    this.AiNewsSubmitFormHandle();
                })
                .catch(() => {
                    this.$message({
                        message: "已取消生成",
                        type: "warning",
                    });
                });
        },
        AiNewsSubmitFormHandle() {
            this.AiNewsArgs.sites = [];
            this.AiNews.sites.forEach(x => {
                if (this.AiNewsArgs.siteIds.indexOf(x.id) != -1) {
                    this.AiNewsArgs.sites.push({ id: x.id, class: x.classSeled });
                }
            });
            var words = this.AiNewsArgs.words.split("\n");
            var data = {
                word: words.shift(),
                titleBetter: this.AiNewsArgs.titleBetter,
                bodyLength: this.AiNewsArgs.bodyLength,
                bodyBetter: this.AiNewsArgs.bodyBetter,
                sites: this.AiNewsArgs.sites,
            };
            this.$axios.post("?m=crawler&a=AiNewsPost", data).then(res => {
                if (res.data.success) {
                    this.AiCaiji.result.push(res.data.title);
                    this.AiCaiji.result.splice(0, this.AiCaiji.result.length - 18);
                }
                this.AiNewsArgs.words = words.join("\n");
                if (this.AiCaiji.dialogVisible) {
                    if (words.length > 0) {
                        if (words.length > 10) {
                            this.AiNewsSubmitFormHandle();
                        } else {
                            setTimeout(() => {
                                this.AiNewsSubmitFormHandle();
                            }, 500);
                        }
                    } else {
                        this.AiCaiji.result.push("<font color=red>生成完成</font>");
                    }
                }
            });
        },
        AiNewsClassSelectionChange(val) {
            this.AiNewsArgs.siteIds = [];
            val.forEach(item => {
                this.AiNewsArgs.siteIds.push(item.id);
            });
        },
        close_AiCaiji() {
            this.AiCaiji.result = [];
        },
    },
};
</script>

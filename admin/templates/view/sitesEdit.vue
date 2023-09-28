<template>
    <div class="cp-wrap">
        <div class="crumb">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item to="/sites">站点管理</el-breadcrumb-item>
                <el-breadcrumb-item>站点编辑</el-breadcrumb-item>
            </el-breadcrumb>
        </div>
        <div v-if="is_demo" style="padding: 10px 0">
            <el-alert type="error" effect="dark">
                <div slot="title">
                    <div style="font-size: 20px"><i class="el-icon-warning"></i>演示站操作提示</div>
                </div>
                <div slot>
                    <div style="font-size: 16px">
                        1、我们提供的演示子域名供您任意使用，直接添加站点 {随机}.llsq.cn 即可访问测试<br />
                        2、您也可以把自己的域名（需要已备案）解析到我们到演示服务器ip：113.31.119.240 进行测试<br />
                        3、测试程序数据仅保留1小时，如需更多测试您可以安装到自己到服务器
                    </div>
                </div>
            </el-alert>
        </div>
        <div class="cp-form">
            <el-alert
                v-if="sitesList.length == 0"
                class="noSiteTip"
                title="您的站群还没有网站，现在只需要一分钟，即可创建第一个网站"
                type="error"
                effect="dark">
            </el-alert>
            <el-tabs type="border-card" v-model="tabName">
                <el-tab-pane label="单个网站" name="single">
                    <el-form ref="form" :model="form" :rules="rules" label-width="120px">
                        <el-form-item label="域名" prop="name">
                            <el-space>
                                <el-input v-model="form.name" placeHolder="网站域名 xxx.com" style="width: 300px"></el-input>
                                &nbsp;
                                <el-tooltip class="item" effect="dark" content="[广告]通过购买有建站历史、有过收录的高权重域名搭建网站，可加快收录排名速度，点击查看" placement="top">
                                    <a style="color: gray" href="https://www.juming.com/?tt=124097" target="_blank" rel="noopener noreferrer"
                                        >高权重老域名抢注通道</a
                                    >
                                </el-tooltip>
                            </el-space>
                        </el-form-item>
                        <el-form-item label="网站名称" prop="sitename">
                            <el-input v-model="form.sitename" style="width: 300px"></el-input>
                        </el-form-item>
                        <el-form-item label="首页标题">
                            <el-input
                                v-model="form.siteTitle"
                                placeHolder="浏览器标题栏title标签中，如 xxx网-专业提供xxx服务"
                                style="width: 400px"></el-input>
                            <span class="tip"></span>
                        </el-form-item>
                        <el-form-item label="站点LOGO">
                            <ul v-if="!!form.logo" class="el-upload-list el-upload-list--picture-card">
                                <li class="el-upload-list__item" style="height: auto">
                                    <img class="el-upload-list__item-thumbnail" :src="$baseHost + form.logo" />
                                    <span class="el-upload-list__item-actions">
                                        <span class="el-upload-list__item-delete" @click="removeThumb">
                                            <i class="el-icon-delete"></i>
                                        </span>
                                    </span>
                                </li>
                            </ul>
                            <el-upload v-else list-type="picture-card" action="/null" :http-request="uploadFile" :show-file-list="false">
                                <i class="el-icon-plus"></i>
                            </el-upload>
                            <span class="tip">请注意控制图片大小</span>
                        </el-form-item>
                        <el-form-item label="关键词">
                            <el-input v-model="form.keywords" style="width: 400px"></el-input>
                        </el-form-item>
                        <el-form-item label="描述">
                            <el-input
                                v-model="form.description"
                                type="textarea"
                                :autosize="{ minRows: 2, maxRows: 4 }"
                                style="width: 400px"></el-input>
                        </el-form-item>
                        <el-form-item label="网站风格" prop="template">
                            <el-select v-model="form.template" placeholder="请选择">
                                <el-option v-for="(item, index) in themes" :key="index" :label="item.name + '(' + index + ')'" :value="index">
                                </el-option>
                            </el-select>
                        </el-form-item>
                        <el-form-item>
                            <el-button type="primary" size="medium" @click="submitForm(true)">保存</el-button>
                        </el-form-item>
                    </el-form>
                </el-tab-pane>
                <el-tab-pane label="批量添加" name="multi" v-if="!this.form.id">
                    <el-table :data="multiForm" tooltip-effect="dark" style="width: 100%">
                        <el-table-column label="域名">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.name"></el-input>
                            </template>
                        </el-table-column>
                        <el-table-column label="网站名称">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.sitename"></el-input>
                            </template>
                        </el-table-column>
                        <el-table-column label="首页标题">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.siteTitle"></el-input>
                            </template>
                        </el-table-column>
                        <el-table-column label="关键词">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.keywords"></el-input>
                            </template>
                        </el-table-column>
                        <el-table-column label="描述">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.description"></el-input>
                            </template>
                        </el-table-column>
                        <el-table-column label="网站风格">
                            <template slot-scope="scope">
                                <el-select v-model="scope.row.template" placeholder="请选择">
                                    <el-option v-for="(item, index) in themes" :key="index" :label="item.name + '(' + index + ')'" :value="index">
                                    </el-option>
                                </el-select>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作">
                            <template slot-scope="scope">
                                <el-button
                                    v-if="multiForm.length > 1"
                                    type="primary"
                                    size="medium"
                                    @click="multiRowDel(scope.$index)"
                                    class="el-icon-delete"
                                    circle></el-button>
                                <el-button
                                    v-if="scope.$index == multiForm.length - 1"
                                    type="primary"
                                    size="medium"
                                    @click="multiRowAdd"
                                    class="el-icon-plus"
                                    circle></el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-divider content-position="left"> 模版提交（一行一个） </el-divider>
                    <el-input type="textarea" v-model="multiTxt" :rows="10" placeholder="域名#网站名称#首页标题#关键词#描述#网站风格"></el-input>
                    <div class="box-column">
                        <el-button type="danger" size="medium" v-if="multiTxt" @click="multiTxtHandle">识别模版数据</el-button>
                        <el-button type="primary" size="medium" v-else @click="multiSaveHandle" :loading="loading">立即创建</el-button>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>
    </div>
</template>
<style scoped>
.noSiteTip {
    margin-bottom: 10px;
}
</style>
<script>
import mixin from "./mixin";
export default {
    mixins: [mixin],
    data() {
        return {
            form: {},
            themes: [],
            rules: {
                name: [{ required: true, message: "请填写网站域名", trigger: "change" }],
                sitename: [{ required: true, message: "请填写网站名称", trigger: "change" }],
                template: [{ required: true, message: "请选择网站模版", trigger: "change" }],
            },
            multiForm: [{}, {}, {}],
            multiTxt: "",
            loading: false,
        };
    },
    computed: {
        is_demo() {
            return window.location.host.match(/\.llsq\.cn$/i);
        },
        tabName: {
            get: function () {
                return this.$route.query.tab ? this.$route.query.tab : "single";
            },
            set: function (newValue) {
                this.routerUpdate({ tab: newValue });
            },
        },
    },
    watch: {
        multiForm(n) {
            if (n.length == 0) {
                this.multiForm = [{}];
            }
        },
    },
    methods: {
        pageInit() {
            this.$axios.get("?m=sites&a=get_themes").then(res => {
                if (res.data.success) {
                    this.themes = res.data.success;
                }
            });
            if (this.$route.query.id) {
                return new Promise(resolve => {
                    this.$axios.get("?m=sites&a=edit&id=" + this.$route.query.id).then(res => {
                        if (res.data.success) {
                            this.form = res.data.success;
                            resolve();
                        }
                    });
                });
            }
        },
        submitForm() {
            this.$refs["form"].validate(valid => {
                if (valid) {
                    this.$axios.post("?m=sites&a=edit", this.form).then(async res => {
                        if (res.data.success) {
                            await this.loadCommon(true);
                            this.routerTo("/sites");
                        }
                    });
                }
            });
        },
        uploadFile(params) {
            let formdata = new FormData();
            formdata.append("file", params.file);
            this.$axios.post("?m=upload", formdata, { headers: { "Content-Type": "multipart/form-data;charset=UTF-8" } }).then(res => {
                this.$set(this.form, "logo", res.data.success.location);
            });
        },
        removeThumb() {
            this.$set(this.form, "logo", "");
        },
        multiTxtHandle() {
            this.multiTxt.split("\n").forEach(item => {
                var a = item.split("#");
                this.multiForm.push({
                    name: a[0],
                    sitename: a[1],
                    siteTitle: a[2],
                    keywords: a[3],
                    description: a[4],
                    template: a[5],
                });
            });
            this.multiTxt = "";
        },
        multiSaveHandle() {
            this.loading = true;
            var data = {
                data: this.multiForm,
            };
            this.$axios.post("?m=sites&a=multiSave", data).then(async res => {
                this.loading = false;
                var tmp = [];
                if (res.data.success) {
                    this.multiForm.forEach((item, i) => {
                        if (res.data.success.successIndexs.indexOf(i) == -1) {
                            tmp.push(item);
                        }
                    });
                    this.multiForm = tmp;
                    this.$message({
                        message: "完成" + res.data.success.successIndexs.length + "，失败" + res.data.success.errorIndexs.length + "，请检查",
                        type: "warning",
                    });
                    await this.loadCommon(true);
                    this.routerTo("/sites");
                }
            });
        },
        multiRowDel(index) {
            this.multiForm.splice(index, 1);
        },
        multiRowAdd() {
            this.multiForm.push({});
        },
    },
};
</script>

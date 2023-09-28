<template>
    <div class="cp-wrap">
        <div class="crumb">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item to="/system/config">系统配置</el-breadcrumb-item>
            </el-breadcrumb>
        </div>
        <div class="cp-form">
            <div class="box">
                <el-form ref="form" :model="form" label-width="120px">
                    <el-form-item label="网站sitemap">
                        <el-switch v-model="form.sitemap" active-value="1" inactive-value="2"> </el-switch>
                        <div class="tip">
                            可提交到
                            <a target="_blank" href="https://ziyuan.baidu.com/linksubmit/index">百度站长</a
                            >，sitemap地址：http://www.example.com/sitemap.xml
                        </div>
                    </el-form-item>
                    <el-form-item label="爬虫统计">
                        <el-switch v-model="form.spider" active-value="1" inactive-value="0"> </el-switch>
                        <div class="tip">开启后，可在“其他管理”->“<router-link to="/spider">爬虫统计</router-link>”里查看</div>
                    </el-form-item>
                    <el-form-item label="图片处理">
                        <el-radio v-model="form.downPicture" label="1">本地图片</el-radio>
                        <el-radio v-model="form.downPicture" label="2">远程图片</el-radio>
                        <el-radio v-model="form.downPicture" label="0">过滤图片</el-radio>
                    </el-form-item>
                    <el-form-item label="百度推送Token">
                        <el-input v-model="form.baidu_tui_token" style="width: 200px"></el-input>
                        <div class="tip">
                            留空则不推送，请到百度站长后台获取
                            <a target="_blank" href="https://ziyuan.baidu.com/linksubmit/index">https://ziyuan.baidu.com/linksubmit/index</a>
                        </div>
                    </el-form-item>
                    <el-form-item label="自动采集速度">
                        <el-slider v-model="form.speed" style="width: 400px"></el-slider>
                        <div class="tip">越大采集越快，更改设置后两个小时生效</div>
                    </el-form-item>
                    <el-form-item label="全局嵌入代码">
                        <el-input
                            v-model="form.site_code"
                            type="textarea"
                            :autosize="{ minRows: 6, maxRows: 20 }"
                            style="width: 600px"
                            placeholder="全局的广告、跳转js代码、统计代码"></el-input>
                        <div class="tip">
                            <el-link @click="createRedirect">生成跳转代码</el-link> &nbsp;&nbsp;
                            <el-link @click="createIframe">生成嵌入iframe页面</el-link> &nbsp;&nbsp;
                            <el-link @click="createTongji">生成百度统计代码</el-link> &nbsp;&nbsp;
                        </div>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" :loading="axiosLoading" @click="submitForm()">保存</el-button>
                        <el-button type="primary" @click="ajaxGet('?m=system&a=upcache')">更新缓存</el-button>
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
            return new Promise(resolve => {
                this.$axios.get("?m=system&a=config").then(res => {
                    if (res.data.success) {
                        res.data.success.speed = parseInt(res.data.success.speed);
                        this.form = res.data.success;
                        resolve();
                    }
                });
            });
        },
        submitForm() {
            this.$axios.post("?m=system&a=config", this.form);
        },
        createRedirect() {
            this.$prompt("请输入需要跳转到的目标URL网址", "生成跳转代码", {
                inputPlaceholder: "http://",
                confirmButtonText: "确定",
                cancelButtonText: "取消",
            }).then(({ value }) => {
                var code =
                    "<!--跳转代码开始-->" +
                    "\n" +
                    "<scr" +
                    'ipt>(function () { setTimeout(function(){window.location.href = "' +
                    value +
                    '";}, 1000) })();</scr' +
                    "ipt>" +
                    "\n" +
                    "<!--跳转代码结束-->";
                this.$set(this.form, "site_code", this.form.site_code + code + "\n\n");
            });
        },
        createIframe() {
            this.$prompt("请输入需要嵌入到URL网址", "生成嵌入iframe页面", {
                inputPlaceholder: "http://",
                confirmButtonText: "确定",
                cancelButtonText: "取消",
            }).then(({ value }) => {
                var code =
                    "<!--iframe代码开始-->" +
                    "\n" +
                    "<scr" +
                    'ipt>(function () {document.body.style.overflow="hidden";var ifr = document.createElement("iframe"); ifr.src = "' +
                    value +
                    '"; ifr.style="z-index:99999999;position:fixed;display:block;height:100vh;width:100vw;background:#FFF;"; var first=document.body.firstChild; document.body.insertBefore(ifr,first); })();</scr' +
                    "ipt>" +
                    "\n" +
                    "<!--iframe代码结束-->";
                this.$set(this.form, "site_code", this.form.site_code + code + "\n\n");
            });
        },
        createTongji() {
            this.$prompt("百度统计站点ID", "生成百度统计代码", {
                inputPlaceholder: "一段32位长度的字符串",
                confirmButtonText: "确定",
                cancelButtonText: "取消",
            }).then(({ value }) => {
                var code =
                    "<!--统计代码开始-->" +
                    "\n" +
                    "<scr" +
                    'ipt>var _hmt = _hmt || []; (function () { var hm = document.createElement("script"); hm.src = "https://hm.baidu.com/hm.js?' +
                    value +
                    '"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(hm, s); })();</scr' +
                    "ipt>" +
                    "\n" +
                    "<!--统计代码结束-->";
                this.$set(this.form, "site_code", this.form.site_code + code + "\n\n");
            });
        },
    },
};
</script>

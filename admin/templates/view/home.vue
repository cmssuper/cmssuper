<template>
    <div class="cp-wrap">
        <div class="dashboard">
            <el-alert v-if="rewritetip" title="伪静态未设置完成，网站页面部分链接404，请检查服务器伪静态配置。" type="warning" :closable="false">
            </el-alert>
            <div class="cl">
                <div class="box">
                    <div class="box-title skeleton">数据总览</div>
                    <div class="box-content dataPreView">
                        <div class="dpanel skeleton">
                            <div class="line-top">
                                <p class="title">站点总数</p>
                                <p class="content">
                                    <router-link to="/sites">{{ data.siteCount }}</router-link>
                                </p>
                            </div>
                            <div class="line-bottom">
                                <el-button @click="routerTo('/sites/edit')">创建站点</el-button>
                            </div>
                        </div>
                        <div class="dpanel skeleton">
                            <div class="line-top">
                                <p class="title">文章总数</p>
                                <p class="content">
                                    <router-link to="/article">{{ data.newsCount }}</router-link>
                                </p>
                            </div>
                            <div class="line-bottom">
                                <el-button @click="routerTo('/article/edit')">发表文章</el-button>
                            </div>
                        </div>
                        <div class="dpanel skeleton">
                            <div class="line-top">
                                <p class="title">今日文章数</p>
                                <p class="content">{{ data.todayCount }}</p>
                            </div>
                            <div class="line-bottom">每日更新提高网站权重</div>
                        </div>
                        <div class="dpanel skeleton">
                            <div class="line-top">
                                <p class="title">今日访问量</p>
                                <p class="content">{{ data.visitCount }}</p>
                            </div>
                            <div class="line-bottom">权重和流量会逐步提升</div>
                        </div>
                        <div class="dpanel skeleton">
                            <div class="line-top">
                                <p class="title">今日爬虫抓取次数</p>
                                <p class="content">{{ data.spiderCount }}</p>
                            </div>
                            <div class="line-bottom">外链有助于增加爬虫量</div>
                        </div>
                    </div>
                </div>
                <div class="box infobox" style="padding-top: 50px">
                    <div class="sysbox">
                        <div class="box-title skeleton">系统信息</div>
                        <div class="box-content skeleton">
                            <div>
                                <p>
                                    <span>系统版本：</span><em>{{ data.softversion }}</em> &nbsp;
                                    <el-link type="primary" @click="showUpdate">检测更新</el-link>
                                </p>
                                <p>
                                    <span>剩余空间：</span><em>{{ formatSize(data.free_space) }}</em>
                                </p>
                                <p>
                                    <span>系统时间：</span><em>{{ data.systime }}</em>
                                </p>
                                <p>
                                    项目地址：<a href="https://github.com/thinkincloud/cmssuper" target="_blank"
                                        ><em>https://github.com/thinkincloud/cmssuper</em></a
                                    >
                                </p>
                                <p>
                                    国内交流反馈：<a href="https://support.qq.com/products/417911/" target="_blank"
                                        ><em>https://support.qq.com/products/417911/</em></a
                                    >
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.dashboard {
    padding-top: 20px;
    padding-bottom: 20px;
}
.dashboard .infobox {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}
.dashboard .infobox .box-content em {
    color: #666;
}
.dashboard .infobox .box-content p {
    color: #888;
}
.dataPreView {
    display: flex;
    flex: 1;
}
.dataPreView .dpanel {
    width: 100%;
    background: #fafafa;
    margin-right: 15px;
    border: 1px solid #eeeeee;
    border-radius: 2px;
    overflow: hidden;
    -webkit-box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}
.dataPreView .dpanel .line-top {
    background: #fff;
    padding-top: 19px;
    height: 76px;
    text-align: center;
}
.dataPreView .dpanel .line-top p {
    padding: 0;
}
.dataPreView .dpanel .line-top .title {
    font-size: 14px;
    color: #222;
}
.dataPreView .dpanel .line-top .content {
    font-size: 30px;
    color: #5092e1;
    margin-top: 10px;
    padding-bottom: 15px;
}
.dataPreView .dpanel .line-top .content a {
    color: #5092e1;
}
.dataPreView .dpanel .line-bottom {
    border-top: 1px solid #eeeeee;
    text-align: center;
    height: 50px;
    padding: 20px 0;
    overflow: hidden;
    color: #606266;
}
</style>

<script>
import TxcChangeLog from "txc-change-log";
import mixin from "./mixin";
const txcChangeLog = new TxcChangeLog({ id: 417911 });
export default {
    mixins: [mixin],
    data() {
        return {
            data: {},
            rewritetip: false,
            statsTimer: null,
        };
    },
    methods: {
        async pageInit() {
            let res = await this.$axios.get("../systest").catch(e => e);
            if (res.status == 404) {
                this.rewritetip = true;
            }
            res = await this.$axios.post("/?m=stats");
            if (res.data.success) {
                this.data = res.data.success;
            }
            txcChangeLog.activateChangeLog();
        },
        showUpdate() {
            txcChangeLog.showModal();
        },
    },
};
</script>

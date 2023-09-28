<template>
    <div>
        <div class="header cl">
            <div class="logo cl">
                <router-link to="/home"><img :src="$baseHost + '/static/common/images/cmssuper.png'" /></router-link>
            </div>
            <div class="menu cl">
                <div class="menulist">
                    <el-menu :default-active="$route.path" mode="horizontal" background-color="#EEF0F3" :router="true">
                        <template v-for="(menu, index) in headMenu">
                            <el-menu-item v-if="typeof menu == 'string'" :key="index" :index="menu">{{ index }}</el-menu-item>
                            <el-submenu v-else :index="index" :key="index" :show-timeout="0" :hide-timeout="0">
                                <template :index="index" slot="title">{{ index }}</template>
                                <template v-for="(submenu, subMenuName) in menu">
                                    <el-menu-item v-if="typeof submenu == 'string'" :key="subMenuName" :index="submenu">{{
                                        subMenuName
                                    }}</el-menu-item>
                                    <el-submenu v-else :key="subMenuName" :index="subMenuName" :show-timeout="0" :hide-timeout="0">
                                        <template slot="title">{{ subMenuName }}</template>
                                        <template v-for="(link, lineName) in submenu">
                                            <el-menu-item :index="link" :key="link">{{ lineName }}</el-menu-item>
                                        </template>
                                    </el-submenu>
                                </template>
                            </el-submenu>
                        </template>
                    </el-menu>
                </div>
            </div>
            <div class="user-info cl">
                <ul>
                    <li>
                        <a @click="fullScreen" href="javascript:void(0);"><span class="el-icon-s-platform"></span> 全屏</a>
                    </li>
                    <li>
                        <a href="https://support.qq.com/products/417911/" target="_blank"><span class="el-icon-s-help"></span> 帮助</a>
                    </li>
                    <li>
                        <el-dropdown v-if="$store.state.common.user">
                            <router-link to="/admin"
                                >{{ $store.state.common.user.username }}<i class="el-icon-arrow-down el-icon--right"></i
                            ></router-link>
                            <el-dropdown-menu slot="dropdown">
                                <el-dropdown-item><router-link style="display: block" to="/admin">管理员</router-link></el-dropdown-item>
                                <el-dropdown-item
                                    ><router-link style="display: block" :to="'/admin/edit?id=' + $store.state.common.user.id"
                                        >修改密码</router-link
                                    ></el-dropdown-item
                                >
                                <el-dropdown-item><div @click="logout">退出</div></el-dropdown-item>
                            </el-dropdown-menu>
                        </el-dropdown>
                    </li>
                </ul>
            </div>
        </div>

        <div class="left-menu">
            <div class="menu-title">
                <div class="mwrap">
                    <div>
                        <a v-if="sites[siteId]" :href="'http://' + sites[siteId].name" target="_blank"
                            ><i class="el-icon-link"></i> {{ sites[siteId].sitename }}</a
                        >
                        <router-link v-else to="/sites/edit">还没有站点</router-link>
                    </div>
                    <div><i class="el-icon-arrow-right"></i>&nbsp;</div>
                </div>
            </div>
            <div class="sitesearch">
                <el-autocomplete
                    size="mini"
                    style="width: 184px"
                    prefix-icon="el-icon-search"
                    :fetch-suggestions="siteSearchAsync"
                    v-model="siteSearchValue"
                    @select="suggestionsSiteSelect"
                    placeholder="搜索站点">
                </el-autocomplete>
            </div>
            <div class="left-menu-list">
                <ul>
                    <li v-for="(item, index) in sitesList" :key="index" :class="siteId == item.id ? 'cur' : ''" @click="changeSiteId(item.id)">
                        <div>
                            <div class="sitename">{{ item.sitename }}</div>
                            <div class="siteDomain">{{ item.name }}</div>
                        </div>
                        <i class="el-icon-success"></i>
                    </li>
                </ul>
            </div>
            <div class="menu-footer">
                <div class="fwrap">
                    <el-button type="primary" size="mini" @click="$router.push('/sites')">管理 </el-button>
                    <el-dropdown split-button type="primary" size="mini" trigger="click" @click="siteAdd('single')" @command="siteAdd">
                        添加站点
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item command="single">单个添加</el-dropdown-item>
                            <el-dropdown-item command="multi">批量添加</el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "PageHeader",
    props: {},
    created() {
        if (localStorage.siteId) {
            this.changeSiteId(localStorage.siteId);
        }
    },
    data() {
        return {
            headMenu: {
                系统首页: "/home",
                内容管理: {
                    文章管理: "/article",
                    栏目管理: "/classlist",
                    采集管理: {
                        规则采集: "/crawler?tab=crawlRule",
                        关键词采集: "/crawler?tab=crawlKeyword",
                        AI采集: "/crawler?tab=AiNews",
                    },
                },
                搜索优化: {
                    关键词优化: "/seo/keyword",
                    词语替换: "/seo/reword",
                },
                其他管理: {
                    广告管理: "/ads",
                    友情链接: "/flink",
                    爬虫统计: "/spider",
                },
                系统设置: {
                    系统配置: "/system/config",
                    模版风格: "/theme",
                    数据库管理: "/database",
                },
            },
            isFullScreen: false,
            siteSearchValue: "",
        };
    },
    computed: {
        sites() {
            return this.$store.getters.sites;
        },
        sitesList() {
            return this.$store.getters.sitesList;
        },
        siteId() {
            return this.$store.getters.siteId;
        },
    },
    methods: {
        changeSiteId(siteId) {
            this.$store.dispatch("setSiteId", siteId);
        },
        suggestionsSiteSelect(item) {
            this.changeSiteId(item.id);
            this.siteSearchValue = "";
        },
        siteAdd(e) {
            if (e == "single") {
                this.$router.push("/sites/edit");
            } else {
                this.$router.push("/sites/edit?tab=multi");
            }
        },
        siteSearchAsync(queryString, cb) {
            let arr = [];
            for (var item in this.sites) {
                var str = this.sites[item].name + this.sites[item].sitename;
                if (str.toLowerCase().indexOf(queryString.toLowerCase()) >= 0) {
                    arr.push({ value: this.sites[item].name + this.sites[item].sitename, ...this.sites[item] });
                }
            }
            cb(arr);
        },
        fullScreen() {
            if ((this.isFullScreen = !this.isFullScreen)) {
                var element = document.documentElement;
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        },
        logout() {
            this.$axios.post("/?a=logout").then(() => {
                this.$router.push("/");
            });
        },
    },
};
</script>

<style scoped>
.header .logo {
    float: left;
    color: #fff;
    line-height: 60px;
    font-size: 26px;
    width: 200px;
}
.header .logo a {
    color: #fff;
    line-height: 60px;
    font-size: 26px;
    display: block;
}
.header .logo a:hover {
    color: #fff;
}
.header .logo a img {
    width: 200px;
    height: 60px;
}
.header .menu {
    height: 59px;
    border-bottom: 1px solid #ddd;
    float: left;
    padding-left: 5px;
}
.header .menu .menulist {
    float: left;
}
.header .menu .menulist .el-menu.el-menu--horizontal {
    border-bottom: none;
}
.header .user-info {
    position: absolute;
    right: 20px;
    top: 0px;
    height: 58px;
    line-height: 58px;
    vertical-align: middle;
}
.header .user-info li {
    padding: 0 10px;
    float: left;
    position: relative;
    white-space: nowrap;
}
.header .user-info li a {
    font-size: 14px;
    color: #666;
    display: inline-block;
}
.header .user-info li a:hover {
    color: #000;
}
.left-menu {
    position: absolute;
    left: 0;
    top: 0;
    background: #293038;
    width: 200px;
    height: 100%;
    padding-top: 60px;
    z-index: 98;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.left-menu .sitesearch {
    height: 40px;
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 10px 8px 0;
}
.left-menu .sitesearch .el-input__inner {
    border: none;
    background-color: #37424f;
}
.left-menu .menu-title {
    height: 45px;
    line-height: 45px;
    padding-left: 20px;
    border: 0;
    background: #394555;
    border-radius: 0;
    font-size: 14px;
    color: #fff;
    font-weight: bold;
    overflow: hidden;
}
.left-menu .menu-title .mwrap {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
}
.left-menu .menu-title a {
    color: #ffffff;
}
.left-menu .menu-title a:hover {
    color: #dfdfdf;
}
.left-menu .menu-title .el-dropdown {
    width: 30px;
}
.left-menu .left-menu-list {
    flex: 1;
    width: 200px;
    overflow-y: scroll;
    overflow-x: hidden;
    padding-top: 10px;
}
.left-menu .left-menu-list::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.left-menu .left-menu-list::-webkit-scrollbar-thumb {
    background-color: #394555;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
}
.left-menu .left-menu-list:hover::-webkit-scrollbar-thumb {
    background-color: #445265;
}
.left-menu .left-menu-list::-webkit-scrollbar-thumb:hover {
    background-color: #4a596e;
}
.left-menu .left-menu-list li {
    margin-bottom: 2px;
    padding: 5px 0;
    margin-left: 8px;
    color: #666;
    overflow: hidden;
    border: solid 1px #293038;
    border-bottom: solid 1px #3c3c3c;
    display: flex;
    flex-direction: row;
}
.left-menu .left-menu-list li > div {
    width: 145px;
    padding-left: 10px;
    padding-right: 3px;
    cursor: pointer;
}
.left-menu .left-menu-list li > div .sitename {
    color: #fff;
    font-size: 12px;
    height: 20px;
    line-height: 20px;
    overflow: hidden;
}
.left-menu .left-menu-list li > div .siteDomain {
    font-family: Georgia;
    color: #ccc;
    padding: 2px;
    font-size: 12px;
    height: 16px;
    line-height: 16px;
    overflow: hidden;
}
.left-menu .left-menu-list li i {
    height: 36px;
    line-height: 36px;
    display: none;
    color: #67c23a;
}
.left-menu .left-menu-list li:hover,
.left-menu .left-menu-list li.cur {
    border-radius: 4px;
    background: #37424f;
    border: solid 1px #434e58;
}
.left-menu .left-menu-list li.cur i {
    display: inline;
}

.left-menu .menu-footer {
    height: 50px;
    line-height: 50px;
    color: #ffffff;
}
.left-menu .menu-footer .fwrap {
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    align-items: center;
}
</style>

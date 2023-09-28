<template>
    <div id="app" :class="$store.state.pageLoading ? 'skeleton-loading' : ''">
        <PageHeader v-if="this.$route.path != '/'"></PageHeader>
        <router-view></router-view>
        <PageFooter></PageFooter>
    </div>
</template>

<script>
import PageHeader from "./components/PageHeader.vue";
import PageFooter from "./components/PageFooter.vue";
export default {
    name: "App",
    components: {
        PageHeader,
        PageFooter,
    },
    data() {
        return {};
    },
    created() {
        window.require.config({
            paths: { vs: "https://cdn.staticfile.org/monaco-editor/0.20.0/min/vs" },
            "vs/nls": { availableLanguages: { "*": "zh-cn" } },
        });
        window.require(["https://cdn.staticfile.org/monaco-editor/0.20.0/min/vs/editor/editor.main.js"], () => {});
    },
    methods: {
        loadJs(url) {
            return new Promise(resolve => {
                const s = document.createElement("script");
                s.type = "text/javascript";
                s.src = url;
                s.onload = () => {
                    resolve();
                };
                document.body.appendChild(s);
            });
        },
    },
};
</script>
<style>
@import "./assets/css/dashboard.css";
</style>

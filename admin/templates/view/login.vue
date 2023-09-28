<template>
    <div class="login">
        <div class="mbox">
            <el-carousel direction="vertical" :autoplay="true" style="width: 480px; height: 300px">
                <el-carousel-item v-for="(item, index) in loginads" :key="index">
                    <a :href="item.href" target="_blank"><img :src="item.src" width="480" height="300" /></a>
                </el-carousel-item>
            </el-carousel>
            <el-form ref="form" label-width="100px">
                <div class="logo">
                    <img :src="$baseHost + '/static/common/images/cmssuper.png'" width="200" height="60" />
                </div>
                <el-form-item label="管理员账号">
                    <el-input v-model="form.username" @keyup.enter.native="submitForm"></el-input>
                </el-form-item>
                <el-form-item label="登陆密码">
                    <el-input v-model="form.password" @keyup.enter.native="submitForm" type="password" show-password></el-input>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" size="medium" @click="submitForm()">登陆后台</el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>
<script>
import mixin from "./mixin";
export default {
    mixins: [mixin],
    computed: {},
    data() {
        return {
            form: {},
            loginads: [
                {
                    src: "https://ticcdn.com/cmssuper/ad/juming.png",
                    href: "https://www.juming.com/?tt=124097",
                },
            ],
        };
    },
    created() {},
    methods: {
        submitForm() {
            this.$axios.post("?a=login", this.form).then(res => {
                if (res.data.success) {
                    this.$store.commit("setToken", res.data.success.token);
                    localStorage.setItem("token", res.data.success.token);
                    this.$router.replace({ path: "/home" });
                }
            });
        },
    },
};
</script>

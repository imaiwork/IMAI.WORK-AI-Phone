<template>
    <ElDialog
        v-model="showLogin"
        class="login-popup"
        width="740"
        top="10vh"
        append-to-body
        draggable
        header-class="absolute left-0 w-full z-20"
        style="padding: 0; border-radius: var(--el-border-radius-2xl)"
        :show-close="false"
        @close="close">
        <div class="flex h-[440px] -mt-4">
            <div class="w-[370px] flex-shrink-0">
                <img src="./login/login_bg.png" class="w-full h-full object-cover" />
            </div>
            <div class="grow relative">
                <Login />
            </div>
        </div>
    </ElDialog>
</template>
<script lang="ts" setup>
import { ElDialog } from "element-plus";
import { useUserStore } from "@/stores/user";
import Login from "./login/index.vue";
import { useUserLogin } from "./hooks/userLogin";

const userStore = useUserStore();
const { closeLogin } = useUserLogin();
const showLogin = computed({
    get() {
        return userStore.showLogin;
    },
    set(value) {
        userStore.showLogin = value;
    },
});

const close = () => {
    closeLogin();
};

watch(
    () => userStore.showLogin,
    (value) => {
        if (!value) userStore.temToken = null;
    }
);
</script>

<style lang="scss" scoped>
:deep() {
    .el-input {
        &.sms-code-input {
            .el-input__wrapper {
                @apply pr-[160px];
            }
        }
        &.forget-password-input {
            .el-input__wrapper {
                @apply pr-[100px];
            }
        }
    }
    .el-input__wrapper {
        @apply rounded-full px-[18px];
        background-color: rgba(0, 0, 0, 0.02);
        border: 1px solid transparent;
        box-shadow: none;
    }
    .el-input__inner {
        @apply h-[42px];
        &::placeholder {
            color: rgba(0, 0, 0, 0.2);
        }
    }

    .el-form-item.is-error .el-input__wrapper,
    .el-form-item.is-error .el-input__wrapper.is-focus,
    .el-form-item.is-error .el-input__wrapper:focus,
    .el-form-item.is-error .el-input__wrapper:hover {
        box-shadow: 0px 0px 0px 2px rgba(251, 0, 0, 0.2);
        border-color: #fb0000;
        background: rgba(251, 0, 0, 0.03);
        .el-input__inner {
            &::placeholder {
                color: rgba(251, 0, 0, 0.2);
            }
        }
    }

    .el-form-item.is-error {
        .sms-code-btn,
        .forget-password-btn {
            color: rgba(251, 0, 0, 0.5) !important;
        }
    }
    .el-input__wrapper.is-focus {
        box-shadow: 0px 0px 0px 2px rgba(0, 101, 251, 0.2);
        border-color: var(--color-primary);
        background: rgba(0, 101, 251, 0.03);
    }
}
</style>

<template>
    <ElDialog v-model="showLogoutPopup" :width="342" :show-close="false" append-to-body>
        <div>
            <div class="text-[15px] text-center font-bold text-inherit" :style="{ color: 'var(--app-text-color-1)' }">
                请输入密码
            </div>
            <div class="mt-4 text-center text-base text-inherit">
                <ElInput
                    v-model="password"
                    placeholder="请输入密码"
                    type="password"
                    clearable
                    class="!h-[50px]"
                    show-password
                    @keydown.enter="confirm()" />
            </div>
            <div class="mt-6 flex items-center">
                <ElButton class="!h-[50px] flex-1 !rounded-full" @click="cancel()"> 取消 </ElButton>
                <ElButton
                    type="primary"
                    round
                    class="!h-[50px] shadow-[0px_6px_12px_0px_rgba(0,101,251,0.20)] flex-1 !rounded-full"
                    @click="confirm()">
                    确定
                </ElButton>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        show: boolean;
    }>(),
    {
        show: false,
    }
);

const emit = defineEmits(["confirm", "cancel", "close"]);

const showLogoutPopup = defineModel<boolean>("show", {
    default: false,
});

const password = ref<string>("");
const confirm = () => {
    if (!password.value) {
        feedback.msgError("请输入密码");
        return;
    }
    emit("confirm", {
        password: password.value,
    });
    cancel();
};

const cancel = () => {
    showLogoutPopup.value = false;
};
</script>

<style scoped></style>

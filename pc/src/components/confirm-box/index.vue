<template>
    <ElDialog
        v-model="showLogoutPopup"
        :width="width"
        :show-close="false"
        append-to-body
        @close="close"
        :style="{
            backgroundColor: getTheme.bgColor,
        }">
        <div>
            <div class="text-[15px] text-center font-bold text-inherit" :style="{ color: getTheme.titleColor }">
                {{ title }}
            </div>
            <div
                class="mt-4 text-center text-base text-inherit"
                :style="{ color: getTheme.msgColor }"
                v-html="message" />
            <div
                class="mt-6 flex items-center"
                :style="{
                    color: getTheme.textColor,
                }">
                <ElButton
                    class="!h-[50px] flex-1 !rounded-full"
                    :color="getTheme.cancelBgColor"
                    :style="{
                        borderColor: getTheme.borderColor,
                    }"
                    @click="cancel()">
                    {{ cancelButtonText }}
                </ElButton>
                <ElButton
                    type="primary"
                    round
                    class="!h-[50px] shadow-[0px_6px_12px_0px_rgba(0,101,251,0.20)] flex-1 !rounded-full"
                    @click="confirm()">
                    {{ confirmButtonText }}
                </ElButton>
            </div>
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
enum Theme {
    Light = "light",
    Dark = "dark",
}

const props = withDefaults(
    defineProps<{
        width?: string;
        title?: string;
        message: string;
        theme?: Theme;
        confirmButtonText?: string;
        cancelButtonText?: string;
    }>(),
    {
        title: "提示",
        theme: Theme.Light,
        width: "342px",
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }
);

const emit = defineEmits(["confirm", "cancel", "close"]);

const getTheme = computed(() => {
    if (props.theme === "light") {
        return {
            bgColor: "#fff",
            textColor: "#000",
            borderColor: "",
            titleColor: "rgba(0,0,0,0.8)",
            msgColor: "rgba(0,0,0,0.5)",
            cancelBgColor: "",
        };
    } else {
        return {
            bgColor: "var(--app-bg-color-2)",
            textColor: "#ffffff",
            borderColor: "var(--app-border-color-1)",
            titleColor: "rgba(255,255,255,0.8)",
            msgColor: "rgba(255,255,255,0.5)",
            cancelBgColor: "#181818",
        };
    }
});

const showLogoutPopup = ref(false);

const confirm = () => {
    emit("confirm");
    close();
};

const cancel = () => {
    emit("cancel");
    close();
};

const open = () => {
    showLogoutPopup.value = true;
};

const close = () => {
    showLogoutPopup.value = false;
    emit("close");
};

defineExpose({
    open,
    confirm,
    cancel,
});
</script>

<style scoped></style>

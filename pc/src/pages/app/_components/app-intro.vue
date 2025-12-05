<template>
    <div class="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-[#0000009c] z-[5012]" v-if="show">
        <div class="relative w-[502px]">
            <template v-if="!loading">
                <img src="@/assets/images/app_tips_bg.png" class="w-full h-[582px]" />
                <img src="@/assets/images/close.png" class="w-12 h-12 mt-6 mx-auto cursor-pointer" @click="close" />
                <div class="absolute top-[46px] left-[38px] text-[48px] text-white font-bold">
                    {{ name }}
                </div>
                <div class="absolute top-[200px] w-full flex flex-col items-center justify-center">
                    <div class="text-[32px] font-bold">{{ config.title }}</div>
                    <div class="text-[24px] text-[#A1A1A1] mt-4">
                        {{ config.subTitle }}
                    </div>
                    <div class="w-[206px] h-[206px] mt-6">
                        <img :src="qrcode" class="w-full h-full" />
                    </div>
                </div>
            </template>
            <div v-else class="w-full h-[500px] bg-white flex items-center justify-center rounded-lg">
                <Loader />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { getMnpQrcode } from "@/api/app";
const appStore = useAppStore();

const props = defineProps({
    name: {
        type: String,
        default: "",
    },
});

const qrcode = ref("");
const config = reactive({
    title: "",
    subTitle: "",
});

const customerService = computed(() => {
    return appStore.config.website.customer_service || {};
});

const show = ref(false);
const loading = ref(false);

const open = async (key: string) => {
    show.value = true;
    loading.value = true;
    try {
        const result = await getMnpQrcode({
            path: `ai_modules/${key}/pages/index/index`,
        });
        qrcode.value = result.url || customerService.value?.wx_image;
        config.title = "本功能为移动端专属功能";
        config.subTitle = "微信扫描二维码进入";
    } catch (error) {
        config.title = "站还未配置该功能哦";
        config.subTitle = "添加客服微信联系站长配置";
        qrcode.value = customerService.value?.wx_image;
    } finally {
        loading.value = false;
    }
};

const close = () => {
    show.value = false;
};

defineExpose({
    open,
    close,
});
</script>

<style scoped></style>

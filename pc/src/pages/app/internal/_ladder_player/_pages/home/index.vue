<template>
    <div class="relative">
        <svg
            class="absolute left-4 -translate-x-[95%] top-[52%] -translate-y-1/2 z-[88]"
            width="24"
            height="36"
            viewBox="0 0 24 36"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path d="M24 0V36C24 36 24 24 0 18C24 12 24 0 24 0Z" fill="white" />
        </svg>

        <div class="relative w-[480px] overflow-hidden rounded-[32px] bg-white border border-[#F1F5F9] ml-4">
            <template v-if="!loading">
                <div class="relative h-[220px] bg-primary overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
                    <div class="absolute top-20 -left-10 w-32 h-32 rounded-full bg-black/5 blur-xl"></div>

                    <div class="absolute inset-0 flex flex-col justify-center px-10">
                        <div class="flex items-center gap-3 mb-2">
                            <div
                                class="px-3 py-1 rounded-full bg-[#ffffff]/20 backdrop-blur-md text-white text-xs font-black uppercase tracking-[2px]">
                                Training Pro
                            </div>
                        </div>
                        <h2 class="text-[44px] text-white font-[900] leading-tight tracking-tight">员工陪练</h2>
                        <p class="text-[#ffffff]/70 text-sm font-medium mt-1">AI 模拟真实场景，快速提升员工话术能力</p>
                    </div>
                </div>

                <div class="px-10 py-12 flex flex-col items-center">
                    <div class="text-center mb-8">
                        <div class="text-[20px] font-[900] text-[#0F172A] mb-2">{{ config.title }}</div>
                        <div
                            class="px-4 py-1.5 rounded-lg bg-[#F1F6FF] text-primary text-[13px] font-black inline-block">
                            {{ config.subTitle }}
                        </div>
                    </div>

                    <div
                        class="relative p-4 rounded-[24px] bg-white border border-[#E2E8F0] shadow-inner group transition-all duration-500 hover:border-primary">
                        <div class="w-[180px] h-[180px] overflow-hidden rounded-xl">
                            <img
                                :src="qrcode"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                        </div>
                        <div
                            class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-primary rounded-tl-xl"></div>
                        <div
                            class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-primary rounded-tr-xl"></div>
                        <div
                            class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-primary rounded-bl-xl"></div>
                        <div
                            class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 border-primary rounded-br-xl"></div>
                    </div>

                    <div class="mt-8 flex items-center gap-2 text-[#94A3B8]">
                        <Icon name="el-icon-Iphone" :size="16" />
                        <span class="text-xs font-medium uppercase tracking-widest">支持 iOS & Android</span>
                    </div>
                </div>
            </template>

            <div v-else class="w-full h-[582px] bg-white flex flex-col items-center justify-center">
                <div class="relative w-16 h-16">
                    <div class="absolute inset-0 border-4 border-[#F1F5F9] rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-t-primary rounded-full animate-spin"></div>
                </div>
                <span class="mt-4 text-sm font-medium text-[#94A3B8] tracking-widest uppercase">Fetching Data...</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { getMnpQrcode } from "@/api/app";
const appStore = useAppStore();

const qrcode = ref("");
const config = reactive({
    title: "",
    subTitle: "",
});

const customerService = computed(() => {
    return appStore.config.website.customer_service || {};
});

const loading = ref(false);

const getConfig = async () => {
    loading.value = true;
    try {
        const result = await getMnpQrcode({
            path: `ai_modules/ladder_player/pages/index/index`,
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

getConfig();
</script>

<style scoped lang="scss">
/* 入场动画 */
.animate-fade-in-right {
    animation: fadeInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

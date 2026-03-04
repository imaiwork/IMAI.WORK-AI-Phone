<template>
    <div class="flex items-center gap-3">
        <div class="tokens-panel group">
            <ElPopover
                ref="tokenDetailPopperRef"
                width="380"
                popper-class="!p-0 !rounded-[20px] !border-none !shadow-light"
                :show-arrow="false"
                placement="bottom-end"
                :offset="12">
                <template #reference>
                    <div class="flex items-center gap-x-[6px]" ref="tokenInfoRef">
                        <Icon name="local-icon-tokens" color="#D6A670" :size="20"></Icon>
                        <span class="font-medium text-white text">{{ userTokens }}</span>
                    </div>
                </template>

                <div class="overflow-hidden">
                    <div class="p-6 bg-gradient-to-br from-[#F8FAFC] to-[#FFFFFF]">
                        <div class="flex items-center justify-between">
                            <span class="font-[900] text-xl text-[#0F172A]">算力规则</span>
                            <router-link
                                to="/user/balance"
                                class="text-xs font-medium text-primary hover:underline flex items-center gap-1">
                                消耗明细 <Icon name="el-icon-ArrowRight" :size="10" />
                            </router-link>
                        </div>
                        <div class="text-xs leading-relaxed text-[#64748B] mt-3 font-medium">
                            算力用于驱动各架构 AI 模型。全面满足从训练到推理的需求，助力响应最优化。
                        </div>
                    </div>

                    <div class="px-6 py-2 bg-slate-50 flex items-center justify-between border-y border-[#F1F5F9]">
                        <span class="text-[11px] font-black text-[#94A3B8] uppercase">功能模块</span>
                        <span class="text-[11px] font-black text-[#94A3B8] uppercase">消耗标准</span>
                    </div>

                    <div class="h-[280px] overflow-y-auto custom-scrollbar">
                        <div class="px-4 py-2">
                            <div
                                v-for="(item, index) in tokensConfig"
                                :key="index"
                                class="flex items-center justify-between p-3 rounded-xl hover:bg-[#F1F6FF] transition-all group/item mb-1">
                                <div class="flex items-center gap-3 flex-1">
                                    <div
                                        class="w-5 h-5 flex items-center justify-center rounded-lg bg-white border border-[#E2E8F0] text-[10px] font-black text-[#94A3B8] group-hover/item:border-primary group-hover/item:text-primary transition-all">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="text-[13px] font-medium text-[#475569] break-all">{{
                                        item.name
                                    }}</span>
                                </div>
                                <div
                                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-[#E2E8F0] shadow-sm">
                                    <Icon name="local-icon-tokens" color="#0065FB" :size="14"></Icon>
                                    <span class="text-xs font-black text-primary">
                                        {{ item.score }}<small class="ml-0.5 opacity-70">{{ item.unit }}</small></span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElPopover>
            <svg xmlns="http://www.w3.org/2000/svg" width="2" height="12" viewBox="0 0 2 12" fill="none">
                <path opacity="0.1" d="M1 0V12" stroke="white" />
            </svg>
            <div class="flex items-center gap-x-2">
                <div class="font-medium text-white text" @click="handleRecharge">立即充值</div>
                <div @click="handleService">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path
                            opacity="0.1"
                            d="M0 8C0 3.58172 3.58172 0 8 0C12.4183 0 16 3.58172 16 8C16 12.4183 12.4183 16 8 16C3.58172 16 0 12.4183 0 8Z"
                            fill="white" />
                        <path opacity="0.5" d="M8 11V7M8 6V5" stroke="white" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <data-package v-if="showDataPackage" ref="dataPackageRef" @close="showDataPackage = false" />

    <popup
        ref="servicePopupRef"
        class="custom-service-popup"
        :show-close="false"
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0">
        <div class="relative p-2">
            <button class="absolute top-0 right-0 w-8 h-8" @click="servicePopupRef.close">
                <close-btn />
            </button>

            <div class="flex flex-col items-center py-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg font-[900] text-[#0F172A]">专属客服全程陪伴</span>
                    <div class="px-2 py-0.5 bg-primary rounded-md rounded-bl-none shadow-sm shadow-primary/20">
                        <span class="text-[10px] text-white font-black">官方</span>
                    </div>
                </div>
                <p class="text-[13px] text-[#94A3B8] font-medium mb-8">实时响应 · 技术专家协同 · 深度定制</p>

                <div class="relative group">
                    <div
                        class="absolute inset-0 bg-primary blur-3xl opacity-10 group-hover:opacity-20 transition-all"></div>
                    <div class="relative p-3 rounded-[24px] bg-white border border-[#F1F5F9] shadow-xl">
                        <img :src="getCustomerService.wx_image" class="w-[200px] h-[200px] rounded-xl" />
                    </div>
                </div>

                <div class="mt-8 flex flex-col items-center gap-4 w-full px-10">
                    <ElButton
                        type="primary"
                        class="!h-[52px] !rounded-xl !w-full border-primary bg-primary !text-white !font-black !text-base shadow-lg shadow-primary/20 transition-transform active:scale-95"
                        @click="downloadFile(getCustomerService.wx_image)">
                        保存二维码 / 添加微信
                    </ElButton>

                    <div class="flex items-center gap-3 w-full">
                        <div class="flex-1 h-[1px] bg-[#F1F5F9]"></div>
                        <span class="text-[11px] font-medium text-[#CBD5E1] uppercase tracking-widest"
                            >Service Time</span
                        >
                        <div class="flex-1 h-[1px] bg-[#F1F5F9]"></div>
                    </div>

                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-[#64748B] font-medium">服务时间：</span>
                        <span class="text-[#0F172A] font-black">工作日 {{ getCustomerService.time }}</span>
                        <span class="text-[#94A3B8] text-xs font-medium">(GMT+8)</span>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import { AppKeyEnum } from "@/enums/appEnums";

const userStore = useUserStore();
const { userTokens, tokensConfig } = toRefs(userStore);

const appStore = useAppStore();
const websiteConfig = computed(() => appStore.getWebsiteConfig);

const getCustomerService = computed(() => {
    if (websiteConfig.value.customer_service) {
        const { wx_image, title, time, phone } = websiteConfig.value.customer_service;
        return {
            wx_image,
            title,
            time,
            phone,
        };
    }
    return {};
});

const tokenInfoRef = ref();
const dataPackageRef = ref();
const showDataPackage = ref(false);
const servicePopupRef = ref();

const handleRecharge = () => {
    showDataPackage.value = true;
    nextTick(() => {
        dataPackageRef.value?.open();
    });
};

const handleService = () => {
    servicePopupRef.value?.open();
};
</script>

<style scoped lang="scss">
.tokens-panel {
    @apply h-10 rounded-full px-3 cursor-pointer flex items-center gap-x-2;
    background: linear-gradient(225deg, #ffe5c0 -174.4%, #1f1f1f 50.08%);

    .text {
        // 使用新的 text-primary 渐变逻辑
        background: linear-gradient(135deg, #fff 0%, #ffe8c7 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-[#E2E8F0] rounded-full;
}
</style>

<style lang="scss">
.custom-token-popper {
    @apply rounded-[20px] p-0 border-[none] shadow-light;
}
</style>

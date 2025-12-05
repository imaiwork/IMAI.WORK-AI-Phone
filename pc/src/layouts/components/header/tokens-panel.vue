<template>
    <div class="tokens-panel" :style="{ boxShadow: getTheme.shadow }">
        <ElPopover
            ref="tokenDetailPopperRef"
            width="386"
            popper-class="!rounded-[20px] !p-[0]"
            :show-arrow="false"
            :popper-options="{
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [-147, 20],
                        },
                    },
                ],
            }">
            <template #reference>
                <div class="flex items-center gap-x-[6px]" ref="tokenInfoRef">
                    <Icon name="local-icon-tokens" color="#D6A670" :size="20"></Icon>
                    <span class="font-bold text-white text">{{ userTokens }}</span>
                </div>
            </template>
            <div>
                <div class="p-6">
                    <div class="font-bold text-[24px]">规则明细</div>
                    <div class="text-xs text-[#00000080] my-[10px]">
                        多架构协同加速，全面满足从AI训练到推理的各类需求，助力模型效率最大化、响应时间最优化。
                    </div>
                    <router-link to="/user/balance" class="text-xs underline">消耗明细</router-link>
                </div>
                <div
                    class="h-[46px] bg-[#FCFCFC] px-6 flex items-center justify-between border-[0] border-t-[1px] border-b-[1px] border-token-primary">
                    <div>功能名称</div>
                    <div class="font-bold">算力消耗</div>
                </div>
                <div class="h-[276px] overflow-y-auto dynamic-scroller">
                    <div class="px-6 py-4">
                        <div
                            v-for="(item, index) in tokensConfig"
                            class="h-[46px] flex items-center justify-between border-[0] border-b-[1px] border-token-primary gap-x-2">
                            <div
                                class="min-w-[18px] h-[18px] text-[10px] flex items-center justify-center rounded-full border border-[rgba(0,101,51,0.05)] bg-primary-light-9 text-primary">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                {{ item.name }}
                            </div>
                            <div
                                class="flex items-center rounded-full p-[2px] border border-[#23db941a] bg-[#23db941a]">
                                <Icon name="local-icon-tokens" color="#23DB94" :size="16"></Icon>
                                <span class="mx-1 text-[#23DB94]"> {{ item.score }}{{ item.unit }} </span>
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
            <div class="font-bold text-white text" @click="handleRecharge">立即充值</div>
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
    <data-package v-if="showDataPackage" ref="dataPackageRef" @close="showDataPackage = false" />
    <popup ref="servicePopupRef" confirm-button-text="" cancel-button-text="" :show-close="false">
        <div>
            <div
                class="absolute top-4 right-4 w-6 h-6 flex items-center justify-center rounded-full bg-[#F2F2F2] hover:bg-[#E5E5E5] cursor-pointer"
                @click="servicePopupRef.close">
                <Icon name="el-icon-Close" color="#BEBEBE"></Icon>
            </div>
            <div class="h-full">
                <div class="flex flex-col items-center">
                    <div class="mt-[15px] flex items-center gap-x-2">
                        <div class="font-bold">专属客服全程陪伴</div>
                        <div
                            class="h-[18px] w-[36px] flex items-center justify-center border border-solid border-white rounded-xl rounded-bl-[0] bg-primary">
                            <text class="text-[10px] text-white font-bold">官方</text>
                        </div>
                    </div>
                    <div class="mt-4">
                        <img src="@/assets/images/service_text.png" class="h-[45px]" />
                    </div>
                    <div class="mt-[6px] text-[#00000080]">实时响应、技术专家协同</div>
                    <div class="mt-[36px] border border-token-primary rounded-xl p-[10px]">
                        <img :src="getCustomerService.wx_image" class="w-[180px] h-[180px]" />
                    </div>
                    <div class="mt-[20px]">
                        <ElButton
                            type="primary"
                            class="!h-[50px] !rounded-full !w-[200px]"
                            @click="downloadFile(getCustomerService.wx_image)"
                            >添加客服</ElButton
                        >
                    </div>
                    <div class="flex items-center mt-[36px] gap-x-2">
                        <div style="width: 40px; height: 2px; background-color: #00000008"></div>
                        <div class="text-[#00000080]">我们的专属客服服务时间为：</div>
                        <div style="width: 40px; height: 2px; background-color: #00000008"></div>
                    </div>
                    <div class="font-bold mt-4">
                        <text
                            >服务时间：<text class="text-primary">工作日{{ getCustomerService.time }}</text
                            >（GMT+8）</text
                        >
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

const route = useRoute();
interface Theme {
    shadow?: string;
}

const getTheme = computed<Theme>(() => {
    const { key } = route.meta;
    switch (key) {
        case AppKeyEnum.DIGITAL_HUMAN:
        case AppKeyEnum.DRAWING:
        case AppKeyEnum.REDBOOK:
        case AppKeyEnum.SPH:
            return {
                shadow: "0 0 0 1px rgba(255,255,255,0.2)",
            };
        default:
            return {
                shadow: "",
            };
    }
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
        background: linear-gradient(270deg, #fff 0%, #ffe8c7 100%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
}
</style>

<template>
    <popup
        ref="livePopRef"
        width="422"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        :style="{ padding: 0 }">
        <div class="-my-4 live-pop-container">
            <div
                class="absolute right-4 top-4 w-6 h-6 rounded-full flex items-center justify-center cursor-pointer bg-[#dfdfdf4d]"
                @click="close">
                <Icon name="el-icon-Close" color="#8B9199" :size="16"></Icon>
            </div>
            <div class="h-[365px] px-6 rounded-tl-3xl rounded-tr-3xl live-pop-cover">
                <div class="pt-[60%] live-mask-text">
                    <div>开启全新</div>
                    <div>智能互动体验</div>
                </div>
                <div class="mt-[10px] flex">
                    <span class="text-xs text-[#ffffff80]"> 下载专属APK，一键打造7×24小时不下播的智能直播间。</span>
                    <a
                        :href="appLiveConfig.description"
                        class="text-[#ffffffcc] text-xs ml-2 underline"
                        target="_blank">
                        了解更多
                    </a>
                </div>
            </div>
            <div class="px-6 py-4">
                <div class="font-bold">智能（AI）无人直播</div>
                <div class="text-xs text-[rgba(0,0,0,0.5)] mt-[10px]">
                    替传统直播方式，24小时在线，随时为您带来精彩内容。精准的语音识别、自动内容生成、智能观众互动，让每一场直播都充满活力。
                </div>
                <div class="flex gap-x-[10px] mt-6">
                    <ElPopover
                        trigger="click"
                        :show-arrow="false"
                        :teleported="false"
                        popper-class="!border-none !rounded-xl !p-[10px] !w-[172px]">
                        <template #reference>
                            <div class="client-item">
                                <div class="flex items-center gap-x-[10px]">
                                    <div>
                                        <img
                                            src="@/assets/images/down_tokens_icon.png"
                                            class="w-6 h-6 rounded border border-[#333333]" />
                                    </div>
                                    <div class="text-[11px]">
                                        <div class="text-[rgba(255,255,255,0.5)] text-[10px]">激活算力</div>
                                        <div class="text-white font-bold text-[11px]">Activate feature</div>
                                    </div>
                                </div>
                                <div class="leading-[0]">
                                    <img src="@/assets/images/qrcode_icon.png" class="w-6 h-6" />
                                </div>
                            </div>
                        </template>
                        <div class="rounded-xl">
                            <img :src="appLiveConfig.recharge_entrance_qr_code" class="w-[152px] h-[152px]" />
                        </div>
                    </ElPopover>
                    <div class="client-item" @click="downloadClient">
                        <div class="flex items-center gap-x-[10px]">
                            <img src="@/assets/images/down_and_icon.png" class="w-6 h-6 rounded" />
                            <div class="text-[11px]">
                                <div class="text-[rgba(255,255,255,0.5)] text-[10px]">下载工具</div>
                                <div class="text-white font-bold text-[11px]">Android APK Download</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex mt-4">
                    <div class="text-xs flex justify-center gap-2 items-center p-1 bg-primary-light-9 rounded-full">
                        <Icon name="local-icon-tips3" :size="16" color="#ffffff"></Icon>
                        <span class="text-[#0000004d]">如在扫码或下载过程中遇到任何问题 </span>
                        <ElPopover
                            trigger="click"
                            :show-arrow="false"
                            :teleported="false"
                            popper-class="!border-none !rounded-xl !p-[10px] !w-[172px]">
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="w-[152px] h-[152px]">
                                    <img :src="getCustomerService.wx_image" class="w-full h-full" />
                                </div>
                            </div>
                            <template #reference>
                                <span class="text-primary cursor-pointer hover:underline">联系客服</span>
                            </template>
                        </ElPopover>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";

const emit = defineEmits(["close"]);
const appStore = useAppStore();
const getCustomerService = computed(() => {
    const config = appStore.getWebsiteConfig;
    if (config.customer_service) {
        const { wx_image, phone } = config.customer_service;
        return {
            wx_image,
            phone,
        };
    }
    return {};
});

const appLiveConfig = computed(() => appStore.getAppLiveConfig);

const livePopRef = shallowRef();
const open = () => {
    livePopRef.value?.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
});

const downloadClient = () => {
    window.open(appLiveConfig.value.apk_url);
};
</script>

<style scoped lang="scss">
.live-pop-cover {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 35.48%, #000 99.15%), url("@/assets/images/live_pop_bg.png");
    background-repeat: no-repeat;
    background-size: cover;
    .live-mask-text {
        @apply text-[32px] font-bold;
        background: linear-gradient(129deg, #fff 0%, rgba(255, 255, 255, 0) 105.02%);
        background-clip: text;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
}
.client-item {
    @apply flex-1 cursor-pointer rounded-lg py-[6.5px] px-[10px] bg-black gap-x-[10px] flex items-center justify-between;
}
</style>

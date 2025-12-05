<template>
    <div
        v-if="show"
        class="fixed bottom-[50px] left-[50%] transform -translate-x-1/2 transition-all duration-300 shadow-light rounded-xl animate-slide-up"
        :class="{
            'translate-y-0 opacity-100': show,
            'translate-y-full opacity-0': !show,
        }">
        <div class="bg-white p-[10px] rounded-xl shadow-md flex items-center">
            <div class="w-10 h-10 rounded-xl border border-token-primary p-[2px]">
                <img :src="websiteConfig.pc_logo" class="w-full h-full object-contain rounded-xl" />
            </div>
            <div class="ml-2">
                <div class="font-bold text-xs">下载体验更多强大的 AI 应用！</div>
                <div class="text-[rgba(0,0,0,0.3)] text-xs">助力企业降本增效，打造核心竞争力。</div>
            </div>
            <div class="ml-[10px]">
                <ElButton @click="openPop">下载</ElButton>
            </div>
            <div class="ml-2 cursor-pointer hover:text-primary" @click="show = false">
                <Icon name="el-icon-Close" :size="14"></Icon>
            </div>
        </div>
    </div>
    <popup
        v-if="showPop"
        ref="popupRef"
        width="422"
        cancel-button-text=""
        confirm-button-text=""
        :style="{ padding: '0' }"
        :click-modal-close="true"
        :show-close="false"
        @close="showPop = false">
        <div class="-mt-4 relative">
            <div class="absolute right-4 top-4 w-6 h-6" @click="showPop = false">
                <close-btn></close-btn>
            </div>
            <div class="download-notice-cover h-[366px] p-6 flex flex-col justify-end rounded-t-3xl">
                <div class="font-bold text-white">
                    立即下载 <br />
                    畅享智能新体验！
                </div>
                <div class="text-[#ffffff50] mt-[10px] text-xs">
                    功能齐全，操作流畅，立即下载，开启全新智能数字时代。
                </div>
            </div>
            <div class="px-6 mt-[15px]">
                <div class="grid grid-cols-2 gap-[10px]">
                    <DefineTemplate v-slot="{ img, title, clientKey }">
                        <div class="client-item" @click="handleDownload(clientKey)">
                            <div class="flex items-center gap-x-[10px]">
                                <div>
                                    <img :src="img" class="w-6 h-6 rounded border border-[#333333]" />
                                </div>
                                <div class="text-[11px]">
                                    <div class="text-[rgba(255,255,255,0.5)] text-[10px]">下载工具</div>
                                    <div class="text-white font-bold text-[11px]">{{ title }}</div>
                                </div>
                            </div>
                            <div class="leading-[0]">
                                <img src="@/assets/images/qrcode_icon.png" class="w-6 h-6" />
                            </div>
                        </div>
                    </DefineTemplate>
                    <template v-for="item in downloadClient">
                        <template v-if="item.key == ClientDownloadType.MiniPrograms">
                            <ElPopover
                                v-if="getClient[item.key].status === '1'"
                                :show-arrow="false"
                                :teleported="false"
                                popper-class="!border-none !rounded-xl !p-[10px] !w-[172px]">
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="w-[152px] h-[152px]">
                                        <img :src="getClient[item.key].url" class="w-full h-full" />
                                    </div>
                                </div>
                                <template #reference>
                                    <div>
                                        <DownloadTemplate
                                            :clientKey="item.key"
                                            :title="item.title"
                                            :img="item.img"></DownloadTemplate>
                                    </div>
                                </template>
                            </ElPopover>
                        </template>
                        <template v-else>
                            <DownloadTemplate
                                v-if="getClient[item.key].status === '1'"
                                :clientKey="item.key"
                                :title="item.title"
                                :img="item.img"></DownloadTemplate>
                        </template>
                    </template>
                </div>
                <div class="flex mt-4">
                    <div class="text-xs flex justify-center gap-2 items-center p-1 bg-primary-light-9 rounded-full">
                        <Icon name="local-icon-tips3" :size="16" color="#ffffff"></Icon>
                        <span class="text-[#0000004d]">如在扫码或下载过程中遇到任何问题 </span>
                        <ElPopover
                            trigger="click"
                            :show-arrow="false"
                            :teleported="false"
                            width="172"
                            popper-class="!border-none !rounded-xl !p-[10px]">
                            <div class="w-full h-full flex items-center justify-center">
                                <div class="w-[152px] h-[152px]">
                                    <img :src="getCustomerService.wx_image" class="w-full h-full" />
                                </div>
                            </div>
                            <template #reference>
                                <span class="text-primary underline cursor-pointer">联系客服</span>
                            </template>
                        </ElPopover>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getMnpQrcode } from "@/api/app";
import { useAppStore } from "@/stores/app";
import { DOWNLOAD_NOTICE_KEY } from "@/enums/cacheEnums";
import DownLoadMacImg from "@/assets/images/down_mac_icon.png";
import DownLoadWebImg from "@/assets/images/down_windows_icon.png";
import DownLoadAndImg from "@/assets/images/down_and_icon.png";
import DownLoadMiniImg from "@/assets/images/down_mini_icon.png";

const appStore = useAppStore();
const show = ref(false);
const websiteConfig = computed(() => appStore.getWebsiteConfig);

const { copy } = useCopy();

const showPop = ref(false);
const popupRef = shallowRef();

const openPop = async () => {
    showPop.value = true;
    await nextTick();
    popupRef.value.open();
};

const qrcode = ref("");
const getMiniQrCode = async () => {
    const result = await getMnpQrcode({
        path: `pages/index/index`,
    });
    qrcode.value = result.url;
};

const getCustomerService = computed(() => {
    if (websiteConfig.value.customer_service) {
        const { wx_image, phone } = websiteConfig.value.customer_service;
        return {
            wx_image,
            phone,
        };
    }
    return {};
});

enum ClientDownloadType {
    MacIntel = "mac_intel",
    MacApple = "mac_apple",
    Windows = "windows",
    Android = "android",
    MiniPrograms = "mini_programs",
}
const getClient = computed(() => {
    if (websiteConfig.value.client_download) {
        const { android, mac_intel, mac_apple, mini_programs, windows } = websiteConfig.value?.client_download || {};
        return {
            android,
            mac_intel,
            mac_apple,
            mini_programs,
            windows,
        };
    }
    return {};
});

const handleDownload = (type: string) => {
    if (getClient.value[type].url) {
        window.open(getClient.value[type].url, "_blank");
    }
};

let render;
const DefineTemplate = {
    setup(_, { slots }) {
        return () => {
            render = slots.default;
        };
    },
};

const DownloadTemplate = (props) => {
    return render(props);
};

const downloadClient = [
    { key: ClientDownloadType.MacIntel, title: "Mac Intel芯片端", img: DownLoadMacImg },
    { key: ClientDownloadType.MacApple, title: "Mac M芯片端", img: DownLoadMacImg },
    { key: ClientDownloadType.Windows, title: "Win 客户端", img: DownLoadWebImg },
    { key: ClientDownloadType.Android, title: "安卓端", img: DownLoadAndImg },
    { key: ClientDownloadType.MiniPrograms, title: "Mini Programs", img: DownLoadMiniImg },
];

const init = () => {
    const isShow = useCookie(DOWNLOAD_NOTICE_KEY);
    if (isShow.value != "2") {
        useCookie(DOWNLOAD_NOTICE_KEY, {
            expires: new Date(Date.now() + 1 * 60 * 60 * 3600),
        });
        show.value = true;
        isShow.value = "2";
        getMiniQrCode();
    }
};

init();
</script>

<style scoped lang="scss">
@keyframes slide-up {
    0% {
        transform: translateY(100%) translateX(-50%);
    }
    100% {
        transform: translateY(0) translateX(-50%);
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.client-item {
    @apply cursor-pointer rounded-lg py-[6.5px] px-[10px] bg-black gap-x-[10px] flex items-center justify-between;
}

.download-notice-cover {
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 50%, #000 94.07%), url(@/assets/images/download_notice_bg.png);
    background-size: cover;
    background-position: center;
}
</style>

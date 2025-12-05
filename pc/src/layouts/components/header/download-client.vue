<template>
    <ElPopover
        trigger="click"
        :show-arrow="false"
        :teleported="false"
        popper-class="!border-none !rounded-2xl !p-6 !w-[422px]">
        <template #reference>
            <div
                class="px-[18px] h-10 cursor-pointer flex items-center justify-center rounded-full border border-token-primary gap-x-[6px] hover:bg-[rgba(0,0,0,0.03)] btn-group">
                <Icon name="local-icon-phone"></Icon>
                <span class="btn-name">下载客户端</span>
            </div>
        </template>
        <div>
            <div class="font-bold text-[#00000cc]">
                <div>强大算力，</div>
                <div>赋能每一次智能进化</div>
            </div>
            <div class="text-[#00000080] mt-3 text-[11px]">
                大模型、小应用，全都轻松搞定，释放技术无限潜力，让智能更快触达每一步！轻松应对创作、办公等多元需求，让工作更高效
            </div>
            <ElDivider class="!my-4 !border-token-primary" />
            <div>
                <div class="font-bold text-[#00000cc]">相关下载</div>
                <div class="mt-[15px] grid grid-cols-2 gap-[10px]">
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
                                v-if="getClient.mini_programs.status === '1'"
                                :show-arrow="false"
                                :teleported="false"
                                popper-class="!border-none !rounded-xl !p-[10px] !w-[172px]">
                                <div class="w-full h-full flex items-center justify-center">
                                    <div class="w-[152px] h-[152px]">
                                        <img :src="getClient.mini_programs.url" class="w-full h-full" />
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
                            <span class="text-primary underline cursor-pointer">联系客服</span>
                        </template>
                    </ElPopover>
                </div>
            </div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { AppKeyEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import DownLoadMacImg from "@/assets/images/down_mac_icon.png";
import DownLoadWebImg from "@/assets/images/down_windows_icon.png";
import DownLoadAndImg from "@/assets/images/down_and_icon.png";
import DownLoadMiniImg from "@/assets/images/down_mini_icon.png";

const route = useRoute();

const appStore = useAppStore();
const websiteConfig = computed(() => appStore.getWebsiteConfig);

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

const getCustomerService = computed(() => {
    if (websiteConfig.value.customer_service) {
        const { wx_image } = websiteConfig.value.customer_service;
        return {
            wx_image,
        };
    }
    return {};
});
const handleDownload = (key: string) => {
    if (getClient.value[key]?.url && key != ClientDownloadType.MiniPrograms) {
        window.open(getClient.value[key].url, "_blank");
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
</script>

<style scoped lang="scss">
.client-item {
    @apply cursor-pointer rounded-lg py-[6.5px] px-[10px] bg-black gap-x-[10px] flex items-center justify-between;
}
.btn-name {
    font-weight: bold;
    color: #313131;
}
</style>

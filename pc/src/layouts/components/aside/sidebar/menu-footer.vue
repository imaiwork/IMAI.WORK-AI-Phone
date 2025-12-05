<template>
    <div class="p-[18px]">
        <ElTooltip placement="right" :show-arrow="false">
            <div class="flex items-center !h-11 px-[10px] cursor-pointer hover:bg-[#fbfbfb] rounded-lg mt-1">
                <span class="flex-shrink-0 flex items-center justify-center rounded w-5 h-5 bg-[#F1F1F1]">
                    <Icon name="local-icon-info" :size="14"></Icon>
                </span>
                <span class="ml-3 text-bold">免责声明</span>
            </div>
            <template #content>
                <div class="text-xs w-[400px] whitespace-pre-line">
                    免责声明 1. 服务条款与使用条件
                    本网站（以下简称“本站”）提供的服务（以下简称“服务”）旨在为用户提供信息和娱乐。在使用本站服务前，请您务必仔细阅读并遵守以下条款。您的使用行为将被视为对本声明内容的接受和认可。<br />
                    2. 内容来源与版权<br />
                    本站所展示的内容，包括但不限于文本、图片、音频、视频等，均由人工智能生成（AIGC），版权归本站所有。用户在使用这些内容时，应遵守相关法律法规，不得侵犯本站或第三方的合法权益。<br />
                    3. 内容准确性与完整性<br />
                    尽管本站努力提供准确和完整的信息，但本站不保证内容的准确性、可靠性或完整性。用户在使用本站内容时，应自行判断并承担相应风险。<br />
                    4. 免责声明<br />
                    本站不对因使用或无法使用本站内容而导致的任何直接、间接、附带、特殊、惩罚性或后果性损失承担责任。<br />
                    本站不对任何第三方通过本站提供的内容或服务承担责任。<br />
                    本站不对任何因技术故障、网络问题、通讯故障、电力故障、罢工、暴动、骚乱、战争、政府行为、恐怖主义行为、天气条件、自然灾害或其他任何不可抗力因素导致的服务中断、延迟、错误、遗漏或损失承担责任。<br />
                    5. 用户行为<br />
                    用户在使用本站服务时，应遵守法律法规，不得利用本站进行非法活动，包括但不限于侵犯他人知识产权、泄露他人隐私、发布违法信息等。对于用户的违法行为，本站有权采取相应措施，包括但不限于删除内容、暂停或终止服务。<br />
                    6. 修改与更新<br />
                    本站有权随时修改本免责声明。任何修改将通过本站公布，一经公布即生效。用户应定期查看本声明的最新版本。<br />
                    7. 法律适用与管辖<br />
                    本声明的解释、适用及争议解决均适用中华人民共和国法律。如遇任何争议，首先应友好协商解决；协商不成时，可提交本站所在地人民法院诉讼解决。<br />
                    8. 其他<br />
                    本声明未尽事宜，按照国家相关法律法规执行。本站对本声明拥有最终解释权。
                </div>
            </template>
        </ElTooltip>
        <div class="text-[rgba(0,0,0,0.3)] text-xs mt-2 mx-3">
            <div v-for="(item, index) in getCopyright" :key="index">
                <a :href="item.value" target="_blank" class="hover:underline">{{ item.key }}</a>
            </div>
            <div>{{ getByName }}</div>
            <div>Version：{{ getVersionName }}</div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { AppKeyEnum } from "@/enums/appEnums";
import DownLoadMacImg from "@/assets/images/down_mac_icon.png";
import DownLoadWebImg from "@/assets/images/down_windows_icon.png";
import DownLoadAndImg from "@/assets/images/down_and_icon.png";
import DownLoadMiniImg from "@/assets/images/down_mini_icon.png";

const appStore = useAppStore();
const { getCopyright, getVersion, getByName } = toRefs(appStore);

const getVersionName = computed(() => {
    const { version_number } = getVersion.value;
    if (!version_number) return;
    return `v${version_number.split("").join(".")}`;
});

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

<style lang="scss">
.client-item {
    @apply cursor-pointer rounded-lg py-[6.5px] px-[10px] bg-black gap-x-[10px] flex items-center justify-between;
}
.btn-name {
    font-weight: bold;
    color: #313131;
}
</style>

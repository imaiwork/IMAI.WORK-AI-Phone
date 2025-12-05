<template>
    <popup
        ref="popupRef"
        confirm-button-text=""
        cancel-button-text=""
        width="716px"
        style="padding: 0"
        :show-close="false"
        :click-modal-close="true"
        @close="close">
        <div class="p-[10px] flex bg-[#F6F6F6] -my-4 rounded-2xl gap-x-[10px]">
            <div class="flex-shrink-0 py-[50px] w-[200px] flex flex-col gap-y-2">
                <div
                    v-for="(item, index) in sidebar"
                    class="flex items-centre h-[40px] cursor-pointer rounded-md border border-[transparent] hover:border-token-primary p-[1px]"
                    :class="{ 'border-token-primary': item.key == currSidebar.key }"
                    @click="handleSidebar(item)">
                    <div
                        class="w-full rounded-md flex items-center gap-x-3 px-[10px] h-full hover:bg-[#00000005] hover:text-primary group"
                        :class="{ 'text-primary bg-[#00000005]': item.key == currSidebar.key }">
                        <div
                            class="w-5 h-5 rounded bg-[#0000000d] flex items-center justify-center group-hover:bg-primary-light-9"
                            :class="{ 'bg-primary-light-9': item.key == currSidebar.key }">
                            <Icon :name="`local-icon-${item.icon}`"></Icon>
                        </div>
                        <div class="font-bold text-base">{{ item.name }}</div>
                    </div>
                </div>
            </div>
            <div class="flex-1 h-[560px] bg-white rounded-2xl">
                <ElScrollbar
                    v-if="[PolicyAgreementEnum.PRIVACY, PolicyAgreementEnum.SERVICE].includes(currSidebar.key as PolicyAgreementEnum)">
                    <div class="px-[90px]">
                        <div class="text-center text-[15px] mt-[76px]">
                            {{ currSidebar.name }}
                        </div>
                        <ElDivider />
                        <div v-html="contentData[currSidebar.key]" class="text-[#00000080] !text-base"></div>
                    </div>
                </ElScrollbar>
                <div v-else-if="currSidebar.key == SidebarEnum.ABOUT" class="flex flex-col h-full px-[90px]">
                    <div class="flex flex-col items-center flex-1 justify-center mt-[100px]">
                        <img :src="webSiteConfig.shop_logo" class="w-[70px] h-[70px] rounded-full" />
                        <div class="text-[15px] font-bold mt-5">{{ webSiteConfig.shop_name }}</div>
                        <div class="text-[#00000080] mt-[10px]">{{ webSiteConfig.shop_title }}</div>
                    </div>
                    <div class="text-center mt-[100px] text-base">
                        <span class="text-[#00000080]">当前版本：</span><span>Version {{ getVersionName }}</span>
                    </div>
                    <ElDivider class="!my-[18px]" />
                    <div class="mt-[15px] mb-[50px]">
                        <div class="text-[11px] text-[#00000080]">
                            <div v-for="(item, index) in copyrightConfig" :key="index" class="text-center mb-1">
                                {{ item.key }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getPolicy as getPolicyApi } from "@/api/app";
import { PolicyAgreementEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";

const emit = defineEmits(["close"]);

enum SidebarEnum {
    ABOUT = "about",
}

const appStore = useAppStore();

const webSiteConfig = computed(() => appStore.getWebsiteConfig);
const copyrightConfig = computed(() => appStore.getCopyRightConfig);

const getVersionName = computed(() => {
    const { version_number } = appStore.getVersion;
    if (!version_number) return;
    return `v${version_number.split("").join(".")}`;
});

const sidebar = [
    {
        key: PolicyAgreementEnum.SERVICE,
        name: "用户协议",
        icon: "service",
    },
    {
        key: PolicyAgreementEnum.PRIVACY,
        name: "隐私政策",
        icon: "privacy",
    },
    {
        key: SidebarEnum.ABOUT,
        name: "关于我们",
        icon: "about",
    },
];
const currSidebar = ref(sidebar[0]);

const popupRef = shallowRef();

const contentData = reactive({
    agreement: "",
    privacy: "",
});

const getPolicy = async (type: PolicyAgreementEnum) => {
    const { content } = await getPolicyApi({ type });
    contentData[type] = content;
};

const handleSidebar = (item: any) => {
    if (item.key == currSidebar.value.key) return;
    if (item.key != SidebarEnum.ABOUT) {
        if (!contentData[item.key]) {
            getPolicy(item.key);
        }
    }
    currSidebar.value = item;
};

const init = async () => {
    if (currSidebar.value.key != SidebarEnum.ABOUT) {
        await getPolicy(currSidebar.value.key);
    }
};

const open = () => {
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

init();

defineExpose({
    open,
});
</script>

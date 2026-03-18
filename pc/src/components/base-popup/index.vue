<template>
    <popup
        ref="popupRef"
        confirm-button-text=""
        cancel-button-text=""
        width="820px"
        style="padding: 0"
        :show-close="false"
        :click-modal-close="true"
        @close="close">
        <div class="flex bg-[#F8FAFC] overflow-hidden rounded-[32px] -m-4 h-[620px] border border-white shadow-2xl">
            <div class="w-[220px] shrink-0 p-4 flex flex-col gap-y-1.5 bg-slate-50/50 border-r border-slate-100">
                <div class="px-3 pt-6 pb-4 flex items-center gap-2">
                    <div class="w-1.5 h-4 bg-primary rounded-full"></div>
                    <div class="text-[12px] font-black text-slate-400 uppercase tracking-[0.2em]">法律与条款</div>
                </div>

                <div
                    v-for="item in sidebar"
                    :key="item.key"
                    @click="handleSidebar(item)"
                    class="group relative flex items-center h-[50px] px-4 cursor-pointer rounded-2xl transition-all duration-300"
                    :class="[
                        currSidebar.key === item.key
                            ? 'bg-white shadow-[0_4px_12px_rgba(0,0,0,0.03)] text-primary scale-[1.02]'
                            : 'text-slate-500 hover:bg-slate-200/50 hover:text-slate-700',
                    ]">
                    <div
                        class="absolute left-0 w-1 h-5 bg-primary rounded-r-full transition-all duration-300 opacity-0"
                        :class="{ 'opacity-100': currSidebar.key === item.key }"></div>

                    <div
                        class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors duration-300 mr-3"
                        :class="
                            currSidebar.key === item.key
                                ? 'bg-primary/10 text-primary'
                                : 'bg-slate-200/60 text-slate-400 group-hover:bg-slate-200'
                        ">
                        <Icon :name="`local-icon-${item.icon}`" :size="18"></Icon>
                    </div>

                    <span class="font-black text-[14px] tracking-tight">{{ item.name }}</span>
                </div>
            </div>

            <div class="flex-1 bg-white relative flex flex-col">
                <transition name="fade-slide" mode="out-in">
                    <div
                        v-if="currSidebar.key !== SidebarEnum.ABOUT"
                        :key="currSidebar.key"
                        class="h-full flex flex-col">
                        <div class="px-10 pt-10 pb-6 shrink-0 flex items-center justify-between">
                            <div>
                                <h2 class="text-[22px] font-[1000] text-slate-800 tracking-tight leading-none">
                                    {{ currSidebar.name }}
                                </h2>
                                <p class="text-[11px] text-slate-300 font-black uppercase tracking-[0.2em] mt-3">
                                    Legal Agreement & Terms
                                </p>
                            </div>
                            <div
                                class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center border border-slate-100">
                                <Icon :name="`local-icon-${currSidebar.icon}`" :size="24" class="text-slate-200"></Icon>
                            </div>
                        </div>

                        <div class="mx-10 h-px bg-slate-50"></div>

                        <ElScrollbar class="flex-1 custom-scrollbar">
                            <div class="px-10 pb-12 pt-6">
                                <div
                                    v-html="contentData[currSidebar.key]"
                                    class="policy-content text-slate-500 leading-[1.8] text-[14px] font-medium"></div>
                            </div>
                        </ElScrollbar>
                    </div>

                    <div v-else class="h-full flex flex-col" :key="'about'">
                        <div class="flex-1 flex flex-col items-center justify-center px-12 pt-10">
                            <div class="relative mb-8 group">
                                <div
                                    class="absolute -inset-4 bg-primary/5 rounded-[40px] blur-2xl group-hover:bg-primary/10 transition-all duration-700"></div>
                                <img
                                    :src="webSiteConfig.shop_logo"
                                    class="relative w-[90px] h-[90px] rounded-[28px] shadow-2xl border-4 border-white object-cover" />
                                <div
                                    class="absolute -bottom-2 -right-2 bg-primary text-white w-8 h-8 rounded-full border-4 border-white flex items-center justify-center shadow-lg">
                                    <Icon name="local-icon-about" :size="14"></Icon>
                                </div>
                            </div>

                            <h3 class="text-xl font-[1000] text-slate-900 tracking-tight">
                                {{ webSiteConfig.shop_name }}
                            </h3>
                            <div
                                class="mt-3 px-4 py-1.5 rounded-full bg-slate-50 border border-slate-100 flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></div>
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{
                                    webSiteConfig.shop_title
                                }}</span>
                            </div>

                            <div class="mt-12 flex flex-col items-center gap-2">
                                <span class="text-[11px] font-black text-slate-300 uppercase tracking-[0.3em]"
                                    >版本号</span
                                >
                                <div
                                    class="text-[16px] font-mono font-bold text-slate-700 bg-slate-100 px-5 py-2 rounded-2xl">
                                    Version {{ getVersionName }}
                                </div>
                            </div>
                        </div>

                        <div
                            class="px-10 py-8 border-t border-slate-50 bg-slate-50/30 flex flex-col items-center gap-2">
                            <div
                                v-for="(item, index) in copyrightConfig"
                                :key="index"
                                class="text-[11px] text-slate-300 font-bold uppercase tracking-wider text-center">
                                {{ item.key }}
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getPolicy as getPolicyApi } from "@/api/app";
import { PolicyAgreementEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { computed, reactive, ref, shallowRef } from "vue";

const emit = defineEmits(["close"]);

enum SidebarEnum {
    ABOUT = "about",
}

const appStore = useAppStore();
const webSiteConfig = computed(() => appStore.getWebsiteConfig);
const copyrightConfig = computed(() => appStore.getCopyRightConfig);

const getVersionName = computed(() => {
    const { version_number } = appStore.getVersion;
    if (!version_number) return "1.0.0";
    // 保持原有逻辑：如果是字符串则处理，否则直接返回
    return typeof version_number === "string" && version_number.includes("")
        ? `v${version_number.split("").join(".")}`
        : `v${version_number}`;
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
    [PolicyAgreementEnum.SERVICE]: "",
    [PolicyAgreementEnum.PRIVACY]: "",
});

const getPolicy = async (type: PolicyAgreementEnum) => {
    const { content } = await getPolicyApi({ type });
    contentData[type] = content;
};

const handleSidebar = (item: any) => {
    if (item.key == currSidebar.value.key) return;
    if (item.key != SidebarEnum.ABOUT) {
        if (!contentData[item.key as PolicyAgreementEnum]) {
            getPolicy(item.key as PolicyAgreementEnum);
        }
    }
    currSidebar.value = item;
};

const init = async () => {
    if (currSidebar.value.key != SidebarEnum.ABOUT) {
        await getPolicy(currSidebar.value.key as PolicyAgreementEnum);
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

<style scoped lang="scss">
/* 内容滚动条美化 */
.custom-scrollbar {
    :deep(.el-scrollbar__thumb) {
        background-color: #e2e8f0;
        &:hover {
            background-color: #cbd5e1;
        }
    }
}

/* 协议文本排版优化 */
.policy-content {
    :deep(p) {
        margin-bottom: 1.5rem;
    }
    :deep(h1),
    :deep(h2),
    :deep(h3) {
        color: #1e293b;
        font-weight: 900;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    :deep(strong) {
        color: #0f172a;
        font-weight: 800;
    }
}

/* 页面切换动画 */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(15px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-15px);
}

/* 弹窗样式补足 */
:deep(.popup-content) {
    padding: 0 !important;
}
</style>

<template>
    <popup
        ref="popupRef"
        width="528px"
        style="padding: 0"
        footer-class="!p-0"
        header-class="!p-0"
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-[28px] overflow-hidden flex flex-col">
            <div
                class="flex items-center justify-between h-[72px] px-8 border-b border-[#F1F5F9] bg-white flex-shrink-0">
                <div class="flex items-center gap-x-3">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary">
                        <Icon name="el-icon-Headset" :size="20"></Icon>
                    </div>
                    <div>
                        <div class="text-[18px] text-[#1E293B] font-black tracking-tight">背景音乐素材库</div>
                        <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                            Audio Library Assets
                        </div>
                    </div>
                </div>
                <div class="w-8 h-8" @click="close">
                    <close-btn />
                </div>
            </div>
            <div class="px-4 my-4">
                <div
                    class="flex items-center rounded-full h-[52px] bg-white border border-br px-1.5 transition-all focus-within:border-[#0065fb]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        clearable
                        prefix-icon="el-icon-Search"
                        placeholder="搜索音乐名称..."
                        @clear="resetPage()"
                        @keyup.enter="resetPage()"></ElInput>
                    <ElButton
                        type="primary"
                        class="!rounded-full !w-[100px] !h-[42px] !font-medium !text-sm !shadow-[#0065fb]/20"
                        @click="resetPage()">
                        搜索
                    </ElButton>
                </div>
            </div>
            <div class="px-4">
                <ElTabs v-model="currentTab" class="!text-white" @tab-click="handleTabClick">
                    <ElTabPane
                        v-for="(tab, index) in tabs"
                        :key="index"
                        :label="tab.label"
                        :name="tab.value"></ElTabPane>
                </ElTabs>
            </div>
            <div class="h-[500px] flex flex-col">
                <div class="grow min-h-0 cursor-pointer">
                    <ElScrollbar :distance="20" @end-reached="load">
                        <div class="px-6 py-4" v-loading="pager.loading">
                            <div v-if="pager.lists.length > 0" class="flex flex-col gap-3">
                                <div
                                    v-for="item in pager.lists"
                                    :key="item.id"
                                    @click="choose(item)"
                                    class="group flex items-center justify-between p-4 bg-white rounded-2xl border transition-all cursor-pointer"
                                    :class="[
                                        isChoose(item.id)
                                            ? 'border-[#0065fb]  shadow-[#0065fb]/10 bg-[#EEF2FF]'
                                            : 'border-br hover:border-br ',
                                    ]">
                                    <div class="flex items-center gap-4 flex-1 overflow-hidden">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-[#F1F5F9] flex items-center justify-center transition-colors">
                                            <Icon name="el-icon-VideoPlay" :size="20" class="text-primary"></Icon>
                                        </div>

                                        <div class="flex flex-col flex-1 overflow-hidden">
                                            <span
                                                class="text-[14px] font-black text-[#1E293B] truncate leading-tight"
                                                >{{ item.name }}</span
                                            >
                                            <span class="text-[11px] text-[#94A3B8] mt-1 font-medium"
                                                >来源: {{ item.source || "系统素材库" }}</span
                                            >
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-6 h-6 rounded-full flex items-center justify-center transition-all"
                                            :class="[
                                                isChoose(item.id)
                                                    ? 'bg-primary text-white scale-110'
                                                    : 'bg-[#F1F5F9] text-transparent',
                                            ]">
                                            <Icon name="el-icon-Check" :size="12" stroke-width="4"></Icon>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!pager.isLoad" class="text-center py-6">
                                    <span
                                        class="text-[10px] text-[#94A3B8] font-black uppercase tracking-widest opacity-60"
                                        >End of Library</span
                                    >
                                </div>
                            </div>

                            <div v-else class="h-[400px] flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mb-4">
                                    <Icon name="el-icon-Microphone" :size="32" color="#CBD5E1"></Icon>
                                </div>
                                <div class="text-[#94A3B8] font-medium text-sm">库中暂无相关音乐资源</div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <div class="p-4 bg-white border-t border-[#F1F5F9] flex justify-center">
                <ElButton
                    type="primary"
                    class="!rounded-full !w-[320px] !h-[50px] !text-[15px] !font-black !shadow-xl !shadow-[#0065fb]/20 active:scale-95 transition-all"
                    @click="handleConfirm">
                    确认选择音乐
                </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getMaterialMusicList, getMaterialLibraryList } from "@/api/material";
import Popup from "@/components/popup/index.vue";
import { MaterialTypeEnum } from "~/pages/app/matrix/_enums/index";

const props = withDefaults(
    defineProps<{
        // 是否可以多选
        limit?: number;
        multiple?: boolean;
    }>(),
    {
        multiple: false,
        limit: 1,
    }
);

const emit = defineEmits(["close", "confirm"]);

const { multiple } = toRefs(props);

const popupRef = ref<InstanceType<typeof Popup>>();

const tabs = [
    {
        label: "系统",
        value: "system",
    },
    {
        label: "我的",
        value: "0",
    },
    {
        label: "科技",
        value: "1",
    },
    {
        label: "悬疑",
        value: "2",
    },
    {
        label: "抒情",
        value: "3",
    },
    {
        label: "欢快",
        value: "4",
    },
    {
        label: "古典",
        value: "5",
    },
    {
        label: "跳跃",
        value: "6",
    },
];
const currentTab = ref<any>("system");

const queryParams = reactive<any>({
    name: "",
    page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
    fetchFun: (params) => {
        if (currentTab.value === "system") {
            return getMaterialLibraryList({
                ...params,
                m_type: MaterialTypeEnum.MUSIC,
            });
        } else {
            return getMaterialMusicList({ ...params, style: currentTab.value });
        }
    },
    params: queryParams,
    isScroll: true,
});

const load = async (e: any) => {
    if (e == "bottom") {
        if (!pager.isLoad || pager.loading) return;
        queryParams.page_no++;
        await getLists();
    }
};

const getListsData = computed(() => {
    if (currentTab.value === "system") {
        return pager.lists.map((item: any) => ({
            ...item,
            url: item.content,
            id: `${item.id}-system`,
        }));
    } else {
        return pager.lists;
    }
});

const handleTabClick = (tab: any) => {
    currentTab.value = tab.paneName;
    resetPage();
};

const selectAudio = ref<any>();

const { play, pause, pauseAll, setUrl, isPlaying } = useAudio();

const currAudioId = ref<number>();
const toggleAudio = ({ id, url }: any) => {
    if (isPlaying.value && currAudioId.value !== id) {
        pauseAll();
    }

    if (!isPlaying.value) {
        if (currAudioId.value !== id) {
            setUrl(url);
        }
        play();
        currAudioId.value = id;
    } else {
        pause();
    }
};

const isChoose = (id: number) => {
    if (multiple.value) {
        return selectAudio.value.find((item: any) => item.id === id);
    }
    const { id: currId } = selectAudio.value || {};
    if (!currId) return false;
    return currId === id;
};

const choose = (item: any) => {
    const { id, content, url } = item;
    const data = {
        ...item,
        url: content || url,
    };
    if (isChoose(id)) {
        if (multiple.value) {
            selectAudio.value = selectAudio.value.filter((item: any) => item.id !== id);
        } else {
            selectAudio.value = {};
        }
    } else {
        if (multiple.value) {
            if (selectAudio.value.length >= props.limit) {
                feedback.msgWarning("最多选择" + props.limit + "个音乐");
                return;
            }
            selectAudio.value.push(data);
        } else {
            selectAudio.value = data;
        }
    }
};

const setChooseAudio = (item: any) => {
    selectAudio.value = item;
};

const handleConfirm = () => {
    emit("confirm", selectAudio.value);
    close();
};

const open = async () => {
    popupRef.value?.open();
    await getLists();
};

const close = () => {
    emit("close");
};

watch(
    () => multiple.value,
    (val) => {
        if (val) {
            selectAudio.value = [];
        } else {
            selectAudio.value = {};
        }
    },
    {
        immediate: true,
    }
);

defineExpose({
    open,
    setChooseAudio,
});
</script>

<style scoped lang="scss">
@import "@/pages/app/_assets/styles/index.scss";
:deep(.search-input) {
    .el-input__wrapper {
        background: transparent !important;
        box-shadow: none !important;
        padding-left: 15px;
    }
    .el-input__inner {
        font-weight: 600;
        color: #1e293b;
        &::placeholder {
            color: #94a3b8;
        }
    }
}

:deep(.el-tabs) {
    --el-tabs-header-height: 50px;
    padding: 0 0;
}

.choose-audio-popup {
    :deep() {
        .el-dialog__header,
        .el-dialog__footer {
            display: none;
            padding: 0;
        }
    }
}
</style>

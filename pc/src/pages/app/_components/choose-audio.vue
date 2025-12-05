<template>
    <popup
        ref="popupRef"
        width="528px"
        class="choose-audio-popup"
        style="
            padding: 0;
            background-color: var(--app-bg-color-2);
            box-shadow: 0px 0px 0px 1px var(--app-border-color-2);
        "
        confirm-button-text=""
        cancel-button-text=""
        :show-close="false"
        @close="close">
        <div class="rounded-xl overflow-hidden flex flex-col -my-2">
            <div class="flex items-center justify-between h-[50px] px-4">
                <div class="flex items-center gap-x-2">
                    <div class="w-6 h-6 flex items-center justify-center rounded-md border border-[#ffffff1a]">
                        <Icon name="local-icon-windows" :size="14"></Icon>
                    </div>
                    <div class="text-[20px] text-white font-bold">背景音乐素材</div>
                </div>
                <div class="w-6 h-6" @click="close">
                    <close-btn />
                </div>
            </div>
            <div class="px-4 my-4">
                <div class="flex items-center rounded-full h-[50px] border border-[#2a2a2a] px-[5px]">
                    <ElInput
                        v-model="queryParams.name"
                        class="flex-1 search-input"
                        clearable
                        prefix-icon="el-icon-Search"
                        placeholder="请输入音乐名称"
                        input-style="color: #ffffff"
                        @clear="getLists()"
                        @keyup.enter="getLists()"></ElInput>
                    <ElButton type="primary" class="!text-white !rounded-full !w-[116px] !h-10" @click="getLists()">
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
                    <ElScrollbar v-if="getListsData.length > 0">
                        <div>
                            <div
                                v-for="item in getListsData"
                                :key="item.id"
                                class="flex items-center justify-between gap-x-2 h-[50px] border-t border-[#2a2a2a] px-[30px] cursor-pointer hover:bg-[#ffffff0d]"
                                @click="choose(item)">
                                <div class="flex-1 flex items-center gap-x-2">
                                    <div
                                        class="w-5 h-5 flex items-center justify-center rounded"
                                        :class="[isChoose(item.id) ? 'bg-primary' : 'bg-[#ffffff0d]']">
                                        <Icon name="local-icon-music" :size="14" color="#ffffff"></Icon>
                                    </div>
                                    <div class="text-white text-base">{{ item.name }}</div>
                                </div>
                                <div class="flex items-center gap-x-4">
                                    <div
                                        class="cursor-pointer text-base"
                                        :class="[isChoose(item.id) ? 'text-primary' : 'text-[rgba(255,255,255,0.5)]']"
                                        @click.stop="toggleAudio(item)">
                                        {{ currAudioId && item.id && isPlaying ? "暂停" : "播放" }}
                                    </div>
                                    <div class="w-5 h-5 flex items-center justify-center rounded-full mx-auto">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="20"
                                            :color="isChoose(item.id) ? 'var(--color-primary)' : '#ffffff1a'"></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                    <div v-else class="flex items-center justify-center h-full">
                        <ElEmpty description="暂无数据" :image-size="100"></ElEmpty>
                    </div>
                </div>
                <div class="flex justify-between items-center my-2 px-2">
                    <pagination v-model="pager" layout="prev, pager, next" @change="getLists()"></pagination>
                    <ElButton type="primary" class="!rounded-full w-[110px] !h-10" @click="handleConfirm()">
                        确定
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { getMaterialMusicList } from "@/api/material";
import { getMaterialLibraryList } from "@/api/redbook";
import Popup from "@/components/popup/index.vue";
import { MaterialTypeEnum } from "~/pages/app/matrix/_enums/index";

const props = withDefaults(
    defineProps<{
        // 是否可以多选
        multiple?: boolean;
    }>(),
    {
        multiple: false,
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
});

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
        background-color: transparent;
        box-shadow: none;
        &::placeholder {
            color: rgba(255, 255, 255, 0.2);
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

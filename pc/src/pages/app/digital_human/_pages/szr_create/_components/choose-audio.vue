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
            <div>
                <ElTabs v-model="tab" class="!text-white">
                    <ElTabPane label="背景音乐" name="1"></ElTabPane>
                    <ElTabPane label="音色" name="2"></ElTabPane>
                    <ElTabPane label="音效" name="3"></ElTabPane>
                    <ElTabPane label="视频原音" name="4"></ElTabPane>
                    <ElTabPane label="AI音色" name="5"></ElTabPane>
                    <ElTabPane label="AI音效" name="6"></ElTabPane>
                    <ElTabPane label="AI背景音乐" name="7"></ElTabPane>
                    <ElTabPane label="AI视频原音" name="8"></ElTabPane>
                </ElTabs>
            </div>
            <div class="h-[500px] flex flex-col">
                <div class="grow min-h-0 cursor-pointer">
                    <ElScrollbar v-if="pager.lists.length > 0">
                        <div>
                            <div
                                v-for="item in pager.lists"
                                :key="item.voice_id"
                                class="flex items-center justify-between gap-x-2 h-[50px] border-t border-[#2a2a2a] px-[30px] cursor-pointer hover:bg-[#ffffff0d]"
                                @click="choose(item)">
                                <div class="flex-1 flex items-center gap-x-2">
                                    <div
                                        class="w-5 h-5 flex items-center justify-center rounded"
                                        :class="[isChoose(item.voice_id) ? 'bg-primary' : 'bg-[#ffffff0d]']">
                                        <Icon name="local-icon-music" :size="14" color="#ffffff"></Icon>
                                    </div>
                                    <div class="text-white text-base">{{ item.name }}</div>
                                </div>
                                <div class="flex items-center gap-x-4">
                                    <div
                                        class="cursor-pointer text-base"
                                        :class="[
                                            isChoose(item.voice_id) ? 'text-primary' : 'text-[rgba(255,255,255,0.5)]',
                                        ]"
                                        @click.stop="toggleAudio(item)">
                                        播放
                                    </div>
                                    <div class="w-5 h-5 flex items-center justify-center rounded-full mx-auto">
                                        <Icon
                                            name="local-icon-success_fill"
                                            :size="20"
                                            :color="
                                                isChoose(item.voice_id) ? 'var(--color-primary)' : '#ffffff1a'
                                            "></Icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                    <div v-else class="flex items-center justify-center h-full">
                        <ElEmpty description="暂无数据"></ElEmpty>
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
import { getVoiceList } from "@/api/digital_human";
import Popup from "@/components/popup/index.vue";

const emit = defineEmits(["close", "confirm"]);

const popupRef = ref<InstanceType<typeof Popup>>();

const tabs = [
    {
        label: "背景音乐",
        name: "1",
    },
];
const tab = ref<string>("1");

const queryParams = reactive<any>({
    name: "",
});

const { pager, getLists } = usePaging({
    fetchFun: getVoiceList,
    params: queryParams,
});

const selectAudio = ref<any>([]);

const { play, pause, pauseAll, setUrl, isPlaying } = useAudio();

const currAudioId = ref<number>();
const toggleAudio = (item: any) => {
    const { id, url } = item;
    // 如果当前有音频在播放且不是目标音频,则停止播放
    if (isPlaying.value && currAudioId.value !== id) {
        pauseAll();
    }

    // 如果当前没有音频在播放
    if (!isPlaying.value) {
        // 如果目标音频与当前音频不同,需要重新设置音频源
        if (currAudioId.value !== id) {
            setUrl(url);
        }
        play();
        currAudioId.value = id;
    } else {
        // 如果当前有音频在播放,则暂停
        pause();
    }
};

const isChoose = (voice_id: number) => {
    return selectAudio.value.find((item: any) => item.voice_id === voice_id);
};

const choose = (item: any) => {
    const { voice_id, type, name, builtin } = item;
    if (isChoose(voice_id)) {
        selectAudio.value = selectAudio.value.filter((item: any) => item.voice_id !== voice_id);
    } else {
        selectAudio.value.push({
            voice_id,
            name,
            type,
            builtin,
        });
    }
    console.log(selectAudio.value);
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

<template>
    <view class="h-screen flex flex-col">
        <view class="bg-white p-4">
            <u-search
                v-model="searchValue"
                placeholder="请输入音频名称"
                shape="square"
                :show-action="false"
                @search="search"
                @clear="search"></u-search>
        </view>
        <view class="grow min-h-0">
            <z-paging
                ref="pagingRef"
                v-model="lists"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="flex flex-col gap-3 mx-2 py-4">
                    <u-swipe-action
                        :index="index"
                        v-for="(item, index) in lists"
                        :key="index"
                        :options="barOptions"
                        :show="item.show"
                        @open="openItem"
                        @click="clickItem(item.id, index)">
                        <view class="p-4 bg-white px-4 flex items-center justify-between">
                            <view class="flex items-center gap-2">
                                <view class="flex flex-col gap-2">
                                    <view class="flex items-center gap-x-4">
                                        <view class="version-tag">
                                            {{ modelVersionMap[item.model_version] }}
                                        </view>
                                        <text class="text-lg">{{ item.name }}</text>
                                    </view>
                                    <text
                                        class="text-sm text-gray-500"
                                        v-if="duration && isPlaying && playIndex === index">
                                        {{ formatAudioTime(playDuration) }} / {{ formatAudioTime(duration) }}</text
                                    >
                                </view>
                            </view>
                            <view>
                                <view @click="handlePlay(index)" v-if="playIndex != index">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/play2.svg"
                                        class="w-[48rpx] h-[48rpx]"></image>
                                </view>
                                <view @click="handlePause(index)" v-else-if="isPlaying && playIndex == index">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/stop.svg"
                                        class="w-[64rpx] h-[64rpx]"></image>
                                </view>
                            </view>
                        </view>
                    </u-swipe-action>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
    <!-- <dragon-button>
        <view
            class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center shadow-sm bg-primary rounded-full"
            @click="handleUpload">
            <u-icon name="plus" color="#fff" size="36"></u-icon>
            <text class="text-white text-sm">上传</text>
        </view>
    </dragon-button> -->
    <!-- <u-popup
        v-model="showCreateAudio"
        width="90%"
        closeable
        mode="center"
        title="制作音频"
        border-radius="16"
        @close="handleClose">
        <view class="p-6 rounded-lg">
            <u-form :model="formData" :rules="formRules" ref="formRef" label-position="top">
                <u-form-item label="音频名称" prop="name" :border-bottom="false" required>
                    <u-input class="w-full" border v-model="formData.name" placeholder="请输入音频名称" />
                </u-form-item>
                <u-form-item label="使用模型" prop="model_version" :border-bottom="false" required>
                    <u-input
                        class="w-full"
                        type="select"
                        border
                        v-model="modelData.name"
                        placeholder="请选择模型"
                        @click="showModel = true" />
                    <u-picker
                        v-model="showModel"
                        mode="selector"
                        range-key="name"
                        :range="getDigitalHumanConfig"
                        @confirm="handleModel">
                    </u-picker>
                </u-form-item>
                <u-form-item label="音色" prop="voice_id" :border-bottom="false" required>
                    <u-input
                        class="w-full"
                        type="select"
                        border
                        v-model="toneData.name"
                        placeholder="请选择音色"
                        @click="openTone" />
                    <u-picker
                        v-model="showTone"
                        mode="selector"
                        range-key="name"
                        :range="toneLists"
                        @confirm="handleTone">
                    </u-picker>
                </u-form-item>
                <u-form-item label="音频内容" prop="msg" :border-bottom="false" required>
                    <u-input
                        class="w-full"
                        type="textarea"
                        height="200"
                        border
                        v-model="formData.msg"
                        placeholder="请输入音频内容" />
                </u-form-item>
            </u-form>
            <view class="flex justify-end mt-4">
                <u-button
                    type="primary"
                    size="mini"
                    :custom-style="{
                        background: '#0065FB',
                        border: 'none',
                    }"
                    :disabled="isLock"
                    @click="handleSubmitLock">
                    <text class="text-white"
                        >开始转写<template v-if="tokensValue">（消耗{{ tokensValue }}算力）</template>
                    </text>
                </u-button>
            </view>
        </view>
    </u-popup> -->
</template>

<script setup lang="ts">
import { useAudio } from "@/hooks/useAudio";
import { getAudioList, getVoiceList, createAudio, deleteAudio } from "@/api/digital_human";
import { useLockFn } from "@/hooks/useLockFn";
import { formatAudioTime } from "@/utils/util";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";

const appStore = useAppStore();
const { getDigitalHumanConfig } = toRefs(appStore);
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const getHumanAudioToken = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AUDIO)?.score;
const getHumanAudioProToken = userStore.getTokenByScene(TokensSceneEnum.HUMAN_AUDIO_PRO)?.score;
const tokensValue = computed(() => {
    if (formData.model_version === "1") {
        return getHumanAudioProToken;
    }
    if (formData.model_version === "2") {
        return getHumanAudioToken;
    }
    return null;
});

const modelVersionMap = computed(() => {
    return getDigitalHumanConfig.value.reduce((acc: Record<string, any>, item: any) => {
        acc[item.id] = item.name;
        return acc;
    }, {});
});

const searchValue = ref("");
const search = () => {
    pagingRef.value?.reload();
};

const lists = ref<any[]>([]);
const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getAudioList({
            page_no,
            page_size,
            name: searchValue.value,
        });
        lists.forEach((item: any) => {
            item.show = false;
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const barOptions = [
    {
        text: "删除",
        style: {
            backgroundColor: "#dd524d",
        },
    },
];

const openItem = (index: number) => {
    lists.value.forEach((item) => {
        item.show = false;
    });
    lists.value[index].show = true;
};

const clickItem = (id: number, index: number) => {
    uni.showModal({
        title: "删除",
        content: "是否删除该音频",
        success: async (res) => {
            if (res.confirm) {
                uni.showLoading({
                    title: "删除中...",
                    mask: true,
                });
                try {
                    await deleteAudio({ id });
                    lists.value.splice(index, 1);
                    uni.hideLoading();
                    uni.showToast({
                        title: "删除成功",
                        icon: "none",
                        duration: 2000,
                    });
                } catch (error: any) {
                    uni.hideLoading();
                    uni.showToast({
                        title: error || "删除失败",
                        icon: "none",
                        duration: 2000,
                    });
                }
            }
        },
    });
};

const playIndex = ref<number>();
const playDuration = ref<number>(0);
const playInterval = ref<any>();
const { play, pauseAll, setUrl, duration, isPlaying } = useAudio({
    onPlay: () => {
        clearInterval(playInterval.value);
        playDuration.value = 0;
        playInterval.value = setInterval(() => {
            playDuration.value += 1;
        }, 1000);
    },
    onStop: () => {
        playIndex.value = undefined;
        playDuration.value = 0;
        clearInterval(playInterval.value);
    },
});

const handlePlay = async (index: number) => {
    playIndex.value = index;
    const url = lists.value[index].url;
    setUrl(url);
    play();
};

const handlePause = (index: number) => {
    playIndex.value = undefined;
    pauseAll();
};

const formRef = ref<any>();
const formData = reactive<any>({
    name: "",
    msg: "",
    voice_id: "",
    model_version: "",
});
const formRules = ref<any>({
    name: [{ required: true, message: "请输入音频名称" }],
    msg: [{ required: true, message: "请输入音频内容" }],
    voice_id: [{ required: true, message: "请选择音色" }],
    model_version: [{ required: true, message: "请选择模型" }],
});
const showCreateAudio = ref<boolean>(false);

const showModel = ref<boolean>(false);
const modelData = reactive<Record<string, any>>({
    name: "",
});
const handleModel = (index: number[]) => {
    const data = getDigitalHumanConfig.value[index[0]];
    formData.model_version = data.id;
    modelData.name = data.name;
};

const toneLists = ref<any[]>([]);
const getToneList = async () => {
    const { lists } = await getVoiceList({ page_no: 1, page_size: 9999 });
    toneLists.value = lists.length ? lists.filter((item: any) => item.status == 1) : [];
};

const showTone = ref<boolean>(false);
const toneData = ref<any>({});
const handleTone = (index: number[]) => {
    const data = toneLists.value[index[0]];
    formData.user_voice_id = data.voice_id;
    toneData.value = data;
};

const openTone = () => {
    if (!formData.model_version) {
        uni.$u.toast("请先选择模型");
        return;
    }
    showTone.value = true;
};

const handleSubmit = async () => {
    if (userTokens.value < tokensValue.value) {
        uni.$u.toast("算力不足，请充值！");
        return;
    }
    formRef.value?.validate(async (valid: boolean) => {
        if (valid) {
            try {
                uni.showLoading({
                    title: "正在转写",
                    mask: true,
                });
                await createAudio(formData);
                uni.hideLoading();
                userStore.getUser();
                showCreateAudio.value = false;
                pagingRef.value?.reload();
            } catch (error) {
                uni.hideLoading();
            }
        }
    });
};

const { lockFn: handleSubmitLock, isLock } = useLockFn(handleSubmit);

const handleClose = () => {
    showCreateAudio.value = false;
};

const handleUpload = async () => {
    getToneList();
    showCreateAudio.value = true;
    // const filesResult = await chooseFile({
    // 	type: "file",
    // 	extension: ["mp3", "wav", "m4a"],
    // });
};

onLoad(async () => {
    await nextTick();
    pagingRef.value?.reload();
});

onReady(() => {
    formRef.value?.setRules(formRules.value);
});
</script>

<style scoped></style>

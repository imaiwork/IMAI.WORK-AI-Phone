<template>
    <popup-bottom
        v-model="showPopup"
        title="请选择音色"
        custom-class="bg-[#F9FAFB]"
        :is-disabled-touch="true"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view class="px-[32rpx]" v-if="getTabsList.length > 1">
                    <u-tabs
                        :list="getTabsList"
                        :current="current"
                        bg-color="transparent"
                        :is-scroll="false"
                        gutter="100"
                        @change="handleChange"></u-tabs>
                </view>

                <navigator
                    :url="`/ai_modules/digital_human/pages/tone_clone/tone_clone?model_version=${modelVersion}`"
                    hover-class="none"
                    class="flex items-center justify-center gap-x-2 mx-[32rpx] bg-white rounded-[24rpx] h-[140rpx] mt-2">
                    <image src="@/ai_modules/digital_human/static/icons/add.svg" class="w-[32rpx] h-[32rpx]"></image>
                    <text class="text-[30rpx] font-medium">去克隆音色</text>
                </navigator>

                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :default-page-size="15"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="py-[30rpx] px-[32rpx]">
                            <view
                                v-for="(item, index) in dataLists"
                                class="flex items-center mb-[16rpx] gap-x-[24rpx] bg-white rounded-[24rpx] p-[32rpx]"
                                :class="{
                                    '!bg-[#F0F7FF] shadow-[0px_0px_0px_1px_rgba(0,101,251,1)]':
                                        chooseToneItem.voice_id == item.voice_id,
                                }"
                                :key="index"
                                @click="chooseTone(item, item.builtin)">
                                <view class="flex-shrink-0 leading-[0]">
                                    <image
                                        v-if="item.type === 0"
                                        class="w-[72rpx] h-[72rpx]"
                                        src="@/ai_modules/digital_human/static/images/common/user_tone.svg"></image>
                                    <image
                                        v-else
                                        class="w-[72rpx] h-[72rpx]"
                                        src="@/ai_modules/digital_human/static/images/common/system_tone.svg"></image>
                                </view>
                                <view class="flex-1 text-[26rpx]">
                                    <text class="line-clamp-1 break-all">{{ item.name }}</text>
                                </view>
                                <view
                                    class="flex items-center justify-center gap-x-2 rounded-[16rpx] px-[20rpx] py-[12rpx] bg-[#eef6ff] border border-solid border-[#dbeafe]"
                                    :class="isPlaying && isChoose(item.voice_id) ? 'playing' : 'paused'"
                                    style="background: linear-gradient(135deg, #eef6ff 0%, #dbeafe 100%)"
                                    @click.stop="toggleAudioPlayback(item)">
                                    <u-icon
                                        :name="isPlaying && isChoose(item.voice_id) ? 'pause-circle' : 'play-circle'"
                                        :size="30"
                                        color="#0065fb"></u-icon>
                                    <text class="text-[26rpx] font-medium text-primary">
                                        {{ isPlaying && isChoose(item.voice_id) ? "暂停" : "试听" }}
                                    </text>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>
                <view class="p-4">
                    <u-button type="primary" @click="handleConfirm">确定</u-button>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVoiceList } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    modelVersion: {
        type: [String, Number],
        default: "",
    },
    activeTone: {
        type: [String, Number],
        default: "",
    },
    showOriginalTone: {
        type: Boolean,
        default: false,
    },
    showFreeTone: {
        type: Boolean,
        default: true,
    },
});
const emit = defineEmits(["update:modelValue", "confirm", "close"]);

const showPopup = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

const tabsList = ref<any[]>([
    { name: "系统音色", value: 0 },
    { name: "用户音色", value: 1 },
]);

// 根据showFreeTone控制显示的tab
const getTabsList = computed(() => {
    if (!props.showFreeTone) {
        return tabsList.value.filter((item: any) => item.value === 0);
    }
    return tabsList.value;
});

const current = ref(0);
const chooseToneItem = ref<any>({ voice_id: props.activeTone });

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);

const currVoiceId = ref(null);
// 音频播放hook
const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const isChoose = (voice_id: string) => {
    return currVoiceId.value === voice_id;
};

const toggleAudioPlayback = async (item: any) => {
    if (isPlaying.value && isChoose(item.voice_id)) {
        pause();
        return;
    }

    if (isPlaying.value) {
        pauseAll();
    }

    play(item.builtin === 1 ? item.voice_urls : item.url);
    currVoiceId.value = item.voice_id;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getVoiceList({
            page_no,
            page_size,
            model_version: props.modelVersion,
            status: 1,
            builtin: props.showFreeTone && current.value == 0 ? 0 : 1, // 用户音色
        });
        if (props.showFreeTone && current.value == 0) {
            lists.forEach((item: any) => {
                item.voice_id = item.code;
            });
        }
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleChange = (index: number) => {
    current.value = index;
    // tab切换时重新加载数据
    pagingRef.value?.reload();
};

const chooseTone = (item: any, type: number) => {
    if (props.activeTone === item.voice_id) {
        chooseToneItem.value = {};
        return;
    }
    chooseToneItem.value = item;
};

const handleConfirm = () => {
    destroy();
    emit("confirm", chooseToneItem.value);
};

const close = () => {
    destroy();
    emit("close");
};

onUnmounted(() => {
    pauseAll();
    destroy();
});
</script>

<style scoped></style>

<template>
    <popup-bottom
        v-model="show"
        title="请选择音色"
        :custom-class="plain ? 'bg-white' : 'bg-[#F9FAFB]'"
        :clearable="!plain"
        :is-disabled-touch="true"
        @close="close">
        <template v-if="plain" #header>
            <view class="selector-handle"></view>
            <view class="selector-header">
                <view class="flex-1 min-w-0">
                    <text class="selector-title">{{ limit === 1 ? "选择音色" : "添加音色" }}</text>
                    <text class="selector-subtitle">
                        {{ limit === 1 ? "选择一个音色，确认后完成绑定" : "可多选加入当前人设" }} · 已选
                        {{ chooseLists.length }} 个
                    </text>
                </view>
                <view class="selector-close" @click="close">
                    <u-icon name="close" color="#5B6B80" size="24"></u-icon>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full flex flex-col" :class="{ 'persona-voice--plain': plain }">
                <view class="px-[32rpx]" v-if="getTabsList.length > 1">
                    <u-tabs
                        :list="getTabsList"
                        :current="current"
                        bg-color="transparent"
                        :is-scroll="false"
                        gutter="100"
                        @change="handleChange" />
                </view>

                <view class="flex items-center justify-between px-[32rpx] mt-3 mb-1" v-if="limit > 1 && !plain">
                    <text class="text-[#666666] font-medium">请选择音色</text>
                    <view class="text-xs text-[#999999]">
                        已选
                        <text class="text-primary font-bold mx-0.5">{{ chooseLists.length }}</text>
                        / {{ limit }}
                    </view>
                </view>

                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :default-page-size="15"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="voice-list py-[24rpx] px-[32rpx] flex flex-col gap-[16rpx]">
                            <navigator
                                :url="`/ai_modules/digital_human_create/pages/tone_clone/tone_clone?model_version=${modelVersion}`"
                                hover-class="none"
                                class="voice-clone flex items-center gap-x-[24rpx] rounded-[24rpx] p-[32rpx] border-[2rpx] border-dashed border-[#C5D9FF] bg-[#F0F7FF] active:opacity-70">
                                <view
                                    class="voice-clone-icon w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                    :style="
                                        plain ? {} : { background: 'linear-gradient(135deg, #4da3ff 0%, #0065fb 100%)' }
                                    ">
                                    <u-icon name="plus" size="28" :color="plain ? '#0065FB' : '#fff'" />
                                </view>
                                <view class="flex-1">
                                    <text class="voice-clone-title text-[28rpx] font-bold text-primary block"
                                        >去克隆音色</text
                                    >
                                    <text class="voice-clone-description text-[23rpx] text-[#7AABFF] mt-[4rpx] block"
                                        >复刻您的专属声音</text
                                    >
                                </view>
                                <u-icon name="arrow-right" :color="plain ? '#647184' : '#7AABFF'" size="28" />
                            </navigator>

                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="voice-option flex items-center gap-x-[24rpx] rounded-[24rpx] p-[32rpx] transition-opacity"
                                :class="[
                                    isDisabled(item)
                                        ? 'bg-[#F5F5F5] opacity-60'
                                        : isChooseItem(item)
                                        ? 'bg-[#F0F7FF] shadow-[0px_0px_0px_2rpx_rgba(0,101,251,1)] active:opacity-80'
                                        : 'bg-white active:opacity-80',
                                    {
                                        'voice-option--selected': isChooseItem(item),
                                        'voice-option--disabled': isDisabled(item),
                                    },
                                ]"
                                @click="handleSelect(item)">
                                <view class="flex-shrink-0 leading-[0] relative">
                                    <image
                                        v-if="item.type === 0"
                                        class="w-[72rpx] h-[72rpx]"
                                        :class="isDisabled(item) ? 'grayscale' : ''"
                                        src="@/ai_modules/person/static/images/common/user_tone.svg" />
                                    <image
                                        v-else
                                        class="w-[72rpx] h-[72rpx]"
                                        :class="isDisabled(item) ? 'grayscale' : ''"
                                        src="@/ai_modules/person/static/images/common/system_tone.svg" />
                                    <view
                                        v-if="isChooseItem(item)"
                                        class="absolute -bottom-[4rpx] -right-[4rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center">
                                        <u-icon name="checkmark" color="#fff" size="16" />
                                    </view>
                                </view>

                                <view class="flex-1 min-w-0">
                                    <text
                                        class="voice-name text-[28rpx] font-medium line-clamp-1 break-all block"
                                        :class="isDisabled(item) ? 'text-[#BBBBBB]' : 'text-[#212121]'">
                                        {{ item.name }}
                                    </text>
                                    <text
                                        class="voice-description text-[22rpx] mt-[4rpx] block"
                                        :class="isDisabled(item) ? 'text-[#CCCCCC]' : 'text-[#b4b4b4]'">
                                        {{ isDisabled(item) ? "已使用" : item.type === 0 ? "用户音色" : "系统音色" }}
                                    </text>
                                </view>

                                <view
                                    class="voice-preview flex items-center justify-center gap-x-[8rpx] rounded-[16rpx] px-[20rpx] py-[12rpx] flex-shrink-0"
                                    :class="
                                        isDisabled(item)
                                            ? 'bg-[#F0F0F0] border border-solid border-[#E0E0E0]'
                                            : isPlaying && isChooseAudio(item.voice_id)
                                            ? 'bg-[#FFF0F0] border border-solid border-[#FFD6D6]'
                                            : 'bg-[#EEF6FF] border border-solid border-[#DBEAFE]'
                                    "
                                    @click.stop="toggleAudioPlayback(item)">
                                    <u-icon
                                        :name="
                                            isPlaying && isChooseAudio(item.voice_id) ? 'pause-circle' : 'play-circle'
                                        "
                                        :size="30"
                                        :color="
                                            isDisabled(item)
                                                ? '#BBBBBB'
                                                : isPlaying && isChooseAudio(item.voice_id)
                                                ? '#FF4D4F'
                                                : '#0065fb'
                                        " />
                                    <text
                                        class="text-xs font-medium"
                                        :class="
                                            isDisabled(item)
                                                ? 'text-[#BBBBBB]'
                                                : isPlaying && isChooseAudio(item.voice_id)
                                                ? 'text-[#FF4D4F]'
                                                : 'text-primary'
                                        ">
                                        {{ isPlaying && isChooseAudio(item.voice_id) ? "暂停" : "试听" }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <template #empty>
                            <empty />
                        </template>
                    </z-paging>
                </view>

                <view
                    class="selector-footer bg-[#ffffff]/90 px-[32rpx] pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] flex items-center justify-between gap-4 z-50 border-t border-solid border-[#F0F0F0]">
                    <view
                        v-if="limit && limit > 1"
                        class="flex items-center gap-2 active:opacity-70 transition-opacity py-2"
                        @click="toggleSelectAll">
                        <view
                            class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-colors"
                            :class="isAllSelected ? 'bg-primary' : 'border-[3rpx] border-[#D1D5DB] bg-[#F9FAFB]'">
                            <image
                                v-if="isAllSelected"
                                src="/static/images/icons/success.svg"
                                class="w-[24rpx] h-[24rpx]" />
                        </view>
                        <text class="text-[28rpx] text-[#333333] font-medium">全选</text>
                    </view>

                    <view class="flex-1">
                        <u-button
                            type="primary"
                            :shape="plain ? 'square' : 'circle'"
                            ripple
                            :custom-style="
                                plain
                                    ? {
                                          fontSize: '28rpx',
                                          fontWeight: '600',
                                          height: '94rpx',
                                          borderRadius: '24rpx',
                                      }
                                    : {
                                          fontSize: '28rpx',
                                          fontWeight: 'bold',
                                          height: '88rpx',
                                      }
                            "
                            @click="handleConfirm">
                            {{ plain ? (limit === 1 ? "确认绑定" : "确认加入") : "确定选择"
                            }}{{ limit > 1 && chooseLists.length > 0 ? `(${chooseLists.length})` : "" }}
                        </u-button>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVoiceList } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    modelVersion: { type: [String, Number], default: "" },
    activeTone: { type: [String, Number], default: "" },
    showOriginalTone: { type: Boolean, default: false },
    showFreeTone: { type: Boolean, default: true },
    limit: { type: Number, default: 99 },
    plain: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "select", "close"]);

const show = computed({
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

const getTabsList = computed(() => {
    if (!props.showFreeTone) {
        return tabsList.value.filter((item: any) => item.value === 0);
    }
    return tabsList.value;
});

const current = ref(0);
const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);

// ── 多选列表（单选时复用，只保留1条）
const chooseLists = ref<any[]>([]);
const disabledLists = ref<any[]>([]);

// ── 禁用判断
const isDisabled = (item: any) => disabledLists.value.some((d) => d.voice_id === item.id);

// ── 全选相关（排除禁用项）
const selectableLists = computed(() => dataLists.value.filter((item) => !isDisabled(item)));
const isAllSelected = computed(
    () => selectableLists.value.length > 0 && chooseLists.value.length === selectableLists.value.length,
);

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        chooseLists.value = [];
    } else {
        const all = [...selectableLists.value];
        if (all.length > props.limit) {
            uni.showToast({ title: `最多只能选择${props.limit}个`, icon: "none" });
            chooseLists.value = all.slice(0, props.limit);
        } else {
            chooseLists.value = all;
        }
    }
};

// ── 单条选择
const handleSelect = (item: any) => {
    if (isDisabled(item)) {
        uni.showToast({ title: "该音色已被使用，无法选择", icon: "none" });
        return;
    }

    const index = chooseLists.value.findIndex((c) => c.voice_id === item.voice_id);
    if (index > -1) {
        chooseLists.value.splice(index, 1);
    } else {
        if (props.limit === 1) {
            chooseLists.value = [item];
        } else {
            if (chooseLists.value.length >= props.limit) {
                uni.showToast({ title: `最多只能选择${props.limit}个`, icon: "none" });
            } else {
                chooseLists.value.push(item);
            }
        }
    }
};

const isChooseItem = (item: any) => chooseLists.value.some((c) => c.voice_id === item.voice_id);

// ── 试听
const currVoiceId = ref<string | null>(null);
const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const isChooseAudio = (voice_id: string) => currVoiceId.value === voice_id;

const toggleAudioPlayback = async (item: any) => {
    if (isPlaying.value && isChooseAudio(item.voice_id)) {
        pause();
        return;
    }
    if (isPlaying.value) pauseAll();
    play(item.builtin === 1 ? item.voice_urls : item.url);
    currVoiceId.value = item.voice_id;
};

// ── 分页
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getVoiceList({
            page_no,
            page_size,
            model_version: props.modelVersion,
            status: 1,
            builtin: props.showFreeTone && current.value == 0 ? 0 : 1,
        });
        if (props.showFreeTone && current.value == 0) {
            lists.forEach((item: any) => ({ ...item, voice_id: item.code, url: item.voice_urls }));
        }
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

const handleChange = (index: number) => {
    current.value = index;
    chooseLists.value = [];
    pagingRef.value?.reload();
};

const handleConfirm = () => {
    if (chooseLists.value.length === 0 && disabledLists.value.length === 0 && !props.showOriginalTone) {
        uni.showToast({ title: "请至少选择一个音色", icon: "none" });
        return;
    }
    close();
    emit("select", props.limit === 1 ? chooseLists.value[0] : chooseLists.value);
};

const close = () => {
    destroy();
    show.value = false;
    emit("close");
};

// 初始化选中
watch(
    () => props.activeTone,
    (val) => {
        if (val) chooseLists.value = [{ voice_id: val }];
    },
    { immediate: true },
);

onUnmounted(() => {
    pauseAll();
    destroy();
});

defineExpose({
    setChooseLists: (lists: any[]) => {
        chooseLists.value = JSON.parse(JSON.stringify(lists));
    },
    setDisabledLists: (lists: any[]) => {
        disabledLists.value = JSON.parse(JSON.stringify(lists));
    },
});
</script>

<style lang="scss" scoped>
.selector-handle {
    @apply w-[64rpx] h-[8rpx] rounded-[8rpx] bg-[#DCE3EC] mx-auto mt-[20rpx] mb-[4rpx];
}

.selector-header {
    @apply flex items-center gap-[24rpx] px-[36rpx] pt-[18rpx] pb-[26rpx]
        border-[0] border-b-[2rpx] border-solid border-[#EEF1F5];
}

.selector-title {
    @apply block text-[34rpx] font-semibold text-[#202937];
}

.selector-subtitle {
    @apply block mt-[8rpx] text-[24rpx] leading-[36rpx] text-[#647184];
}

.selector-close {
    @apply relative w-[56rpx] h-[56rpx] rounded-full bg-[#F1F4F8] flex items-center justify-center shrink-0 active:opacity-70;

    &::after {
        content: "";
        @apply absolute inset-[-16rpx];
    }
}

.persona-voice--plain {
    @apply bg-white;

    .voice-list {
        @apply p-[36rpx] gap-[20rpx];
    }

    .voice-clone {
        @apply p-[24rpx] rounded-[22rpx] border-[#C5D8F0] bg-[#F3F7FC];
    }

    .voice-clone-icon {
        @apply rounded-[20rpx] bg-[#E6EFFF];
    }

    .voice-clone-title {
        @apply font-medium text-[#202937];
    }

    .voice-clone-description {
        @apply text-[24rpx] text-[#647184] leading-[36rpx];
    }

    .voice-option {
        @apply min-h-[108rpx] p-[24rpx] gap-x-[20rpx] rounded-[22rpx]
            border-[2rpx] border-solid border-[#DDE5EF] bg-white shadow-none;
    }

    .voice-option--selected {
        @apply border-[#87B7FF] bg-[#F1F6FF];
    }

    .voice-name {
        @apply text-[#202937];
    }

    .voice-description {
        @apply text-[24rpx] text-[#647184] leading-[36rpx];
    }

    .voice-option--disabled {
        @apply border-[#E4E9EF] bg-[#F6F8FA];

        .voice-name,
        .voice-description {
            @apply text-[#8390A1];
        }
    }

    .voice-preview {
        @apply min-h-[76rpx] px-[16rpx] rounded-[18rpx];
    }

    .selector-footer {
        @apply bg-white px-[36rpx] pt-[24rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))] gap-[24rpx]
            border-[0] border-t-[2rpx] border-solid border-[#E7ECF2];
    }
}
</style>

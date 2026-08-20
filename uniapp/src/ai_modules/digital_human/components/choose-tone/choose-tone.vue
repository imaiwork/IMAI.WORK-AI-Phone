<template>
    <popup-bottom
        v-model="show"
        title="请选择音色"
        custom-class="bg-[#F7F9FC]"
        :is-disabled-touch="true"
        @close="close">
        <template #content>
            <view class="h-full flex flex-col">
                <view
                    class="flex-shrink-0 border-[0] border-b border-solid border-[#F0F2F5]"
                    v-if="getTabsList.length > 1">
                    <view class="mx-[28rpx] flex bg-[#F3F4F6] rounded-[12rpx] p-[4rpx] my-[16rpx]">
                        <view
                            v-for="tab in getTabsList"
                            :key="tab.value"
                            class="flex-1 flex items-center justify-center py-[10rpx] rounded-[10rpx] transition-all"
                            :class="
                                current === tab.value
                                    ? 'bg-white text-[#111827] font-semibold shadow-sm'
                                    : 'text-[#6B7280]'
                            "
                            @click="handleChange(tab.value)">
                            {{ tab.name }}
                        </view>
                    </view>
                </view>

                <view
                    v-if="limit > 1"
                    class="flex items-center justify-between px-[28rpx] mt-[16rpx] mb-[8rpx]"
                    @click="toggleChoosePanel">
                    <view class="flex items-center gap-[10rpx]">
                        <text class="text-xs text-[#00000080]">
                            已选：<text
                                class="font-semibold"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-primary'">
                                {{ chooseLists.length }}
                            </text>
                        </text>
                        <view
                            class="flex items-center gap-[4rpx] px-[12rpx] h-[36rpx] rounded-full"
                            :class="chooseLists.length >= limit ? 'bg-[#FEF2F2]' : 'bg-[#F0F2F5]'">
                            <u-icon
                                :name="chooseLists.length >= limit ? 'info-circle-fill' : 'info-circle'"
                                :color="chooseLists.length >= limit ? '#EF4444' : '#9CA3AF'"
                                size="18" />
                            <text
                                class="text-[20rpx] font-medium"
                                :class="chooseLists.length >= limit ? 'text-[#EF4444]' : 'text-[#9CA3AF]'">
                                最多 {{ limit }} 个
                            </text>
                        </view>
                    </view>
                    <view v-if="chooseLists.length > 0" class="flex items-center gap-1">
                        <text class="text-[22rpx] text-[#6B7280]">{{ showChoosePanel ? "收起" : "查看已选" }}</text>
                        <u-icon :name="showChoosePanel ? 'arrow-up' : 'arrow-down'" size="12" color="#6B7280" />
                    </view>
                </view>

                <view
                    v-if="showChoosePanel && chooseLists.length > 0"
                    class="mx-[28rpx] mb-[16rpx] bg-white rounded-[20rpx] border border-solid border-[#F1F5F9] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.06)] overflow-hidden flex-shrink-0">
                    <view
                        class="flex items-center justify-between px-[24rpx] py-[16rpx] border-[0] border-b border-solid border-[#F1F5F9]">
                        <text class="text-xs font-semibold text-[#374151]">已选（{{ chooseLists.length }}）</text>
                        <text class="text-[22rpx] text-[#EF4444] font-medium" @click.stop="clearAll">清空</text>
                    </view>
                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                        <view class="flex gap-[16rpx] px-[24rpx] py-[16rpx]" style="width: max-content">
                            <view
                                v-for="item in chooseLists"
                                :key="item.voice_id"
                                class="relative flex-shrink-0 w-[120rpx] h-[120rpx] rounded-[16rpx] overflow-hidden bg-[#F0F6FF] flex flex-col items-center justify-center gap-[6rpx] border border-solid border-[#DBEAFE]">
                                <image
                                    v-if="item.type === 0"
                                    class="w-[48rpx] h-[48rpx]"
                                    src="@/ai_modules/person/static/images/common/user_tone.svg" />
                                <image
                                    v-else
                                    class="w-[48rpx] h-[48rpx]"
                                    src="@/ai_modules/person/static/images/common/system_tone.svg" />
                                <text class="text-[18rpx] text-[#374151] px-[8rpx] text-center line-clamp-1 w-full">
                                    {{ item.name }}
                                </text>
                                <view
                                    class="absolute top-[6rpx] right-[6rpx] w-[32rpx] h-[32rpx] rounded-full bg-[#374151] flex items-center justify-center"
                                    @click.stop="handleSelect(item)">
                                    <u-icon name="close" size="12" color="#fff" />
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="grow min-h-0">
                    <z-paging
                        ref="pagingRef"
                        v-model="dataLists"
                        :default-page-size="15"
                        :fixed="false"
                        :safe-area-inset-bottom="true"
                        @query="queryList">
                        <view class="px-[28rpx] pt-[16rpx] pb-[24rpx] flex flex-col gap-[12rpx]">
                            <view
                                v-if="showOriginalTone"
                                class="flex items-center gap-[24rpx] rounded-[24rpx] px-[28rpx] py-[24rpx] border-2 active:opacity-70 transition-all"
                                :class="
                                    originalSelected
                                        ? 'border-solid border-[#16A34A] bg-[#DCFCE7] shadow-[0_0_0_2rpx_rgba(22,163,74,0.25)]'
                                        : 'border-dashed border-[#16A34A]/30 bg-[#F0FDF4]'
                                "
                                @click="handleSelectOriginal">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(22,163,74,0.30)]"
                                    style="background: linear-gradient(135deg, #4ade80 0%, #16a34a 100%)">
                                    <u-icon name="mic" size="28" color="#fff" />
                                </view>
                                <view class="flex-1">
                                    <text class="text-[28rpx] font-bold text-[#16A34A] block">使用形象原声</text>
                                    <text
                                        class="text-[22rpx] mt-[4rpx] block"
                                        :class="originalSelected ? 'text-[#16A34A]' : 'text-[#4ADE80]'">
                                        {{ originalSelected ? "当前使用中" : "使用当前形象的原始声音" }}
                                    </text>
                                </view>
                                <view
                                    v-if="originalSelected"
                                    class="w-[40rpx] h-[40rpx] rounded-full bg-[#16A34A] flex items-center justify-center flex-shrink-0">
                                    <u-icon name="checkmark" color="#fff" size="20" />
                                </view>
                                <u-icon v-else name="arrow-right" color="#4ADE80" size="28" />
                            </view>

                            <navigator
                                :url="`/ai_modules/digital_human/pages/tone_clone/tone_clone?model_version=${cloneToneModelVersion}`"
                                hover-class="none"
                                class="flex items-center gap-[24rpx] rounded-[24rpx] px-[28rpx] py-[24rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] active:opacity-70">
                                <view
                                    class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center flex-shrink-0 shadow-[0_4rpx_12rpx_rgba(0,101,251,0.30)]"
                                    style="background: linear-gradient(135deg, #4da3ff 0%, #0065fb 100%)">
                                    <u-icon name="plus" size="28" color="#fff" />
                                </view>
                                <view class="flex-1">
                                    <text class="text-[28rpx] font-bold text-primary block">去克隆音色</text>
                                    <text class="text-[22rpx] text-[#7AABFF] mt-[4rpx] block">复刻您的专属声音</text>
                                </view>
                                <u-icon name="arrow-right" color="#7AABFF" size="28" />
                            </navigator>

                            <view
                                v-for="(item, index) in dataLists"
                                :key="index"
                                class="flex items-center gap-[20rpx] rounded-[24rpx] px-[28rpx] py-[24rpx] transition-all"
                                :class="
                                    isDisabled(item)
                                        ? 'bg-[#F5F5F5] opacity-60'
                                        : isChooseItem(item)
                                        ? 'bg-[#EBF2FF] shadow-[0_0_0_2rpx_rgba(0,101,251,0.4)]'
                                        : 'bg-white shadow-[0_2rpx_8rpx_rgba(0,0,0,0.05)]'
                                "
                                @click="handleSelect(item)">
                                <view class="flex-shrink-0 relative leading-[0]">
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
                                        class="absolute -bottom-[4rpx] -right-[4rpx] w-[28rpx] h-[28rpx] rounded-full bg-primary flex items-center justify-center shadow-sm">
                                        <u-icon name="checkmark" color="#fff" size="16" />
                                    </view>
                                </view>

                                <view class="flex-1 min-w-0">
                                    <text
                                        class="text-[28rpx] font-semibold line-clamp-1 break-all block"
                                        :class="isDisabled(item) ? 'text-[#BBBBBB]' : 'text-[#0D1117]'">
                                        {{ item.name }}
                                    </text>
                                    <view class="flex items-center gap-[8rpx] mt-[6rpx]">
                                        <view
                                            v-if="!isDisabled(item)"
                                            class="px-[10rpx] h-[28rpx] rounded-full flex items-center"
                                            :class="item.type === 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0FDF4]'">
                                            <text
                                                class="text-[18rpx] font-medium"
                                                :class="item.type === 0 ? 'text-primary' : 'text-[#16A34A]'">
                                                {{ item.type === 0 ? "用户音色" : "系统音色" }}
                                            </text>
                                        </view>
                                        <text v-else class="text-[22rpx] text-[#CCCCCC]">已使用</text>
                                    </view>
                                </view>

                                <view
                                    class="flex items-center justify-center gap-[6rpx] rounded-[16rpx] px-[20rpx] h-[60rpx] flex-shrink-0 border border-solid"
                                    :class="
                                        isDisabled(item)
                                            ? 'bg-[#F0F0F0] border-[#E0E0E0]'
                                            : isPlaying && isChooseAudio(item.voice_id)
                                            ? 'bg-[#FFF0F0] border-[#FFD6D6]'
                                            : 'bg-[#EBF2FF] border-[#DBEAFE]'
                                    "
                                    @click.stop="toggleAudioPlayback(item)">
                                    <u-icon
                                        :name="
                                            isPlaying && isChooseAudio(item.voice_id) ? 'pause-circle' : 'play-circle'
                                        "
                                        :size="28"
                                        :color="
                                            isDisabled(item)
                                                ? '#BBBBBB'
                                                : isPlaying && isChooseAudio(item.voice_id)
                                                ? '#FF4D4F'
                                                : '#0065fb'
                                        " />
                                    <text
                                        class="text-[22rpx] font-semibold"
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
                    class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-[28rpx] pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx] shadow-[0_-2rpx_12rpx_rgba(0,0,0,0.06)]">
                    <view v-if="limit && limit > 1" class="flex flex-col gap-[6rpx] flex-shrink-0">
                        <view class="flex items-center gap-[10rpx]" @click="toggleSelectAll">
                            <view class="w-[32rpx] h-[32rpx]">
                                <image
                                    v-if="isCurrentPageAllSelected"
                                    src="/static/images/icons/success.svg"
                                    class="w-full h-full" />
                                <view
                                    class="w-full h-full rounded-full shadow-[0_0_0_2rpx_rgba(0,0,0,0.2)]"
                                    v-else></view>
                            </view>
                            <text class="text-[#374151]">全选</text>
                        </view>
                    </view>

                    <view
                        class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all"
                        :class="
                            chooseLists.length > 0 || props.limit == 1
                                ? 'shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]'
                                : 'bg-[#E5E7EB]'
                        "
                        :style="
                            chooseLists.length > 0 || props.limit == 1
                                ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                                : ''
                        "
                        @click="handleConfirm">
                        <text
                            class="text-[30rpx] font-extrabold"
                            :class="chooseLists.length > 0 || props.limit == 1 ? 'text-white' : 'text-[#9CA3AF]'">
                            确定选择
                        </text>
                        <text v-if="limit > 1 && chooseLists.length > 0" class="text-xs text-[#ffffff]/70 font-medium">
                            ({{ chooseLists.length }}/{{ limit }})
                        </text>
                    </view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getVoiceList } from "@/api/digital_human";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { useAudio } from "@/hooks/useAudio";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    modelVersion: { type: [String, Number], default: "" },
    activeTone: { type: [String, Number], default: "" },
    showOriginalTone: { type: Boolean, default: false },
    originalSelected: { type: Boolean, default: false },
    showUserTone: { type: Boolean, default: true },
    showFreeTone: { type: Boolean, default: true },
    limit: { type: Number, default: 99 },
    type: { type: Number, default: 1 },
});
const emit = defineEmits(["update:modelValue", "select", "original", "close"]);

const show = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});

// 多版本查询时，「去克隆」取驱动模型（第一个），避免误带 MiniMax
const cloneToneModelVersion = computed(() => {
    const first = String(props.modelVersion || "")
        .split(",")
        .map((v) => v.trim())
        .find(Boolean);
    return first || DigitalHumanModelVersionEnum.SHANJIAN;
});

const tabsList = ref<any[]>([
    { name: "系统音色", value: 0 },
    { name: "用户音色", value: 1 },
]);

const getTabsList = computed(() => {
    if (!props.showFreeTone && !props.showUserTone) return [];
    if (!props.showFreeTone) return tabsList.value.filter((item) => item.value !== 0);
    if (!props.showUserTone) return tabsList.value.filter((item) => item.value !== 1);
    return tabsList.value;
});

const current = ref(0);

const getCurrTab = computed(() => current.value);

watch(
    getTabsList,
    (tabs) => {
        if (tabs.length > 0 && !tabs.find((t) => t.value === current.value)) {
            current.value = tabs[0].value;
            // pagingRef.value?.reload();
        }
    },
    { immediate: true },
);

const pagingRef = shallowRef();
const dataLists = ref<any[]>([]);
const chooseLists = ref<any[]>([]);
const disabledLists = ref<any[]>([]);
const showChoosePanel = ref(false);

const toggleChoosePanel = () => {
    if (chooseLists.value.length === 0) return;
    showChoosePanel.value = !showChoosePanel.value;
};

const clearAll = () => {
    chooseLists.value = [];
    showChoosePanel.value = false;
};

const isDisabled = (item: any) => disabledLists.value.some((d) => d.voice_id === item.id);

const selectableLists = computed(() => dataLists.value.filter((item) => !isDisabled(item)));

const isCurrentPageAllSelected = computed(() => {
    if (selectableLists.value.length === 0) return false;
    const unselected = selectableLists.value.filter((item) => !isChooseItem(item));
    if (unselected.length === 0) return true;
    const remaining = props.limit - chooseLists.value.length;
    if (remaining <= 0) return true;
    return false;
});

const toggleSelectAll = () => {
    if (isCurrentPageAllSelected.value) {
        const selectableIds = new Set(selectableLists.value.map((i) => i.voice_id));
        chooseLists.value = chooseLists.value.filter((i) => !selectableIds.has(i.voice_id));
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
    } else {
        for (const item of selectableLists.value) {
            if (chooseLists.value.length >= props.limit) break;
            if (!isChooseItem(item)) chooseLists.value.push(item);
        }
    }
};

const handleSelect = (item: any) => {
    if (isDisabled(item)) {
        uni.showToast({ title: "该音色已被使用，无法选择", icon: "none" });
        return;
    }
    const index = chooseLists.value.findIndex((c) => c.voice_id === item.voice_id);
    if (index > -1) {
        chooseLists.value.splice(index, 1);
        if (chooseLists.value.length === 0) showChoosePanel.value = false;
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

const currVoiceId = ref<string | null>(null);
const { isPlaying, play, pause, pauseAll, destroy } = useAudio();

const isChooseAudio = (voice_id: string) => currVoiceId.value === voice_id;

const getToneAudioUrl = (item: any) => {
    const url = item?.builtin === 1 ? item?.voice_urls : item?.url;
    return typeof url === "string" ? url.trim() : "";
};

const toggleAudioPlayback = async (item: any) => {
    if (!item.voice_urls) {
        uni.$u.toast("当前音频不支持试听");
        return;
    }
    if (isPlaying.value && isChooseAudio(item.voice_id)) {
        pause();
        return;
    }
    const audioUrl = getToneAudioUrl(item);
    if (!audioUrl) {
        // 无音频时必须停掉上一条，避免继续播旧源
        pauseAll();
        currVoiceId.value = null;
        return;
    }
    if (isPlaying.value) pauseAll();
    play(audioUrl);
    currVoiceId.value = item.voice_id;
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        let { lists } = await getVoiceList({
            page_no,
            page_size,
            model_version: props.modelVersion || ``,
            status: 1,
            builtin: props.showFreeTone && getCurrTab.value === 0 ? 0 : 1,
            type: getCurrTab.value === 1 || props.type == 0 ? "" : props.type,
        });
        if (props.showFreeTone && getCurrTab.value === 0) {
            lists = lists.map((item: any) => ({
                ...item,
                voice_id: item.code,
                url: item.url || item.voice_urls,
            }));
        }
        pagingRef.value?.complete(lists);
    } catch {
        pagingRef.value?.complete([]);
    }
};

const handleChange = (value: number) => {
    current.value = value;
    showChoosePanel.value = false;
    pagingRef.value?.reload();
};

const handleSelectOriginal = () => {
    close();
    emit("original");
};

const handleConfirm = () => {
    if (chooseLists.value.length === 0 && disabledLists.value.length === 0 && props.limit > 1) {
        uni.showToast({ title: "请至少选择一个音色", icon: "none" });
        return;
    }
    close();
    emit("select", props.limit === 1 ? chooseLists.value[0] : chooseLists.value);
};

const close = () => {
    destroy();
    show.value = false;
    showChoosePanel.value = false;
    emit("close");
};

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

<style scoped></style>

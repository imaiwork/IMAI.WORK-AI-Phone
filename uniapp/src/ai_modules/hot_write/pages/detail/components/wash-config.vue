<template>
    <view
        class="w-full box-border overflow-hidden rounded-[18rpx] bg-white px-[22rpx] py-[22rpx]"
        style="border-left: 4rpx solid #bfdbfe; box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08)">
        <text class="text-[22rpx] font-bold text-[#D97706] block mb-[16rpx]">请选择要合成的视频类型</text>

        <!-- 视频类型（参考设计稿：两张卡片，数字人口播混剪 / 素材混剪） -->
        <view class="grid grid-cols-2 gap-[14rpx]">
            <view
                v-for="item in typeOptions"
                :key="item.val"
                class="rounded-[16rpx] border-[3rpx] border-solid px-[12rpx] py-[20rpx] flex flex-col items-center text-center"
                :class="generationType === item.val ? 'border-primary bg-[#EFF6FF]' : 'border-[#E5E9F0] bg-[#F9FAFB]'"
                @click="pickType(item.val)">
                <image :src="item.icon" mode="aspectFit" class="w-[44rpx] h-[44rpx] mb-[10rpx]" />
                <text
                    class="text-[25rpx] font-bold"
                    :class="generationType === item.val ? 'text-primary' : 'text-[#111827]'">
                    {{ item.label }}
                </text>
                <text class="block text-[20rpx] text-[#9CA3AF] mt-[8rpx] leading-snug">{{ item.desc1 }}</text>
                <text class="block text-[20rpx] text-[#9CA3AF] leading-snug">{{ item.desc2 }}</text>
            </view>
        </view>

        <view v-if="loading" class="flex items-center justify-center gap-[12rpx] py-[32rpx]">
            <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
            <text class="text-[22rpx] text-[#9CA3AF]">正在加载可用形象与音色...</text>
        </view>

        <template v-else>
            <!-- 数字人形象 -->
            <view v-if="needAvatar" class="mt-[28rpx]">
                <text class="text-[24rpx] font-bold text-[#111827] block mb-[14rpx]">选择数字人形象</text>
                <view v-if="avatars.length" class="avatar-strip">
                    <view class="flex gap-[14rpx] pr-[8rpx]" style="width: max-content">
                        <view
                            v-for="a in avatars"
                            :key="a.id"
                            class="w-[144rpx] flex-shrink-0"
                            @click="avatarId = a.id">
                            <view
                                class="relative w-[144rpx] h-[188rpx] rounded-[14rpx] overflow-hidden bg-[#F3F4F6]"
                                :style="avatarId === a.id ? 'border:3rpx solid #2563EB' : 'border:3rpx solid transparent'">
                                <image v-if="a.image" :src="a.image" mode="aspectFill" lazy-load class="w-full h-full" />
                                <view
                                    v-if="avatarId === a.id"
                                    class="absolute top-[8rpx] right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center">
                                    <u-icon name="checkmark" color="#fff" size="18"></u-icon>
                                </view>
                            </view>
                            <text class="text-[20rpx] text-[#6B7280] block text-center mt-[8rpx] truncate">
                                {{ a.name || "未命名形象" }}
                            </text>
                        </view>
                    </view>
                </view>
                <text v-else class="text-[21rpx] text-[#9CA3AF] block">
                    暂无可用形象，请先在「数字人克隆」中创建形象后再来选择
                </text>
            </view>

            <!-- 音色 -->
            <view v-if="needVoice" class="mt-[28rpx]">
                <text class="text-[24rpx] font-bold text-[#111827] block mb-[14rpx]">选择音色</text>
                <template v-if="voices.length">
                    <view
                        v-for="v in visibleVoices"
                        :key="v.id"
                        class="flex items-center gap-[16rpx] px-[20rpx] py-[18rpx] rounded-[16rpx] mb-[12rpx]"
                        :style="
                            voiceId === v.id
                                ? 'background:#EFF6FF;border:2rpx solid #2563EB'
                                : 'background:#F9FAFB;border:2rpx solid transparent'
                        "
                        @click="voiceId = v.id">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-white flex items-center justify-center flex-shrink-0"
                            style="box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08)"
                            @click.stop="togglePreview(v)">
                            <u-icon
                                :name="playingId === v.id ? 'pause' : 'play-right-fill'"
                                :color="voiceId === v.id ? '#2563EB' : '#6B7280'"
                                size="26"></u-icon>
                        </view>
                        <text
                            class="text-[24rpx] font-semibold flex-1 truncate"
                            :class="voiceId === v.id ? 'text-primary' : 'text-[#374151]'">
                            {{ v.name || "未命名音色" }}
                        </text>
                        <u-icon v-if="voiceId === v.id" name="checkmark-circle-fill" color="#2563EB" size="30"></u-icon>
                    </view>
                    <view
                        v-if="hasMoreVoices"
                        class="flex items-center justify-center gap-[8rpx] py-[16rpx]"
                        @click="loadMoreVoices">
                        <text class="text-[22rpx] text-primary">加载更多音色（还有 {{ voices.length - voiceShowCount }} 个）</text>
                        <u-icon name="arrow-down" color="#0065fb" size="20"></u-icon>
                    </view>
                </template>
                <text v-else class="text-[21rpx] text-[#9CA3AF] block">
                    暂无可用音色，请先在「声音克隆」中创建音色后再来选择
                </text>
            </view>

            <!-- 确认 -->
            <view
                class="w-full mt-[28rpx] py-[22rpx] rounded-full flex items-center justify-center gap-[12rpx]"
                style="
                    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
                    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
                "
                :class="submitting ? 'opacity-60' : ''"
                @click="handleConfirm">
                <u-loading v-if="submitting" mode="circle" size="26" color="#ffffff"></u-loading>
                <text class="text-[26rpx] font-semibold text-white">
                    {{ submitting ? "提交合成中..." : "确认无误，开始合成视频" }}
                </text>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { getGenerationOptions } from "@/api/hot_write";
import { WashGenerationType } from "@/ai_modules/hot_write/enums";
import IconVtypeDigital from "@/ai_modules/hot_write/static/icons/vtype_digital.svg";
import IconVtypeClips from "@/ai_modules/hot_write/static/icons/vtype_clips.svg";
import IconVtypeNews from "@/ai_modules/hot_write/static/icons/vtype_news.svg";

const props = defineProps<{
    taskId: string | number;
    submitting?: boolean;
}>();

const emit = defineEmits<{
    (e: "confirm", payload: { generation_type: number; avatar_id: number; voice_id: number }): void;
}>();

/** 视频类型卡片：按设计稿只展示数字人口播混剪 / 素材混剪；新闻体仅在历史任务已选时显示，避免选中态丢失 */
const TYPE_CARDS = [
    {
        val: WashGenerationType.DIGITAL_HUMAN,
        label: "数字人口播混剪",
        desc1: "数字人出镜口播",
        desc2: "需选形象 · 音色",
        icon: IconVtypeDigital,
        needAvatar: true,
        needVoice: true,
    },
    {
        val: WashGenerationType.MATERIAL,
        label: "素材混剪",
        desc1: "纯素材配旁白",
        desc2: "免选形象 · 需选音色",
        icon: IconVtypeClips,
        needAvatar: false,
        needVoice: true,
    },
    {
        val: WashGenerationType.NEWS,
        label: "新闻体",
        desc1: "新闻播报风格",
        desc2: "免选形象 · 音色",
        icon: IconVtypeNews,
        needAvatar: false,
        needVoice: false,
    },
];

const loading = ref(true);
const avatars = ref<any[]>([]);
const voices = ref<any[]>([]);
const generationType = ref<number>(WashGenerationType.DIGITAL_HUMAN);
const avatarId = ref(0);
const voiceId = ref(0);

/** 音色列表前端分页：默认展示前 N 条，点击加载更多逐步展开 */
const VOICE_PAGE_SIZE = 5;
const voiceShowCount = ref(VOICE_PAGE_SIZE);
const visibleVoices = computed(() => voices.value.slice(0, voiceShowCount.value));
const hasMoreVoices = computed(() => voices.value.length > voiceShowCount.value);
const loadMoreVoices = () => {
    voiceShowCount.value = Math.min(voices.value.length, voiceShowCount.value + VOICE_PAGE_SIZE);
};

const typeOptions = computed(() =>
    TYPE_CARDS.filter((o) => o.val !== WashGenerationType.NEWS || generationType.value === WashGenerationType.NEWS),
);
const currentType = computed(() => TYPE_CARDS.find((o) => o.val === generationType.value));
const needAvatar = computed(() => !!currentType.value?.needAvatar);
const needVoice = computed(() => !!currentType.value?.needVoice);

const pickType = (val: number) => {
    generationType.value = val;
};

const loadOptions = async () => {
    loading.value = true;
    try {
        const res = await getGenerationOptions({ id: props.taskId });
        avatars.value = res?.avatars || [];
        voices.value = res?.voices || [];
        const savedType = Number(res?.generation_type);
        if ([WashGenerationType.DIGITAL_HUMAN, WashGenerationType.MATERIAL, WashGenerationType.NEWS].includes(savedType)) {
            generationType.value = savedType;
        }
        avatarId.value = Number(res?.selected_avatar_id) || 0;
        voiceId.value = Number(res?.selected_voice_id) || 0;
        // 已选音色不在首屏时，展开到能看到它为止
        const selectedIndex = voices.value.findIndex((v: any) => Number(v.id) === voiceId.value);
        voiceShowCount.value = Math.max(VOICE_PAGE_SIZE, selectedIndex + 1);
    } catch (error: any) {
        uni.$u.toast(error || "获取可用形象音色失败");
    } finally {
        loading.value = false;
    }
};

// ── 音色试听 ──
let audio: UniApp.InnerAudioContext | null = null;
const playingId = ref(0);

const stopPreview = () => {
    if (audio) {
        audio.stop();
        audio.destroy();
        audio = null;
    }
    playingId.value = 0;
};

const togglePreview = (v: any) => {
    if (playingId.value === v.id) {
        stopPreview();
        return;
    }
    if (!v.preview_url) {
        uni.$u.toast("该音色暂无试听");
        return;
    }
    stopPreview();
    audio = uni.createInnerAudioContext();
    audio.src = v.preview_url;
    audio.onEnded(() => (playingId.value = 0));
    audio.onError(() => {
        playingId.value = 0;
        uni.$u.toast("试听播放失败");
    });
    audio.play();
    playingId.value = v.id;
};

const handleConfirm = () => {
    if (props.submitting) return;
    if (!generationType.value) {
        uni.$u.toast("请选择视频类型");
        return;
    }
    if (needAvatar.value && !avatarId.value) {
        uni.$u.toast("请选择数字人形象");
        return;
    }
    if (needVoice.value && !voiceId.value) {
        uni.$u.toast("请选择音色");
        return;
    }
    stopPreview();
    emit("confirm", {
        generation_type: generationType.value,
        avatar_id: needAvatar.value ? avatarId.value : 0,
        voice_id: needVoice.value ? voiceId.value : 0,
    });
};

onMounted(loadOptions);
onUnmounted(stopPreview);
</script>

<style scoped lang="scss">
.avatar-strip {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    &::-webkit-scrollbar {
        display: none;
    }
}
</style>

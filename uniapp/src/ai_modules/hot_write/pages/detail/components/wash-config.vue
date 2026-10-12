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
                <view v-if="avatars.length" class="pick-strip">
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
                <template v-if="voiceOptions.length">
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
                        <text class="text-[22rpx] text-primary">加载更多音色（还有 {{ voiceOptions.length - voiceShowCount }} 个）</text>
                        <u-icon name="arrow-down" color="#0065fb" size="20"></u-icon>
                    </view>
                </template>
                <text v-else class="text-[21rpx] text-[#9CA3AF] block">{{ voiceEmptyTip }}</text>
            </view>

            <!-- 视频模板：与热点追踪同款大卡，封面看配色、播放键看样片 -->
            <view class="mt-[28rpx]">
                <view class="flex items-center justify-between gap-[10rpx] mb-[14rpx]">
                    <view class="min-w-0">
                        <text class="text-[24rpx] font-bold text-[#111827]">视频模板</text>
                        <text class="block text-[20rpx] text-[#9CA3AF]">选填 · 点播放键看样片</text>
                    </view>
                    <view
                        v-if="currentTemplates.length"
                        class="flex items-center gap-[8rpx] flex-shrink-0 py-[16rpx] text-primary"
                        role="button"
                        :aria-label="showAllTemplates ? '收起模板' : '展开全部模板'"
                        @click="showAllTemplates = !showAllTemplates">
                        <text class="text-[22rpx]">{{ showAllTemplates ? "收起模板" : "展开全部" }}</text>
                        <u-icon :name="showAllTemplates ? 'arrow-up' : 'arrow-down'" color="#0065fb" size="20"></u-icon>
                    </view>
                </view>
                <scroll-view :key="generationType" :scroll-x="!showAllTemplates" class="w-full">
                    <view
                        :class="showAllTemplates ? 'grid grid-cols-2 gap-[16rpx]' : 'flex gap-[16rpx] pr-[8rpx]'"
                        :style="showAllTemplates ? '' : 'width: max-content'">
                        <!-- 自动随机放首位，等价于不指定模板 -->
                        <view
                            class="min-w-0 box-border flex-shrink-0 rounded-[16rpx] overflow-hidden bg-white border-[3rpx] border-solid"
                            :class="[
                                showAllTemplates ? 'w-full' : 'w-[228rpx]',
                                clipTemplateId ? 'border-[#EDEFF3]' : 'border-primary',
                            ]"
                            @click="clipTemplateId = ''">
                            <view class="w-full h-[304rpx] bg-[#F3F4F6] flex flex-col items-center justify-center">
                                <u-icon name="reload" :color="clipTemplateId ? '#9CA3AF' : '#0065fb'" size="44"></u-icon>
                                <text class="text-[20rpx] text-[#9CA3AF] mt-[12rpx]">每次随机挑一个</text>
                            </view>
                            <text
                                class="block text-[22rpx] text-center py-[12rpx] truncate px-[8rpx]"
                                :class="clipTemplateId ? 'text-[#6B7280]' : 'text-primary font-semibold'">
                                自动随机
                            </text>
                        </view>
                        <view
                            v-for="t in currentTemplates"
                            :key="t.id"
                            class="min-w-0 box-border flex-shrink-0 rounded-[16rpx] overflow-hidden bg-white border-[3rpx] border-solid"
                            :class="[
                                showAllTemplates ? 'w-full' : 'w-[228rpx]',
                                clipTemplateId === t.id ? 'border-primary' : 'border-[#EDEFF3]',
                            ]"
                            @click="pickClipTemplate(t.id)">
                            <view class="relative w-full h-[304rpx] bg-[#F3F4F6]">
                                <image
                                    v-if="t.cover_url"
                                    :src="t.cover_url"
                                    mode="aspectFill"
                                    lazy-load
                                    class="w-full h-full" />
                                <!-- 封面只看得出配色看不出剪辑风格，播放键单独拦一下点击去放样片 -->
                                <view
                                    v-if="t.demo_url"
                                    class="absolute inset-0 flex items-center justify-center"
                                    @click.stop="previewClipTemplate(t)">
                                    <view
                                        class="w-[72rpx] h-[72rpx] rounded-full flex items-center justify-center"
                                        style="background: rgba(0, 0, 0, 0.45)">
                                        <u-icon name="play-right-fill" color="#ffffff" size="30" class="ml-[4rpx]"></u-icon>
                                    </view>
                                </view>
                                <view
                                    v-if="clipTemplateId === t.id"
                                    class="absolute top-[8rpx] right-[8rpx] w-[36rpx] h-[36rpx] rounded-full bg-primary flex items-center justify-center">
                                    <u-icon name="checkmark" color="#ffffff" size="20"></u-icon>
                                </view>
                            </view>
                            <text
                                class="block text-[22rpx] text-center py-[12rpx] truncate px-[8rpx]"
                                :class="clipTemplateId === t.id ? 'text-primary font-semibold' : 'text-[#6B7280]'">
                                {{ t.name || "未命名模板" }}
                            </text>
                        </view>
                    </view>
                </scroll-view>
                <text v-if="!currentTemplates.length" class="text-[21rpx] text-[#9CA3AF] block mt-[8rpx]">
                    当前视频类型暂无可选模板，将自动随机
                </text>
            </view>

            <!-- 背景音乐来源 -->
            <view class="mt-[28rpx]">
                <text class="text-[24rpx] font-bold text-[#111827] block mb-[14rpx]">背景音乐</text>
                <view class="flex items-center gap-[14rpx]">
                    <view
                        v-for="opt in MUSIC_SOURCE_OPTIONS"
                        :key="opt.val"
                        class="flex-1 rounded-[16rpx] px-[16rpx] py-[18rpx] text-center"
                        :style="
                            musicSource === opt.val
                                ? 'background:#EFF6FF;border:2rpx solid #2563EB'
                                : 'background:#F9FAFB;border:2rpx solid transparent'
                        "
                        @click="pickMusicSource(opt.val)">
                        <text
                            class="text-[23rpx] font-semibold"
                            :class="musicSource === opt.val ? 'text-primary' : 'text-[#6B7280]'">
                            {{ opt.label }}
                        </text>
                    </view>
                </view>
                <view
                    v-if="musicSource === WashMusicSource.PICK"
                    class="flex items-center gap-[14rpx] mt-[14rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB]">
                    <text class="flex-1 text-[22rpx] truncate" :class="systemMusicId ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                        {{ systemMusicName || "还没有选择系统音乐" }}
                    </text>
                    <text class="text-[22rpx] text-primary font-medium" @click="showSystemMusic = true">
                        {{ systemMusicId ? "重选" : "选择" }}
                    </text>
                </view>
                <view
                    v-if="musicSource === WashMusicSource.UPLOAD"
                    class="flex items-center gap-[14rpx] mt-[14rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB]">
                    <u-icon name="volume-up" color="#2563EB" size="28"></u-icon>
                    <text class="flex-1 text-[22rpx] truncate" :class="musicUrl ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                        {{ musicName || "还没有上传音乐" }}
                    </text>
                    <text class="text-[22rpx] text-primary font-medium" @click="handleUploadMusic">
                        {{ musicUrl ? "重新上传" : "上传" }}
                    </text>
                </view>
                <text v-if="musicSource === WashMusicSource.UPLOAD" class="text-[20rpx] text-[#9CA3AF] block mt-[8rpx]">
                    支持 mp3/wav/m4a/aac，微信端需先把音频文件发到聊天再选取
                </text>
            </view>

            <!-- 音乐音量 / 口播语速 -->
            <view class="mt-[28rpx]">
                <view class="flex items-center justify-between mb-[8rpx]">
                    <text class="text-[24rpx] font-semibold text-[#374151]">背景音乐音量</text>
                    <text class="text-[22rpx] text-[#2563EB]">{{ musicVolumeLabel }}</text>
                </view>
                <slider
                    :value="musicVolumeStep"
                    :min="0"
                    :max="10"
                    :step="1"
                    block-size="20"
                    activeColor="#2563EB"
                    backgroundColor="#E5E9F0"
                    @changing="onMusicVolumeChange"
                    @change="onMusicVolumeChange" />
                <text class="text-[20rpx] text-[#9CA3AF] block mt-[-4rpx]">拖到 0 即不添加背景音乐</text>
            </view>

            <view class="mt-[24rpx]">
                <view class="flex items-center justify-between mb-[8rpx]">
                    <text
                        class="text-[24rpx] font-semibold"
                        :class="speechRateEnabled ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                        口播语速
                    </text>
                    <text class="text-[22rpx]" :class="speechRateEnabled ? 'text-[#2563EB]' : 'text-[#9CA3AF]'">
                        {{ speechRateEnabled ? speechRateLabel : "不可调" }}
                    </text>
                </view>
                <slider
                    :value="speechRateStep"
                    :min="5"
                    :max="20"
                    :step="1"
                    :disabled="!speechRateEnabled"
                    block-size="20"
                    :activeColor="speechRateEnabled ? '#2563EB' : '#C7CBD3'"
                    backgroundColor="#E5E9F0"
                    @changing="onSpeechRateChange"
                    @change="onSpeechRateChange" />
                <text v-if="speechRateHint" class="text-[20rpx] text-[#9CA3AF] block mt-[-4rpx]">
                    {{ speechRateHint }}
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

        <video-preview
            v-model="showTemplatePreview"
            title="模板样片"
            confirm-btn-text="使用该模板"
            :video-url="previewTemplate?.demo_url || ''"
            :poster="previewTemplate?.cover_url || ''"
            @confirm="pickPreviewedTemplate" />
        <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
        <system-music-sheet v-model="showSystemMusic" :selected-id="systemMusicId" @confirm="onSystemMusic" />
    </view>
</template>

<script setup lang="ts">
import { getGenerationOptions } from "@/api/hot_write";
import SystemMusicSheet from "@/components/system-music-sheet/system-music-sheet.vue";
import { WashGenerationType, WashMusicSource, WASH_ORIGIN_VOICE_ID } from "@/ai_modules/hot_write/enums";
import useUpload from "@/hooks/useUpload";
import IconVtypeDigital from "@/ai_modules/hot_write/static/icons/vtype_digital.svg";
import IconVtypeClips from "@/ai_modules/hot_write/static/icons/vtype_clips.svg";
import IconVtypeNews from "@/ai_modules/hot_write/static/icons/vtype_news.svg";

const props = defineProps<{
    taskId: string | number;
    submitting?: boolean;
}>();

const emit = defineEmits<{
    (e: "confirm", payload: {
        generation_type: number;
        avatar_id: number;
        voice_id: number;
        music_volume: number;
        speech_rate?: number;
        clip_template_id: string;
        music_url: string;
    }): void;
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

// ── 视频模板：空串 = 自动随机，与后端 wash_clip_template_id 同一约定 ──
const clipTemplates = ref<Record<string, any[]>>({});
const clipTemplateId = ref("");
const showAllTemplates = ref(false);
const currentTemplates = computed<any[]>(() => clipTemplates.value?.[String(generationType.value)] || []);
// 再点一次已选模板即取消，回到自动随机
const pickClipTemplate = (id: string) => {
    clipTemplateId.value = clipTemplateId.value === id ? "" : id;
};
/** 样片预览：播放键只在有样片时渲染，这里再兜一层，别弹出一个空播放器 */
const showTemplatePreview = ref(false);
const previewTemplate = ref<any>(null);
const previewClipTemplate = (template: any) => {
    if (!template.demo_url) {
        pickClipTemplate(template.id);
        return;
    }
    // 音色试听还在放时会和样片声音叠在一起
    stopPreview();
    previewTemplate.value = template;
    showTemplatePreview.value = true;
};
const pickPreviewedTemplate = () => {
    if (previewTemplate.value) clipTemplateId.value = previewTemplate.value.id;
};

// ── 背景音乐：系统随机 / 自传 ──
const MUSIC_SOURCE_OPTIONS = [
    { val: WashMusicSource.SYSTEM, label: "系统随机" },
    { val: WashMusicSource.PICK, label: "指定系统音乐" },
    { val: WashMusicSource.UPLOAD, label: "自己上传" },
];
const musicSource = ref<number>(WashMusicSource.SYSTEM);
const musicUrl = ref("");
const musicName = ref("");
const systemMusicId = ref("");
const systemMusicName = ref("");
const showSystemMusic = ref(false);
const { uploadMaterialList, showUploadProgress, uploadAndProcessFiles } = useUpload({
    count: 1,
    fileAccept: ["mp3", "wav", "m4a", "aac"],
    fileSize: 20,
    onSuccess: (materials: any[]) => {
        const m = materials?.[0];
        if (!m?.url) return;
        musicUrl.value = m.url;
        musicName.value = m.name || "已上传音乐";
    },
});
const pickMusicSource = (val: number) => {
    musicSource.value = val;
    if (val === WashMusicSource.SYSTEM) {
        musicUrl.value = "";
        musicName.value = "";
        systemMusicId.value = "";
        systemMusicName.value = "";
    } else if (val === WashMusicSource.UPLOAD) {
        systemMusicId.value = "";
        systemMusicName.value = "";
    } else if (val === WashMusicSource.PICK) {
        musicUrl.value = "";
        musicName.value = "";
        showSystemMusic.value = true;
    }
};
const onSystemMusic = (value: { id: string; name: string }) => {
    if (!value.id) {
        musicSource.value = WashMusicSource.SYSTEM;
        systemMusicId.value = "";
        systemMusicName.value = "";
        return;
    }
    musicSource.value = WashMusicSource.PICK;
    systemMusicId.value = value.id;
    systemMusicName.value = value.name;
    musicUrl.value = "";
    musicName.value = "";
};
const handleUploadMusic = async () => {
    await uploadAndProcessFiles("file");
    // 通用 useUpload 的失败提示被注释掉了，这里自己兜一下
    const failed = uploadMaterialList.value.find((i: any) => i.status === "fail");
    if (failed && !musicUrl.value) uni.$u.toast("音乐上传失败，请重试");
};

/** 音色列表前端分页：默认展示前 N 条，点击加载更多逐步展开 */
// 滑块用整数步进（原生 slider 不接受小数 step）：音量 0~10 ⇔ 0~1；语速 5~20 ⇔ 0.5~2.0
const DEFAULT_MUSIC_VOLUME = 0.5;
const DEFAULT_SPEECH_RATE = 1.0;
const musicVolumeStep = ref(Math.round(DEFAULT_MUSIC_VOLUME * 10));
const speechRateStep = ref(Math.round(DEFAULT_SPEECH_RATE * 10));
const onMusicVolumeChange = (e: any) => (musicVolumeStep.value = Number(e?.detail?.value ?? musicVolumeStep.value));
const onSpeechRateChange = (e: any) => (speechRateStep.value = Number(e?.detail?.value ?? speechRateStep.value));
const musicVolumeLabel = computed(() =>
    musicVolumeStep.value === 0 ? "不添加背景音乐" : `${musicVolumeStep.value * 10}%`,
);
const speechRateLabel = computed(() => `${(speechRateStep.value / 10).toFixed(1)}x`);

const selectedAvatar = computed(() => avatars.value.find((a: any) => Number(a.id) === avatarId.value));

/** 形象原音跟着所选形象走：只有数字人口播、且该形象克隆时出了原音才给这个选项 */
const originVoiceOption = computed(() => {
    if (generationType.value !== WashGenerationType.DIGITAL_HUMAN) return null;
    const avatar = selectedAvatar.value;
    if (!avatar?.origin_voice_available) return null;
    const avatarName = String(avatar.name || "").trim();
    return {
        id: WASH_ORIGIN_VOICE_ID,
        name: avatarName ? `形象原音·${avatarName}` : "形象原音",
        provider: "shanjian",
        speech_rate_supported: true,
        preview_url: avatar.origin_voice_preview_url || "",
    };
});

const voiceOptions = computed<any[]>(() =>
    originVoiceOption.value ? [originVoiceOption.value, ...voices.value] : voices.value,
);

// 换形象或换视频类型后原音可能已不可选，清掉选中态避免提交一个后端会拒的音色
watch([avatarId, generationType], () => {
    if (voiceId.value === WASH_ORIGIN_VOICE_ID && !originVoiceOption.value) {
        voiceId.value = 0;
    }
});

/** 当前所选音色是否支持语速：MiniMax 走音频驱动，闪剪会忽略 speedRatio */
const selectedVoice = computed(() => voiceOptions.value.find((v: any) => Number(v.id) === voiceId.value));
const speechRateEnabled = computed(() => {
    if (generationType.value === WashGenerationType.NEWS) return false;
    if (!voiceId.value) return false;
    return selectedVoice.value?.speech_rate_supported !== false;
});
const speechRateHint = computed(() => {
    if (generationType.value === WashGenerationType.NEWS) return "新闻体无口播，语速不适用";
    if (!voiceId.value) return "请先选择音色";
    return speechRateEnabled.value ? "" : "该音色暂不支持调节语速";
});

/** 没有克隆音色时，别让用户以为只能去声音克隆——先选形象就能用原音 */
const voiceEmptyTip = computed(() =>
    generationType.value === WashGenerationType.DIGITAL_HUMAN && !avatarId.value
        ? "请先选择上方形象，可直接使用该形象的原音"
        : "暂无可用音色，请先在「声音克隆」中创建音色后再来选择",
);

const VOICE_PAGE_SIZE = 5;
const voiceShowCount = ref(VOICE_PAGE_SIZE);
const visibleVoices = computed(() => voiceOptions.value.slice(0, voiceShowCount.value));
const hasMoreVoices = computed(() => voiceOptions.value.length > voiceShowCount.value);
const loadMoreVoices = () => {
    voiceShowCount.value = Math.min(voiceOptions.value.length, voiceShowCount.value + VOICE_PAGE_SIZE);
};

const typeOptions = computed(() =>
    TYPE_CARDS.filter((o) => o.val !== WashGenerationType.NEWS || generationType.value === WashGenerationType.NEWS),
);
const currentType = computed(() => TYPE_CARDS.find((o) => o.val === generationType.value));
const needAvatar = computed(() => !!currentType.value?.needAvatar);
const needVoice = computed(() => !!currentType.value?.needVoice);

const pickType = (val: number) => {
    if (generationType.value === val) return;
    generationType.value = val;
    // 换类型后模板 scene 变了，旧选择不再适用；放在这里而不是 watch，
    // 是为了不让首屏回显时的类型赋值把已存的模板冲掉
    clipTemplateId.value = "";
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
        clipTemplates.value = res?.clip_templates || {};
        clipTemplateId.value = String(res?.selected_clip_template_id || "");
        musicUrl.value = String(res?.selected_music_url || "");
        musicName.value = musicUrl.value ? "已上传音乐" : "";
        systemMusicId.value = String(res?.selected_system_music_id || "");
        const catalog = Array.isArray(res?.system_musics) ? res.system_musics : [];
        systemMusicName.value = catalog.find((item: any) => item.id == systemMusicId.value)?.name || "";
        const savedSource = Number(res?.music_source);
        musicSource.value = savedSource === WashMusicSource.UPLOAD
            ? WashMusicSource.UPLOAD
            : savedSource === WashMusicSource.PICK || systemMusicId.value
              ? WashMusicSource.PICK
              : WashMusicSource.SYSTEM;
        // -1 = 后端未设置，用默认值作为滑块初始位置
        const savedVolume = Number(res?.music_volume);
        musicVolumeStep.value = Math.round(
            (savedVolume >= 0 ? savedVolume : Number(res?.default_music_volume ?? DEFAULT_MUSIC_VOLUME)) * 10,
        );
        const savedRate = Number(res?.speech_rate);
        speechRateStep.value = Math.round(
            (savedRate >= 0 ? savedRate : Number(res?.default_speech_rate ?? DEFAULT_SPEECH_RATE)) * 10,
        );
        // 已选音色不在首屏时，展开到能看到它为止
        const selectedIndex = voiceOptions.value.findIndex((v: any) => Number(v.id) === voiceId.value);
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
    if (musicSource.value === WashMusicSource.UPLOAD && !musicUrl.value) {
        uni.$u.toast("请先上传背景音乐，或改回系统音乐随机");
        return;
    }
    if (musicSource.value === WashMusicSource.PICK && !systemMusicId.value) {
        uni.$u.toast("请先选择一首系统音乐，或改回系统随机");
        return;
    }
    emit("confirm", {
        generation_type: generationType.value,
        avatar_id: needAvatar.value ? avatarId.value : 0,
        voice_id: needVoice.value ? voiceId.value : 0,
        clip_template_id: clipTemplateId.value,
        music_url: musicSource.value === WashMusicSource.UPLOAD ? musicUrl.value : "",
        system_music_id: musicSource.value === WashMusicSource.PICK ? systemMusicId.value : "",
        music_volume: Number((musicVolumeStep.value / 10).toFixed(1)),
        // 语速不可调时不提交，避免落一个不会生效的值
        ...(speechRateEnabled.value ? { speech_rate: Number((speechRateStep.value / 10).toFixed(1)) } : {}),
    });
};

onMounted(loadOptions);
onUnmounted(stopPreview);
</script>

<style scoped lang="scss">
.pick-strip {
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

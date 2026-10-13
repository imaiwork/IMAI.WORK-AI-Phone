<template>
    <view>
        <view class="flex items-center gap-[20rpx] py-[24rpx]" @click="expanded = !expanded">
            <view class="adv-ico" style="background: #f3eeff">
                <u-icon name="setting" color="#7B61FF" size="20" />
            </view>
            <view class="flex-1 min-w-0">
                <text class="block text-[28rpx] font-bold text-[#0D1117]">高级设置</text>
                <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx] line-clamp-1">{{ summary }}</text>
            </view>
            <u-icon :name="expanded ? 'arrow-up' : 'arrow-down'" color="#C0C4CC" size="18" />
        </view>

        <view v-if="expanded">
            <view
                v-for="item in PACK_ITEMS"
                :key="item.key"
                class="flex items-center gap-[20rpx] py-[24rpx] border-[0] border-t border-solid border-[#F4F6FA]">
                <view class="adv-ico" :style="{ background: item.bg }">
                    <u-icon :name="item.icon" :color="item.color" size="18" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[27rpx] font-bold text-[#0D1117]">{{ item.label }}</text>
                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">{{ item.desc }}</text>
                </view>
                <u-switch
                    :model-value="isOn(item.key)"
                    size="34"
                    active-color="#0065fb"
                    inactive-color="#E5E5E5"
                    @change="handleSwitch(item.key, $event)" />
            </view>

            <view
                v-if="showMusic"
                class="flex items-start gap-[20rpx] py-[24rpx] border-[0] border-t border-solid border-[#F4F6FA]">
                <view class="adv-ico" style="background: #eef0ff">
                    <image
                        src="@/static/images/digital_human/music_black.svg"
                        class="w-[30rpx] h-[30rpx]"
                        mode="aspectFit" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[27rpx] font-bold text-[#0D1117]">背景音乐</text>
                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx] mb-[18rpx]">{{ musicTip }}</text>

                    <view class="flex gap-[12rpx]">
                        <view
                            v-for="opt in MUSIC_SOURCE_OPTIONS"
                            :key="opt.value"
                            class="adv-seg"
                            :class="{ 'adv-seg--on': musicSource === opt.value }"
                            @click="handleMusicSource(opt.value)">
                            <text>{{ opt.label }}</text>
                        </view>
                    </view>

                    <view
                        v-if="musicSource === MusicSourceEnum.SYSTEM"
                        class="mt-[18rpx] flex items-center gap-[12rpx] px-[16rpx] py-[14rpx] rounded-[16rpx] bg-[#F4F6FA]">
                        <text class="flex-1 min-w-0 text-[24rpx] text-[#374151] line-clamp-1">
                            {{ modelValue.system_music_name || "当前：系统随机" }}
                        </text>
                        <text class="flex-shrink-0 text-[24rpx] text-primary font-bold" @click="showSystemMusic = true">
                            换一首
                        </text>
                    </view>

                    <view v-if="musicSource === MusicSourceEnum.CUSTOM" class="mt-[18rpx]">
                        <view class="adv-upload" @click="handleChooseMusic">
                            <u-icon name="attach" color="#9CA3AF" size="18" />
                            <text class="text-[24rpx] font-bold text-[#6B7280] line-clamp-1">
                                {{ modelValue.music_name || "选择音乐文件" }}
                            </text>
                        </view>
                        <text class="block text-[20rpx] text-[#C0C4CC] mt-[10rpx]">
                            支持 {{ musicAcceptText }}，{{ ADVANCED_MUSIC_SIZE }}MB 以内
                        </text>
                    </view>

                    <view v-if="musicSource !== MusicSourceEnum.NONE" class="mt-[24rpx]">
                        <view class="flex items-center justify-between mb-[8rpx]">
                            <text class="text-[22rpx] text-[#9CA3AF]">音乐音量</text>
                            <text class="text-[24rpx] font-bold text-primary">{{ musicVolumePercent }}%</text>
                        </view>
                        <slider
                            :value="musicVolumePercent"
                            :min="10"
                            :max="100"
                            :step="10"
                            active-color="#0065fb"
                            background-color="#E5E7EB"
                            block-size="16"
                            @change="handleMusicVolume"
                            @changing="handleMusicVolume" />
                    </view>
                </view>
            </view>
        </view>

        <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
        <system-music-sheet
            v-model="showSystemMusic"
            :selected-id="modelValue.system_music_id"
            @confirm="onSystemMusic" />
    </view>
</template>

<script setup lang="ts">
import useUpload from "@/hooks/useUpload";
import SystemMusicSheet from "@/components/system-music-sheet/system-music-sheet.vue";
import { MusicSourceEnum } from "@/enums/digitalHuman";
import {
    ADVANCED_MUSIC_ACCEPT,
    ADVANCED_MUSIC_SIZE,
    ADVANCED_MUSIC_VOLUME_DEFAULT,
    type AdvancedSetting,
    type PackSwitchSetting,
} from "@/ai_modules/digital_human_create/config/advanced-setting";

type PackSwitchKey = keyof PackSwitchSetting;
/** 宿主可以是混剪的 extra，也可以是纯口播的 packaging，组件只认里面这几个键 */
type SettingValue = PackSwitchSetting & Partial<AdvancedSetting> & Record<string, any>;

const props = withDefaults(
    defineProps<{
        modelValue: SettingValue;
        /** 背景音乐只在没有独立音乐入口的页面显示（目前只有数字人纯口播） */
        showMusic?: boolean;
    }>(),
    {
        showMusic: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: SettingValue): void;
}>();

const PACK_ITEMS: Array<{
    key: PackSwitchKey;
    label: string;
    desc: string;
    icon: string;
    color: string;
    bg: string;
}> = [
    {
        key: "header_switch",
        label: "标题包装",
        desc: "画面顶部大字标题，带底色块或花字",
        icon: "file-text",
        color: "#F0883E",
        bg: "#FFF1E8",
    },
    {
        key: "material_switch",
        label: "素材包装",
        desc: "转场、运镜特效、滤镜色调",
        icon: "photo",
        color: "#3BAF63",
        bg: "#EAF7EE",
    },
    {
        key: "subtitle_switch",
        label: "字幕包装",
        desc: "口播字幕的字体、描边与底色条",
        icon: "list",
        color: "#0065fb",
        bg: "#EBF2FF",
    },
    {
        key: "keyword_switch",
        label: "关键词包装",
        desc: "念到重点字时放大弹出，配音效",
        icon: "star",
        color: "#E05B84",
        bg: "#FDEEF3",
    },
];

const PACK_SHORT_LABEL: Record<PackSwitchKey, string> = {
    header_switch: "标题",
    material_switch: "素材",
    subtitle_switch: "字幕",
    keyword_switch: "关键词",
};

const MUSIC_SOURCE_OPTIONS = [
    { value: MusicSourceEnum.SYSTEM, label: "系统推荐" },
    { value: MusicSourceEnum.CUSTOM, label: "自己上传" },
    { value: MusicSourceEnum.NONE, label: "不要音乐" },
];

const MUSIC_SOURCE_TIPS: Record<MusicSourceEnum, string> = {
    [MusicSourceEnum.SYSTEM]: "每次从系统曲库随机一首",
    [MusicSourceEnum.CUSTOM]: "上传一首自己的音乐",
    [MusicSourceEnum.NONE]: "成片只有口播，没有背景音乐",
};

const expanded = ref(false);

const musicAcceptText = computed(() => ADVANCED_MUSIC_ACCEPT.map((item) => item.toUpperCase()).join(" / "));

const musicSource = computed(() => props.modelValue.music_source ?? MusicSourceEnum.SYSTEM);

const musicVolumePercent = computed(() =>
    Math.round((props.modelValue.music_volume ?? ADVANCED_MUSIC_VOLUME_DEFAULT) * 100),
);

const showSystemMusic = ref(false);

const musicTip = computed(() => {
    if (musicSource.value === MusicSourceEnum.CUSTOM && props.modelValue.music_name) {
        return `已选：${props.modelValue.music_name}`;
    }
    if (musicSource.value === MusicSourceEnum.SYSTEM && props.modelValue.system_music_name) {
        return `已指定：${props.modelValue.system_music_name}`;
    }
    return MUSIC_SOURCE_TIPS[musicSource.value];
});

const isOn = (key: PackSwitchKey): boolean => props.modelValue[key] !== false;

// 折叠时也要一眼看出配了什么，摘要跟着开关和音乐来源走
const summary = computed(() => {
    const off = PACK_ITEMS.filter((item) => !isOn(item.key));
    const packText =
        off.length === 0
            ? `${PACK_ITEMS.length} 项包装全开`
            : off.length === PACK_ITEMS.length
            ? "包装项已全部关闭"
            : `已关闭：${off.map((item) => PACK_SHORT_LABEL[item.key]).join("、")}`;
    if (!props.showMusic) return packText;
    const musicText =
        musicSource.value === MusicSourceEnum.CUSTOM
            ? props.modelValue.music_name || "自己上传"
            : musicSource.value === MusicSourceEnum.SYSTEM && props.modelValue.system_music_name
            ? props.modelValue.system_music_name
            : MUSIC_SOURCE_OPTIONS.find((item) => item.value === musicSource.value)?.label;
    return `${packText} · 背景音乐：${musicText}`;
});

const patch = (value: Partial<SettingValue>): void => {
    emit("update:modelValue", { ...props.modelValue, ...value });
};

const handleSwitch = (key: PackSwitchKey, value: boolean): void => {
    patch({ [key]: value } as Partial<SettingValue>);
};

const handleMusicSource = (value: MusicSourceEnum): void => {
    if (musicSource.value === value) return;
    patch({
        music_source: value,
        ...(value === MusicSourceEnum.SYSTEM
            ? {}
            : { system_music_id: "", system_music_name: "", system_music_url: "" }),
    });
};

const onSystemMusic = (value: { id: string; name: string; audio_url?: string }): void => {
    patch({
        music_source: MusicSourceEnum.SYSTEM,
        system_music_id: value.id,
        system_music_name: value.name,
        system_music_url: value.id ? value.audio_url || "" : "",
    });
};

const handleMusicVolume = (e: any): void => {
    patch({ music_volume: Number((e.detail.value / 100).toFixed(1)) });
};

const { uploadAndProcessFiles, uploadMaterialList, showUploadProgress } = useUpload({
    count: 1,
    fileAccept: ADVANCED_MUSIC_ACCEPT,
    fileSize: ADVANCED_MUSIC_SIZE,
    onSuccess: (materials) => {
        const [music] = materials;
        if (!music) return;
        patch({ music_url: music.url, music_name: music.name });
    },
});

const handleChooseMusic = (): void => {
    uploadAndProcessFiles("file");
};
</script>

<style lang="scss" scoped>
.adv-ico {
    @apply w-[56rpx] h-[56rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0;
}

.adv-seg {
    @apply flex-1 h-[64rpx] rounded-[16rpx] flex items-center justify-center;
    @apply text-[24rpx] font-bold text-[#8A93A3] bg-[#F4F6FA];
    @apply border-[2rpx] border-solid border-[transparent];
}

.adv-seg--on {
    @apply bg-[#EBF2FF] text-primary border-primary;
}

.adv-upload {
    @apply w-full h-[76rpx] rounded-[16rpx] flex items-center justify-center gap-[10rpx];
    @apply bg-[#F4F6FA] border-[2rpx] border-dashed border-[#C9D2E0];
}
</style>

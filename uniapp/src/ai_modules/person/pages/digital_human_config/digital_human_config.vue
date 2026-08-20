<template>
    <view class="min-h-screen bg-[#F7F9FC] pb-[250rpx] relative">
        <u-navbar :border-bottom="false" :background="{ background: navBgColor }" title="配置数字人" title-bold />

        <template v-if="loading">
            <view class="px-[30rpx] pt-4 flex flex-col gap-8">
                <view class="bg-[#F0F0F0] rounded-[24rpx] h-[100rpx] animate-pulse mb-6" />
                <view>
                    <view class="flex items-center justify-between mb-5">
                        <view class="h-[40rpx] w-[200rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                        <view class="h-[52rpx] w-[140rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                    </view>
                    <view class="grid grid-cols-3 gap-x-3 gap-y-5">
                        <view v-for="i in 3" :key="i" class="flex flex-col gap-2.5">
                            <view class="w-full aspect-[3/4] rounded-[24rpx] bg-[#F0F0F0] animate-pulse" />
                            <view class="h-[28rpx] w-3/4 mx-auto bg-[#F0F0F0] rounded-full animate-pulse" />
                        </view>
                    </view>
                </view>
                <view>
                    <view class="flex items-center justify-between mb-5">
                        <view class="h-[40rpx] w-[200rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                        <view class="h-[52rpx] w-[140rpx] bg-[#F0F0F0] rounded-full animate-pulse" />
                    </view>
                    <view class="flex flex-col gap-3.5">
                        <view
                            v-for="i in 3"
                            :key="i"
                            class="bg-white rounded-[32rpx] p-4 flex items-center gap-4 border border-solid border-white">
                            <view
                                class="w-[96rpx] h-[96rpx] rounded-[28rpx] bg-[#F0F0F0] animate-pulse flex-shrink-0" />
                            <view class="flex-1 flex flex-col gap-2">
                                <view class="h-[32rpx] w-1/2 bg-[#F0F0F0] rounded-full animate-pulse" />
                                <view class="h-[24rpx] w-1/3 bg-[#F0F0F0] rounded-full animate-pulse" />
                            </view>
                            <view class="w-[100rpx] h-[56rpx] rounded-[16rpx] bg-[#F0F0F0] animate-pulse" />
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="px-[30rpx] pt-4 flex flex-col gap-8 relative z-10">
                <view
                    class="flex items-start gap-3 bg-[#EBF3FF] border border-solid border-[#C5D9FF] rounded-[24rpx] px-4 py-4 mb-6">
                    <view
                        class="w-[44rpx] h-[44rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <u-icon name="info-circle-fill" color="#ffffff" size="26" />
                    </view>
                    <text class="text-[25rpx] text-[#2D5FBF] leading-[1.7] font-medium flex-1">
                        系统将在"一键生成视频"时，根据提取的文案风格，自动从下方库中为您匹配最合适的出镜形象与音色。
                    </text>
                </view>

                <!-- ───────── 数字人形象 ───────── -->
                <view>
                    <view class="flex items-center justify-between mb-5">
                        <view class="flex items-baseline gap-1.5">
                            <text class="text-[34rpx] font-extrabold text-[#1A1A1A] tracking-wide">数字人形象</text>
                            <text class="text-xs text-[#999999] font-medium">({{ avatars.length }})</text>
                        </view>
                        <view
                            class="px-3.5 py-1.5 rounded-full bg-[#F0F5FF] text-primary flex items-center gap-1"
                            @click="handleAddAvatar">
                            <u-icon name="plus" size="20" />
                            <text class="text-xs font-bold">添加形象</text>
                        </view>
                    </view>

                    <view
                        v-if="avatars.length === 0"
                        class="flex flex-col items-center justify-center py-[60rpx] bg-white rounded-[32rpx] border border-dashed border-[#D5E3FF]">
                        <view
                            class="w-[120rpx] h-[120rpx] rounded-full bg-[#EBF3FF] flex items-center justify-center mb-5">
                            <u-icon name="account" color="#93B8FF" size="60" />
                        </view>
                        <text class="text-[28rpx] font-bold text-[#333333] mb-2">暂无出镜形象</text>
                        <text class="text-xs text-[#b4b4b4] mb-6">添加形象后，系统将自动匹配出镜人物</text>
                        <view
                            class="flex items-center gap-1.5 px-6 py-2.5 rounded-full bg-primary"
                            @click="handleAddAvatar">
                            <u-icon name="plus" color="#ffffff" size="22" />
                            <text class="font-bold text-white">立即添加形象</text>
                        </view>
                    </view>

                    <view v-else class="grid grid-cols-3 gap-x-3 gap-y-5">
                        <view v-for="item in avatars" :key="item.id" class="flex flex-col gap-2.5">
                            <view
                                class="relative w-full aspect-[3/4] rounded-[24rpx] overflow-hidden bg-[#F4F5F7] border border-solid border-[#000000]/5">
                                <image :src="item.pic" class="w-full aspect-[3/4]" mode="aspectFill" />
                                <view
                                    class="absolute top-2 right-2 w-[44rpx] h-[44rpx] rounded-full bg-[#000000]/30 flex items-center justify-center border border-[#ffffff]/20"
                                    @click.stop="handleRemoveAvatar(item)">
                                    <u-icon name="close" color="#ffffff" size="20" />
                                </view>

                                <view
                                    v-if="item.voice_name"
                                    class="absolute bottom-0 left-0 right-0 px-[10rpx] py-[8rpx] flex items-center gap-[6rpx]">
                                    <u-icon name="volume-up" color="#ffffff" size="18" />
                                    <text class="text-[20rpx] text-white font-medium truncate flex-1">{{
                                        item.voice_name
                                    }}</text>
                                </view>
                            </view>

                            <text class="text-[28rpx] text-[#333333] font-bold text-center truncate px-1">
                                {{ item.name }}
                            </text>

                            <view
                                class="flex items-center justify-center gap-[6rpx] rounded-[16rpx] py-[10rpx] border border-solid bg-[#EBF3FF] border-[#C5D9FF]"
                                @click="handleOpenVoiceForAvatar(item)">
                                <text class="text-[22rpx] font-medium truncate text-primary">
                                    {{ item.is_original_voice == 1 ? item.bind_desc : item.voice_name }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>

                <view>
                    <view class="flex items-center justify-between mb-5">
                        <view class="flex items-baseline gap-1.5">
                            <text class="text-[34rpx] font-extrabold text-[#1A1A1A] tracking-wide">数字人音色</text>
                            <text class="text-xs text-[#999999] font-medium">({{ voices.length }})</text>
                        </view>
                        <view
                            class="px-3.5 py-1.5 rounded-full bg-[#F0F5FF] text-primary flex items-center gap-1"
                            @click="handleAddVoice">
                            <u-icon name="plus" size="20" />
                            <text class="text-xs font-bold">添加音色</text>
                        </view>
                    </view>

                    <view
                        v-if="voices.length === 0"
                        class="flex flex-col items-center justify-center py-[60rpx] bg-white rounded-[32rpx] border border-dashed border-[#D5E3FF]">
                        <view
                            class="w-[120rpx] h-[120rpx] rounded-full bg-[#EBF3FF] flex items-center justify-center mb-5">
                            <u-icon name="volume-up" color="#93B8FF" size="60" />
                        </view>
                        <text class="text-[28rpx] font-bold text-[#333333] mb-2">暂无音色配置</text>
                        <text class="text-xs text-[#b4b4b4] mb-6">添加音色后，系统将自动为视频匹配声音</text>
                        <view
                            class="flex items-center gap-1.5 px-6 py-2.5 rounded-full bg-primary"
                            @click="handleAddVoice">
                            <u-icon name="plus" color="#ffffff" size="22" />
                            <text class="font-bold text-white">立即添加音色</text>
                        </view>
                    </view>

                    <view v-else class="flex flex-col gap-3.5">
                        <view
                            v-for="item in voices"
                            :key="item.voice_id"
                            class="bg-white rounded-[32rpx] p-4 flex items-center shadow-[0_4rpx_24rpx_rgba(0,0,0,0.02)] border border-solid border-white relative">
                            <view
                                class="w-[96rpx] h-[96rpx] rounded-[28rpx] bg-[#F0F5FF] flex-shrink-0 flex items-center justify-center mr-4 border border-solid border-white">
                                <u-icon name="volume-up" color="#0065fb" size="48" />
                            </view>
                            <view class="flex-1 min-w-0 flex flex-col justify-center gap-1.5">
                                <text class="text-[32rpx] font-extrabold text-[#1A1A1A] truncate">{{ item.name }}</text>
                            </view>

                            <view class="flex items-center gap-2">
                                <view
                                    class="flex items-center justify-center gap-x-[8rpx] rounded-[16rpx] px-[20rpx] py-[12rpx] flex-shrink-0"
                                    :class="getPlayBtnClass(item.voice_id)"
                                    @click.stop="handlePlayVoice(item)">
                                    <u-icon
                                        :name="getPlayIcon(item.voice_id)"
                                        :size="30"
                                        :color="getPlayColor(item.voice_id)" />
                                    <text class="text-xs font-medium" :class="getPlayTextClass(item.voice_id)">
                                        {{ isCurrentPlaying(item.voice_id) ? "暂停" : "试听" }}
                                    </text>
                                </view>
                            </view>

                            <view class="absolute top-[-12rpx] right-[-12rpx]">
                                <view
                                    class="w-[44rpx] h-[44rpx] rounded-full bg-[#F4F5F7] flex items-center justify-center border border-solid border-white"
                                    @click="handleRemoveVoice(item)">
                                    <u-icon name="close" color="#666666" size="20" />
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <choose-anchor v-model="showChooseAnchor" ref="chooseAnchorRef" @select="handleChooseAnchor" />

        <choose-voice
            v-if="showChooseVoice"
            v-model="showChooseVoice"
            ref="chooseVoiceRef"
            :show-free-tone="false"
            :model-version="PERSONA_MATERIAL_VOICE_MODEL_VERSIONS"
            @select="handleChooseVoice" />

        <choose-voice
            v-if="showAvatarVoicePicker"
            v-model="showAvatarVoicePicker"
            ref="avatarVoicePickerRef"
            :show-free-tone="false"
            :show-original-tone="true"
            :model-version="DigitalHumanModelVersionEnum.SHANJIAN"
            @select="handleAvatarVoiceSelect" />
    </view>
</template>

<script setup lang="ts">
import {
    getAvatarList as getAvatarListApi,
    getVoiceList as getVoiceListApi,
    addAvatar,
    addVoice,
    deleteAvatar,
    deleteVoice,
    bindAvatarVoice,
} from "@/api/person";
import { useAudio } from "@/hooks/useAudio";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { PERSONA_MATERIAL_VOICE_MODEL_VERSIONS } from "@/ai_modules/person/enums";
import ChooseAnchor from "@/ai_modules/person/components/choose-anchor/choose-anchor.vue";
import ChooseVoice from "@/ai_modules/person/components/choose-voice/choose-voice.vue";

// ─── 类型定义 ─────────────────────────────────────────────────────

interface AvatarItem {
    id: string;
    dh_id: string;
    pic: string;
    url: string;
    name: string;
    is_local?: boolean;
    /** ★ 绑定的音色 id */
    voice_id?: string;
    /** ★ 绑定的音色名称（用于展示） */
    voice_name?: string;
    bind_desc?: string;
    is_original_voice?: number;
}

interface VoiceItem {
    voice_id: string;
    id: string;
    name: string;
    url: string;
    is_local?: boolean;
}

// ─── 子组件 ref 类型 ──────────────────────────────────────────────

interface ChooseListRef {
    setChooseLists: (list: AvatarItem[] | VoiceItem[]) => void;
    setDisabledLists: (list: AvatarItem[] | VoiceItem[]) => void;
}

// ─── 页面状态 ─────────────────────────────────────────────────────

const loading = ref<boolean>(true);
const personaId = ref<string>("");

const avatars = ref<AvatarItem[]>([]);
const voices = ref<VoiceItem[]>([]);

// ─── 弹窗状态 ─────────────────────────────────────────────────────

const showChooseAnchor = ref<boolean>(false);
const showChooseVoice = ref<boolean>(false);
const chooseAnchorRef = ref<ChooseListRef | null>(null);
const chooseVoiceRef = ref<ChooseListRef | null>(null);

// ★ 形象绑定音色专用弹窗
const showAvatarVoicePicker = ref<boolean>(false);
const avatarVoicePickerRef = ref<ChooseListRef | null>(null);
/** 当前正在为哪个形象选择音色 */
const currentBindingAvatar = ref<AvatarItem | null>(null);

// ─── 音频播放 ─────────────────────────────────────────────────────

const currVoiceId = ref<string | null>(null);
const { isPlaying, play, pause, destroy } = useAudio();

const isCurrentPlaying = (voiceId: string): boolean => isPlaying.value && currVoiceId.value === voiceId;

const getPlayBtnClass = (voiceId: string) =>
    isCurrentPlaying(voiceId)
        ? "bg-[#FFF0F0] border border-solid border-[#FFD6D6]"
        : "bg-[#EEF6FF] border border-solid border-[#DBEAFE]";

const getPlayIcon = (voiceId: string) => (isCurrentPlaying(voiceId) ? "pause-circle" : "play-circle");
const getPlayColor = (voiceId: string) => (isCurrentPlaying(voiceId) ? "#FF4D4F" : "#0065fb");
const getPlayTextClass = (voiceId: string) => (isCurrentPlaying(voiceId) ? "text-[#FF4D4F]" : "text-primary");

const handlePlayVoice = (item: VoiceItem): void => {
    if (isCurrentPlaying(item.voice_id)) {
        pause();
        return;
    }
    pause();
    currVoiceId.value = item.voice_id;
    play(item.url);
};

// ─── 通用删除逻辑 ─────────────────────────────────────────────────

const confirmRemove = <T extends { is_local?: boolean }>(
    list: ReturnType<typeof ref<T[]>>,
    item: T,
    label: string,
    apiFn: (() => Promise<void>) | null,
): void => {
    uni.showModal({
        title: "提示",
        content: `确定移除${label}吗？`,
        confirmColor: "#FF4D4F",
        success: ({ confirm }) => {
            if (!confirm) return;
            (async () => {
                try {
                    uni.showLoading({ title: "删除中...", mask: true });
                    if (!item.is_local && apiFn) {
                        await apiFn();
                    }
                    (list.value as T[]) = (list.value as T[]).filter((i) => i !== item);
                    uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
                } catch (error: unknown) {
                    const msg = typeof error === "string" ? error : "删除失败，请重试";
                    uni.showToast({ title: msg, icon: "none", duration: 3000 });
                } finally {
                    uni.hideLoading();
                }
            })();
        },
    });
};

// ─── 形象操作 ─────────────────────────────────────────────────────

const handleAddAvatar = async (): Promise<void> => {
    showChooseAnchor.value = true;
    await nextTick();
    chooseAnchorRef.value?.setDisabledLists(avatars.value.map((item) => ({ ...item, id: item.dh_id })));
};

const handleChooseAnchor = async (res: AvatarItem[]): Promise<void> => {
    if (res.length === 0) return;
    const newItems = res.filter((item) => !avatars.value.some((a) => a.id === item.id));
    try {
        uni.showLoading({ title: "保存中...", mask: true });
        await addAvatar({ persona_id: personaId.value, dh_ids: newItems.map((item) => item.id) });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        getAvatarList();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const handleRemoveAvatar = (item: AvatarItem): void => {
    confirmRemove(avatars, item, `形象「${item.name}」`, item.is_local ? null : () => deleteAvatar({ ids: [item.id] }));
};

// ★ 打开形象绑定音色选择器
const handleOpenVoiceForAvatar = (item: AvatarItem): void => {
    uni.showActionSheet({
        itemList: ["选择原音", "选择音色"],
        success: async ({ tapIndex }) => {
            if (tapIndex === 0) {
                // 选择原音 → 直接调用绑定接口
                await handleBindOriginalVoice(item);
            } else if (tapIndex === 1) {
                // 选择音色 → 弹出音色选择弹窗
                currentBindingAvatar.value = item;
                showAvatarVoicePicker.value = true;
                await nextTick();
                // 若已绑定音色，则在选择器中高亮已选项
                if (item.voice_id) {
                    avatarVoicePickerRef.value?.setChooseLists([
                        {
                            voice_id: item.voice_id,
                            id: item.voice_id,
                            name: item.voice_name ?? "",
                            url: "",
                        },
                    ]);
                }
            }
        },
    });
};

// ★ 处理选择原音绑定逻辑
const handleBindOriginalVoice = async (item: AvatarItem): Promise<void> => {
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await bindAvatarVoice({
            persona_avatar_id: item.id,
            voice_id: 0,
            is_original_voice: 1,
        });
        item.is_original_voice = 1;
        item.voice_id = "";
        item.voice_name = "";
        uni.showToast({ title: "原音绑定成功", icon: "none", duration: 2000 });
    } catch (error: any) {
        uni.showToast({ title: error ?? "绑定失败，请重试", icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

// ★ 处理形象绑定音色选中回调
const handleAvatarVoiceSelect = async (res: VoiceItem[]): Promise<void> => {
    if (!currentBindingAvatar.value || res.length === 0) {
        return;
    }
    const selected = res[0];
    const target = currentBindingAvatar.value;
    uni.showLoading({ title: "保存中...", mask: true });
    try {
        await bindAvatarVoice({
            persona_avatar_id: target.id,
            voice_id: selected.id,
            is_original_voice: 0,
        });
        target.is_original_voice = 0;
        target.voice_id = selected.id;
        target.voice_name = selected.name;
        uni.hideLoading();
        uni.showToast({ title: "音色绑定成功", icon: "none", duration: 2000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error ?? "绑定失败，请重试", icon: "none", duration: 3000 });
    } finally {
        currentBindingAvatar.value = null;
    }
};

// ─── 音色操作 ─────────────────────────────────────────────────────

const handleAddVoice = async (): Promise<void> => {
    showChooseVoice.value = true;
    await nextTick();
    chooseVoiceRef.value?.setDisabledLists(voices.value.map((item) => ({ ...item, id: item.voice_id })));
};

const handleChooseVoice = async (res: VoiceItem[]): Promise<void> => {
    if (res.length === 0) return;
    const newItems = res.filter((item) => !voices.value.some((v) => v.voice_id === item.voice_id));
    try {
        uni.showLoading({ title: "保存中...", mask: true });
        await addVoice({ persona_id: personaId.value, voice_ids: newItems.map((item) => item.id) });
        uni.hideLoading();
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        getVoiceList();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    }
};

const handleRemoveVoice = (item: VoiceItem): void => {
    if (isCurrentPlaying(item.voice_id)) {
        pause();
        currVoiceId.value = null;
    }

    confirmRemove(voices, item, `音色「${item.name}」`, item.is_local ? null : () => deleteVoice({ ids: [item.id] }));
};

// ─── 数据获取 ─────────────────────────────────────────────────────

const getAvatarList = async (): Promise<void> => {
    const { lists } = await getAvatarListApi({ persona_id: personaId.value, page_size: 9999 });
    avatars.value = (lists ?? []).map((item: any) => ({
        ...item,
        pic: item.cover_url,
        url: item.video_url,
        name: item.humanAnchor?.name ?? "",
        // ★ 若接口返回了绑定音色信息，在此映射
        voice_id: item.voice_id ?? "",
        voice_name: item.voice_name ?? "",
    }));
};

const getVoiceList = async (): Promise<void> => {
    const { lists } = await getVoiceListApi({ persona_id: personaId.value, page_size: 9999 });
    voices.value = (lists ?? []).map((item: any) => ({
        ...item,
        name: item.voice_name,
        url: item.preview_audio_url,
    }));
};

const init = async (): Promise<void> => {
    try {
        await Promise.all([getAvatarList(), getVoiceList()]);
    } finally {
        loading.value = false;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────

onLoad((options: any) => {
    personaId.value = options.id ?? "";
    init();
});

// ─── 导航栏颜色随滚动变化 ─────────────────────────────────────────
const navBgColor = ref("transparent");
onPageScroll(({ scrollTop }) => {
    navBgColor.value = scrollTop > 100 ? "#ffffff" : "transparent";
});

onUnload(() => {
    pause();
    destroy();
});
</script>

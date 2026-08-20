import WechatOA from "@/utils/wechat";
import { createMontageStoryboard } from "@/api/digital_human";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import { useUserStore } from "@/stores/user";
import type { ShallowRef, Ref } from "vue";

export const VIDEO_COUNT_MIN = 1;
export const VIDEO_COUNT_MAX = 99;

export const ORDER_MODE_TABS = ["按顺序", "随机"] as const;

export const enum ClipMode {
    RANDOM = 0,
    MANUAL = 1,
}

interface UseGenerateSettingOptions {
    formData: any;
    copywriterTypeIndex: Ref<number>;
    rechargePopupRef: ShallowRef<any>;
    /**
     * 可选：覆盖提交参数构建
     * 不传则使用数字人口播混剪的默认参数结构
     */
    buildCreateParams?: () => Record<string, any>;
    /**
     * 可选：覆盖跳转发布页的参数
     */
    redirectParams?: (id: number) => Record<string, any>;
    /**
     * 可选：覆盖跳转创作记录页的参数
     */
    toRecordParams?: Record<string, any>;
}

export function useGenerateSetting({ formData, copywriterTypeIndex, rechargePopupRef }: UseGenerateSettingOptions) {
    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);
    const chooseToneRef = ref<any>(null);
    const showChooseTone = ref(false);

    const showCreateSuccess = ref(false);
    const showTokensCost = ref(false);
    const createResult = ref<any>(null);

    // ─── 音色选择 ────────────────────────────────────────────────
    const openChooseTone = async () => {
        await nextTick();
        if (formData.voiceValue.voice_id) {
            chooseToneRef.value?.setChooseLists([formData.voiceValue]);
        }
        showChooseTone.value = true;
    };

    const handleSelectTone = async (tone: any): Promise<void> => {
        if (!tone) {
            formData.voiceValue = {};
        } else {
            formData.voiceValue = tone;
        }
        showChooseTone.value = false;
    };

    // ─── 视频数量 ────────────────────────────────────────────────

    const handleVideoCount = (type: "minus" | "add"): void => {
        if (type === "minus") {
            if (formData.extra.video_count <= VIDEO_COUNT_MIN) {
                uni.$u.toast(`视频数量最少为${VIDEO_COUNT_MIN}`);
                return;
            }
            formData.extra.video_count--;
        } else {
            if (formData.extra.video_count >= VIDEO_COUNT_MAX) {
                uni.$u.toast(`视频数量最多为${VIDEO_COUNT_MAX}`);
                return;
            }
            formData.extra.video_count++;
        }
    };

    // ─── 提交前校验 ──────────────────────────────────────────────

    const validateBeforeCreate = (): boolean => {
        if (userTokens.value <= 0) {
            rechargePopupRef.value?.open();
            return false;
        }
        if (!formData.name.trim()) {
            uni.$u.toast("请输入视频名称");
            return false;
        }
        if (formData.extra.video_count < VIDEO_COUNT_MIN) {
            uni.$u.toast("请输入视频数量");
            return false;
        }
        if (formData.extra.video_count > VIDEO_COUNT_MAX) {
            uni.$u.toast(`视频数量最多为${VIDEO_COUNT_MAX}`);
            return false;
        }
        return true;
    };

    // ─── 默认提交参数（数字人口播混剪） ──────────────────────────

    const defaultBuildCreateParams = (): Record<string, any> => {
        const mediaGroupArray = formData.storyboardList.map((item: any) => ({
            GroupName: item.groupName,
            MediaArray: item.materialList.map((m: any) => m.url),
            Volume: item.is_use ? 1 : 0,
        }));
        const totalDuration = formData.storyboardList.reduce((groupAcc: number, group: any) => {
            const groupDuration = group.materialList.reduce((materialAcc: number, material: any) => {
                return materialAcc + (material.type === "video" ? Number(material.duration) : 5);
            }, 0);
            return groupAcc + groupDuration;
        }, 0);

        const params: any = {
            name: formData.name,
            number: formData.extra.video_count,
            duration: Number(totalDuration.toFixed(2)),
            minimax_voice_id: formData.voiceValue.id || undefined,
            system_voice_code: formData.voiceValue.code || undefined,
            TitleArray: formData.topTitleList,
            SpeechTextArray: formData.copywriterList,
            MediaGroupArray: mediaGroupArray,
            BackgroundMusicArray: formData.music.map((item: any) => item.content),
            BackgroundMusicVolume: formData.extra.volume,
        };

        if (copywriterTypeIndex.value === 1) {
            delete params.SpeechTextArray;
            for (let i = 0; i < mediaGroupArray.length; i++) {
                params.MediaGroupArray[i].SpeechTextArray =
                    i < formData.subtitleList.length
                        ? formData.subtitleList[i].contentList.map((item: any) => item)
                        : [];
            }
        }
        return params;
    };

    // ─── 生成视频 ────────────────────────────────────────────────

    const handleCreateVideo = async (): Promise<void> => {
        if (!validateBeforeCreate()) return;
        uni.showLoading({ title: "提交中...", mask: true });

        try {
            const res = await createMontageStoryboard(defaultBuildCreateParams());
            uni.hideLoading();
            createResult.value = res;
            showCreateSuccess.value = true;
            WechatOA.notify();
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({ title: error || "提交失败", icon: "none", duration: 3000 });
        }
    };

    // ─── 跳转 ────────────────────────────────────────────────────

    const toPublish = () => {
        showCreateSuccess.value = false;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
            type: "redirect",
            params: {
                task_id: JSON.stringify([createResult.value.id]),
                scene: 1,
                type: MontageTypeEnum.STORYBOARD_MIX,
            },
        });
    };

    const toRecord = () => {
        uni.$u.route({
            url: "/packages/pages/creation/creation",
            type: "redirect",
            params: { source: "1", type: 7 },
        });
    };
    return {
        showChooseTone,
        chooseToneRef,
        showCreateSuccess,
        showTokensCost,
        createResult,
        openChooseTone,
        handleSelectTone,
        handleVideoCount,
        handleCreateVideo,
        toPublish,
        toRecord,
    };
}

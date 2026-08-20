import WechatOA from "@/utils/wechat";
import { createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import type { ShallowRef } from "vue";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { montageConfig } from "@/ai_modules/digital_human/config";

export const VIDEO_COUNT_MIN = 1;
export const VIDEO_COUNT_MAX = 99;

export const ORDER_MODE_TABS = ["按顺序", "随机"] as const;
export const CLIP_MODE_TABS = ["随机", "手动选择"] as const;

export const enum ClipMode {
    RANDOM = 0,
    MANUAL = 1,
}

interface UseGenerateSettingOptions {
    formData: any;
    copywriterTypeIndex: Ref<number>;
    rechargePopupRef: ShallowRef<any>;
    chooseToneRef: ShallowRef<any>;
}

export function useGenerateSetting({
    formData,
    copywriterTypeIndex,
    rechargePopupRef,
    chooseToneRef,
}: UseGenerateSettingOptions) {
    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);
    const showChooseTone = ref(false);

    const voiceValue = ref<any>({});
    const showCreateSuccess = ref(false);
    const showTokensCost = ref(false);
    const createResult = ref<any>(null);

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

    // ─── 音色选择 ────────────────────────────────────────────────

    const openChooseTone = async (): Promise<void> => {
        await nextTick();
        chooseToneRef.value?.setChooseLists([{ voice_id: formData.voice?.[0]?.voice_id }]);
        showChooseTone.value = true;
    };

    const handleSelectTone = async (tone: any): Promise<void> => {
        if (!tone) {
            // 重置为形象原声
            formData.voice = formData.anchorLists.map((item: any) => ({
                voice_id: item.voice_id,
                voice_url: item.voice_url,
                name: item.name,
                model_version: DigitalHumanModelVersionEnum.SHANJIAN,
            }));
            voiceValue.value = {};
        } else {
            formData.voice = [
                {
                    voice_id: tone.voice_id,
                    voice_url: tone.voice_urls,
                    name: tone.name,
                    model_version: tone.model_version,
                },
            ];
            voiceValue.value = tone;
        }
        showChooseTone.value = false;
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
        if (formData.voice.length === 0) {
            showChooseTone.value = true;
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
        if (formData.extra.clip === ClipMode.MANUAL && formData.clip.length === 0) {
            uni.$u.toast("请选择视频风格");
            return false;
        }
        return true;
    };

    // ─── 生成视频 ────────────────────────────────────────────────

    const handleCreateVideo = async (): Promise<void> => {
        if (!validateBeforeCreate()) return;
        uni.showLoading({ title: "创建中...", mask: true });
        try {
            const res = await createShanjianTask({
                name: formData.name,
                anchor: formData.anchorLists.map((item: any) => ({
                    anchor_id: item.anchor_id,
                    pic: item.pic,
                    anchor_url: item.anchor_url,
                    name: item.name,
                })),
                character_design: [
                    {
                        name: formData.person_name,
                        introduced: formData.person_introduction,
                    },
                ],
                voice: formData.voice,
                copywriting: copywriterTypeIndex.value === 0 ? formData.copywriterList : [],
                material: formData.materialList.map((item: any) => ({
                    fileUrl: item.url,
                    type: item.type,
                    duration: item.type === "image" ? montageConfig.imageDuration : item.duration,
                })),
                music: formData.music.map((item: any) => item.content),
                extra: formData.extra,
                audio: copywriterTypeIndex.value === 1 ? formData.audio.map((item: any) => item.url) : [],
                clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
                pic: formData.cover,
            });

            if (formData.person_name && formData.person_introduction) {
                addShanjianPerson({
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                });
            }
            uni.hideLoading();
            createResult.value = res;
            showCreateSuccess.value = true;
            WechatOA.notify();
        } catch (error: unknown) {
            uni.hideLoading();
            const msg = typeof error === "string" ? error : "创建失败";
            uni.showToast({ title: msg, icon: "none", duration: 3000 });
        }
    };

    // ─── 创建成功后跳转 ──────────────────────────────────────────

    const toPublish = (): void => {
        showCreateSuccess.value = false;
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
            type: "redirect",
            params: {
                task_id: JSON.stringify([createResult.value.id]),
                scene: 1,
                type: formData.shanjian_type,
            },
        });
    };

    const toRecord = (): void => {
        uni.$u.route({
            url: "/packages/pages/creation/creation",
            type: "redirect",
            params: { source: "1", type: 2 },
        });
    };

    return {
        // 状态
        showChooseTone,
        voiceValue,
        showCreateSuccess,
        showTokensCost,
        createResult,
        // 方法
        handleVideoCount,
        openChooseTone,
        handleSelectTone,
        handleCreateVideo,
        toPublish,
        toRecord,
    };
}

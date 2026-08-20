import WechatOA from "@/utils/wechat";
import { createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import type { ShallowRef, Ref } from "vue";
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
    /** 可选：提交前额外校验，返回 false 则阻止提交 */
    extraValidate?: () => boolean;
    /** 可选：覆盖跳转发布页的参数 */
    redirectParams?: (id: number) => Record<string, any>;
    /** 可选：覆盖跳转创作记录页的参数 */
    toRecordParams?: Record<string, any>;
    chooseToneRef: ShallowRef<any>;
}

export function useGenerateSetting({
    formData,
    copywriterTypeIndex,
    rechargePopupRef,
    extraValidate,
    redirectParams,
    toRecordParams,
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

    // ─── 音色选择（数字人口播专用，其他页面自行处理） ────────────

    const openChooseTone = async (): Promise<void> => {
        await nextTick();
        chooseToneRef.value?.setChooseLists([{ voice_id: formData.voice }]);
        showChooseTone.value = true;
    };

    const handleSelectTone = (tone: any): void => {
        if (!tone) {
            voiceValue.value = {};
            formData.voice = "";
        } else {
            formData.voice = tone.voice_id;
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
        // 数字人口播：voice 是数组；真人口播：无 voice；素材混剪：通过 extraValidate 校验
        if (Array.isArray(formData.voice) && formData.voice.length === 0) {
            openChooseTone();
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
        // 外部额外校验（如素材混剪的 voice 字符串校验）
        if (extraValidate && !extraValidate()) return false;
        return true;
    };

    // ─── 默认提交参数 ────────────────────────────────────────────

    const buildCreateParams = (): Record<string, any> => ({
        name: formData.name,
        copywriting: copywriterTypeIndex.value === 0 ? formData.copywriterList : [],
        material: formData.materialList.map((group: any[]) =>
            group.map((item: any) => ({
                fileUrl: item.url,
                type: item.type,
                cover: item.pic,
                duration: item.type === "image" ? montageConfig.imageDuration : item.duration,
            })),
        ),
        model_version: voiceValue.value?.model_version || DigitalHumanModelVersionEnum.SHANJIAN,
        shanjian_type: formData.shanjian_type,
        voice: formData.voice,
        music: formData.music.map((item: any) => item.content),
        extra: formData.extra,
        audio: copywriterTypeIndex.value === 1 ? formData.audio.map((item: any) => item.url) : formData.audio,
        clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
        pic: formData.cover,
    });

    // ─── 生成视频 ────────────────────────────────────────────────

    const handleCreateVideo = async (): Promise<void> => {
        if (!validateBeforeCreate()) return;

        uni.showLoading({ title: "创建中...", mask: true });
        try {
            const res = await createShanjianTask(buildCreateParams());

            if (formData.person_name && formData.person_introduction) {
                addShanjianPerson({
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                }).catch(() => {});
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

    // ─── 跳转 ────────────────────────────────────────────────────

    const toPublish = (): void => {
        showCreateSuccess.value = false;
        const params = redirectParams
            ? redirectParams(createResult.value.id)
            : {
                  task_id: JSON.stringify([createResult.value.id]),
                  scene: 1,
                  type: formData.shanjian_type,
              };
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
            type: "redirect",
            params,
        });
    };

    const toRecord = (): void => {
        uni.$u.route({
            url: "/packages/pages/creation/creation",
            type: "redirect",
            params: toRecordParams ?? { source: "1", type: 2 },
        });
    };

    return {
        showChooseTone,
        voiceValue,
        showCreateSuccess,
        showTokensCost,
        createResult,
        openChooseTone,
        handleVideoCount,
        handleSelectTone,
        handleCreateVideo,
        toPublish,
        toRecord,
    };
}

import { addShanjianPerson, createShanjianTask } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { MontageTypeEnum } from "@/ai_modules/digital_human/enums";
import WechatOA from "@/utils/wechat";
import { montageConfig } from "@/ai_modules/digital_human/config";

export function useGenerateSetting(formData: any) {
    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);

    const showTokensCost = ref(false);
    const showCreateSuccess = ref(false);
    const createResult = ref<any>(null);
    const rechargePopupRef = shallowRef();

    // ─── 视频数量 ────────────────────────────────────────────
    const handleMinusVideoCount = (type: "minus" | "add") => {
        if (type === "minus") {
            if (formData.extra.video_count <= 1) {
                uni.$u.toast("数量最少为1");
                return;
            }
            formData.extra.video_count--;
        } else {
            if (formData.extra.video_count >= 99) {
                uni.$u.toast("数量最多为99");
                return;
            }
            formData.extra.video_count++;
        }
    };

    // ─── 新闻体时长（5–300 秒，步长 5）────────────────────────
    const NEWS_DURATION = { min: 5, max: 300, step: 5, default: 10 } as const;

    const clampNewsDuration = (value: unknown): number => {
        const num = Math.round(Number(value));
        if (!Number.isFinite(num)) return NEWS_DURATION.default;
        return Math.max(NEWS_DURATION.min, Math.min(NEWS_DURATION.max, num));
    };

    const handleNewsDurationStep = (type: "minus" | "add") => {
        const current = clampNewsDuration(formData.extra.videoDuration);
        if (type === "minus") {
            if (current <= NEWS_DURATION.min) {
                uni.$u.toast(`最短 ${NEWS_DURATION.min} 秒`);
                return;
            }
            formData.extra.videoDuration = Math.max(NEWS_DURATION.min, current - NEWS_DURATION.step);
        } else {
            if (current >= NEWS_DURATION.max) {
                uni.$u.toast(`最长 ${NEWS_DURATION.max} 秒`);
                return;
            }
            formData.extra.videoDuration = Math.min(NEWS_DURATION.max, current + NEWS_DURATION.step);
        }
    };

    const handleNewsDurationBlur = () => {
        formData.extra.videoDuration = clampNewsDuration(formData.extra.videoDuration);
    };

    // ─── 表单校验 ────────────────────────────────────────────
    const validateForm = (): string | null => {
        if (userTokens.value <= 0) return "__recharge__";
        if (!formData.name) return "请输入视频名称";
        if (formData.extra.video_count <= 0) return "请输入视频数量";
        if (formData.extra.video_count > 99) return "视频数量最多为99";
        if (formData.extra.clip === 1 && formData.clip.length === 0) return "请选择视频风格";
        return null;
    };

    // ─── 生成视频 ────────────────────────────────────────────
    const handleCreateVideo = async () => {
        const error = validateForm();
        if (error === "__recharge__") {
            rechargePopupRef.value?.open();
            return;
        }
        if (error) {
            uni.$u.toast(error);
            return;
        }

        uni.showLoading({ title: "提交中...", mask: true });
        try {
            const res = await createShanjianTask({
                name: formData.name,
                copywriting: formData.copywriterList.map((item: any) => ({ title: [item] })),
                character_design: [
                    {
                        name: formData.person_name,
                        introduced: formData.person_introduction,
                    },
                ],
                material: formData.materialList.map((group: any) =>
                    group.map((item: any) => ({
                        fileUrl: item.url,
                        type: item.type,
                        cover: item.pic,
                        duration: item.type === "image" ? montageConfig.imageDuration : item.duration,
                    })),
                ),
                shanjian_type: formData.shanjian_type,
                music: formData.music.map((item: any) => item.content),
                extra: formData.extra,
                audio: formData.audio,
                clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
                pic: formData.cover,
            });

            // 静默保存人设，不阻塞主流程
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
        } catch (err: any) {
            uni.hideLoading();
            uni.showToast({ title: err, icon: "none", duration: 3000 });
        }
    };

    // ─── 跳转 ────────────────────────────────────────────────
    const toPublish = () => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
            type: "redirect",
            params: {
                task_id: JSON.stringify([createResult.value?.id]),
                scene: 1,
                type: formData.shanjian_type,
            },
        });
    };

    const toRecord = () => {
        uni.$u.route({
            url: "/packages/pages/creation/creation",
            type: "redirect",
            params: { source: "1", type: 5 },
        });
    };

    return {
        showTokensCost,
        showCreateSuccess,
        rechargePopupRef,
        handleMinusVideoCount,
        handleNewsDurationStep,
        handleNewsDurationBlur,
        handleCreateVideo,
        toPublish,
        toRecord,
    };
}

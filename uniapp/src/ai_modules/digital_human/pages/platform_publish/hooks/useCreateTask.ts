import type { PublishFormData } from "./types";
import { createManualPublish } from "@/api/device";

/**
 * 创建发布任务
 * - 表单校验
 * - 接口提交
 * - 成功后跳转
 */
export function useCreateTask(formData: PublishFormData) {
    const showCreateSuccess = ref(false);

    const handleCreateVideo = async () => {
        if (formData.copywriterList.length === 0) {
            uni.$u.toast("请至少填写一个文案");
            return;
        }
        const isOverLimit = formData.copywriterList.some((item) => item.content.length > 1000);
        if (isOverLimit) {
            uni.$u.toast("文案内容含有超过1000个字符的文案，请修改后重新提交");
            return;
        }

        uni.showLoading({ title: "创建中...", mask: true });
        try {
            await createManualPublish({
                name: formData.name,
                media_url: formData.materialList.map(({ url, pic, type }) => ({ url, pic, type })),
                copywriting: formData.copywriterList.map(({ title, content, topic, poi }) => ({
                    title,
                    content,
                    topic,
                    poi,
                })),
            });
            uni.hideLoading();
            showCreateSuccess.value = true;
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({ title: error, icon: "none", duration: 3000 });
        }
    };

    const toRecord = () => {
        uni.$u.route({
            url: "/ai_modules/digital_human/pages/platform_publish_works/platform_publish_works",
            type: "redirect",
        });
    };

    return { showCreateSuccess, handleCreateVideo, toRecord };
}

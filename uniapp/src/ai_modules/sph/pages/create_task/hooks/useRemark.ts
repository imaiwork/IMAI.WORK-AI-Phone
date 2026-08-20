// hooks/useRemarkStep.ts — Step3 加好友备注 & 打招呼设置
import { GreetingContentSettingTypeEnum } from "./types";
import type { SphFormData } from "./types";

export function useRemarkStep(formData: SphFormData) {
    const showAddRemark = ref(false);
    const showOCRTip = ref(false);
    const editRemarkIndex = ref(-1);
    const addRemarkContent = ref("");

    const handleGreetingContentSetting = (type: GreetingContentSettingTypeEnum) => {
        uni.$u.route({
            url: "/ai_modules/sph/pages/task_prompt/task_prompt",
            params: { type, prompt: JSON.stringify(formData[type]) },
        });
    };

    const handleAddRemark = () => {
        if (!addRemarkContent.value) {
            uni.$u.toast("请输入加好友备注内容");
            return;
        }
        if (editRemarkIndex.value === -1) {
            formData.remarks.push(addRemarkContent.value);
        } else {
            formData.remarks[editRemarkIndex.value] = addRemarkContent.value;
        }
        editRemarkIndex.value = -1;
        addRemarkContent.value = "";
        showAddRemark.value = false;
    };

    const handleEditRemark = (index: number) => {
        editRemarkIndex.value = index;
        addRemarkContent.value = formData.remarks[index];
        showAddRemark.value = true;
    };

    const handleDeleteRemark = (index: number) => formData.remarks.splice(index, 1);

    return {
        showAddRemark,
        showOCRTip,
        addRemarkContent,
        handleGreetingContentSetting,
        handleAddRemark,
        handleEditRemark,
        handleDeleteRemark,
    };
}

// ============================================================
// hooks/useFrequencySetting.ts  —  Step2 频率 / 备注设置
// ============================================================
import { DAY_NUM_LIST, TIME_INTERVAL_LIST } from "./types";
import type { AddFriendFormData } from "./types";

export function useFrequencySetting(formData: AddFriendFormData) {
    const dayNumList = DAY_NUM_LIST;
    const timeIntervalList = TIME_INTERVAL_LIST;
    const timeIntervalIndex = ref(0);
    const timeInterval = ref<number | undefined>();

    // 备注弹窗
    const showRemarkPopup = ref(false);
    const newRemark = ref("");
    const editRemarkIndex = ref(-1);

    const handleDayNum = (item: number) => {
        formData.add_number = item;
    };

    const handleTimeInterval = (item: number, index: number) => {
        timeIntervalIndex.value = index;
        formData.add_interval_time = item;
    };

    const handleEditRemark = (index: number) => {
        editRemarkIndex.value = index ?? -1;
        newRemark.value = index > -1 ? formData.remarks[index] : "";
        showRemarkPopup.value = true;
    };

    const handleRemarkConfirm = () => {
        if (!newRemark.value.trim()) {
            uni.$u.toast("请输入备注内容");
            return;
        }
        if (editRemarkIndex.value === -1) {
            formData.remarks.push(newRemark.value);
        } else {
            formData.remarks[editRemarkIndex.value] = newRemark.value;
        }
        editRemarkIndex.value = -1;
        newRemark.value = "";
        showRemarkPopup.value = false;
    };

    const closeRemarkPopup = () => {
        showRemarkPopup.value = false;
        newRemark.value = "";
        editRemarkIndex.value = -1;
    };

    const handleDeleteRemark = (index: number) => {
        formData.remarks.splice(index, 1);
    };

    return {
        dayNumList,
        timeIntervalList,
        timeIntervalIndex,
        timeInterval,
        showRemarkPopup,
        newRemark,
        editRemarkIndex,
        handleDayNum,
        handleTimeInterval,
        handleEditRemark,
        handleRemarkConfirm,
        closeRemarkPopup,
        handleDeleteRemark,
    };
}

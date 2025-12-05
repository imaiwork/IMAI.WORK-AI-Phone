import { getDeviceList } from "@/api/device";
import { getWeChatLists } from "@/api/person_wechat";
import { useAppStore } from "@/stores/app";
import RemarkPop from "@/pages/app/sph/_components/remark-pop.vue";

export function useCreateTask(formData: any) {
    const appStore = useAppStore();

    const getWechatRemarks = computed(() => {
        return appStore.config.wechat_remarks || [];
    });

    const currentFrequency = ref(0);

    const disabledDate = (date: Date) => {
        return date.getTime() < new Date().getTime() - 24 * 60 * 60 * 1000;
    };

    const { optionsData: deviceOptions } = useDictOptions<{
        deviceLists: any[];
        wechatLists: any[];
    }>({
        deviceLists: {
            api: getDeviceList,
            params: { page_size: 1000 },
            transformData: (data) => data.lists,
        },
        wechatLists: {
            api: getWeChatLists,
            params: { page_size: 1000 },
            transformData: (data) => data.lists,
        },
    });

    const handleFrequency = (item: number, index: number) => {
        currentFrequency.value = index;
        formData.task_frep = item;
    };

    const isAddRemarkGen = ref(false);
    const remarkPopupRef = shallowRef<InstanceType<typeof RemarkPop>>();
    const editRemarkIndex = ref(-1);

    const handleAddRemark = async () => {
        isAddRemarkGen.value = true;
        await nextTick();
        remarkPopupRef.value?.open();
    };

    const handleAddRemarkConfirm = (remark: string) => {
        if (editRemarkIndex.value == -1) {
            formData.remarks.push(remark);
        } else {
            formData.remarks[editRemarkIndex.value] = remark;
        }
        editRemarkIndex.value = -1;
        isAddRemarkGen.value = false;
    };

    const handleEditRemark = async (item: string, index: number) => {
        isAddRemarkGen.value = true;
        editRemarkIndex.value = index;
        await nextTick();
        remarkPopupRef.value?.open(item);
    };

    const handleDeleteRemark = (index: number) => {
        formData.remarks.splice(index, 1);
    };

    const checkTimeConfig = () => {
        if (!formData.time_config[0] || !formData.time_config[1]) {
            feedback.msgWarning("请选择执行时间");
            return false;
        }
        if (formData.time_config[0] && formData.time_config[1]) {
            const startTime = new Date(`2000/01/01 ${formData.time_config[0]}`);
            const endTime = new Date(`2000/01/01 ${formData.time_config[1]}`);
            const diffTime = endTime.getTime() - startTime.getTime();
            if (diffTime < 15 * 60 * 1000) {
                feedback.msgWarning("结束时间不能小于开始时间15分钟");
                return false;
            }
        }
        return true;
    };

    return {
        getWechatRemarks,
        deviceOptions,
        currentFrequency,
        disabledDate,
        handleFrequency,
        isAddRemarkGen,
        remarkPopupRef,
        editRemarkIndex,
        handleAddRemark,
        handleAddRemarkConfirm,
        handleEditRemark,
        handleDeleteRemark,
        checkTimeConfig,
    };
}

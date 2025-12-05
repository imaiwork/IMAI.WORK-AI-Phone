<template>
    <div class="h-full bg-app-bg-2 rounded-[20px] flex flex-col">
        <!-- 头部导航 -->
        <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#ffffff1a]">
            <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                <div class="text-white">返回</div>
            </div>
            <div class="flex items-center gap-1">
                <ElButton type="primary" class="!rounded-full !h-10 w-[98px]" :loading="isLock" @click="lockFn">
                    确认执行
                </ElButton>
            </div>
        </div>
        <!-- 内容区 -->
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="w-[456px] mx-auto mt-6">
                    <div class="text-[20px] font-bold text-white">创建新的自动加好友任务</div>
                    <div class="mt-6 flex flex-col gap-y-3">
                        <div>
                            <div class="text-[#ffffff80] mb-3">任务名称</div>
                            <ElInput
                                v-model="formData.name"
                                placeholder="请输入任务名称"
                                class="!h-11"
                                maxlength="30"
                                show-word-limit
                                clearable />
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">线索来源</div>
                            <div>
                                <ElRadioGroup v-model="formData.source">
                                    <ElRadio :value="1" border>
                                        <div class="text-white">表格导入</div>
                                    </ElRadio>
                                    <ElRadio :value="2" border>
                                        <div class="text-white">获客任务引用</div>
                                    </ElRadio>
                                </ElRadioGroup>
                            </div>
                        </div>

                        <template v-if="formData.source == 1">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[#ffffff80]">导入模版</div>
                                    <div class="text-primary cursor-pointer">
                                        <a href="/static/file/template/wechatidcsv.csv" target="_blank">下载模版</a>
                                    </div>
                                </div>
                                <upload
                                    type="file"
                                    list-type="text"
                                    show-file-list
                                    accept=".csv,.xlsx"
                                    drag
                                    :max-size="20"
                                    :limit="1"
                                    @success="handleUploadSuccess"
                                    @remove="formData.fileurl = ''">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <Icon name="local-icon-upload2" :size="48" color="#ffffff4d"></Icon>
                                        <div class="mt-3 text-[#ffffff4d]">点击添加或将文件拖拽到该处</div>
                                        <div class="text-xs text-[#ffffff33] mt-2 w-[80%]">
                                            支持扩展名：.csv、.xlsx格式
                                        </div>
                                    </div>
                                </upload>
                            </div>
                        </template>
                        <template v-if="formData.source == 2">
                            <div>
                                <div class="text-[#ffffff80] mb-3">获客任务引用</div>
                                <ElSelect
                                    v-model="formData.crawling_task_ids"
                                    placeholder="请选择获客任务"
                                    class="!h-11"
                                    multiple
                                    filterable
                                    clearable
                                    collapse-tags
                                    collapse-tags-tooltip
                                    remote
                                    popper-class="dark-select-popper"
                                    :show-arrow="false"
                                    :remote-method="getTaskList">
                                    <ElOption
                                        v-for="item in taskList"
                                        :label="item.name"
                                        :value="item.id"
                                        :key="item.id"></ElOption>
                                </ElSelect>
                            </div>
                        </template>
                        <div>
                            <div class="text-[#ffffff80] mb-3">执行设备</div>
                            <div>
                                <ElSelect
                                    v-model="formData.device_codes"
                                    placeholder="请选择执行设备"
                                    class="!h-11"
                                    multiple
                                    filterable
                                    clearable
                                    collapse-tags
                                    collapse-tags-tooltip
                                    popper-class="dark-select-popper"
                                    :show-arrow="false">
                                    <ElOption
                                        v-for="item in deviceOptions.deviceLists"
                                        :label="item.device_code"
                                        :value="item.device_code"
                                        :key="item.device_code"></ElOption>
                                </ElSelect>
                            </div>
                        </div>
                        <div>
                            <div class="text-[#ffffff80] mb-3">时间设置</div>
                            <div class="bg-app-bg-3 rounded-xl shadow-[0_0_0_1px_var(--app-border-color-2)] p-4">
                                <div class="text-white">自动加好友设置</div>
                                <div class="mt-4">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(item, index) in [1, 3, 5, 10, 30]"
                                            :key="index"
                                            class="cursor-pointer rounded-md px-4 py-2 border border-app-border-2 text-white hover:bg-app-bg-1"
                                            :class="[
                                                formData.task_frep == item && currentFrequency != 5 ? 'bg-primary' : '',
                                            ]"
                                            @click="handleFrequency(item, index)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="cursor-pointer rounded-md px-4 py-2 border border-app-border-2 text-white"
                                            :class="[currentFrequency == 5 ? 'bg-primary' : '']"
                                            @click="currentFrequency = 5">
                                            自定义
                                        </div>
                                    </div>
                                    <div class="mt-2" v-if="currentFrequency == 5">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            type="dates"
                                            placeholder="请选择自定义日期"
                                            format="MM-DD"
                                            value-format="YYYY-MM-DD"
                                            popper-class="dark-date-picker-popper"
                                            :disabled-date="disabledDate" />
                                    </div>
                                </div>
                                <div class="text-white mt-4">每日执行时间</div>
                                <div class="mt-4">
                                    <ElTimePicker
                                        v-model="formData.time_config"
                                        type="time"
                                        is-range
                                        start-placeholder="请选择开始时间"
                                        end-placeholder="请选择结束时间"
                                        format="HH:mm"
                                        value-format="HH:mm"
                                        popper-class="dark-select-popper"
                                        :show-arrow="false" />
                                </div>
                                <div v-if="taskErrorMsg" class="mt-2">
                                    <div>任务冲突</div>
                                    <view class="text-[#FF2442] mt-1 text-xs">
                                        {{ taskErrorMsg }}
                                    </view>
                                </div>
                            </div>
                        </div>
                        <div class="bg-app-bg-3 rounded-xl shadow-[0_0_0_1px_var(--app-border-color-2)] p-4 mt-4">
                            <div class="text-white">自动加好友设置</div>
                            <div class="mt-2">
                                <div class="text-[#ffffff80] mb-3">加微微信：</div>
                                <ElSelect
                                    v-model="formData.wechat_id"
                                    placeholder="请选择添加的微信"
                                    class="!h-11"
                                    multiple
                                    filterable
                                    clearable
                                    collapse-tags
                                    collapse-tags-tooltip
                                    popper-class="dark-select-popper"
                                    :show-arrow="false">
                                    <ElOption
                                        v-for="item in deviceOptions.wechatLists"
                                        :label="item.wechat_nickname"
                                        :value="item.wechat_id"
                                        :key="item.wechat_id"></ElOption>
                                </ElSelect>
                            </div>
                            <div class="mt-2">
                                <div class="text-[#ffffff80] mb-3">加微规则：</div>
                                <ElSelect
                                    v-model="formData.wechat_reg_type"
                                    class="!h-11"
                                    popper-class="dark-select-popper"
                                    :show-arrow="false">
                                    <ElOption label="全部" :value="0"></ElOption>
                                    <ElOption label="微信号" :value="1"></ElOption>
                                    <ElOption label="手机号" :value="2"></ElOption>
                                </ElSelect>
                            </div>
                            <div class="mt-2">
                                <div class="text-[#ffffff80] mb-3">自动加好友频率：</div>
                                <div class="flex items-center gap-x-4">
                                    <div class="flex-1">
                                        <div class="text-white mb-3">当天执行</div>
                                        <div class="flex items-center gap-x-2">
                                            <div class="flex-1">
                                                <ElInput
                                                    v-model="formData.add_number"
                                                    v-number-input="{
                                                        min: 1,
                                                        max: 99,
                                                    }"
                                                    type="number"
                                                    class="!h-11" />
                                            </div>
                                            <span class="text-[#ffffff80]">条</span>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-white mb-3">每次间隔</div>
                                        <div class="flex items-center gap-x-2">
                                            <div class="flex-1">
                                                <ElInput
                                                    v-model="formData.add_interval_time"
                                                    v-number-input="{
                                                        min: 1,
                                                        max: 999,
                                                    }"
                                                    type="number"
                                                    class="!h-11" />
                                            </div>
                                            <span class="text-[#ffffff80]">分钟</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-white">加好友验证内容</div>
                                    <ElSwitch
                                        v-model="formData.add_remark_enable"
                                        style="--el-switch-off-color: #333333"
                                        :active-value="1"
                                        :inactive-value="0" />
                                </div>
                                <div v-if="formData.add_remark_enable == 1">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(item, index) in formData.remarks"
                                            :key="index"
                                            class="cursor-pointer hover:bg-app-bg-1 transition-all duration-300 border border-app-border-2 rounded-md px-4 py-2 flex items-center"
                                            @click="handleEditRemark(item, index)">
                                            <div class="text-white text-xs">{{ item }}</div>
                                            <div class="w-[1px] h-[8px] bg-app-border-2 mx-2"></div>
                                            <div class="w-4 h-4" @click.stop="handleDeleteRemark(index)">
                                                <close-btn :icon-size="10" :theme="ThemeEnum.DARK"></close-btn>
                                            </div>
                                        </div>
                                    </div>
                                    <ElDivider class="!my-4 !border-app-border-2" />
                                    <div class="flex justify-end">
                                        <ElButton
                                            class="!h-8 !border-[#ffffff1a]"
                                            color="#121212"
                                            @click="handleAddRemark">
                                            <Icon name="el-icon-Plus" color="#ffffff" :size="12"></Icon>
                                            <div class="text-white text-xs ml-1">新增文案</div>
                                        </ElButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <remark-pop
        v-if="isAddRemarkGen"
        ref="remarkPopupRef"
        @close="isAddRemarkGen = false"
        @confirm="handleAddRemarkConfirm" />
</template>

<script setup lang="ts">
import { getTaskList as getTaskListApi, createManualAddWechat } from "@/api/sph";
import { ThemeEnum } from "@/enums/appEnums";
import RemarkPop from "@/pages/app/sph/_components/remark-pop.vue";
import dayjs from "dayjs";
import { useCreateTask } from "../../../_hooks/useCreateTask";

const emit = defineEmits(["back"]);

interface FormData {
    name: string;
    source: 1 | 2;
    fileurl: string;
    crawling_task_ids: string[];
    add_type: "0" | "1";
    add_number: number;
    add_interval_time: number;
    add_friends_prompt: string;
    add_remark_enable: 0 | 1;
    remarks: string[];
    wechat_id: string[];
    wechat_reg_type: 0 | 1 | 2;
    task_frep: number;
    custom_date: string[];
    time_config: string[];
    device_codes: string[];
}

const formData = reactive<FormData>({
    name: `自动加好友任务${dayjs().format("YYYYMMDDHHmmss")}`,
    source: 1,
    fileurl: "",
    crawling_task_ids: [],
    add_type: "1",
    add_number: 15,
    add_interval_time: 10,
    add_friends_prompt: "",
    add_remark_enable: 1,
    remarks: [],
    wechat_id: [],
    wechat_reg_type: 0,
    task_frep: 1,
    custom_date: [],
    time_config: ["", ""],
    device_codes: [],
});

const taskErrorMsg = ref("");

const {
    getWechatRemarks,
    deviceOptions,
    currentFrequency,
    disabledDate,
    handleFrequency,
    isAddRemarkGen,
    remarkPopupRef,
    handleAddRemark,
    handleAddRemarkConfirm,
    handleEditRemark,
    handleDeleteRemark,
    checkTimeConfig,
} = useCreateTask(formData);

watch(
    getWechatRemarks,
    (val) => {
        formData.remarks = [...(val || [])];
    },
    { immediate: true }
);

const taskList = ref<any[]>([]);

const handleUploadSuccess = (result: any) => {
    formData.fileurl = result.data.uri;
};

const { isLock, lockFn } = useLockFn(async () => {
    if (!formData.name) {
        feedback.msgWarning("请输入任务名称");
        return;
    } else if (formData.source == 1 && formData.fileurl == "") {
        feedback.msgWarning("请上传文件");
        return;
    } else if (formData.source == 2 && formData.crawling_task_ids.length == 0) {
        feedback.msgWarning("请选择获客任务");
        return;
    } else if (formData.device_codes.length == 0) {
        feedback.msgWarning("请选择执行设备");
        return;
    } else if (currentFrequency.value == 5 && formData.custom_date.length == 0) {
        feedback.msgWarning("请选择自定义日期");
        return;
    } else if (!checkTimeConfig()) {
        return;
    } else if (formData.wechat_id.length == 0) {
        feedback.msgWarning("请选择加微微信");
        return;
    } else if (formData.add_remark_enable == 1 && formData.remarks.length == 0) {
        feedback.msgWarning("请输入加好友备注内容");
        return;
    }
    try {
        await createManualAddWechat({
            ...formData,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
        });
        feedback.msgSuccess("创建成功");
        emit("back");
    } catch (error) {
        taskErrorMsg.value = error;
        feedback.msgError(error);
    }
});

const getTaskList = async (query?: string) => {
    const { lists } = await getTaskListApi({
        page: 1,
        page_size: 20,
        status: "3,4",
        name: query,
    });
    taskList.value = lists;
};
getTaskList();
</script>

<style scoped lang="scss">
:deep(.el-radio) {
    &.is-bordered {
        padding: 20px;
        @apply border-app-border-2;
        &.is-checked {
            @apply bg-app-bg-1;
        }
    }
    .el-radio__input {
        &:not(.is-checked) {
            .el-radio__inner {
                background-color: transparent !important;
                border-color: #2a2a2a !important;
            }
        }
    }
}

:deep(.el-select__input) {
    color: #ffffff !important;
}

:deep(.el-upload-list) {
    .el-upload-list__item {
        @apply h-11 flex items-center shadow-[0_0_0_1px_var(--app-border-color-2)];
    }
    .el-progress {
        @apply top-[34px] left-0;
    }
}
</style>

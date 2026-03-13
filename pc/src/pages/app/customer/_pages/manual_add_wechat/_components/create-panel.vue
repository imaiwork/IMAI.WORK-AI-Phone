<template>
    <div class="h-full bg-white rounded-2xl flex flex-col min-w-[1000px] border border-br overflow-hidden">
        <div
            class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-br-extra-light bg-white z-10">
            <div class="flex items-center gap-2 cursor-pointer group" @click="emit('back')">
                <div
                    class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                </div>
                <div class="text-sm font-medium text-tx-regular">返回列表</div>
            </div>
            <div class="flex items-center gap-3">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-11 px-8 !font-black shadow-light hover:scale-105 active:scale-95 transition-all"
                    :loading="isLock"
                    @click="lockFn">
                    确认执行
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0 bg-gray-50/50">
            <ElScrollbar>
                <div class="w-[640px] mx-auto py-10 pb-24">
                    <div class="mb-8">
                        <h1 class="text-3xl font-[900] text-gray-950">创建自动加好友任务</h1>
                        <p class="text-sm text-tx-secondary mt-2">配置您的加好友策略，AI 将自动为您添加目标客户</p>
                    </div>

                    <div class="flex flex-col gap-y-3">
                        <!-- 基本信息 -->
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                基本信息
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <ElFormItem label="任务名称" tip="给任务起一个好记的名字" />
                                    <ElInput
                                        v-model="formData.name"
                                        placeholder="例如：自动加好友任务"
                                        class="custom-input"
                                        maxlength="30"
                                        show-word-limit
                                        clearable />
                                </div>

                                <div>
                                    <ElFormItem label="线索来源" />
                                    <div class="grid grid-cols-2 gap-3">
                                        <div
                                            class="p-4 rounded-xl cursor-pointer border-2 transition-all"
                                            :class="
                                                formData.source === 1
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-br-light hover:border-primary/50'
                                            "
                                            @click="formData.source = 1">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                                                    <Icon name="el-icon-Upload" color="var(--color-primary)" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-sm">表格导入</div>
                                                    <div class="text-xs text-tx-secondary">上传CSV/Excel文件</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="p-4 rounded-xl cursor-pointer border-2 transition-all"
                                            :class="
                                                formData.source === 2
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-br-light hover:border-primary/50'
                                            "
                                            @click="formData.source = 2">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                                                    <Icon name="el-icon-Link" color="var(--green-500)" />
                                                </div>
                                                <div>
                                                    <div class="font-medium text-sm">获客任务引用</div>
                                                    <div class="text-xs text-tx-secondary">使用已有获客数据</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <ElFormItem label="执行设备" />
                                    <ElSelect
                                        v-model="formData.device_codes"
                                        multiple
                                        collapse-tags
                                        class="custom-select w-full"
                                        placeholder="请选择执行设备">
                                        <ElOption
                                            v-for="item in deviceOptions.deviceLists"
                                            :key="item.device_code"
                                            :label="`${item.device_name}（${item.device_code}）`"
                                            :value="item.device_code" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>

                        <!-- 数据来源配置 -->
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-blue-500 rounded-full"></span>
                                数据来源配置
                            </div>

                            <template v-if="formData.source == 1">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-medium text-tx-regular">导入模版</div>
                                        <a
                                            href="/static/file/template/wechatidcsv.csv"
                                            target="_blank"
                                            class="flex items-center cursor-pointer text-primary text-xs">
                                            <Icon name="el-icon-Download" />
                                            <span class="ml-1">下载模版</span>
                                        </a>
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
                                        <div
                                            class="flex flex-col items-center justify-center py-8 px-4 rounded-xl border-2 border-dashed border-br-light hover:border-primary transition-colors">
                                            <div
                                                class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3">
                                                <Icon
                                                    name="local-icon-upload2"
                                                    :size="24"
                                                    color="var(--color-primary)"></Icon>
                                            </div>
                                            <div class="text-sm font-medium text-tx-regular">
                                                点击添加或将文件拖拽到该处
                                            </div>
                                            <div class="text-xs text-tx-secondary mt-1">
                                                支持扩展名：.csv、.xlsx格式，最大20MB
                                            </div>
                                        </div>
                                    </upload>
                                </div>
                            </template>

                            <template v-if="formData.source == 2">
                                <div>
                                    <ElFormItem label="获客任务引用" />
                                    <ElSelect
                                        v-model="formData.crawling_task_ids"
                                        placeholder="请选择获客任务"
                                        class="custom-select w-full"
                                        multiple
                                        filterable
                                        clearable
                                        collapse-tags
                                        collapse-tags-tooltip
                                        remote
                                        :remote-method="getTaskList">
                                        <ElOption
                                            v-for="item in taskList"
                                            :label="item.name"
                                            :value="item.id"
                                            :key="item.id"></ElOption>
                                    </ElSelect>
                                </div>
                            </template>
                        </div>

                        <!-- 时间与频率 -->
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                时间与频率
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <div class="text-sm font-medium text-tx-regular mb-4">执行周期</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="item in [1, 3, 5, 10, 30]"
                                            :key="item"
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                formData.task_frep == item && currentFrequency != 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="handleFrequency(item, 0)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                currentFrequency == 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="currentFrequency = 5">
                                            自定义日期
                                        </div>
                                    </div>
                                    <div v-if="currentFrequency == 5" class="mt-4">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            class="!w-full custom-date-picker"
                                            placeholder="请选择日期"
                                            type="dates"
                                            format="MM-DD"
                                            value-format="YYYY-MM-DD"
                                            :disabled-date="disabledDate" />
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-dashed border-br-light">
                                    <div class="text-sm font-medium text-tx-regular mb-4">每日执行时段</div>
                                    <ElTimePicker
                                        v-model="formData.time_config"
                                        type="time"
                                        is-range
                                        range-separator="至"
                                        start-placeholder="开始"
                                        end-placeholder="结束"
                                        format="HH:mm"
                                        value-format="HH:mm"
                                        class="!w-full custom-time-picker"
                                        :show-arrow="false" />
                                </div>

                                <div v-if="taskErrorMsg" class="p-4 bg-red-50 border border-red-200 rounded-xl">
                                    <div class="text-sm font-medium text-red-600 mb-2">任务冲突</div>
                                    <div class="text-xs text-red-500">{{ taskErrorMsg }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 自动加好友设置 -->
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-orange-500 rounded-full"></span>
                                自动加好友设置
                            </div>

                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-6">
                                <div class="flex items-start gap-3">
                                    <Icon name="el-icon-Warning" color="var(--amber-500)" />
                                    <div class="text-xs text-amber-700">
                                        <div class="font-medium mb-1">重要提醒</div>
                                        <div>开启此任务请确保当前网络是常用安全网络，加好友账号为常态化老号</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="bg-white p-6 rounded-[24px] border border-slate-100 shadow-sm">
                                        <label
                                            class="text-[13px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1">
                                            使用微信
                                        </label>
                                        <ElSelect
                                            v-model="formData.wechat_id"
                                            multiple
                                            collapse-tags
                                            class="premium-select w-full"
                                            placeholder="选择执行账号">
                                            <ElOption
                                                v-for="item in deviceOptions.wechatLists"
                                                :key="item.account"
                                                :label="item.nickname"
                                                :value="item.account" />
                                        </ElSelect>
                                    </div>
                                    <div class="bg-white p-6 rounded-[24px] border border-slate-100 shadow-sm">
                                        <label
                                            class="text-[13px] font-black text-slate-400 uppercase tracking-widest mb-3 block ml-1">
                                            加微规则
                                        </label>
                                        <ElSelect v-model="formData.wechat_reg_type" class="premium-select w-full">
                                            <ElOption label="全部方式" :value="0" />
                                            <ElOption label="仅微信号" :value="1" />
                                            <ElOption label="仅手机号" :value="2" />
                                        </ElSelect>
                                    </div>
                                </div>

                                <div
                                    class="p-7 rounded-[28px] border border-slate-100 flex gap-8 relative overflow-hidden group">
                                    <div class="flex-1 space-y-4 relative z-10">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                                                <Icon name="el-icon-Refresh" :size="16" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-[1000] text-slate-700">每日执行次数</span>
                                                <span
                                                    class="text-[9px] text-slate-400 font-black uppercase tracking-tighter"
                                                    >Daily Total Count</span
                                                >
                                            </div>
                                        </div>
                                        <div class="premium-number-wrapper">
                                            <ElInputNumber
                                                v-model="formData.add_number"
                                                :min="1"
                                                :max="99"
                                                controls-position="right"
                                                class="premium-input-number !w-full" />
                                        </div>
                                    </div>

                                    <div
                                        class="w-[1px] h-12 bg-gradient-to-b from-[transparent] via-slate-200 to-[transparent] self-center"></div>

                                    <div class="flex-1 space-y-4 relative z-10">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-50 text-primary flex items-center justify-center">
                                                <Icon name="el-icon-Timer" :size="16" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-[1000] text-slate-700"
                                                    >间隔时间 (分钟)</span
                                                >
                                                <span
                                                    class="text-[9px] text-slate-400 font-black uppercase tracking-tighter"
                                                    >Wait Interval</span
                                                >
                                            </div>
                                        </div>
                                        <div class="premium-number-wrapper">
                                            <ElInputNumber
                                                v-model="formData.add_interval_time"
                                                :min="1"
                                                :max="999"
                                                controls-position="right"
                                                class="premium-input-number !w-full" />
                                        </div>
                                    </div>

                                    <div
                                        class="absolute -right-4 -bottom-4 w-24 h-24 bg-[#0065fb]/5 rounded-full blur-2xl group-hover:bg-[#0065fb]/10 transition-colors"></div>
                                </div>

                                <div class="bg-white p-7 rounded-[28px] border border-slate-100">
                                    <header class="flex items-center justify-between mb-5">
                                        <h4 class="text-[15px] font-[1000] text-slate-800">验证备注内容</h4>
                                        <div class="flex items-center bg-slate-100 p-1 rounded-xl">
                                            <button
                                                @click="formData.add_remark_enable = 0"
                                                :class="[
                                                    'px-3 py-1.5 text-[11px] font-black rounded-lg transition-all',
                                                    formData.add_remark_enable == 0
                                                        ? 'bg-white text-primary'
                                                        : 'text-slate-400',
                                                ]">
                                                单一内容
                                            </button>
                                            <button
                                                @click="formData.add_remark_enable = 1"
                                                :class="[
                                                    'px-3 py-1.5 text-[11px] font-black rounded-lg transition-all',
                                                    formData.add_remark_enable == 1
                                                        ? 'bg-white text-primary shadow-sm'
                                                        : 'text-slate-400',
                                                ]">
                                                随机库
                                            </button>
                                        </div>
                                    </header>

                                    <div
                                        v-if="formData.add_remark_enable == 1"
                                        class="p-5 bg-slate-50 rounded-[20px] border border-dashed border-slate-200">
                                        <div class="flex flex-wrap gap-2.5">
                                            <div
                                                v-for="(item, index) in formData.remarks"
                                                :key="index"
                                                class="bg-white border border-slate-100 pl-3 pr-2 py-2 rounded-xl flex items-center group hover:border-primary transition-all cursor-pointer"
                                                @click="handleEditRemark(item, index)">
                                                <span class="text-xs font-medium text-slate-600 mr-3">{{ item }}</span>
                                                <button
                                                    @click.stop="handleDeleteRemark(index)"
                                                    class="w-5 h-5 rounded-md bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center">
                                                    <Icon name="el-icon-Close" :size="10" />
                                                </button>
                                            </div>
                                            <button
                                                @click="handleAddRemark"
                                                class="h-[34px] px-4 rounded-xl border-2 border-dashed border-[#0065fb]/30 text-primary hover:bg-primary hover:text-white transition-all flex items-center gap-2 text-xs font-black">
                                                <Icon name="el-icon-Plus" /> 添加文案
                                            </button>
                                        </div>
                                    </div>

                                    <ElInput
                                        v-else
                                        v-model="formData.remark"
                                        type="textarea"
                                        :rows="4"
                                        placeholder="你好，我是..."
                                        class="custom-textarea" />
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
import { getTaskList as getTaskListApi, createManualAddWechat } from "~/api/customer";
import dayjs from "dayjs";
import RemarkPop from "@/pages/app/customer/_components/remark-pop.vue";
import { useCreateTask } from "@/pages/app/customer/_hooks/useCreateTask";

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
    remark: string;
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
    remark: "",
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
:deep(.el-form-item) {
    @apply mb-1;
}

:deep(.custom-input),
:deep(.custom-select),
:deep(.custom-date-picker) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 border-[none] shadow-[none] h-12 px-4 transition-all;
        &:hover {
            @apply bg-slate-100;
        }
        &.is-focus {
            @apply bg-white shadow-[0_0_0_2px_#0065fb];
        }
    }
}
:deep(.custom-time-picker) {
    @apply bg-slate-50;
}
:deep(.premium-input-number) {
    .el-input__wrapper {
        .el-input__inner {
            @apply text-slate-700 font-[1000] text-lg text-left;
        }
    }
}

:deep(.el-upload-dragger) {
    @apply border-none bg-[transparent] p-0;
}

:deep(.el-upload-list) {
    .el-upload-list__item {
        @apply h-11 flex items-center shadow-[0_0_0_1px_var(--app-border-color-2)] rounded-xl;
    }
    .el-progress {
        @apply top-[34px] left-0;
    }
}
</style>

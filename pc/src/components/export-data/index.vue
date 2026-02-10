<template>
    <div class="export-data-component">
        <popup
            ref="popupRef"
            title="导出数据设置"
            width="540px"
            confirm-button-text="启动导出"
            :async="true"
            @open="getData"
            @confirm="handleConfirm">
            <template #trigger>
                <slot name="trigger"></slot>
            </template>

            <div class="p-2">
                <div class="mb-6 grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 border border-br-extra-light rounded-xl p-4">
                        <div class="text-xs text-tx-secondary mb-1 flex items-center gap-1">
                            <Icon name="el-icon-PieChart" :size="14" /> 预计导出总量
                        </div>
                        <div class="text-xl font-[900] text-primary">
                            {{ exportData.count }} <span class="text-xs font-normal text-tx-regular">条数据</span>
                        </div>
                        <div class="text-[11px] text-tx-placeholder mt-1">
                            共 {{ exportData.sum_page }} 页 / 每页 {{ exportData.page_size }} 条
                        </div>
                    </div>

                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4">
                        <div class="text-xs text-blue-600 mb-1 flex items-center gap-1">
                            <Icon name="el-icon-Warning" :size="14" /> 导出限制说明
                        </div>
                        <div class="text-sm font-medium text-blue-700">单次最大 {{ exportData.max_page }} 页</div>
                        <div class="text-[11px] text-blue-400 mt-1">
                            上限 {{ exportData.all_max_size }} 条 / 确保导出稳定性
                        </div>
                    </div>
                </div>

                <ElForm
                    ref="formRef"
                    :model="formData"
                    label-position="top"
                    :rules="formRules"
                    class="custom-export-form">
                    <ElFormItem label="导出范围" prop="page_type">
                        <ElRadioGroup v-model="formData.page_type" class="custom-radio-group">
                            <ElRadio :label="0" border class="!mr-4 !rounded-xl">全部导出</ElRadio>
                            <ElRadio :label="1" border class="!mr-0 !rounded-xl">分页导出</ElRadio>
                        </ElRadioGroup>
                    </ElFormItem>

                    <Transition name="el-fade-in">
                        <ElFormItem label="指定分页范围" v-if="formData.page_type == 1">
                            <div class="flex items-center gap-3">
                                <ElFormItem prop="page_start" class="!mb-0">
                                    <ElInputNumber
                                        v-model="formData.page_start"
                                        :min="1"
                                        controls-position="right"
                                        class="!w-[160px]" />
                                </ElFormItem>
                                <span class="text-tx-secondary font-medium">至</span>
                                <ElFormItem prop="page_end" class="!mb-0">
                                    <ElInputNumber
                                        v-model="formData.page_end"
                                        :min="1"
                                        :max="exportData.sum_page"
                                        controls-position="right"
                                        class="!w-[160px]" />
                                </ElFormItem>
                                <span class="text-tx-secondary">页</span>
                            </div>
                        </ElFormItem>
                    </Transition>

                    <div class="my-4 py-4 border-y border-br-extra-light" v-if="$slots['form-item']">
                        <slot name="form-item" :formData="formData"></slot>
                    </div>

                    <ElFormItem label="导出文件名称" prop="file_name">
                        <ElInput v-model="formData.file_name" placeholder="请定义文件名..." class="custom-input">
                            <template #suffix>
                                <span class="text-tx-placeholder text-xs pr-2">.xlsx</span>
                            </template>
                        </ElInput>
                    </ElFormItem>
                </ElForm>

                <div class="mt-4 flex items-start gap-2 p-3 bg-orange-50 rounded-lg">
                    <Icon name="el-icon-InfoFilled" color="var(--orange-400)" :size="14" />
                    <p class="text-[11px] text-orange-600 leading-relaxed">
                        提示：大数据量导出可能需要 1-3 分钟，请勿在导出过程中刷新页面。导出完成后文件将自动下载。
                    </p>
                </div>
            </div>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import feedback from "@/utils/feedback";
import Popup from "@/components/popup/index.vue";
import type { FormInstance } from "element-plus";

const formRef = shallowRef<FormInstance>();
const props = defineProps({
    params: { type: Object, default: () => ({}) },
    pageSize: { type: Number, default: 25 },
    fetchFun: { type: Function, required: true },
    exportFun: { type: Function, default: null },
});

const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formData = reactive({
    page_type: 0,
    page_start: 1,
    page_end: 200,
    file_name: "",
    status: "",
});

const formRules = {
    file_name: [{ required: true, message: "文件名不能为空", trigger: "blur" }],
    page_start: [{ required: true, message: "请输入起止页", trigger: "blur" }],
    page_end: [{ required: true, message: "请输入结束页", trigger: "blur" }],
};

const exportData = reactive({
    count: 0,
    sum_page: 0,
    page_size: 0,
    max_page: 0,
    all_max_size: 0,
});

const getData = async () => {
    try {
        const res = await props.fetchFun({
            ...props.params,
            page_size: props.pageSize,
            export: 1,
        });
        Object.assign(exportData, res);
        formData.file_name = res.file_name || `数据导出_${new Date().getTime()}`;
        formData.page_end = res.page_end || res.sum_page;
        formData.page_start = res.page_start || 1;
    } catch (e) {
        console.error("获取导出预览数据失败", e);
    }
};

const handleConfirm = async () => {
    await formRef.value?.validate();
    feedback.loading("正在准备导出文件...");
    try {
        const fetchMethod = props.exportFun || props.fetchFun;
        const res = await fetchMethod({
            ...props.params,
            ...formData,
            page_size: props.pageSize,
            export: 2,
        });

        popupRef.value?.close();
        feedback.closeLoading();

        if (res?.url) {
            feedback.msgSuccess("导出成功，开始下载");
            window.open(res.url);
        } else {
            feedback.msgError("导出失败：未获取到文件地址");
        }
    } catch (error) {
        feedback.closeLoading();
    }
};
</script>

<style lang="scss" scoped>
:deep(.custom-export-form) {
    .el-form-item__label {
        @apply text-tx-regular font-black mb-2 flex items-center;
        &::before {
            @apply text-danger;
        }
    }
}

:deep(.custom-radio-group) {
    @apply w-full;
    .el-radio {
        @apply flex-1 h-12 transition-all border-br;
        &.is-checked {
            @apply border-primary bg-[#E6EFFF];
            .el-radio__label {
                @apply text-primary font-medium;
            }
        }
        .el-radio__inner {
            @apply hidden;
        }
        .el-radio__label {
            @apply pl-0 text-center w-full;
        }
    }
}

:deep(.el-input-number__increase),
:deep(.el-input-number__decrease) {
    @apply bg-[transparent] border-[none] text-tx-secondary hover:text-primary;
}
</style>

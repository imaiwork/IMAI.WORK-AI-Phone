<template>
    <div class="h-full flex overflow-hidden rounded-[20px] bg-white border border-br">
        <div class="w-[280px] h-full flex flex-col border-r border-br-extra-light bg-[#f9f9f9]/50">
            <div class="h-[74px] flex items-center px-6 bg-primary shrink-0">
                <Icon name="el-icon-Setting" color="white" :size="20" />
                <span class="ml-2 text-white text-[18px] font-[900] tracking-wider">策略设置</span>
            </div>

            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="p-[20px] flex flex-col gap-[24px]">
                        <ElForm :model="formData" label-position="top" class="custom-strategy-form">
                            <div class="setting-group-card">
                                <div class="flex items-center justify-between mb-[12px]">
                                    <span class="text-[14px] font-[900] text-tx-primary">开启自动化</span>
                                    <ElSwitch
                                        v-model="formData.is_enable"
                                        :active-value="1"
                                        :inactive-value="0"
                                        inline-prompt
                                        active-text="ON"
                                        inactive-text="OFF" />
                                </div>
                                <p class="text-xs text-tx-placeholder leading-relaxed">
                                    开启后，系统将在好友通过后自动执行打招呼策略。
                                </p>
                            </div>

                            <ElFormItem label="执行延迟">
                                <div class="flex items-center w-full gap-[8px]">
                                    <ElInputNumber
                                        v-model="formData.interval_time"
                                        :precision="0"
                                        :min="0"
                                        class="!w-full custom-number-input"
                                        controls-position="right" />
                                    <span class="shrink-0 text-[13px] font-medium text-tx-secondary">分钟后</span>
                                </div>
                            </ElFormItem>

                            <ElDivider class="!my-[4px] opacity-50" />

                            <ElFormItem label="对方打招呼是否回复">
                                <ElRadioGroup v-model="formData.friend_greet_is_reply" class="custom-radio-stack">
                                    <ElRadio :value="1" class="strategy-radio">
                                        <span class="font-medium">静默</span>
                                        <span class="text-[11px] block opacity-60 font-normal">不再打招呼</span>
                                    </ElRadio>
                                    <ElRadio :value="0" class="strategy-radio">
                                        <span class="font-medium">强制执行</span>
                                        <span class="text-[11px] block opacity-60 font-normal">按原计划发送内容</span>
                                    </ElRadio>
                                </ElRadioGroup>
                            </ElFormItem>

                            <ElFormItem label="后续接管模式">
                                <ElRadioGroup v-model="formData.greet_after_ai_enable" class="custom-radio-stack">
                                    <ElRadio :value="1" class="strategy-radio">
                                        <div class="flex items-center gap-1">
                                            <Icon name="el-icon-Cpu" :size="14" />
                                            <span class="font-medium">AI 智能接管</span>
                                        </div>
                                    </ElRadio>
                                    <ElRadio :value="0" class="strategy-radio">
                                        <div class="flex items-center gap-1">
                                            <Icon name="el-icon-User" :size="14" />
                                            <span class="font-medium">人工手动接管</span>
                                        </div>
                                    </ElRadio>
                                </ElRadioGroup>
                            </ElFormItem>
                        </ElForm>
                    </div>
                </ElScrollbar>
            </div>
        </div>
        <div class="h-full flex flex-col grow bg-white">
            <header
                class="h-[74px] flex-shrink-0 flex items-center justify-between px-8 border-b border-br-extra-light">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-[4px] h-[18px] bg-primary rounded-full"></div>
                        <h3 class="text-[16px] font-[900] text-tx-primary">编辑打招呼素材内容</h3>
                    </div>
                    <div class="flex items-center text-xs text-tx-placeholder bg-gray-50 px-3 py-1 rounded-full">
                        <Icon name="el-icon-Edit" />
                        <span class="ml-1">内容将按顺序逐条发送</span>
                    </div>
                </div>
                <ElButton
                    type="primary"
                    class="!h-[44px] px-[60px] !rounded-xl !font-[900] shadow-lg shadow-primary/25 hover:scale-105 transition-transform"
                    :loading="lockLoading"
                    @click="lockConfirm">
                    保存策略
                </ElButton>
            </header>

            <div class="grow min-h-0 p-8">
                <div class="max-w-[900px] mx-auto h-full">
                    <AddContent v-model="formData.greet_content" />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
import { sopGreetInfo, sopGreetEdit } from "@/api/person_wechat";
import AddContent from "../../../_components/add-content.vue";

// --- 数据状态定义 ---

// 使用 reactive 创建响应式表单数据对象
const formData = reactive({
    is_enable: 0, // 是否开启打招呼 (1: 开启, 0: 关闭)
    interval_time: 1, // 添加好友后打招呼的间隔时间（分钟）
    friend_greet_is_reply: 1, // 对方打招呼后是否回复 (1: 不再打招呼, 0: 继续打招呼)
    greet_after_ai_enable: 0, // 打招呼后由谁接管 (1: AI接管, 0: 人工接管)
    greet_content: [] as any[], // 打招呼素材内容列表
});

// --- 核心业务逻辑 ---

/**
 * @description 处理保存操作
 * 验证表单数据，并调用API进行保存
 */
const handleSave = async () => {
    // 校验打招呼内容是否为空
    if (formData.greet_content.length === 0) {
        feedback.msgError("请添加打招呼素材");
        return;
    }
    try {
        // 调用编辑接口
        await sopGreetEdit(formData);
        feedback.msgSuccess("保存成功");
    } catch (error) {
        // 捕获并处理API请求错误
        feedback.msgError(error);
    }
};

// 使用自定义 hook useLockFn 防止重复提交，并管理加载状态
// lockConfirm 是包装后的函数，lockLoading 是一个布尔值的 ref，表示是否正在加载
const { lockFn: lockConfirm, isLock: lockLoading } = useLockFn(handleSave);

// --- 数据获取与处理 ---

/**
 * @description 获取SOP打招呼设置详情
 * 组件加载时调用，用于获取初始数据
 */
const getSopGreetInfo = async () => {
    try {
        const data = await sopGreetInfo();
        // 使用获取到的数据设置表单
        setFormData(data);
    } catch (error) {
        // 错误处理
        console.error("获取SOP打招呼信息失败:", error);
    }
};

/**
 * @description 将从API获取的数据填充到表单中
 * @param {object} data - 从 sopGreetInfo API 获取的数据
 */
const setFormData = (data: any) => {
    // 遍历本地 formData 的键，用服务器返回的数据进行更新
    // 这样做可以确保不会从服务器数据中添加本地不存在的属性
    for (const key in formData) {
        if (data[key] != null) {
            // 检查服务器返回的数据中是否存在对应键且值不为 null/undefined
            // @ts-ignore - 此处为动态赋值，类型检查较为复杂，暂时忽略
            formData[key] = data[key];
        }
    }

    // 对 greet_content 字段进行特殊处理
    // API返回的素材内容中 type 可能是字符串，需要转换为数字
    // 如果服务器没有返回 greet_content，则默认为空数组
    formData.greet_content =
        data.greet_content?.map((item: any) => ({
            ...item,
            type: parseInt(item.type, 10), // 明确指定基数为10，避免潜在的解析问题
        })) || [];
};

// --- 生命周期钩子 ---

// onMounted 是 Vue 的生命周期钩子，在组件挂载到 DOM 后执行
onMounted(() => {
    // 获取并设置SOP打招呼的初始信息
    getSopGreetInfo();
});
</script>

<style scoped lang="scss">
.setting-group-card {
    @apply p-[16px] bg-white border border-br-extra-light rounded-xl  mb-[8px];
}

:deep(.custom-radio-stack) {
    @apply flex flex-col gap-[10px] w-full;
    .el-radio {
        @apply h-auto w-full p-[12px] m-0 border border-br rounded-xl bg-white transition-all;
        .el-radio__label {
            @apply flex flex-col text-tx-primary;
        }
        &.is-checked {
            @apply border-primary bg-[#eff6ff]/30;
            .el-radio__inner {
                @apply border-primary bg-primary;
            }
            .el-radio__label {
                @apply text-primary;
            }
        }
        &:hover:not(.is-checked) {
            @apply border-primary-light-7 bg-[#eff6ff]/10;
        }
    }
}

:deep(.el-button--primary) {
    background: linear-gradient(135deg, var(--color-primary) 0%, #2b82ff 100%);
    border: none;
}
</style>

<template>
    <div class="h-full flex flex-col">
        <!-- 主内容区 -->
        <div class="flex grow min-h-0 bg-white rounded-lg overflow-hidden">
            <!-- 左侧设置面板 -->
            <div class="w-[250px] h-full flex flex-col border-r border-[#E5E5E5]">
                <div class="h-[74px] flex items-center justify-center text-white text-2xl bg-primary">策略设置</div>
                <div class="grow min-h-0">
                    <ElScrollbar>
                        <div class="p-4">
                            <ElForm :model="formData" label-position="top">
                                <ElFormItem label="是否开启打招呼">
                                    <ElSwitch v-model="formData.is_enable" :active-value="1" :inactive-value="0" />
                                </ElFormItem>
                                <ElFormItem label="添加后打招呼间隔">
                                    <ElInputNumber
                                        v-model="formData.interval_time"
                                        :precision="0"
                                        :min="0"
                                        size="small" />
                                    <span class="ml-2">分钟后</span>
                                </ElFormItem>
                                <ElFormItem label="对方打招呼是否回复：">
                                    <ElRadioGroup v-model="formData.friend_greet_is_reply">
                                        <ElRadio :value="1">不再打招呼</ElRadio>
                                        <ElRadio :value="0">继续打招呼</ElRadio>
                                    </ElRadioGroup>
                                </ElFormItem>
                                <ElFormItem label="打招呼后接管类型：">
                                    <ElRadioGroup v-model="formData.greet_after_ai_enable">
                                        <ElRadio :value="1">AI接管</ElRadio>
                                        <ElRadio :value="0">人工接管</ElRadio>
                                    </ElRadioGroup>
                                </ElFormItem>
                            </ElForm>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <!-- 右侧内容编辑区 -->
            <div class="px-6 h-full flex flex-col grow">
                <div class="h-[75px] flex-shrink-0 flex items-center">编辑打招呼素材内容</div>
                <ElDivider class="!my-0" />
                <div class="grow min-h-0 py-4">
                    <!-- 素材添加/编辑组件 -->
                    <AddContent v-model="formData.greet_content" />
                </div>
            </div>
        </div>
        <!-- 底部操作区 -->
        <div class="mt-4 flex justify-center">
            <ElButton type="primary" class="w-[100px] !h-[40px]" :loading="lockLoading" @click="lockConfirm">
                保存
            </ElButton>
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
// 使用 :deep() 选择器穿透 scoped 样式，修改子组件 Element Plus 的样式
:deep(.el-form-item__label) {
    color: #9e9e9e;
}
:deep(.el-radio-group) {
    // 将单选框组垂直排列
    flex-direction: column;
    align-items: flex-start;
    .el-radio {
        margin-right: 0;
    }
}
</style>

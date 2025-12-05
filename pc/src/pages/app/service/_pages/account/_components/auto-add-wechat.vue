<template>
    <div class="px-4 flex flex-col h-full gap-4">
        <div class="grow min-h-0">
            <ElForm ref="formRef" :model="formData" label-position="top" :rules="rules">
                <div class="grid grid-cols-2 gap-x-10">
                    <ElFormItem label="加微策略">
                        <ElSelect v-model="formData.wechat_enable" placeholder="请选择加微策略">
                            <ElOption label="自动添加" :value="1"></ElOption>
                            <ElOption label="不执行加微" :value="0"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <!-- 加微匹配规则 -->
                    <ElFormItem label="加微匹配规则">
                        <ElSelect
                            v-model="formData.wechat_reg_type"
                            :empty-values="[null, undefined]"
                            placeholder="请选择加微匹配规则">
                            <ElOption label="全部" :value="0"></ElOption>
                            <ElOption label="微信号" :value="1"></ElOption>
                            <ElOption label="手机号" :value="2"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <!-- 添加好友的微信号 -->
                    <ElFormItem label="添加好友的微信号" prop="wechat_id">
                        <template #label>
                            <div class="flex items-center gap-2">
                                <ElTooltip
                                    content="一个微信号短频（2小时）大约搜索添加次数在15次左右，请尽量选择多微信进行好友添加">
                                    <div class="cursor-pointer leading-[0]">
                                        <Icon name="el-icon-WarningFilled" class="text-red-500"></Icon>
                                    </div>
                                </ElTooltip>
                                <span>添加好友的微信号</span>
                            </div>
                        </template>
                        <ElSelect
                            v-model="formData.wechat_id"
                            filterable
                            multiple
                            clearable
                            placeholder="请选择添加好友的微信号">
                            <ElOption
                                v-for="(item, index) in optionsData.wechatLists"
                                :key="index"
                                :label="`${item.wechat_nickname}(${item.wechat_id})`"
                                :value="item.wechat_id"></ElOption>
                        </ElSelect>
                    </ElFormItem>
                    <!-- 加微备注信息 -->
                    <ElFormItem label="加微备注信息">
                        <ElInput
                            v-model="formData.remark"
                            type="textarea"
                            placeholder="请输入加微备注信息"
                            :rows="5"></ElInput>
                    </ElFormItem>
                </div>
            </ElForm>
        </div>
        <div class="flex items-center justify-center my-4">
            <ElButton type="primary" class="w-[166px] !h-[40px]" :loading="isLockSubmit" @click="lockSubmit">
                保存
            </ElButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { updateAutoAddWechat, getAutoAddWechatConfig } from "@/api/service";
import { getWeChatLists } from "@/api/person_wechat";
import { FormInstance } from "element-plus";

const route = useRoute();

const account = computed(() => route.query.account);

const formRef = ref<FormInstance>();
const formData = reactive({
    account: account.value,
    account_type: 3,
    remark: "",
    wechat_enable: 0,
    wechat_reg_type: 0,
    wechat_id: [],
});

const rules = {
    wechat_id: [
        {
            required: true,
            message: "请选择添加好友的微信号",
        },
    ],
};

const { optionsData } = useDictOptions<{
    wechatLists: any[];
}>({
    wechatLists: {
        api: getWeChatLists,
        params: {
            page_size: 999,
        },
        transformData: (data) => data.lists,
    },
});
const { lockFn: lockSubmit, isLock: isLockSubmit } = useLockFn(async () => {
    await formRef.value?.validate();
    try {
        await updateAutoAddWechat(formData);
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
});

const getDetail = async () => {
    const data = await getAutoAddWechatConfig({ account: account.value });

    setFormData(data, formData);
};

watch(
    () => route.query.account,
    (val) => {
        if (val) {
            formData.account = val;
            getDetail();
        }
    },
    {
        immediate: true,
    }
);
</script>

<style scoped lang="scss">
:deep(.el-form-item__label) {
    display: flex;
    align-items: center;
}
</style>

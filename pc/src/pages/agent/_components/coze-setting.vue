<template>
    <popup
        ref="popupRef"
        width="370px"
        async
        :show-close="false"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0; border-radius: 32px">
        <div class="box rounded-[32px] overflow-hidden">
            <!-- 关闭按钮 -->
            <div class="absolute w-6 h-6 right-4 top-4 z-20 cursor-pointer" @click="close">
                <close-btn />
            </div>
            <!-- 顶部装饰背景 -->
            <div class="bg">
                <img src="@/assets/images/coze_bg.png" class="h-[200px] w-full object-cover" />
            </div>
            <!-- 表单内容 -->
            <div class="px-[38px] pb-5">
                <div class="text-[20px]">Coze令牌配置</div>
                <div class="text-xs text-[#0000004d] mt-4">
                    用于与 Coze 服务进行鉴权的 Bearer Token，请确保令牌具有所需的权限范围，粘贴后系统会进行格式校验。
                </div>
                <div class="mt-4">
                    <ElInput
                        v-model="formData.secret_token"
                        class="!h-[50px]"
                        placeholder="请输入Coze的Token"
                        clearable />
                </div>
                <div class="mt-6">
                    <ElButton
                        type="primary"
                        class="w-full !h-[50px] !rounded-full shadow-[0_6px_12px_0_#0065fb33]"
                        :loading="isLock"
                        @click="lockFn">
                        立即保存
                    </ElButton>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { cozeConfigAdd, cozeConfigUpdate } from "@/api/agent";

const emit = defineEmits(["close", "success"]);

const popupRef = shallowRef();

// 表单数据
const formData = reactive({
    id: "",
    secret_token: "",
});

// 打开弹窗
const open = async () => {
    popupRef.value.open();
};

// 关闭弹窗
const close = () => {
    emit("close");
};

// 使用 useLockFn 防止重复提交
const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.secret_token) {
        feedback.msgWarning("请输入Coze令牌");
        return;
    }
    try {
        // 根据是否存在ID判断是新增还是更新
        formData.id ? await cozeConfigUpdate(formData) : await cozeConfigAdd(formData);
        close();
        emit("success");
        feedback.msgSuccess("保存成功");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

// 暴露方法给父组件
defineExpose({
    open,
    setFormData: (data: any) => setFormData(data, formData),
});
</script>

<style scoped lang="scss">
:deep(.el-input__wrapper) {
    background-color: rgba(0, 0, 0, 0.03);
    box-shadow: none;
}

.bg {
    @apply relative;
    &::after {
        content: "";
        background: linear-gradient(180deg, #ffffff00 10%, #ffffffff 100%);
        @apply absolute bottom-0 z-10 w-full h-full;
    }
}
</style>

<template>
    <popup
        ref="popupRef"
        width="400px"
        async
        confirm-button-text=""
        cancel-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0; border-radius: 32px"
        :show-close="false">
        <div class="relative overflow-hidden bg-white rounded-[32px]">
            <div class="absolute right-4 top-4 z-30 w-8 h-8" @click="close">
                <close-btn />
            </div>

            <div class="relative h-[180px] overflow-hidden">
                <img src="@/assets/images/coze_bg.png" class="w-full h-full object-cover scale-110" />
            </div>

            <div class="px-10 pb-10 pt-6 text-center">
                <div class="text-[20px] font-[900] text-[#1E293B]">Coze 令牌配置</div>
                <div class="text-xs font-medium text-[#94A3B8] mt-2 leading-relaxed px-2">
                    请输入您的个人访问令牌 (PAT)，系统将加密存储该信息以确保与 Coze 服务的安全通讯。
                </div>

                <div class="mt-8 space-y-4">
                    <div class="text-left">
                        <span class="text-xs font-black text-[#64748B] ml-1">请输入令牌</span>
                        <div class="mt-2 group">
                            <ElInput
                                v-model="formData.secret_token"
                                class="custom-token-input"
                                placeholder="pat_xxxxxxxxxxxxxx"
                                clearable
                                type="password"
                                show-password />
                        </div>
                    </div>

                    <div class="pt-4">
                        <ElButton type="primary" class="save-config-btn" :loading="isLock" @click="lockFn">
                            确认保存配置
                        </ElButton>
                        <div
                            class="mt-4 text-[11px] font-medium text-[#CBD5E1] flex items-center justify-center gap-x-1">
                            <Icon name="el-icon-InfoFilled" />
                            <span>令牌将仅用于获取智能体列表及对话流</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { cozeConfigAdd, cozeConfigUpdate } from "@/api/agent";
const emit = defineEmits(["close", "success"]);
const popupRef = shallowRef();

const formData = reactive({
    id: "",
    secret_token: "",
});

const open = async () => {
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.secret_token) {
        feedback.msgWarning("请输入Coze令牌");
        return;
    }
    try {
        formData.id ? await cozeConfigUpdate(formData) : await cozeConfigAdd(formData);
        close();
        emit("success");
        feedback.msgSuccess("配置已保存");
    } catch (error) {
        feedback.msgError(error as string);
    }
});

defineExpose({
    open,
    setFormData: (data: any) => {
        if (data) {
            formData.id = data.id || "";
            formData.secret_token = data.secret_token || "";
        }
    },
});
</script>

<style scoped lang="scss">
/* 输入框深度定制 */
:deep(.custom-token-input) {
    .el-input__wrapper {
        @apply h-[54px] rounded-2xl bg-slate-50 shadow-[none] border border-br px-4 transition-all duration-300;
        &:hover {
            @apply border-[#CBD5E1];
        }
        &.is-focus {
            @apply border-primary bg-white;
            box-shadow: 0 0 0 4px rgba(var(--el-color-primary), 0.08) !important;
        }
    }
    .el-input__inner {
        @apply text-[14px] font-medium text-[#1E293B];
        &::placeholder {
            @apply text-[#CBD5E1] font-sans;
        }
    }
}

.save-config-btn {
    @apply w-full h-[54px] rounded-2xl text-[15px] font-black border-none transition-all duration-300;
    box-shadow: 0 10px 20px -6px rgba(var(--el-color-primary), 0.4);

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 25px -6px rgba(var(--el-color-primary), 0.5);
    }

    &:active {
        transform: translateY(0);
    }
}

@keyframes slow-zoom {
    from {
        transform: scale(1);
    }
    to {
        transform: scale(1.1);
    }
}
img {
    animation: slow-zoom 20s infinite alternate ease-in-out;
}
</style>

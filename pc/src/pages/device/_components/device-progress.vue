<template>
    <div class="fixed inset-0 z-[2000] flex items-center justify-center bg-[#0f172a]/60 backdrop-blur-sm">
        <transition name="el-zoom-in-center" mode="out-in">
            <div
                v-if="progressValue < 100"
                key="loading-or-error"
                class="w-[440px] bg-white rounded-[32px] p-8 relative overflow-hidden">
                <template v-if="!progressError">
                    <div class="flex flex-col items-center py-4">
                        <div class="relative w-24 h-24 mb-6">
                            <ElProgress
                                type="circle"
                                :percentage="progressValue"
                                :stroke-width="8"
                                :width="96"
                                :show-text="false" />
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-primary animate-pulse">
                                    <Icon name="el-icon-Connection" :size="28" />
                                </span>
                            </div>
                        </div>

                        <h3 class="text-xl font-medium text-[#1E293B] mb-2">正在同步云端数据</h3>
                        <p class="text-[15px] font-medium text-primary px-4 py-1.5 bg-[#0065fb]/5 rounded-full mb-6">
                            {{ step || "正在建立与 RPA 软件的连接..." }}
                        </p>

                        <div class="w-full space-y-4">
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-primary transition-all duration-500 rounded-full"
                                    :style="{ width: `${progressValue}%` }"></div>
                            </div>

                            <div
                                class="flex items-start gap-2 text-[11px] text-slate-400 leading-relaxed text-center justify-center italic">
                                <Icon name="el-icon-InfoFilled" :size="12" />
                                <span
                                    >为了确保同步成功，请暂时不要离开或关闭此页面<br />若超过 60s
                                    无响应，请尝试刷新重试</span
                                >
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="flex flex-col items-center py-2 text-center">
                        <div
                            class="w-16 h-16 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center mb-4 border border-red-100">
                            <Icon name="el-icon-WarningFilled" :size="32" />
                        </div>
                        <h3 class="text-lg font-black text-slate-800 mb-3">账号信息获取失败</h3>

                        <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100">
                            <p class="text-[13px] text-slate-600 leading-relaxed font-medium">
                                {{ progressErrorMsg || "无法通过 RPA 建立连接" }}，请检查手机软件运行状态是否正常。
                            </p>
                        </div>

                        <div class="flex gap-3 w-full">
                            <ElButton class="flex-1 !h-12 !rounded-2xl !font-medium" @click="handleClose"
                                >暂不同步</ElButton
                            >
                            <ElButton type="primary" class="flex-1 !h-12 !rounded-2xl !font-medium" @click="handleRetry"
                                >重新尝试</ElButton
                            >
                        </div>
                    </div>
                </template>
            </div>

            <div v-else key="success" class="w-[440px] bg-white rounded-[32px] p-8 shadow-2xl text-center">
                <div
                    class="w-20 h-20 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-6 border border-emerald-100">
                    <Icon name="el-icon-CircleCheckFilled" :size="48" />
                </div>
                <h3 class="text-xl font-medium text-slate-800 mb-2">同步任务完成</h3>
                <p class="text-[14px] text-slate-400 font-medium mb-8">AI 设备及对应账号信息已成功添加至列表</p>

                <div
                    class="bg-[#ecfdf5]/50 rounded-2xl p-4 mb-8 text-xs text-emerald-700 font-medium border border-[#d1fae5]/50">
                    若列表未及时显示，请在 1-2 分钟后刷新页面重试
                </div>

                <ElButton
                    type="primary"
                    class="w-full !h-12 !rounded-2xl !font-black !text-[15px]"
                    @click="handleClose">
                    确定
                </ElButton>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    progressValue: number;
    progressError: boolean;
    progressErrorMsg?: string;
    step: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "retry"): void;
}>();

const handleClose = () => {
    emit("close");
};

const handleRetry = () => {
    emit("retry");
};
</script>

<style scoped lang="scss">
:deep(.el-progress__text) {
    @apply text-primary;
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.el-zoom-in-center-enter-active,
.el-zoom-in-center-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

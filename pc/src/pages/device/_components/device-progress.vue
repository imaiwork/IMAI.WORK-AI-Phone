<template>
    <div class="fixed top-0 left-0 w-full h-full bg-black/5 z-[1000]">
        <div class="flex items-center justify-center h-full">
            <div
                class="w-[418px] h-[205px] bg-white rounded-lg flex flex-col items-center px-4"
                v-if="progressValue < 100">
                <template v-if="!progressError">
                    <div class="text-primary text-lg mt-8">请耐心等待</div>
                    <div class="text-primary text-[24px] font-bold mt-3">{{ step || "正在获取账号信息中..." }}</div>
                    <div class="w-full mt-4">
                        <ElProgress :percentage="progressValue" :stroke-width="10" striped striped-flow />
                    </div>
                    <div class="text-[#C4C4C4] mt-4 text-center text-xs">
                        请暂时不要关闭窗口，如果超过一分钟未响应，可以尝试刷新页面
                    </div>
                </template>
                <template v-else>
                    <div class="flex items-center justify-center mt-6">
                        <Icon name="el-icon-WarningFilled" color="var(--el-color-danger)" :size="28"></Icon>
                    </div>
                    <div class="text-lg mt-3 font-bold">账号信息获取失败</div>
                    <div class="text-center text-[#666666] mt-3">
                        无法获取您的账号信息，请检查手机RPA软件连接是否正常后重试。如果问题持续存在，请联系客服支持。
                    </div>
                    <div class="mt-4">
                        <ElButton @click="handleClose">关闭</ElButton>
                        <ElButton type="primary" @click="handleRetry">重试</ElButton>
                    </div>
                </template>
            </div>
            <div class="w-[441px] bg-white rounded-lg flex flex-col p-4" v-else>
                <div class="flex items-center gap-2">
                    <Icon name="el-icon-SuccessFilled" color="#28C76F" :size="20"></Icon>
                    <span class="text-lg font-bold">AI设备及对应账号添加成功</span>
                </div>
                <div class="text-[#87879B] mt-4">若未发现您的设备对应账号，可在稍后刷新重新拉取信息</div>
                <div class="flex justify-end mt-4">
                    <ElButton type="primary" round @click="handleClose">确定</ElButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    progressValue: number;
    progressError: boolean;
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

<style scoped></style>

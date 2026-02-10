<template>
    <div
        class="bg-white rounded-2xl h-auto cursor-pointer border border-[#e8eaf2] group hover:border-primary hover:shadow-light transition-all duration-300"
        @click="handleItem(item)">
        <div class="flex flex-col items-center">
            <div class="rounded-2xl w-full h-auto overflow-hidden">
                <div class="content-box">
                    <div v-if="item.status == TurnStatus.SUCCESS" class="absolute inset-0">
                        <template v-if="getResult">
                            <div class="success-box">
                                <img
                                    src="../_assets/images/tps.png"
                                    class="absolute top-[20px] left-[24px] w-[40px] h-[27px]" />
                            </div>
                            <div class="absolute top-0 left-0 pt-[16px] px-[24px] pb-[14px] z-2 w-full h-full">
                                <div
                                    class="text-[#585a73] text-xs indent-8 h-[60px] my-[10px] leading-[20px] w-full line-clamp-3">
                                    {{ getResult }}
                                </div>
                                <div
                                    class="h-[16px] absolute bottom-[13px] left-[24px] overflow-hidden mt-1"
                                    style="width: calc(100% - 48px)">
                                    <img src="../_assets/images/audio_spectrum.png" class="h-[16px] max-w-none" />
                                </div>
                            </div>
                            <div
                                class="absolute rounded bg-[rgba(0,0,0,0.27)] right-[20px] bottom-[12px] flex items-center justify-center h-[20px] py-[2px] px-1">
                                <span class="text-xs text-white">
                                    {{ getDuration }}
                                </span>
                            </div>
                        </template>
                        <div v-else class="success-empty-box">
                            <Icon name="local-icon-audio_mic" :size="56"></Icon>
                        </div>
                    </div>
                    <div
                        v-else-if="item.status == TurnStatus.ERROR"
                        class="absolute inset-0 flex items-center justify-center p-6">
                        <div
                            class="w-full h-full rounded-xl bg-[#fef2f2]/50 border border-red-100 flex flex-col items-center justify-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shadow-sm">
                                <Icon name="el-icon-WarningFilled" :size="24" color="#F56C6C" />
                            </div>
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-sm font-black text-red-600">转写服务异常</span>
                                <span class="text-[11px] text-red-400 font-medium">请尝试重新提交该任务</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="error-box"
                        v-else-if="item.status == TurnStatus.ING || item.status == TurnStatus.WAITING">
                        <div class="flex flex-col items-center gap-3">
                            <div class="ai-writing-dots"><span></span><span></span><span></span></div>
                            <span class="text-xs font-black text-primary animate-pulse uppercase tracking-widest"
                                >Processing</span
                            >
                        </div>
                    </div>
                </div>

                <div class="w-full h-[76px] py-3 px-6">
                    <div class="text-ellipsis whitespace-nowrap overflow-hidden font-black text-gray-950">
                        {{ formatName(item.name) }}
                    </div>
                    <div class="mt-2">
                        <template v-if="item.status == TurnStatus.SUCCESS">
                            <div class="flex flex-wrap gap-1.5 overflow-hidden max-h-[20px]" v-if="getTags?.length">
                                <div
                                    v-for="(tag, index) in getTags"
                                    :key="index"
                                    class="text-[10px] font-medium text-tx-secondary px-2 flex justify-center items-center bg-gray-100 h-[20px] rounded-md border border-br-extra-light">
                                    {{ tag }}
                                </div>
                            </div>
                            <div class="text-xs text-tx-placeholder" v-else>暂无主题标签</div>
                        </template>
                        <div
                            class="text-xs text-red-500 font-medium flex items-center gap-1"
                            v-else-if="item.status == TurnStatus.ERROR">
                            <Icon name="el-icon-CircleClose" :size="12" color="#F56C6C" />
                            任务失败
                        </div>
                        <div class="text-xs text-primary font-medium animate-pulse" v-else>正在智能处理中...</div>
                    </div>
                </div>

                <div class="px-6 pb-4 flex items-center justify-between">
                    <div class="text-tx-placeholder text-[11px] font-medium">
                        {{ dayjs(item.create_time).format("MM/DD HH:mm") }}
                    </div>

                    <div class="flex items-center gap-1">
                        <ElPopover
                            placement="bottom-end"
                            :show-arrow="false"
                            popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                            <template #reference>
                                <div
                                    class="w-7 h-7 rounded-lg flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer"
                                    @click.stop="visibleChange(true, item.id)">
                                    <Icon name="el-icon-MoreFilled" color="#94A3B8" :size="14"></Icon>
                                </div>
                            </template>

                            <div class="flex flex-col p-1 gap-1">
                                <div
                                    v-if="item.status == TurnStatus.ERROR"
                                    class="table-action-item"
                                    @click.stop="emit('again', item.id)">
                                    <Icon name="el-icon-Refresh" :size="14" />
                                    <span>重试任务</span>
                                </div>
                                <div
                                    class="table-action-item !text-red-500 hover:!bg-red-50"
                                    @click.stop="emit('delete', item.id)">
                                    <Icon name="el-icon-Delete" :size="14" />
                                    <span>删除记录</span>
                                </div>
                            </div>
                        </ElPopover>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";
import { dayjs } from "element-plus";
import { TurnStatus } from "../_enums";
import useHandleApi from "../_hooks/useHandleApi";

const props = defineProps<{
    item: any;
}>();

const emit = defineEmits(["delete", "again", "train"]);

const { formatName, handleItem, handleTrain } = useHandleApi();

const active = ref<number | undefined>();

const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        active.value = undefined;
    } else {
        active.value = id;
    }
};

const getTags = computed(() => {
    const { response } = props.item;
    return response.Result?.MeetingAssistance?.MeetingAssistance?.Keywords;
});

const getResult = computed(() => {
    const { response } = props.item;
    return response.Result?.Summarization?.Summarization?.ParagraphSummary;
});

const getDuration = computed(() => {
    const { response } = props.item;
    const { Duration } = response.Result?.Transcription?.Transcription?.AudioInfo;
    if (Duration) {
        return formatAudioTime(Duration / 1000);
    }
    return 0;
});

const onTrain = (item: any) => {
    handleTrain(item, async (result: any) => {
        emit("train", result);
    });
};
</script>

<style scoped lang="scss">
.content-box {
    @apply w-full h-full pt-[50%] relative;
}

.success-empty-box {
    background: radial-gradient(50% 50% at 50% 50%, rgb(243, 242, 255) 0%, rgb(247, 246, 252) 98%);
    @apply w-full h-full flex items-center justify-center rounded-xl;
}

// .error-box {
//     background: radial-gradient(50% 50% at 50% 50%, rgb(243, 242, 255) 0%, rgb(247, 246, 252) 98%);
//     @apply absolute w-full h-full top-0 left-0 flex items-center justify-center rounded-xl;
// }

/* 纯 CSS 处理中动画 */
.ai-writing-dots {
    @apply flex gap-1;
    span {
        @apply w-1.5 h-1.5 rounded-full bg-primary;
        animation: dot-jump 0.8s infinite alternate;
        &:nth-child(2) {
            animation-delay: 0.2s;
        }
        &:nth-child(3) {
            animation-delay: 0.4s;
        }
    }
}

@keyframes dot-jump {
    from {
        transform: translateY(0);
        opacity: 0.4;
    }
    to {
        transform: translateY(-6px);
        opacity: 1;
    }
}

/* 操作项样式 */
.action-item {
    @apply flex items-center gap-2.5 px-3 py-2 rounded-lg cursor-pointer text-xs font-medium text-tx-regular hover:bg-gray-100 transition-all active:scale-95;
}

.success-box {
    @apply w-full h-full relative bg-cover bg-no-repeat rounded-xl;
    background-image: url("../_assets/images/tps_bg.jpg");
    background-position: left top;
    background-size: cover;
}

.error-box {
    background: #f8fafc;
    @apply absolute inset-0 flex items-center justify-center rounded-xl border border-br-extra-light;
}
</style>

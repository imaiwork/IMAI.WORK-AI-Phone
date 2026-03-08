<template>
    <popup
        ref="popupRef"
        width="480px"
        title="上传资源"
        cancel-button-text=""
        confirm-button-text=""
        destroy-on-close
        :show-close="false"
        @close="close">
        <div class="p-6">
            <DefineTemplate v-slot="{ percentage, name }">
                <div class="mb-8 last:mb-0">
                    <div class="flex items-end justify-between mb-3">
                        <div class="flex flex-col gap-1">
                            <span class="text-[13px] font-black text-slate-400 uppercase tracking-wider">文件名称</span>
                            <span class="text-sm font-medium text-slate-700 truncate max-w-[280px]">{{
                                name || "正在准备文件..."
                            }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-[1000] tabular-nums tracking-tighter text-primary">
                                {{ percentage }}<span class="text-sm ml-0.5">%</span>
                            </span>
                        </div>
                    </div>

                    <div class="premium-progress-track">
                        <div
                            class="premium-progress-fill"
                            :style="{
                                width: percentage + '%',
                                background: progressGradient(percentage),
                            }">
                            <div class="inner-stripe" v-if="percentage < 100"></div>
                            <div class="progress-head-glow" v-if="percentage > 0 && percentage < 100"></div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <div :class="['status-dot', { 'is-complete': percentage === 100 }]">
                            <div class="pulse-ring" v-if="percentage > 0 && percentage < 100"></div>
                        </div>
                        <span
                            class="text-xs font-medium transition-colors"
                            :class="percentage === 100 ? 'text-emerald-500' : 'text-slate-400'">
                            {{ getStatusText(percentage) }}
                        </span>
                    </div>
                </div>
            </DefineTemplate>

            <div class="space-y-6">
                <UseTemplate
                    v-for="(item, index) in getPercentage"
                    :key="index"
                    :percentage="item.percentage"
                    :name="item.name" />
            </div>

            <Transition name="slide-up">
                <div v-if="isFinished && showConfirmButton" class="mt-8">
                    <button class="confirm-action-btn" @click="close">
                        <Icon name="el-icon-Check" />
                        <span class="ml-2"> 我知道了 </span>
                    </button>
                </div>
            </Transition>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { createReusableTemplate } from "@vueuse/core";

const props = withDefaults(
    defineProps<{
        percentage: { percentage: number; name: string } | { percentage: number; name: string }[];
        showConfirmButton?: boolean;
    }>(),
    {
        percentage: () => [{ percentage: 0, name: "" }],
        showConfirmButton: true,
    }
);

const emit = defineEmits<{ (e: "close"): void }>();
const popupRef = shallowRef();
const [DefineTemplate, UseTemplate] = createReusableTemplate();

const getPercentage = computed(() => (Array.isArray(props.percentage) ? props.percentage : [props.percentage]));

const isFinished = computed(() => {
    const list = getPercentage.value;
    return list.every((item) => item.percentage === 100);
});

const getStatusText = (percentage: number) => {
    if (percentage === 0) return "等待队列中...";
    if (percentage < 30) return "正在建立连接，准备上传...";
    if (percentage < 70) return "正在全力上传，请勿关闭页面...";
    if (percentage < 100) return "上传已完成，正在同步云端...";
    return "上传成功，文件处理完毕";
};

const progressGradient = (percentage: number) => {
    if (percentage === 100) return "linear-gradient(90deg, #10b981, #34d399)";
    return "linear-gradient(90deg, #0065fb 0%, #3b82f6 100%)";
};

const open = () => popupRef.value.open();
const close = () => emit("close");

defineExpose({ open, close });
</script>

<style scoped>
.premium-progress-track {
    height: 12px;
    background: #f1f5f9;
    border-radius: 100px;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
}

.premium-progress-fill {
    height: 100%;
    border-radius: 100px;
    position: relative;
    transition: width 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 12px rgba(0, 101, 251, 0.2);
}

.inner-stripe {
    position: absolute;
    inset: 0;
    background-image: linear-gradient(
        45deg,
        rgba(255, 255, 255, 0.2) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255, 255, 255, 0.2) 50%,
        rgba(255, 255, 255, 0.2) 75%,
        transparent 75%
    );
    background-size: 20px 20px;
    animation: move-stripe 1s linear infinite;
}

.progress-head-glow {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: 30px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8));
    filter: blur(4px);
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #0065fb;
    position: relative;
}
.status-dot.is-complete {
    background: #10b981;
}

.pulse-ring {
    position: absolute;
    top: -4px;
    left: -4px;
    width: 14px;
    height: 14px;
    border: 2px solid #0065fb;
    border-radius: 50%;
    animation: pulse-ring 1.5s cubic-bezier(0.24, 0, 0.38, 1) infinite;
}

.confirm-action-btn {
    width: 100%;
    height: 50px;
    background: #0065fb;
    color: white;
    border: none;
    border-radius: 16px;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(0, 101, 251, 0.2);
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.confirm-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(0, 101, 251, 0.3);
}

@keyframes move-stripe {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 20px 0;
    }
}

@keyframes pulse-ring {
    0% {
        transform: scale(0.5);
        opacity: 1;
    }
    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}

.slide-up-enter-active {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
</style>

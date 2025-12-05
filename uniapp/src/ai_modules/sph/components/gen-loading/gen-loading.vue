<template>
    <view class="loading" :class="{ 'loading--done': allStepsDone }">
        <view class="loading__steps">
            <view
                v-for="(s, i) in stepObjects"
                :key="i"
                class="loading__step"
                :class="{ 'loading__step--in': s.state !== 'waiting' }"
                :style="{
                    opacity: 1 - Math.abs(i - step) * 0.225,
                    transform: `translateY(${100 * distance(i)}%)`,
                }">
                <template v-if="s.state === 'done'">
                    <image src="@/ai_modules/sph/static/icons/success.svg" class="icon icon--success"></image>
                </template>
                <template v-if="s.state == 'progress'">
                    <image src="@/ai_modules/sph/static/icons/progress.svg" class="icon icon--progress"></image>
                </template>
                <template v-if="s.state == 'waiting'">
                    <image src="@/ai_modules/sph/static/icons/waiting.svg" class="icon icon--waiting"></image>
                </template>
                <view>
                    <view class="loading__step-title">{{ s.title }}</view>
                    <view class="loading__step-info">
                        <template v-if="s.state === 'progress'">
                            <view class="loading__ellipsis">
                                <text class="loading__ellipsis-dot">.</text>
                                <text class="loading__ellipsis-dot">.</text>
                                <text class="loading__ellipsis-dot">.</text>
                            </view>
                        </template>
                    </view>
                    <view class="loading__step-info" v-if="s.filesPrepared !== undefined">
                        {{ s.filesPrepared }} / {{ s.filesPreparedMax }} 准备完成
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/* ---------- 类型常量 ---------- */
const stateMap = {
    waiting: "empty-circle",
    progress: "half-circle",
    done: "check-circle",
};
const colorMap = { waiting: "neutral", progress: "warning", done: "success" };

/* ---------- 初始数据 ---------- */
const steps = [
    {
        id: 0,
        state: "waiting",
        title: "🔍 正在分析行业信息...",
        filesPreparedMax: 100,
    },
    {
        id: 1,
        state: "waiting",
        title: "🧩 识别目标客户群体...",
        filesPreparedMax: 100,
    },
    {
        id: 2,
        state: "waiting",
        title: "🔗 解析上下游关联场景...",
        filesPreparedMax: 100,
    },
    {
        id: 3,
        state: "waiting",
        title: "🎯 提取潜在账号主题...",
        filesPreparedMax: 100,
    },
    {
        id: 4,
        state: "waiting",
        title: "✨ 生成客户线索关键词...",
        filesPreparedMax: 100,
    },
    {
        id: 5,
        state: "waiting",
        title: "✨✅ 正在整理结果...",
        filesPreparedMax: 100,
    },
];

const stepCount = steps.length;
const step = ref(0);
const stepObjects = ref(JSON.parse(JSON.stringify(steps))); // 深拷贝
const allStepsDone = computed(() => step.value === stepCount);

function updatedItem(item: any) {
    const { id, state, start, filesPrepared = 0, filesPreparedMax = 0 } = item;
    const updated: any = { id, state };

    if (!start) {
        updated.start = new Date();
        updated.state = "progress";
        if (!filesPreparedMax) return updated;
    }

    if (filesPreparedMax) {
        // 如果是最后一步（id=4）且当前 98 -> 99 就不再涨了
        if (id === 5 && filesPrepared >= 99) {
            updated.filesPrepared = 99;
            return updated;
        }
        const next = Math.min(filesPrepared + 1, filesPreparedMax);
        updated.filesPrepared = next;
    }

    // 普通步骤正常结束
    if (!filesPreparedMax || updated.filesPrepared === filesPreparedMax) {
        updated.finish = new Date();
        updated.state = "done";
    }
    return updated;
}

/* ---------- 动画循环 ---------- */
let timer: any = null;
const stepDelayMap: any = {
    0: 35, // Preparing 每 20 ms 跳一次，100 份 ≈ 2 秒
    1: 40, // Downloading
    2: 40, // Analyzing
    3: 35, // Creating
    4: 40, // Finalizing
    5: 40, // Finalizing
};
function nextTick() {
    const cur = step.value;
    const target = stepObjects.value.find((v: any) => v.id === cur);
    if (!target) return;

    const updated = updatedItem(target);
    stepObjects.value[cur] = { ...target, ...updated };

    if (updated.state === "done") {
        step.value += 1;
    }

    const delay = stepDelayMap[step.value] ?? 1500;
    timer = setTimeout(nextTick, delay);
}

nextTick();

onUnmounted(() => {
    clearTimeout(timer);
});

/* ---------- 计算位移 ---------- */
function distance(i: number) {
    if (allStepsDone.value) {
        return i - Math.floor(stepCount / 2);
    } else {
        let moveBy = step.value;
        if (i > step.value + 1) {
            const stepHeight = 5.25;
            moveBy += (i - (step.value + 1)) * (1.5 / stepHeight);
        }
        return i - moveBy;
    }
}

function resolveLastStep() {
    const last = stepObjects.value[4];
    if (last && last.state === "progress") {
        last.filesPrepared = 100;
        last.finish = new Date();
        last.state = "done";
        step.value += 1; // 触发整体完成
    }
}

defineExpose({ resolveLastStep });
</script>

<style>
.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    width: 100%;
}
.loading__steps {
    position: relative;
    width: 60%;
}
.loading__step {
    position: absolute;
    width: 100%;
    height: 160rpx;
    display: flex;
    transition: all 0.3s;
}
.loading__step--in {
    opacity: 1 !important;
}
.loading__step-title {
    font-size: 28rpx;
    margin-left: 20rpx;
    color: #ffffff;
}
.loading__step-info {
    font-size: 22rpx;
    color: #666;
    margin-left: 20rpx;
}
.loading__ellipsis {
    display: inline-flex;
    gap: 4rpx;
}
.loading__ellipsis-dot {
    animation: dot 1.2s infinite;
}
@keyframes dot {
    0%,
    80%,
    100% {
        opacity: 0;
    }
    40% {
        opacity: 1;
    }
}
.icon {
    flex-shrink: 0;
    width: 48rpx;
    height: 48rpx;
}

.loading__ellipsis {
    display: inline-flex;
    gap: 4rpx;
}
.loading__ellipsis-dot {
    animation: dot 1.2s infinite;
}
.loading__ellipsis-dot:nth-child(2) {
    animation-delay: 0.2s;
}
.loading__ellipsis-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes dot {
    0%,
    80%,
    100% {
        opacity: 0;
    }
    40% {
        opacity: 1;
    }
}
</style>

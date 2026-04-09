<template>
    <view class="min-h-screen bg-[#F4F6F8] pb-[300rpx]">
        <u-navbar
            fixed
            :border-bottom="false"
            :background="{ background: 'transparent' }"
            title="IP人设分析"
            title-bold
            title-color="#ffffff"
            back-icon-color="#ffffff"
            :custom-back="back">
        </u-navbar>

        <view class="fixed w-full top-0 pt-24 pb-6 px-6 z-[888]" :style="{ background: getGradientBackground() }">
            <view class="relative z-10">
                <view class="flex justify-between items-start">
                    <view>
                        <view
                            class="inline-block bg-[#ffffff]/20 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full mb-2 tracking-widest uppercase">
                            Customized AI Strategy
                        </view>
                        <view
                            class="text-2xl font-black text-white leading-tight tracking-wide"
                            v-html="getHeaderTitle()">
                        </view>
                    </view>
                    <view class="bg-[#ffffff]/20 p-2.5 rounded-2xl border border-solid border-[#ffffff]/20 shadow-lg">
                        <image :src="getHeaderIcon()" class="w-6 h-6" />
                    </view>
                </view>
            </view>
        </view>

        <view v-if="mode === 'add' && loading" class="fixed inset-0 z-[9999]">
            <view
                class="w-full h-full flex items-center justify-center relative overflow-hidden"
                :style="{ background: getGradientBackground() }">
                <view class="absolute inset-0 pointer-events-none">
                    <view
                        v-for="n in 12"
                        :key="n"
                        class="absolute bg-[#ffffff]/30 rounded-full animate-float"
                        :style="getParticleStyle(n)">
                    </view>
                </view>

                <view class="flex flex-col items-center px-5 py-10 relative z-10">
                    <view class="relative mb-10">
                        <view
                            class="w-[120px] h-[120px] bg-[#ffffff]/20 rounded-full flex items-center justify-center border-2 border-solid border-[#ffffff]/30">
                            <image :src="getHeaderIcon()" class="w-[60px] h-[60px] brightness-0 invert" />
                        </view>
                        <view
                            class="absolute -inset-[10px] border-2 border-[#ffffff]/60 rounded-full border-t-[transparent] animate-spin"></view>
                        <view
                            class="absolute -inset-[20px] border-2 border-[#ffffff]/30 rounded-full border-t-[transparent] animate-spin-reverse"></view>
                    </view>

                    <view class="text-center mb-10">
                        <view class="text-[28px] font-bold text-white mb-3 tracking-[1px]">{{
                            getAnalysisTitle()
                        }}</view>
                        <view class="text-[16px] text-[#ffffff]/80 tracking-[0.5px]">AI正在深度分析您的数据...</view>
                    </view>

                    <view class="w-[280px] mb-10">
                        <view class="w-full h-[6px] bg-[#ffffff]/20 rounded-[3px] overflow-hidden mb-3">
                            <view
                                class="h-full bg-[#ffffff]/90 rounded-[3px] transition-[width] duration-300 ease-out relative"
                                :style="{ width: progressWidth + '%' }">
                                <view
                                    class="absolute inset-y-0 right-0 w-[20px] animate-shimmer"
                                    style="background: linear-gradient(to right, transparent, #ffffff80)"></view>
                            </view>
                        </view>
                        <view class="text-center text-[#ffffff]/90 text-[14px] font-bold"
                            >{{ Math.round(progressWidth) }}%</view
                        >
                    </view>

                    <view class="w-full max-w-[300px] mb-10">
                        <view
                            v-for="(step, index) in getAnalysisSteps()"
                            :key="index"
                            class="flex items-center mb-5 opacity-50 transition-opacity duration-300"
                            :class="{
                                'opacity-100': currentStep >= index,
                                'opacity-80': currentStep > index,
                            }">
                            <view
                                class="w-8 h-8 rounded-full bg-[#ffffff]/20 flex items-center justify-center mr-4 border-2 border-solid border-[#ffffff]/30 transition-all duration-300"
                                :class="{
                                    'bg-[#ffffff]/30 border-[#ffffff]/60': currentStep === index,
                                    'bg-[#ffffff]/90 border-[#ffffff]/90': currentStep > index,
                                }">
                                <view v-if="currentStep > index" class="text-[#10b981] font-bold text-[16px]">✓</view>
                                <view
                                    v-else-if="currentStep === index"
                                    class="w-4 h-4 border-2 border-solid border-[#ffffff]/30 border-t-white rounded-full animate-spin"></view>
                                <view v-else class="text-white text-[14px] font-bold">{{ index + 1 }}</view>
                            </view>

                            <view
                                class="text-[#ffffff]/90 text-[15px] flex-1 transition-all duration-300"
                                :class="{ 'text-white font-medium': currentStep === index }">
                                {{ step }}
                            </view>
                        </view>
                    </view>

                    <view
                        class="px-6 py-3 bg-[#ffffff]/10 border border-solid border-[#ffffff]/30 rounded-[25px] transition-all duration-300"
                        @click="back">
                        <text class="text-[#ffffff]/90 text-[14px] font-medium">跳过分析</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="px-[30rpx] pt-4 mt-[200rpx]">
            <template v-if="loading && mode === 'edit'">
                <skeleton />
            </template>
            <template v-else-if="!loading">
                <individual v-if="personaType === 1" :report-data="reportData" />
                <enterprise v-if="personaType === 2" :report-data="reportData" />
                <local v-if="personaType === 3" :report-data="reportData" />
            </template>
        </view>

        <view
            v-if="!loading && mode === 'add'"
            class="flex items-center justify-between gap-4 fixed bg-white bottom-0 left-0 right-0 px-4 pt-3 shadow-[0_-4rpx_20rpx_rgba(0,0,0,0.05)] z-50"
            style="padding-bottom: calc(12px + env(safe-area-inset-bottom))">
            <view class="flex items-center gap-x-1" @click="back">
                <text>返回</text>
            </view>
            <view class="flex-1">
                <u-button
                    type="primary"
                    shape="circle"
                    :ripple="true"
                    :custom-style="getButtonStyle()"
                    @click="handleConfirm">
                    确定无误，前往上传素材
                </u-button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getPersonDetail } from "@/api/person";
import usePolling from "@/hooks/usePolling";
import IndividualIcon from "@/ai_modules/person/static/icons/badge.svg";
import EnterpriseIcon from "@/ai_modules/person/static/icons/bag.svg";
import LocalIcon from "@/ai_modules/person/static/icons/store.svg";
import Enterprise from "./components/enterprise.vue";
import Local from "./components/local.vue";
import Individual from "./components/individual.vue";
import Skeleton from "./components/skeleton.vue";

// 响应式数据
const mode = ref<"add" | "edit">("add");
const personId = ref<string>("");
const personaType = ref<number>(0);
const loading = ref(true);
const reportData = ref<any>(null);

// 分析动画相关状态
const progressWidth = ref(0);
const currentStep = ref(0);
const analysisTimer = ref<any>(null);

// 人设类型配置
const PERSONA_CONFIG: Record<number, any> = {
    1: {
        name: "individual",
        title: "全自动个人IP<br />增长引爆方案",
        icon: IndividualIcon,
        gradient: "linear-gradient(to bottom right, #3b82f6 0%, #7c3aed 100%)",
        component: Individual,
        analysisTitle: "个人IP智能分析中",
        steps: ["扫描社交媒体数据", "分析内容影响力", "评估粉丝画像", "生成增长策略"],
    },
    2: {
        name: "enterprise",
        title: "全自动B端获客<br />商务引擎方案",
        icon: EnterpriseIcon,
        gradient: "linear-gradient(to bottom right, #10b981 0%, #2563eb 100%)",
        component: Enterprise,
        analysisTitle: "企业获客系统分析中",
        steps: ["分析目标客户群体", "评估获客渠道效果", "制定获客策略", "生成执行方案"],
    },
    3: {
        name: "local",
        title: "全自动门店引流<br />终极部署方案",
        icon: LocalIcon,
        gradient: "linear-gradient(to bottom right, #f97316 0%, #dc2626 100%)",
        component: Local,
        analysisTitle: "本地门店分析中",
        steps: ["分析本地市场环境", "识别目标客户群体", "制定引流策略", "生成执行计划"],
    },
};

// 样式方法
const getGradientBackground = (): string => {
    return PERSONA_CONFIG[personaType.value]?.gradient || PERSONA_CONFIG[1].gradient;
};

const getHeaderTitle = (): string => {
    return PERSONA_CONFIG[personaType.value]?.title || PERSONA_CONFIG[1].title;
};

const getHeaderIcon = (): string => {
    return PERSONA_CONFIG[personaType.value]?.icon || PERSONA_CONFIG[1].icon;
};

const getButtonStyle = () => {
    const gradient = getGradientBackground();
    return {
        height: "96rpx",
        fontSize: "30rpx",
        fontWeight: "900",
        border: "none",
        background: gradient,
    };
};

const getAnalysisTitle = (): string => {
    return PERSONA_CONFIG[personaType.value]?.analysisTitle || "AI智能分析中";
};

const getAnalysisSteps = (): string[] => {
    return PERSONA_CONFIG[personaType.value]?.steps || [];
};

const getParticleStyle = (index: number) => {
    const delay = index * 0.5;
    const duration = 3 + (index % 3);
    const size = 4 + (index % 3) * 2;
    return {
        animationDelay: `${delay}s`,
        animationDuration: `${duration}s`,
        width: `${size}px`,
        height: `${size}px`,
        left: `${10 + ((index * 7) % 80)}%`,
        top: `${20 + ((index * 11) % 60)}%`,
    };
};

// 分析动画控制
const startAnalysisAnimation = () => {
    progressWidth.value = 0;
    currentStep.value = 0;

    const steps = getAnalysisSteps();
    const totalDuration = 15000; // 15秒
    const stepDuration = totalDuration / steps.length;

    // 进度条动画：最多到 99，等接口回来才到 100
    const progressInterval = setInterval(() => {
        if (progressWidth.value < 99) {
            // ✅ 改这里：< 99
            progressWidth.value += 100 / (totalDuration / 100);
            if (progressWidth.value > 99) progressWidth.value = 99; // ✅ 兜底截断
        }
    }, 100);

    // 步骤动画
    const stepInterval = setInterval(() => {
        if (currentStep.value < steps.length - 1) {
            currentStep.value++;
        }
    }, stepDuration);

    analysisTimer.value = {
        progressInterval,
        stepInterval,
    };
};

const stopAnalysisAnimation = (success = false) => {
    if (analysisTimer.value) {
        clearInterval(analysisTimer.value.progressInterval);
        clearInterval(analysisTimer.value.stepInterval);
        analysisTimer.value = null;
    }
    if (success) {
        progressWidth.value = 100; // ✅ 接口成功才打到 100
    }
};

// 业务方法
const handleConfirm = () => {
    uni.reLaunch({
        url: `/ai_modules/person/pages/detail/detail?id=${personId.value}`,
    });
};

const back = () => {
    if (mode.value === "add" && !reportData.value) {
        uni.showModal({
            title: "提示",
            content: "分析结果未生成，确定跳过分析吗？",
            success: (res) => {
                if (res.confirm) {
                    stopAnalysisAnimation();
                    handleConfirm();
                }
            },
        });
    } else {
        uni.navigateBack();
    }
};

const getDetail = async () => {
    try {
        const res = await getPersonDetail({ id: personId.value });
        if (res.report_status == 2) {
            const { individual, enterprise, local } = res.report_content;
            reportData.value = individual || enterprise || local || {};
            stopAnalysisAnimation(true);
            loading.value = false;
            end();
        }
    } catch (error) {
        stopAnalysisAnimation(false);
        loading.value = false;
    }
};

// 轮询逻辑
const { start, end } = usePolling(getDetail, {
    time: 3000,
});

// 页面加载
onLoad(async (options: any) => {
    mode.value = options.mode as "add" | "edit";
    personId.value = options.id;
    personaType.value = Number(options.type) || 1;

    if (mode.value === "add") {
        startAnalysisAnimation();
        start();
    } else {
        await getDetail();
        start();
    }
});

// 页面卸载时清理轮询和动画
onUnmounted(() => {
    stopAnalysisAnimation();
    end();
});
</script>

<style scoped>
/* 只保留无法用Tailwind表达的自定义动画 */
@keyframes float {
    0%,
    100% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.3;
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
        opacity: 0.8;
    }
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

@keyframes spin-reverse {
    to {
        transform: rotate(-360deg);
    }
}

.animate-float {
    animation: float 4s ease-in-out infinite;
}

.animate-shimmer {
    animation: shimmer 1.5s infinite;
}

.animate-spin-reverse {
    animation: spin-reverse 3s linear infinite;
}
</style>

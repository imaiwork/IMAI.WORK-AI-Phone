<template>
    <HeaderContentDefineTemplate v-slot="{ title, index }">
        <div class="flex items-center gap-x-3 py-1.5 group">
            <div
                class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center border transition-all duration-300 bg-[#3b82f620] border-[#3b82f660]">
                <span class="text-[10px] font-medium text-[#60a5fa]">{{ index }}</span>
            </div>
            <span class="text-[14px] font-medium transition-colors text-[#cbd5e1]">{{ title }}</span>
        </div>
    </HeaderContentDefineTemplate>

    <div class="h-full rounded-[24px] relative bg-[#0f172a] min-w-[1000px]">
        <div
            class="absolute top-0 right-0 w-[500px] h-[400px] opacity-20 pointer-events-none"
            style="background: radial-gradient(circle, #3b82f6 0%, transparent 70%); filter: blur(60px)"></div>
        <div
            class="absolute bottom-0 left-0 w-[400px] h-[300px] opacity-10 pointer-events-none"
            style="background: radial-gradient(circle, #8b5cf6 0%, transparent 70%); filter: blur(50px)"></div>

        <ElScrollbar class="h-full z-10 relative">
            <div class="p-10 mx-auto flex flex-col justify-center">
                <div class="flex items-center justify-between gap-x-12 mb-10">
                    <div class="basis-1/2">
                        <div
                            class="inline-flex items-center px-3 py-1 rounded-md mb-6 border bg-[#3b82f615] border-[#3b82f630]">
                            <span
                                class="w-2 h-2 rounded-full mr-2 animate-pulse"
                                style="background-color: #10b981"></span>
                            <span class="text-xs font-medium uppercase tracking-wider text-[#60a5fa]"
                                >AI Matrix System</span
                            >
                        </div>
                        <h1 class="text-[44px] leading-[1.2] font-extrabold tracking-tight mb-6 text-[#ffffff]">
                            如何用
                            <span
                                class="text-[#3b82f6] bg-gradient-to-r from-[#3b82f6] to-[#8b5cf6] bg-clip-text text-transparent"
                                >自动化工具</span
                            ><br />
                            搭建私域引流矩阵
                        </h1>
                        <p class="text-[18px] font-light leading-relaxed max-w-md text-[#94a3b8]">
                            告别低效人工，利用 AI 与 RPA 技术实现 24 小时自动获客增长。
                        </p>
                    </div>

                    <div class="basis-[420px] rounded-[24px] p-[1px] bg-gradient-to-r from-[#334155] to-[#0f172a]">
                        <div class="rounded-[23px] p-8 bg-[#1e293b]">
                            <div class="flex items-center justify-between mb-8">
                                <h3 class="text-[20px] font-medium text-[#f8fafc]">私域引流作战台</h3>
                                <div
                                    class="px-2 py-0.5 rounded text-[10px] border text-[#10b981] border-[#10b98140] bg-[#10b98110]">
                                    LIVE
                                </div>
                            </div>

                            <div class="space-y-3 p-4 rounded-xl mb-8 border bg-[#0f172a80] border-[#334155]">
                                <HeaderContentUseTemplate title="智能（AI）手机硬件级授权" index="01" />
                                <HeaderContentUseTemplate title="私信 → 加微全闭环流程" index="02" />
                                <HeaderContentUseTemplate title="全平台社媒账号矩阵覆盖" index="03" />
                            </div>

                            <button
                                @click="handleClickCourseUrl"
                                class="w-full py-3.5 rounded-xl font-medium text-[14px] transition-all active:scale-95 bg-[#3b82f6] text-[#ffffff]">
                                查看操作手册 →
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-5 mb-10">
                    <div
                        v-for="(item, index) in steps"
                        :key="index"
                        class="p-6 rounded-2xl border transition-all duration-300 hover:-translate-y-1 bg-[#1e293b60] border-[#334155]">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mb-5"
                            :style="{ backgroundColor: item.color + '20', border: '1px solid ' + item.color + '40' }">
                            <img :src="item.icon" class="w-6 h-6" style="filter: brightness(1.2)" />
                        </div>
                        <div
                            class="text-[10px] font-medium mb-2 uppercase tracking-tighter"
                            :style="{ color: item.color }">
                            Step 0{{ index + 1 }}
                        </div>
                        <h4 class="text-[16px] font-medium mb-2 text-[#f1f5f9]">{{ item.title }}</h4>
                        <p class="text-xs leading-relaxed text-[#64748b]">{{ item.desc }}</p>
                    </div>
                </div>

                <div class="flex gap-6 h-[300px]">
                    <div
                        class="flex-[3] rounded-2xl border overflow-hidden relative group bg-[#1e293b] border-[#334155]">
                        <img
                            src="../../_assets/images/home_bg.png"
                            class="absolute inset-0 w-full h-full object-cover opacity-40 group-hover:opacity-60 transition-opacity duration-500" />
                        <div
                            class="absolute inset-0 bg-[#0f172a10] bg-gradient-to-t from-[#0f172a] to-[transparent]"></div>
                        <div class="absolute bottom-8 left-8">
                            <h3 class="text-[24px] font-black mb-3 text-[#ffffff]">矩阵运营最佳实践</h3>
                            <div class="flex gap-2">
                                <span
                                    class="px-2 py-1 rounded text-[11px] font-medium bg-[#3b82f630] text-[#60a5fa] border border-[#3b82f640]"
                                    >批量剪辑</span
                                >
                                <span
                                    class="px-2 py-1 rounded text-[11px] font-medium bg-[#8b5cf630] text-[#a78bfa] border border-[#8b5cf640]"
                                    >一键分发</span
                                >
                                <span
                                    class="px-2 py-1 rounded text-[11px] font-medium bg-[#10b98130] text-[#34d399] border border-[#10b98140]"
                                    >自动私信</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="flex-[2] flex flex-col gap-4">
                        <div
                            v-for="feat in sideFeatures"
                            :key="feat.name"
                            class="flex-1 flex items-center gap-4 px-6 rounded-2xl border transition-colors hover:bg-[#ffffff05] bg-[#1e293b40] border-[#334155]">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                                :style="{ backgroundColor: feat.color + '15', color: feat.color }">
                                <img :src="feat.icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-[15px] font-medium text-[#f1f5f9]">{{ feat.name }}</div>
                                <div class="text-[11px] text-[#64748b]">{{ feat.sub }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { createReusableTemplate } from "@vueuse/core";
import BindAccount from "@/pages/app/matrix/_assets/icons/bind_account.svg";
import AutoMsg from "@/pages/app/matrix/_assets/icons/auto_msg.svg";
import PhonePublish from "@/pages/app/matrix/_assets/icons/phone_publish.svg";
import MixAuto from "@/pages/app/matrix/_assets/icons/mix_auto.svg";
import MixList from "@/pages/app/matrix/_assets/icons/mix_list.svg";
import MsgWechat from "@/pages/app/matrix/_assets/icons/msg_wechat.svg";
import FlowData from "@/pages/app/matrix/_assets/icons/flow_data.svg";

const appStore = useAppStore();

const getCourseUrl = computed(() => {
    return appStore.getAppConfig?.course_url || "";
});

const steps = [
    { title: "绑定账号", desc: "AI手机硬件授权添加抖音账号", icon: BindAccount, color: "#3b82f6" },
    { title: "创作内容", desc: "智能混剪与一键素材分发", icon: PhonePublish, color: "#8b5cf6" },
    { title: "自动发布", desc: "自定义时间定时批量推流", icon: MixAuto, color: "#f59e0b" },
    { title: "私信引流", desc: "24h自动回复并识别微信", icon: AutoMsg, color: "#10b981" },
];

const sideFeatures = [
    { name: "抖音、快手、小红书、视频号AI 矩阵", sub: "打破流量边界，重构认知", icon: MixList, color: "#ef4444" },
    { name: "私信转微信", sub: "日均加微 89+，秒级响应", icon: MsgWechat, color: "#10b981" },
    { name: "全天候流量收割", sub: "错峰发布，覆盖多展示位", icon: FlowData, color: "#0ea5e9" },
];

const handleClickCourseUrl = () => {
    window.open(getCourseUrl.value, "_blank");
};

const [HeaderContentDefineTemplate, HeaderContentUseTemplate] = createReusableTemplate<{
    title: string;
    index: string;
}>();
</script>

<style scoped>
/* 仅保留滚动条基础样式美化 */
:deep(.el-scrollbar__bar.is-vertical) {
    width: 6px;
}
:deep(.el-scrollbar__thumb) {
    background-color: #334155 !important;
}
</style>

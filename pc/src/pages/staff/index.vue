<template>
    <div class="min-h-full w-full">
        <ElScrollbar>
            <div class="flex gap-x-5 pb-4 pr-4">
                <div class="bg-white rounded-[20px] px-6 py-[18px] min-w-[800px] basis-1/2">
                    <div class="text-lg font-bold">AI智能获客</div>
                    <div class="mt-[18px]">
                        <div class="flex gap-x-[15px]">
                            <div class="h-[230px] rounded-[10px] bg-[#F9F9FA] p-4">
                                <div class="text-lg font-bold">主动获客</div>
                                <div class="mt-[15px] grid grid-cols-4 gap-x-[17px]">
                                    <img
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                        :src="RedbookIcon" />
                                    <img
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                        :src="DouyinIcon" />
                                    <img
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                        :src="SphIcon" />
                                    <img
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                        :src="KuaishouIcon" />
                                </div>
                                <div class="text-xs mt-[18px] text-[#00000080]">
                                    聚焦解决“客户难找、渠道单一”等核心痛点，从各大平台精准引流客资至私域。
                                </div>
                                <div
                                    class="w-[70px] h-7 rounded-[50px] bg-white mt-[18px] flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-100"
                                    @click="handleStart(AppKeyEnum.SPH)">
                                    使用
                                </div>
                            </div>
                            <div
                                class="h-[230px] w-[185px] rounded-[10px] bg-[#F9F9FA] p-4 flex flex-col items-center justify-center">
                                <div class="text-lg font-bold">被动获客</div>
                                <div class="grid grid-cols-2 gap-x-3 gap-y-[10px] mt-[15px]">
                                    <img
                                        src="@/assets/images/app/redbook2.png"
                                        class="w-12 h-12 transition-all duration-300 ease-in-out hover:scale-110" />
                                    <img
                                        src="@/assets/images/app/douyin2.png"
                                        class="w-12 h-12 transition-all duration-300 ease-in-out hover:scale-110" />
                                    <img
                                        src="@/assets/images/app/sph2.png"
                                        class="w-12 h-12 transition-all duration-300 ease-in-out hover:scale-110" />
                                    <img
                                        src="@/assets/images/app/kuaishou2.png"
                                        class="w-12 h-12 transition-all duration-300 ease-in-out hover:scale-110" />
                                </div>
                                <div
                                    class="w-[70px] h-7 rounded-[50px] bg-white mt-5 flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-100"
                                    @click="handleStart(AppKeyEnum.MATRIX)">
                                    使用
                                </div>
                            </div>
                            <div class="h-[230px] w-[250px] rounded-[10px] bg-[#F9F9FA] p-4 relative">
                                <div class="text-lg font-bold">AI个微助手</div>
                                <div class="mt-[18px] text-xs text-[#00000080]">
                                    解决销售“人力高效率低、跟进不及时、客户流失严重等”的痛点，让销售告别繁琐重复劳动。
                                </div>
                                <div
                                    class="w-[70px] h-7 rounded-[50px] bg-white mt-[70px] flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-100"
                                    @click="handleStart(AppKeyEnum.PERSON_WECHAT)">
                                    使用
                                </div>
                                <div class="absolute right-4 bottom-4">
                                    <img
                                        src="@/assets/images/wechat2.png"
                                        class="w-[65px] h-[65px] transition-all duration-300 ease-in-out hover:scale-110" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[20px] px-6 py-[18px] min-w-[600px] basis-1/2">
                    <div class="text-lg font-bold">AI内务</div>
                    <div class="mt-[26px]">
                        <div class="grid gap-x-2" :class="`grid-cols-${aiStaffLists.length}`">
                            <div v-for="item in aiStaffLists" class="flex flex-col justify-center items-center">
                                <img
                                    :src="getCateImage(item.key)"
                                    class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110" />
                                <div class="mt-4 text-lg font-bold">{{ item.name }}</div>
                                <div class="text-xs text-[#00000080] mt-[23px] text-center">
                                    <div>{{ item.desc1 }}</div>
                                    <div>{{ item.desc2 }}</div>
                                    <div>{{ item.desc3 }}</div>
                                </div>
                                <div
                                    class="w-[70px] h-7 rounded-[50px] bg-[#F6F7FA] mt-5 flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-200"
                                    @click="handleStart(item.key)">
                                    使用
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
        <ElScrollbar>
            <div class="mt-2 flex gap-x-5 pb-5 pr-4">
                <div class="bg-white rounded-[20px] px-6 py-[18px] basis-1/3">
                    <div class="text-lg font-bold">AI内容营销</div>
                    <div class="flex gap-x-6">
                        <div class="mt-5">
                            <div class="grid grid-cols-2 gap-x-10 min-w-[250px]">
                                <div class="flex flex-col items-center justify-center">
                                    <img
                                        src="@/assets/images/app/post.png"
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110" />
                                    <div class="text-lg font-bold mt-4">发布图文/视频</div>
                                    <div
                                        class="w-[70px] h-7 rounded-[50px] bg-[#F6F7FA] mt-6 flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-200"
                                        @click="handleStart(AppKeyEnum.MATRIX_PUBLISH)">
                                        使用
                                    </div>
                                </div>
                                <div class="flex flex-col items-center justify-center">
                                    <img
                                        src="@/assets/images/app/video.png"
                                        class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110" />
                                    <div class="text-lg font-bold mt-4">视频创作</div>
                                    <div
                                        class="w-[70px] h-7 rounded-[50px] bg-[#F6F7FA] mt-6 flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-200"
                                        @click="handleStart('matrix_dh')">
                                        使用
                                    </div>
                                </div>
                            </div>
                            <div class="mt-[30px] text-xs text-[#00000080]">
                                超级AI创作引擎，重塑企业内容生产力<br />从创作到发布，多平台一键搞定
                            </div>
                        </div>
                        <div class="bg-[#F9F9FA] rounded-[10px] w-[250px] h-[255px] px-6 relative">
                            <div class="pt-5 text-lg font-bold">数字人定制</div>
                            <div class="text-xs text-[#00000080] mt-[15px]">
                                形象、声音1v1克隆 ，输入文本AI替您发言 ，自定台词精准匹配口型
                            </div>
                            <div
                                class="w-[70px] h-7 rounded-[50px] bg-white mt-[26px] flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-100"
                                @click="handleStart(AppKeyEnum.DIGITAL_HUMAN)">
                                使用
                            </div>
                            <div class="absolute right-4 bottom-0">
                                <img
                                    src="@/assets/images/app/dh_bg.png"
                                    class="w-[95px] h-[94px] transition-all duration-300 ease-in-out hover:scale-110" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[20px] px-6 py-[18px] basis-2/3">
                    <div class="text-lg font-bold">AI工具</div>
                    <div class="mt-[26px] grid min-w-[700px] gap-x-2" :class="`grid-cols-${aiTools.length}`">
                        <div v-for="item in aiTools" class="flex flex-col justify-center items-center">
                            <img
                                :src="getCateImage(item.key)"
                                class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110" />
                            <div class="mt-4 text-lg font-bold">{{ item.name }}</div>
                            <div class="text-xs text-[#00000080] mt-[23px] text-center">
                                <div>{{ item.desc1 }}</div>
                                <div>{{ item.desc2 }}</div>
                                <div>{{ item.desc3 }}</div>
                            </div>
                            <div
                                class="w-[70px] h-7 rounded-[50px] bg-[#F6F7FA] mt-5 flex items-center justify-center cursor-pointer transition-all duration-300 ease-in-out hover:bg-gray-200"
                                @click="handleStart(item.key)">
                                使用
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
        <ElScrollbar>
            <div class="mt-2 flex gap-x-5 pb-5 pr-5">
                <div class="bg-white rounded-[10px] px-6 py-[18px] flex-1 min-w-[700px]">
                    <div class="text-lg font-bold">持续开发中</div>
                    <div class="mt-[25px] grid" :class="`grid-cols-${developingTools.length}`">
                        <div
                            v-for="(item, index) in developingTools"
                            class="flex flex-col items-center justify-center cursor-pointer p-4 rounded-md transition-all duration-300 ease-in-out hover:bg-gray-100"
                            @click="handleStart(item.key)">
                            <img
                                class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                :src="getCateImage(item.key)" />
                            <div class="mt-4 font-bold text-lg">{{ item.name }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-[10px] px-6 py-[18px] w-[500px] flex-shrink-0">
                    <div class="text-lg font-bold">技术底座</div>
                    <div class="mt-[25px] grid" :class="`grid-cols-${technicalFoundationTools.length}`">
                        <div
                            v-for="(item, index) in technicalFoundationTools"
                            class="flex flex-col items-center justify-center">
                            <img
                                class="w-[55px] h-[55px] transition-all duration-300 ease-in-out hover:scale-110"
                                :src="item.icon" />
                            <div class="mt-4 font-bold text-lg">{{ item.name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
    </div>

    <!-- 弹窗组件 -->
    <app-intro v-if="showTips" ref="appTipsRef" :name="appName" @close="showTips = false" />
    <app-live v-if="showLive" ref="appLiveRef" @close="showLive = false" />
</template>

<script setup lang="ts">
import { AppKeyEnum, appKeyNameMap } from "@/enums/appEnums";

// 导入本地资源
import RedbookIcon from "@/assets/images/app/redbook.png";
import SphIcon from "@/assets/images/app/sph.png";
import DouyinIcon from "@/assets/images/app/douyin.png";
import KuaishouIcon from "@/assets/images/app/kuaishou.png";

import DeepSeekIcon from "@/assets/images/app/deepseek.png";
import TongyiIcon from "@/assets/images/app/tongyi.png";
import DoubaoIcon from "@/assets/images/app/doubao.png";
import GeminiIcon from "@/assets/images/app/gemini.png";

// 导入子组件
import AppIntro from "../app/_components/app-intro.vue";
import AppLive from "../app/_components/app-live.vue";

// --- 响应式状态定义 ---

const router = useRouter();
const appName = ref(""); // App名称，用于弹窗
const showTips = ref(false); // 控制AppIntro弹窗显示
const showLive = ref(false); // 控制AppLive弹窗显示
const appTipsRef = ref<InstanceType<typeof AppIntro> | null>(null);
const appLiveRef = ref<InstanceType<typeof AppLive> | null>(null);

// --- 静态数据定义 ---

// AI内务列表
const aiStaffLists = [
    {
        name: "AI员工陪练",
        key: AppKeyEnum.LADDER_PLAYER,
        desc1: "自动培训员工",
        desc2: "5类算法全面分析",
        desc3: "陪练式学习反馈",
    },
    {
        name: "AI人事",
        key: AppKeyEnum.INTERVIEW,
        desc1: "全自动化招聘流程",
        desc2: "AI自动筛选牛人",
        desc3: "自动面试并出具报告",
    },
    {
        name: "会议助手",
        key: AppKeyEnum.MEETING_MINUTES,
        desc1: "多人发言自动识别",
        desc2: "AI分析待办事项",
        desc3: "纪要代办一手总结",
    },
    {
        name: "思维导图",
        key: AppKeyEnum.MIND_MAP,
        desc1: "一句话生成思维导图",
        desc2: "系统化梳理逻辑",
        desc3: "可视化思维导图展示",
    },
];

// AI工具列表
const aiTools = [
    {
        name: "AI自动客服",
        key: AppKeyEnum.SERVICE,
        desc1: "支持多渠道整合",
        desc2: "提供智能客服解答",
        desc3: "兼容H5/小程序/个/企微",
    },
    {
        name: "AI无人直播",
        key: AppKeyEnum.LIVE,
        desc1: "24小时无人直播",
        desc2: "无需人工值守",
        desc3: "智能和用户互动",
    },
    {
        name: "AI智能生图",
        key: AppKeyEnum.DRAW_IMAGE,
        desc1: "AI智能生成设计",
        desc2: "风格自选秒变专业视觉",
        desc3: "输入需求定制专属创意",
    },
    {
        name: "AI商品图",
        key: AppKeyEnum.DRAW_GOODS,
        desc1: "AI一键生成商品精修图",
        desc2: "一秒打造高端视觉质感",
        desc3: "即刻产出多风格场景",
    },
    {
        name: "AI模特换装",
        key: AppKeyEnum.DRAW_FASHION,
        desc1: "AI模特一秒换装",
        desc2: "智能生成各类服装效果",
        desc3: "百变风格随心换",
    },
];

// 持续开发中工具列表
const developingTools = [
    { name: "AI法务", key: AppKeyEnum.LAW },
    { name: "AI税务", key: AppKeyEnum.TAX },
    { name: "AI合同审核", key: AppKeyEnum.CONTRACT },
    { name: "AI报表", key: AppKeyEnum.STATEMENT },
    { name: "AI-企微", key: AppKeyEnum.COMPANY_WECHAT },
    { name: "AI-office", key: AppKeyEnum.OFFICE },
];

// 技术底座工具列表
const technicalFoundationTools = [
    { name: "DeepSeek", icon: DeepSeekIcon },
    { name: "通义", icon: TongyiIcon },
    { name: "豆包", icon: DoubaoIcon },
    { name: "Gemini", icon: GeminiIcon },
];

// App路由映射
const appRoutes: Record<string, string> = {
    [AppKeyEnum.SPH]: `/app/${AppKeyEnum.SPH}`,
    [AppKeyEnum.REDBOOK]: `/app/matrix?type=1`,
    [AppKeyEnum.DIGITAL_HUMAN]: `/app/${AppKeyEnum.DIGITAL_HUMAN}`,
    [AppKeyEnum.DRAW_IMAGE]: `/app/${AppKeyEnum.DRAWING}?type=1`,
    [AppKeyEnum.DRAW_FASHION]: `/app/${AppKeyEnum.DRAWING}?type=3`,
    [AppKeyEnum.DRAW_POSTER]: `/app/${AppKeyEnum.DRAWING}?type=4`,
    [AppKeyEnum.DRAW_GOODS]: `/app/${AppKeyEnum.DRAWING}?type=2`,
    [AppKeyEnum.DRAW_VIDEO]: `/app/${AppKeyEnum.DRAWING}?type=5`,
    [AppKeyEnum.MEETING_MINUTES]: `/app/${AppKeyEnum.MEETING_MINUTES}`,
    [AppKeyEnum.MIND_MAP]: `/app/${AppKeyEnum.MIND_MAP}`,
    [AppKeyEnum.INTERVIEW]: `/app/${AppKeyEnum.INTERVIEW}`,
    [AppKeyEnum.SERVICE]: `/app/${AppKeyEnum.SERVICE}`,
    [AppKeyEnum.PERSON_WECHAT]: `/app/person_wechat/chat`,
    [AppKeyEnum.MATRIX]: `/app/matrix`,
    [AppKeyEnum.MATRIX_PUBLISH]: `/app/matrix?type=2`,
    matrix_dh: `/app/matrix?type=5`,
};

// 动态导入App图片资源
const appImageModules = import.meta.glob("@/assets/images/app/*.png", { eager: true });
const appImageData = Object.entries(appImageModules).reduce((acc, [path, module]) => {
    const name = path.split("/").pop()?.split(".")[0];
    if (name) {
        acc[name] = { name, src: (module as any).default };
    }
    return acc;
}, {} as Record<string, { name: string; src: string }>);

// --- 方法定义 ---

/**
 * 获取App分类图片
 * @param key App的key
 */
const getCateImage = (key: AppKeyEnum) => {
    return appImageData[key]?.src || "";
};

/**
 * 处理"立即使用"按钮点击事件
 * @param key App的key
 */
const handleStart = async (key: string) => {
    const route = appRoutes[key];
    if (route) {
        router.push(route);
        return;
    }

    // 处理需要特殊操作的App（如弹窗）
    switch (key) {
        case AppKeyEnum.LADDER_PLAYER:
            appName.value = appKeyNameMap[AppKeyEnum.LADDER_PLAYER];
            showTips.value = true;
            await nextTick();
            appTipsRef.value?.open("ladder_player");
            break;
        case AppKeyEnum.LIVE:
            showLive.value = true;
            await nextTick();
            appLiveRef.value?.open();
            break;
        default:
            feedback.msgWarning("功能正在开发中，敬请期待!");
            break;
    }
};
</script>

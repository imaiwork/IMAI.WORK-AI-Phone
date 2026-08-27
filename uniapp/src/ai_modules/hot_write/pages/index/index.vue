<template>
    <view
        class="flex flex-col min-h-screen"
        style="background: linear-gradient(180deg, #c2d9f8 0%, #e8f1fb 500rpx, #f4f7fa 600rpx)">
        <u-navbar title="" :border-bottom="false" :background="{ background: 'transparent' }"></u-navbar>

        <view class="mx-4 mt-2">
            <view class="relative px-[36rpx] pt-[40rpx] pb-[44rpx]">
                <view
                    class="absolute top-0 left-[20rpx] w-[200rpx] h-[200rpx] rounded-full"
                    style="
                        background: radial-gradient(circle, rgba(255, 255, 255, 0.55) 0%, transparent 70%);
                        pointer-events: none;
                    "></view>
                <view
                    class="absolute bottom-0 right-[40rpx] w-[160rpx] h-[160rpx] rounded-full"
                    style="
                        background: radial-gradient(circle, rgba(100, 160, 255, 0.2) 0%, transparent 70%);
                        pointer-events: none;
                    "></view>
                <view class="flex items-center gap-[28rpx] mb-[28rpx]">
                    <view
                        class="w-[108rpx] h-[108rpx] rounded-[28rpx] flex items-center justify-center flex-shrink-0"
                        style="
                            background: linear-gradient(145deg, #ffffff, #ddeeff);
                            box-shadow: 0 8px 24px rgba(0, 102, 255, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.9);
                        ">
                        <image
                            src="@/ai_modules/hot_write/static/icons/video_copy.svg"
                            mode="aspectFit"
                            class="w-full h-full" />
                    </view>
                    <view class="flex-1">
                        <text
                            class="text-[40rpx] font-extrabold text-[#0F1F3D] block mb-[8rpx]"
                            style="letter-spacing: 1px">
                            一键复刻爆款视频
                        </text>
                        <text class="text-xs text-[#5A7BAF]">轻松打造同款内容，智能匹配爆款模板</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 平台切换 + 粘贴作品链接（对齐设计稿） -->
        <view class="mx-4 mt-[8rpx]">
            <view
                class="inline-flex items-center rounded-full p-[6rpx] mb-[24rpx]"
                style="background: rgba(255, 255, 255, 0.7); box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04)">
                <view
                    v-for="opt in platformOptions"
                    :key="opt.key"
                    class="platform-chip flex items-center gap-[10rpx] px-[32rpx] py-[16rpx] rounded-full"
                    :style="currentPlatform === opt.key ? `background:${opt.activeBg}` : 'background:rgba(0,0,0,0)'"
                    hover-class="platform-chip--pressed"
                    :hover-stay-time="100"
                    @click="handlePlatformChange(opt.key)">
                    <image
                        :src="currentPlatform === opt.key ? opt.iconWhite : opt.iconGray"
                        mode="aspectFit"
                        class="w-[28rpx] h-[28rpx]" />
                    <text
                        class="text-[26rpx] leading-none"
                        :class="currentPlatform === opt.key ? 'text-white font-semibold' : 'text-[#4B5563]'">
                        {{ opt.label }}
                    </text>
                </view>
            </view>

            <view class="bg-white rounded-[24rpx] p-[32rpx]" style="box-shadow: 0 6px 22px rgba(99, 120, 200, 0.1)">
                <view class="flex items-center justify-between">
                    <text class="text-base font-bold text-[#111827]">粘贴作品链接</text>
                    <view
                        v-if="!isWashMode"
                        class="flex items-center gap-[6rpx] bg-[#F3F4F6] rounded-full px-[24rpx] py-[12rpx]"
                        @click="onSelectIP">
                        <text class="text-xs" :class="selectedPerson ? 'text-[#374151]' : 'text-[#9CA3AF]'">
                            IP: {{ selectedPerson?.persona_name || "请选择人设" }}
                        </text>
                        <u-icon name="arrow-down" :color="selectedPerson ? '#6B7280' : '#9CA3AF'" size="22"></u-icon>
                    </view>
                    <view v-else class="flex items-center gap-[6rpx] bg-[#FFFBEB] rounded-full px-[24rpx] py-[12rpx]">
                        <text class="text-xs text-[#D97706] font-semibold">洗稿模式 · 无需人设</text>
                    </view>
                </view>

                <!-- 文案模式：人设仿写 / 洗稿（洗稿仅视频链路开放，小红书图文不显示） -->
                <view
                    v-if="isDouyinPlatform"
                    class="inline-flex items-center bg-[#F3F4F6] rounded-full p-[6rpx] mt-[20rpx]">
                    <view
                        v-for="opt in rewriteModeOptions"
                        :key="opt.val"
                        class="px-[24rpx] py-[12rpx] rounded-full text-[22rpx]"
                        :class="rewriteMode === opt.val ? 'bg-white text-primary font-semibold' : 'text-[#9CA3AF]'"
                        :style="rewriteMode === opt.val ? 'box-shadow: 0 1px 2px rgba(0,0,0,0.05)' : ''"
                        @click="handleRewriteModeChange(opt.val)">
                        {{ opt.label }}
                    </view>
                </view>

                <view class="bg-[#F9FAFB] rounded-[20rpx] p-[24rpx] mt-[24rpx]">
                    <textarea
                        v-model="inputUrl"
                        :placeholder="linkPlaceholder"
                        placeholder-style="color:#9CA3AF; font-size:28rpx"
                        class="w-full text-sm text-[#1F2937]"
                        style="min-height: 160rpx; line-height: 1.6"
                        auto-height
                        :maxlength="-1"></textarea>

                    <view class="flex items-center justify-between mt-[8rpx]">
                        <view v-if="isDouyinPlatform" class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx]">
                            <view
                                v-for="opt in materialSourceOptions"
                                :key="opt.val"
                                class="px-[18rpx] py-[12rpx] rounded-full text-[22rpx]"
                                :class="[
                                    materialSource === opt.val
                                        ? 'bg-white text-primary font-semibold'
                                        : 'text-[#9CA3AF]',
                                    isWashMode && opt.val !== 1 ? 'opacity-40' : '',
                                ]"
                                :style="materialSource === opt.val ? 'box-shadow: 0 1px 2px rgba(0,0,0,0.05)' : ''"
                                @click="onPickMaterialSource(opt)">
                                {{ opt.label }}
                            </view>
                        </view>
                        <view v-else class="flex-1"></view>
                        <text class="text-sm text-primary font-medium px-[8rpx]" @click="onPaste"> 粘贴 </text>
                    </view>

                    <view
                        v-if="isDouyinPlatform && currentMaterialOption.needExtraPower"
                        class="inline-flex items-center gap-[10rpx] mt-[24rpx] px-[24rpx] py-[12rpx] rounded-full border border-[#FDE68A] bg-[#FFFBEB]">
                        <u-icon name="warning" color="#F59E0B" size="22"></u-icon>
                        <text class="text-xs text-[#D97706]">需额外消耗算力</text>
                    </view>
                </view>

                <view v-if="isWashMode" class="flex items-start gap-[10rpx] mt-[24rpx] px-[4rpx]">
                    <text class="mt-[4rpx]">
                        <u-icon name="info-circle" color="#2563EB" size="24"></u-icon>
                    </text>
                    <text class="text-xs text-[#2563EB] leading-relaxed flex-1">
                        洗稿模式：不使用人设，AI 同义改写原文案；视频类型、数字人形象与音色将在任务流程中由你自选
                    </text>
                </view>

                <view class="flex items-start gap-[10rpx] mt-[24rpx] px-[4rpx]">
                    <text class="mt-[4rpx]">
                        <u-icon name="info-circle" color="#F59E0B" size="24"></u-icon>
                    </text>
                    <text class="text-xs text-[#D97706] leading-relaxed flex-1">
                        提示：若注明了不可转载的作品可能会造成仿写失败
                    </text>
                </view>

                <view
                    class="w-full mt-[32rpx] py-[28rpx] rounded-full flex items-center justify-center gap-[20rpx]"
                    style="
                        background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
                        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
                    "
                    :class="isCreating ? 'opacity-60' : ''"
                    hover-class="cta-pressed"
                    :hover-stay-time="100"
                    @click="handleCreate">
                    <u-loading v-if="isCreating" mode="circle" size="28" color="#ffffff"></u-loading>
                    <text class="text-base font-semibold text-white">
                        {{ isCreating ? "创建中..." : "开始复刻" }}
                    </text>
                    <view
                        v-if="costBadgeText && !isCreating"
                        class="rounded-full px-[20rpx] py-[4rpx]"
                        style="background: rgba(255, 255, 255, 0.2)">
                        <text class="text-xs text-white">{{ costBadgeText }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="mx-4 mt-[32rpx]">
            <view class="flex items-center justify-between mb-[20rpx]">
                <view class="flex items-center gap-[12rpx]">
                    <text class="text-[30rpx] font-bold text-[#1F2937]">创作队列</text>
                    <view class="px-[16rpx] py-[4rpx] rounded-full bg-[#EFF6FF]">
                        <text class="text-[22rpx] text-primary font-medium">{{ taskList.length }}</text>
                    </view>
                </view>
                <view
                    class="flex items-center bg-white rounded-full p-[6rpx]"
                    style="box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06)">
                    <view
                        v-for="tab in tabs"
                        :key="tab.key"
                        class="px-[24rpx] py-[10rpx] rounded-full text-xs font-medium"
                        :class="currentTab === tab.key ? 'bg-primary text-white' : ''"
                        @click="handleTab(tab.key)">
                        {{ tab.label }}
                    </view>
                </view>
            </view>

            <view class="flex flex-col gap-[20rpx] pb-[40rpx]">
                <queue-task-card
                    v-for="(task, index) in filteredList"
                    :key="task.id || index"
                    :task="task"
                    :retrying="retryingTaskId === task.id"
                    @detail="toDetail(task)"
                    @preview="onPreview(task)"
                    @publish="onPublish(task)"
                    @more="onMore(task)"
                    @retry="onRetryTask(task)">
                    <template #steps>
                        <view class="mb-[16rpx]">
                            <view class="flex items-center">
                                <template v-for="(step, si) in task.steps || []" :key="'icon-' + si">
                                    <view
                                        class="w-[80rpx] h-[80rpx] rounded-full flex items-center justify-center flex-shrink-0"
                                        :style="stepBg(step.status)">
                                        <image
                                            :src="stepIcon(step.status)"
                                            mode="aspectFit"
                                            class="w-[36rpx] h-[36rpx]" />
                                    </view>
                                    <view
                                        v-if="si < (task.steps || []).length - 1"
                                        class="flex-1 h-[4rpx] rounded-full overflow-hidden"
                                        :style="stepLineWrapStyle(step.status, task.steps[si + 1].status)">
                                        <view
                                            v-if="
                                                step.status === 'done' &&
                                                ['running', 'wait'].includes(task.steps[si + 1].status)
                                            "
                                            class="step-line-shine" />
                                    </view>
                                </template>
                            </view>
                            <view class="flex items-start mt-[10rpx]">
                                <template v-for="(step, si) in task.steps || []" :key="'label-' + si">
                                    <view class="w-[90rpx] flex-shrink-0 flex flex-col items-center">
                                        <text class="text-[20rpx]" :style="stepLabelStyle(step.status)">
                                            {{ step.name }}
                                        </text>
                                    </view>
                                    <view v-if="si < (task.steps || []).length - 1" class="flex-1"></view>
                                </template>
                            </view>
                        </view>
                        <!-- 失败原因已在卡片状态区外露；此处仅保留进行中提示 -->
                        <view
                            v-if="
                                (isImageTextTask(task) &&
                                    task.status === 1 &&
                                    Number(task.image_rewrite_status) === ImageRewriteStatus.SELECTING) ||
                                (!isImageTextTask(task) && [2, 3].includes(task.status) && task.publish_confirm == 0) ||
                                (!isImageTextTask(task) && task.status === 1 && task.shanjian_task_id == '')
                            "
                            class="text-[18rpx] mt-[4rpx] leading-[1.4] mb-2">
                            <text
                                v-if="
                                    isImageTextTask(task) &&
                                    task.status === 1 &&
                                    Number(task.image_rewrite_status) === ImageRewriteStatus.SELECTING
                                "
                                class="text-[#D97706]">
                                提示：逐图分析完成，需要你确认哪几张图
                            </text>
                            <text
                                v-else-if="
                                    !isImageTextTask(task) && [2, 3].includes(task.status) && task.publish_confirm == 0
                                "
                                class="text-[#EF4444]">
                                提示：发布文案未确认，请点击任务查看并前往确认发布文案。
                            </text>
                            <text
                                v-else-if="
                                    !isImageTextTask(task) &&
                                    isWashTask(task) &&
                                    task.status === 1 &&
                                    Number(task.generation_config_confirmed) !== 1
                                "
                                class="text-[#D97706]">
                                提示：洗稿文案已生成，请点击任务选择视频类型、形象和音色。
                            </text>
                            <text
                                v-else-if="!isImageTextTask(task) && task.status === 1 && task.shanjian_task_id == ''"
                                class="text-[#EF4444]">
                                提示：仿写文案未确认，请点击任务查看并前往确认仿写文案。
                            </text>
                        </view>
                        <view
                            v-if="!isImageTextTask(task) && task.status == 2"
                            class="inline-flex items-center gap-[8rpx] px-[28rpx] py-[14rpx] rounded-[16rpx] bg-[#1F2937] mb-2"
                            @click.stop="onPrePublish(task)">
                            <u-icon name="play-right" color="#fff" size="26"></u-icon>
                            <text class="text-xs text-white font-medium">预发布</text>
                        </view>
                    </template>
                </queue-task-card>

                <view v-if="!loading && filteredList.length === 0" class="flex items-center justify-center py-[60rpx]">
                    <empty />
                </view>

                <view class="flex items-center justify-center py-[24rpx] gap-[12rpx]" v-else>
                    <block v-if="loading">
                        <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
                        <text class="text-xs text-[#9ca3af]">加载中...</text>
                    </block>
                    <block v-else-if="finished && taskList.length > 0">
                        <view class="h-[2rpx] w-[100rpx] bg-[#E5E7EB]"></view>
                        <text class="text-xs text-[#9CA3AF]">已加载全部</text>
                        <view class="h-[2rpx] w-[100rpx] bg-[#E5E7EB]"></view>
                    </block>
                </view>
            </view>
        </view>
    </view>

    <choose-person
        v-if="showChoosePerson"
        v-model="showChoosePerson"
        :limit="1"
        :skip-un-config="false"
        :is-config="false"
        :check-viral-assets="true"
        @select="handleSelectPerson" />
    <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { getHotWriteList, createHotWrite, createHotWriteImageText, deleteHotWrite } from "@/api/hot_write";
import { checkViralAssets, getPersonDetail } from "@/api/person";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { useUserStore } from "@/stores/user";
import usePolling from "@/hooks/usePolling";
import {
    HOT_WRITE_IMAGE_MODEL_ALIAS,
    HOT_WRITE_IMAGE_MODEL_NAME,
    HOT_WRITE_PLATFORM_OPTIONS,
    HotWritePlatform,
    HotWriteRewriteMode,
    ImageRewriteStatus,
    getTaskPreviewImages,
    isImageTextTask,
    isWashTask,
} from "@/ai_modules/hot_write/enums";
import QueueTaskCard from "./components/queue-task-card.vue";
import StepDone from "@/ai_modules/hot_write/static/icons/step_done.svg";
import StepRunning from "@/ai_modules/hot_write/static/icons/step_running.svg";
import StepPending from "@/ai_modules/hot_write/static/icons/step_pending.svg";
import StepFailed from "@/ai_modules/hot_write/static/icons/step_failed.svg";

const appStore = useAppStore();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const getTokenVal = computed(() => userStore.getTokenByScene(TokensSceneEnum.HOT_WRITE));
const rechargePopupRef = shallowRef();

const inputUrl = ref("");
const selectedPerson = ref<any>(null);
const currentTab = ref("all");
const showChoosePerson = ref(false);
const showVideoPreview = ref(false);
const videoUrl = ref("");
const currentPlatform = ref<HotWritePlatform>(HotWritePlatform.DOUYIN);

const isCreating = ref(false);

const platformOptions = HOT_WRITE_PLATFORM_OPTIONS;
const isDouyinPlatform = computed(() => currentPlatform.value === HotWritePlatform.DOUYIN);
const linkPlaceholder = computed(
    () => platformOptions.find((item) => item.key === currentPlatform.value)?.placeholder || "粘贴抖音作品链接",
);

/** 图文复刻：取 draw_model 中 image-2 */
const image2Model = computed(() => {
    const list = (appStore.getDrawModel || []) as any[];
    return (
        list.find((m) => {
            const name = String(m?.name || "").toLowerCase();
            const alias = String(m?.alias || "").toLowerCase();
            return name === HOT_WRITE_IMAGE_MODEL_NAME || alias === HOT_WRITE_IMAGE_MODEL_ALIAS;
        }) || null
    );
});

const image2UnitPrice = computed(() => {
    const price = Number(image2Model.value?.unit_price);
    return Number.isFinite(price) && price > 0 ? price : 0;
});

/** 当前平台 CTA 算力展示文案 */
const costBadgeText = computed(() => {
    if (!isDouyinPlatform.value) {
        if (!image2UnitPrice.value) return "";
        const priceText = image2UnitPrice.value.toFixed(2);
        // price_unit_label / unit 已是「算力/次」或「算力/张」
        const unitLabel =
            String(image2Model.value?.price_unit_label || image2Model.value?.unit || "算力/次").trim() || "算力/次";
        return `消耗${priceText}${unitLabel}`;
    }
    if (!getTokenVal.value?.score) return "";
    return `消耗${getTokenVal.value.score}/${getTokenVal.value.unit}`;
});

const createCostScore = computed(() =>
    isDouyinPlatform.value ? Number(getTokenVal.value?.score || 0) : image2UnitPrice.value,
);

const handlePlatformChange = (key: HotWritePlatform) => {
    if (currentPlatform.value === key) return;
    currentPlatform.value = key;
    // 小红书图文不开放洗稿，切过去时回到人设仿写，避免带着洗稿参数提交
    if (key !== HotWritePlatform.DOUYIN) rewriteMode.value = HotWriteRewriteMode.PERSONA;
};

const tabs = [
    { key: "all", label: "全部" },
    { key: "running", label: "执行中" },
    { key: "done", label: "已完成" },
    { key: "failed", label: "失败" },
];

const taskList = ref<any[]>([]);
const total = ref(0);
const loading = ref(false);
const finished = ref(false);

const queryParams = reactive({
    page_no: 1,
    page_size: 10,
    status: "",
});

// 步骤定义
const stepDefs = [{ name: "关联人设" }, { name: "提取文案" }, { name: "匹配形象" }, { name: "云端渲染" }];

// ✅ 素材选项：新增 needExtraPower（是否消耗额外算力）和 isDefault（是否默认选中）
const materialSourceOptions = [
    { val: 1, label: "AI找素材", needExtraPower: true, isDefault: false },
    { val: 2, label: "AI+人设素材", needExtraPower: true, isDefault: true },
    { val: 3, label: "纯人设素材", needExtraPower: false, isDefault: false },
];

// ✅ 默认选中 val=2（AI+人设素材，与图片中"默认选中"一致）
const materialSource = ref(2);

// ✅ 当前选中素材选项的完整信息
const currentMaterialOption = computed(
    () => materialSourceOptions.find((o) => o.val === materialSource.value) ?? materialSourceOptions[0],
);

// ────────── 文案模式：人设仿写 / 洗稿 ──────────

const rewriteMode = ref<HotWriteRewriteMode>(HotWriteRewriteMode.PERSONA);
const isWashMode = computed(() => rewriteMode.value === HotWriteRewriteMode.WASH);
const rewriteModeOptions = [
    { val: HotWriteRewriteMode.PERSONA, label: "人设仿写" },
    { val: HotWriteRewriteMode.WASH, label: "洗稿 · 自选形象音色" },
];

const handleRewriteModeChange = (val: HotWriteRewriteMode) => {
    if (rewriteMode.value === val) return;
    rewriteMode.value = val;
    // 洗稿无人设，素材来源强制 AI 找素材
    if (val === HotWriteRewriteMode.WASH) {
        materialSource.value = 1;
    }
};

const onPickMaterialSource = (opt: { val: number }) => {
    if (isWashMode.value && opt.val !== 1) {
        uni.$u.toast("洗稿模式仅支持AI找素材");
        return;
    }
    materialSource.value = opt.val;
};

// ────────── 步骤相关 ──────────

function resolveStepStatus(task: any, index: number): "done" | "running" | "pending" | "failed" {
    const taskStatus = task.status;
    const s = Number(taskStatus);
    if (s === 4) {
        if (task.avatar_id == 0) {
            if (index == 0) return "done";
            if (index == 1) return "failed";
        } else {
            if (index < 3) return "done";
            if (index == 3) return "failed";
        }
        return "pending";
    }
    if (s === 3) return "done";
    if (index <= s) return "done";
    if (index === s + 1) return "running";
    return "pending";
}

function resolveStepProgress(taskStatus: any) {
    const s = Number(taskStatus);
    if (s === 4) return 0;
    const doneCount = stepDefs.filter((_, index) => {
        return resolveStepStatus({ status: taskStatus }, index) === "done";
    }).length;
    return Math.round((doneCount / stepDefs.length) * 100);
}

/** 图文进度短标签（对齐设计稿：解析/提取/选图/生成/完成） */
const IMAGE_TEXT_STEP_SHORT: Record<string, string> = {
    persona: "解析",
    extract: "提取",
    select_images: "选图",
    image_rewrite: "生成",
    done: "完成",
};

/** 洗稿视频进度短标签（后端 wash 步骤流） */
const WASH_VIDEO_STEP_SHORT: Record<string, string> = {
    wash_mode: "模式",
    extract: "提取",
    rewrite: "洗稿",
    generation_type: "类型",
    avatar: "形象",
    voice: "音色",
    confirm: "确认",
    render: "渲染",
};

const mapProgressSteps = (item: any) => {
    const list = Array.isArray(item.progress_steps) ? item.progress_steps : [];
    if (!list.length) return null;
    const imageText = isImageTextTask(item);
    const wash = !imageText && isWashTask(item);
    const shortMap = imageText ? IMAGE_TEXT_STEP_SHORT : WASH_VIDEO_STEP_SHORT;
    const selecting = imageText && Number(item.image_rewrite_status) === ImageRewriteStatus.SELECTING;
    // 洗稿视频：文案就绪但配置未确认时，类型/形象/音色属于用户自选步骤
    const washWaiting = wash && Number(item.status) === 1 && Number(item.generation_config_confirmed) !== 1;
    // 卡片空间有限：洗稿隐藏恒完成的「模式」与随确认自动完成的「确认」两步
    const steps = wash ? list.filter((s: any) => !["wash_mode", "confirm"].includes(s.key)) : list;
    return steps.map((step: any, index: number) => {
        let status: "done" | "running" | "pending" | "failed" | "wait" = "pending";
        if (step.failed) status = "failed";
        else if (step.done) status = "done";
        else if (index === 0 || steps[index - 1]?.done) {
            // 待选图：当前「选图」步用等待态（琥珀），与设计稿一致
            if (selecting && step.key === "select_images") status = "wait";
            else if (washWaiting && ["generation_type", "avatar", "voice"].includes(step.key)) status = "wait";
            else status = "running";
        }
        return {
            status,
            key: step.key || "",
            name: shortMap[step.key] || step.name || "",
            remark: step.remarks || item.remarks,
        };
    });
};

const normalizeTask = (item: any) => {
    const progressSteps = isImageTextTask(item) || isWashTask(item) ? mapProgressSteps(item) : null;
    return {
        ...item,
        name: item.title || "提取文案中...",
        progress: resolveStepProgress(item.status),
        steps:
            progressSteps ||
            stepDefs.map((step, index) => {
                const status = resolveStepStatus(item, index);
                return {
                    status,
                    name: index === 2 ? (item.is_material === 1 ? "匹配素材" : "匹配形象") : step.name,
                    remark: item.remarks,
                };
            }),
    };
};

// ────────── 事件处理 ──────────

const handleSelectPerson = (person: any) => {
    selectedPerson.value = person;
    if (inputUrl.value.trim()) {
        handleCreate();
    }
};

const handleTab = (key: string) => {
    currentTab.value = key;
    if (key === "all") {
        queryParams.status = "";
    } else if (key === "running") {
        queryParams.status = "0,1,2";
    } else if (key === "done") {
        queryParams.status = "3";
    } else if (key === "failed") {
        queryParams.status = "4";
    }
    reset();
};

const getLists = async () => {
    loading.value = true;
    try {
        const res = await getHotWriteList(queryParams);
        const lists = res?.lists || [];
        const count = res?.count || 0;
        const newList = lists.map((item: any) => normalizeTask(item));
        taskList.value = [...taskList.value, ...newList];
        total.value = count;
        if (taskList.value.length >= count) {
            finished.value = true;
        }
        checkAndStartPolling();
    } catch (error) {
        finished.value = true;
    } finally {
        loading.value = false;
    }
};

const checkAndStartPolling = () => {
    const hasRunning = taskList.value.some((t) => t.status !== 3 && t.status !== 4);
    if (hasRunning) {
        start();
    } else {
        end();
    }
};

const silentRefresh = async () => {
    try {
        const loadedSize = queryParams.page_no * queryParams.page_size;
        const res = await getHotWriteList({ page_no: 1, page_size: loadedSize });
        const lists = res?.lists || [];
        const newMap = new Map(lists.map((item: any) => [item.id, item]));
        taskList.value = taskList.value.map((task) => {
            const updated = newMap.get(task.id);
            return updated ? normalizeTask(updated) : task;
        });
        if (lists.every((item: any) => item.status === 3 || item.status === 4)) {
            end();
        }
    } catch (_) {
        end();
    }
};

const reset = () => {
    queryParams.page_no = 1;
    taskList.value = [];
    total.value = 0;
    finished.value = false;
    getLists();
};

const filteredList = computed(() => {
    if (currentTab.value === "all") return taskList.value;
    if (currentTab.value === "running") return taskList.value.filter((t) => t.status !== 3 && t.status !== 4);
    return taskList.value.filter((t) => t.status === 3 || t.status === 4);
});

// ────────── 步骤样式工具函数 ──────────

function stepBg(status: string) {
    if (status === "done") return "background:#DCFCE7";
    if (status === "running") return "background:#EFF6FF; border: 2rpx solid #BFDBFE";
    if (status === "wait") return "background:#FEF3C7; border: 2rpx solid #FDE68A";
    if (status === "failed") return "background:#FEF2F2; border: 2rpx solid #FECACA";
    return "background:#F1F5F9; border: 2rpx solid #E2E8F0";
}

function stepIcon(status: string) {
    if (status === "done") return StepDone;
    if (status === "running" || status === "wait") return StepRunning;
    if (status === "failed") return StepFailed;
    return StepPending;
}

function stepLabelStyle(status: string) {
    if (status === "done") return "color:#059669";
    if (status === "running") return "color:#0066FF; font-weight:600";
    if (status === "wait") return "color:#D97706; font-weight:600";
    if (status === "failed") return "color:#EF4444; font-weight:600";
    return "color:#9CA3AF";
}

function stepLineWrapStyle(leftStatus: string, rightStatus: string) {
    if (leftStatus === "done" && rightStatus === "done") return "background: linear-gradient(90deg, #34D399, #059669)";
    if (leftStatus === "done" && rightStatus === "running")
        return "background: linear-gradient(90deg, #34D399, #60A5FA)";
    if (leftStatus === "done" && rightStatus === "wait") return "background: linear-gradient(90deg, #34D399, #FBBF24)";
    if (leftStatus === "done" && rightStatus === "failed")
        return "background: linear-gradient(90deg, #34D399, #FCA5A5)";
    if (leftStatus === "done" && rightStatus === "pending")
        return "background: linear-gradient(90deg, #6EE7B7, #E2E8F0)";
    if (leftStatus === "running" && rightStatus === "pending")
        return "background: repeating-linear-gradient(90deg, #93C5FD 0px, #93C5FD 6px, transparent 6px, transparent 12px)";
    if (leftStatus === "failed") return "background: #FEE2E2";
    return "background: repeating-linear-gradient(90deg, #CBD5E1 0px, #CBD5E1 4px, transparent 4px, transparent 8px)";
}

const onPaste = () => {
    uni.getClipboardData({
        success: (res) => {
            inputUrl.value = res.data;
        },
        fail: (err) => {
            uni.$u.toast(err);
        },
    });
};

const onSelectIP = () => {
    showChoosePerson.value = true;
};

const initSelectedPerson = async (personId?: string) => {
    if (!personId) return;
    try {
        const person = await getPersonDetail({ id: personId });
        const assets = await checkViralAssets({ id: personId });
        const hasAvatar = Number(assets?.has_avatar) === 1;
        const hasVoice = Number(assets?.has_voice) === 1;
        if (!hasAvatar && !hasVoice) {
            selectedPerson.value = null;
            uni.$u.toast("该人设没有形象及音色，不支持选择");
            return;
        }
        selectedPerson.value = person;
    } catch {
        selectedPerson.value = null;
    }
};

const handleCreate = async () => {
    if (isCreating.value) return;
    if (!inputUrl.value.trim()) {
        uni.$u.toast(isDouyinPlatform.value ? "请粘贴抖音作品链接" : "请粘贴小红书图文链接");
        return;
    }
    if (!isWashMode.value && !selectedPerson.value) {
        uni.$u.toast("请选择人设");
        showChoosePerson.value = true;
        return;
    }
    if (createCostScore.value > 0 && userTokens.value <= createCostScore.value) {
        rechargePopupRef.value?.open();
        return;
    }
    isCreating.value = true;
    uni.showLoading({ title: "开始复刻..." });
    try {
        const personaId = isWashMode.value ? 0 : selectedPerson.value?.id;
        if (currentPlatform.value === HotWritePlatform.XHS) {
            await createHotWriteImageText({
                url: inputUrl.value,
                persona_id: personaId,
                rewrite_mode: rewriteMode.value,
            });
        } else {
            await createHotWrite({
                url: inputUrl.value,
                persona_id: personaId,
                visual_material_source: isWashMode.value ? 1 : materialSource.value,
                rewrite_mode: rewriteMode.value,
            });
        }
        inputUrl.value = "";
        uni.hideLoading();
        uni.showToast({ title: "复刻任务创建成功", icon: "none", duration: 3000 });
        reset();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "复刻任务创建失败", icon: "none", duration: 3000 });
    } finally {
        isCreating.value = false;
    }
};

const onPrePublish = (task: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        params: {
            task_id: JSON.stringify([task.id]),
            source: "hot_write",
        },
    });
};

const onPreview = (task: any) => {
    if (isImageTextTask(task)) {
        const urls = getTaskPreviewImages(task);
        if (!urls.length) {
            uni.$u.toast("暂无可预览图片");
            return;
        }
        uni.previewImage({ urls, current: urls[0] });
        return;
    }
    videoUrl.value = task.video_url;
    showVideoPreview.value = true;
};

const onPublish = (task: any) => {
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task",
        params: {
            // TaskType：1 视频 2 图文，与 HotWriteMediaType 一致
            type: isImageTextTask(task) ? 2 : 1,
            source: "hot_write",
            data: JSON.stringify({ id: task.id }),
        },
    });
};

const retryingTaskId = ref<number | string | null>(null);

/** 失败重跑：图文 image2text / 视频 video2text，均传原任务 id */
const onRetryTask = async (task: any) => {
    if (Number(task.status) !== 4) return;
    if (retryingTaskId.value != null) return;
    const url = String(task.prompt || task.url || "").trim();
    const washTask = isWashTask(task);
    const personaId = washTask ? 0 : task.persona_id;
    if (!url) {
        uni.$u.toast("缺少原链接，无法重试");
        return;
    }
    if (!washTask && !personaId) {
        uni.$u.toast("缺少人设信息，无法重试");
        return;
    }
    const taskRewriteMode = Number(task.rewrite_mode) || HotWriteRewriteMode.PERSONA;
    retryingTaskId.value = task.id;
    uni.showLoading({ title: "重试中...", mask: true });
    try {
        if (isImageTextTask(task)) {
            await createHotWriteImageText({
                id: task.id,
                url,
                persona_id: personaId,
                rewrite_mode: taskRewriteMode,
            });
        } else {
            await createHotWrite({
                id: task.id,
                url,
                persona_id: personaId,
                visual_material_source: washTask ? 1 : task.visual_material_source ?? 3,
                rewrite_mode: taskRewriteMode,
            });
        }
        uni.hideLoading();
        uni.showToast({ title: "已重新提交", icon: "none", duration: 2500 });
        reset();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "重试失败", icon: "none", duration: 3000 });
    } finally {
        retryingTaskId.value = null;
    }
};

const onMore = async (task: any) => {
    uni.showActionSheet({
        itemList: ["删除任务", "详情信息"],
        success: async (res) => {
            if (res.tapIndex === 0) {
                uni.showModal({
                    title: "提示",
                    content: "确定删除该任务吗？",
                    success: async (res) => {
                        if (res.confirm) {
                            uni.showLoading({ title: "删除中..." });
                            try {
                                await deleteHotWrite({ id: task.id });
                                uni.hideLoading();
                                uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
                                taskList.value = taskList.value.filter((t) => t.id !== task.id);
                            } catch (error: any) {
                                uni.hideLoading();
                                uni.showToast({ title: error, icon: "none", duration: 3000 });
                            }
                        }
                    },
                });
            }
            if (res.tapIndex === 1) {
                toDetail(task);
            }
        },
    });
};

const toDetail = (task: any) => {
    uni.navigateTo({
        url: `/ai_modules/hot_write/pages/detail/detail?id=${task.id}`,
    });
};

const { start, end } = usePolling(silentRefresh, { time: 3000 });

onLoad((options: any) => {
    initSelectedPerson(options?.person_id || options?.id);
});

onReachBottom(() => {
    if (loading.value || finished.value) return;
    queryParams.page_no += 1;
    getLists();
});

onShow(() => {
    reset();
});
onUnmounted(() => {
    end();
});
onHide(() => {
    end();
});
onUnload(() => {
    end();
});
</script>

<style scoped>
.platform-chip {
    transition: background-color 180ms ease-out, opacity 150ms ease-out;
}

.platform-chip--pressed {
    opacity: 0.85;
}

.cta-pressed {
    opacity: 0.9;
}

.step-line-shine {
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.75) 50%, transparent 100%);
    background-size: 200% 100%;
    animation: line-shine 1.8s ease-in-out infinite;
}

@keyframes line-shine {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .platform-chip,
    .platform-chip--pressed,
    .cta-pressed,
    .step-line-shine {
        transition: none;
        animation: none;
        transform: none;
    }
}
</style>

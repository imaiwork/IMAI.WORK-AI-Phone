<template>
    <view
        class="flex flex-col min-h-screen"
        style="background: linear-gradient(180deg, #c2d9f8 0%, #d4e6fb 20%, #e8f1fb 40%, #f4f7fa 65%, #f4f7fa 100%)">
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
                        <text class="text-[24rpx] text-[#5A7BAF]">轻松打造同款内容，智能匹配爆款模板</text>
                    </view>
                </view>
            </view>
        </view>

        <view
            class="mx-4 bg-white rounded-[28rpx] px-[32rpx] py-[28rpx]"
            style="box-shadow: 0 4px 24px rgba(100, 140, 200, 0.1)">
            <view class="flex items-center justify-between mb-[16rpx]">
                <view class="flex items-center gap-[10rpx]">
                    <u-icon name="link" color="#6B7280" size="28"></u-icon>
                    <text class="text-[26rpx] text-[#374151] font-medium">粘贴作品链接</text>
                </view>
                <view
                    class="flex items-center gap-[8rpx] px-[20rpx] py-[8rpx] rounded-full border border-[#BFDBFE] bg-[rgba(239,246,255,0.8)]"
                    @click="onSelectIP">
                    <text class="text-[24rpx]" :class="[selectedPerson ? 'text-primary' : 'text-[#9CA3AF]']"
                        >IP: {{ selectedPerson?.persona_name || "请选择人设" }}</text
                    >
                    <u-icon name="arrow-down" :color="selectedPerson ? '#0065fb' : '#9CA3AF'" size="22"></u-icon>
                </view>
            </view>

            <view class="bg-[#F8FAFC] rounded-[16rpx] px-[24rpx] py-[20rpx] mb-[24rpx]">
                <textarea
                    v-model="inputUrl"
                    placeholder="粘贴抖音作品链接"
                    placeholder-style="color:#C0C4CC; font-size:26rpx"
                    class="w-full text-[26rpx] text-[#1F2937]"
                    style="min-height: 120rpx; line-height: 1.6"
                    auto-height></textarea>
                <view class="flex justify-end mt-[12rpx]">
                    <view class="flex items-center gap-[8rpx]" @click="onPaste">
                        <u-icon name="copy" color="#0065fb" size="26"></u-icon>
                        <text class="text-xs text-primary">粘贴</text>
                    </view>
                </view>
            </view>
            <view class="flex items-center gap-[10rpx] px-[4rpx] mb-[20rpx]">
                <u-icon name="info-circle" color="#F59E0B" size="24"></u-icon>
                <text class="text-[22rpx] text-[#92400E] leading-[1.6]">
                    提示：若注明了不可转载的作品可能会造成仿写失败
                </text>
            </view>

            <view
                class="w-full h-[96rpx] rounded-full flex items-center justify-center"
                style="background: linear-gradient(135deg, #0066ff, #3b82f6)"
                @click="handleCreate">
                <text class="text-[30rpx] font-semibold text-white"
                    >开始复刻<template v-if="getToken">(消耗{{ getToken }}算力)</template></text
                >
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
                        class="px-[24rpx] py-[10rpx] rounded-full text-[24rpx] font-medium"
                        :style="currentTab === tab.key ? 'background:#1F2937;color:#fff;' : 'color:#6B7280;'"
                        @click="handleTab(tab.key)">
                        {{ tab.label }}
                    </view>
                </view>
            </view>

            <view class="flex flex-col gap-[20rpx] pb-[40rpx]">
                <view
                    v-for="(task, index) in filteredList"
                    :key="task.id || index"
                    class="bg-white rounded-[24rpx] px-[28rpx] py-[28rpx]"
                    style="box-shadow: 0 2px 12px rgba(100, 140, 200, 0.08)"
                    @click="toDetail(task)">
                    <template v-if="task.publish_confirm == 1 && task.video_url">
                        <view class="flex items-start gap-[20rpx] mb-[24rpx]">
                            <view
                                class="w-[120rpx] h-[120rpx] rounded-[16rpx] overflow-hidden bg-[#F1F5F9] flex-shrink-0">
                                <image
                                    v-if="task.thumbnail"
                                    :src="task.thumbnail"
                                    mode="aspectFill"
                                    class="w-full h-full" />
                                <view v-else class="w-full h-full flex items-center justify-center">
                                    <u-icon name="play-right" color="#C0C4CC" size="40"></u-icon>
                                </view>
                            </view>
                            <view class="flex-1">
                                <text class="text-[28rpx] font-semibold text-[#1F2937] leading-[1.5] block mb-[16rpx]">
                                    {{ task.title }}
                                </text>
                                <view class="flex items-center gap-[12rpx]">
                                    <view class="px-[16rpx] py-[6rpx] rounded-full bg-[#ECFDF5]">
                                        <text class="text-[22rpx] text-[#059669] font-medium">已完成</text>
                                    </view>
                                    <text class="text-[22rpx] text-[#9CA3AF]">{{ task.create_time }}</text>
                                </view>
                            </view>
                        </view>

                        <view class="flex items-center gap-[16rpx]">
                            <view
                                class="flex-1 h-[80rpx] rounded-[16rpx] bg-[#F1F5F9] flex items-center justify-center"
                                @click.stop="onPreview(task)">
                                <text class="text-[26rpx] text-[#374151] font-medium">预览视频</text>
                            </view>
                            <view
                                class="flex-1 h-[80rpx] rounded-[16rpx] bg-primary flex items-center justify-center"
                                @click.stop="onPublish(task)">
                                <text class="text-white font-medium">一键发布</text>
                            </view>
                            <view
                                class="w-[80rpx] h-[80rpx] rounded-[16rpx] bg-[#F1F5F9] flex items-center justify-center"
                                @click.stop="onMore(task)">
                                <u-icon name="more-dot-fill" color="#6B7280" size="32"></u-icon>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <view class="flex items-center justify-between mb-[24rpx]">
                            <text class="text-[28rpx] font-semibold text-[#1F2937] flex-1 mr-3">
                                {{ task.name }}
                            </text>
                            <view v-if="task.status == 4" class="flex items-center gap-[6rpx]">
                                <view
                                    class="w-[24rpx] h-[24rpx] rounded-full bg-[#FEE2E2] flex items-center justify-center">
                                    <view class="w-[12rpx] h-[12rpx] rounded-full bg-[#EF4444]"></view>
                                </view>
                                <text class="font-bold text-[#EF4444]">失败</text>
                            </view>
                            <view v-if="[0, 1, 2].includes(task.status)" class="flex items-center gap-[6rpx]">
                                <view
                                    class="w-[24rpx] h-[24rpx] rounded-full bg-[#0065fb]/10 flex items-center justify-center animate-spin">
                                    <view class="w-[12rpx] h-[12rpx] rounded-full bg-primary"></view>
                                </view>
                                <text class="font-bold text-primary">进行中</text>
                            </view>
                        </view>

                        <view class="mb-[24rpx]">
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
                                            v-if="step.status === 'done' && task.steps[si + 1].status === 'running'"
                                            class="step-line-shine" />
                                    </view>
                                </template>
                            </view>

                            <view class="flex items-start mt-[10rpx]">
                                <template v-for="(step, si) in task.steps || []" :key="'label-' + si">
                                    <view class="w-[80rpx] flex-shrink-0 flex flex-col items-center">
                                        <text class="text-[20rpx]" :style="stepLabelStyle(step.status)">
                                            {{ step.name }}
                                        </text>
                                    </view>
                                    <view v-if="si < (task.steps || []).length - 1" class="flex-1"></view>
                                </template>
                            </view>
                        </view>
                        <view class="text-[18rpx] text-[#EF4444] mt-[4rpx] leading-[1.4] mb-2">
                            <template v-if="task.status === 4 && task.remarks"> 提示：{{ task.remarks }} </template>
                            <template v-if="[2, 3].includes(task.status) && task.publish_confirm == 0">
                                提示：发布文案未确认，请点击任务查看并前往确认发布文案。
                            </template>
                            <template v-if="task.status === 1 && task.shanjian_task_id == ''">
                                提示：仿写文案未确认，请点击任务查看并前往确认仿写文案。
                            </template>
                        </view>
                        <view class="flex items-center justify-between">
                            <view>
                                <text class="text-[22rpx] text-[#9CA3AF]">{{ task.create_time }}</text>
                                <view
                                    v-if="task.status == 2"
                                    class="flex items-center gap-[8rpx] px-[28rpx] py-[14rpx] rounded-[16rpx] bg-[#1F2937]"
                                    @click.stop="onPrePublish(task)">
                                    <u-icon name="play-right" color="#fff" size="26"></u-icon>
                                    <text class="text-[24rpx] text-white font-medium">预发布</text>
                                </view>
                            </view>
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-full bg-[#F1F5F9] flex items-center justify-center"
                                @click.stop="onMore(task)">
                                <u-icon name="more-dot-fill" color="#6B7280" size="28"></u-icon>
                            </view>
                        </view>
                    </template>
                </view>

                <view v-if="!loading && filteredList.length === 0" class="flex items-center justify-center py-[60rpx]">
                    <empty :size="250" />
                </view>

                <view class="flex items-center justify-center py-[24rpx] gap-[12rpx]" v-else>
                    <block v-if="loading">
                        <u-loading mode="circle" size="28" color="#0065fb"></u-loading>
                        <text class="text-[24rpx] text-[#9ca3af]">加载中...</text>
                    </block>
                    <block v-else-if="finished && taskList.length > 0">
                        <view class="h-[2rpx] w-[100rpx] bg-[#E5E7EB]"></view>
                        <text class="text-[24rpx] text-[#9CA3AF]">已加载全部</text>
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
        :skip-un-config="true"
        :is-config="false"
        @select="handleSelectPerson" />
    <video-preview-v2 v-model:show="showVideoPreview" :video-url="videoUrl" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import { getHotWriteList, createHotWrite, deleteHotWrite } from "@/api/hot_write";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useUserStore } from "@/stores/user";
import usePolling from "@/hooks/usePolling";
import StepDone from "@/ai_modules/hot_write/static/icons/step_done.svg";
import StepRunning from "@/ai_modules/hot_write/static/icons/step_running.svg";
import StepPending from "@/ai_modules/hot_write/static/icons/step_pending.svg";
import StepFailed from "@/ai_modules/hot_write/static/icons/step_failed.svg";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const getToken = computed(() => userStore.getTokenByScene(TokensSceneEnum.HOT_WRITE)?.score);
const rechargePopupRef = shallowRef();

const inputUrl = ref("");
const selectedPerson = ref<any>(null);
const currentTab = ref("all");
const showChoosePerson = ref(false);
const showVideoPreview = ref(false);
const videoUrl = ref("");
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

/**
 * 推导每个步骤的状态
 * status 含义：
 *   0 → 关联人设中（step[0] running）
 *   1 → 提取文案中（step[1] running）
 *   2 → 匹配形象中（step[2] running）
 *   3 → 全部完成
 *   4 → 失败（step[1] failed，之前 done，之后 pending）
 */
function resolveStepStatus(taskStatus: any, index: number): "done" | "running" | "pending" | "failed" {
    const s = Number(taskStatus);
    if (s === 4) {
        // 失败固定在"提取文案"步骤（index=1）
        if (index < 1) return "done";
        if (index === 1) return "failed";
        return "pending";
    }
    if (s === 3) return "done";
    if (index <= s) return "done";
    if (index === s + 1) return "running";
    return "pending";
}

function resolveStepProgress(taskStatus: any) {
    const s = Number(taskStatus);
    if (s === 4) return 0; // 失败不展示进度

    const doneCount = stepDefs.filter((_, index) => {
        return resolveStepStatus(taskStatus, index) === "done";
    }).length;

    return Math.round((doneCount / stepDefs.length) * 100);
}

const normalizeTask = (item: any) => {
    return {
        ...item,
        name: item.title || "提取文案中...",
        progress: resolveStepProgress(item.status),
        steps: stepDefs.map((step, index) => {
            const status = resolveStepStatus(item.status, index);
            return {
                status,
                name: index === 2 ? (item.is_material === 1 ? "匹配素材" : "匹配形象") : step.name,
                remark: item.remarks,
            };
        }),
    };
};

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
    // status 3=完成 4=失败，两者都不需要轮询
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
        // 全部完成或失败时停止轮询
        if (lists.every((item: any) => item.status === 3 || item.status === 4)) {
            end();
        }
    } catch (_) {}
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
    if (status === "failed") return "background:#FEF2F2; border: 2rpx solid #FECACA";
    return "background:#F1F5F9; border: 2rpx solid #E2E8F0";
}

function stepIcon(status: string) {
    if (status === "done") return StepDone;
    if (status === "running") return StepRunning;
    if (status === "failed") return StepFailed;
    return StepPending;
}

function stepLabelStyle(status: string) {
    if (status === "done") return "color:#059669";
    if (status === "running") return "color:#0066FF; font-weight:600";
    if (status === "failed") return "color:#EF4444; font-weight:600";
    return "color:#9CA3AF";
}

function stepLineWrapStyle(leftStatus: string, rightStatus: string) {
    if (leftStatus === "done" && rightStatus === "done") return "background: linear-gradient(90deg, #34D399, #059669)";
    if (leftStatus === "done" && rightStatus === "running")
        return "background: linear-gradient(90deg, #34D399, #60A5FA)";
    if (leftStatus === "done" && rightStatus === "failed")
        return "background: linear-gradient(90deg, #34D399, #FCA5A5)";
    if (leftStatus === "done" && rightStatus === "pending")
        return "background: linear-gradient(90deg, #6EE7B7, #E2E8F0)";
    if (leftStatus === "running" && rightStatus === "pending")
        return "background: repeating-linear-gradient(90deg, #93C5FD 0px, #93C5FD 6px, transparent 6px, transparent 12px)";
    if (leftStatus === "failed") return "background: #FEE2E2";
    return "background: repeating-linear-gradient(90deg, #CBD5E1 0px, #CBD5E1 4px, transparent 4px, transparent 8px)";
}

// ────────── 事件处理 ──────────

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

const handleCreate = async () => {
    if (!inputUrl.value.trim()) {
        uni.$u.toast("请输入作品链接或粘贴作品链接");
        return;
    }
    if (!selectedPerson.value) {
        uni.$u.toast("请选择人设");
        showChoosePerson.value = true;
        return;
    }
    if (userTokens.value <= getToken.value) {
        rechargePopupRef.value?.open();
        return;
    }
    uni.showLoading({ title: "开始复刻..." });
    try {
        await createHotWrite({
            url: inputUrl.value,
            persona_id: selectedPerson.value?.id,
        });
        inputUrl.value = "";
        uni.hideLoading();
        uni.showToast({ title: "复刻任务创建成功", icon: "none", duration: 3000 });
        reset();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "复刻任务创建失败", icon: "none", duration: 3000 });
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
    videoUrl.value = task.video_url;
    showVideoPreview.value = true;
};

const onPublish = (task: any) => {
    uni.$u.route({
        url: "/ai_modules/device/pages/create_task/create_task",
        params: {
            source: "hot_write",
            data: JSON.stringify({
                url: task.video_url,
                pic: task.thumbnail,
                title: task.title,
                content: task.publish_text,
                topic: task.publish_topic ? JSON.parse(task.publish_topic) : [],
            }),
        },
    });
};

const onMore = async (task: any) => {
    const itemList = ["删除任务"];
    if (task.status !== 4) {
        itemList.push("详情信息");
    }
    uni.showActionSheet({
        itemList: itemList,
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
    if (task.status === 4) return;
    uni.navigateTo({
        url: `/ai_modules/hot_write/pages/detail/detail?id=${task.id}`,
    });
};

const { start, end } = usePolling(silentRefresh, {
    time: 3000,
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
</style>

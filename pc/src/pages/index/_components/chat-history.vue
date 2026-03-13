<template>
    <div class="w-full h-full flex flex-col relative">
        <div class="px-4 mt-3">
            <div class="flex items-center justify-between px-1">
                <div class="flex items-center gap-2 text-slate-400">
                    <Icon name="local-icon-time" :size="14"></Icon>
                    <span class="text-xs font-black uppercase tracking-widest">最近会话</span>
                </div>
                <nuxt-link class="text-xs text-slate-400" to="/creation">全部</nuxt-link>
            </div>
        </div>

        <div class="grow min-h-0">
            <div v-if="isRefreshing" class="px-4 space-y-6 animate-pulse mt-4">
                <div v-for="i in 2" :key="i">
                    <div class="h-3 w-12 bg-gray-200 rounded-full mb-4 mx-2"></div>
                    <div class="space-y-2">
                        <div v-for="j in 3" :key="j" class="h-11 bg-gray-200 rounded-xl"></div>
                    </div>
                </div>
            </div>

            <template v-else>
                <ElScrollbar v-if="chatHistory.length > 0" :distance="20" @end-reached="load">
                    <div class="px-3 pb-6">
                        <div v-for="group in groupChatHistoryByTime" :key="group.date" class="mt-4">
                            <div class="text-[11px] font-black text-slate-400 px-3 py-2 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                                {{ group.date }}
                            </div>

                            <div class="mt-1">
                                <div
                                    v-for="session in group.sessions"
                                    :key="session.task_id"
                                    class="session-item group"
                                    :class="{
                                        'is-active': currentSessionId === session.task_id,
                                        'is-popover-open': activePopoverId === session.task_id,
                                    }"
                                    @click="switchToSession(session.task_id)">
                                    <div class="session-title">
                                        {{ session.message || "空会话" }}
                                    </div>

                                    <ElPopover
                                        popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                        placement="right-start"
                                        trigger="click"
                                        :show-arrow="false"
                                        :popper-options="{
                                            modifiers: [
                                                {
                                                    name: 'offset',
                                                    options: {
                                                        offset: [40, -80],
                                                    },
                                                },
                                            ],
                                        }"
                                        @show="() => handlePopoverShow(session.task_id)"
                                        @hide="() => handlePopoverHide(session.task_id)">
                                        <template #reference>
                                            <div class="more-trigger" @click.stop>
                                                <Icon name="el-icon-MoreFilled" :size="12"></Icon>
                                            </div>
                                        </template>
                                        <div
                                            class="table-action-item !text-red-500 hover:!bg-red-50"
                                            @click="deleteSession(session.task_id)">
                                            <Icon name="local-icon-delete" :size="14"></Icon>
                                            <span>彻底删除</span>
                                        </div>
                                    </ElPopover>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="isFinished"></load-text>
                    </div>
                </ElScrollbar>

                <div v-else class="h-full flex flex-col items-center justify-center opacity-40 grayscale">
                    <ElEmpty :image-size="80" description="没有找到对话记录" />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useChatHistory } from "../_modules/composables/useChatHistory";

const {
    chatHistory,
    isLoading,
    isRefreshing,
    isFinished,
    currentSessionId,
    fetchChatRecord,
    createNewSession,
    switchToSession,
    deleteSession,
    loadHistory,
} = useChatHistory();

const activePopoverId = ref<string | null>(null);

const handlePopoverShow = (sessionId: string) => {
    activePopoverId.value = sessionId;
};

const handlePopoverHide = (sessionId: string) => {
    activePopoverId.value = null;
};

const groupChatHistoryByTime = computed(() => {
    const now = dayjs();
    const groupsMap = new Map<string, any[]>();

    chatHistory.value.forEach((session) => {
        const sessionDate = dayjs(session.update_time);
        const daysDiff = now.diff(sessionDate, "day");

        let groupKey: string;

        if (daysDiff === 0) {
            groupKey = "今天";
        } else if (daysDiff === 1) {
            groupKey = "昨天";
        } else if (daysDiff <= 7) {
            groupKey = "七天内";
        } else if (daysDiff <= 30) {
            groupKey = "30天内";
        } else {
            groupKey = sessionDate.format("YYYY-MM");
        }

        if (!groupsMap.has(groupKey)) {
            groupsMap.set(groupKey, []);
        }
        groupsMap.get(groupKey)!.push(session);
    });

    const groupsArray = Array.from(groupsMap.entries()).map(([date, sessions]) => ({
        date,
        sessions,
    }));

    return groupsArray.sort((a, b) => {
        // 定义正确的时间顺序
        const timeOrder = ["今天", "昨天", "七天内", "30天内"];

        const aIndex = timeOrder.indexOf(a.date);
        const bIndex = timeOrder.indexOf(b.date);

        // 如果都是预定义的时间分组，按顺序排列
        if (aIndex !== -1 && bIndex !== -1) {
            return aIndex - bIndex;
        }

        // 预定义分组优先于日期分组
        if (aIndex !== -1) return -1;
        if (bIndex !== -1) return 1;

        // 日期分组按时间倒序（最新的在前）
        return b.date.localeCompare(a.date);
    });
});

const handleNewSession = () => {
    createNewSession();
};

const load = (e: any) => {
    if (e == "bottom") {
        loadHistory();
    }
};

onMounted(() => {
    fetchChatRecord();
});
</script>

<style scoped lang="scss">
.modern-new-btn {
    @apply border-none shadow-light shadow-[#0065fb]/20 transition-all transform;
    background: linear-gradient(135deg, #0065fb 0%, #2581ff 100%);
    &:hover {
        @apply -translate-y-0.5 shadow-light shadow-[#0065fb]/30;
        filter: brightness(1.1);
    }
    &:active {
        @apply translate-y-0 scale-[0.98];
    }
}

.session-item {
    @apply flex items-center gap-3 px-3 h-[35px] cursor-pointer rounded-xl relative overflow-hidden;
    @apply text-slate-600 border border-[transparent];

    // 悬停样式 + popover 打开时的样式
    &:hover,
    &.is-popover-open {
        @apply bg-[#F1F2F3];
    }

    &.is-active {
        @apply bg-[#F1F2F3] font-medium;
    }

    .session-icon {
        @apply opacity-30 flex-shrink-0 transition-opacity;
    }

    &:hover .session-icon,
    &.is-popover-open .session-icon,
    &.is-active .session-icon {
        @apply opacity-100;
    }

    .session-title {
        @apply text-[13px] truncate flex-1 leading-none;
    }

    .more-trigger {
        @apply w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 opacity-0;

        &:hover {
            @apply bg-[#E5E6E8] text-slate-600;
        }
    }

    &:hover .more-trigger,
    &.is-popover-open .more-trigger {
        @apply opacity-100;
    }

    &.is-popover-open .more-trigger {
        @apply bg-[#E5E6E8] text-slate-600;
    }
}
</style>

<template>
    <div class="w-full h-full flex flex-col relative bg-[#F9F9FA] border-r border-[#e2e8f0]/60 transition-all">
        <div class="px-4 pt-8 pb-4">
            <ElButton class="modern-new-btn w-full !h-[48px] !rounded-2xl" type="primary" @click="handleNewSession">
                <Icon name="local-icon-history_add" :size="18"></Icon>
                <span class="ml-2 text-[14px] font-[900] tracking-wide">新建智能会话</span>
            </ElButton>

            <div class="flex items-center justify-between mt-8 px-1">
                <div class="flex items-center gap-2 text-slate-400">
                    <Icon name="local-icon-time" :size="14"></Icon>
                    <span class="text-[12px] font-black uppercase tracking-widest">历史会话</span>
                </div>
            </div>
        </div>

        <div class="grow min-h-0">
            <div v-if="isRefreshing" class="px-4 space-y-6 animate-pulse mt-4">
                <div v-for="i in 2" :key="i">
                    <div class="h-3 w-12 bg-slate-200 rounded-full mb-4 mx-2"></div>
                    <div class="space-y-2">
                        <div v-for="j in 3" :key="j" class="h-11 bg-slate-100/80 rounded-xl"></div>
                    </div>
                </div>
            </div>

            <template v-else>
                <ElScrollbar v-if="chatHistory.length > 0" class="custom-scrollbar" :distance="20" @end-reached="load">
                    <div class="px-3 pb-6">
                        <div v-for="group in groupChatHistoryByTime" :key="group.date" class="mt-4">
                            <div
                                class="sticky top-0 z-[10] bg-slate-50/80 backdrop-blur-md text-[11px] font-black text-slate-400 px-3 py-2 flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-200"></span>
                                {{ group.date }}
                            </div>

                            <div class="mt-1">
                                <div
                                    v-for="session in group.sessions"
                                    :key="session.task_id"
                                    class="session-item group"
                                    :class="{ 'is-active': currentSessionId === session.task_id }"
                                    @click="switchToSession(session.task_id)">
                                    <div class="session-title">
                                        {{ session.message || "空会话" }}
                                    </div>

                                    <ElPopover
                                        popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light"
                                        placement="right-start"
                                        trigger="click"
                                        :show-arrow="false"
                                        :offset="0">
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

// 按照时间在分类
const groupChatHistoryByTime = computed(() => {
    const now = dayjs();
    const groupsMap = new Map<string, any[]>();
    chatHistory.value.forEach((session) => {
        const sessionDate = dayjs(session.create_time);

        let groupKey: string;
        if (now.diff(sessionDate, "day") < 30) {
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
        if (a.date === b.date) return 0;
        if (a.date === "30天内") return -1;
        if (b.date === "30天内") return 1;
        return b.date.localeCompare(a.date);
    });
});
const active = ref("");
const visibleChange = (visible: boolean, id: string) => {
    active.value = visible ? id : "";
};

const chooseActive = (id: string) => {
    active.value = id;
};

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
    @apply flex items-center gap-3 px-3 h-11 cursor-pointer rounded-xl transition-all relative overflow-hidden;
    @apply text-slate-600 border border-[transparent];

    &:hover {
        @apply bg-white border-[#e2e8f0]/60  text-slate-900;
    }

    &.is-active {
        @apply bg-white border-[#0065fb]/20  shadow-[#0065fb]/5 text-primary font-bold;
    }

    .session-icon {
        @apply opacity-30 flex-shrink-0 transition-opacity;
    }
    &:hover .session-icon,
    &.is-active .session-icon {
        @apply opacity-100;
    }

    .session-title {
        @apply text-[13px] truncate flex-1 leading-none;
    }

    .more-trigger {
        @apply w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 opacity-0 transition-all;
        &:hover {
            @apply bg-slate-100 text-slate-600;
        }
    }
    &:hover .more-trigger {
        @apply opacity-100;
    }
}
</style>

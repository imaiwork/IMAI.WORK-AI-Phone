<template>
    <div class="w-full h-full flex flex-col bg-[#F9FAFB] border-r border-gray-200">
        <div class="px-4 mt-[33px]">
            <ElButton class="w-full !h-[45px]" @click="handleNewSession">
                <Icon name="local-icon-history_add" :size="16"></Icon>
                <span class="ml-2 text-[14px] font-bold">新建会话</span>
            </ElButton>
            <div class="flex items-center gap-1 mt-[25px]">
                <Icon name="local-icon-time" :size="16"></Icon>
                <span class="text-[14px] font-bold">最近对话</span>
            </div>
        </div>
        <div class="grow min-h-0 mt-4" v-loading="isLoading">
            <ElScrollbar @end-reached="load" v-if="chatHistory.length > 0">
                <div class="px-2">
                    <div v-for="group in groupChatHistoryByTime">
                        <div class="text-[#81858c] text-xs px-2 py-2.5 sticky top-0 z-[88] bg-[#f9fafb]">
                            {{ group.date }}
                        </div>
                        <div class="mb-4 flex flex-col gap-y-1">
                            <div
                                v-for="session in group.sessions"
                                :key="session.task_id"
                                class="h-10 flex items-center justify-between px-2 py-[9px] cursor-pointer transition-colors rounded-lg group relative"
                                :class="[
                                    currentSessionId === session.task_id
                                        ? 'bg-[#e4edfd] text-primary hover:bg-[#e4edfd] hover:text-primary'
                                        : ' hover:bg-[#f1f3f5] ',
                                ]"
                                @click="() => switchToSession(session.task_id)">
                                <div class="text-[14px] truncate">
                                    {{ session.message }}
                                </div>
                                <ElPopover
                                    popper-class="!p-2 !rounded-xl !border-[#efefef]"
                                    trigger="click"
                                    :show-arrow="false"
                                    @hide="visibleChange(false, session.task_id)">
                                    <template #reference>
                                        <div
                                            class="absolute -right-2 h-full flex items-center justify-center w-[56px] invisible group-hover:visible"
                                            :class="active === session.task_id ? '!visible' : ''"
                                            @click.stop="chooseActive(session.task_id)">
                                            <div
                                                class="w-[28px] h-[28px] flex items-center justify-center rounded-full hover:bg-[#2631480f] text-gray-500"
                                                :class="active === session.task_id ? '!bg-[#2631480f]' : ''">
                                                <Icon name="el-icon-MoreFilled" :size="14"></Icon>
                                            </div>
                                        </div>
                                    </template>
                                    <div class="flex flex-col gap-2">
                                        <div
                                            class="h-8 px-3 rounded-lg cursor-pointer flex items-center gap-3 hover:shadow-[0_0_0_1px_rgba(239,239,239,1)] hover:bg-[#F6F6F6]"
                                            @click="deleteSession(session.task_id)">
                                            <span
                                                class="flex w-5 h-5 rounded items-center justify-center bg-[#0000000b]">
                                                <Icon name="local-icon-delete"></Icon>
                                            </span>
                                            <span>删除</span>
                                        </div>
                                    </div>
                                </ElPopover>
                            </div>
                        </div>
                    </div>
                    <div v-if="!isFinished" class="text-tx-secondary text-center text-xs w-full py-4">暂无更多了~</div>
                </div>
            </ElScrollbar>
            <div class="p-10 text-center" v-else>
                <ElEmpty description="暂无会话记录" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useChatHistory } from "../_modules/composables/useChatHistory";

const {
    chatHistory,
    isLoading,
    isFinished,
    currentSessionId,
    fetchChatHistory,
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
    fetchChatHistory();
});
</script>

<style scoped>
/* 移除原来的 SCSS 样式 */
</style>

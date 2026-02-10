<template>
    <div class="h-full flex flex-col bg-[#FFFFFF] rounded-[20px] border border-br overflow-hidden">
        <div class="flex items-center justify-between px-6 py-6">
            <div class="flex items-center gap-4">
                <div
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl hover:bg-slate-50 cursor-pointer transition-all group"
                    @click="emit('back')">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                    <span class="text-slate-600 font-medium text-[14px] group-hover:text-primary">返回计划列表</span>
                </div>
                <div class="w-[1px] h-6 bg-slate-100 mx-2"></div>
                <div class="text-[18px] font-black text-slate-800">推送日志</div>
            </div>
            <div class="flex items-center gap-3">
                <ElInput
                    v-model="queryParams.nickname"
                    class="!w-[280px] custom-input"
                    placeholder="搜索好友昵称或ID..."
                    clearable
                    @clear="getLists()"
                    @keydown.enter="getLists()">
                    <template #prefix>
                        <Icon name="el-icon-Search"></Icon>
                    </template>
                </ElInput>
            </div>
        </div>

        <div class="grow min-h-0">
            <ElTable :data="pager.lists" height="100%" v-loading="pager.loading">
                <ElTableColumn label="推送好友" min-width="220">
                    <template #default="{ row }">
                        <div class="flex flex-col">
                            <span class="font-black text-slate-800 text-[14px]">{{ row.nickname }}</span>
                            <span class="text-[11px] text-slate-400 font-medium uppercase tracking-tighter"
                                >ID: {{ row.friend_id }}</span
                            >
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="计划推送点" width="200">
                    <template #default="{ row }">
                        <div class="flex items-center gap-1 text-slate-500 font-medium text-[13px]">
                            <Icon name="el-icon-Timer" :size="14" color="var(--slate-300)"></Icon>
                            {{ row.push_time || "-" }}
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="执行时间" prop="push_real_time" width="180">
                    <template #default="{ row }">
                        <span class="text-slate-500 font-medium text-[13px]">{{ row.push_real_time || "未执行" }}</span>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="发布状态" width="160" align="center">
                    <template #default="{ row }">
                        <div class="inline-flex items-center">
                            <span v-if="row.status == 0" class="status-badge is-info">
                                <span class="dot"></span> 待推送
                            </span>
                            <span v-if="row.status == 1" class="status-badge is-success">
                                <span class="dot"></span> 推送成功
                            </span>
                            <span v-if="row.status == 2" class="status-badge is-danger">
                                <span class="dot"></span> 推送失败
                            </span>
                        </div>
                    </template>
                </ElTableColumn>

                <ElTableColumn label="操作" width="100" fixed="right" align="center">
                    <template #default="{ row }">
                        <ElButton
                            type="danger"
                            link
                            class="!font-medium hover:!text-red-600"
                            @click="handleDelete(row.id)">
                            删除
                        </ElButton>
                    </template>
                </ElTableColumn>

                <template #empty>
                    <div class="py-20">
                        <ElEmpty description="暂无推送记录">
                            <template #image>
                                <Icon name="local-icon-empty" :size="60" color="#E2E8F0"></Icon>
                            </template>
                        </ElEmpty>
                    </div>
                </template>
            </ElTable>
        </div>
        <div class="flex justify-between items-center px-8 py-5 bg-slate-50">
            <div class="text-xs font-medium text-[#94A3B8] uppercase tracking-wider">共 {{ pager.count }} 条记录</div>
            <pagination v-model="pager" @change="getLists"></pagination>
        </div>
    </div>
</template>

<script setup lang="ts">
import { getSopPushLog, deleteWeChatFriendSopPush } from "@/api/person_wechat";

const emit = defineEmits<{
    (e: "back"): void;
}>();

const nuxtApp = useNuxtApp();
const query = searchQueryToObject();

const queryParams = reactive({
    nickname: "",
    push_id: query.id,
});

const { pager, getLists } = usePaging({
    fetchFun: getSopPushLog,
    params: queryParams,
});

const handleDelete = async (id: number) => {
    nuxtApp.$confirm({
        message: "是否删除该推送记录",
        onConfirm: async () => {
            try {
                await deleteWeChatFriendSopPush({ id });
                getLists();
                feedback.msgSuccess("删除成功");
            } catch (error) {
                feedback.msgError(error);
            }
        },
    });
};
onMounted(() => {
    getLists();
});
</script>

<style lang="scss" scoped>
.status-badge {
    @apply flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black tracking-tight;

    .dot {
        @apply w-1.5 h-1.5 rounded-full;
    }

    &.is-info {
        @apply bg-blue-50 text-blue-500;
        .dot {
            @apply bg-blue-500 animate-pulse;
        }
    }

    &.is-success {
        @apply bg-emerald-50 text-emerald-500;
        .dot {
            @apply bg-emerald-500;
        }
    }

    &.is-danger {
        @apply bg-red-50 text-red-500;
        .dot {
            @apply bg-red-500;
        }
    }
}
</style>

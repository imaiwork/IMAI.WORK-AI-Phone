<template>
    <div class="h-full flex flex-col bg-white rounded-xl">
        <div class="flex-shrink-0 p-4">
            <ElScrollbar>
                <div class="flex items-center justify-between gap-[14px]">
                    <div class="flex items-center gap-2 cursor-pointer" @click="emit('back')">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                        <div class="">返回</div>
                    </div>
                    <div class="flex items-center gap-[14px]">
                        <ElInput
                            v-model="queryParams.nickname"
                            prefix-icon="el-icon-Search"
                            class="!w-[240px]"
                            placeholder="请输入名称"
                            clearable
                            @clear="getLists()"
                            @keydown.enter="getLists()">
                            <template #append>
                                <ElButton @click="getLists()"> 搜索 </ElButton>
                            </template>
                        </ElInput>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="grow min-h-0 flex flex-col">
            <div class="grow min-h-0">
                <ElTable
                    :data="pager.lists"
                    stripe
                    height="100%"
                    :header-row-style="{ height: '62px' }"
                    :row-style="{ height: '60px' }"
                    v-loading="pager.loading">
                    <ElTableColumn label="推送好友" min-width="200">
                        <template #default="{ row }"> {{ row.nickname }}（{{ row.friend_id }}） </template>
                    </ElTableColumn>
                    <ElTableColumn label="发布时间点" prop="push_time" width="160">
                        <template #default="{ row }">
                            {{ row.push_time || "-" }}
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="执行时间" prop="push_real_time" width="160"> </ElTableColumn>
                    <ElTableColumn label="发布状态" prop="create_time" width="180">
                        <template #default="{ row }">
                            <div class="flex items-center gap-2 justify-center">
                                <template v-if="row.status == 0">
                                    <span class="text-info"> 待推送 </span>
                                </template>
                                <template v-if="row.status == 1">
                                    <span class="text-success"> 推送成功 </span>
                                </template>
                                <template v-if="row.status == 2">
                                    <span class="text-danger"> 推送失败 </span>
                                </template>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="100" fixed="right">
                        <template #default="{ row }">
                            <ElButton type="danger" link @click="handleDelete(row.id)"> 删除 </ElButton>
                        </template>
                    </ElTableColumn>
                    <template #empty>
                        <ElEmpty description="暂无数据"></ElEmpty>
                    </template>
                </ElTable>
            </div>
            <div class="flex justify-end p-4">
                <pagination v-model="pager" @change="getLists"></pagination>
            </div>
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
<style lang="scss" scoped></style>

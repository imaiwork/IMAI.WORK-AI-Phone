<template>
    <view class="h-screen flex flex-col relative">
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :background="{
                    background: 'transparent',
                }"
                title="创作记录"
                title-bold>
            </u-navbar>
        </view>
        <view class="grow min-h-0 relative z-10 flex flex-col mt-[32rpx]">
            <view class="grow min-h-0">
                <z-paging
                    ref="pagingRef"
                    v-model="recordLists"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryList">
                    <view class="flex flex-col gap-4 px-[32rpx]">
                        <view
                            class="bg-white rounded-[24rpx] px-[32rpx] py-[24rpx]"
                            v-for="(item, index) in recordLists"
                            :key="index"
                            @click="handleRecord(item)">
                            <view class="line-clamp-1 font-bold text-[26rpx]">
                                {{ item.scene_name }}
                            </view>
                            <view class="line-clamp-3 mt-[16rpx] opacity-30 text-[22rpx]">
                                {{ item.message }}
                            </view>
                            <view class="my-3">
                                <u-line></u-line>
                            </view>
                            <view class="flex items-center justify-between">
                                <view class="text-[#858597] text-xs bg-[#F9FAFB] rounded-full px-2 py-1">
                                    {{ item.update_time }}
                                </view>
                                <view @click.stop="handleDelete(item.task_id, index)">
                                    <u-icon name="/static/images/icons/delete.svg" size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { getCreativeRecord, deleteCreativeRecord } from "@/api/chat";

const recordLists = ref<any[]>([]);

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getCreativeRecord({
            page_no,
            page_size,
            scene_id: "",
            type: 1,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const handleRecord = (row: any) => {
    const { robot_id, assistant_id, robot_name, avatar, task_id } = row;
    if (assistant_id == 0) {
        uni.$u.route({
            url: "/pages/index/index",
            params: {
                task_id,
                agent_name: robot_name,
                agent_id: robot_id,
                agent_logo: avatar,
            },
        });
    } else {
        uni.$u.route({
            url: "/packages/pages/robot_chat/robot_chat",
            params: {
                id: assistant_id,
                task_id,
            },
        });
    }
};

const handleDelete = async (task_id: number, index: number) => {
    uni.showModal({
        title: "提示",
        content: "确定删除此创作记录吗？",
        success: async (res) => {
            if (res.confirm) {
                try {
                    await deleteCreativeRecord({ task_id });
                    recordLists.value.splice(index, 1);
                } catch (error) {
                    uni.$u.toast("删除失败");
                }
            }
        },
    });
};
</script>

<style lang="scss" scoped></style>

<template>
    <popup-bottom v-model="show" title="历史记录" :is-disabled-touch="true" custom-class="bg-[#F9FAFB]">
        <template #content>
            <view class="h-full py-[30rpx]">
                <z-paging
                    ref="pagingRef"
                    v-model="recordLists"
                    :auto="false"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="queryRecordList">
                    <view class="flex flex-col gap-4 px-[32rpx]">
                        <view
                            v-for="(item, index) in recordLists"
                            :key="index"
                            class="bg-white rounded-[24rpx] p-[24rpx]"
                            @click="emit('select', item)">
                            <view class="flex items-center justify-between">
                                <view class="text-[#AEAFB0] text-xs bg-[#F9FAFB] rounded-[12rpx] py-[4rpx] px-[8rpx]">
                                    {{ formatRecordTime(item.create_time) }}
                                </view>
                                <view class="flex items-center gap-2">
                                    <view
                                        v-if="phoneAgent"
                                        class="text-[20rpx] rounded-[12rpx] py-[4rpx] px-[10rpx]"
                                        :class="getPhoneAgentStatusClass(getPhoneAgentStatus(item))">
                                        {{ getPhoneAgentStatusLabel(item) }}
                                    </view>
                                    <view
                                        v-if="phoneAgent"
                                        class="px-[8rpx] py-[4rpx]"
                                        @click.stop="handleDeletePhoneAgent(item)">
                                        <u-icon name="trash" color="#EF4444" size="28"></u-icon>
                                    </view>
                                </view>
                            </view>
                            <view class="line-clamp-3 mt-4">
                                {{ getRecordTitle(item) }}
                            </view>
                            <view
                                v-if="phoneAgent && getPhoneAgentDeviceText(item)"
                                class="text-[22rpx] text-[#9CA3AF] mt-2 line-clamp-1">
                                设备名称：{{ getPhoneAgentDeviceText(item) }}
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { ref, shallowRef, computed, nextTick, watch } from "vue";
import { getCreativeRecord } from "@/api/chat";
import { drawConversationLists, normalizeConversationList } from "@/api/draw";
import { deletePhoneAgentConversation, getPhoneAgentTaskHistory } from "@/api/phone_agent";
import { getMapLeadConversations } from "@/api/map_lead";

const props = defineProps<{
    visible: boolean;
    phoneAgent?: boolean;
    /** chat | image | video | map | ppt */
    workbenchScene?: string;
}>();

const emit = defineEmits<{
    (e: "update:visible", v: boolean): void;
    (e: "select", record: any): void;
}>();

const show = computed({
    get: () => props.visible,
    set: (v: boolean) => emit("update:visible", v),
});
const phoneAgent = computed(() => !!props.phoneAgent);
const isDrawScene = computed(() => ["image", "video", "ppt"].includes(props.workbenchScene || ""));
const isMapScene = computed(() => props.workbenchScene === "map");

const pagingRef = shallowRef();
const recordLists = ref<any[]>([]);
const deleting = ref(false);

const queryRecordList = async (page_no: number, page_size: number) => {
    try {
        if (props.phoneAgent) {
            const { lists } = await getPhoneAgentTaskHistory({ page_no, page_size });
            pagingRef.value?.complete(lists || []);
            return;
        }

        if (isDrawScene.value) {
            const mediaType = props.workbenchScene || "image";
            const raw = await drawConversationLists({ media_type: mediaType });
            const lists = normalizeConversationList(raw);
            // 本地分页
            const start = (page_no - 1) * page_size;
            pagingRef.value?.complete(lists.slice(start, start + page_size));
            return;
        }

        if (isMapScene.value) {
            const res: any = await getMapLeadConversations({ page_no, page_size });
            const lists = Array.isArray(res?.lists) ? res.lists : Array.isArray(res) ? res : [];
            pagingRef.value?.complete(lists);
            return;
        }

        const { lists } = await getCreativeRecord({
            page_no,
            page_size,
            scene_id: 0,
            type: 1,
        });
        pagingRef.value?.complete(lists);
    } catch (error) {
        console.error(error);
        pagingRef.value?.complete([]);
    }
};

watch(
    () => [props.visible, props.phoneAgent, props.workbenchScene],
    async ([visible]) => {
        if (!visible) return;
        recordLists.value = [];
        await nextTick();
        pagingRef.value?.reload();
    },
);

const getRecordTitle = (item: any) => {
    if (props.phoneAgent) {
        return item.title || item.last_message || item.message || "-";
    }
    if (isDrawScene.value) {
        return item.title || item.last_prompt || "-";
    }
    if (isMapScene.value) {
        return item.title || item.query || item.last_query || item.conversation_id || "-";
    }
    return item.message || item.file_info?.name || "-";
};

const formatRecordTime = (time: number | string) => {
    if (time === 0 || time === "0" || time == null || time === "") return "-";
    if (typeof time === "string" && time.includes("-")) return time;

    const numeric = Number(time);
    if (!Number.isFinite(numeric) || numeric <= 0) return String(time);

    const timestamp = numeric < 1e12 ? numeric * 1000 : numeric;
    return uni.$u.timeFormat(timestamp, "yyyy-mm-dd hh:MM") || String(time);
};

const getPhoneAgentStatus = (item: any) => {
    return item.last_task_status || item.status || "";
};

const getPhoneAgentStatusLabel = (item: any) => {
    if (item.status_text) return item.status_text;
    return getPhoneAgentStatusText(getPhoneAgentStatus(item));
};

const getPhoneAgentDeviceText = (item: any) => {
    const name = item.device_name || item.device_code || "";
    const model = item.device_model || "";
    if (name && model && name !== model) return `${name}（${model}）`;
    return name || model || "";
};

const getPhoneAgentStatusText = (status: string) => {
    const statusMap: Record<string, string> = {
        created: "已创建",
        observing: "观察中",
        model_pending: "思考中",
        waiting_report: "执行中",
        completed: "已完成",
        failed: "失败",
        canceled: "已取消",
    };
    return statusMap[status] || status || "-";
};

const getPhoneAgentStatusClass = (status: string) => {
    if (status === "completed") return "bg-[#DCFCE7] text-[#16A34A]";
    if (status === "failed" || status === "canceled") return "bg-[#FEE2E2] text-[#DC2626]";
    return "bg-[#EFF6FF] text-primary";
};

const handleDeletePhoneAgent = (item: any) => {
    const conversationId = item?.conversation_id;
    if (!conversationId) {
        uni.$u.toast("会话信息不完整");
        return;
    }
    if (deleting.value) return;

    uni.showModal({
        title: "提示",
        content: "确定删除该会话吗？",
        success: async (res) => {
            if (!res.confirm) return;
            deleting.value = true;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                await deletePhoneAgentConversation({ conversation_id: conversationId });
                uni.showToast({ title: "删除成功", icon: "none" });
                pagingRef.value?.reload();
            } catch (error: any) {
                uni.$u.toast(typeof error === "string" ? error : error?.message || "删除失败");
            } finally {
                deleting.value = false;
                uni.hideLoading();
            }
        },
    });
};
</script>

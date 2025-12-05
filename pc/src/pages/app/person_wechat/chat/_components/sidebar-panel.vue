<template>
    <div class="w-full h-full bg-black flex flex-col py-2">
        <div class="flex items-center justify-center gap-2 mt-2">
            <Icon name="local-icon-wechat" color="#00C800" :size="22"></Icon>
            <span class="text-white font-bold whitespace-nowrap">个微</span>
            <span class="flex-shrink-0 cursor-pointer" v-if="false">
                <Icon name="local-icon-arrow_left_right" color="#ffffff" :size="10"></Icon>
            </span>
        </div>
        <div class="grow min-h-0 mt-4">
            <ElScrollbar>
                <div class="flex flex-col gap-y-1 mt-4 px-2">
                    <div
                        v-for="(item, index) in wechatList"
                        :key="index"
                        class="w-full h-[66px] mx-auto flex items-center justify-center cursor-pointer rounded"
                        :class="{
                            'bg-[#00C800]': currentWechat?.wechat_id === item.wechat_id,
                            'hover:bg-[#00C800]': !item.loading,
                        }"
                        @click="handleSelectWechat(item)">
                        <ElTooltip :content="item.wechat_nickname" placement="right">
                            <div class="relative w-[48px] h-[48px] rounded">
                                <div class="w-full h-full rounded">
                                    <img :src="item.wechat_avatar" alt="" class="w-full h-full rounded" />
                                </div>
                                <div
                                    class="absolute -right-1 -top-1 z-50"
                                    v-if="item.MsgCnt > 0 && item.wechat_status == 1">
                                    <ElBadge
                                        :value="item.MsgCnt"
                                        color="#EA3323"
                                        :badge-style="{
                                            border: 'none',
                                        }">
                                    </ElBadge>
                                </div>
                                <div
                                    class="absolute w-full h-full top-0 left-0 flex items-center justify-center bg-[#BABABAAF] z-40 rounded"
                                    v-if="item.wechat_status != 1">
                                    <Icon name="local-icon-offline" :size="24"></Icon>
                                </div>
                                <div class="absolute top-0 left-0 w-full h-full z-[888]" v-if="item.loading">
                                    <div class="w-full h-full rounded bg-gray-200 animate-pulse"></div>
                                </div>
                            </div>
                        </ElTooltip>
                    </div>
                </div>
            </ElScrollbar>
        </div>
        <div class="flex justify-center mt-2 mx-2" v-if="showAddWeChat">
            <ElButton color="#00C800" class="w-full" @click="handleAddWeChat"> 添加微信 </ElButton>
        </div>
    </div>
    <popup
        v-if="showAddWeChatPop"
        ref="addWeChatPopRef"
        title="添加设备"
        async
        confirm-button-text="确认绑定"
        :confirm-loading="loading"
        @close="deviceId = ''"
        @confirm="confirmAddWeChat">
        <div class="flex flex-col gap-y-4">
            <ElInput v-model="deviceId" placeholder="请输入您的设备授权码" clearable class="!h-[48px]"></ElInput>
        </div>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";

const props = withDefaults(
    defineProps<{
        currentWechat?: any;
        wechatList: any[];
        loading?: boolean;
        showAddWeChat?: boolean;
    }>(),
    {
        currentWechat: null,
        wechatList: () => [],
        loading: false,
        showAddWeChat: true,
    }
);

const emit = defineEmits<{
    (e: "update:currentWechat", value: number): void;
    (e: "addWechat", deviceId: string): void;
}>();

const currentWechat = computed({
    get() {
        return props.currentWechat;
    },
    set(value) {
        emit("update:currentWechat", value);
    },
});

const addWeChatPopRef = ref<InstanceType<typeof Popup>>();
const showAddWeChatPop = ref(false);
const deviceId = ref("");
const handleAddWeChat = async () => {
    showAddWeChatPop.value = true;
    await nextTick();
    addWeChatPopRef.value?.open();
};

const confirmAddWeChat = async () => {
    if (!deviceId.value) {
        feedback.msgError("请输入您的设备授权码");
        return;
    }
    emit("addWechat", deviceId.value);
};

const handleSelectWechat = (item: any) => {
    if (item.loading) return;
    // 如果当前微信状态为离线，则不进行切换
    if (item.wechat_status != 1) {
        feedback.msgError("当前微信状态为离线，请先上线");
        return;
    }
    currentWechat.value = item;
};

defineExpose({
    closeAddWeChatPop: () => {
        showAddWeChatPop.value = false;
        addWeChatPopRef.value?.close();
    },
});
</script>

<style scoped></style>

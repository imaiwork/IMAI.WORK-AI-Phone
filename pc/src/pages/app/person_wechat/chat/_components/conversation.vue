<template>
    <div class="h-full">
        <template v-if="conversationList.length > 0">
            <DynamicScroller
                class="h-full pb-4 dynamic-scroller"
                :items="conversationList"
                :min-item-size="66"
                key-field="UserName"
                @scroll="scroll">
                <template #default="{ item, index, active }">
                    <DynamicScrollerItem :item="item" :active="active">
                        <div
                            class="h-[66px] flex items-center px-4 gap-2 hover:bg-[#D3D3D3] cursor-pointer"
                            :class="{
                                'bg-[#D3D3D3]': currentFriend?.UserName === item.UserName,
                            }"
                            @click="handleClickFriend(item)">
                            <div class="w-[42px] h-[42px] flex-shrink-0 relative">
                                <ElAvatar v-if="item.Avatar" :size="42" shape="square" :src="item.Avatar"> </ElAvatar>
                                <ElAvatar v-else :size="42" shape="square" :icon="Picture"> </ElAvatar>
                                <div class="absolute -top-1 -right-1" v-if="!item.IsSilent && item.UnreadCnt > 0">
                                    <ElBadge
                                        :value="item.UnreadCnt"
                                        color="#EA3323"
                                        :badge-style="{
                                            border: 'none',
                                        }"></ElBadge>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center gap-x-2">
                                    <div class="line-clamp-1 break-all">
                                        {{ item.Memo || item.ShowName }}
                                    </div>
                                    <div>
                                        <span class="text-[#949494] text-xs">{{ formatDate(item.UpdateTime) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-x-3 mt-1">
                                    <div
                                        class="flex-1 w-0 text-xs text-[#949494] text-ellipsis overflow-hidden whitespace-nowrap h-[18px]">
                                        <span class="">
                                            {{ formatDigest(item.Digest) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-x-2">
                                        <div v-if="item.IsSilent">
                                            <Icon name="local-icon-notification_off" color="#c7c7c7"></Icon>
                                        </div>
                                        <div v-if="item.IsTop">
                                            <Icon name="local-icon-zhiding" color="#c7c7c7"></Icon>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </DynamicScrollerItem>
                </template>
            </DynamicScroller>
        </template>
        <div class="flex justify-center items-center h-full" v-else>
            <ElEmpty description="暂无会话" :image-size="100" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import { Picture } from "@element-plus/icons-vue";
import dayjs from "dayjs";
import useHandle from "../../_hooks/useHandle";

const props = withDefaults(
    defineProps<{
        conversationList: any[];
    }>(),
    {
        conversationList: () => [],
    }
);

const emit = defineEmits<{
    (e: "change-friend", friend: any): void;
    (e: "bottom-conversation"): void;
}>();

const { currentFriend } = useHandle();

const handleClickFriend = (friend: any) => {
    if (currentFriend.value?.UserName === friend.UserName) return;
    currentFriend.value = friend;
    emit("change-friend", currentFriend.value);
};

const formatDigest = (digest: string) => {
    if (!digest) {
        return "";
    }
    return digest.substring(digest.indexOf(":") + 1);
};

const formatDate = (date: string) => {
    return dayjs(date).isSame(dayjs(), "day") ? dayjs(date).format("HH:mm") : dayjs(date).format("YYYY/MM/DD");
};

const scroll = (event: any) => {
    const { scrollTop, scrollHeight, clientHeight } = event.target;
    if (scrollTop + clientHeight >= scrollHeight) {
        emit("bottom-conversation");
    }
};
</script>

<style scoped></style>

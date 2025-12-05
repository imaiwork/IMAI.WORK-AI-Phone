<template>
    <div class="h-full">
        <template v-if="friendList.length > 0">
            <DynamicScroller
                class="h-full pb-4 dynamic-scroller"
                :items="friendList"
                :min-item-size="100"
                :emit-update="true"
                key-field="FriendId">
                <template #default="{ item, index, active }">
                    <DynamicScrollerItem
                        :item="item"
                        :active="active"
                        :data-index="index"
                        :data-active="active"
                        :size-dependencies="[item.Remark, item.FriendNick, item.Avatar]">
                        <div
                            class="friend-item"
                            :class="{
                                'bg-[#D3D3D3]': currentFriend?.UserName === item.FriendId,
                            }"
                            @click="handleClickFriend(item)">
                            <div class="flex items-center h-[56px]">
                                <img :src="item.Avatar" alt="avatar" class="w-8 h-8 rounded-full mr-2" />
                                <span>{{ item.Remark || item.FriendNick }}</span>
                            </div>
                        </div>
                        <ElDivider class="!my-2" />
                    </DynamicScrollerItem>
                </template>
            </DynamicScroller>
        </template>
        <template v-else>
            <div class="flex items-center justify-center h-full">
                <ElEmpty description="暂无好友" :image-size="100" />
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import pinyin from "@/utils/pinyin";
import useHandle from "../../_hooks/useHandle";

const props = withDefaults(
    defineProps<{
        friendList: any[];
    }>(),
    {
        friendList: () => [],
    }
);

const emit = defineEmits<{
    (e: "change-friend", friend: any): void;
}>();

const { currentWechat, currentFriend } = useHandle();

const handleClickFriend = (friend: any) => {
    if (currentFriend.value?.UserName === friend.FriendId || currentWechat.value.wechat_id === friend.FriendId) return;
    currentFriend.value = {
        UserName: friend.FriendId,
        Avatar: friend.Avatar,
        ShowName: friend.FriendNick,
        Remark: friend.Remark,
    };
    emit("change-friend", friend);
};
</script>

<style scoped lang="scss">
.friend-item {
    @apply px-4 hover:bg-[#D3D3D3] cursor-pointer;
}
</style>

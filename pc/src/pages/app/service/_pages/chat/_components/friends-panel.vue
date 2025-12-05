<template>
    <div class="w-full h-full bg-white flex flex-col">
        <div class="flex-shrink-0 h-[58px] flex items-center px-2 relative z-20 border-b border-[#E5E5E5]">
            <ElInput
                v-model="search"
                placeholder="搜索"
                class="w-full !h-[32px]"
                clearable
                @input="searchFriend"
                @keyup.enter="searchFriend">
                <template #prepend>
                    <Icon name="el-icon-Search" :size="16"></Icon>
                </template>
            </ElInput>
        </div>
        <div class="grow min-h-0">
            <template v-if="friendPager.friendList.length > 0">
                <div class="h-full">
                    <DynamicScroller
                        class="h-full pb-4 dynamic-scroller"
                        :items="friendPager.friendList"
                        :min-item-size="50">
                        <template #default="{ item, index, active }">
                            <DynamicScrollerItem :item="item" :active="active">
                                <div
                                    class="flex p-4 gap-2 hover:bg-[#D3D3D3] cursor-pointer"
                                    :class="{
                                        'bg-[#D3D3D3]': friendPager.currentFriend?.UserName === item.UserName,
                                    }"
                                    @click="handleClickFriend(item)">
                                    <div class="w-[42px] h-[42px] flex-shrink-0 relative">
                                        <ElAvatar v-if="item.Avatar" :size="42" shape="square" :src="item.Avatar">
                                        </ElAvatar>
                                        <ElAvatar v-else :size="42" shape="square" :icon="Picture"> </ElAvatar>
                                        <div
                                            class="absolute -top-1 -right-1"
                                            v-if="!item.IsSilent && item.UnreadCnt > 0">
                                            <ElBadge
                                                :value="item.UnreadCnt"
                                                color="#EA3323"
                                                :badge-style="{
                                                    border: 'none',
                                                }"></ElBadge>
                                        </div>
                                    </div>
                                    <div class="flex-1 h-full">
                                        <div class="flex justify-between items-center gap-x-2">
                                            <div class="line-clamp-1 break-all">
                                                {{ item.Memo || item.ShowName }}
                                            </div>
                                            <div>
                                                <span class="text-[#949494] text-xs">{{
                                                    formatDate(item.UpdateTime)
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </DynamicScrollerItem>
                        </template>
                    </DynamicScroller>
                </div>
            </template>
            <template v-else>
                <div class="flex items-center justify-center h-full">
                    <ElEmpty description="暂无好友" :image-size="100" />
                </div>
            </template>
        </div>
        <div class="flex-shrink-0 p-4 flex items-center justify-center" v-if="false">
            <ElButton text>
                <Icon name="el-icon-Refresh" :size="16"></Icon>
                <span class="text-[#86909C]">刷新</span>
            </ElButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Picture } from "@element-plus/icons-vue";
import { DynamicScroller, DynamicScrollerItem } from "vue-virtual-scroller";
import "vue-virtual-scroller/dist/vue-virtual-scroller.css";
import dayjs from "dayjs";
import useFriends from "../../../_hooks/useFriends";

const { friendPager, getFriendList } = useFriends();

const search = ref<string>("");

const searchFriend = () => {
    console.log(search.value);
};

const handleClickFriend = (friend: any) => {
    console.log(friend);
};

const formatDate = (date: string) => {
    return dayjs(date).isSame(dayjs(), "day") ? dayjs(date).format("HH:mm") : dayjs(date).format("YYYY/MM/DD");
};

const scroll = (event: any) => {
    console.log(event);
};

getFriendList();
</script>

<style scoped lang="scss">
:deep(.el-input-group__prepend) {
    padding: 0 10px;
    box-shadow: none;
    background-color: #f0f0f0;
}
:deep(.el-input__wrapper) {
    box-shadow: none;
    padding: 0;
    padding-right: 10px;
    background-color: #f0f0f0;
}
:deep(.el-input__inner) {
    --el-input-inner-height: 32px;
}
</style>

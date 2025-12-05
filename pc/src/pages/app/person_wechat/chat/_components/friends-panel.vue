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
        <div
            class="grow min-h-0"
            :class="{
                'bg-[#F7F7F7]': [FriendPanelEnum.Friend, FriendPanelEnum.Group].includes(currentPanel),
            }">
            <div class="h-full">
                <component
                    v-bind="{
                        conversationList: localConversationList,
                        friendList: localFriendList,
                    }"
                    :is="panelComponent[currentPanel].component"
                    @bottom-conversation="emit('bottom-conversation')"
                    @change-friend="handleChangeFriend"></component>
            </div>
        </div>
        <div class="h-[65px] shadow-lighter flex items-center flex-shrink-0">
            <div
                class="flex-1 flex items-center justify-center flex-col"
                v-for="(item, key) in panelComponent"
                :key="key">
                <div
                    class="flex items-center justify-center flex-col cursor-pointer"
                    :class="{
                        'text-[#07C160]': currentPanel === key,
                    }"
                    @click="handleClickPanel(key)">
                    <Icon
                        :name="`local-icon-${currentPanel === key ? item.activeIcon : item.icon}`"
                        :color="currentPanel === key ? '#07C160' : '#11170B'"
                        :size="20"></Icon>
                    <span class="mt-1">{{ item.title }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Conversation from "./conversation.vue";
import Friends from "./friends.vue";
import Group from "./group.vue";
import { FriendPanelEnum } from "../../_enums";
import useHandle from "../../_hooks/useHandle";

const props = withDefaults(
    defineProps<{
        currentPanel: FriendPanelEnum;
    }>(),
    {
        currentPanel: FriendPanelEnum.Dialogue,
    }
);

const emit = defineEmits<{
    (e: "change-panel", key: FriendPanelEnum): void;
    (e: "change-friend", friend: any): void;
    (e: "bottom-friend"): void;
    (e: "bottom-conversation"): void;
}>();

const { currentFriend, friendList, conversationList } = useHandle();

const currentPanel = computed({
    get() {
        return props.currentPanel;
    },
    set(value: FriendPanelEnum) {
        emit("change-panel", value);
    },
});

const search = ref("");
const panelComponent = computed(() => {
    return {
        [FriendPanelEnum.Dialogue]: {
            component: Conversation,
            title: "对话",
            icon: "wx_dialogue",
            activeIcon: "wx_dialogue_active",
        },
        [FriendPanelEnum.Friend]: {
            component: Friends,
            title: "好友",
            icon: "wx_friends",
            activeIcon: "wx_friends_active",
        },
        // [FriendPanelEnum.Group]: {
        // 	component: Group,
        // 	title: "群聊",
        // 	icon: "wx_group",
        // 	activeIcon: "wx_group_active",
        // },
    };
});

const localConversationList = ref<any[]>(conversationList.value);
const localFriendList = ref<any[]>(friendList.value);

const searchFriend = () => {
    const searchValue = search.value.trim();
    const filterList = (list: any[], key: string) => list.filter((item) => item[key].includes(searchValue));

    if (currentPanel.value === FriendPanelEnum.Dialogue) {
        localConversationList.value = searchValue
            ? filterList(conversationList.value, "ShowName")
            : conversationList.value;
    } else if (currentPanel.value === FriendPanelEnum.Friend) {
        localFriendList.value = searchValue ? filterList(friendList.value, "FriendNick") : friendList.value;
    }
};

const handleClickPanel = (key: FriendPanelEnum) => {
    currentPanel.value = key;
};

const handleChangeFriend = (friend: any) => {
    emit("change-friend", friend);
};

watchEffect(() => {
    localConversationList.value = conversationList.value;
    localFriendList.value = friendList.value;
});
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
